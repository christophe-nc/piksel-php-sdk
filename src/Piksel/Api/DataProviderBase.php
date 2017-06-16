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

use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;

/**
 * Base class to extend for a Piksel data provider creation
 *
 * DataProviderBase is a base class.
 *
 * @author Alex Druhet <alex@pixadelic.com>
 * @package Piksel\Api
 */
abstract class DataProviderBase
{
    /** @var string A unique identifier */
    protected $id;

    /**  @var array An array to store the fetched data */
    protected $data;

    /** @var int The total count of fetchable data */
    protected $totalCount;

    /** @var array The Piksel configuration */
    protected $config;

    /** @var boolean Debug mode flag */
    protected $debug = false;


    /**
     * The DataProviderBase constructor must be called with a $config argument
     *
     * @param array $config A config array as following :
     *     ```
     *     $config = array(
     *         'baseURL' => 'https://api-ovp.piksel.com', // The Piksel API base url
     *         'token' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx', // A Piksel API token
     *         'refIDPrefix' => '', // A prefix for handling same category names between two sub accounts
     *         'searchUUID' => 'xxxxxxxx', // A default project UUID to pickup videos
     *         'api' => array(
     *             'username' => 'xxxxxx', // Piksel API user name
     *             'password' => '******', // Piksel API user password
     *         ),
     *         'debug' => true // Optional, false by default
     *     );
     *     ```
     * @throws \Exception Throw an exception with an explicit message in case of failure.
     */
    public function __construct($config)
    {
        // Plug cache activation to
        // debug environment
        if (isset($config['debug'])) {
            $this->debug = $config['debug'];
        }

        $this->config = $config;
    }

    /**
     * Makes a technical name human readable.
     *
     * Sequences of underscores are replaced by single spaces. The first letter
     * of the resulting string is capitalized, while all other letters are
     * turned to lowercase.
     *
     * @param string $text The text to humanize.
     *
     * @return string The humanized text.
     */
    public function humanize($text)
    {
        return ucfirst(
          trim(
            strtolower(
              preg_replace(
                array(
                  '/([A-Z])/',
                  '/[_\s]+/',
                  '/[--\s]+/',
                  '/[-\s]+/',
                ),
                array('_$1', ' ', '-', ' '),
                $text
              )
            )
          )
        );
    }

    /**
     * Returns the ID that uniquely identifies the data provider.
     *
     * @return string The unique ID that uniquely identifies the data provider among all data providers.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the provider ID.
     *
     * @param string $value The unique ID that uniquely identifies the data provider among all data providers.
     */
    public function setId($value)
    {
        $this->id = $value;
    }

    /**
     * Returns the data items currently available.
     *
     * @param boolean $refresh whether the data should be re-fetched from persistent storage.
     *
     * @return array The list of data items currently available in this data provider.
     */
    public function getData($refresh = false)
    {
        if ($this->data === null || $refresh) {
            $this->data = $this->fetchData();
        }

        return $this->data;
    }

    /**
     * Sets the data items for this provider.
     *
     * @param array $value Put the data items into this provider.
     */
    public function setData($value)
    {
        $this->data = $value;
    }

    /**
     * Fetches the data
     *
     * @return array List of data items
     */
    abstract function fetchData();

    /**
     * Clear stored data.
     */
    public function clear()
    {
        $this->data = null;
        $this->id = null;
        $totalCount = null;
    }

    /**
     * Returns the total number of data items.
     *
     * @param boolean $refresh whether the total number of data items should be re-calculated.
     *
     * @return int Total number of possible data items.
     */
    public function getTotalCount($refresh = false)
    {
        if ($this->totalCount === null || $refresh) {
            $this->totalCount = $this->calculateTotalCount();
        }

        return $this->totalCount;
    }

    /**
     * Sets the total number of data items.
     *
     * This method is provided in case when the total number
     * cannot be determined by {@link calculatetotalCount}.
     *
     * @param int $value The total number of data items.
     */
    public function setTotalCount($value)
    {
        $this->totalCount = $value;
    }

    /**
     * Calculates the total number of data items.
     *
     * @return int The total number of data items.
     */
    abstract function calculateTotalCount();

    /**
     * Perform a request
     *
     * Wrapper for requesting the Piksel API
     *
     * @param string $query The well-formed query string
     * @param string $function Optional, the Piksel API method, ws_asset by default
     * @param int $count Optional, a count variable by reference
     * @param boolean $cache Optional force no-cache request
     * @param null $token Optional specify the token to use
     * @return array|mixed|null A data or failure array
     */
    protected function doRequest(
      $query,
      $function = 'ws_asset',
      &$count = 0,
      $cache = true,
      $token = null
    ) {
        $data = null;

        if (!$token) {
            $token = $this->config['token'];
        }

        if ($this->debug) {
            $cache = false;
        }

        // Build the query
        if (substr($query, 0, 1) === '/') {
            $uri = sprintf(
              '%s/ws/%s/api/%s/mode/json/apiv/5%s%s',
              $this->config['baseURL'],
              $function,
              $token,
              $query,
              (!$cache ? '/?ck='.rand() : '')
            );
            // Store related response key
            $responseKey = sprintf('%s', $this->camelize($function));
        } else {
            $uri = sprintf(
              '%s/ws/%s/api/%s/mode/json/apiv/5?%s%s',
              $this->config['baseURL'],
              $function,
              $token,
              $query,
              (!$cache ? '&ck='.rand() : '')
            );
            // Store related response key
            $responseKey = sprintf('%sResponse', $this->camelize($function));
        }

        // Log requested url
        if ($this->debug) {
            error_log($uri);
        }

        $useGoutte = true;

        if (!$useGoutte) {

            if (!$cache) {
                $opts = array(
                  'http' => array(
                    'method' => "GET",
                    'header' => "Cache-Control: private, max-age=0, no-cache, no-store, must-revalidate\r\n"
                      ."Pragma: no-cache\r\n"
                      ."Expires: 0\r\n",
                  ),
                );
            } else {
                $opts = array(
                  'http' => array(
                    'method' => "GET",
                    'header' => "Cache-Control: private, max-age=0, no-cache, no-store, must-revalidate\r\n"
                      ."Pragma: no-cache\r\n"
                      ."Expires: 0\r\n",
                  ),
                );
            }

            $opts['ssl'] = array(
              'verify_peer' => false,
              'verify_peer_name' => false,
            );

            $context = stream_context_create($opts);

            // Create the request
            $data = json_decode(file_get_contents($uri, false, $context), true);

        } else {

            // Build request
            $client = new Client();

            // Uncomment to disable SSL verification (but don't do that please...)
            // @see Guzzle 6 doc
            $guzzleClient = new GuzzleClient(
              array(
                'verify' => false,
              )
            );
            $client->setClient($guzzleClient);

            if (!$cache) {
                // Set header to build a no caching request
                $client->setHeader(
                  'Cache-Control',
                  'private, max-age=0, no-cache, no-store, must-revalidate'
                );
                $client->setHeader('Pragma', 'no-cache');
                $client->setHeader('Expires', '0');
            }

            // Run POST request
            $client->request('GET', $uri);

            // Handle response
            $response = $client->getResponse();
            $data = json_decode($response->getContent(), true);

        }

        // Handle successful response
        if (
          isset($data['response'])
          && isset($data['response']['success'])
          && in_array(
            $data['response']['success']['code'],
            array(
              224, // User token found
              321, // Account Metadata found
              303, // Programs found
              205, // Asset Found
              304, // Assets Found
              325  // Thumbnail found
            )
          )
          && isset($data['response'][$responseKey])
        ) {
            // Handle totalCount
            if (isset($data['response'][$responseKey]['totalCount'])) {
                $count = (int)$data['response'][$responseKey]['totalCount'];
            }
            // Proxifying data
            $data = (array)$data['response'][$responseKey];
        } // Handle failure
        else {
            if (
              isset($data['response'])
              && isset($data['response']['failure'])
            ) {
                $data = $data['response'];
            }
        }

        return $data;
    }

    /**
     * Camelizes a given string.
     *
     * @param string $string Some string
     *
     * @return string The camelized version of the string
     */
    public function camelize($string)
    {
        return strtr(
          ucwords(strtr($string, array('_' => ' '))),
          array(' ' => '')
        );
    }
}