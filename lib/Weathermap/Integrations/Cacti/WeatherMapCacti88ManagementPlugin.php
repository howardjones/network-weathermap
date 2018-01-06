<?php

namespace Weathermap\Integrations\Cacti;

require_once dirname(__FILE__) . '/weathermap-cacti88-plugin-compat.php';
//require_once dirname(__FILE__) . "/WeatherMapCactiManagementPlugin.php";

class WeatherMapCacti88ManagementPlugin extends WeatherMapCactiManagementPlugin
{
    public $colours;

    public function __construct($config, $colours, $basePath)
    {
        parent::__construct($config, $basePath);
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
        print "This will all be replaced.";
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
