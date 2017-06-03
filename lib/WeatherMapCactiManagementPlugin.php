<?php

require_once "database.php";
require_once "WeatherMap.functions.php";
require_once "WeatherMapUIBase.class.php";
include_once 'WeathermapManager.class.php';

class WeatherMapCactiManagementPlugin extends WeatherMapUIBase
{

    public $cactiBasePath;
    public $manager;
    public $configPath;
    public $cacti_config;
    public $my_url;
    public $editor_url;

    public $commands = array(
        'groupadmin_delete' => array('handler' => 'handleGroupDelete', 'args' => array(array("id", "int"))),
        'groupadmin' => array('handler' => 'handleGroupSelect', 'args' => array()),
        'group_form' => array('handler' => 'handleGroupForm', 'args' => array(array("id", "int"))),
        'group_update' => array('handler' => 'handleGroupUpdate', 'args' => array(array("id", "int"), array("gname", "non-empty-string"))),
        'move_group_up' => array('handler' => 'handleGroupOrderUp', 'args' => array(array("id", "int"), array("order", "int"))),
        'move_group_down' => array('handler' => 'handleGroupOrderDown', 'args' => array(array("id", "int"), array("order", "int"))),

        'chgroup_update' => array('handler' => 'handleMapChangeGroup', 'args' => array(array("map_id", "int"), array("new_group", "int"))),
        'chgroup' => array('handler' => 'handleMapGroupChangeForm', 'args' => array(array("id", "int"))),

        'map_settings_delete' => array('handler' => 'handleMapSettingsDelete', 'args' => array(array("mapid", "int"), array("id", "int"))),
        'map_settings_form' => array('handler' => 'handleMapSettingsForm', 'args' => array(array("mapid", "int"))),
        'map_settings' => array('handler' => 'handleMapSettingsPage', 'args' => array(array("id", "int"))),
        'save' => array('handler' => 'handleMapSettingsSave', 'args' => array(array("mapid", "int"), array("id", "int"), array("name", "non-empty-string"), array("value", "string"))),

        'perms_add_user' => array('handler' => 'handlePermissionsAddUser', 'args' => array(array("mapid", "int"), array("userid", "int"))),
        'perms_delete_user' => array('handler' => 'handlePermissionsDeleteUser', 'args' => array(array("mapid", "int"), array("userid", "int"))),
        'perms_edit' => array('handler' => 'handlePermissionsPage', 'args' => array(array("id", "int"))),

        'delete_map' => array('handler' => 'handleDeleteMap', 'args' => array(array("id", "int"))),
        'deactivate_map' => array('handler' => 'handleDeactivateMap', 'args' => array(array("id", "int"))),
        'activate_map' => array('handler' => 'handleActivateMap', 'args' => array(array("id", "int"))),

        'addmap' => array('handler' => 'handleMapListAdd', 'args' => array(array("file", "mapfile"))),
        'addmap_picker' => array('handler' => 'handleMapPicker', 'args' => array(array("show_all", "bool"))),

        'move_map_up' => array('handler' => 'handleMapOrderUp', 'args' => array(array("id", "int"), array("order", "int"))),
        'move_map_down' => array('handler' => 'handleMapOrderDown', 'args' => array(array("id", "int"), array("order", "int"))),

        'viewconfig' => array('handler' => 'handleViewConfig', 'args' => array(array("file", "mapfile"))),

//        'rebuildnow' => array('handler' => 'handleRebuildNowStep1', 'args' => array()),
//        'rebuildnow2' => array('handler' => 'handleRebuildNowStep2', 'args' => array()),
        'settingsdump' => array('handler' => 'handleDumpSettings', 'args' => array()),
        'dump_maps' => array('handler' => 'handleDumpMaps', 'args' => array()),
        'enable_poller_output' => array('handler' => 'handleEnablePollerOutput', 'args' => array()),
        ':: DEFAULT ::' => array('handler' => 'handleManagementMainScreen', 'args' => array())
    );

    public function __construct($config)
    {
        parent::__construct();

        $this->my_url = "SHOULD-BE-OVERRIDDEN";
        $this->editor_url = "SHOULD-BE-OVERRIDDEN";
        $this->cacti_config = $config;
        $this->configPath = realpath(dirname(__FILE__) . '/../configs');
        $this->cactiBasePath = $config["base_path"];
        $this->manager = new WeathermapManager(weathermap_get_pdo(), $this->configPath);
    }

    public function make_url($params, $alt_url = "")
    {
        $base_url = $this->my_url;
        if ($alt_url != "") {
            $base_url = $alt_url;
        }
        $url = $base_url . (strpos($this->my_url, '?') === false ? '?' : '&');

        $parts = array();
        foreach ($params as $name => $value) {
            $parts [] = urlencode($name) . "=" . urlencode($value);
        }
        $url .= join("&", $parts);

        return $url;
    }

    public function handleDumpMaps($request, $appObject)
    {
        header('Content-type: application/json');

        $data = array(
            'maps' => $this->manager->getMaps(),
            'groups' => $this->manager->getGroups()
        );

        print json_encode($data);
    }

    public function handleEnablePollerOutput($request, $appObject)
    {
        $this->manager->saveMapSetting(0, 'rrd_use_poller_output', 1);
        header("Location: " . $this->make_url(array("action" => "map_settings", "id" => 0)));
    }

    public function handleGroupDelete($request, $appObject)
    {
        $id = -1;
        if (isset_request_var('id')) {
            $id = get_filter_request_var('id');
        }

        if ($id >= 1) {
            $this->manager->deleteGroup($id);
        }

        header("Location: " . $this->make_url(array("action" => "groupadmin", "header" => "false")));
    }

    public function handleGroupUpdate($request, $appObject)
    {
        $id = -1;
        $newname = "";
        if (isset($request['id']) && is_numeric($request['id'])) {
            $id = intval($request['id']);
        }
        if (isset($request['gname']) && (strlen($request['gname']) > 0)) {
            $newname = $request['gname'];
        }

        if ($id >= 0 && $newname != "") {
            $this->manager->renameGroup($id, $newname);
        }
        if ($id < 0 && $newname != "") {
            $this->manager->createGroup($newname);
        }
        header("Location: " . $this->make_url(array("action" => "groupadmin", "header" => "false")));
    }

    /**
     * @param $request
     */
    protected function handleGroupOrderUp($request, $appObject)
    {
        if (isset($request['id']) && is_numeric($request['id']) &&
            isset($request['order']) && is_numeric($request['order'])
        ) {
            $this->manager->moveGroup(intval($request['id']), intval($request['order']), -1);
        }
        header("Location: " . $this->make_url(array("action" => "groupadmin", "header" => "false")));
    }

    /**
     * @param $request
     */
    protected function handleGroupOrderDown($request, $appObject)
    {
        if (isset($request['id']) && is_numeric($request['id']) &&
            isset($request['order']) && is_numeric($request['order'])
        ) {
            $this->manager->moveGroup(intval($request['id']), intval($request['order']), 1);
        }
        header("Location: " . $this->make_url(array("action" => "groupadmin", "header" => "false")));
    }

    public function handleMapChangeGroup($request, $appObject)
    {
        $mapid = -1;
        $groupid = -1;

        if (isset($request['map_id']) && is_numeric($request['map_id'])) {
            $mapid = intval($request['map_id']);
        }
        if (isset($request['new_group']) && is_numeric($request['new_group'])) {
            $groupid = intval($request['new_group']);
        }

        if (($groupid > 0) && ($mapid >= 0)) {
            $this->manager->setMapGroup($mapid, $groupid);
        }

        header("Location: " . $this->make_url(array()));
    }

    public function handleMapSettingsDelete($request, $appObject)
    {
        $mapid = null;
        $settingid = null;
        if (isset($request['mapid']) && is_numeric($request['mapid'])) {
            $mapid = intval($request['mapid']);
        }
        if (isset($request['id']) && is_numeric($request['id'])) {
            $settingid = intval($request['id']);
        }

        if (!is_null($mapid) && !is_null($settingid)) {
            // create setting
            $this->manager->deleteMapSetting($mapid, $settingid);
        }
        header("Location: " . $this->make_url(array("action" => "map_settings", "id" => $mapid, "header" => "false")));
    }

    public function handleMapSettingsSave($request, $appObject)
    {
        $mapid = null;
        $settingid = null;
        $name = '';
        $value = '';

        if (isset($request['mapid']) && is_numeric($request['mapid'])) {
            $mapid = intval($request['mapid']);
        }

        if (isset($request['id']) && is_numeric($request['id'])) {
            $settingid = intval($request['id']);
        }

        if (isset($request['name']) && $request['name']) {
            $name = $request['name'];
        }

        if (isset($request['value']) && $request['value']) {
            $value = $request['value'];
        }

        if (!is_null($mapid) && $settingid == 0) {
            // create setting
            $this->manager->saveMapSetting($mapid, $name, $value);
        } elseif (!is_null($mapid) && !is_null($settingid)) {
            // update setting
            $this->manager->updateMapSetting($mapid, $settingid, $name, $value);
        }
        header("Location: " . $this->make_url(array("action" => "map_settings", "id" => $mapid, "header" => "false")));
    }

    protected function handlePermissionsAddUser($request, $appObject)
    {
        if (isset($request['mapid']) && is_numeric($request['mapid'])
            && isset($request['userid']) && is_numeric($request['userid'])
        ) {
            $this->manager->addPermission(intval($request['mapid']), intval($request['userid']));
            header("Location: " . $this->make_url(array("action" => "perms_edit", "id" => $request['mapid'], "header" => "false")));
        }
    }

    protected function handlePermissionsDeleteUser($request, $appObject)
    {
        if (isset($request['mapid']) && is_numeric($request['mapid'])
            && isset($request['userid']) && is_numeric($request['userid'])
        ) {
            $this->manager->removePermission($request['mapid'], $request['userid']);
            header("Location: " . $this->make_url(array("action" => "perms_edit", "id" => $request['mapid'], "header" => "false")));
        }
    }

    protected function handleDeleteMap($request, $appObject)
    {
        if (isset($request['id']) && is_numeric($request['id'])) {
            $this->manager->deleteMap($request['id']);
        }
        header("Location: " . $this->make_url(array("header" => "false")));
    }

    protected function handleDeactivateMap($request, $appObject)
    {
        if (isset($request['id']) && is_numeric($request['id'])) {
            $this->manager->disableMap($request['id']);
        }
        header("Location: " . $this->make_url(array("header" => "false")));
    }

    protected function handleActivateMap($request, $appObject)
    {
        if (isset($request['id']) && is_numeric($request['id'])) {
            $this->manager->activateMap($request['id']);
        }
        header("Location: " . $this->make_url(array("header" => "false")));
    }

    protected function handleMapListAdd($request, $appObject)
    {
        if (isset($request['file'])) {
            $this->manager->addMap($request['file']);
            header("Location: " . $this->make_url(array("header" => "false")));
        } else {
            print __("No such file.");
        }
    }

    protected function handleMapOrderUp($request)
    {
        if (isset($request['id']) && is_numeric($request['id']) &&
            isset($request['order']) && is_numeric($request['order'])
        ) {
            $this->manager->moveMap($request['id'], $request['order'], -1);
        }
        header("Location: " . $this->make_url(array("header" => "false")));
    }

    /**
     * @param $request
     */
    protected function handleMapOrderDown($request, $appObject)
    {
        if (isset($request['id']) && is_numeric($request['id']) &&
            isset($request['order']) && is_numeric($request['order'])
        ) {
            $this->manager->moveMap($request['id'], $request['order'], +1);
        }
        header("Location: " . $this->make_url(array("header" => "false")));
    }


    // *****************************************************************************************
    // These ones need overrides (UI stuff)
    public function handleMapSettingsForm($request, $appObject)
    {
        $this->cacti_header();
        print __("Unimplemented.");
        $this->cacti_footer();
    }

    public function handleGroupSelect($request, $appObject)
    {
        $this->cacti_header();
        print __("Unimplemented.");
        $this->cacti_footer();
    }

    public function handleGroupForm($request, $appObject)
    {
        $this->cacti_header();
        print __("Unimplemented.");
        $this->cacti_footer();
    }

    public function handleMapGroupChangeForm($request, $appObject)
    {
        $this->cacti_header();
        print __("Unimplemented.");
        $this->cacti_footer();
    }

    protected function handleMapSettingsPage($request, $appObject)
    {
        $this->cacti_header();
        print __("Unimplemented.");
        $this->cacti_footer();
    }

    protected function handlePermissionsPage($request, $appObject)
    {
        $this->cacti_header();
        print __("Unimplemented.");
        $this->cacti_footer();
    }

    protected function handleViewConfig($request, $appObject)
    {
        $this->cacti_header();
        $this->preview_config($request['file']);
        $this->cacti_footer();
    }

    protected function handleMapPicker($request, $appObject)
    {
        $this->cacti_header();
        print __("Unimplemented.");
        $this->cacti_footer();
    }

    public function handleDumpSettings($request, $appObject)
    {
        $this->cacti_header();
        print __("Unimplemented.");
        $this->cacti_footer();
    }

    public function handleManagementMainScreen($request, $appObject)
    {
        $this->cacti_header();
        print __("Unimplemented.");
        $this->cacti_footer();
    }

    // *****************************************************************************************

    function maplist_warnings()
    {
        if (!wm_module_checks()) {
            print '<div align="center" class="wm_warning"><p>';

            print '<b>' . __('Required PHP extensions are not present in your mod_php/ISAPI PHP module. Please check your PHP setup to ensure you have the GD extension installed and enabled.') . '</b><p>';
            print __('If you find that the weathermap tool itself is working, from the command-line or Cacti poller, then it is possible that you have two different PHP installations. The Editor uses the same PHP that webpages on your server use, but the main weathermap tool uses the command-line PHP interpreter.') . '<p>';
            print '<p>' . __('You should also run <a href=\'check.php\'>check.php</a> to help make sure that there are no problems.') . '</p><hr/>';


            print '</p></div>';
            exit();
        }

        $tables = weathermap_get_table_list(weathermap_get_pdo());
        if (!in_array('weathermap_maps', $tables)) {
            print '<div align="center" class="wm_warning"><p>';
            print __('The weathermap_maps table is missing completely from the database. Something went wrong with the installation process.');
            print '</p></div>';
        }

        $boost_enabled = $this->manager->getAppSetting('boost_rrd_update_enable', 'off');

        if ($boost_enabled == 'on') {
            $has_global_poller_output = $this->manager->getMapSettingByName(0, 'rrd_use_poller_output', false);

            if (!$has_global_poller_output) {
                print '<div align="center" class="wm_warning"><p>';
                print __('You are using the Boost plugin to update RRD files. Because this delays data being written to the files, it causes issues with Weathermap updates. You can resolve this by using Weathermap\'s \'poller_output\' support, which grabs data directly from the poller. <a href="%s">You can enable that globally by clicking here.</a>', $this->make_url(array("action" => "enable_poller_output")));
                print '</p></div>';
            }
        }

        $last_started = $this->manager->getAppSetting('weathermap_last_started_file', true);
        $last_finished = $this->manager->getAppSetting('weathermap_last_finished_file', true);
        $last_start_time = intval($this->manager->getAppSetting('weathermap_last_start_time', true));
        $last_finish_time = intval($this->manager->getAppSetting('weathermap_last_finish_time', true));
        $poller_interval = intval($this->manager->getAppSetting('poller_interval'));

        if (($last_finish_time - $last_start_time) > $poller_interval) {
            if (($last_started != $last_finished) && ($last_started != '')) {
                print '<div align="center" class="wm_warning"><p>';
                print __('Last time it ran, Weathermap did NOT complete its run. It failed during processing for \'%s\'', $last_started);
                print __('This <b>may</b> have affected other plugins that run during the poller process.') . '</p><p>';
                print __('You should either disable this map, or fault-find. Possible causes include memory_limit issues. The log may have more information.');
                print '</p></div>';
            }
        }
    }

    function maplist()
    {

        html_start_box(__('Weathermaps'), '100%', '', '3', 'center', $this->make_url(array("action" => "addmap_picker")));

        $headers = array(
            __('Config File'),
            __('Title'),
            __('Group'),
            __('Last Run'),
            __('Active'),
            __('Settings'),
            __('Sort Order'),
            __('Accessible By')
        );
        $userlist = $this->manager->getUserList();
        $users[0] = __('Anyone');

        foreach ($userlist as $user) {
            $users[$user->id] = $user->username;
        }

        $i = 0;

        $maps = $this->manager->getMapsWithGroups();
        $had_warnings = 0;

        html_header($headers, 2);

        if (is_array($maps)) {
            $this->cacti_row_start($i);

            print '<td>' . __('ALL MAPS') . '</td><td>' . __('(special settings for all maps)') . '</td><td></td><td></td>';
            print '<td></td>';

            print '<td><a class="hyperLink" href="' . $this->make_url(array("action" => "map_settings", "id" => 0)) . '">';
            $setting_count = $this->manager->getMapSettingCount(0, 0);
            if ($setting_count > 0) {
                print sprintf(__n('%d special', '%d specials', $setting_count), $setting_count);
            } else {
                print __('standard');
            }
            print '</a>';

            print '</td>';
            print '<td></td>';
            print '<td></td>';
            print '<td></td>';
            print '</tr>';

            foreach ($maps as $map) {
                $this->cacti_row_start($i);

                $editor_url = $this->make_url(array("action" => "nothing", "mapname" => $map->configfile), $this->editor_url);
                print '<td><a title="' . __('Click to start editor with this file') . '" href="' . $editor_url . '">' . htmlspecialchars($map->configfile) . '</a>';
                print '</td>';

                print '<td>' . htmlspecialchars($map->titlecache) . '</td>';
                print '<td><a class="hyperLink" title="' . __('Click to change group') . '" class="hyperLink" href="' . $this->make_url(array("action" => "chgroup", "id" => $map->id)) . '">' . htmlspecialchars($map->groupname) . '</a></td>';


                print "<td>";
                print sprintf("%.2gs", $map->runtime);
                if ($map->warncount > 0) {
                    $had_warnings++;
                    print '<br><a href="../../utilities.php?tail_lines=500&message_type=2&action=view_logfile&filter=' . urlencode($map->configfile) . '" title="Check cacti.log for this map"><img border=0 src="cacti-resources/img/exclamation.png" title="' . $map->warncount . ' warnings last time this map was run. Check your logs.">' . $map->warncount . "</a>";
                }
                print "</td>";

                if ($map->active == 'on') {
                    print '<td class="wm_enabled"><a title="' . __('Click to Deactivate') . '" class="hyperLink" href="' . $this->make_url(array("action" => "deactivate_map", "id" => $map->id)) . '"><font color="green">' . __('Yes') . '</font></a>';
                } else {
                    print '<td class="wm_disabled"><a title="' . __('Click to Activate') . '" class="hyperLink" href="' . $this->make_url(array("action" => "activate_map", "id" => $map->id)) . '"><font color="red">' . __('No') . '</font></a>';
                }
                print '<td>';

                print '<a class="hyperLink" href="' . $this->make_url(array("action" => "map_settings", "id" => $map->id)) . '">';
                $setting_count = $this->manager->getMapSettingCount($map->id);
                if ($setting_count > 0) {
                    print sprintf(__n('%s special', '%s specials', $setting_count), $setting_count);
                } else {
                    print __('standard');
                }
                print '</a>';
                print '</td>';

                print '<td>';

                print '<span class="remover fa fa-caret-up moveArrow" href="' . $this->make_url(array("action" => "move_map_up", "id" => $map->id, "order" => $map->sortorder)) . '" title="' . __('Move Map Up') . '"></span>';
                print '<span class="remover fa fa-caret-down moveArrow" href="' . $this->make_url(array("action" => "move_map_down", "id" => $map->id, "order" => $map->sortorder)) . '" title="' . __('Move Map Down') . '"></span>';
                print '</td>';

                print '<td>';

                $userlist = $this->manager->getMapAuthUsers($map->id);
                $mapusers = array();
                foreach ($userlist as $user) {
                    if (array_key_exists($user->userid, $users)) {
                        $mapusers[] = $users[$user->userid];
                    }
                }

                print '<a title="' . __('Click to edit permissions') . '" href="' . $this->make_url(array("action" => "perms_edit", "id" => $map->id)) . '">';
                if (count($mapusers) == 0) {
                    print __('(no users)');
                } else {
                    print join(', ', $mapusers);
                }
                print '</a>';

                print '</td>';
                print '<td class="right">';

                print '<span class="remover fa fa-remove deleteMarker" href="' . $this->make_url(array("action" => "delete_map", "id" => $map->id)) . '" title="' . __('Delete Map') . '"></span>';
                print '</td>';
                print '</tr>';
                $i++;
            }
        }

        if ($i == 0) {
            print '<tr><td colspan="4"><em>' . __('No Weathermaps Configured') . '</em></td></tr>';
        }

        html_end_box();

        $last_stats = $this->manager->getAppSetting('weathermap_last_stats', '');

        if ($last_stats != '') {
            print '<div align="center">' . __('Last Completed Run: %s', $last_stats) . '</div>';
        }

        if ($had_warnings > 0) {
            print '<div align="center" class="wm_warning">';
            print sprintf(__n('%s of your maps had warnings last time it ran. You can try to find these in your Cacti log file or by clicking on the warning sign next to that map (you might need to increase the log line count)', '%s of your maps had warnings last time it ran. You can try to find these in your Cacti log file or by clicking on the warning sign next to that map (you might need to increase the log line count)', $had_warnings), $had_warnings);
            print '</div>';
        }

        print '<div class="break"></div>';
        print '<div align="center">';
        print '<input type="button" id="edit" value="' . __('Edit Groups') . '">';
        print '<input type="button" id="settings" value="' . __('Settings') . '">';

        print '</div>';
    }


    function preview_config($file)
    {
        chdir($this->configPath);

        $path_parts = pathinfo($file);
        $file_dir = realpath($path_parts['dirname']);

        if ($file_dir != $this->configPath) {
            // someone is trying to read arbitrary files?
            // print '$file_dir != $weathermap_confdir';
            print '<h3>' . __('Path mismatch') . '</h3>';
        } else {
            html_start_box(__('Preview of %s', $file), '100%', '', '3', 'center', '');

            print '<tr><td class="textArea">';
            print '<pre>';

            $realfile = $this->configPath . '/' . $file;

            if (is_file($realfile)) {
                $fd = fopen($realfile, 'r');
                while (!feof($fd)) {
                    $buffer = fgets($fd, 4096);
                    print $buffer;
                }
                fclose($fd);
            }

            print '</pre>';
            print '</td></tr>';
            html_end_box();
        }
    }

    public function cacti_footer()
    {
    }

    private function cacti_header()
    {
    }

    private function cacti_row_start($i)
    {
    }
}
