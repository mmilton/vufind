<?php
/**
 * EBSCO Search API abstract base class
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
 * @category EBSCOIndustries
 * @package  EBSCO
 * @author   Michelle Milton <mmilton@epnet.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link http://edswiki.ebscohost.com/EDS_API_Documentation
 */

namespace EBSCO\EdsApi;

require_once dirname(__FILE__) . '/Exception.php';
/**
 * EBSCO Search API abstract base class
 * @category EBSCOIndustries
 * @package  EBSCO
 * @author   Michelle Milton <mmilton@epnet.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://edswiki.ebscohost.com/EDS_API_Documentation
 */
abstract class EdsApi_REST_Base
{
    /**
     * A boolean value determining whether to print debug information
     * @var bool
     */
    protected $debug = false;

    /**
     * EDSAPI host
     * @var string
     */
    protected $edsApiHost = 'http://eds-api.ebscohost.com/edsapi/rest';
    
    /**
     * Auth host
     * @var string
     */
    protected $authHost = 'https://eds-api.ebscohost.com/authservice/rest';
    
    /**
     * The authentication token required for communication with the api
     * @var string
     */
    protected $authenticationToken;

    /**
     * The organization id use for authentication
     * @var string
     */
    protected $orgId;

    /**
     * The session token for the current request
     * @var string
     */
    protected $sessionToken = '';

    /**
     * Is the end user a guest. Valid values are 'y' or 'n'
     * @var string
     */
    protected $isGuest = 'y';

			
    /**
     * Constructor
     *
     * Sets up the EDS API Client
     *
     * @param array  $settings Associative array of setting to use in 
     * 	                       conjunction with the EDS API
     *    <ul>
     *      <li>debug - boolean to control debug mode</li>
     *      <li>authtoken - Authentication to use for calls to the API. If 
     *      using IP Authentication, this is not needed.</li>
     *      <li>username -  EBSCO username for account setup for usage with 
     *      the EDS API. This is only required for institutions using UID 
     *      Authentication </li>
     *      <li>password - EBSCO password for account setup for usage with the
     *       EDS API. This is only required for institutions using UID 
     *       Authentication </li>
     *      <li>orgid - Organization making calls to the EDS API </li>
     *      <li>sessiontoken - SessionToken this call is associated with, is 
     *      one exists. If not, the a profile value must be present </li>
     *     	<li>profile - EBSCO profile to use for calls to the API. </li>
     *      <li>isguest - is the user a guest. This needs to be present if 
     *      there is no session token present</li>
     *    </ul>
     */
    public function __construct($settings = array())
    {
        foreach ($settings as $key => $value) 
        {
            switch($key)
            {
            	case 'debug':
            		$this->debug = $value;
            		break;
            	case 'authtoken':
            		$this->authenticationToken= $value;
            		break;
            	case 'username':
            		$this->username = $value;	
					break;
            	case 'password':
            		$this->password = $value;
            		break;
            	case 'orgid':
            		$this->orgId = $value;
            		break;
            	case 'sessiontoken':
            		$this->sessionToken = $value;
            		break;
            	case 'profile':
            		$this->profile = $value;
            		break;
            	case 'isguest':
            		$this->isguest = $value;
            		break;
            }		
        }
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
        if ($this->debug) {
            echo "<pre>{$msg}</pre>\n";
        }
    }
    
    /**
     * Obtain edsapi search critera and application related settings
     *
     * @return array   
     */
    public function info()
    {
    	$this->debugPrint("Info");
    	$url = $this->$edsApiHost.'/info';
   		return $this->call($baseUrl);
    }
    
    /**
     * Creates a new session 
     *
     * @param string $profil	Profile to use 
     * @param string $isGuest	Whether or not this sesssion will be a guest
     *                          session
     * @return array    
     */
    public function createSession($profile = null, $isGuest = null)
    {
    	$this->debugPrint("Create Session for profile: $profile, guest: $isGuestToUse ");
    	$profileToUse = isset($profile) ? $profile : $this->profile;
    	$isGuestToUse = isset($isGuest) ? $isGuest : $this->isGuest;
    	$qs = array('profile' => $profileToUse, 'isguest' => $isGuestToUse);
    	$url = $edsApiHost.'/createsession';
   		return $this->call($url, $qs);
    }
    

    /**
     * Retrieves a record specified by its identifiers
     *
     * @param string $an 				An of the record to retrieve from the 
     *                                  EdsApi
     * @param string $dbId				Database identifier of the record to retrieve from the EdsApi
     * @param string $highlightTerms	Comma seperated list of terms to highlight in the retrieved record respones
	 *
     * @return array    The requested record
     */
    public function retrieve($an, $dbId, $highlightTerms = null)
    {
        $this->debugPrint("Get Record. an: $id, dbid: $dbId");
        $qs = array('an' => $an, 'dbid' => $dbId);
        if(null != $highlightTerm)
        	$qs['highlightterms'] = $highlightTerms;
        $url = $this->$edsApiHost.'/retrieve';
	  	return $this->call($url, $qs);
       
    }

    /**
     * Execute an EdsApi search
     *
     * @param SearchRequestModel $query     Search request object
     *
     * @return array             An array of query results as returned from the api
     */
    public function search($query)
    {
        // Query String Parameters
        $qs = $query->convertToQueryStringParameterArray();
		$this->debugPrint('Query: ' . print_r($qs, true));
        $url = $this->$edsApiHost.'/search';
        return $this->call($url, $qs);
    }
    
    /**
     * Generate an authentication token with a valid EBSCO EDS Api account 
     * @param string $username 	username associated with an EBSCO EdsApi account
     * @param string $password 	password associated with an EBSCO EdsApi account
     * @param string $orgid    	Organization id the request is initiated from
     * @return array 
     */
    public function authenticate($username = null, $password = null, $orgid = null)
    {
    	$this->debugPrint("Authenticating: username: $username, password: $password, orgid: $orgid ");
    	$url = $this->$authHost.'/uidauth';
    	$un = isset($username)? $username : $this->username;
    	$pwd = isset($password) ? $password : $this->password;
    	$org = isset($orgid) ? $orgid : $this->orgId;
    	$authInfo = array();
     	if(isset($un))
			  $authInfo['username'] = $un;
     	if(isset($pwd))
     		$authInfo['password'] = $password;
     	if(isset($org))
     		$authInfo['orgid'] = $org; 		
     	$messageBody = json_encode($authInfo);
    	return $this->call($url, null, 'POST', $messageBody);
    }

    /**
     * Convert an array of search parameters to EDS API querystring parameters
     * @param array $params Parameters to convert to querystring parameters
     * @return array
     */
    protected function createQSFromArray($params)
    {
    	$queryParameters = array();
    	foreach ($params as $key => $value) 
    	{    			
    		if(is_array($value))
    		{
    			$parameterName = $key;
    			$isIndexed = SearchRequestModel::isParameterIndexed($parameterName);
    			if( $isIndexed)
    				$parameterName = SearchRequestModel::getIndexedParameterName($parameterName);

    			foreach ($value as $subKey => $subValue)
    			{
					if( SearchRequestModel::isParameterIndexed($key))
						$queryParameters[] = $parameterName.'='.urlencode($subValue);
    			}
    		}
    		else 
    			$queryParameters[] = $key.'='.urlencode($value);    			
    	}
    	return $queryParameters;
    }
    
    /**
     * Submit REST Request
     *
     * @param string $baseUrl URL of service
     * @param array  $params  An array of parameters for the request
     * @param string $method  The HTTP Method to use
     * @param string $message  Message to POST if $method is POST
     *
     * @throws \EbscoEdsApiException
     * @return object         EDS API response (or an Error object).
     */
    protected function call($baseUrl, $params = array(), $method = 'GET',
        $message = null, $messageFormat = ""
    ) {
        // Build Query String Parameters
        $queryParameters = createQSFromArray($params);
        $queryString = implode('&', $queryParameters);

        // Build headers
        $headers = array(
            'Accept' => $this->accept,
        );
        if (0 < strlen($this->sessionToken)) {
            $headers['x-sessionToken'] = $this->sessionToken;
        }
        if (0 < strlen($this->authenticationToken)) {
        	$headers['x-authenticationToken'] = $this->authenticationToken;
        }

        try{ 
        	// Send and process request
        	return $this->process(
            	$this->httpRequest($baseUrl, $method, $queryString, $headers, $message, $messageFormat) );
        }catch(EbscoEdsApiException $e){
        	$rethrow = true;
        	if($e->isApiError())
        	{
        		switch ($e->getCode())
        		{
        			//need to decide where authentication errors should be handled
        			//TODO: do we have enough information? if so we could handle:
        			//104 	Auth Token Invalid 
        			//107 	Authentication Token Missing <-- not sure this one is actually reachable anymore
					//108 	Session Token Missing

        			case 109: //Session Token Invalid
        				if(isset($profile)){
        					//generate a new session token, and re-call once.
        					try{
        						$response = $this->createSession($this->profile, $this->isGuest);
        						if(!$isset($response['error'])){
        							
        						}
        					} catch(Exception $e1){}
        				}			
        				break;
        		}
        	}
        	if(rethrow)
       			throw $e;
        }
 
    }

    /**
     * Process EDSAPI response message
     *
     * @param array $input The raw response from Summon
     *
     * @throws EbscoEdsApiException
     * @return array       The processed response from EDS API
     */
    protected function process($input)
    {
        //process response.
        try {
        	$result = json_decode($input, true);
        }catch(Exception $e){
			throw new EbscoEdsApiException('An error occurred when processing EDS Api response: '.$e->getMessage());
        }
        if (!isset($result)) {
        	throw new EbscoEdsApiException('Unknown error processing reponse');
        }
        return $result;
    }

    /**
     * Perform an HTTP request.
     *
	 * @param string $baseUrl     		Base URL for request
	 * @param string $method      		HTTP method for request (GET,POST, etc.)
	 * @param string $queryString 		Query string to append to URL
	 * @param array  $headers     		HTTP headers to send
	 * @param string $messageBody 		Message body to for HTTP Request
	 * @param string $messageFormat	  		Format of request $messageBody and respones
	 * 
     * @throws SerialsSolutions_Summon_Exception
     * @return string             HTTP response body
     */
    abstract protected function httpRequest($baseUrl, $method, $queryString, $headers, $messageBody, $messageFormat);
   
}
