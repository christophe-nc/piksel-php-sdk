<?php
/**
 * This file is part of the Piksel package.
 *
 * @copyright 2015 Alex Druhet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Piksel\Tests;

use Piksel\Piksel;

/**
 * Piksel test class
 *
 * Extends PHPUnit_Framework_TestCase
 *
 * @author Alex Druhet <alex@pixadelic.com>
 * @package Piksel\Tests
 */
class PikselTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test if configuration is passed
     */
    public function testHasConfig()
    {
        $message = null;

        if (!array_key_exists('config', $GLOBALS)) {
            $message = "\$config is not defined.".PHP_EOL.
              "See README.md for details".PHP_EOL;
        }
        if (!array_key_exists('baseURL', $GLOBALS['config'])) {
            $message .= "\$config['baseURL'] is not defined.".PHP_EOL.
              "See README.md for details".PHP_EOL;
        }
        if (!array_key_exists('token', $GLOBALS['config'])) {
            $message .= "\$config['token'] is not defined.".PHP_EOL.
              "See README.md for details".PHP_EOL;
        }
        if ($message) {
            die($message);
        }

        echo 'Piksel configuration is ok.'.PHP_EOL;
    }

    /**
     * Test if Piksel is delivering categories
     */
    public function testGetCategories()
    {
        global $config;

        $piksel = new Piksel($config);
        $count = count($piksel->getCategories());

        $this->assertGreaterThan(0, $count);

        if (is_int($count)) {
            echo sprintf('%s categories found', $count).PHP_EOL;
        }

    }

    /**
     * Test how many assets we can get from Piksel
     */
    public function testGetTotalCount()
    {
        global $config;

        $piksel = new Piksel($config);
        $count = $piksel->getTotalCount();

        $this->assertGreaterThan(0, $count);

        if (is_int($count)) {
            echo sprintf('%s assets found', $count).PHP_EOL;
        }
    }

}
