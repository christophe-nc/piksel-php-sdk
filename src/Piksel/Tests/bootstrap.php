<?php
/**
 * This file is part of the Piksel package.
 *
 * @copyright 2015 Pixopat, Alex Druhet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This file is necessary for running unit tests
 *
 * First it includes the composer autoload
 * then the piksel config (you should customize it).
 *
 */

include_once(__DIR__ . '/../../../vendor/autoload.php');
include_once(__DIR__ . '/../../../../../../config/piksel.php');
$config = $app['config.piksel'];
$config['debug'] = true;