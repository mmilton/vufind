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
 * @category VuFind2
 * @package  EBSCO
 * @author   Michelle Milton <mmilton@epnet.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.vufind.org  Main Page
 */
namespace VuFind\Search\EDS;

use VuFindSearch\ParamBag;
use Zend\ServiceManager\ServiceLocatorAwareInterface,
    Zend\ServiceManager\ServiceLocatorInterface;

/**
 * EDS API Options
 *
 * @category VuFind2
 * @package  EBSCO
 * @author   Michelle Milton <mmilton@epnet.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.vufind.org  Main Page
 */
class Options extends \VuFind\Search\Base\Options
{
    /**
     * Maximum number of results
     *
     * @var int
     */
    protected $resultLimit = 100;

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
     * The set search mode
     * @var string
     */
    protected $searchMode ;

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
     * Available limiter options
     * @var unknown
    */
    protected $limiterOptions = array();

    /**
     * Wheither or not to return available facets with the search response
     * @var unknown
     */
    protected $includeFacets = 'y';

    protected $serviceLocator;

    /**
     * Available Search Options from the API
     * @var array
     */
    protected $apiInfo;

    /**
     * Limiters to display on the basic search screen
     *
     * @var array
     */
    protected $commonLimiters = array();

    /**
     * Expanders to display on the basic search screen
     *
     * @var array
     */
    protected $commonExpanders = array();

    /**
     * Pre-assigned filters
     *
     * @var array
     */
    protected $hiddenFilters = array();

    /**
     * Constructor
     *
     * @param \VuFind\Config\PluginManager $configLoader Configuration loader
     */
    public function __construct(\VuFind\Config\PluginManager $configLoader)
    {
        $this->searchIni = 'EDS';
        $searchSettings = $configLoader->get($this->searchIni);
        parent::__construct($configLoader);
        $this->viewOptions = array(
            'list|title' => 'title', 'list|brief' => 'brief',
            'list|detailed'=>'detailed'
        );
        $container = new \Zend\Session\Container('EBSCO');
        $this->apiInfo = $container->info;
        $this->setOptionsFromApi($searchSettings);
        $this->setOptionsFromConfig($searchSettings);
    }

    /**
     * Get an array of search mode options
     *
     * @return array
     */
    public function getModeOptions()
    {
        return $this->modeOptions;
    }

    /**
     * Get the default search mode
     *
     * @return string
     */
    public function getDefaultMode()
    {
        return $this->defaultMode;
    }

    /**
     * Obtain the set searchmode
     *
     * @return string the search mode
     */
    public function getSearchMode()
    {
        return $this->searchMode;
    }

    /**
     * Set the search mode
     *
     * @param string $mode Mode
     *
     * @return void
     */
    public function setSearchMode($mode)
    {
        $this->searchMode = $mode;
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
     * Return the view associated with this configuration
     *
     * @return string
     */
    public function getView()
    {
        return $this->defaultView;
    }

    /**
     * Return the view associated with this configuration
     *
     * @return string
     */
    public function getEdsView()
    {
        $viewArr = explode('|', $this->defaultView);
        return (1 < count($viewArr)) ? $viewArr[1] : $this->defaultView;
    }

    /**
     * Whether or not to specify highlighting in the API
     *
     * @return bool
     */
    public function getHighlight()
    {
        return $this->highlight;
    }
    /**
     * Return the expander ids that have the default on flag set in admin
     *
     * @return array
     */
    public function getDefaultExpanders()
    {
        return $this->defaultExpanders;
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
     * @return void
     */
    public function setOptionsFromApi()
    {
        // Set options from the INFO method first. If settings are set in the config
        // file, use them as 'overrides', but only if they are available (ie. are
        // returned in the INFO method)
        $this->populateViewSettings();
        $this->populateSearchCriteria();
    }

    /**
     * Load options from the configuration file. These will override the defaults set
     * from the values in the Info method. (If the values set in the config files in
     * not a 'valid' EDS API value, it will be ignored.
     *
     * @param \Zend\Config\Config $searchSettings Configuration
     *
     * @return void
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
            $this->highlight = $searchSettings->General->highlighting;
        }

        // Set up facet preferences
        if (isset($searchSettings->General->highlighting)) {
            $this->includeFacets = $searchSettings->General->include_facets;
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
                if (isset($this->basicHandlers[$key])) {
                    $newBasicHandlers[$key] = $value;
                }
            }
            if (!empty($newBasicHandlers)) {
                $this->basicHandlers = $newBasicHandlers;
            }
        }

        if (isset($searchSettings->Advanced_Searches)) {
            $newAdvancedHandlers = array();
            foreach ($searchSettings->Advanced_Searches as $key => $value) {
                if (isset($this->advancedHandlers[$key])) {
                    $newAdvancedHandlers[$key] = $value;
                }
            }
            if (!empty($newAdvancedHandlers)) {
                $this->advancedHandlers = $newAdvancedHandlers;
            }
        }

        // Sort preferences:
        if (isset($searchSettings->Sorting)) {
            $newSortOptions = array();
            foreach ($searchSettings->Sorting as $key => $value) {
                if (isset($this->sortOptions[$key])) {
                    $newSortOptions = $value;
                }
            }
            if (!empty($newSortOptions)) {
                $this->sortOptions = $newSortOptions;
            }
        }

        if (isset($searchSettings->General->default_sort)
            && isset($this->sortOptions[$searchSettings->General->default_sort])
        ) {
            $this->defaultSort = $searchSettings->General->default_sort;
        }


        if (isset($searchSettings->General->default_amount)
            && isset($this->amountOptions[$searchSettings->General->default_amount])
        ) {
            $this->defaultAmount = $searchSettings->General->default_amount;
        }

        if (isset($searchSettings->General->default_mode)
            && isset($this->modeOptions[$searchSettings->General->default_mode])
        ) {
            $this->defaultMode = $searchSettings->General->default_mode;
        }

        //View preferences
        if (isset($searchSettings->General->default_view)) {
            $this->defaultView = 'list|'.$searchSettings->General->default_view;
        }


        if (isset($searchSettings->Advanced_Facet_Settings->special_facets)) {
            $this->specialAdvancedFacets
                = $searchSettings->Advanced_Facet_Settings->special_facets;
        }


        //Only the common limiters that are valid limiters for this profile
        //will be used
        if (isset($searchSettings->General->common_limiters)) {
            $commonLimiters = $searchSettings->General->common_limiters;
            if (isset($commonLimiters)) {
                $cLimiters = explode(',', $commonLimiters);

                if (!empty($cLimiters) && isset($this->limiterOptions)
                    && !empty($this->limiterOptions)
                ) {
                    foreach ($cLimiters as $cLimiter) {
                        if (isset($this->limiterOptions[$cLimiter])) {
                            $this->commonLimiters[] = $cLimiter;
                        }
                    }
                }
            }
        }

        //Only the common expanders that are valid expanders for this profile
        //will be used
        if (isset($searchSettings->General->common_expanders)) {
            $commonExpanders= $searchSettings->General->common_expanders;
            if (isset($commonExpanders)) {
                $cExpanders = explode(',', $commonExpanders);
                if (!empty($cExpanders) && isset($this->expanderOptions)
                    && !empty($this->expanderOptions)
                ) {
                    foreach ($cExpanders as $cExpander) {
                        if (isset($this->expanderOptions[$cExpander])) {
                            $this->commonExpanders[] = $cExpander;
                        }
                    }
                }
            }
        }
    }

    /**
     * Populate available search criteria from the EDS API Info method
     *
     * @return void
     */
    protected function populateSearchCriteria()
    {
        if (isset($this->apiInfo)
            && isset($this->apiInfo['AvailableSearchCriteria'])
        ) {
            // Reference for readability:
            $availCriteria = & $this->apiInfo['AvailableSearchCriteria'];

            //Sort preferences
            $this->sortOptions = array();
            if (isset($availCriteria['AvailableSorts'])) {
                foreach ($availCriteria['AvailableSorts'] as $sort) {
                    $this->sortOptions[$sort['Id']] = $sort['Label'];
                }
            }

            // By default, use all of the available search fields for both
            // advanced and basic. Use the values in the config files to filter.
            $this->basicHandlers = array('AllFields' => 'All Fields');
            if (isset($availCriteria['AvailableSearchFields'])) {
                foreach ($availCriteria['AvailableSearchFields'] as $searchField) {
                    $this->basicHandlers[$searchField['FieldCode']]
                        = $searchField['Label'];
                }
            }
            $this->advancedHandlers = $this->basicHandlers;

            // Search Mode preferences
            $this->modeOptions = array();
            if (isset($availCriteria['AvailableSearchModes'])) {
                foreach ($availCriteria['AvailableSearchModes'] as $mode) {
                    $this->modeOptions[$mode['Mode']] = array(
                        'Label'=>$mode['Label'], 'Value' => $mode['Mode']
                    );
                    if (isset($mode['DefaultOn'])
                        &&  'y' == $mode['DefaultOn']
                    ) {
                        $this->defaultMode = $mode['Mode'];
                    }
                }
            }

            //expanders
            $this->expanderOptions = array();
            $this->defaultExpanders = array();
            if (isset($availCriteria['AvailableExpanders'])) {
                foreach ($availCriteria['AvailableExpanders'] as $expander) {
                    $this->expanderOptions[$expander['Id']] = array(
                        'Label' => $expander['Label'], 'Value' => $expander['Id']
                    );
                    if (isset($expander['DefaultOn'])
                        && 'y' == $expander['DefaultOn']
                    ) {
                        $this->defaultExpanders[] =  $expander['Id'];
                    }
                }
            }

            //Limiters
            $this->limiterOptions= array();
            if (isset($availCriteria['AvailableLimiters'])) {
                foreach ($availCriteria['AvailableLimiters'] as $limiter) {
                    $val = '';
                    if ('select' == $limiter['Type']) {
                        $val = 'y';
                    }
                    $this->limiterOptions[$limiter['Id']] = array(
                        'Id' => $limiter['Id'],
                        'Label' => $limiter['Label'],
                        'Type' => $limiter['Type'],
                        'LimiterValues' => isset($limiter['LimiterValues'])
                            ? $this->populateLimiterValues(
                                $limiter['LimiterValues']
                            )
                            : array(array('Value' => $val)),
                        'DefaultOn' => isset($limiter['DefaultOn'])
                            ? $limiter['DefaultOn'] : 'n',
                    );

                }

            }
        }
    }

    /**
     * Populate limiter values forom the EDS API INFO method data
     *
     * @param array $limiterValues Limiter values from the API
     *
     * @return array
     */
    protected function populateLimiterValues($limiterValues)
    {
        $availableLimiterValues = array();
        if (isset($limiterValues)) {
            foreach ($limiterValues as $limiterValue) {
                $availableLimiterValues[] = array(
                    'Value' => $limiterValue['Value'],
                    'LimiterValues' => isset($limiterValue['LimiterValues'])
                        ? $this
                            ->populateLimiterValues($limiterValue['LimiterValues'])
                        : null
                );
            }
        }
        return empty($availableLimiterValues) ? null : $availableLimiterValues;
    }

    /**
     * Returns the available limters
     *
     * @return array
     */
    public function getAvailableLimiters()
    {
        return $this->limiterOptions;
    }

    /**
     * Returns the available expanders
     *
     * @return array
     */
    public function getAvailableExpanders()
    {
        return $this->expanderOptions;
    }

    /**
     * Sets the view settings from EDS API info method call data
     *
     * @return number
     */
    protected function populateViewSettings()
    {
        if (isset($this->apiInfo)
            && isset($this->apiInfo['ViewResultSettings'])
        ) {
            //default result Limit
            if (isset($this->apiInfo['ViewResultSettings']['ResultsPerPage'])) {
                $this->defaultLimit
                    = $this->apiInfo['ViewResultSettings']['ResultsPerPage'];
            } else {
                $this->defaultLimit = 20;
            }

            //default view (amount)
            if (isset($this->apiInfo['ViewResultSettings']['ResultListView'])) {
                $this->defaultView = 'list|'
                    . $this->apiInfo['ViewResultSettings']['ResultListView'];
            } else {
                $this->defaultView = 'list|brief';
            }
        }
    }
    /**
     * Obtain limiters to display ont the basic search screen
     *
     * @return array
     */
    public function getSearchScreenLimiters()
    {
        $ssLimiterOptions = array();
        if (isset($this->commonLimiters)) {
            foreach ($this->commonLimiters as $key) {
                $limiter = $this->limiterOptions[$key] ;
                $ssLimiterOptions[$key] = array(
                    'selectedvalue' => 'LIMIT|'.$key.':y',
                    'description' => $limiter['Label'],
                    'selected' => ('y' == $limiter['DefaultOn'])? true : false
                );
            }
        }
        return $ssLimiterOptions;
    }

    /**
     * Obtain expanders to display on the basic search screen
     *
     * @return array
     */
    public function getSearchScreenExpanders()
    {
        $ssExpanderOptions = array();
        if (isset($this->commonExpanders)) {
            foreach ($this->commonExpanders as $key) {
                $expander = $this->expanderOptions[$key];
                $ssExpanderOptions[$key] = array(
                    'selectedvalue' => 'EXPAND:'.$key,
                    'description' => $expander['Label'],
                    'selected' =>(isset($defaultExpander[$key]))? true : false
                );
            }
        }
        return $ssExpanderOptions;
    }

    /**
     * Get default view setting.
     *
     * @return int
     */
    public function getDefaultView()
    {
        $viewArr = explode('|', $this->defaultView);
        return $viewArr[0];
    }

    /**
     * Add a hidden (i.e. not visible in facet controls) filter query to the object.
     *
     * @param string $fq Filter query for Solr.
     *
     * @return void
     */
    public function addHiddenFilter($fq)
    {
        $this->hiddenFilters[] = $fq;
    }

    /**
     * Get an array of hidden filters.
     *
     * @return array
     */
    public function getHiddenFilters()
    {
        return $this->hiddenFilters;
    }
}