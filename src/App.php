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

use Wikimedia\SimpleI18n\I18nContext;
use Wikimedia\SimpleI18n\JsonCache;
use Wikimedia\Slimapp\AbstractApp;
use Wikimedia\Slimapp\Config;
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
		$slim->config( array(
			'parsoid.url' => Config::getStr( 'PARSOID_URL',
				'http://parsoid-lb.eqiad.wikimedia.org/enwiki/'
			),
			'parsoid.cache' => Config::getStr( 'CACHE_DIR',
				"{$this->deployDir}/data/cache"
			),
			'es.url' => Config::getStr( 'ES_URL', 'http://127.0.0.1:9200/' ),
			'can.edit' => Config::getBool( 'CAN_EDIT', false ),
			'can.vote' => Config::getBool( 'CAN_VOTE', false ),
		) );

		$slim->configureMode( 'production', function () use ( $slim ) {
			$slim->config( array(
				'debug' => false,
				'log.level' => Config::getStr( 'LOG_LEVEL', 'INFO' ),
			) );

			// Install a custom error handler
			$slim->error( function ( \Exception $e ) use ( $slim ) {
				$errorId = substr( session_id(), 0, 8 ) . '-' .
					substr( uniqid(), -8 );
				$slim->log->critical( $e->getMessage(), array(
					'exception' => $e,
					'errorId' => $errorId,
				) );
				$slim->view->set( 'errorId', $errorId );
				$slim->render( 'error.html' );
			} );
		} );

		$slim->configureMode( 'development', function () use ( $slim ) {
			$slim->config( array(
				'debug' => true,
				'log.level' => Config::getStr( 'LOG_LEVEL', 'DEBUG' ),
				'view.cache' => false,
			) );
		} );
	}


	/**
	 * Configure inversion of control/dependency injection container.
	 *
	 * @param \Slim\Helper\Set $container IOC container
	 */
	protected function configureIoc( \Slim\Helper\Set $container ) {
		$container->singleton( 'i18nCache', function ( $c ) {
			return new JsonCache(
				$c->settings['i18n.path'], $c->log
			);
		} );

		$container->singleton( 'i18nContext', function ( $c ) {
			return new I18nContext(
				$c->i18nCache, $c->settings['i18n.default'], $c->log
			);
		} );

		$container->singleton( 'mailer',  function ( $c ) {
			return new Mailer(
				array( 'Host' => $c->settings['smtp.host'] ),
				$c->log
			);
		} );

		$container->singleton( 'parsoid', function ( $c ) {
			return new ParsoidClient(
				$c->settings['parsoid.url'],
				$c->settings['parsoid.cache'],
				$c->log
			);
		} );

		$container->singleton( 'quips', function ( $c ) {
			$client = new \Elastica\Client( array(
				'url' => $c->settings['es.url'],
				'log' => true,
			) );
			$client->setLogger( $c->log );
			return new Quips( $client, $c->log );
		} );

		// TODO: figure out where to send logs
	}


	/**
	 * Configure view behavior.
	 *
	 * @param \Slim\View $view Default view
	 */
	protected function configureView( \Slim\View $view ) {
		$view->parserOptions = array(
			'charset' => 'utf-8',
			'cache' => $this->slim->config( 'view.cache' ),
			'debug' => $this->slim->config( 'debug' ),
			'auto_reload' => true,
			'strict_variables' => false,
			'autoescape' => true,
		);

		// Install twig parser extensions
		$view->parserExtensions = array(
			new \Slim\Views\TwigExtension(),
			new TwigExtension( $this->slim->parsoid ),
			new \Wikimedia\SimpleI18n\TwigExtension( $this->slim->i18nContext ),
			new \Twig_Extension_Debug(),
		);

		// Set default view data
		$view->replace( array(
			'app' => $this->slim,
			'i18nCtx' => $this->slim->i18nContext,
		) );
	}


	/**
	 * Configure routes to be handled by application.
	 *
	 * @param \Slim\Slim $slim Application
	 */
	protected function configureRoutes( \Slim\Slim $slim ) {
		$slim->group( '/', function () use ( $slim ) {
			App::redirect( $slim, '', 'random', 'home' );
			App::redirect( $slim, 'index', 'random' );

			$slim->get( 'random', function () use ( $slim ) {
				$page = new Pages\Random( $slim );
				$page->setI18nContext( $slim->i18nContext );
				$page->setQuips( $slim->quips );
				$page();
			} )->name( 'random' );

			$slim->get( 'search', function () use ( $slim ) {
				$page = new Pages\Search( $slim );
				$page->setI18nContext( $slim->i18nContext );
				$page->setQuips( $slim->quips );
				$page();
			} )->name( 'search' );

			App::template( $slim, 'about' );
		} ); //end group '/'

		$slim->group( '/quip/', function () use ( $slim ) {
			$slim->get( ':id', function ( $id ) use ( $slim ) {
				$page = new Pages\Quip( $slim );
				$page->setI18nContext( $slim->i18nContext );
				$page->setQuips( $slim->quips );
				$page( $id );
			} )->name( 'quip' );

			$slim->get( ':id/edit', function ( $id ) use ( $slim ) {
				$page = new Pages\Edit( $slim );
				$page->setI18nContext( $slim->i18nContext );
				$page->setQuips( $slim->quips );
				$page( $id );
			} )->name( 'edit' );

			$slim->post( ':id/post', function ( $id ) use ( $slim ) {
				$page = new Pages\Edit( $slim );
				$page->setI18nContext( $slim->i18nContext );
				$page->setQuips( $slim->quips );
				$page( $id );
			} )->name( 'edit_post' );

			$slim->post( ':id/delete', function ( $id ) use ( $slim ) {
				$page = new Pages\Delete( $slim );
				$page->setI18nContext( $slim->i18nContext );
				$page->setQuips( $slim->quips );
				$page( $id );
			} )->name( 'delete_post' );

			$slim->post( ':id/vote', function ( $id ) use ( $slim ) {
				$page = new Pages\Vote( $slim );
				$page->setI18nContext( $slim->i18nContext );
				$page->setQuips( $slim->quips );
				$page( $id );
			} )->name( 'vote_post' );
		} );

		$slim->notFound( function () use ( $slim ) {
			$slim->render( '404.html' );
		} );
	} //end configureRoutes
}
