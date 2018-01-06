<?php

namespace Weathermap\Integrations\Cacti;

//require_once dirname(__FILE__) . "/../../UI/UIBase.php";
//require_once dirname(__FILE__) . "/../MapManager.php";
require_once dirname(__FILE__) . "/WeatherMapCactiUserPlugin.php";

class WeatherMapCacti10UserPlugin extends WeatherMapCactiUserPlugin
{

    public function __construct($config, $imageformat, $basePath)
    {
        parent::__construct($config, $imageformat, $basePath);

        $this->myURL = "weathermap-cacti10-plugin.php";
        $this->editorURL = "weathermap-cacti10-plugin-editor.php";
        $this->managementURL = "weathermap-cacti10-plugin-mgmt.php";

        $this->managementRealm = "weathermap-cacti10-plugin-mgmt.php";
        $this->editorRealm = "weathermap-cacti10-plugin-editor.php";
    }

    public function cactiHeader()
    {
        \top_header();
    }

    public function cactiFooter()
    {
        \bottom_footer();
    }

    public function cactiRowStart($i)
    {
        \form_alternate_row();
    }
}
