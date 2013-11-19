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

namespace VuFindSearch\Backend\EDS;
require_once 'C:\Users\Administrator\git\vufind-git\vendor\ebsco\edsapi\Ebsco\EdsApi\SearchRequestModel.php';

use EBSCO\EdsApi\Zend2 as ApiClient; 
use EBSCO\EdsApi\EdsApi_REST_Base;
use EBSCO\EdsApi\EbscoEdsApiException;
use EBSCO\EdsApi\SearchRequestModel as SearchRequestModel;

use VuFindSearch\Query\AbstractQuery;

use VuFindSearch\ParamBag;

use VuFindSearch\Response\RecordCollectionInterface;
use VuFindSearch\Response\RecordCollectionFactoryInterface as
 RecordCollectionFactoryInterface;

use VuFindSearch\Backend\BackendInterface as BackendInterface;
use VuFindSearch\Backend\Exception\BackendException;

use Zend\Log\LoggerInterface;
use VuFindSearch\Backend\EDS\Response\RecordCollection;
use VuFindSearch\Backend\EDS\Response\RecordCollectionFactory;

use Zend\ServiceManager\ServiceLocatorInterface;

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
	 * User name for EBSCO EDS API account if using UID Authentication
	 * @var string
	 */
	protected $userName = null;
	
	/**
	 * Password for EBSCO EDS API account if using UID Authentication
	 * @var string
	 */
	protected $password = null;
	
	/**
	 * Profile for EBSCO EDS API account
	 * @var string
	 */
	protected $profile = null;
	
	/**
	 * Whether or not to use IP Authentication for communication with the EDS API
	 * @var boolean
	 */
	protected $ipAuth = false;
	
	/**
	 * Organization EDS API requests are being made for
	 * @var string
	 */
	protected $orgid = null;
	
	/**
	 * Superior service manager.
	 *
	 * @var ServiceLocatorInterface
	 */
	protected $serviceLocator;
	
	/**
	 * Constructor.
	 *
	 * @param ApiClient                        $client 	EdsApi client to use 
	 * @param RecordCollectionFactoryInterface $factory Record collection factory
	 *
	 * @return void
	 */
	public function __construct(ApiClient $client,
			RecordCollectionFactoryInterface $factory, array $account) {
		$this->setRecordCollectionFactory($factory);
		$this->client = $client;
		$this->identifier   = null;
		$this->userName = isset($account['username']) ? $account['username'] : null;
		$this->password = isset($account['password']) ? $account['password'] : null;
		$this->ipAuth = isset($account['ipauth']) ? $account['ipauth'] : null;
		$this->profile = isset($account['profile']) ? $account['profile'] : null;
		$this->orgId = isset($account['orgid']) ? $account['orgid'] : null;
		
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
     * Sets the superior service locator
     * 
     * @param ServiceLocatorInterface $serviceLocator Superior service locator
     */
    public function setServiceLocator($serviceLocator)
    {
    	$this->serviceLocator =  $serviceLocator;
    }

    /**
     * gets the superior service locator
     * 
     * @return ServiceLocatorInterface Superior service locator
     */
    public function getServiceLocator()
    {
    	return $this->serviceLocator;
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
    	$queryString = $query->getString();
    	$paramsString = implode('&', $params->request());
    	$this->debugPrint("Query: $queryString, Limit: $limit, Offset: $offset, Params: $paramsString ");
    	
    	$authenticationToken = $this->getAuthenticationToken();
    	$this->debugPrint("Authentication Token to use for creating session: $authenticationToken");
    	//check to see if the profile is overriden
    	$overrideProfile =  $params->get('profile');
    	if(isset($overrideProfile))
    		$this->profile = $overrideProfile;
    	$sessionToken = $this->getSessionToken($authenticationToken);
    	$baseParams = $this->getQueryBuilder()->build($query);
    	$paramsString = implode('&', $baseParams->request());
    	$this->debugPrint("BaseParams: $paramsString ");
    	if (null !== $params) {
    		$baseParams->mergeWith($params);
    	}
    	$baseParams->set('resultsPerPage', $limit);
    	$page = $limit > 0 ? floor($offset / $limit) + 1 : 1;
    	$baseParams->set('pageNumber', $page);

    	$searchModel = $this->paramBagToEBSCOSearchModel($baseParams);
    	$qs = $searchModel->convertToQueryString();
    	$this->debugPrint("Search Model query string: $qs");
    	try {
    		$response = $this->client->search($searchModel, $authenticationToken, $sessionToken);
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
    		$authenticationToken = $this->getAuthenticationToken();
    		//check to see if the profile is overriden
    		$overrideProfile =  $params->get('profile');
    		if(isset($overrideProfile))
    			$this->profile = $overrideProfile;
    		$sessionToken = $this->getSessionToken($authenticationToken);
    		
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
    		$dbId = substr($id, 0, $pos);
	 		$an  = substr($id, $pos+1);
    		$highlightTerms = '';//$params['highlight'];
    		$response = $this->client->retrieve($an, $dbId, $highlightTerms,$authenticationToken, $sessionToken);
    	} catch (\EbscoEdsApiException $e) {
    		throw new BackendException(
    				$e->getMessage(),
    				$e->getCode(),
    				$e
    		);
    	}
    	$collection = $this->createRecordCollection(array('Records'=> $response));
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
    protected function paramBagToEBSCOSearchModel(ParamBag $params)
    {
    	$params= $params->getArrayCopy();    	
        	// Convert the options:
        //$paramContents = explode('&', $params);
        //$this->debugPrint("ParamBag Contents: $paramContents");
    	$options = array();
    	// Most parameters need to be flattened from array format, but a few
    	// should remain as arrays:
    	$arraySettings = array('query', 'facets', 'filters', 'groupFilters', 'rangeFilters');
    	foreach ($params as $key => $param) {
    		$options[$key] = in_array($key, $arraySettings) ? $param : $param[0];
    	}
    	return new SearchRequestModel($options);
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
    
    /**
     * Set the Logger.
     *
     * @param LoggerInterface $logger Logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger)
    {
    	$this->logger = $logger;
    }
    
    
    /**
     * Obtain the authentication to use with the EDS API from cache if it exists. If not,
     * then generate a new one.
     *
     * @param string $userName EBSCO EDS API username
     * @param string $password EBSCO EDS API password
     * @return string
     */
    protected function getAuthenticationToken()
    {
    	$token = null;
    	if(!empty($this->ipAuth) && true == $this->ipAuth)
    		return $token;
    	$cache = $this->getServiceLocator()->get('VuFind\CacheManager')->getCache('object');
    	$authTokenData = $cache->getItem('edsAuthenticationToken');
    	$currentToken =  $authTokenData['token'];
    	$expirationTime = $authTokenData['expiration'];
    	$this->debugPrint("Cached Authentication data: $currentToken, expiration time: $expirationTime");
    	//data cached should be:
    	// 		token, expiration
    	//check to see if the token expiration time is greater than the current time.
    	$generateToken = true;
    	if(null != $authTokenData)
    	{
    		$expirationTime = $authTokenData['expiration'];
    		//if the token is expired or within 5 minutes of expiring,
    		//generate a new one
    		if( time() <= ($expirationTime - (60*5)) )
    		{
    			$val =  $authTokenData['token'];
    			$this->debugPrint("Token to return: $val ");
    			return $val;
    		}
    	}
    	$username = $this->userName;
    	$password = $this->password;
    	$orgId = $this->orgId;
    	if(!empty($username) && !empty($password))
    	{
    		$this->debugPrint("Calling Authenticate with username: $username, password: $password, orgid: $orgId ");
    		$results = $this->client->authenticate($username, $password, $orgId);
    		$token = $results['AuthToken'];
    		$timeout = $results['AuthTimeout'] + time();
    		$authTokenData = array('token' => $token, 'expiration' => $timeout);
    		$cache->setItem('edsAuthenticationToken', $authTokenData);
    	}
    	return $token;
    }
    
    /**
     * Obtain the session to use with the EDS API from cache if it exists. If not,
     * then generate a new one.
     *
     * @param string $authToken Authentication to use for generating a new session if necessary
     * @return string
     */
    protected function getSessionToken($authToken)
    {
    	$cache = $this->getServiceLocator()->get('VuFind\CacheManager')->getCache('object');
    	//TODO: REMOVE THIS!!! WE SHOULD NEVER CACHE IT. FIND OUT HOW TO PUT IT IN USER SESSION
    	$sessionData = $cache->getItem('edsSessionData');
    	if(!empty($sessionData))
    		return $sessionData['token'];
    	$isguest = $sessionData['isguest'];
    	if(empty($isguest))
    		$isguest='n';
    	$results = $this->client->createSession($this->profile,  $isguest, $authToken);
    	$sessionToken = $results['SessionToken'];
    	$cache->setItem('edsSessionToken', array('token' => $sessionToken, 'isguest' => $isguest));
    	return $sessionToken;
    }
    
    /**
     * Print a message if debug is enabled.
     *
     * @param string $msg Message to print
     *
     * @return void
     */
    protected function debugPrint($msg)
    {
    	if ($this->logger) {
    		$this->logger->debug("$msg\n");
    	} else {
    		parent::debugPrint($msg);
    	}
    }

}