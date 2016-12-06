<?php
/**
 * This file is part of the Piksel package.
 *
 * @copyright 2015 Alex Druhet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Piksel\Entity;

/**
 * A Video entity
 *
 * Extends Base
 *
 * @author Alex Druhet <alex@pixadelic.com>
 * @package Piksel\Entity
 */
class Video extends Base
{

    /** @var stdObject Store asset as a php standard object */
    private $assetOrProgram;

    /** @var array Array of associated data */
    private $associatedData;

    /**
     * {@inheritDoc}
     *
     * @param array $assetOrProgram A piksel asset array
     */
    public function __construct($assetOrProgram)
    {

        $title = 'undefined';

        if (isset($assetOrProgram['title'])) {
            $title = $assetOrProgram['title'];
        }

        if (isset($assetOrProgram['Title'])) {
            $title = $assetOrProgram['Title'];
        }

        parent::__construct($title);

        $this->assetOrProgram = (object)$assetOrProgram;

        if (isset($assetOrProgram['dateStart'])) {
            $datemod = $assetOrProgram['dateStart'];
        }

        if (isset($assetOrProgram['datemod'])) {
            $datemod = $assetOrProgram['datemod'];
        }

        if ($datemod) {
            $this->setLastModified(new \DateTime($datemod));
        }

        $this->setId($this->assetOrProgram->assetid);
    }

    /**
     * Return the Asset ID
     *
     * @return int The Piksel assetid
     */
    public function getAssetid()
    {
        return $this->assetOrProgram->assetid;
    }

    /**
     * Return the ID, the Piksel assetid by default
     * or programUUID if available
     *
     * @return string The Video ID
     */
    public function getId()
    {
        if (!parent::getId()) {
//            if (isset($this->assetOrProgram->uuid)) {
//                return parent::setId($this->assetOrProgram->uuid);
//            }
            return parent::setId($this->assetOrProgram->assetid);
        }

        return parent::getId();
    }

    /**
     * Return the thumbnail URL
     *
     * @param boolean $resize Optional, if true the thumbnail size will be forced to 420x315px
     * @param int $width Optional, the thumbnail width
     * @param int $height Optional, the thumbnail height
     * @return string The thumbnail URL
     */
    public function getThumbnailUrl(
      $resize = false,
      $width = 420,
      $height = 315
    ) {
        if ($resize) {
            // Force thumb size
            $tmp = preg_replace(
              '/w(=)?(\d{1,4})(&|\/)h(=)?(\d{1,4})/',
              'w$1%d$3h$4%d',
              $this->assetOrProgram->thumbnailUrl
            );

            return sprintf($tmp, $width, $height);
        }

        return $this->assetOrProgram->thumbnailUrl;
    }

    /**
     * Return the description
     *
     * @return string The description
     */
    public function getDescription()
    {
        $description = false;
        if (isset($this->assetOrProgram->description)) {
            $description = $this->assetOrProgram->description;
        }
        if (isset($this->assetOrProgram->Description)) {
            $description = $this->assetOrProgram->Description;
        }

        return $description;
    }

    /**
     * Return the video source
     *
     * It has been decided so far to use the m3u8 Android URL property.
     *
     * @return string A source URL
     */
    public function getSrc()
    {
        $src = false;
        if (isset($this->assetOrProgram->metadatas['custom_m3u8android'])) {
            $src = $this->assetOrProgram->metadatas['custom_m3u8android'];
        }
        if (!$src && isset($this->assetOrProgram->m3u8AndroidURL)) {
            $src = $this->assetOrProgram->m3u8AndroidURL;
        }
        if (!$src && isset($this->assetOrProgram->asset['m3u8AndroidURL'])) {
            $src = $this->assetOrProgram->asset['m3u8AndroidURL'];
        }

//        if (isset($this->assetOrProgram->iphoneM3u8Url)) {
//            $src = $this->assetOrProgram->iphoneM3u8Url;
//        }
//        if (isset($this->assetOrProgram->asset['iphoneM3u8Url'])) {
//            $src = $this->assetOrProgram->asset['iphoneM3u8Url'];
//        }

        return $src;
    }

    /**
     * Return the associated data
     *
     * @return array
     */
    public function getAssociatedData()
    {
        return $this->setAssociatedData();
    }

    /**
     * Set the associated data
     *
     * We only keep the associated programs
     *
     * @param null $data
     * @return array
     */
    public function setAssociatedData($data = null)
    {
        if (!$this->associatedData && is_array($data)) {

            $programs = array();
            foreach ($data as $key => $value) {
                if ($key === 'associatedPrograms') {
                    foreach ($data[$key] as $program) {
                        // TODO $defaultProjectTitle
//                        if (
//                          $program['project_title'] == $defaultProjectTitle
//                          && $program['program_title'] != $this->getTitle()
//                        ) {
                        $programs[] = $program;
//                        }
                    }
                }
            }
            $this->associatedData = $programs;
            $this->setLastModified(new \DateTime('now'));
        }

        return $this->associatedData;
    }

    /**
     * Return the video tags
     *
     * @return array
     */
    public function getTags()
    {
        $tags = false;
        if (isset($this->assetOrProgram->tags) && $this->assetOrProgram->tags) {
            $tags = explode(', ', $this->assetOrProgram->tags);
        }

        return $tags;
    }

    /**
     * Return the video publication date
     *
     * This method is just an alias of getStartDate so far.
     *
     * @return string A string date with iso format
     */
    public function getPubDate()
    {
        return $this->getDateStart();
    }

    /**
     * Return the video start date
     *
     * date_start is used because of Kewego import.
     *
     * @return string A string date with iso format
     */
    public function getDateStart()
    {
        $date_start = false;
        if (isset($this->assetOrProgram->date_start)) {
            $date_start = $this->assetOrProgram->date_start;
        }
        if (isset($this->assetOrProgram->dateStart)) {
            $date_start = $this->assetOrProgram->dateStart;
        }

        return $date_start;
    }

    /**
     * Return the video duration formatted
     *
     * This method return the video duration as H:M:S
     * if the duration is one hour or more, otherwise
     * it returns a M:S formatted duration.
     *
     * @return string H:i:s|i:s
     */
    public function getFormattedDuration()
    {
        $output = '';
        if (self::getDuration()) {
            $seconds = floor(self::getDuration());
            if ($seconds >= 3600) {
                $format = 'H:i:s';
            } else {
                $format = 'i:s';
            }
            $output = gmdate($format, $seconds);
        }

        return $output;
    }

    /**
     * Return the video duration
     *
     * duration is available in the different assets for each encoding, I use the first one.
     *
     * @return float duration
     */
    public function getDuration()
    {
        $duration = false;
        if (isset($this->assetOrProgram->assetFiles)) {
            $duration = $this->assetOrProgram->assetFiles[0]['duration'];
        }
        if (isset($this->assetOrProgram->duration)) {
            $duration = $this->assetOrProgram->duration;
        }

        return $duration;
    }

    /**
     * Return captions data in json if exists
     *
     * @return bool|json
     */
    public function getCaptionsJson()
    {
        $captions = $this->getCaptions();

        return $captions ? json_encode($captions) : null;

    }

    /**
     * * Return captions data if exists
     *
     * @return bool|array
     */
    public function getCaptions()
    {
        $captions = false;
        if (isset($this->assetOrProgram->captions)) {
            $captions = $this->assetOrProgram->captions;
        }
        if (isset($this->assetOrProgram->asset->captions)) {
            $captions = $this->assetOrProgram->asset->captions;
        }

        return $captions;
    }

    /**
     * Return an HD download url over CDN
     *
     * @return string|bool
     */
    public function getDownloadUrl()
    {
        $url = false;
        if (
          isset($this->assetOrProgram->assetFiles)
          && $this->isDownloadable()
        ) {
            $url = $this->assetOrProgram->assetFiles[0]['full_cdn_path'];
        }

        return $url;
    }

    /**
     * Return if the video is downloadable
     *
     * @return bool
     */
    public function isDownloadable()
    {
        $isset = isset($this->assetOrProgram->metadatas['downloadable']);
        if (!$isset) {
            return true;
        }

        return $this->assetOrProgram->metadatas['downloadable'] === 'true' ? true : false;
    }

    /**
     * Return the biggest video size formatted
     *
     * This method return the video size in a
     * human readable format.
     *
     * @param int $precision [optional] Number of digits after the decimal point (eg. 1)
     * @return string converted with unit (eg. 25.3KB)
     */
    public function getFormattedBiggestSize($precision = 2)
    {
        $output = '';
        if ($bytes = self::getBiggestSize()) {
            $unit = ["B", "KB", "MB", "GB"];
            $exp = floor(log($bytes, 1024)) | 0;
            $output = round($bytes / (pow(1024, $exp)), $precision).$unit[$exp];
        }

        return $output;
    }

    /**
     * Return the biggest video size
     *
     * Filesize is available in the different assets
     * for each encoding, we use the biggest one.
     *
     * @return int filesize
     */
    public function getBiggestSize()
    {
        $filesize = false;
        if (isset($this->assetOrProgram->assetFiles)) {
            $filesize = $this->assetOrProgram->assetFiles[0]['filesize'];
        }
        if (isset($this->assetOrProgram->filesize)) {
            $filesize = $this->assetOrProgram->filesize;
        }

        return $filesize;
    }

    /**
     * Get if the video is published
     *
     * @return bool
     */
    public function isPublished()
    {
        return (boolean)$this->assetOrProgram->isPublished;
    }

}