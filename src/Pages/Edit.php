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

use Elastica\Result;

/**
 * @author Bryan Davis <bd808@wikimedia.org>
 * @copyright © 2015 Bryan Davis and contributors.
 */
class Edit extends Page {
	protected function setupForm ( $defaults = array() ) {
		$defaults = array_merge( array(
			'@timestamp' => date( 'c' ),
			'nick' => 'anonymous',
			'message' => null,
			'up_votes' => 0,
			'down_votes' => 0,
			'score' => 0,
			'tags' => array(),
		), $defaults );

		$this->form->expectString( '@timestamp', array(
			'default' => $defaults['@timestamp'],
		) );
		$this->form->expectString( 'nick', array(
			'default' => $defaults['nick'],
		) );
		$this->form->requireString( 'message', array(
			'default' => $defaults['message'],
		) );
		$this->form->expectInt( 'up_votes', array(
			'min' => 0,
			'default' => $defaults['up_votes'],
		) );
		$this->form->expectInt( 'down_votes', array(
			'min' => 0,
			'default' => $defaults['down_votes'],
		) );
		$this->form->expectInt( 'score', array(
			'min' => 0,
			'default' => $defaults['score'],
		) );
		$this->form->expectStringArray( 'tags', array(
			'default' => $defaults['tags'],
		) );

		$this->view->set( 'form', $this->form );
	}

	protected function handleGet( $id ) {
		$defaults = array();
		if ( $id !== 'new' ) {
			$defaults = array_map(
				function ( $v ) {
					if ( is_array( $v ) && count( $v ) === 1 ) {
						return $v[0];
					}
					return $v;
				},
				$this->quips->getQuip( $id )[0]->getData()
			);
		}
		$this->setupForm( $defaults );

		$this->view->set( 'id', $id );
		$this->render( 'quip/edit.html' );
	}

	protected function handlePost( $id ) {
		$this->setupForm();
		$redir = $this->urlFor( 'edit', array( 'id' => $id ) );

		if ( $this->form->validate() ) {
			$id = $this->quips->save( $id, array(
				'@timestamp' => $this->form->get( '@timestamp' ),
				'nick' => $this->form->get( 'nick' ),
				'message' => $this->form->get( 'message' ),
				'up_votes' => $this->form->get( 'up_votes' ),
				'down_votes' => $this->form->get( 'down_votes' ),
				'score' => $this->form->get( 'score' ),
				'tags' => $this->form->get( 'tags' ),
			) );

			if ( $id !== false ) {
				$this->flash( 'info', $this->msg( 'quips-edit-save' )->toString() );
				$redir = $this->urlFor( 'quip', array( 'id' => $id ) );

			} else {
				$this->flash( 'error', $this->msg( 'quips-edit-save-error' )->toString() );
			}

		} else {
			$this->flash( 'error', $this->msg( 'quips-edit-error' )->toString() );
		}
		$this->redirect( $redir );
	}
}
