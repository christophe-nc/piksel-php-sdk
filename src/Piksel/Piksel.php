<?php
/**
 * This file is part of the Piksel package.
 *
 * @copyright 2015 Alex Druhet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Piksel;

use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;
use Piksel\Api\AssetDataProvider;
use Piksel\Api\CategoriesDataProvider;
use Piksel\Api\ProgramDataProvider;
use Piksel\Api\ProgramSearchDataProvider;
use Piksel\Api\TagMenuDataProvider;
use Piksel\Api\ThumbnailDataProvider;
use Piksel\Api\UserTokenDataProvider;
use Piksel\Entity\Category;
use Piksel\Entity\Video;

/**
 * The Piksel class wrap all the logical process for accessing data from the Piksel API through clean objects
 *
 * This class offer methods returning two objects types : {@link Category} and {@link Video}.
 *
 * @author Alex Druhet <alex@pixadelic.com>
 * @package Piksel
 */
class Piksel
{

    /** @var string Current library version */
    const VERSION = '1.0.0-DEV';

    /** @var array Store the Piksel configuration */
    private $config;

    /** @var int Store the total count of videos available in the Piksel account that we use */
    private $totalCount;

    /** @var CategoriesDataProvider A static storage of fetched categories data */
    private $categoriesDataProvider;

    /** @var array A static collection of Category objects */
    private $categoryCollection;

    /** @var AssetDataProvider A static storage of fetched assets data */
    private $assetDataProvider;

    /** @var array A static collection of Video objects */
    private $videoCollection;

    /** @var TagMenuDataProvider A static storage of fetched categories data */
    private $tagMenuDataProvider;

    /** @var array A static collection of Tags keys containing objects */
    private $tagMenuCollection;

    /** @var ProgramDataProvider A static storage of fetched program data */
    private $programDataProvider;

    /** @var ProgramSearchDataProvider A static storage of program searched data */
    private $programSearchDataProvider;

    /** @var array A static collection of UUID programs keys containing objects */
    private $programCollection;

    /**
     * The Piksel constructor must be called with a $config argument
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
    public function __construct(array $config)
    {

        if (!isset($config['baseURL'])) {
            throw new \Exception(
              'There is no API base URL provided in your Piksel config.'
            );
        }

        if (!isset($config['token'])) {
            throw new \Exception(
              'There is no account token provided in your Piksel config.'
            );
        }

        if (!isset($config['clientToken'])) {
            throw new \Exception(
              'There is no client token provided in your Piksel config.'
            );
        }

        if (!isset($config['searchUUID'])) {
            throw new \Exception(
              'There is no default project UUID provided in your Piksel config.'
            );
        }

        if (!isset($config['api'])) {
            throw new \Exception(
              'There is no API configuration provided in your Piksel config.'
            );
        }

        if (!isset($config['api']['username'])) {
            throw new \Exception(
              'There is no api username provided in your Piksel config.'
            );
        }

        if (!isset($config['api']['password'])) {
            throw new \Exception(
              'There is no api password provided in your Piksel config.'
            );
        }

        $this->config = $config;

        if (!$this->categoriesDataProvider) {
            $this->categoriesDataProvider = new CategoriesDataProvider($this->config);
        }

        if (!$this->tagMenuDataProvider) {
            $this->tagMenuDataProvider = new TagMenuDataProvider($this->config);
        }

        if (!$this->assetDataProvider) {
            $this->assetDataProvider = new AssetDataProvider($this->config);
        }

        if (!$this->programDataProvider) {
            $this->programDataProvider = new ProgramDataProvider($this->config);
        }

        if (!$this->programSearchDataProvider) {
            $this->programSearchDataProvider = new ProgramSearchDataProvider($this->config);
        }

        if (!isset($this->config['debug'])) {
            $this->config['debug'] = false;
        }

        if (!$this->config['debug']) {

            if (session_id() === '') {
                session_start();
            }

            if (!isset($_SESSION['videoCollection'])) {
                $_SESSION['videoCollection'] = array();
            }
            $this->videoCollection = &$_SESSION['videoCollection'];

            if (!isset($_SESSION['tagCollection'])) {
                $_SESSION['tagCollection'] = array();
            }
            $this->tagCollection = &$_SESSION['tagCollection'];

            if (!isset($_SESSION['programCollection'])) {
                $_SESSION['programCollection'] = array();
            }
            $this->programCollection = &$_SESSION['programCollection'];

        }

    }

    /**
     * Return an array of tags
     *
     * This method fills the $tagMenuCollection
     * from $tagMenuDataProvider.
     *
     * @return array A collection of tags
     */
    public function getTagMenu()
    {
        if (!$this->tagMenuCollection) {
            $tags = $this->tagMenuDataProvider->getData();
            if ($tags) {
                foreach ($tags as $tag) {
                    $this->tagMenuCollection[trim($tag)] = trim($tag);
                }
            }
        }

        return $this->tagMenuCollection;
    }

    /**
     * Return the total count of videos available in the Piksel account that we use
     *
     * This method fills the $totalCount
     * from $assetDataProvider.
     *
     * @return int The total count of videos available
     */
    public function getTotalCount()
    {
        if (!$this->totalCount) {
//            $this->totalCount = $this->assetDataProvider->getTotalCount();
            $this->totalCount = $this->programDataProvider->getTotalCount();
        }

        return $this->totalCount;
    }

    /**
     * Return an array of Video objects
     *
     * This method fills the $videoCollection
     * from $programDataProvider. A filter to remove
     * assets marked as hidden is applied.
     *
     * @return array A collection of Video objects
     */
    public function getLatestVideos()
    {
        if (!$this->videoCollection) {
            $data = $this->programDataProvider->fetchByProjectUUID(
              $this->config['searchUUID'],
              0,
              20,
              'dateStart',
              'desc'
            );
            $data = $this->programDataProvider->filterAssetsByProperty(
              $data,
              'isHidden',
              1
            );
//            $data = $this->programDataProvider->filterAssetsByProperty(
//                $data,
//                'isPublished',
//                1
//            );
//            $data = $this->programDataProvider->filterAssetsByProperty($data, 'asset.metadatas.validation', false);
            if ($data['totalCount'] === 0 || !count($data)) {
//                throw new \Exception('No programs found');
                return false;
            }
            foreach ($data as $program) {
                if (is_array($program)) {
                    $video = new Video($program);
                    $video->setLastModified(new \DateTime());
                    $this->videoCollection[$video->getSlug()] = $video;
                }
            }
        }

        return $this->videoCollection;
    }

    /**
     * Return an array of Video objects
     *
     * This method fills the $videoCollection
     * from $assetDataProvider.
     *
     * @return array A collection of Video objects
     */
    public function getRawLatestVideos()
    {
        if (!$this->videoCollection) {
            $data = $this->assetDataProvider->fetchData();
            if ($data['totalCount'] === 0 || !count($data)) {
                return false;
            }
            foreach ($data['asset'] as $asset) {
                if (is_array($asset)) {
                    $video = new Video($asset);
                    $video->setLastModified(new \DateTime());
                    $this->videoCollection[$video->getSlug()] = $video;
                }
            }
        }

//        $this->sortVideoCollection();

        return array_slice($this->videoCollection, 0, 20);
    }

    /**
     * Sort stored video collection
     *
     * @param string $sortBy
     * @param string $sortDir
     */
    public function sortVideoCollection(
      $sortBy = 'lastModified',
      $sortDir = 'desc'
    ) {
        $method = 'get'.ucfirst($sortBy);
        usort(
          $this->videoCollection,
          function ($a, $b) use ($method) {

              $ca = '';
              $cb = '';

              if ($method === 'getLastModified') {
                  $ca = $a->$method()->getTimestamp();
                  $cb = $b->$method()->getTimestamp();
              } elseif (method_exists($a, $method)) {
                  $ca = $a->$method();
                  $cb = $b->$method();
              }

              return strnatcmp(
                $ca,
                $cb
              );
          }
        );

        if ($sortDir === 'desc') {
            $this->videoCollection = array_reverse(
              $this->videoCollection
            );
        }
    }

    /**
     * Return a Video object from is slug property
     *
     * DON'T USE IT!
     * This method is not working yet
     * since we can't build back a title
     * just from a slug
     *
     * @param string $slug The searched video slug
     * @return Video A video object
     * @throws \Exception If the video is not found
     */
    public function getVideoBySlug($slug)
    {
        if ($this->videoCollection && array_key_exists(
            $slug,
            $this->videoCollection
          )
        ) {
            return $this->videoCollection[$slug];
        }

        // TODO: Implement a process to rebuild the title from a slug
        $title = $slug;

        $data = $this->assetDataProvider->fetchAssetByTitle($title);
        if (!array_key_exists('assetid', $data)) {
            throw new \Exception('No video found');
        }
        $video = new Video($data);
        $this->videoCollection[$video->getSlug()] = $video;

        return $video;
    }

    /**
     * Retrieve a Category object with its videos.
     *
     * This method have the ability to fetch the category assets
     * from the $assetDataProvider and to store them as a Video objects collection
     * in the videos property of the requested Category object.
     *
     * @param string $slug The requested Category slug
     * @param int $start Beginning of the Video collection subset for pagination, 0 by default
     * @param int $limit Limit of the Video collection subset for pagination, 20 by default
     * @param string $sortby Possible values sortnum, dateStart, dateEnd, viewcount. sortnum by default
     * @param string $sortdir Optional, possible values: desc, asc ; default: desc
     * @return Category A Category object with its associated videos
     */
    public function getCategoryBySlug(
      $slug,
      $start = 0,
      $limit = 20,
      $sortby = 'date_start',
      $sortdir = 'desc'
    ) {
        $videos = null;

        if (is_array($this->categoryCollection)) {
            if (array_key_exists($slug, $this->categoryCollection)) {
                $videos = $this->categoryCollection[$slug]->getVideos();
            }
        } else {
            $this->getCategories();
        }
        if (!$videos && $this->categoryCollection[$slug]->getTotalCount() > 0) {
            $title = $this->assetDataProvider->humanize($slug);
            $assets = $this->assetDataProvider->fetchAssetsByMetadata(
              'Categories',
              $title,
              $start,
              $limit,
              $sortby,
              $sortdir
            );
            if (array_key_exists('failure', $assets)) {
                return false;
            }
            $assets = $this->assetDataProvider->filterAssetsByProperty(
              $assets,
              'isHidden',
              1
            );
            $count = $assets['totalCount'];
            unset($assets['totalCount']);
            $videos = array();
            if ($count > 0) {
                foreach ($assets['asset'] as $asset) {
                    $video = new Video($asset);
                    $videos[$video->getSlug()] = $video;
                }
            }
            if (!array_key_exists($slug, $this->categoryCollection)) {
                $this->categoryCollection[$slug] = new Category($title, $slug);
            }
            $this->categoryCollection[$slug]->setTotalCount($count);
            $this->categoryCollection[$slug]->setVideos($videos);
        }

        // Return the data
        return $this->categoryCollection[$slug];
    }

    /**
     * Return an array of Category objects
     *
     * This method fills the $categoryCollection
     * from $categoriesDataProvider.
     *
     * @return array A collection of Category objects
     */
    public function getCategories()
    {
        if (!$this->categoryCollection) {
            $categories = $this->categoriesDataProvider->getData();
            if ($categories) {
                foreach ($categories as $categoryTitle) {
                    $category = new Category($categoryTitle);
                    $this->categoryCollection[$category->getSlug()] = $category;
                }
            }
        }

        return $this->categoryCollection;
    }

//    /**
//     * Retrieve a Category object with its videos.
//     *
//     * This method have the ability to fetch the category assets
//     * from the $assetDataProvider and to store them as a Video objects collection
//     * in the videos property of the requested Category object.
//     *
//     * @param string $slug The requested Category slug
//     * @param int $start Beginning of the Video collection subset for pagination, 0 by default
//     * @param int $limit Limit of the Video collection subset for pagination, 20 by default
//     * @param string $sortby Possible values sortnum, dateStart, dateEnd, viewcount. sortnum by default
//     * @param string $sortdir Optional, possible values: desc, asc ; default: desc
//     * @return Category A Category object with its associated videos
//     */
//    public function getCategoryBySlug(
//        $slug,
//        $start = 0,
//        $limit = 20,
//        $sortby = 'sortnum',
//        $sortdir = 'desc'
//    ) {
//        $videos = null;
//
//        if (is_array($this->categoryCollection)) {
//            if (array_key_exists($slug, $this->categoryCollection)) {
//                $videos = $this->categoryCollection[$slug]->getVideos();
//            }
//        } else {
//            $this->getCategories();
//        }
//        if (!$videos && $this->categoryCollection[$slug]->getTotalCount() > 0) {
//            $title = $this->assetDataProvider->humanize($slug);
//            $refId = $this->config['refIDPrefix'].$this->assetDataProvider->camelize(
//                    $title
//                );
//            $programs = $this->programDataProvider->fetchByRefId(
//                $refId,
//                $start,
//                $limit,
//                $sortby,
//                $sortdir
//            );
//            if (array_key_exists('failure', $programs)) {
//                return false;
//            }
//            $programs = $this->programDataProvider->filterAssetsByProperty(
//                $programs,
//                'isHidden',
//                1
//            );
//            $count = $programs['totalCount'];
//            unset($programs['totalCount']);
//            $videos = array();
//            if ($count > 0) {
//                foreach ($programs as $program) {
//                    $video = new Video($program);
//                    $videos[$video->getSlug()] = $video;
//                }
//            }
//            if (!array_key_exists($slug, $this->categoryCollection)) {
//                $this->categoryCollection[$slug] = new Category($title, $slug);
//            }
//            $this->categoryCollection[$slug]->setTotalCount($count);
//            $this->categoryCollection[$slug]->setVideos($videos);
//        }
//
//        // Return the data
//        return $this->categoryCollection[$slug];
//    }

    /**
     * Returns the videos total count of a Category object from its slug
     *
     * This method is convenient for a pagination purpose
     * in a controller or a view
     *
     * @param string $slug A Category slug
     * @return int Return the videos total count
     */
    public function getCategoryTotalCountBySlug($slug)
    {
        $count = 0;
        if (is_array($this->categoryCollection)) {
            if (isset($this->categoryCollection[$slug])) {
                $count = $this->categoryCollection[$slug]->getTotalCount();
            }
        } else {
            $this->getCategories();
        }
        if (!$count) {
            $title = $this->assetDataProvider->humanize($slug);
            $assets = $this->assetDataProvider->fetchAssetsByMetadata(
              'Categories',
              $title,
              0,
              1
            );
            if (array_key_exists('failure', $assets)) {
                return false;
            }
            $count = $assets['totalCount'];

            if (!isset($this->categoryCollection[$slug])) {
                $this->categoryCollection[$slug] = new Category($title, $slug);
            }

            $this->categoryCollection[$slug]->setTotalCount($count);

        }

        return $count;
    }

//    /**
//     * Returns the videos total count of a Category object from its slug
//     *
//     * This method is convenient for a pagination purpose
//     * in a controller or a view
//     *
//     * @param string $slug A Category slug
//     * @return int Return the videos total count
//     */
//    public function getCategoryTotalCountBySlug($slug)
//    {
//        $count = 0;
//        if (is_array($this->categoryCollection)) {
//            if (isset($this->categoryCollection[$slug])) {
//                $count = $this->categoryCollection[$slug]->getTotalCount();
//            }
//        } else {
//            $this->getCategories();
//        }
//        if (!$count) {
//            $title = $this->assetDataProvider->humanize($slug);
//            $refId = $this->config['refIDPrefix'].$this->assetDataProvider->camelize(
//                    $title
//                );
//            $programs = $this->programDataProvider->fetchByRefId($refId, 0, 1);
//            if (array_key_exists('failure', $programs)) {
//                return false;
//            }
//            $count = $programs['totalCount'];
//
//            if (!isset($this->categoryCollection[$slug])) {
//                $this->categoryCollection[$slug] = new Category($title, $slug);
//            }
//
//            $this->categoryCollection[$slug]->setTotalCount($count);
//
//        }
//
//        return $count;
//    }

    /**
     * Get and set associated data of a Video
     *
     * @param string $slug A Video slug
     * @param string $id A piksel assetId
     * @return mixed
     * @throws \Exception
     */
    public function getAssociatedDataBySlug($slug, $id)
    {
        if (!isset($this->videoCollection[$slug])) {
            $this->videoCollection[$slug] = $this->getVideoByVid($id);
        }
        if (!$this->videoCollection[$slug]->getAssociatedData()) {
            $data = $this->assetDataProvider->fetchAssociationsByAssetid(
              $this->videoCollection[$slug]->getId()
            );
            if (array_key_exists('failure', $data)) {
                return false;
            }
            $this->videoCollection[$slug]->setAssociatedData($data);
        }

        return $this->videoCollection[$slug]->getAssociatedData();
    }

    /**
     * Return a Video object from a vid
     *
     * The vid could be either a Kewego sig
     * a Piksel assetid or a Piksel program UUID
     *
     * @param $vid A Kewego sig, a Piksel assetid or a Piksel program UUID
     * @return Video A Video object
     * @throws \Exception If the video is not found
     */
    public function getVideoByVid($vid)
    {
        if (is_array($this->videoCollection)) {
            foreach ($this->videoCollection as $video) {
                if ($video->getId() === $vid) {
                    return $video;
                }
            }
        }

        $data = $this->assetDataProvider->fetchAssetByVid($vid);

        if (array_key_exists('failure', $data)) {
            $data = $this->programDataProvider->fetchByProgramUUID($vid);
        }

        if (!array_key_exists('assetid', $data)) {
            return false;
        }

        $video = new Video($data);

        $this->videoCollection[$video->getSlug()] = $video;

        return $video;
    }

    /**
     * Get videos by tag
     *
     * Retrieve Videos by tags
     *
     * @param string $tag A tag
     * @param int $start Beginning of the Video collection subset for pagination, 0 by default
     * @param int $limit Limit of the Video collection subset for pagination, 20 by default
     * @param string $sortby Possible values sortnum, dateStart, dateEnd, viewcount. sortnum by default
     * @param string $sortdir Possible values: desc or asc
     * @return mixed
     */
    public function getVideosByTag(
      $tag,
      $start = 0,
      $limit = 20,
      $sortby = 'date_start',
      $sortdir = 'desc'
    ) {
        $totalRequired = $start > 0 ? $start + $limit : $limit;
        if (
          !isset($this->tagCollection[$tag]) ||
          !isset($this->tagCollection[$tag]['videos']) ||
          (isset($this->tagCollection[$tag]['videos']) && count(
              $this->tagCollection[$tag]['videos']
            ) <= $totalRequired)
        ) {
            $data = $this->assetDataProvider->fetchAssetsByTag(
              $tag,
              $start,
              $limit,
              $sortby,
              $sortdir
            );
            if (!array_key_exists('asset', $data)) {
                return false;
            }
            $data = $this->assetDataProvider->filterAssetsByProperty(
              $data,
              'isHidden',
              1
            );
            $assets = $data['asset'];
            $count = $data['totalCount'];
            unset($data['totalCount']);
            $videos = array();
            if ($count > 0) {
                foreach ($assets as $asset) {
                    $video = new Video($asset);
                    $videos[$video->getSlug()] = $video;
                }
            }
            $this->tagCollection[$tag]['videos'] = $videos;
            $this->tagCollection[$tag]['totalCount'] = (int)$count;
        }

        return $this->tagCollection[$tag]['videos'];
    }

    /**
     * Get total count by tag
     *
     * @param string $tag A tag
     * @return int
     */
    public function getTotalCountByTag($tag)
    {
        if (!isset($this->tagCollection[$tag]) || !isset($this->tagCollection[$tag]['totalCount'])) {
            $data = $this->assetDataProvider->fetchAssetsByTag($tag, 0, 1);
            $this->tagCollection[$tag]['totalCount'] = (int)$data['totalCount'];
        }

        return $this->tagCollection[$tag]['totalCount'];
    }

    /**
     * Get videos by Project UUID
     *
     * Retrieve Videos by Project UUID
     *
     * @param string $puuid A project UUID
     * @param int $start Beginning of the Video collection subset for pagination, 0 by default
     * @param int $limit Limit of the Video collection subset for pagination, 20 by default
     * @param string $sortby Possible values: any asset property. date_start by default
     * @param string $sortdir Possible values: asc or desc. desc by default
     * @return mixed
     */
    public function getVideosByProjectUUID(
      $puuid,
      $start = 0,
      $limit = 20,
      $sortby = 'sortnum',
      $sortdir = 'desc'
    ) {
        $totalRequired = $start > 0 ? $start * $limit : $limit;
        if (
          !isset($this->programCollection[$puuid]) ||
          !isset($this->programCollection[$puuid]['videos']) ||
          (isset($this->programCollection[$puuid]['videos']) && count(
              $this->programCollection[$puuid]['videos']
            ) <= $totalRequired)
        ) {
            $programs = $this->programDataProvider->fetchByProjectUUID(
              $puuid,
              $start,
              $limit,
              $sortby,
              $sortdir
            );
            $count = $programs['totalCount'];
            unset($programs['totalCount']);
            $videos = array();
            if ($count > 0) {
                foreach ($programs as $program) {
                    $video = new Video($program);
                    $videos[$video->getSlug()] = $video;
                }
            }
            $this->programCollection[$puuid]['videos'] = $videos;
            $this->programCollection[$puuid]['totalCount'] = (int)$count;
        }

        return $this->programCollection[$puuid]['videos'];
    }

    /**
     * Get videos by search string
     *
     * @param $search_string Search string to search on with length >= 3 characters, else nothing is returned
     * @param $project_uuid Project uuid found on the get code page
     * @param int $start Beginning of the data subset, 0 by default
     * @param int $limit Limit of the data subset, 20 by default
     * @param string $sort_by default is by search weight, this can be overwritten with: programTitle, assetTitle, programCreation, assetCreation
     * @param string $sort_dir possible values: asc, desc
     * @return array An array of programs
     */
    public function getVideosByProgramSearch(
      $search_string,
      $project_uuid,
      $start = 0,
      $limit = 20,
      $sort_by = '',
      $sort_dir = 'desc'
    ) {
        $data = $this->programSearchDataProvider->fetchData(
          $search_string,
          $project_uuid,
          $start,
          $limit,
          $sort_by,
          $sort_dir
        );
        $videos = array();
        if (isset($data['totalCount']) && $data['totalCount'] > 0) {
            foreach ($data['programs'] as $program) {
                $video = new Video($program);
                $videos[$video->getSlug()] = $video;
            }
        }

        return $videos;
    }

    /**
     * Get total count by program search string
     *
     * @param $search_string string Search string to search on with length >= 3 characters, else nothing is returned
     * @param $project_uuid  string Project uuid found on the get code page
     * @return array|null An array of programs
     */
    public function getTotalCountByProgramSearch($search_string, $project_uuid)
    {
        $data = $this->programSearchDataProvider->fetchData(
          $search_string,
          $project_uuid,
          0,
          1
        );

        return isset($data['totalCount']) ? $data['totalCount'] : 0;
    }

    /**
     * Check status of an asset
     *
     * 1. If the asset cannot be found, return "not found"
     * 2. If the asset is found and has already been moved to the specified folder,
     * return "updated"
     * 3. If the asset is found, has not been moved and has a thumbnail,
     * that means his encoding is done, return "ready"
     * 4. If the asset is found, has not been moved and has no thumbnail,
     * that means the asset is just uploaded and not yet encoded, return "not ready"
     *
     * @param $assetid A piksel assetId
     * @return string
     */
    public function checkAssetStatus($assetid)
    {
        if (session_id()) {
            @session_unset();
            @session_destroy();
            unset($_SESSION);
            $_SESSION = array();
        }

        $assetDataProvider = new AssetDataProvider($this->config);
        $data = $assetDataProvider->fetchAssetByVid(
          $assetid,
          false,
          false,
          false
        );

        // Abort if the asset cannot be found
        if (
          array_key_exists('failure', $data) &&
          $data['failure']['code'] === 303
        ) {
            return 'not found';
        }

        // Prepare rules conditions
        $isEncoded = isset($data['duration']) || (int)$data['duration'] > 0;
        $hasSlugLink = isset($data['associatedLinks']) && count($data['associatedLinks']);

        $isInDefaultProject = $hasSlugLink;

        if (isset($data['metadatas']['in_default_project'])) {
            $isInDefaultProject = $data['metadatas']['in_default_project'] == '1' ? true : false;
        }

        $thumbnailDataProvider = new ThumbnailDataProvider($this->config);
        $thumbnailData = $thumbnailDataProvider->get($assetid);
        $hasThumbnail = is_null($thumbnailData) ? $thumbnailData : ($thumbnailData && count($thumbnailData));
        $isShared = count($data['folders']) && !isset($this->config['folderID']);

        if ($isShared) {
            $isInDefaultProject = false;
        }

        if ($hasThumbnail === false && !$isEncoded) {
            return 'error';
        }

        if ($isShared) {
            return 'shared';
        }

        if (!$isInDefaultProject && $isEncoded && $hasThumbnail !== false) {
            return 'ready';
        }

        // If the asset is found and has already been moved to the specified folder
        if ($isInDefaultProject && $hasThumbnail !== false) {
            return 'updated';
        }

        return 'not ready';
    }

    /**
     * Return a Video download url from a vid
     *
     * The vid could be either a Kewego sig
     * a Piksel assetid or a Piksel program UUID
     *
     * @param $vid A Kewego sig, a Piksel assetid or a Piksel program UUID
     * @return string An url for the resource to download
     */
    public function getDownloadUrlByVid($vid)
    {
        $video = $this->getVideoByVid($vid);

        return $video->getDownloadUrl();
    }

    /**
     * Return an array to build a decent download
     *
     * @param $vid A Kewego sig, a Piksel assetid or a Piksel program UUID
     * @return array|bool
     */
    public function getDownloadInfo($vid)
    {
        $video = $this->getVideoByVid($vid);

        if ($video && $video->isDownloadable()) {
            return array(
              'slug' => $video->getSlug(),
              'url' => $video->getDownloadUrl(),
            );
        }

        return false;
    }

    /**
     * Set an associated link with the slug of the asset
     *
     * @param $assetId
     * @return array
     */
    public function setAssociatedLinkAsSlug($assetId)
    {

        // Get asset
        $assetDataProvider = new AssetDataProvider($this->config);
        $asset = $assetDataProvider->fetchAssetByVid(
          $assetId,
          false,
          false,
          false
        );

        // If asset not exists
        if (!isset($asset['assetid'])) {
            return array(
              'failure' => true,
              'message' => sprintf(
                '[Piksel::setAssociatedLinkAsSlug] Asset ID %d does not exists',
                $assetId
              ),
            );
        }

        // Use a Video object for convenience
        $video = new Video($asset);

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

        // Set header to build a no caching request
        $client->setHeader(
          'Cache-Control',
          'private, max-age=0, no-cache, no-store, must-revalidate'
        );
        $client->setHeader('Pragma', 'no-cache');
        $client->setHeader('Expires', '0');

        // Get user token
        $userToken = $this->getApiTmpUserToken($this->config);

        // Prepare request data
        $url = sprintf(
          '%s/services/index.php?&mode=json',
          $this->config['baseURL']
        );
        $requestData = array(
          'request' => array(
            'authentication' => array(
              'app_token' => $this->config['token'],
              'client_token' => $this->config['clientToken'],
              'user_token' => $userToken,
            ),
            'header' => array(
              'header_version' => 1,
              'api_version' => '5',
              'no_cache' => true,
            ),
            'Ws_Asset_Associated_Link' => array(
              'assetId' => $assetId,
              'title' => $video->getTitle(),
              'url' => $video->getSlug(),
            ),
          ),
        );

        // Run POST request
        $client->request(
          'POST',
          $url,
          array(),
          array(),
          array(),
          json_encode($requestData)
        );

        // Handle response
        $response = $client->getResponse();
        $content = json_decode($response->getContent());

        // Failure handling
//        if (
//          isset($content->response->failure) ||
//          (isset($content->response->success->code) && $content->response->success->code === 832)
//        ) {
        if (isset($content->response->failure)) {

            $message = isset($response->response->failure) ? $response->response->failure->reason : 'an error occured during execution';

            return array(
              'failure' => true,
              'message' => sprintf(
                '[Piksel::setAssociatedLinkAsSlug] %s',
                $message
              ),
            );
        }

        // Success handling
        if (isset($content->response->success)) {
            return array(
              'success' => true,
              'message' => sprintf(
                '[Piksel::setAssociatedLinkAsSlug] a link (%s) has been associated successfully to asset %s',
                $video->getSlug(),
                $assetId
              ),
            );
        }

    }

    /**
     * Get a temporary API user token
     *
     * The token is preserved and active for one session
     *
     * @param $config
     * @return string
     */
    public function getApiTmpUserToken($config)
    {
        if (session_id()) {
            @session_unset();
            @session_destroy();
            unset($_SESSION);
            $_SESSION = array();

        }
        $userTokenDataProvider = new UserTokenDataProvider($config);
        $userTokenDataProvider->clear();

        return $userTokenDataProvider->get();
    }

    public function hasAssociatedLinkAsSlug($assetId)
    {
        // Get asset
        $assetDataProvider = new AssetDataProvider($this->config);
        $asset = (object)$assetDataProvider->fetchAssetByVid(
          $assetId,
          false,
          false,
          false
        );

        // If asset not exists
        if (!isset($asset->assetid)) {
            return array(
              'failure' => true,
              'message' => sprintf(
                '[Piksel::setAssociatedLinkAsSlug] Asset ID %d does not exists',
                $assetId
              ),
            );
        }

        return isset($asset->associatedLinks);

    }

    /**
     * Create program in the default project
     *
     * @param $assetId
     * @param bool $checkIfIn
     * @return array
     */
    public function createProgramIntoDefaultProject($assetId, $checkIfIn = true)
    {

        // Get asset
        $assetDataProvider = new AssetDataProvider($this->config);
        $asset = (object)$assetDataProvider->fetchAssetByVid(
          $assetId,
          false,
          false,
          false
        );

        // If asset not exists
        if (!isset($asset->assetid)) {
            return array(
              'failure' => true,
              'message' => sprintf(
                '[Piksel::setToDefaultProject] Asset ID %d does not exists',
                $assetId
              ),
            );
        }

        // Already placed handler
        if (
          $checkIfIn &&
          isset($asset->metadatas['in_default_project']) &&
          $asset->metadatas['in_default_project'] != 'false'
        ) {
            return array(
              'success' => true,
              'message' => sprintf(
                '[Piksel::setToDefaultProject] asset %s was already placed in default project (%s)',
                $assetId,
                $this->config['searchUUID']
              ),
            );
        }


        // Use a Video object for convenience
//        $video = new Video($asset);

        // Build request
        $client = new Client();

        // Uncomment to disable SSL verification if needed
        $guzzleClient = new GuzzleClient(
          array(
            'verify' => false,
          )
        );
        $client->setClient($guzzleClient);

        // Set header to build a no caching request
        $client->setHeader(
          'Cache-Control',
          'private, max-age=0, no-cache, no-store, must-revalidate'
        );
        $client->setHeader('Pragma', 'no-cache');
        $client->setHeader('Expires', '0');

        // Get user token
        $userToken = $this->getApiTmpUserToken($this->config);

        // Prepare request data
        $url = sprintf(
          '%s/services/index.php?&mode=json',
          $this->config['baseURL']
        );
        $requestData = array(
          'request' => array(
            'authentication' => array(
              'app_token' => $this->config['token'],
              'client_token' => $this->config['clientToken'],
              'user_token' => $userToken,
            ),
            'header' => array(
              'header_version' => 1,
              'api_version' => '5',
              'no_cache' => true,
            ),
            'ws_program' => array(
              'assetId' => $assetId,
              'projectUUID' => $this->config['searchUUID'],
            ),
          ),
        );

        // Run POST request
        $client->request(
          'POST',
          $url,
          array(),
          array(),
          array(),
          json_encode($requestData)
        );

        // Handle response
        $response = $client->getResponse();
        $content = json_decode($response->getContent());

        // Failure handling
        if (isset($content->response->failure)) {
            $message = isset($response->response->failure) ? $response->response->failure->reason : 'an error occured during execution';

            return array(
              'failure' => true,
              'message' => sprintf(
                '[Piksel::setToDefaultProject] %s',
                $message
              ),
            );
        }

        // Success handling
        if (isset($content->response->success)) {

            if ($checkIfIn && isset($asset->metadatas['in_default_project'])) {
                $this->setAssetProperties(
                  $assetId,
                  array(
                    'request' => array(
                      'ws_asset' => array(
                        'metadatas' => array(
                          'in_default_project' => true,
                        ),
                      ),
                    ),
                  )
                );
            }

            return array(
              'success' => true,
              'message' => sprintf(
                '[Piksel::setToDefaultProject] asset %s has been placed in default project (%s) successfully',
                $assetId,
                $this->config['searchUUID']
              ),
            );
        }


    }

    /**
     * Set asset properties
     *
     * @param $assetId
     * @param array $data
     * @return array
     */
    public function setAssetProperties($assetId, array $data = [])
    {
        // Get asset
        $assetDataProvider = new AssetDataProvider($this->config);
        $asset = (object)$assetDataProvider->fetchAssetByVid(
          $assetId,
          false,
          false,
          false
        );

        // If asset not exists
        if (!isset($asset->assetid)) {
            return array(
              'failure' => true,
              'message' => sprintf(
                '[Piksel::setMetadatas] Asset ID %d does not exists',
                $assetId
              ),
            );
        } elseif (count($data)) {

            // Use a Video object for convenience
//            $video = new Video($asset);

            // Build request
            $client = new Client();

            // Uncomment to disable SSL verification if needed
            $guzzleClient = new GuzzleClient(
              array(
                'verify' => false,
              )
            );
            $client->setClient($guzzleClient);

            // Set header to build a no caching request
            $client->setHeader(
              'Cache-Control',
              'private, max-age=0, no-cache, no-store, must-revalidate'
            );
            $client->setHeader('Pragma', 'no-cache');
            $client->setHeader('Expires', '0');

            // Get user token
            $userToken = $this->getApiTmpUserToken($this->config);

            // Prepare request data
            $url = sprintf(
              '%s/ws/ws_asset/mode/json/apiv/5.0?method=put&',
              str_replace('api-', '', $this->config['baseURL'])
            );
            $requestData = array(
              'request' => array(
                'authentication' => array(
                  'app_token' => $this->config['token'],
                  'client_token' => $this->config['clientToken'],
                  'user_token' => $userToken,
                ),
                'header' => array(
                  'header_version' => 1,
                  'api_version' => '5',
                  'no_cache' => true,
                ),
                'ws_asset' => array(
                  'assetid' => (int)$assetId,
                ),
              ),
            );

            $requestData = array_merge_recursive($data, $requestData);

            // Run request
            $client->request(
              'PUT',
              $url,
              array(),
              array(),
              array(),
              json_encode($requestData)
            );

            // Handle response
            $response = $client->getResponse();
            $content = json_decode($response->getContent());

            // Failure handling
            if (isset($content->response->failure)) {
                $message = isset($response->response->failure) ? $response->response->failure->reason : 'an error occured during execution';

                return array(
                  'failure' => true,
                  'message' => sprintf(
                    '[Piksel::setAssetProperties] %s',
                    $message
                  ),
                );
            }

            // Success handling
            if (isset($content->response->success)) {
                return array(
                  'success' => true,
                  'message' => sprintf(
                    '[Piksel::setAssetProperties] asset %s has modified successfully',
                    $assetId
                  ),
                );
            }
        } else {
            return array(
              'success' => true,
              'message' => sprintf(
                '[Piksel::setAssetProperties] Asset ID %d remain not modified',
                $assetId
              ),
            );
        }
    }

    /**
     * Delete the program into the default project
     * associated with an asset
     *
     * @param $assetId
     * @return array
     */
    public function deleteProgramIntoDefaultProject($assetId)
    {
        $assetDataProvider = new AssetDataProvider($this->config);

        // Get asset programUUID in default project
        $associatedData = $assetDataProvider->fetchAssociationsByAssetid(
          $assetId
        );

        $programUUID = null;
        if (
          isset($associatedData['associatedPrograms']) &&
          count($associatedData['associatedPrograms'])
        ) {
            foreach ($associatedData['associatedPrograms'] as $programReference) {
                if ($programReference['project_title'] === $this->config['clientName']) {
                    $programUUID = $programReference['uuid'];
                    break;
                }
            }
        }

        if (!is_null($programUUID)) {

            // Build request
            $client = new Client();

            // Uncomment to disable SSL verification if needed
            $guzzleClient = new GuzzleClient(
              array(
                'verify' => false,
              )
            );
            $client->setClient($guzzleClient);

            // Set header to build a no caching request
            $client->setHeader(
              'Cache-Control',
              'private, max-age=0, no-cache, no-store, must-revalidate'
            );
            $client->setHeader('Pragma', 'no-cache');
            $client->setHeader('Expires', '0');

            // Get user token
            $userToken = $this->getApiTmpUserToken($this->config);

            // Prepare request data
            $url = sprintf(
              '%s/ws/ws_program/mode/json/apiv/5.0?method=delete&',
              str_replace('api-', '', $this->config['baseURL'])
            );
            $requestData = array(
              'request' => array(
                'authentication' => array(
                  'app_token' => $this->config['token'],
                  'client_token' => $this->config['clientToken'],
                  'user_token' => $userToken,
                ),
                'header' => array(
                  'header_version' => 1,
                  'api_version' => '5',
                  'no_cache' => true,
                ),
                'ws_program' => array(
                  'programUuid' => $programUUID,
                ),
              ),
            );

            // Run DELETE request
            $client->request(
              'DELETE',
              $url,
              array(),
              array(),
              array(),
              json_encode($requestData)
            );

            // Handle response
            $response = $client->getResponse();
            $content = json_decode($response->getContent());

            // Failure handling
            if (isset($content->response->failure)) {
                $message = isset($response->response->failure) ? $response->response->failure->reason : 'an error occured during execution';

                return array(
                  'failure' => true,
                  'message' => sprintf(
                    '[Piksel::deleteProgramIntoDefaultProject] %s',
                    $message
                  ),
                );
            }

            // Success handling
            if (isset($content->response->success)) {
                return array(
                  'success' => true,
                  'message' => sprintf(
                    '[Piksel::deleteProgramIntoDefaultProject] program %s has been deleted from the default project (%s) successfully',
                    $programUUID,
                    $this->config['searchUUID']
                  ),
                );
            }

        }

    }

    /**
     * Unpublish asset
     *
     * @param $assetId
     * @param array $requestExtras
     * @return array
     */
    public function unpublish($assetId, array $requestExtras = [])
    {
        // Get asset
        $assetDataProvider = new AssetDataProvider($this->config);
        $asset = (object)$assetDataProvider->fetchAssetByVid(
          $assetId,
          false,
          false,
          false
        );

        // If asset not exists
        if (!isset($asset->assetid)) {
            return array(
              'failure' => true,
              'message' => sprintf(
                '[Piksel::unpublish] Asset ID %d does not exists',
                $assetId
              ),
            );
        } elseif ($asset->isPublished || count($requestExtras)) {

            // Use a Video object for convenience
//            $video = new Video($asset);

            // Build request
            $client = new Client();

            // Uncomment to disable SSL verification if needed
            $guzzleClient = new GuzzleClient(
              array(
                'verify' => false,
              )
            );
            $client->setClient($guzzleClient);

            // Set header to build a no caching request
            $client->setHeader(
              'Cache-Control',
              'private, max-age=0, no-cache, no-store, must-revalidate'
            );
            $client->setHeader('Pragma', 'no-cache');
            $client->setHeader('Expires', '0');

            // Get user token
            $userToken = $this->getApiTmpUserToken($this->config);

            // Prepare request data
            $url = sprintf(
              '%s/ws/ws_asset/mode/json/apiv/5.0?method=put&',
              str_replace('api-', '', $this->config['baseURL'])
            );
            $requestData = array(
              'request' => array(
                'authentication' => array(
                  'app_token' => $this->config['token'],
                  'client_token' => $this->config['clientToken'],
                  'user_token' => $userToken,
                ),
                'header' => array(
                  'header_version' => 1,
                  'api_version' => '5',
                  'no_cache' => true,
                ),
                'ws_asset' => array(
                  'assetid' => (int)$assetId,
                  'isPublished' => 0,
                ),
              ),
            );

            $requestData = array_merge_recursive($requestExtras, $requestData);

            // Run request
            $client->request(
              'PUT',
              $url,
              array(),
              array(),
              array(),
              json_encode($requestData)
            );

            // Handle response
            $response = $client->getResponse();
            $content = json_decode($response->getContent());

            // Failure handling
            if (isset($content->response->failure)) {
                $message = isset($response->response->failure) ? $response->response->failure->reason : 'an error occured during execution';

                return array(
                  'failure' => true,
                  'message' => sprintf(
                    '[Piksel::unpublish] %s',
                    $message
                  ),
                );
            }

            // Success handling
            if (isset($content->response->success)) {
                return array(
                  'success' => true,
                  'message' => sprintf(
                    '[Piksel::unpublish] asset %s has unpublished successfully',
                    $assetId
                  ),
                );
            }
        } else {
            return array(
              'success' => true,
              'message' => sprintf(
                '[Piksel::unpublish] Asset ID %d is already unpublished',
                $assetId
              ),
            );
        }
    }

    /**
     * Publish program in the default project
     *
     * @param $assetId
     * @return array
     */
    public function publishProgramInDefaultProject($assetId)
    {

        $assetDataProvider = new AssetDataProvider($this->config);

        // Get asset programUUID in default project
        $associatedData = $assetDataProvider->fetchAssociationsByAssetid(
          $assetId
        );

        $programUUID = null;
        if (
          isset($associatedData['associatedPrograms']) &&
          count($associatedData['associatedPrograms'])
        ) {
            foreach ($associatedData['associatedPrograms'] as $programReference) {
                if ($programReference['project_title'] === $this->config['clientName']) {
                    $programUUID = $programReference['uuid'];
                    break;
                }
            }
        }

        if (!is_null($programUUID)) {

            // Build request
            $client = new Client();

            // Uncomment to disable SSL verification if needed
            $guzzleClient = new GuzzleClient(
              array(
                'verify' => false,
              )
            );
            $client->setClient($guzzleClient);

            // Set header to build a no caching request
            $client->setHeader(
              'Cache-Control',
              'private, max-age=0, no-cache, no-store, must-revalidate'
            );
            $client->setHeader('Pragma', 'no-cache');
            $client->setHeader('Expires', '0');

            // Get user token
            $userToken = $this->getApiTmpUserToken($this->config);

            // Prepare request data
            $url = sprintf(
              '%s/ws/ws_program/mode/json/apiv/5.0?method=put&',
              str_replace('api-', '', $this->config['baseURL'])
            );
            $requestData = array(
              'request' => array(
                'authentication' => array(
                  'app_token' => $this->config['token'],
                  'client_token' => $this->config['clientToken'],
                  'user_token' => $userToken,
                ),
                'header' => array(
                  'header_version' => 1,
                  'api_version' => '5',
                  'no_cache' => true,
                ),
                'ws_program' => array(
                  'programUUID' => $programUUID,
                  'isPublished' => 1,
                ),
              ),
            );

            // Run POST request
            $client->request(
              'PUT',
              $url,
              array(),
              array(),
              array(),
              json_encode($requestData)
            );

            // Handle response
            $response = $client->getResponse();
            $content = json_decode($response->getContent());

            // Failure handling
            if (isset($content->response->failure)) {
                $message = isset($response->response->failure) ? $response->response->failure->reason : 'an error occured during execution';

                return array(
                  'failure' => true,
                  'message' => sprintf(
                    '[Piksel::publishProgramInDefaultProject] %s',
                    $message
                  ),
                );
            }

            // Success handling
            if (isset($content->response->success)) {
                return array(
                  'success' => true,
                  'message' => sprintf(
                    '[Piksel::publishProgramInDefaultProject] program %s has been published in default project (%s) successfully',
                    $programUUID,
                    $this->config['searchUUID']
                  ),
                );
            }

        }
    }

}