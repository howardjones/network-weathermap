<?php

namespace Weathermap\Integrations\Cacti;

//require_once "WeatherMapUIBase.class.php";
//require_once 'WeathermapManager.php';
//require_once 'WeatherMapCactiUserPlugin.php';

class WeatherMapCacti10UserPlugin extends WeatherMapCactiUserPlugin
{

    public function __construct($config, $imageformat)
    {
        parent::__construct($config, $imageformat);

        $this->my_url = "weathermap-cacti10-plugin.php";
        $this->editor_url = "weathermap-cacti10-plugin-editor.php";
        $this->management_url = "weathermap-cacti10-plugin-mgmt.php";

        $this->management_realm = "weathermap-cacti10-plugin-mgmt.php";
        $this->editor_realm = "weathermap-cacti10-plugin-editor.php";
    }

    public function cactiHeader()
    {
        top_header();
    }

    public function cactiFooter()
    {
        bottom_footer();
    }

    public function cactiRowStart($i)
    {
        form_alternate_row();
    }
}
