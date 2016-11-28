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

use Symfony\Component\Security\Acl\Exception\Exception;

/**
 * ProgramSearchDataProvider fetch and store data from ws_programs
 *
 * ProgramSearchDataProvider is a class that extend the {@link DataProviderBase} class.
 *
 * @author Alex Druhet <alex@pixadelic.com>
 * @package Piksel\Api
 */
class ProgramSearchDataProvider extends DataProviderBase
{
    /**
     * Search programs from the Piksel account
     *
     * Searches one or more projects for programs matching the search string.
     * The following fields are searched: asset metadata, program title,
     * asset title, asset description, and program description.
     *
     * @param $search_string Search string to search on with length >= 3 characters, else nothing is returned
     * @param $project_uuid  Project uuid found on the get code page
     * @param int $start Beginning of the data subset, 0 by default
     * @param int $limit Limit of the data subset, 20 by default
     * @param string $sort_by default is by search weight, this can be overwritten with: programTitle, assetTitle, programCreation, assetCreation
     * @param string $sort_dir possible values: asc, desc
     * @return array|null An array of programs
     */
    public function fetchData($search_string = '*', $project_uuid = null, $start = 0, $limit = 20, $sort_by = '', $sort_dir = 'desc')
    {

        if (!$project_uuid) {
            throwException(new Exception('Project UUID not provided'));
        }

        // Build the query
        $query = sprintf(
            'p=%s&field&s=%s&start=%d&end=%d&sortBy=%s&sortDir=%s',
            $project_uuid,
            urlencode(base64_encode($search_string)),
            $start,
            ($start + $limit - 1),
            $sort_by,
            $sort_dir
        );

        // Return the response data
        $data = $this->doRequest($query, 'ws_search_programs');
        if (isset($data[0]) && isset($data[0]['totalCount'])) {
            $data['totalCount'] = (int)$data[0]['totalCount'];
            unset($data[0]);
            $this->setTotalCount($data['totalCount']);
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function calculateTotalCount()
    {
        return $this->getTotalCount();
    }

}