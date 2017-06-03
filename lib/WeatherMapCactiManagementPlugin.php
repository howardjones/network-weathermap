<?php

require_once "database.php";
require_once "Weathermap.class.php";
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
            header("Location: " . $this->make_url(array("action" => "perms_edit", "id" => $request['mapid'])));
        }
    }

    protected function handlePermissionsDeleteUser($request, $appObject)
    {
        if (isset($request['mapid']) && is_numeric($request['mapid'])
            && isset($request['userid']) && is_numeric($request['userid'])
        ) {
            $this->manager->removePermission($request['mapid'], $request['userid']);
            header("Location: " . $this->make_url(array("action" => "perms_edit", "id" => $request['mapid'])));
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
        if (isset($request['id']) && is_numeric($request['id'])) {
            $this->cacti_header();
            $this->map_settings(intval($request['id']));
            // wmGenerateFooterLinks();
            $this->footer_links();
            $this->cacti_footer();
        }
    }

    protected function handlePermissionsPage($request, $appObject)
    {
        $this->cacti_header();
        if (isset($request['id']) && is_numeric($request['id'])) {
            $this->perms_list($request['id']);
        } else {
            print "Something got lost back there.";
        }
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
        if (isset($request['show_all']) && $request['show_all'] == 1) {
            $this->addmap_picker(true);
        } else {
            $this->addmap_picker(false);
        }
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

    protected function maplist_warnings()
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

    protected function maplist()
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


    protected function preview_config($file)
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

    protected function addmap_picker($show_all = false)
    {
        $loaded = array();
        $flags = array();

        // find out what maps are already in the database, so we can skip those
        $existing_maps = $this->manager->getMaps();
        if (is_array($existing_maps)) {
            foreach ($existing_maps as $map) {
                $loaded[] = $map->configfile;
            }
        }

        html_start_box(__('Available Weathermap Configuration Files'), '100%', '', '1', 'center', '');

        if (is_dir($this->configPath)) {
            $dh = opendir($this->configPath);
            if ($dh) {
                $i = 0;
                $skipped = 0;
                html_header(array('', '', __('Config File'), __('Title'), ''), 2);

                while ($file = readdir($dh)) {
                    $realfile = $this->configPath . '/' . $file;

                    // skip .-prefixed files like .htaccess, since it seems
                    // that otherwise people will add them as map config files.
                    // and the index.php too - for the same reason
                    if (substr($file, 0, 1) != '.' && $file != 'index.php') {
                        $used = in_array($file, $loaded);
                        $flags[$file] = '';
                        if ($used) {
                            $flags[$file] = 'USED';
                        }

                        if (is_file($realfile)) {
                            if ($used && !$show_all) {
                                $skipped++;
                            } else {
                                $title = $this->manager->extractMapTitle($realfile);
                                $titles[$file] = $title;
                                $i++;
                            }
                        }
                    }
                }
                closedir($dh);

                if ($i > 0) {
                    ksort($titles);

                    $n = 0;
                    foreach ($titles as $file => $title) {
                        $title = $titles[$file];
                        $this->cacti_row_start($n);

                        print '<td><a class="hyperLink" href="' . $this->make_url(array("action" => "addmap", "file" => $file)) . '" title="' . __('Add the configuration file') . '">' . __('Add') . '</a></td>';
                        print '<td><a class="hyperLink" href="' . $this->make_url(array("action" => "viewconfig", "file" => $file)) . '" title="' . __('View the configuration file in a new window') . '" target="_blank">' . __('View') . '</a></td>';
                        print '<td>' . htmlspecialchars($file);
                        if ($flags[$file] == 'USED') {
                            print ' <b>' . __('(USED)') . '</b>';
                        }
                        print '</td>';
                        print '<td><em>' . htmlspecialchars($title) . '</em></td>';
                        print '</tr>';
                        $n++;
                    }
                }

                if (($i + $skipped) == 0) {
                    print '<tr><td>' . __('No files were found in the configs directory.') . '</td></tr>';
                }

                if (($i == 0) && $skipped > 0) {
                    print '<tr><td>' . __('(%s files weren\'t shown because they are already in the database.', $skipped) . '</td></tr>';
                }
            } else {
                print '<tr><td>' . __('Can\'t open %s to read - you should set it to be readable by the webserver.', $this->configPath) . '</td></tr>';
            }
        } else {
            print '<tr><td>' . __('There is no directory named %s - you will need to create it, and set it to be readable by the webserver. If you want to upload configuration files from inside Cacti, then it should be <i>writable</i> by the webserver too.', $this->configPath) . '</td></tr>';
        }

        html_end_box();

        if ($skipped > 0) {
            print '<p align="center">' . __('Some files are not shown because they have already been added. You can <a href="%s">show these files too</a>, if you need to.', $this->make_url(array("action" => "addmap_picker", "show_all" => "1"))) . '</p>';
        }

        if ($show_all) {
            print '<p align="center">' . __('Some files are shown even though they have already been added. You can <a href="%s">hide those files too</a>, if you need to.', $this->make_url(array("action" => "addmap_picker"))) . '</p>';
        }
    }

    function perms_list($id)
    {
        $map = $this->manager->getMap($id);
        $title = $map->titlecache;

        $users = $this->manager->getUserList(true);
        $auth = $this->manager->getMapAuth($id);

        $mapuserids = array();

        // build an array of userids that are allowed to see this map (and that actually exist)
        foreach ($auth as $user) {
            if (isset($users[$user->userid])) {
                $mapuserids[] = $user->userid;
            }
        }

        // now build the list of users that exist but aren't currently allowed (for the picklist)
        $candidate_users = array();
        foreach ($users as $uid => $user) {
            if (!in_array($uid, $mapuserids)) {
                $candidate_users [] = $user;
            }
        }

        html_start_box(__('Edit permissions for Weathermap %s: %s', $id, $title), '100%', '', '2', 'center', '');

        html_header(array(__('Username'), ''));

        $n = 0;
        foreach ($mapuserids as $user) {
            $this->cacti_row_start($n);
            print '<td>' . htmlspecialchars($users[$user]->username) . '</td>';

            print '<td>';
            print '<a href="' . $this->make_url(array("action" => "perms_delete_user", "mapid" => $id, "userid" => $user, "header" => "false")) . '">';
            print '<img src="../../images/delete_icon.gif" width="10" height="10" border="0" alt="' . __('Remove permissions for this user to see this map') . '">';
            print '</a></td>';

            print '</tr>';
            $n++;
        }

        if (sizeof($mapuserids) == 0) {
            print '<tr><td><em>' . __('nobody can see this map') . '</em></td></tr>';
        }

        html_end_box();

        html_start_box('', '100%', '', '3', 'center', '');

        print '<tr>';

        if (sizeof($candidate_users) == 0) {
            print '<td><em>' . __('There aren\'t any users left to add!') . '</em></td></tr>';
        } else {
            print '<td><form action="">' . __('Allow') . ' <input type="hidden" name="action" value="perms_add_user"><input type="hidden" name="mapid" value="' . $id . '"><select name="userid">';
            foreach ($candidate_users as $user) {
                printf('<option value="%s">%s</option>', $user->id, $user->username);
            }

            print '</select> ' . __('to see this map') . ' <input type="submit" value="' . __('Update') . '"></form></td>';
            print '</tr>';
        }

        html_end_box();
    }


    function map_settings($id)
    {
        if ($id == 0) {
            $title = __('Additional settings for ALL maps');
            $nonemsg = __('There are no settings for all maps yet. You can add some by clicking Add up in the top-right, or choose a single map from the management screen to add settings for that map.');
            $type = 'global';
            $settingrows = $this->manager->getMapSettings(0);
        } elseif ($id < 0) {
            $group_id = -intval($id);
            $group = $this->manager->getGroup($group_id);

            $title = __('Edit per-map settings for Group %s: %s', $group->id, $group->name);
            $nonemsg = __('There are no per-group settings for this group yet. You can add some by clicking Add up in the top-right.');
            $type = 'group';
            $settingrows = $this->manager->getMapSettings(-$group_id);

            print '<p>' . __('All maps in this group are also affected by the following GLOBAL settings (group overrides global, map overrides group, but BOTH override SET commands within the map config file):') . '</p>';
            $this->map_readonly_settings(0, __('Global Settings'));
        } else {
            $map = $this->manager->getMap($id);
            $group = $this->manager->getGroup($map->group_id);

            $title = __('Edit per-map settings for Weathermap %s: %s', $id, $map->titlecache);
            $nonemsg = __('There are no per-map settings for this map yet. You can add some by clicking Add up in the top-right.');
            $type = 'map';
            $settingrows = $this->manager->getMapSettings(intval($id));

            print '<p>' . __('This map is also affected by the following GLOBAL and GROUP settings (group overrides global, map overrides group, but BOTH override SET commands within the map config file):') . '</p>';

            $this->map_readonly_settings(0, __('Global Settings'));
            $this->map_readonly_settings(-$map->group_id, __('Group Settings (%s)', htmlspecialchars($group->name)));
        }

        html_start_box($title, '100%', '', '2', 'center', 'weathermap-cacti10-plugin-mgmt.php?action=map_settings_form&mapid=' . intval($id));
        html_header(array(__('Actions'), __('Name'), __('Value')), 2);

        $n = 0;
        if (is_array($settingrows)) {
            if (sizeof($settingrows) > 0) {
                foreach ($settingrows as $setting) {
                    $this->cacti_row_start($n);

                    print '<td style="width:4%"><a href="' . $this->make_url(array("action" => "map_settings_form", "mapid" => $id, "id" => intval($setting->id))) . '"><img src="../../images/graph_properties.gif" width="16" height="16" border="0" alt="' . __('Edit this definition') . '">' . __('Edit') . '</a></td>';
                    print "<td>" . htmlspecialchars($setting->optname) . "</td>";
                    print "<td>" . htmlspecialchars($setting->optvalue) . "</td>";
                    print '<td><a class="hyperLink" href="' . $this->make_url(array("action" => "map_settings_delete", "mapid" => $id, "header" => "false", "id" => intval($setting->id))) . '"><img src="../../images/delete_icon_large.gif" width="12" height="12" border="0" alt="' . __('Remove this definition from this map') . '"></a></td>';
                    print "</tr>";
                    $n++;
                }
            } else {
                print '<tr>';
                print "<td colspan=2><em>$nonemsg</em></td>";
                print '</tr>';
            }
        }

        html_end_box();

        print '<div align=center>';

        if ($type == 'group') {
            print '<a class="hyperLink" href="weathermap-cacti10-plugin-mgmt.php?action=groupadmin">' . __('Back to Group Admin') . '</a>';
        }

        if ($type == 'global') {
            print '<a class="hyperLink" href="weathermap-cacti10-plugin-mgmt.php?action=">' . __('Back to Map Admin') . '</a>';
        }

        print '</div>';
    }

    function map_readonly_settings($id, $title = '')
    {
        if ($title == '') {
            $title = __('Settings');
        }

        if ($id == 0) {
            $settings = $this->manager->getMapSettings(0);
        }

        if ($id < 0) {
            $settings = $this->manager->getMapSettings(intval($id));
        }

        if ($id > 0) {
            $settings = $this->manager->getMapSettings(intval($id));
        }

        html_start_box($title, '100%', '', '2', 'center', '');
        html_header(array(__('Name'), __('Value')));

        $n = 0;
        if (sizeof($settings) > 0) {
            foreach ($settings as $setting) {
                $this->cacti_row_start($n);
                print '<td>' . htmlspecialchars($setting->optname) . '</td>';
                print '<td>' . htmlspecialchars($setting->optvalue) . '</td>';
                print '</tr>';
                $n++;
            }
        } else {
            $this->cacti_row_start($n);
            print '<td colspan=4><em>' . __('No Settings') . '</em></td>';
            print '</tr>';
        }

        html_end_box();
    }

    public function cacti_footer()
    {
        print "OVERRIDE ME";
    }

    public function cacti_header()
    {
        print "OVERRIDE ME";
    }

    public function cacti_row_start($i)
    {
    }

    public function footer_links()
    {
        global $WEATHERMAP_VERSION;

        $html = "";

        $html .= '<a class="linkOverDark" href="manual/">Local Documentation</a>';
        $html .= ' -- <a class="linkOverDark" href="http://www.network-weathermap.com/">Weathermap Website</a>';
        $html .= ' -- <a class="linkOverDark" href="'.$this->make_url(array(), $this->editor_url).'">Weathermap Editor</a>';
        $html .= " -- This is version $WEATHERMAP_VERSION";

        print "<br />";
        html_start_box($html, '78%', '', '4','center','');
        html_end_box();
    }
}
