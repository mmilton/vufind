<?php
/**
 * EDS API Params
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
namespace VuFind\Search\eds;
use VuFindSearch\ParamBag;
use EBSCO\EdsApi\SearchRequestModel;

class Params extends \VuFind\Search\Base\Params
{
	/**
	 * Settings for the date facet only
	 *
	 * @var array
	 */
	protected $dateFacetSettings = array();
	
	/**
	 * Additional filters to display as side facets
	 * 
	 * @var array
	 */
	protected $extraFilterList = array();
	
	/**
	 * property to determine if the request using this parameters objects is for setup only, 
	 * 
	 * @var boolean
	 */
	public $isSetupOnly = false;
	/**
	 * Pull the search parameters
	 *
	 * @param \Zend\StdLib\Parameters $request Parameter object representing user
	 * request.
	 *
	 * @return void
	 */
	public function initFromRequest($request)
	{
		parent::initFromRequest($request);
	}
	
    /**
     * Create search backend parameters for advanced features.
     *
     * @return ParamBag
     */
    public function getBackendParameters()
    {
        $backendParams = new ParamBag();

        $options = $this->getOptions();

        // The "relevance" sort option is a VuFind reserved word; we need to make
        // this null in order to achieve the desired effect with Summon:
        $sort = $this->getSort();
        $finalSort = ($sort == 'relevance') ? null : $sort;
        $backendParams->set('sort', $finalSort);

        if ($options->getHighlight()) {
            $backendParams->set('highlight', true);
        }
        
        $view = $options->getView();
        if(isset($view))
        	$backendParams->set('view', $view);
        
        $mode= $options->getDefaultMode();
        if(isset($mode))
        	$backendParams->set('searchMode', $mode);
     
        //process the setup only parameter
        if(true == $this->isSetupOnly)
        	$backendParams->set('setuponly',$this->isSetupOnly);
        $this->createBackendFilterParameters($backendParams, $options);

        return $backendParams;
    }

    /**
     * Set up facets based on VuFind settings.
     *
     * @return array
     */
    protected function getBackendFacetParameters()
    {
        $config = $this->getServiceLocator()->get('VuFind\Config')->get('EDS');
        $defaultFacetLimit = isset($config->Facet_Settings->facet_limit)
            ? $config->Facet_Settings->facet_limit : 30;

        $finalFacets = array();
        foreach ($this->getFullFacetSettings() as $facet) {
            // See if parameters are included as part of the facet name;
            // if not, override them with defaults.
            $parts = explode(',', $facet);
            $facetName = $parts[0];
            $defaultMode = ($this->getFacetOperator($facet) == 'OR') ? 'or' : 'and';
            $facetMode = isset($parts[1]) ? $parts[1] : $defaultMode;
            $facetPage = isset($parts[2]) ? $parts[2] : 1;
            $facetLimit = isset($parts[3]) ? $parts[3] : $defaultFacetLimit;
            $facetParams = "{$facetMode},{$facetPage},{$facetLimit}";
            $finalFacets[] = "{$facetName},{$facetParams}";
        }
        return $finalFacets;
    }

    /**
     * Set up filters based on VuFind settings.
     *
     * @param ParamBag $params Parameter collection to update
     * @param Options $options Options from which to add extra filter parameters
     * @return void
     */
    public function createBackendFilterParameters(ParamBag $params, Options $options)
    {
        // Which filters should be applied to our query?
        $filterList = $this->getFilterList();
        if (!empty($filterList)) {
            // Loop through all filters and add appropriate values to request:
            foreach ($filterList as $filterArray) {
                foreach ($filterArray as $filt) {
                	$safeValue = SearchRequestModel::escapeSpecialCharacters($filt['value']);
					// Standard case:
					$fq = "{$filt['field']}:{$safeValue}";
                	$params->add('filters', $fq);
                }
            }
        }
        $this->addLimitersAsCheckboxFacets($options);
        $this->addExpandersAsCheckboxFacets($options);
    }
    
    /**
     * Set up limiter based on VuFind settings.
     *
     * @param ParamBag $params Parameter collection to update
     *
     * @return void
     */
    public function createBackendLimiterParameters(ParamBag $params)
    {
    	//group limiters with same id together
		$edsLimiters = array();
    	foreach($this->limiters as $limiter){
    		if(isset($limiter) &&!empty($limiter)){
    			//split the id/value
    			$pos = strpos($limiter, ':');
    			$key =  substr($limiter, 0, $pos);
    			$value = substr($limiter, $pos + 1);
    			$value = SearchRequestModel::escapeSpecialCharacters($value);
    			if(!isset($edsLimiters[$key]))
    				$edsLimiters[$key] = $value;
    			else 
    				$edsLimiters[$key] = $edsLimiters[$key].','.$value;
    		}
    	}    		
    	if(!empty($edsLimiters)){
    		foreach ($edsLimiters as $key => $value){
    			$params->add('limiters', $key.':'.$value);
    		}
    	}
    }
    

	
    /**
     * Set up expanders based on VuFind settings.
     *
     * @param ParamBag $params Parameter collection to update
     *
     * @return void
     */
    public function createBackendExpanderParameters(ParamBag $params)
    {
    	// Which filters should be applied to our query?
    	if (!empty($this->expanders)) {
    		// Loop through all filters and add appropriate values to request:
    		$value = '';
    		foreach ($this->expanders as $expander) {
				if(!empty($value))
					$value = $value.','.$expander;
				else 
					$value = $expander;
    		}
    		if(!empty($value))
	    		$params->add('expander', $value);
    	}
    }
    

    /**
     * Return the value for which search view we use
     *
     * @return string
     */
    public function getView()
    {
    	return 'list';
    }
    
    /**
     * Add a field to facet on.
     *
     * @param string $newField Field name
     * @param string $newAlias Optional on-screen display label
     * @param bool   $ored     Should we treat this as an ORed facet?
     *
     * @return void
     */
    public function addFacet($newField, $newAlias = null, $ored = false)
    {
    	// Save the full field name (which may include extra parameters);
    	// we'll need these to do the proper search using the Summon class:
    	if (strstr($newField, 'PublicationDate')) {
    		// Special case -- we don't need to send this to the EDS API,
    		// but we do need to set a flag so VuFind knows to display the
    		// date facet control.
    		$this->dateFacetSettings[] = 'PublicationDate';
    	} else {
    		$this->fullFacetSettings[] = $newField;
    	}
    
    	// Field name may have parameters attached -- remove them:
    	$parts = explode(',', $newField);
    	return parent::addFacet($parts[0], $newAlias, $ored);
    }
    
    /**
     * Get the full facet settings stored by addFacet -- these may include extra
     * parameters needed by the search results class.
     *
     * @return array
     */
    public function getFullFacetSettings()
    {
    	return isset($this->fullFacetSettings) ? $this->fullFacetSettings : array();
    }
    
	/**
	 * Apply applied limiters
	 *
	 * @param \Zend\StdLib\Parameters $request Parameter object representing user
	 * request.
	 *
	 * @return string
	 */
	protected function initExpanders($request)
	{
		$vfExpanders= $request->get('expander');
		if (!empty($vfExpanders)) {
			if (is_array($vfExpanders)) {
				foreach ($vfExpanders as $current) {
					$this->addExpander($current);
				}
			} else {
				$this->addExpander($vfExpanders);
			}
		}
	}
	
	/**
	 * Get a user-friendly string to describe the provided facet field.
	 *
	 * @param string $field Facet field name.
	 *
	 * @return string       Human-readable description of field.
	 */
	public function getFacetLabel($field)
	{
		return isset($field) ? $field : "Other";
	}
	
	/**
	 * Get the date facet settings stored by addFacet.
	 *
	 * @return array
	 */
	public function getDateFacetSettings()
	{
		return $this->dateFacetSettings;
	}
	

	/**
	 * Populate common limiters as checkbox facets
	 * @param unknown $options
	 */
	public function addLimitersAsCheckboxFacets($options)
	{
		$ssLimiters = $options->getSearchScreenLimiters();
		if(isset($ssLimiters)){
			foreach($ssLimiters as $key => $ssLimiter)
				$this->addCheckboxFacet($ssLimiter['selectedvalue'], $ssLimiter['description']);
				
		}
	}
	
	/**
	 * Populate expanders as checkbox facets
	 * @param unknown $options
	 */
	public function addExpandersAsCheckboxFacets($options)
	{
		$availableExpanders = $options->getSearchScreenExpanders();
		if(isset($availableExpanders)){
			foreach($availableExpanders as $key => $expander)
				$this->addCheckboxFacet($expander['selectedvalue'], $expander['description']);
	
		}
	}
	
	
}