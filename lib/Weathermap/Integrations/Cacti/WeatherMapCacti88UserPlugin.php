<?php

namespace Weathermap\Integrations\Cacti;

require_once dirname(__FILE__) . '/weathermap-cacti88-plugin-compat.php';
require_once dirname(__FILE__) . "/WeatherMapCactiUserPlugin.php";


class WeatherMapCacti88UserPlugin extends WeatherMapCactiUserPlugin
{
    public $colours;

    public function __construct($config, $colours, $imageformat, $basePath)
    {
        parent::__construct($config, $imageformat, $basePath);

        $this->colours = $colours;
        $this->myURL = "weathermap-cacti88-plugin.php";
        $this->editorURL = "weathermap-cacti88-plugin-editor.php";
        $this->managementRealm = "weathermap-cacti88-plugin-mgmt.php";
        $this->editorRealm = "weathermap-cacti88-plugin-editor.php";
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
