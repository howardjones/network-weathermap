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
    public $sortorder;
    public $group_id;
    public $active;
    public $configfile;
    public $imagefile;
    public $htmlfile;
    public $titlecache;
    public $filehash;
    public $warncount;
    public $config;
    public $thumb_width;
    public $thumb_height;
    public $schedule;
    public $archiving;
}
