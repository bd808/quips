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

use Bd808\Bash\Page;

/**
 * @author Bryan Davis <bd808@wikimedia.org>
 * @copyright © 2015 Bryan Davis and contributors.
 */
class Vote extends Page {

	protected function handlePost( $id ) {
		$this->form->requireInArray( 'vote', array( 'up', 'down' ) );
		if ( $this->form->validate() ) {
			$ok = $this->quips->vote( $id, $this->form->get( 'vote' ) );
		} else {
			$ok = false;
		}

		$this->view->set( 'ok', $ok );
		$this->slim->response->header(
			'Content-Type', 'application/json; charset=UTF-8'
		);
		$this->render( 'quip/vote.json' );
	}
}
