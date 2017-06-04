<?php

require_once "database.php";
require_once "Weathermap.class.php";
require_once "WeatherMap.functions.php";
require_once "WeatherMapUIBase.class.php";
include_once 'WeathermapManager.class.php';

class WeatherMapCactiUserPlugin extends WeatherMapUIBase
{
    public $manager;
    public $my_url;
    public $editor_url;
    private $outputDirectory;
    private $imageFormat;
    public $cacti_config;
    public $configPath;

    public $commands = array(
        'viewthumb' => array('handler' => 'handleBigThumb', 'args' => array(array("id", "hash"))),
        'viewthumb48' => array('handler' => 'handleLittleThumb', 'args' => array(array("id", "hash"))),
        'viewimage' => array('handler' => 'handleImage', 'args' => array(array("id", "hash"))),

        'viewmap' => array('handler' => 'handleViewMap', 'args' => array(array("id", "hash"), array("group_id", "int", true))),

        'viewcycle_fullscreen' => array('handler' => 'handleViewCycleFullscreen', 'args' => array(array("id", "hash"))),
        'viewcycle_filtered_fullscreen' => array('handler' => 'handleViewCycleFilteredFullscreen', 'args' => array(array("id", "hash"), array("group_id", "int", true))),

        'viewcycle' => array('handler' => 'handleViewCycle', 'args' => array(array("id", "hash"))),
        'viewcycle_filtered' => array('handler' => 'handleViewCycleFiltered', 'args' => array(array("id", "hash"), array("group_id", "int", true))),

//        'viewmapcycle' => array(
//            'handler' => 'handleViewCycle', 'args' => array(
//                array("fullscreen", "int", true),
//                array("group", "int", true)
//            )),
        ':: DEFAULT ::' => array(
            'handler' => 'handleDefaultView',
            'args' => array(
                array("group_id", "int", true)
            )
        )
    );

    public function __construct($config, $imageFormat)
    {
        parent::__construct();

        $this->cacti_config = $config;
        $this->my_url = "SHOULD-BE-OVERRIDDEN";
        $this->configPath = realpath(dirname(__FILE__) . '/../configs');
        $this->outputDirectory = realpath(dirname(__FILE__) . '/../output/');
        $this->imageFormat = $imageFormat;
        $this->manager = new WeathermapManager(weathermap_get_pdo(), $this->configPath);
    }

    function main($request)
    {
        $action = ":: DEFAULT ::";
        if (isset($request['action'])) {
            $action = strtolower(trim($request['action']));
        }

        if ($this->validateRequest($action, $request)) {
            $result = $this->dispatchRequest($action, $request, null);
        } else {
            print "INPUT VALIDATION FAIL";
        }
    }

    public function make_url($params, $alt_url = "")
    {
        $base_url = $this->my_url;
        if ($alt_url != "") {
            $base_url = $alt_url;
        }
        $url = $base_url . (strpos($this->my_url, '?') === false ? '?' : '&');

        $parts = array();
        foreach ($params as $name => $value) {
            $parts [] = urlencode($name) . "=" . urlencode($value);
        }
        $url .= join("&", $parts);

        return $url;
    }

    public function handleBigThumb($request, $appObject)
    {
        print "Unimplemented";
    }

    public function handleLittleThumb($request, $appObject)
    {
        print "Unimplemented";
    }

    public function handleImage($request, $appObject)
    {
        print "Unimplemented";
    }

    public function handleViewMap($request, $appObject)
    {
        print "Unimplemented";
    }

    public function handleViewCycleFullscreen($request, $appObject)
    {
        print "Unimplemented";
    }

    public function handleViewCycleFilteredFullscreen($request, $appObject)
    {
        print "Unimplemented";
    }

    public function handleViewCycle($request, $appObject)
    {
        print "Unimplemented";
    }

    public function handleViewCycleFiltered($request, $appObject)
    {
        print "Unimplemented";
    }

    public function handleDefaultView($request, $appObject)
    {
        print "Unimplemented";
    }

    public function cacti_footer()
    {
        print "OVERRIDE ME";
    }

    public function cacti_header()
    {
        print "OVERRIDE ME";
    }

    public function cacti_row_start($i)
    {
    }
}