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
 * UserTokenDataProvider fetch data from ws_user_token
 *
 * UserTokenDataProvider is a class that extend the {@link DataProviderBase} class.
 *
 * @author Alex Druhet <alex@pixadelic.com>
 * @package Piksel\Api
 */
class UserTokenDataProvider extends DataProviderBase
{

    /**
     * {@inheritDoc}
     */
    public function fetchData()
    {
        // Build the query
        $query = sprintf(
            '/u/%s/p/%s',
            $this->config['api']['username'],
            urlencode(base64_encode($this->config['api']['password']))
        );

        $count = 0;

        return $this->doRequest($query, 'ws_user_token', $count, false);
    }

    /**
     * {@inheritDoc}
     */
    public function calculateTotalCount()
    {
        return count($this->data);
    }

    /**
     * Get an API user token
     *
     * @return string API user token
     */
    public function get()
    {
        $data = $this->getData(TRUE);
        if (isset($data['failure']) || !isset($data['token'])) {
            return false;
        }
        return $data['token'];
    }

}
