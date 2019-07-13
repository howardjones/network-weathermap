<?php

namespace Weathermap\Integrations\Cacti;

// replacements for functions in Cacti 1.x, so more code can be shared
require_once dirname(__FILE__) . '/weathermap-cacti88-plugin-compat.php';

/**
 * The 0.8.8x specific parts of the Cacti 'user' plugin (map display)
 *
 * @package Weathermap\Integrations\Cacti
 */
class WeatherMapCacti88UserPlugin extends WeatherMapCactiUserPlugin
{
    public $colours;

    public function __construct($config, $colours, $imageformat, $basePath)
    {
        parent::__construct($config, $imageformat, $basePath);

        $this->colours = $colours;
        $this->myURL = "weathermap-cacti88-plugin.php";
        $this->editorURL = "weathermap-cacti88-plugin-editor.php";
        $this->managementURL = "weathermap-cacti88-plugin-mgmt.php";
        $this->managementRealm = "weathermap-cacti88-plugin-mgmt.php";
        $this->editorRealm = "weathermap-cacti88-plugin-editor.php";
    }

    public function cactiGraphHeader()
    {
        global $config;
        include_once $this->cactiConfig["base_path"] . "/include/top_graph_header.php";
    }

    public function cactiHeader()
    {
        global $config;
        include_once $this->cactiConfig["base_path"] . "/include/top_header.php";
    }

    public function cactiFooter()
    {
        global $config;
        include_once $this->cactiConfig["base_path"] . "/include/bottom_footer.php";
    }

    public function cactiRowStart($i)
    {
        form_alternate_row_color($this->colours["alternate"], $this->colours["light"], $i);
    }
}
