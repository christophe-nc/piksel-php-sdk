<?php
/**
 * This file is part of the Piksel package.
 *
 * @copyright 2015 Alex Druhet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Piksel\Api;

/**
 * AssetDataProvider fetch and store data from ws_asset & ws_asset_associations
 *
 * AssetDataProvider is a class that extend the {@link DataProviderBase} class.
 *
 * @author Alex Druhet <alex@pixadelic.com>
 * @package Piksel\Api
 */
class AssetDataProvider extends DataProviderBase
{

    /**
     * Fetch assets from the Piksel account
     *
     * This method has parameters for querying data subset
     * this feature is mainly used for pagination.
     *
     * @param int $start Beginning of the data subset, 0 by default
     * @param int $limit Limit of the data subset, 20 by default
     * @param string $sortby Possible values: any asset property. date_start by default
     * @param string $sortdir Possible values: asc or desc. desc by default
     * @return array|null An array of assets
     */
    public function fetchData($start = 0, $limit = 20, $sortby = 'date_start', $sortdir = 'desc')
    {

        // Build the query
        $query = sprintf(
          'start=%d&end=%d&sortby=%s&sortdir=%s&isPublished=true&include_shared=true&assetfiles=true',
          $start,
          ($start + $limit - 1),
          $sortby,
          $sortdir
        );

        // Return the response data
//        return $this->doRequest($query, 'ws_asset');

        $count = 0;
        $data = $this->doRequest($query, 'ws_asset', $count);

//        if ($count > 0) {
//            // Populate programs results with missing asset properties
//            foreach ($data['asset'] as &$item) {
//                $item += self::fetchAssetByVid($item['assetid']);
//            }
//        }

        return $data;
    }

    /**
     * Fetch assets filtered by a tag
     *
     * This method has parameters for querying data subset
     * this feature is mainly used for pagination.
     *
     * @param string $tag A tag
     * @param int $start Beginning of the data subset, 0 by default
     * @param int $limit Limit of the data subset, 20 by default
     * @param string $sortby Possible values: any asset property. date_start by default
     * @param string $sortdir Possible values: asc or desc. desc by default
     * @return array|null An array of assets
     */
    public function fetchAssetsByTag($tag, $start = 0, $limit = 20, $sortby = 'date_start', $sortdir = 'desc')
    {

        // Build the query
        $query = sprintf(
//            'start=%d&end=%d&sortby=%s&sortdir=%s&isPublished=true&tags=%s&include_shared=true&assetfiles=true',
          'start=%d&end=%d&sortby=%s&sortdir=%s&isPublished=true&tags=%s&include_shared=true&assetfiles=true',
          $start,
          ($start + $limit - 1),
          $sortby,
          $sortdir,
          urlencode('%'.$tag.'%')
        );

        // Return the response data
//        return $this->doRequest($query, 'ws_asset');

        $count = 0;
        $data = $this->doRequest($query, 'ws_asset', $count);

//        if ($count > 0) {
//            // Populate programs results with missing asset properties
//            foreach ($data['asset'] as &$item) {
//                $item += self::fetchAssetByVid($item['assetid']);
//            }
//        }

        return $data;
    }

    /**
     * Fetch assets filtered by a tag
     *
     * This method has parameters for querying data subset
     * this feature is mainly used for pagination.
     *
     * @param string $metadata The metadata name
     * @param string $metavalue The metadata value
     * @param int $start Beginning of the data subset, 0 by default
     * @param int $limit Limit of the data subset, 20 by default
     * @param string $sortby Possible values: any asset property. date_start by default
     * @param string $sortdir Possible values: asc or desc. desc by default
     * @return array|null An array of assets
     */
    public function fetchAssetsByMetadata(
      $metadata,
      $metavalue,
      $start = 0,
      $limit = 20,
      $sortby = 'date_start',
      $sortdir = 'desc'
    ) {

        // Build the query
        $query = sprintf(
          'start=%d&end=%d&sortby=%s&sortdir=%s&isPublished=true&metadata=%s&metavalue=%s&include_shared=true&assetfiles=true',
          $start,
          ($start + $limit - 1),
          $sortby,
          $sortdir,
          $metadata,
          urlencode($metavalue)
        );

        $count = 0;
        $data = $this->doRequest($query, 'ws_asset', $count);

        // The availability of a read only token means we are processing
        // with a child account configuration. Consequence is we have to
        // fetch the shared assets and add them to the response
        if ($metadata === 'Categories' && isset($this->config['readOnlyToken'])) {
            $sharedCount = 0;
            $sharedData = $this->doRequest($query, 'ws_asset', $sharedCount, true, $this->config['readOnlyToken']);
            if ($sharedCount > 0) {
                $data['asset'] = array_merge($data['asset'], $sharedData['asset']);
                usort(
                  $data['asset'],
                  function ($a, $b) use ($sortby, $sortdir) {
                      if (isset($a[$sortby]) && isset($b[$sortby])) {

                          $valueA = $a[$sortby];
                          $valueB = $b[$sortby];

                          if ($valueA === $valueB) {

                              return 0;
                          } else {
                              if ($valueA > $valueB) {
                                  if ($sortdir === 'desc') {
                                      return -1;
                                  }
                                  if ($sortdir === 'asc') {
                                      return 1;
                                  }

                                  return -1;
                              } else {
                                  if ($sortdir === 'desc') {
                                      return 1;
                                  }
                                  if ($sortdir === 'asc') {
                                      return -1;
                                  }

                                  return 1;
                              }
                          }
                      }
                  }
                );

                // We cut the collection to the limit to honor pagination process
                // we don't use the $limit variable earlier since we cannot know
                // the available number of results for each set.
                $data['asset'] = array_slice($data['asset'], 0, $limit);

                // We update counts
                $count += $sharedCount;
                $data['totalCount'] = $count;
            }
        }

//        if ($count > 0) {
//            // Populate programs results with missing asset properties
//            foreach ($data['asset'] as &$item) {
//                $item += self::fetchAssetByVid($item['assetid']);
//            }
//        }

        return $data;
    }

    /**
     * Retrieve an asset from a $vid
     *
     * The $vid parameter could be :
     *   - a Kewego sig
     *   - a Piksel asset identifier (assetid)
     *
     * @param string $vid A Kewego sig or a Piksel assetid
     * @param boolean $useReferenceId Force usage of r parameter instead of a
     * @return array An asset as array
     */
    public function fetchAssetByVid($vid, $useReferenceId = false, $cache = true, $publishedOnly = true)
    {
        // Load the right identifier
        // it's possible since the assetid is numeric only
        $identifier = is_numeric($vid) && !$useReferenceId ? 'a' : 'r';

        // Build the query
        if ($publishedOnly) {
            $query = sprintf(
              '%s=%s&isPublished=true&include_shared=true&assetfiles=true',
              $identifier,
              $vid
            );
        } else {
            $query = sprintf(
              '%s=%s&include_shared=true&assetfiles=true',
              $identifier,
              $vid
            );
        }


        $count = 0;

        $data = $this->doRequest($query, 'ws_asset', $count, $cache);

        // Return the response data
        return isset($data['asset']) && $data['asset'][0] ? $data['asset'][0] : $data;
    }

    /**
     * Retrieve a video from its title
     *
     * Fetch the Piksel API to retrieve asset data
     * for a given title
     *
     * @param string $title
     * @return array An asset as array
     */
    public function fetchAssetByTitle($title)
    {

        // Build the query
        $query = sprintf(
          'title=%s&isPublished=true&include_shared=true&assetfiles=true',
          $title
        );

        $data = $this->doRequest($query, 'ws_asset');

        // Return the response data
        return isset($data['asset']) && $data['asset'][0] ? $data['asset'][0] : $data;
    }

    /**
     * Retrieve associated asset of an asset from is assetid
     *
     * @param string $assetId a Piksel assetid
     * @param int $start
     * @param int $limit
     * @return array An array of assets
     */
    public function fetchAssociationsByAssetid($assetId, $start = 0, $limit = 20)
    {

        // Build the query
        $query = sprintf(
          'assetId=%s&start=%d&end=%d',
          $assetId,
          $start,
          ($start + $limit - 1)
        );

        $data = $this->doRequest($query, 'ws_asset_associations');

        // Return the response data
        return $data;
    }

    /**
     * Filter assets or programs by a given property and its value
     *
     * A cleaning-asset helper
     *
     * @param array $data
     * @param $property
     * @param $value
     * @return array
     */
    public function filterAssetsByProperty(array $data, $property, $value)
    {

        if (isset($data['asset'])) {
            $data['asset'] = array_filter(
              $data['asset'],
              function ($item) use ($property, $value) {
                  if (isset($item[$property])) {
                      if ($item[$property] == $value) {
                          return false;
                      }
                  }

                  return true;
              }
            );

            if (isset($data['currentCount'])) {
                $data['currentCount'] = count($data['asset']);
            }
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function calculateTotalCount()
    {
        $count = null;
        $data = $this->doRequest(
          sprintf('start=0&end=1&isPublished=true'),
          'ws_asset'
        );

//        $data = $this->fetchProgramsByUUID($this->config['searchUUID'], 0, 1);

        if (isset($data['totalCount'])) {
            $count = (int)$data['totalCount'];
        }

        return $count;
    }
}