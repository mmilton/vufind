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

    protected $serviceLocator;
    

    
    
    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(\VuFind\Config\PluginManager $configLoader)
    {
        $this->searchIni = 'EDS';
        parent::__construct( $configLoader);
        $searchSettings = $configLoader->get($this->searchIni);
        //if (isset($searchSettings->General->options_from_api)) {
			$this->setOptionsFromConfig($searchSettings);
    //    }else{
			//$this->setOptionsFromApi($searchSettings);
	//	}        
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
     * set the search options from the Eds API Info methods results
     */
    public function setOptionsFromApi()
    {
    	//Call search with all null values
    	$paramBag = new ParamBag();
    	$paramBag->add('Info', 'y');
    	//$collection = $this->getSearchService()->search(
    	//		'EDS', null, null, null, $paramBag
    	//);
    	
    }
    
    /**
     * Load options from the configuration file
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
    	
            // Search handler setup:
        if (isset($searchSettings->Basic_Searches)) {
            foreach ($searchSettings->Basic_Searches as $key => $value) {
                $this->basicHandlers[$key] = $value;
            }
        }
    	if (isset($searchSettings->Advanced_Searches)) {
    		foreach ($searchSettings->Advanced_Searches as $key => $value) {
    			$this->advancedHandlers[$key] = $value;
    		}
    	}
    	
    	// Sort preferences:
    	if (isset($searchSettings->Sorting)) {
    		foreach ($searchSettings->Sorting as $key => $value) {
    			$this->sortOptions[$key] = $value;
    		}
    	}
    	if (isset($searchSettings->General->default_sort)) {
    		$this->defaultSort = $searchSettings->General->default_sort;
    	}
    	
    	
    	// Detail amount preferences
    	if (isset($searchSettings->Amount)) {
    		foreach ($searchSettings->Amount as $key => $value) {
    			$this->amountOptions[$key] = $value;
    		}
    	}
    	if (isset($searchSettings->General->default_amount)) {
    		$this->defaultAmount = $searchSettings->General->default_amount;
    	}
    	
    	// Search Mode preferences
    	if (isset($searchSettings->Search_Modes)) {
    		foreach ($searchSettings->Search_Modes as $key => $value) {
    			$this->modeOptions[$key] = $value;
    		}
    	}
    	if (isset($searchSettings->General->default_mode)) {
    		$this->defaultMode = $searchSettings->General->default_mode;
    	}
    	
    	//View preferences
    	if (isset($searchSettings->General->default_view)) {
    		$this->defaultView = $searchSettings->General->default_view;
    	}
	}

}