<?php

require_once "WeatherMapUIBase.class.php";
require_once 'WeathermapManager.class.php';
require_once 'WeatherMapCactiUserPlugin.php';
require_once 'weathermap-cacti88-plugin-compat.php';


class WeatherMapCacti88UserPlugin extends WeatherMapCactiUserPlugin
{
    public $colours;

    public function __construct($config, $colours, $imageformat)
    {
        parent::__construct($config, $imageformat);

        $this->colours = $colours;
        $this->my_url = "weathermap-cacti88-plugin.php";
        $this->editor_url = "weathermap-cacti88-plugin-editor.php";
    }


    public function cacti_header()
    {
        include_once $this->cacti_config["base_path"] . "/include/top_header.php";
    }

    public function cacti_footer()
    {
        include_once $this->cacti_config["base_path"] . "/include/bottom_footer.php";
    }

    public function cacti_row_start($i)
    {
        form_alternate_row_color($this->colours["alternate"], $this->colours["light"], $i);
    }
}