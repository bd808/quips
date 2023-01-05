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

use MediaWiki\OAuthClient\Token;
use Wikimedia\Slimapp\Auth\UserData;

/**
 * @author Bryan Davis <bd808@wikimedia.org>
 * @copyright © 2015 Bryan Davis and contributors.
 */
class OAuthUser implements UserData {

	/**
	 * @var Token
	 */
	protected $token;

	/**
	 * @var stdClass
	 */
	protected $attributes;

	/**
	 * Constructor.
	 *
	 * @param Token $token
	 * @param stdClass $attributes
	 */
	public function __construct( Token $token, $attributes ) {
		$this->token = $token;
		$this->attributes = $attributes;
	}

	/**
	 * Get user's unique numeric id.
	 * @return int
	 */
	public function getId() {
		return $this->attributes->sub;
	}

	/**
	 * Get username
	 * @return string
	 */
	public function getName() {
		return $this->attributes->username;
	}

	/**
	 * Get user's password.
	 * @return string
	 */
	public function getPassword() {
		return null;
	}

	/**
	 * Is this user blocked from logging into the application?
	 * @return bool True if user should not be allowed to log in to the
	 *   application, false otherwise
	 */
	public function isBlocked() {
		return false;
	}
}
