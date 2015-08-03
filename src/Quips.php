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

use Elastica\Client;
use Elastica\Query;
use Elastica\Query\FunctionScore;
use Elastica\Query\Ids;
use Elastica\Query\SimpleQueryString;
use Elastica\ResultSet;
use Elastica\Search;
use Psr\Log\LoggerInterface;

/**
 * @author Bryan Davis <bd808@wikimedia.org>
 * @copyright © 2015 Bryan Davis and contributors.
 */
class Quips {
	/**
	 * @var Client $client
	 */
	protected $client;

	/**
	 * @var LoggerInterface $logger
	 */
	protected $logger;

	public function __construct(
		Client $client, LoggerInterface $logger = null
	) {
		$this->client = $client;
		$this->logger = $logger ?: new \Psr\Log\NullLogger();
	}

	/**
	 * Run a search.
	 *
	 * @param Query $q
	 * @return ResultSet
	 */
	protected function doSearch( Query $q ) {
		$search = new Search( $this->client );
		$search->addIndex( 'bash' )->addType( 'bash' );
		$search->setQuery( $q );
		return $search->search();
	}

	/**
	 * Get a random quip
	 *
	 * @return ResultSet
	 */
	public function getRandom() {
		$fs = new FunctionScore();
		$fs->setRandomScore();
		$query = new Query( $fs );
		$query->setFrom( 0 )
			->setSize( 1 )
			->setFields( array( 'message' ) );
		return $this->doSearch( $query );
	}

	/**
	 * Get a quip
	 *
	 * @param string $id
	 * @return ResultSet
	 */
	public function getQuip( $id ) {
		$ids = new Ids();
		$ids->setIds( $id );
		$query = new Query( $ids );
		$query->setFrom( 0 )
			->setSize( 1 )
			->setFields( array( 'message' ) );
		return $this->doSearch( $query );
	}

	/**
	 * Search for quips
	 *
	 * @param array $params Search parameters:
	 *   - query: Elasticsearch simple query string
	 *   - items: Number of results to return per page
	 *   - page: Page of results to return (0-index)
	 * @return ResultSet
	 */
	public function search( array $params = array() ) {
		$params = array_merge( array(
			'query' => null,
			'items' => 20,
			'page' => 0,
		), $params );

		if ( $params['query'] !== null ) {
			$qs = new SimpleQueryString( $params['query'], array( 'message' ) );
			$query = new Query( $qs );
		} else {
			$query = new Query();
		}
		$query->setFrom( $params['page'] * $params['items'] )
			->setSize( $params['items'] )
			->setFields( array( 'message' ) )
			->setSort( array( '@timestamp' => array( 'order' => 'desc' ) ) );
		return $this->doSearch( $query );
	}
}
