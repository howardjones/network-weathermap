<?php

namespace Weathermap\Integrations\Cacti;

/**
 * The 1.x specific parts of the Cacti 'user' plugin (map display)
 *
 * @package Weathermap\Integrations\Cacti
 */
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
