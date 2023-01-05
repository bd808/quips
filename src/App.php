<?php
/**
 * This file is part of bd808's bash application
 * Copyright (C) 2015  Bryan Davis and contributors
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Bd808\Bash;

use Bd808\Bash\Auth\OAuthUserManager;
use Wikimedia\SimpleI18n\I18nContext;
use Wikimedia\SimpleI18n\JsonCache;
use Wikimedia\Slimapp\AbstractApp;
use Wikimedia\Slimapp\Auth\AuthManager;
use Wikimedia\Slimapp\Config;
use Wikimedia\Slimapp\Form;
use Wikimedia\Slimapp\Mailer;
use Wikimedia\Slimapp\ParsoidClient;
use Wikimedia\Slimapp\TwigExtension;

/**
 * @author Bryan Davis <bd808@wikimedia.org>
 * @copyright © 2015 Bryan Davis and contributors.
 */
class App extends AbstractApp {

	/**
	 * Apply settings to the Slim application.
	 *
	 * @param \Slim\Slim $slim Application
	 */
	protected function configureSlim( \Slim\Slim $slim ) {
		$slim->config( [
			'parsoid.url' => Config::getStr( 'PARSOID_URL',
				'http://parsoid-lb.eqiad.wikimedia.org/enwiki/'
			),
			'parsoid.cache' => Config::getStr( 'CACHE_DIR',
				"{$this->deployDir}/data/cache"
			),
			'es.url' => Config::getStr( 'ES_URL', 'http://127.0.0.1:9200/' ),
			'es.user' => Config::getStr( 'ES_USER', '' ),
			'es.password' => Config::getStr( 'ES_PASSWORD', '' ),
			'can.edit' => Config::getBool( 'CAN_EDIT', false ),
			'can.vote' => Config::getBool( 'CAN_VOTE', false ),
			'oauth.enable' => Config::getBool( 'USE_OAUTH', false ),
			'oauth.consumer_token' => Config::getStr( 'OAUTH_CONSUMER_TOKEN', '' ),
			'oauth.secret_token' => Config::getStr( 'OAUTH_SECRET_TOKEN', '' ),
			'oauth.endpoint' => Config::getStr( 'OAUTH_ENDPOINT', '' ),
			'oauth.redir' => Config::getStr( 'OAUTH_REDIR', '' ),
			'oauth.callback' => Config::getStr( 'OAUTH_CALLBACK', '' ),

		] );

		$slim->configureMode( 'production', static function () use ( $slim ) {
			$slim->config( [
				'debug' => false,
				'log.level' => Config::getStr( 'LOG_LEVEL', 'INFO' ),
			] );

			// Install a custom error handler
			$slim->error( static function ( \Exception $e ) use ( $slim ) {
				$errorId = substr( session_id(), 0, 8 ) . '-' .
					substr( uniqid(), -8 );
				$slim->log->critical( $e->getMessage(), [
					'exception' => $e,
					'errorId' => $errorId,
				] );
				$slim->view->set( 'errorId', $errorId );
				$slim->render( 'error.html' );
			} );
		} );

		$slim->configureMode( 'development', static function () use ( $slim ) {
			$slim->config( [
				'debug' => true,
				'log.level' => Config::getStr( 'LOG_LEVEL', 'DEBUG' ),
				'view.cache' => false,
			] );
		} );
	}

	/**
	 * Configure inversion of control/dependency injection container.
	 *
	 * @param \Slim\Helper\Set $container IOC container
	 */
	protected function configureIoc( \Slim\Helper\Set $container ) {
		$container->singleton( 'i18nCache', static function ( $c ) {
			return new JsonCache(
				$c->settings['i18n.path'], $c->log
			);
		} );

		$container->singleton( 'i18nContext', static function ( $c ) {
			return new I18nContext(
				$c->i18nCache, $c->settings['i18n.default'], $c->log
			);
		} );

		$container->singleton( 'mailer',  static function ( $c ) {
			return new Mailer(
				[ 'Host' => $c->settings['smtp.host'] ],
				$c->log
			);
		} );

		$container->singleton( 'parsoid', static function ( $c ) {
			return new ParsoidClient(
				$c->settings['parsoid.url'],
				$c->settings['parsoid.cache'],
				$c->log
			);
		} );

		$container->singleton( 'quips', static function ( $c ) {
			$settings = [
				'url' => $c->settings['es.url'],
				'log' => true,
			];
			if ( $c->settings['es.user'] !== '' ) {
				$creds = base64_encode(
					$c->settings['es.user'] . ':' .
					$c->settings['es.password']
				);
				$settings['headers'] = [
					'Authorization' => "Basic {$creds}",
				];
			}
			$client = new \Elastica\Client( $settings );
			$client->setLogger( $c->log );
			return new Quips( $client, $c->log );
		} );

		$container->singleton( 'oauthConfig', static function ( $c ) {
			$conf = new \MediaWiki\OAuthClient\ClientConfig(
				$c->settings['oauth.endpoint']
			);
			$conf->setRedirURL( $c->settings['oauth.redir'] );
			$conf->setConsumer( new \MediaWiki\OAuthClient\Consumer(
				$c->settings['oauth.consumer_token'],
				$c->settings['oauth.secret_token']
			) );
			return $conf;
		} );

		$container->singleton( 'oauthClient', static function ( $c ) {
			$client = new \MediaWiki\OAuthClient\Client(
				$c->oauthConfig,
				$c->log
			);
			$client->setCallback( $c->settings['oauth.callback'] );
			return $client;
		} );

		$container->singleton( 'userManager', static function ( $c ) {
			return new OAuthUserManager(
				$c->oauthClient,
				$c->log
			);
		} );

		$container->singleton( 'authManager', static function ( $c ) {
			return new AuthManager( $c->userManager );
		} );

		// TODO: figure out where to send logs
	}

	/**
	 * Configure view behavior.
	 *
	 * @param \Slim\View $view Default view
	 */
	protected function configureView( \Slim\View $view ) {
		$view->parserOptions = [
			'charset' => 'utf-8',
			'cache' => $this->slim->config( 'view.cache' ),
			'debug' => $this->slim->config( 'debug' ),
			'auto_reload' => true,
			'strict_variables' => false,
			'autoescape' => true,
		];

		// Install twig parser extensions
		$view->parserExtensions = [
			new \Slim\Views\TwigExtension(),
			new TwigExtension( $this->slim->parsoid ),
			new \Wikimedia\SimpleI18n\TwigExtension( $this->slim->i18nContext ),
			new \Twig_Extension_Debug(),
		];

		// Set default view data
		$view->replace( [
			'app' => $this->slim,
			'i18nCtx' => $this->slim->i18nContext,
		] );
	}

	/**
	 * Configure routes to be handled by application.
	 *
	 * @param \Slim\Slim $slim Application
	 */
	protected function configureRoutes( \Slim\Slim $slim ) {
		$middleware = [
			'must-revalidate' => static function () use ( $slim ) {
				$slim->response->headers->set(
					'Cache-Control', 'private, must-revalidate, max-age=0'
				);
				$slim->response->headers->set(
					'Expires', 'Thu, 01 Jan 1970 00:00:00 GMT'
				);
			},

			'inject-user' => static function () use ( $slim ) {
				$user = $slim->authManager->getUserData();
				$slim->view->set( 'user', $user );
			},

			'require-user' => static function () use ( $slim ) {
				if ( $slim->authManager->isAnonymous() ) {
					if ( $slim->request->isGet() ) {
						$uri = $slim->request->getUrl() .
							$slim->request->getPath();
						$qs = Form::qsMerge();
						if ( $qs ) {
							$uri = "{$uri}?{$qs}";
						}
						$_SESSION[AuthManager::NEXTPAGE_SESSION_KEY] = $uri;
					}
					// FIXME: use i18n
					$slim->flash( 'error', 'Login required' );
					$slim->flashKeep();
					$slim->redirect( $slim->urlFor( 'login' ) );
				}
			},
		];

		$slim->group( '/',
			$middleware['inject-user'],
			static function () use ( $slim, $middleware ) {
				App::redirect( $slim, '', 'random', 'home' );
				App::redirect( $slim, 'index', 'random' );

				$slim->get( 'random', static function () use ( $slim ) {
					$page = new Pages\Random( $slim );
					$page->setI18nContext( $slim->i18nContext );
					$page->setQuips( $slim->quips );
					$page();
				} )->name( 'random' );

				$slim->get( 'random.json', static function () use ( $slim ) {
					$slim->response->headers->set(
						'Content-Type',
						'application/json'
					);
					$page = new Pages\Random( $slim );
					$page->setI18nContext( $slim->i18nContext );
					$page->setQuips( $slim->quips );
					$page->setTemplate( 'random.json' );
					$page();
				} )->name( 'random.json' );

				$slim->get( 'search', static function () use ( $slim ) {
					$page = new Pages\Search( $slim );
					$page->setI18nContext( $slim->i18nContext );
					$page->setQuips( $slim->quips );
					$page();
				} )->name( 'search' );

				$slim->get( 'top', static function () use ( $slim ) {
					$page = new Pages\Top( $slim );
					$page->setI18nContext( $slim->i18nContext );
					$page->setQuips( $slim->quips );
					$page();
				} )->name( 'top' );

				App::template( $slim, 'login' );

				$slim->get( 'logout',
					$middleware['must-revalidate'],
					static function () use ( $slim ) {
						$slim->authManager->logout();
						$slim->redirect( $slim->urlFor( 'home' ) );
					}
				)->name( 'logout' );

				App::template( $slim, 'about' );
				App::template( $slim, 'help' );
			}
		);

		$slim->group( '/quip/',
			$middleware['inject-user'],
			static function () use ( $slim, $middleware ) {
				$slim->get( ':id', static function ( $id ) use ( $slim ) {
					$page = new Pages\Quip( $slim );
					$page->setI18nContext( $slim->i18nContext );
					$page->setQuips( $slim->quips );
					$page( $id );
				} )->name( 'quip' );

				$slim->get( ':id/edit',
					$middleware['require-user'],
					static function ( $id ) use ( $slim ) {
						$page = new Pages\Edit( $slim );
						$page->setI18nContext( $slim->i18nContext );
						$page->setQuips( $slim->quips );
						$page( $id );
					}
				)->name( 'edit' );

				$slim->post( ':id/post',
					$middleware['require-user'],
					static function ( $id ) use ( $slim ) {
						$page = new Pages\Edit( $slim );
						$page->setI18nContext( $slim->i18nContext );
						$page->setQuips( $slim->quips );
						$page( $id );
					}
				)->name( 'edit_post' );

				$slim->post( ':id/delete',
					$middleware['require-user'],
					static function ( $id ) use ( $slim ) {
						$page = new Pages\Delete( $slim );
						$page->setI18nContext( $slim->i18nContext );
						$page->setQuips( $slim->quips );
						$page( $id );
					}
				)->name( 'delete_post' );

				$slim->post( ':id/vote',
					$middleware['require-user'],
					static function ( $id ) use ( $slim ) {
						$page = new Pages\Vote( $slim );
						$page->setI18nContext( $slim->i18nContext );
						$page->setQuips( $slim->quips );
						$page( $id );
					}
				)->name( 'vote_post' );
			}
		);

		$slim->group( '/oauth/',
			static function () use ( $slim ) {
				$slim->get( '', static function () use ( $slim ) {
					$page = new Pages\OAuth( $slim );
					$page->setOAuth( $slim->oauthClient );
					$page( 'init' );
				} )->name( 'oauth_init' );

				$slim->get( 'callback', static function () use ( $slim ) {
					$page = new Pages\OAuth( $slim );
					$page->setI18nContext( $slim->i18nContext );
					$page->setOAuth( $slim->oauthClient );
					$page->setUserManager( $slim->userManager );
					$page( 'callback' );
				} )->name( 'oauth_callback' );
			}
		);

		$slim->notFound( static function () use ( $slim ) {
			$slim->render( '404.html' );
		} );
	}
}
