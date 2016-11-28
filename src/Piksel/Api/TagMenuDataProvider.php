<?php
/**
 * This file is part of the Piksel package.
 *
 * @copyright 2016 Alex Druhet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Piksel\Api;

/**
 * AssetDataProvider fetch and store filtered data from AccountMetadataDataProvider
 *
 * TagMenuDataProvider is a class that extend the {@link DataProviderBase} class.
 *
 * @author Alex Druhet <alex@pixadelic.com>
 * @package Piksel\Api
 */
class TagMenuDataProvider extends DataProviderBase
{

    /**
     * {@inheritDoc}
     */
    public function fetchData()
    {
        $accountMetadataDataProvider = new AccountMetadataDataProvider($this->config);
        $data = $accountMetadataDataProvider->getData();

        if ($data && isset($data['custom'])) {
            foreach ($data['custom'] as $metadata) {
                if ($metadata['metaname'] === 'tag_menu') {
                    return explode(',', $metadata['fieldOptions']);
                }
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function calculateTotalCount()
    {
        return count($this->data);
    }

}
