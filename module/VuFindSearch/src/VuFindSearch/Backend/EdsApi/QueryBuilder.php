<?php

/**
 * EDS API Querybuilder
 *
 * PHP version 5
 *
 * Copyright (C) EBSCO Industries 2013
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  Search
 * @author   Michelle Milton <mmilton@epnet.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org
 */
namespace VuFindSearch\Backend\EdsApi;

use VuFindSearch\Query\AbstractQuery;
use VuFindSearch\Query\QueryGroup;
use VuFindSearch\Query\Query;
use VuFindSearch\ParamBag;
/**
 * 
 * @category VuFind2
 * @package  Search
 * @author   Michelle Milton <mmilton@epnet.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org
 */

class QueryBuilder
{
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
	}
	
	/**
	 * Construct EdsApi search parameters based on a user query and params.
	 *
	 * @param AbstractQuery $query User query
	 *
	 * @return ParamBag
	 */
	public function build(AbstractQuery $query)
	{
		// Build base query
		$queryStr = $this->abstractQueryToArray($query);
	
		// Send back results
		$params = new ParamBag($queryStr);
		return $params;
	}
	
	/**
	 * Convert a single Query object to an eds api query object
	 * in the format [operator,][fieldCode]:term
	 *
	 * @param Query $query Query to convert
	 *
	 * @return array
	 */
	protected function queryToQueryArray(Query $query)
	{
		// Clean and validate input:
		$fieldCode = ($query->getHandler() == 'AllFields')? '' : $query->getHandler();  //fieldcode
		$term = str_replace('"', '', $query->getString());
		$operator = ''; //TODO::Not sure where/how this is going to be populated yet. 
		return array( 'fieldcode' => $fieldCode, 'term' => $term, 'operator' => $operator);
	}
	
	
	/// Internal API
	
	/**
	 * Convert an AbstractQuery object to a query string.
	 *
	 * @param AbstractQuery $query Query to convert
	 *
	 * @return array
	 */
	protected function abstractQueryToArray(AbstractQuery $query)
	{
		if ($query instanceof Query) {
			return $this->queryToQueryArray($query);
		} else {
			return $this->queryGroupToArray($query);
		}
	}
	
	/**
	 * Convert a QueryGroup object to a query string.
	 *
	 * @param QueryGroup $query QueryGroup to convert
	 *
	 * @return array
	 */
	protected function queryGroupToArray(QueryGroup $query)
	{
		//NEED TO DETERMINE WHETHER OR NOT WE ARE DOING ADVANCED SEARCH QUERIES THE SAME....
		/*
		$groups = $excludes = array();
	
		foreach ($query->getQueries() as $params) {
			// Advanced Search
			if ($params instanceof QueryGroup) {
				$thisGroup = array();
				// Process each search group
				foreach ($params->getQueries() as $group) {
					// Build this group individually as a basic search
					$thisGroup[] = $this->abstractQueryToString($group);
				}
				// Is this an exclusion (NOT) group or a normal group?
				if ($params->isNegated()) {
					$excludes[] = join(" OR ", $thisGroup);
				} else {
					$groups[]
					= join(" ".$params->getOperator()." ", $thisGroup);
				}
			} else {
				// Basic Search
				$groups[] = $this->queryToString($params);
			}
		}
	
		// Put our advanced search together
		$queryStr = '';
		if (count($groups) > 0) {
			$queryStr
			.= "(" . join(") " . $query->getOperator() . " (", $groups) . ")";
		}
		// and concatenate exclusion after that
		if (count($excludes) > 0) {
			$queryStr .= " NOT ((" . join(") OR (", $excludes) . "))";
		}
	
		return $queryStr;
		*/
	}
}