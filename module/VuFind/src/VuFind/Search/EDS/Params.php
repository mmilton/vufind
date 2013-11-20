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
namespace VuFind\Search\EDS;

class Params extends \VuFind\Search\Base\Params
{
	
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

        if ($options->highlight()) {
            $backendParams->set('highlight', true);
        }
        
        $backendParams->set('facets', $this->getBackendFacetParameters());
        $this->createBackendFilterParameters($backendParams);

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
     *
     * @return void
     */
    public function createBackendFilterParameters(ParamBag $params)
    {
        // Which filters should be applied to our query?
        $filterList = $this->getFilterList();
        if (!empty($filterList)) {
            // Loop through all filters and add appropriate values to request:
            foreach ($filterList as $filterArray) {
                foreach ($filterArray as $filt) {
                    $safeValue = SearchRequestModel::escapeSpecialCharacters($filt['value']);
                    // Standard case:
                    $fq = "{$filt['field']},{$safeValue}";
                    $params->add('filters', $fq);
                }
            }
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
}