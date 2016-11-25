<?php
/**
 * This file is part of the Piksel package.
 *
 * @copyright 2015 Pixopat, Alex Druhet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Piksel\Api;

/**
 * ProgramDataProvider fetch and store data from ws_program
 *
 * ProgramDataProvider is a class that extend the {@link DataProviderBase} class.
 *
 * @author Alex Druhet <alex@pixopat.com>
 * @package Piksel\Api
 */
class ProgramDataProvider extends DataProviderBase
{

    /**
     * Fetch programs from the Piksel account
     *
     * This method has parameters for querying data subset
     * this feature is mainly used for pagination.
     *
     * @param int $start
     * @param int $limit
     * @param string $sortby Optional, possible values: sortnum, dateStart, dateEnd ; default: sortnum
     * @param string $sortdir Optional, possible values: desc, asc ; default: desc
     * @return array|mixed|null
     */
    public function fetchData($start = 0, $limit = 20, $sortby = 'sortnum', $sortdir = 'desc')
    {
        return $this->fetchByProjectUUID($this->config['searchUUID'], $start, $limit, $sortby, $sortdir);
    }

    /**
     * fetchProgramsByRefId
     *
     * Retrieve programs by category reference ID.
     *
     * @param string $refId
     * @param int $start
     * @param int $limit
     * @param string $sortby Optional, possible values: sortnum, dateStart, dateEnd ; default: sortnum
     * @param string $sortdir Optional, possible values: desc, asc ; default: desc
     * @return array|mixed|null
     */
    public function fetchByRefId($refId, $start = 0, $limit = 20, $sortby = 'sortnum', $sortdir = 'desc')
    {

        // Build the query
        $query = sprintf(
            'refid=%s&start=%d&end=%d&sortby=%s&sortdir=%s&include_viewcount=true&include_details=true',
            $refId,
            $start,
            ($start + $limit - 1),
            $sortby,
            $sortdir
        );

        // Return the response data
        $count = 0;
        $data = $this->doRequest($query, 'ws_program', $count);
        $data = isset($data['programs']) ? $data['programs'] : $data;
        $data['totalCount'] = $count;

        return $data;
    }

    /**
     * fetchByProgramUUID
     *
     * Get a program data by a program uuid.
     *
     * @param string $uuid a program uuid
     * @return array|mixed|null
     */
    public function fetchByProgramUUID($uuid)
    {

        // Build the query
        $query = sprintf('v=%s', $uuid);

        // Return the response data
        $data = $this->doRequest($query, 'ws_program');
        $data = isset($data['program']) ? $data['program'] : $data;
//
//        if (isset($data['assetid']) && $asset = self::fetchAssetByVid($data['assetid'])) {
//            $data += $asset;
//        }

        return $data;
    }

    /**
     * fetchByProjectUUID
     *
     * Retrieve videos as programs by a project UUID.
     *
     * @param string $uuid a project uuid
     * @param int $start
     * @param int $limit
     * @param string $sortby Optional, possible values: sortnum, dateStart, dateEnd ; default: sortnum
     * @param string $sortdir Optional, possible values: desc, asc ; default: desc
     * @return array|mixed|null
     */
    public function fetchByProjectUUID($uuid, $start = 0, $limit = 20, $sortby = 'sortnum', $sortdir = 'desc')
    {

        // Build the query
        $query = sprintf(
            'p=%s&start=%d&end=%d&sortby=%s&sortdir=%s&include_viewcount=true&include_details=true',
            $uuid,
            $start,
            ($start + $limit - 1),
            $sortby,
            $sortdir
        );

        // Return the response data
        $count = 0;
        $data = $this->doRequest($query, 'ws_program', $count);
        $data = isset($data['programs']) ? $data['programs'] : $data;
//        if ($count > 0) {
//            // Populate programs results with missing asset properties
//            foreach ($data as &$item) {
//                $item += self::fetchAssetByVid($item['assetid']);
//            }
//        }
        $data['totalCount'] = $count;

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

//        if (isset($data['programs'])) {
            if (strpos($property, '.')) {
                $property = explode('.', $property);
            }
            $data = array_filter(
                $data,
                function ($item) use ($property, $value) {
                    if (!is_array($property) && isset($item[$property])) {
                        if ($item[$property] == $value) {
                            return false;
                        }
                    } elseif (
                        isset($item[$property[0]])
//                        && isset($item[$property[0]][array_pop($property)])
                    ) {
//                        foreach (array_shift($property) as $property2) {
//                            if ($item[$property[0]][array_shift($property)] == $value) {
//                                return false;
//                            }
//                        }
                    }

                    return true;
                }
            );
            if (isset($data['currentCount']) && $data['currentCount'] > 0) {
                $data['currentCount'] = count($data) - 1;
            }
            if (isset($data['totalCount']) && $data['totalCount'] > 0) {
                $data['totalCount'] = count($data) - 1;
            }
//        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function calculateTotalCount()
    {
        $count = null;
//        $data = $this->doRequest('start=0&end=1', 'ws_asset');

        $data = $this->fetchByProjectUUID($this->config['searchUUID'], 0, 1);

//        if (is_array($data) && array_key_exists('totalCount', $data)) {
        if (isset($data['totalCount'])) {
            $count = (int)$data['totalCount'];
        }

        return $count;
    }
}