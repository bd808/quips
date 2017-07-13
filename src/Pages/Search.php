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
class Search extends Page {
	protected function handleGet() {
		$this->form->expectString( 'q' );
		$this->form->expectInt( 'p',
			[ 'min_range' => 0, 'default' => 0 ]
		);
		$this->form->expectInt( 'i',
			[ 'min_range' => 1, 'max_range' => 200, 'default' => 20 ]
		);
		$this->form->validate( $_GET );

		$params = [
			'query' => $this->form->get( 'q' ),
			'page' => $this->form->get( 'p' ),
			'items' => $this->form->get( 'i' ),
		];
		$ret = $this->quips->search( $params );
		list( $pageCount, $first, $last ) = $this->pagination(
			$ret->getTotalHits(), $params['page'], $params['items'] );

		$this->view->set( 'q', $this->form->get( 'q' ) );
		$this->view->set( 'p', $this->form->get( 'p' ) );
		$this->view->set( 'i', $this->form->get( 'i' ) );
		$this->view->set( 'results', $ret );
		$this->view->set( 'pages', $pageCount );
		$this->view->set( 'left', $first );
		$this->view->set( 'right', $last );

		$this->render( 'search.html' );
	}
}
