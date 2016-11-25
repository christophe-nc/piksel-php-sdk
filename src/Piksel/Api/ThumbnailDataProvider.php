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
 * ThumbnailDataProvider fetch data from ws_thumbnail
 *
 * ThumbnailDataProvider is a class that extend the {@link DataProviderBase} class.
 *
 * @author Alex Druhet <alex@pixadelic.com>
 * @package Piksel\Api
 */
class ThumbnailDataProvider extends DataProviderBase
{

    /** @var string Store the mandatory asset ID */
    private $assetid;

    /**
     * {@inheritDoc}
     */
    public function fetchData()
    {
        if (!isset($this->assetid)) {
            throw new \Exception('There is no assetID provided in ThumbnailDataProvider.');
        }

        // Build the query
        $query = sprintf(
            'assetId=%s',
            $this->assetid
        );

        $this->assetid = NULL;

        return $this->doRequest($query, $function = 'ws_thumbnail');
    }

    /**
     * Get thumbnail for an asset ID
     *
     * @param $assetid
     * @return array|mixed|null
     * @throws \Exception
     */
    public function get($assetid)
    {
        $this->assetid = $assetid;
        $data = $this->fetchData();
        if (isset($data['failure'])) {
            if ($data['failure']['code'] === 903) {
                return null;
            }
            return false;
        }
        return $data['thumbnails'];
    }

    /**
     * {@inheritDoc}
     */
    public function calculateTotalCount()
    {
        return count($this->data);
    }

}
