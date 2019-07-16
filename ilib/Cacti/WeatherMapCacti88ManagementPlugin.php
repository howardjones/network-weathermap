<?php

namespace Weathermap\Integrations\Cacti;

require_once dirname(__FILE__) . '/weathermap-cacti88-plugin-compat.php';
//require_once dirname(__FILE__) . "/WeatherMapCactiManagementPlugin.php";

/**
 * The 0.8.8x specific parts of the Cacti 'management' plugin
 *
 * @package Weathermap\Integrations\Cacti
 */
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

    public function settingsForm($mapid = 0, $settingid = 0)
    {
        global $colors;

        if ($mapid > 0) {
            $map = $this->manager->getMap($mapid);
            $title = $map->title;
            $title = "per-map setting for Weathermap $mapid: $title";
        }
        if ($mapid < 0) {
            $group = $this->manager->getGroup(-$mapid);
            $title = $group->name;
            $grpid = -$mapid;
            $title = "per-group setting for Group $grpid: $title";
        }

        if ($mapid == 0) {
            $title = "setting for ALL maps";
        }

        $name = "";
        $value = "";

        if ($settingid != 0) {
            $result = $this->manager->getMapSettingById($settingid);

            $name = $result->optname;
            $value = $result->optvalue;
        }

        $values_ar = array();

        $field_ar = array(
            "mapid" => array("friendly_name" => "Map ID", "method" => "hidden_zero", "value" => $mapid),
            "id" => array("friendly_name" => "Setting ID", "method" => "hidden_zero", "value" => $settingid),
            "name" => array(
                "friendly_name" => "Name",
                "method" => "textbox",
                "max_length" => 128,
                "description" => "The name of the map-global SET variable",
                "value" => $name
            ),
            "value" => array(
                "friendly_name" => "Value",
                "method" => "textbox",
                "max_length" => 128,
                "description" => "What to set it to",
                "value" => $value
            )
        );

        $action = "Edit";
        if ($settingid == 0) {
            $action = "Create";
        }

        html_start_box("<strong>$action $title</strong>", "98%", $colors["header"], "3", "center", "");
        draw_edit_form(array("config" => $values_ar, "fields" => $field_ar));
        html_end_box();

        $action_url = $this->makeURL(array("action" => "map_settings", "id" => $mapid));

        form_save_button($action_url);

    }

    public function handleMapSettingsForm(
        $request,
        $appObject
    ) {
        $this->cactiHeader();

        if (isset($request['id'])) {
            $this->settingsForm($request['mapid'], $request['id']);
        } else {
            $this->settingsForm($request['mapid']);
        }

        $this->cactiFooter();
    }
}
