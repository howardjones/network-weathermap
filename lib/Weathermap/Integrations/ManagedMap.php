<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 24/09/17
 * Time: 13:10
 */

namespace Weathermap\Integrations;

class ManagedMap
{
    /**
     * Some day we'll migrate to using this for the managed maps, and the various mutators below will be
     * methods here instead.
     */
    public $id;
    public $sortOrder;
    public $groupId;
    public $active;
    public $configFile;
    public $imageFile;
    public $htmlFile;
    public $titleCache;
    public $fileHash;
    public $warnCount;
    public $config;
    public $thumbWidth;
    public $thumbHeight;
    public $schedule;
    public $archiving;

    public function checkAccess($userId)
    {

    }

    public function delete()
    {

    }

    public function update()
    {

    }

    public function enable()
    {

    }

    public function disable()
    {

    }

    public function enableArchiving()
    {

    }

    public function disableArchiving()
    {

    }

    public function updateTitleCache()
    {

    }

    public function setDebug($newState)
    {

    }

    public function setGroup($groupId)
    {

    }

    public function addAccess($userId)
    {

    }


    public function removeAccess($userId)
    {

    }
}
