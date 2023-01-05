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

namespace Bd808\Bash\Pages;

use Bd808\Bash\Auth\OAuthUserManager;
use Bd808\Bash\Page;
use MediaWiki\OAuthClient\Client;
use MediaWiki\OAuthClient\Token;
use Wikimedia\Slimapp\Auth\AuthManager;

/**
 * @author Bryan Davis <bd808@wikimedia.org>
 * @copyright © 2015 Bryan Davis and contributors.
 */
class OAuth extends Page {

	public const REQEST_KEY = 'oauthreqtoken';

	/**
	 * @var Client
	 */
	protected $oauth;

	/**
	 * @var OAuthUserManager
	 */
	protected $manager;

	/**
	 * Set OAuth client.
	 *
	 * @param Client $oauth
	 */
	public function setOAuth( Client $oauth ) {
		$this->oauth = $oauth;
	}

	/**
	 * Set user manager object.
	 *
	 * @param OAuthUserManager $manager
	 */
	public function setUserManager( OAuthUserManager $manager ) {
		$this->manager = $manager;
	}

	/**
	 * Handle GET requests.
	 *
	 * @param string $stage
	 */
	protected function handleGet( $stage ) {
		switch ( $stage ) {
			case 'callback':
				$this->handleCallback();
				break;

			default:
				$this->handleInitiate();
				break;
		}
	}

	/**
	 * Initiate OAuth handshake and redirect user to OAuth server to authorize
	 * the app.
	 */
	protected function handleInitiate() {
		list( $next, $token ) = $this->oauth->initiate();
		$_SESSION[self::REQEST_KEY] = "{$token->key}:{$token->secret}";
		$this->redirect( $next );
	}

	/**
	 * Process the return result from a user authorizing our app.
	 */
	protected function handleCallback() {
		$next = false;
		if ( isset( $_SESSION[AuthManager::NEXTPAGE_SESSION_KEY] ) ) {
			$next = $_SESSION[AuthManager::NEXTPAGE_SESSION_KEY];
			$next = filter_var( $next, \FILTER_VALIDATE_URL, \FILTER_FLAG_PATH_REQUIRED );
		}

		if ( !isset( $_SESSION[self::REQEST_KEY] ) ) {
			$this->flash( 'error',
				$this->msg( 'oauth-finish-nosession' )->toString()
			);
			$this->redirect( $this->urlFor( 'login' ) );
		}

		list( $key, $secret ) = explode( ':', $_SESSION[self::REQEST_KEY] );
		unset( $_SESSION[self::REQEST_KEY] );
		$token = new Token( $key, $secret );

		$this->form->requireString( 'oauth_verifier' );
		$this->form->requireInArray( 'oauth_token', [ $key ] );

		if ( $this->form->validate( filter_input_array( INPUT_GET ) ) ) {
			$verifyCode = $this->form->get( 'oauth_verifier' );
			try {
				$accessToken = $this->oauth->complete( $token, $verifyCode );
				$user = $this->manager->getUserData( $accessToken );
				$this->authManager->login( $user );

				$this->flash( 'info',
					$this->msg( 'oauth-finish-success' )->toString()
				);
			} catch ( \Exception $e ) {
				$this->flash( 'error',
					$this->msg( 'oauth-finish-fail' )->toString()
				);
				$this->log->error( 'Failed login attempt', [
					'exception' => $e
				] );
			}
			$this->redirect( $next ?: $this->urlFor( 'home' ) );

		} else {
			$this->flash( 'error',
				$this->msg( 'oauth-finish-fail' )->toString()
			);
		}

		$this->redirect( $this->urlFor( 'login' ) );
	}
}
