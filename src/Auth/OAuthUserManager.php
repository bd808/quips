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

namespace Bd808\Bash\Auth;

use MediaWiki\OAuthClient\Client;
use MediaWiki\OAuthClient\Token;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Wikimedia\Slimapp\Auth\UserManager;

/**
 * @author Bryan Davis <bd808@wikimedia.org>
 * @copyright © 2015 Bryan Davis and contributors.
 */
class OAuthUserManager implements LoggerAwareInterface, UserManager {

	/**
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * @var Client
	 */
	protected $oauth;

	/**
	 * @param Client|null $client
	 * @param LoggerInterface|null $logger
	 */
	public function __construct(
		Client $client = null,
		LoggerInterface $logger = null
	) {
		$this->setOAuth( $client );
		$this->setLogger( $logger );
	}

	/**
	 * @param LoggerInterface $logger
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	/**
	 * @param Client $oauth
	 */
	public function setOAuth( Client $oauth ) {
		$this->oauth = $oauth;
	}

	/**
	 * Get a user by accessToken.
	 *
	 * @param Token $accessToken
	 * @return UserData
	 */
	public function getUserData( $accessToken ) {
		$ident = $this->oauth->identify( $accessToken );
		return new OAuthUser( $accessToken, $ident );
	}
}
