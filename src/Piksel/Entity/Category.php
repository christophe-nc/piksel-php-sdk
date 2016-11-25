<?php
/**
 * This file is part of the Piksel package.
 *
 * @copyright 2015 Pixopat, Alex Druhet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Piksel\Entity;

/**
 * A Category entity
 *
 * Extends Base
 *
 * @author Alex Druhet <alex@pixopat.com>
 * @package Piksel\Entity
 */
class Category extends Base
{

    /** @var int The total count of the available Videos in this Category */
    private $totalCount;

    /** @var array An array of the available Videos in this Category */
    private $videos;

    /**
     * {@inheritDoc}
     *
     * @param string $title The human title
     * @param null $slug Optional, the slug
     * @param null $id Optional, an identifier
     * @param null $totalCount Optional, a precalculated count
     */
    public function __construct($title, $slug = null, $id = null, $totalCount = null)
    {
        parent::__construct($title, $slug, $id);
        $this->totalCount = 0;
        $this->setTotalCount($totalCount);
    }

    /**
     * setTotalCount
     *
     * @param null $totalCount
     * @return int
     */
    public function setTotalCount($totalCount = null)
    {
        if (!$this->totalCount && $totalCount) {
            $this->totalCount = (int)$totalCount;
            $this->setLastModified(new \DateTime('now'));
        }

        return $this->totalCount;
    }

    /**
     * getTotalCount
     *
     * @return int
     */
    public function getTotalCount()
    {
        return $this->setTotalCount();
    }

    /**
     * Return an array of Videos
     *
     * @return array
     */
    public function getVideos()
    {
        return $this->setVideos();
    }

    /**
     * Set this Category Videos
     *
     * @param null $videos An array of Videos entities
     * @return array
     */
    public function setVideos($videos = null)
    {
        if ($this->totalCount === 0) {
            return false;
        }
        if ($this->totalCount > 0 && (!$this->videos || is_array($videos))) {
            if (is_array($videos)) {
                $this->videos = $videos;
                $this->setLastModified(new \DateTime('now'));
            }
        }

        return $this->videos;
    }

}