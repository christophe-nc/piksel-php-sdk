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
 * AccountMetadataDataProvider fetch and store data from ws_account_metadata
 *
 * AccountMetadataDataProvider is a class that extend the {@link DataProviderBase} class.
 *
 * @author Alex Druhet <alex@pixopat.com>
 * @package Piksel\Api
 */
class AccountMetadataDataProvider extends DataProviderBase
{

    /**
     * {@inheritDoc}
     */
    public function fetchData()
    {
        return $this->doRequest('', $function = 'ws_account_metadata');
    }

    /**
     * {@inheritDoc}
     */
    public function calculateTotalCount()
    {
        return count($this->data);
    }

}
