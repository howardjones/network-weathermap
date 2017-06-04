<?php

require_once "WeatherMapUIBase.class.php";
require_once 'WeathermapManager.class.php';
require_once 'WeatherMapCactiUserPlugin.php';

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

    public function cacti_header()
    {
        top_header();
    }

    public function cacti_footer()
    {
        bottom_footer();
    }

    public function cacti_row_start($i)
    {
        form_alternate_row();
    }
}