<?php
/**
 * Factory for record collection.
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
namespace VuFindSearch\Backend\EdsApi\Response;

use VuFindSearch\Response\RecordCollectionFactoryInterface;
use VuFindSearch\Exception\InvalidArgumentException;

/**
 * Factory for record collection.
 * @category VuFind2
 * @package  Search
 * @author   Michelle Milton <mmilton@epnet.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org
 */
class RecordCollectionFactory implements RecordCollectionFactoryInterface
{
    /**
     * Factory to turn data into a record object.
     *
     * @var Callable
     */
    protected $recordFactory;

    /**
     * Class of collection.
     *
     * @var string
     */
    protected $collectionClass;

    /**
     * Constructor.
     *
     * @param Callable $recordFactory   Record factory callback
     * @param string   $collectionClass Class of collection
     *
     * @return void
     */
    public function __construct($recordFactory = null, $collectionClass = null)
    {
        if (!is_callable($recordFactory)) {
            throw new InvalidArgumentException('Record factory must be callable.');
        }
        $this->recordFactory = $recordFactory;
        $this->collectionClass = (null === $collectionClass)
            ? 'VuFindSearch\Backend\EdsApi\Response\RecordCollection'
            : $collectionClass;
    }

    /**
     * Return record collection.
     *
     * @param array $response EdsApi search response
     *
     * @return RecordCollection
     */
    public function factory($response)
    {
    	if (!is_array($response)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unexpected type of value: Expected array, got %s',
                    gettype($response)
                )
            );
         }
         $collection = new $this->collectionClass($response);
         //obtain path to records
         //TODO:: This will only work for the search response, not Retrieve!
         //need to change the value that is being set from the EBSCO module
       	$records = array();
       	if(isset($response['SearchResponseMessageGet']) &&  
       	  isset($response['SearchResponseMessageGet']['SearchResult']) &&
       	  isset($response['SearchResponseMessageGet']['SearchResult']['Data']) &&
       	  isset($response['SearchResponseMessageGet']['SearchResult']['Data']['Records']) ){
       		$records = $response['SearchResponseMessageGet']['SearchResult']['Data']['Records'];
        }

        foreach ($records as $record) {
            $collection->add(call_user_func($this->recordFactory, $record));
        }
        
    	return $collection;
    }

}