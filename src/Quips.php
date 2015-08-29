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
use Elastica\Document;
use Elastica\Query;
use Elastica\Query\FunctionScore;
use Elastica\Query\Ids;
use Elastica\Query\SimpleQueryString;
use Elastica\Result;
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
	 * Fields to fetch from the index.
	 */
	protected function defaultFields() {
		return array(
			'id',
			'@timestamp',
			'nick',
			'message',
			'up_votes',
			'down_votes',
			'score',
			'tags',
		);
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
			->setFields( $this->defaultFields() );
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
			->setFields( $this->defaultFields() );
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
			->setFields( $this->defaultFields() )
			->setSort( array( '@timestamp' => array( 'order' => 'desc' ) ) );
		return $this->doSearch( $query );
	}

	/**
	 * Save a quip
	 *
	 * @param string $id
	 * @param array $quip
	 * @return string|bool Quip id or false if failure
	 */
	public function save( $id, array $quip ) {
		$quip = array_merge( array(
			'@timestamp' => date( 'c' ),
			'nick' => 'anonymous',
			'message' => '(this space left intentionally blank)',
			'up_votes' => 0,
			'down_votes' => 0,
			'score' => 0,
			'tags' => array(),
		), $quip );

		$quip['score'] = static::computeScore(
			$quip['up_votes'], $quip['down_votes']
		);

		if ( $id === 'new' ) {
			$id = '';
		}

		$doc = new Document( $id, $quip );
		$doc->setAutoPopulate( true );
		$res = $this->client->getIndex( 'bash' )
			->getType( 'bash' )
			->addDocument( $doc );
		$this->client->getIndex( 'bash' )->refresh();

		if ( $res->isOk() ) {
			$data = $res->getData();
			return $data['_id'];

		} else {
			$this->logger->error( 'Failure saving {id}: {error}', array(
				'id' => $id,
				'error' => $res->getError(),
			) );
			return false;
		}
	}

	/**
	 * Compute a score based on the number of up and down votes.
	 *
	 * @param int $up Up votes
	 * @param int $down Down votes
	 * @param float $z Quantile of standard normal distibution
	 * @return int
	 * @see http://www.evanmiller.org/how-not-to-sort-by-average-rating.html
	 */
	public static function computeScore( $up, $down, $z = 1.96 ) {
		$n = $up + $down;
		if ( $n === 0 ) {
			return 0;
		}
		$p̂ = $up / $n;
		$z² = $z * $z;
		return ( $p̂ + ( $z² / ( 2 * $n ) ) -
			$z * sqrt( ( $p̂ * ( 1 - $p̂ ) + $z² / ( 4 * $n ) ) / $n ) ) /
			( 1 + $z² / $n );
	}
}
