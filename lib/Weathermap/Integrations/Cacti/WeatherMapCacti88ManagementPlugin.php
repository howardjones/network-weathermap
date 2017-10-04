<?php

namespace Weathermap\Integrations\Cacti;

//require_once "WeatherMapUIBase.class.php";
//require_once 'WeathermapManager.php';
//require_once 'WeatherMapCactiManagementPlugin.php';
//require_once 'weathermap-cacti88-plugin-compat.php';

class WeatherMapCacti88ManagementPlugin extends WeatherMapCactiManagementPlugin
{
    public $colours;

    public function __construct($config, $colours)
    {
        parent::__construct($config);
        $this->colours = $colours;
        $this->myURL = "weathermap-cacti88-plugin-mgmt.php";
        $this->editorURL = "weathermap-cacti88-plugin-editor.php";
    }

    /**
     * @param $request
     * @param $appObject
     */
    public function handleManagementMainScreen($request, $appObject)
    {
        $this->cactiHeader();
        $this->maplistWarnings();
        $this->maplist();
        $this->footerLinks();
        $this->cactiFooter();
    }


    public function cactiHeader()
    {
        include_once $this->cactiConfig["base_path"] . "/include/top_header.php";
    }

    public function cactiFooter()
    {
        include_once $this->cactiConfig["base_path"] . "/include/bottom_footer.php";
    }

    public function cactiRowStart($i)
    {
        form_alternate_row_color($this->colours["alternate"], $this->colours["light"], $i);
    }
}
