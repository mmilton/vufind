<?php
/**
 * EDS API Backend
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

use EBSCO\EdsApi\Zend2 as ApiClient; 
use EBSCO\EdsApi\EdsApi_REST_Base;
use EBSCO\EdsApi\EbscoEdsApiException;
use EBSCO\EdsApi\SearchRequestModel;

use VuFindSearch\Query\AbstractQuery;

use VuFindSearch\ParamBag;

use VuFindSearch\Response\RecordCollectionInterface;
use VuFindSearch\Response\RecordCollectionFactoryInterface as
 RecordCollectionFactoryInterface;

use VuFindSearch\Backend\BackendInterface as BackendInterface;
use VuFindSearch\Backend\Exception\BackendException;

use Zend\Log\LoggerInterface;
use VuFindSearch\Backend\EdsApi\Response\RecordCollection;
use VuFindSearch\Backend\EdsApi\Response\RecordCollectionFactory;

/**
 *  EDS API Backend
 * 
 * @category VuFind2
 * @package  Search
 * @author   Michelle Milton <mmilton@epnet.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org
 */
class Backend implements BackendInterface
{
	
	/**
	 * Client user to make the actually requests to the EdsApi
	 * @var ApiClient
	 */
	protected $client;
	
	/**
	 * Backend identifier
	 * @var identifier
	 */
	protected $identifier;
	 
	
	/**
	 * @var QueryBuilder
	 */
	protected $queryBuilder;
	
	/**
	 * @var RecordCollectionFactory
	 */
	protected $collectionFactory;
	
	/**
	 * Logger, if any.
	 *
	 * @var LoggerInterface
	 */
	protected $logger;
	
	
	/**
	 * Constructor.
	 *
	 * @param ApiClient                        $client 	EdsApi client to use 
	 * @param RecordCollectionFactoryInterface $factory Record collection factory
	 *
	 * @return void
	 */
	public function __construct(ApiClient $client,
			RecordCollectionFactoryInterface $factory) {
		$this->setRecordCollectionFactory($factory);
		$this->client = $client;
		$this->identifier   = null;
	}
		
    /**
     * Set the backend identifier.
     *
     * @param string $identifier Backend identifier
     *
     * @return void
     */
    public function setIdentifier($identifier)
    {
    	$this->identifier = $identifier;
    }

    /**
     * Return backend identifier.
     *
     * @return string
     */
    public function getIdentifier()
    {
    	return $this->identifier;
    	
    }

    /**
     * Perform a search and return record collection.
     *
     * @param AbstractQuery $query  Search query
     * @param integer       $offset Search offset
     * @param integer       $limit  Search limit
     * @param ParamBag      $params Search backend parameters
     *
     *@return \VuFindSearch\Response\RecordCollectionInterface
     **/
    public function search(AbstractQuery $query, $offset, $limit,
        ParamBag $params = null) {
    	//create query parameters from VuFind data
    	$baseParams = $this->getQueryBuilder()->build($query);
    	if (null !== $params) {
    		$baseParams->mergeWith($params);
    	}
    	$baseParams->set('resultsperpage', $limit);
    	$page = $limit > 0 ? floor($offset / $limit) + 1 : 1;
    	$baseParams->set('page', $page);
    	
    	$searchModel = $this->backendParamsToEBSCOSearchModel($baseParams);
    	try {
    		$response = $this->client->search($searchModel);
    	} catch (EbscoEdsApiException $e) {
    		throw new BackendException(
    				$e->getMessage(),
    				$e->getCode(),
    				$e
    		);
    	}
    	catch(Exception $e)
    	{    		throw new BackendException(
    				$e->getMessage(),
    				$e->getCode(),
    				$e
    		);
    		
    	}
    	$collection = $this->createRecordCollection($response);
    	$this->injectSourceIdentifier($collection);
    	return $collection;
    }

    /**
     * Retrieve a single document.
     *
     * @param string   $id     Document identifier
     * @param ParamBag $params Search backend parameters
     *
     * @return \VuFindSearch\Response\RecordCollectionInterface
     */
    public function retrieve($id, ParamBag $params = null)
    {
    	try {
    		//not sure how $an and dbid will be coming through. could have the id be the 
    		//query string to identify the record retrieval
    		//or maybe $id = [dbid],[an]
    		$seperator = ',';
    		$pos = strpos($id, $seperator);
    		if($pos === false){
    			throw new BackendException(
    					'Retrieval id is not in the correct format.'
    			);
    		}
    		$dbid = substr($id, 0, $pos);
	 		$an  = substr($id, $pos+1);
    		$highlightTerms = $params['highlight'];
    		$response   = $this->client->retrieve($an, $dbId, $highlightTerms);
    	} catch (\EbscoEdsApiException $e) {
    		throw new BackendException(
    				$e->getMessage(),
    				$e->getCode(),
    				$e
    		);
    	}
    	$collection = $this->createRecordCollection($response);
    	$this->injectSourceIdentifier($collection);
    	return $collection;
    }
    
    /**
     * Convert a ParamBag to a EdsApi Search request object.
     *
     * @param ParamBag $params ParamBag to convert
     *
     * @return SearchRequestModel
     */
    protected function backendParamsToEBSCOSearchModel(ParamBag $params)
    {
    	$model = new SearchRequestModel();
    	$params = $params->getArrayCopy();
    
    	// Convert the options:
    	$options = array();
    	foreach ($params as $key => $param) {
    		//PULL OUT PARAMETERS HERE AND SET THEM ON THE SEARCH REQUEST MODEL


    	}
    
    	return $model;
    }
    /**
     * Set the record collection factory.
     *
     * @param RecordCollectionFactoryInterface $factory Factory
     *
     * @return void
     */
    public function setRecordCollectionFactory(
    		RecordCollectionFactoryInterface $factory
    ) {
    	$this->collectionFactory = $factory;
    }
    
    /**
     * Return the record collection factory.
     *
     * Lazy loads a generic collection factory.
     *
     * @return RecordCollectionFactoryInterface
     */
    public function getRecordCollectionFactory()
    {
    	return $this->collectionFactory;
    }
    
    /**
     * Return query builder.
     *
     * Lazy loads an empty QueryBuilder if none was set.
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
    	if (!$this->queryBuilder) {
    		$this->queryBuilder = new QueryBuilder();
    	}
    	return $this->queryBuilder;
    }
    
    /**
     * Set the query builder.
     *
     * @param QueryBuilder $queryBuilder Query builder
     *
     * @return void
     *
     */
    public function setQueryBuilder(QueryBuilder $queryBuilder)
    {
    	$this->queryBuilder = $queryBuilder;
    }
    
 /// Internal API

    /**
     * Inject source identifier in record collection and all contained records.
     *
     * @param ResponseInterface $response Response
     *
     * @return void
     */
    protected function injectSourceIdentifier(RecordCollectionInterface $response)
    {
        $response->setSourceIdentifier($this->identifier);
        foreach ($response as $record) {
            $record->setSourceIdentifier($this->identifier);
        }
        return $response;
    }

    /**
     * Send a message to the logger.
     *
     * @param string $level   Log level
     * @param string $message Log message
     * @param array  $context Log context
     *
     * @return void
     */
    protected function log($level, $message, array $context = array())
    {
        if ($this->logger) {
            $this->logger->$level($message, $context);
        }
    }

    /**
     * Create record collection.
     *
     * @param array $records Records to process
     *
     * @return RecordCollectionInterface
     */
    protected function createRecordCollection($records)
    {
        return $this->getRecordCollectionFactory()->factory($records);
    }

}