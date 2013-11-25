<?php
/**
 * EDS API Options
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
 * @category Ebsco Industries
 * @package  EBSCO
 * @author   Michelle Milton <mmilton@epnet.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 */
namespace VuFind\Search\EDS;

use VuFindSearch\ParamBag;
use Zend\ServiceManager\ServiceLocatorAwareInterface,
Zend\ServiceManager\ServiceLocatorInterface;
class Options extends \VuFind\Search\Base\Options 
{
	/**
     * Maximum number of results
     *
     * @var int
     */
    protected $resultLimit = 100;

	/**
	 * Amount of data to return 
	 *
	 * @var array
	 */
    protected $amountOptions = array();
    
    /**
     * Default amount of data to return
     *
     * @var string
     */
    protected $defaultAmount = 'detailed';

    /**
     * Available search mode options
     *
     * @var array
     */
    protected $modeOptions = array();

    /**
     * Default search mode options
     * @var string
     */
    protected $defaultMode = 'all';
    
    /**
     * Default expanders to apply
     * @var array
     */
    protected $defaultExpanders = array();
    
    /**
     * Available expander options
     * @var unknown
     */
    protected $expanderOptions = array();

    
    /**
     * Default limiters to apply
     * @var array
     */
    protected $defaultLimiters = array();
    
    /**
     * Available limiter options
     * @var unknown
    */
    protected $limiterOptions = array();
    
    protected $serviceLocator;
    
    /**
     * Available Search Options from the API
     * @var array
     */
	protected $apiInfo;
    
    
    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(\VuFind\Config\PluginManager $configLoader)
    {
        $this->searchIni = 'EDS';
        $searchSettings = $configLoader->get($this->searchIni);
        parent::__construct( $configLoader);;
        $container = new \Zend\Session\Container('EBSCO');
        $this->apiInfo = $container->info;
        $this->setOptionsFromApi($searchSettings);
        $this->setOptionsFromConfig($searchSettings);
    }

    /**
     * Get an array of search mode options
     *
     * @access public
     * @return array
     */
    public function getModeOptions()
    {
        return $this->modeOptions;
    }
    
    /**
     * Return the route name for the search results action.
     *
     * @return string
     */
    public function getSearchAction()
    {
    	return 'eds-search';
    }
    
    /**
     * Whether or not to specify highlighting in the API
     */
    public function getHighlight()
    {
    	return $this->highlight;
    }
    /**
     * Return the route name of the action used for performing advanced searches.
     * Returns false if the feature is not supported.
     *
     * @return string|bool
     */
    public function getAdvancedSearchAction()
    {
    	return 'eds-advanced';
    }
    
    /**
     * If there is a limit to how many search results a user can access, this
     * method will return that limit.  If there is no limit, this will return
     * -1.
     *
     * @return int
     */
    public function getVisibleSearchResultLimit()
    {
    	return $this->resultLimit;
    }

    /**
     * Set the search options from the Eds API Info methods results
     * 
     */
    public function setOptionsFromApi()
    {
    	//Set options from the INFO method first. If settings are set in the config file, use them as
    	//'overrides', but only if they are available (ie. are returned in the INFO method)
    	$this->populateViewSettings();
    	$this->populateSearchCriteria();
    	 
    }
    
    /**
     * Load options from the configuration file. These will override the defaults set from the values
     * in the Info method. (If the values set in the config files in not a 'valid' EDS API value, it 
     * will be ignored.
     * 
     * @param $searchsettings AbstractPluginManager 
     */
    public function setOptionsFromConfig($searchSettings)
    {
    	if (isset($searchSettings->General->default_limit)) {
    		$this->defaultLimit = $searchSettings->General->default_limit;
    	}
    	if (isset($searchSettings->General->limit_options)) {
    		$this->limitOptions
    		= explode(",", $searchSettings->General->limit_options);
    	}
    	
    	// Set up highlighting preference
    	if (isset($searchSettings->General->highlighting)) {
    		$this->highlight = $searchSettings->General->highlighting ;
    	}
    	
    	// Load search preferences:
    	if (isset($searchSettings->General->retain_filters_by_default)) {
    		$this->retainFiltersByDefault
    		= $searchSettings->General->retain_filters_by_default;
    	}
    	
        // Search handler setup. Only valid values set in the config files are used.
        if (isset($searchSettings->Basic_Searches)) {
        	$newBasicHandlers = array();
            foreach ($searchSettings->Basic_Searches as $key => $value) {
                if(isset($this->basicHandlers[$key]))
            		$newBasicHandlers[$key] = $value;
            }
            if(!empty($newBasicHandlers))
            	$this->basicHandlers = $newBasicHandlers;
        }
        
    	if (isset($searchSettings->Advanced_Searches)) {
    		$newAdvancedHandlers = array();
    		foreach ($searchSettings->Advanced_Searches as $key => $value) {
	    		if(isset($this->advancedHandlers[$key]))
    				$newAdvancedHandlers[$key] = $value;
    		}
    		if(!empty($newAdvancedHandlers))
    			$this->advancedHandlers = $newAdvancedHandlers;
    	}
    	
    	// Sort preferences:
    	if (isset($searchSettings->Sorting)) {
    		$newSortOptions = array();
    		foreach ($searchSettings->Sorting as $key => $value) {
    			if(isset($this->sortOptions[$key]))
    				$newSortOptions = $value;
    		}
    		if(!empty($newSortOptions))
    			$this->sortOptions = $newSortOptions;
    	}
    	
    	if (isset($searchSettings->General->default_sort)) {
    		$defaultSort = $searchSettings->General->default_sort;
    		if(isset($this->sortOptions[$searchSettings->General->default_sort]))
	    		$this->defaultSort = $searchSettings->General->default_sort;
    	}
    	
    	
    	if (isset($searchSettings->General->default_amount)) {
    		if(isset($this->amountOptions[$searchSettings->General->default_amount]))
	    		$this->defaultAmount = $searchSettings->General->default_amount;
    	}

    	if (isset($searchSettings->General->default_mode)) {
    		if(isset($this->modeOptions[$searchSettings->General->default_mode]))
    			$this->defaultMode = $searchSettings->General->default_mode;
    	}
    	
    	//View preferences
    	if (isset($searchSettings->General->default_view)) {
    		$this->defaultView = $searchSettings->General->default_view;
    	}
	}
	
	/**
	 * Populate available search criteria from the EDS API Info method
	 */
	protected function populateSearchCriteria()
	{
		if(isset($this->apiInfo) &&
			isset($this->apiInfo['AvailableSearchCriteria']) &&
			isset($this->apiInfo['AvailableSearchCriteria'])) {
		
				//Sort preferences
				$this->sortOptions = array();
				if(isset($this->apiInfo['AvailableSearchCriteria']['AvailableSorts'])){
					foreach($this->apiInfo['AvailableSearchCriteria']['AvailableSorts'] as $sort)
						$this->sortOptions[$sort['Id']] = $sort['Label'];
				}
			
				//By default, use all of the available search fields for both advanced and basic. Use the values in
				//the config files to filter.
				$this->basicHandlers = array('AllFields' => 'All Fields');
				if(isset($this->apiInfo['AvailableSearchCriteria']['AvailableSearchFields'])){
					foreach($this->apiInfo['AvailableSearchCriteria']['AvailableSearchFields'] as $searchField)
						$this->basicHandlers[$searchField['FieldCode']] = $searchField['Label'];
				}
				$this->advancedHandlers = $this->basicHandlers;
		
				// Search Mode preferences
				$this->modeOptions = array();
				if(isset($this->apiInfo['AvailableSearchCriteria']['AvailableSearchModes'])){
					foreach($this->apiInfo['AvailableSearchCriteria']['AvailableSearchModes'] as $mode){
						$this->modeOptions[$mode['Mode']] = $mode['Label'];
						if(isset($mode['DefaultOn']) &&  'y' == $mode['DefaultOn'])
							$this->defaultMode = $mode['Mode'];
					}
				}
		
				//expanders
				$this->expanderOptions = array();
				$this->defaultExpanders = array();
				if(isset($this->apiInfo['AvailableSearchCriteria']['AvailableExpanders'])){
					foreach($this->apiInfo['AvailableSearchCriteria']['AvailableExpanders'] as $expander){
						$this->expanderOptions[$expander['Id']] = $expander['Label'];
						if(isset($expander['DefaultOn']) && 'y' == $expander['DefaultOn']) 
							$this->defaultExpanders[] =  $expander['Id'];
					}
				}
				
				//Limiters
				$this->availableLimiters= array();
				$this->defaultLimiters = array();
				if(isset($this->apiInfo['AvailableSearchCriteria']['AvailableLimiters'])){
					foreach($this->apiInfo['AvailableSearchCriteria']['AvailableLimiters'] as $limiter){
						$this->availableLimiters[$limiter['Id']] = array('Label' => $limiter['Label'],
								'Type' => $limiter['Type'],
								'LimiterValues' => (isset($limiter['Values'])) ? populateLimiterValues($limiter['Values']) : null,
						);
						if(isset($limiter['DefaultOn']) && 'y' == $limiter['DefaultOn'])
							$this->defaultLimiters[] = $limiter['Id'];
					}
					
				}
		}
	}
	
	/**
	 * Populate limiter values forom the EDS API INFO method data
	 * 
	 * @param array $limiterValues Limiter values from the API
	 * @return array
	 */
	protected function populateLimitersValues($limiterValues)
	{
		$availableLimiterValues = array();
		if(isset($limiterValues)){
			foreach($limiterValues as $limiterValue){
				$availableLimiterValues[] = array( 'Value' => $limiterValue['Value'],
			 									   'LimiterValues' => isset($limiterValue['LimiterValues']) ? populateLimiterValues($limiterValue['LimiterValues']) : null);
			}
		}
		return empty($availableLimiterValues) ? null : $availableLimiterValues;
	}
	
	
	/**
	 * Sets the view settings from EDS API info method call data
	 * 
	 * @return number
	 */
	protected function populateViewSettings()
	{
		if(isset($this->apiInfo) && 
			isset($this->apiInfo['ViewResultSettings']))
			 
			//default result Limit
			if(isset($this->apiInfo['ViewResultSettings']['ResultsPerPage']))
				$this->defaultLimit = $this->apiInfo['ViewResultSettings']['ResultsPerPage'];
			else
				$this->defaultLimit = 20;
			
			//default view (amount)
			if(isset($this->apiInfo['ViewResultSettings']['ResultListView']))
				$this->defaultAmount = $this->apiInfo['ViewResultSettings']['ResultListView'];
			else
				$this->defaultAmount = 'brief';
			$this->amountOptions = array('brief', 'title', 'detailed');
			
	}
}