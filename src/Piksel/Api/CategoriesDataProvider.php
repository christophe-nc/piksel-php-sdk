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
 * AssetDataProvider fetch and store filtered data from AccountMetadataDataProvider
 *
 * CategoriesDataProvider is a class that extend the {@link DataProviderBase} class.
 *
 * @author Alex Druhet <alex@pixadelic.com>
 * @package Piksel\Api
 */
class CategoriesDataProvider extends DataProviderBase
{


//    public function __construct($config)
//    {
//        return parent::__construct($config);
//    }

    /**
     * {@inheritDoc}
     */
    public function fetchData()
    {
        $accountMetadataDataProvider = new AccountMetadataDataProvider($this->config);
        $data = $accountMetadataDataProvider->getData();

        if ($data && isset($data['custom'])) {
            foreach ($data['custom'] as $metadata) {
                if ($metadata['metaname']==='Categories') {
                    return explode(',', $metadata['fieldOptions']);
                }
            }
        }

        return false;

//        return explode(',', $data['custom']['Categories']['fieldOptions']);
    }

    /**
     * {@inheritDoc}
     */
    public function calculateTotalCount()
    {
        return count($this->data);
    }

}
