<?php
/**
 * This file is part of the Piksel package.
 *
 * @copyright 2015 Pixopat, Alex Druhet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Piksel\Provider\Silex;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Application;
use Piksel\Piksel;

/**
 * PikselServiceProvider Class
 *
 * @package Piksel\Provider\Silex
 */
class PikselServiceProvider implements ServiceProviderInterface
{

    /**
     * {@inheritdoc}
     *
     * @param Container $app
     */
    public function register(Container $app)
    {
        $app['piksel'] = function () use ($app) {
            $config = (array)$app['config.piksel'];
            $config['debug'] = $app['debug'];
            return new Piksel($config);
        };
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     *
     * @param Container $app
     */
    public function boot(Container $app)
    {
    }

}