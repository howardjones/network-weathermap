<?php

namespace Weathermap\Integrations\Cacti;

require_once dirname(__FILE__) . "/database.php";

use Weathermap\Integrations\ManagementWebAPI;
use Weathermap\Integrations\MapManager;
use Weathermap\UI\UIBase;
use Weathermap\Core\MapUtility;

/**
 * The common parts of the Cacti 'management' plugin
 *
 * @package Weathermap\Integrations\Cacti
 */
class WeatherMapCactiManagementPlugin extends UIBase
{

    public $cactiBasePath;
    /** @var MapManager $manager */
    public $manager;
    public $configPath;
    public $cactiConfig;
    public $myURL;
    public $editorURL;
    public $basePath;

    protected $api;

    public $commands = array(
        ':: DEFAULT ::' => array('handler' => 'handleManagementMainScreen', 'args' => array()),

        'app_settings' => array('handler' => 'handleSettingsAPI', 'args' => array()),
        #'app_listusers' => array('handler' => '', 'args' => array()),
        #'app_listusergroups' => array('handler' => '', 'args' => array()),

        'listmaps' => array('handler' => 'handleDumpMapsAPI', 'args' => array()),
        'listmapfiles' => array('handler' => 'handleDumpMapFilesAPI', 'args' => array()),

//        'map_create' => array(
//            'handler' => '',
//            'args' => array(
//                array('filename', 'mapfile'),
//                array('source', 'mapfile', true),
//                array('add', 'bool', true),
//                array('group_id', 'int')
//            )
//        ),

//        'map_add' => array(
//            'handler' => 'handleMapAddAPI',
//            'args' => array(
//                array('filename', 'mapfile'),
//                array('group_id', 'int')
//            )
//        ),
//

        'maps_add' => array(
            'handler' => 'handleMapsListAdd',
            'args' => array(
                array('maps', 'mapfilelist'),
                array('group_id', 'int')
            )
        ),

        'enable_map' => array(
            'handler' => 'handleMapEnableAPI',
            'args' => array(
                array('id', 'int'),
            )
        ),

        'disable_map' => array(
            'handler' => 'handleMapDisableAPI',
            'args' => array(
                array('id', 'int'),
            )
        ),
//
//        'map_delete' => array(
//            'handler' => '',
//            'args' => array(
//                'args' => array(
//                    array('id', 'int')
//                )
//            )
//        ),
//        'map_update' => array('handler' => '', 'args' => array()),
//        'map_getconfig' => array('handler' => '', 'args' => array()),
//
        'group_add' => array(
            'handler' => 'handleGroupAddAPI',
            'args' => array(
                array('name', 'non-empty-string'),
            )
        ),
        'group_delete' => array(
            'handler' => 'handleGroupDeleteAPI',
            'args' => array(
                array('id', 'int')
            )
        ),
//        'group_update' => array(
//            'handler' => 'handleGroupUpdateAPI',
//            'args' => array(
//                array('id', 'int'),
//                array('name', 'non-empty-string', true),
//                array('position_after', 'int', true)
//            )
//        ),

        #     'settings_add' => array('handler' => '', 'args' => array()),
        #     'settings_update' => array('handler' => '', 'args' => array()),
        #     'settings_delete' => array('handler' => '', 'args' => array()),

        #     'perms_add' => array('handler' => '', 'args' => array()),
        #     'perms_update' => array('handler' => '', 'args' => array()),
        #     'perms_delete' => array('handler' => '', 'args' => array()),


//        'groupadmin_delete' => array('handler' => 'handleGroupDelete', 'args' => array(array("id", "int"))),
//        'groupadmin' => array('handler' => 'handleGroupSelect', 'args' => array()),
//        'group_form' => array('handler' => 'handleGroupForm', 'args' => array(array("id", "int"))),
//        'group_update' => array(
//            'handler' => 'handleGroupUpdate',
//            'args' => array(array("id", "int", true), array("gname", "non-empty-string"))
//        ),
//        'move_group_up' => array(
//            'handler' => 'handleGroupOrderUp',
//            'args' => array(array("id", "int"), array("order", "int"))
//        ),
//        'move_group_down' => array(
//            'handler' => 'handleGroupOrderDown',
//            'args' => array(array("id", "int"), array("order", "int"))
//        ),
//
//        'chgroup_update' => array(
//            'handler' => 'handleMapChangeGroup',
//            'args' => array(array("map_id", "int"), array("new_group", "int"))
//        ),
//        'chgroup' => array('handler' => 'handleMapGroupChangeForm', 'args' => array(array("id", "int"))),
//
//        'map_settings_delete' => array(
//            'handler' => 'handleMapSettingsDelete',
//            'args' => array(array("mapid", "int"), array("id", "int"))
//        ),
//        'map_settings_form' => array('handler' => 'handleMapSettingsForm', 'args' => array(array("mapid", "int"))),
//        'map_settings' => array('handler' => 'handleMapSettingsPage', 'args' => array(array("id", "int"))),
//        'save' => array(
//            'handler' => 'handleMapSettingsSave',
//            'args' => array(
//                array("mapid", "int"),
//                array("id", "int"),
//                array("name", "non-empty-string"),
//                array("value", "string")
//            )
//        ),
//
//        'perms_add_user' => array(
//            'handler' => 'handlePermissionsAddUser',
//            'args' => array(array("mapid", "int"), array("userid", "int"))
//        ),
//        'perms_delete_user' => array(
//            'handler' => 'handlePermissionsDeleteUser',
//            'args' => array(array("mapid", "int"), array("userid", "int"))
//        ),
//        'perms_edit' => array('handler' => 'handlePermissionsPage', 'args' => array(array("id", "int"))),
//
        'delete_map' => array('handler' => 'handleDeleteMap', 'args' => array(array("id", "int"))),
//        'deactivate_map' => array('handler' => 'handleDeactivateMap', 'args' => array(array("id", "int"))),
//        'activate_map' => array('handler' => 'handleActivateMap', 'args' => array(array("id", "int"))),
//
//        'addmap' => array(
//            'handler' => 'handleMapListAdd',
//            'args' => array(
//                array("file", "mapfile")
//            )),
//        'addmap_picker' => array('handler' => 'handleMapPicker', 'args' => array(array("show_all", "bool", true))),
//
//        'move_map_up' => array(
//            'handler' => 'handleMapOrderUp',
//            'args' => array(array("id", "int"), array("order", "int"))
//        ),
//        'move_map_down' => array(
//            'handler' => 'handleMapOrderDown',
//            'args' => array(array("id", "int"), array("order", "int"))
//        ),
//
//        'viewconfig' => array('handler' => 'handleViewConfig', 'args' => array(array("file", "mapfile"))),
//
//        'enable_poller_output' => array('handler' => 'handleEnablePollerOutput', 'args' => array()),


    );

    public function __construct($config, $basePath)
    {
        parent::__construct();

        $this->basePath = $basePath;
        $this->myURL = "SHOULD-BE-OVERRIDDEN";
        $this->editorURL = "SHOULD-BE-OVERRIDDEN";
        $this->cactiConfig = $config;
        $this->configPath = $basePath . '/configs';
        $this->cactiBasePath = $config["base_path"];
        $pdo = weathermap_get_pdo();
        $cactiInterface = new CactiApplicationInterface($pdo);
        $this->manager = new MapManager($pdo, $this->configPath, $cactiInterface);
        $this->api = new ManagementWebAPI($this->manager);
    }

    public function main($request)
    {
        $action = ":: DEFAULT ::";
        if (isset($request['action'])) {
            $action = strtolower(trim($request['action']));
        }

        if ($this->validateRequest($action, $request)) {
            $result = $this->dispatchRequest($action, $request, $this->manager);
            print $result;
        } else {
            print "INPUT VALIDATION FAIL";
        }
    }

    public function makeURL($params, $altURL = "")
    {
        $baseURL = $this->myURL;
        if ($altURL != "") {
            $baseURL = $altURL;
        }
        $url = $baseURL . (strpos($this->myURL, '?') === false ? '?' : '&');

        $parts = array();
        foreach ($params as $name => $value) {
            $parts [] = urlencode($name) . "=" . urlencode($value);
        }
        $url .= join("&", $parts);

        return $url;
    }

    public function handleDumpMapFilesAPI($request, $appObject)
    {
        header('Content-type: application/json');

        $data = $this->getMapFileList();

        print json_encode($data);
    }

    private function getMapFileList()
    {
        $files = array();
        $valid_files = array();

        $loaded = array();

        // find out what maps are already in the database, so we can mark those
        $existingMaps = $this->manager->getMaps();
        if (is_array($existingMaps)) {
            foreach ($existingMaps as $map) {
                $loaded[] = $map->configfile;
            }
        }

        if (!is_dir($this->configPath)) {
            return $files;
        }

        $dh = opendir($this->configPath);
        if ($dh) {
            while ($file = readdir($dh)) {
                // skip .-prefixed files like .htaccess, since it seems
                // that otherwise people will add them as map config files.
                // and the index.php too - for the same reason
                if (substr($file, 0, 1) != '.' && $file != 'index.php') {
                    $valid_files [] = $file;
                }
            }
            closedir($dh);
        }

        sort($valid_files);

        foreach ($valid_files as $file) {
            $realfile = $this->configPath . '/' . $file;
            $used = in_array($file, $loaded);
            $flags = array();
            if ($used) {
                $flags [] = 'USED';
            }

            if (is_file($realfile)) {
                $title = $this->manager->extractMapTitle($realfile);
                $files [] = array("config" => $file, "title" => $title, "flags" => $flags);
            }
        }

        return $files;
    }

    public function handleSettingsAPI(
        $request,
        $appObject
    ) {
        header('Content-type: application/json');

        $styleTextOptions = array("thumbs", "full", "full-first-only");
        $trueFalseLookup = array(false, true);

        $style = $this->manager->application->getAppSetting("weathermap_pagestyle", 0);

        $cycleTime = $this->manager->application->getAppSetting("weathermap_cycle_refresh", 0);
        if ($cycleTime == 0) {
            $cycleTime = 'auto';
        }

        $showAllMapsTab = $this->manager->application->getAppSetting("weathermap_all_tab", 0);
        $showMapSelector = $this->manager->application->getAppSetting("weathermap_map_selector", 0);

        $data = array(
            'wm_version' => WEATHERMAP_VERSION,
            'page_style' => $styleTextOptions[$style],
            'cycle_time' => (string)$cycleTime,
            'show_all_tab' => $trueFalseLookup[$showAllMapsTab],
            'map_selector' => $trueFalseLookup[$showMapSelector],
            'thumb_url' => $this->makeURL(array("action" => "viewthumb")),
            'image_url' => $this->makeURL(array("action" => "viewimage")),
            'editor_url' => $this->makeURL(array("action" => "nothing", "mapname" => ""), $this->editorURL),
            'maps_url' => $this->makeURL(array("action" => "listmaps")),
            'docs_url' => 'docs/index.html',
            'api_url' => $this->makeURL(array("action" => "")),
            'management_url' => $this->makeURL(array("action" => ""))
        );

        print json_encode($data);
    }

    public function handleDumpMapsAPI(
        $request,
        $appObject
    ) {
        $this->api->mapList();
    }

    protected function handleMapEnableAPI(
        $request,
        $appObject
    ) {
        $data = array("success" => false);

        if (isset($request['id']) && is_numeric($request['id'])) {
            $this->manager->activateMap($request['id']);
            $data = array("success" => true);
        }
        header('Content-type: application/json');
        print json_encode($data);
    }

    protected function handleMapDisableAPI(
        $request,
        $appObject
    ) {
        $data = array("success" => false);

        if (isset($request['id']) && is_numeric($request['id'])) {
            $this->manager->disableMap($request['id']);
            $data = array("success" => true);
        }
        header('Content-type: application/json');
        print json_encode($data);
    }

    public function handleGroupAddAPI($request, $appObject)
    {
        $this->manager->createGroup($request['name']);

        $data = array("result" => "OK");

        header('Content-type: application/json');
        return json_encode($data);
    }

    public function handleGroupDeleteAPI($request, $appObject)
    {
        $successful = $this->manager->deleteGroup($request['id']);

        header('Content-type: application/json');
        if ($successful) {
            $data = array("result" => "OK");
        } else {
            $data = array("result" => "FAIL");
        }

        return json_encode($data);
    }

    public function handleMapDeleteAPI($request, $appObject)
    {
        $this->manager->deleteMap($request['id']);

        header('Content-type: application/json');
        $data = array("result" => "OK");

        return json_encode($data);
    }


// ******************************************************


    public function handleEnablePollerOutput(
        $request,
        $appObject
    ) {
        $this->manager->saveMapSetting(0, 'rrd_use_poller_output', 1);
        header("Location: " . $this->makeURL(array("action" => "map_settings", "id" => 0)));
    }

    public function handleGroupDelete(
        $request,
        $appObject
    ) {
        $id = -1;
        if (isset_request_var('id')) {
            $id = get_filter_request_var('id');
        }

        if ($id >= 1) {
            $this->manager->deleteGroup($id);
        }

        header("Location: " . $this->makeURL(array("action" => "groupadmin", "header" => "false")));
    }

    public function handleGroupUpdate(
        $request,
        $appObject
    ) {
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
        header("Location: " . $this->makeURL(array("action" => "groupadmin", "header" => "false")));
    }

    /**
     * @param $request
     */
    protected function handleGroupOrderUp(
        $request,
        $appObject
    ) {
        if (isset($request['id']) && is_numeric($request['id']) &&
            isset($request['order']) && is_numeric($request['order'])
        ) {
            $this->manager->moveGroup(intval($request['id']), -1);
        }
        header("Location: " . $this->makeURL(array("action" => "groupadmin", "header" => "false")));
    }

    /**
     * @param $request
     */
    protected function handleGroupOrderDown(
        $request,
        $appObject
    ) {
        if (isset($request['id']) && is_numeric($request['id']) &&
            isset($request['order']) && is_numeric($request['order'])
        ) {
            $this->manager->moveGroup(intval($request['id']), 1);
        }
        header("Location: " . $this->makeURL(array("action" => "groupadmin", "header" => "false")));
    }

    public function handleMapChangeGroup(
        $request,
        $appObject
    ) {
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

        header("Location: " . $this->makeURL(array()));
    }

    public function handleMapSettingsDelete(
        $request,
        $appObject
    ) {
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
        header(
            "Location: " . $this->makeURL(
                array(
                "action" => "map_settings",
                "id" => $mapid,
                "header" => "false"
                )
            )
        );
    }

    public function handleMapSettingsSave(
        $request,
        $appObject
    ) {
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
        header(
            "Location: " . $this->makeURL(
                array(
                "action" => "map_settings",
                "id" => $mapid,
                "header" => "false"
                )
            )
        );
    }

    protected function handlePermissionsAddUser(
        $request,
        $appObject
    ) {
        if (isset($request['mapid']) && is_numeric($request['mapid'])
            && isset($request['userid']) && is_numeric($request['userid'])
        ) {
            $this->manager->addPermission(intval($request['mapid']), intval($request['userid']));
            header("Location: " . $this->makeURL(array("action" => "perms_edit", "id" => $request['mapid'])));
        }
    }

    protected function handlePermissionsDeleteUser(
        $request,
        $appObject
    ) {
        if (isset($request['mapid']) && is_numeric($request['mapid'])
            && isset($request['userid']) && is_numeric($request['userid'])
        ) {
            $this->manager->removePermission($request['mapid'], $request['userid']);
            header("Location: " . $this->makeURL(array("action" => "perms_edit", "id" => $request['mapid'])));
        }
    }

    protected function handleDeleteMap(
        $request,
        $appObject
    ) {
        if (isset($request['id']) && is_numeric($request['id'])) {
            $this->manager->deleteMap($request['id']);
        }
        header("Location: " . $this->makeURL(array("header" => "false")));
    }

    protected function handleDeactivateMap(
        $request,
        $appObject
    ) {
        if (isset($request['id']) && is_numeric($request['id'])) {
            $this->manager->disableMap($request['id']);
        }
        header("Location: " . $this->makeURL(array("header" => "false")));
    }

    protected function handleActivateMap(
        $request,
        $appObject
    ) {
        if (isset($request['id']) && is_numeric($request['id'])) {
            $this->manager->activateMap($request['id']);
        }
        header("Location: " . $this->makeURL(array("header" => "false")));
    }

    protected function handleMapListAdd(
        $request,
        $appObject
    ) {
        if (isset($request['file'])) {
            $this->manager->addMap($request['file']);
            header("Location: " . $this->makeURL(array("header" => "false")));
        } else {
            print __("No such file.");
        }
    }


    protected function handleMapsListAdd(
        $request,
        $appObject
    ) {
        if (isset($request['maps'])) {
            $maps = explode(',', $request['maps']);
            foreach ($maps as $map) {
                $this->manager->addMap($map, $request['group_id']);
            }
            header("Location: " . $this->makeURL(array("header" => "false")));
        } else {
            print __("No such file.");
        }
    }

    protected function handleMapOrderUp(
        $request
    ) {
        if (isset($request['id']) && is_numeric($request['id']) &&
            isset($request['order']) && is_numeric($request['order'])
        ) {
            $this->manager->moveMap($request['id'], -1);
        }
        header("Location: " . $this->makeURL(array("header" => "false")));
    }

    /**
     * @param $request
     */
    protected function handleMapOrderDown(
        $request,
        $appObject
    ) {
        if (isset($request['id']) && is_numeric($request['id']) &&
            isset($request['order']) && is_numeric($request['order'])
        ) {
            $this->manager->moveMap($request['id'], +1);
        }
        header("Location: " . $this->makeURL(array("header" => "false")));
    }


// *****************************************************************************************
// These ones need overrides (UI stuff)
    public function handleMapSettingsForm(
        $request,
        $appObject
    ) {
        $this->cactiHeader();
        print __("Unimplemented (overidden).");
        $this->cactiFooter();
    }

    public function handleGroupSelect(
        $request,
        $appObject
    ) {
        $this->cactiHeader();

        \html_start_box(
            __('Edit Map Groups'),
            '100%',
            '',
            '2',
            'center',
            'weathermap-cacti10-plugin-mgmt.php?action=group_form&id=0'
        );
        \html_header(array(__('Actions'), __('Group Name'), __('Settings'), __('Sort Order')), 2);

        $groups = $this->manager->getGroups();

        if (is_array($groups)) {
            if (count($groups) > 0) {
                foreach ($groups as $group) {
                    \form_alternate_row();


                    print '<td style="width:4%"><a class="hyperLink" href="';
                    print $this->makeURL(array("action" => "group_form", "id" => $group->id));
                    print '"><img src="../../images/graph_properties.gif" width="16" height="16" border="0" alt="" title="' . __('Rename This Group') . '"></a></td>';
                    print '<td>' . htmlspecialchars($group->name) . '</td>';

                    print '<td>';
                    print "<a class='hyperLink' href='weathermap-cacti10-plugin-mgmt.php?action=map_settings&id=-" . $group->id . "'>";
                    $settingCount = $this->manager->getMapSettingCount(0, $group->id);
                    if ($settingCount > 0) {
                        print sprintf(__n('%s special', '%s specials', $settingCount), $settingCount);
                    } else {
                        print __('standard');
                    }
                    print '</a>';
                    print '</td>';

                    print '<td>';

                    print '<span class="remover fa fa-caret-up moveArrow" href="';
                    print $this->makeURL(array("action" => "move_group_up", "id" => $group->id));
                    print '" title="' . __('Move Group Up') . '"></span>';
                    print '<span class="remover fa fa-caret-down moveArrow" href="';
                    print $this->makeURL(array("action" => "move_group_down", "id" => $group->id));
                    print '" title="' . __('Move Group Down') . '"></span>';
                    print '</td>';

                    print '<td class="right">';
                    if ($group->id > 1) {
                        print '<span class="remover fa fa-remove deleteMarker" href="';
                        print $this->makeURL(array("action" => "groupadmin_delete", "id" => $group->id));
                        print '" title="' . __('Remove this definition from this map') . '"></span>';
                    }
                    print '</td>';

                    print '</tr>';
                }
            } else {
                print '<tr>';
                print '<td colspan=2>' . __('No groups are defined.') . '</td>';
                print '</tr>';
            }
        }

        \html_end_box();

        $this->cactiFooter();
    }

    public function handleGroupForm(
        $request,
        $appObject
    ) {
        $id = $request['id'];

        $this->cactiHeader();

        \form_start('weathermap-cacti10-plugin-mgmt.php');

        $groupName = '';
        // if id==0, it's an Add, otherwise it's an editor.
        if ($id == 0) {
            \html_start_box(__('Adding a Group...'), '100%', '', '2', 'center', '');
        } else {
            \html_start_box(__('Editing Group %s', $id), '100%', '', '2', 'center', '');
            $group = $this->manager->getGroup($id);
            $groupName = $group->name;
        }

        print '<td>' . __('Group Name:') . "<input type='text' name='gname' value='" . htmlspecialchars($groupName) . "'/>\n";

        if ($id > 0) {
            print " <input type='submit' value='" . __('Update') . "' /></td>\n";
        } else {
            print " <input type='submit' value='" . __('Add') . "' /></td>\n";
        }

        print "<td><input type='hidden' name='action' value='group_update'/></td>";
        if ($id > 0) {
            print "<td><input type='hidden' name='id' value='$id' /></td>\n";
        }

        \html_end_box();

        \form_end();

        $this->cactiFooter();
    }

    public function handleMapGroupChangeForm(
        $request,
        $appObject
    ) {
        $this->cactiHeader();

        $mapId = $request['id'];
        $map = $this->manager->getMap($mapId);
        $title = $map->titlecache;
        $curgroup = $map->group_id;

        \form_start('weathermap-cacti10-plugin-mgmt.php', 'editme');

        \html_start_box(
            __('Edit map group for Weathermap %s: %s', $mapId, $title),
            '100%',
            '',
            '2',
            'center',
            ''
        );

        print "<td>" . __('Choose an existing Group:') . "&nbsp;<select name='new_group'>";

        $groups = $this->manager->getGroups();

        foreach ($groups as $grp) {
            print '<option ';
            if ($grp->id == $curgroup) {
                print ' SELECTED ';
            }
            print 'value=' . $grp->id . '>' . htmlspecialchars($grp->name) . '</option>';
        }

        print '</select>';
        print "&nbsp;<input type='button' id='save' name='save' value='" . __('Save') . "' title='" . __('Change Group') . "'>";
        print '</td>';
        print "</tr>\n";
        print '<tr><td></td></tr>';


        print "<tr><td><p>" . __(
            'or create a new group in the <b><a href=\'%s\'>group management screen</a>',
            $this->makeURL(array('action' => 'groupadmin'))
        );
        print "</b></p></td></tr>";

        print "<tr><td><input type=hidden name='map_id' value='" . $mapId . "'></td></td>";
        print "<tr><td><input type=hidden name='action' value='chgroup_update'></td></td>";

        \html_end_box();

        \form_end();

        ?>
        <script type='text/javascript'>
            $(function () {
                $('#save').click(function () {
                    var strURL = 'weathermap-cacti10-plugin-mgmt.php';
                    strURL += (strURL.indexOf('?') >= 0 ? '&' : '?') + 'header=false';
                    var json = $('#editme').serializeObject();
                    $.post(strURL, json).done(function (data) {
                        $('#main').html(data);
                        applySkin();
                        window.scrollTo(0, 0);
                    });
                });
            });
        </script>
        <?php


        $this->cactiFooter();
    }

    protected function handleMapSettingsPage(
        $request,
        $appObject
    ) {
        if (isset($request['id']) && is_numeric($request['id'])) {
            $this->cactiHeader();
            $this->mapSettings(intval($request['id']));
            // wmGenerateFooterLinks();
            $this->footerLinks();
            $this->cactiFooter();
        }
    }

    protected function handlePermissionsPage(
        $request,
        $appObject
    ) {
        $this->cactiHeader();
        if (isset($request['id']) && is_numeric($request['id'])) {
            $this->permissionsList($request['id']);
        } else {
            print "Something got lost back there.";
        }
        $this->cactiFooter();
    }

    protected function handleViewConfig(
        $request,
        $appObject
    ) {
        $this->cactiHeader();
        $this->previewConfig($request['file']);
        $this->cactiFooter();
    }

    protected function handleMapPicker(
        $request,
        $appObject
    ) {
        $this->cactiHeader();
        if (isset($request['show_all']) && $request['show_all'] == 1) {
            $this->addmapPicker(true);
        } else {
            $this->addmapPicker(false);
        }
        $this->cactiFooter();
    }


    public function handleManagementMainScreen(
        $request,
        $appObject
    ) {
        $this->cactiHeader();
        print __("Unimplemented (overridden in subclasses though).");
        $this->cactiFooter();
    }

// *****************************************************************************************

    protected function maplistWarnings()
    {
        if (!MapUtility::moduleChecks()) {
            print '<div align="center" class="wm_warning"><p>';

            print '<b>' . __('Required PHP extensions are not present in your mod_php/ISAPI PHP module. Please check your PHP setup to ensure you have the GD extension installed and enabled.') . '</b><p>';
            print __('If you find that the weathermap tool itself is working, from the command-line or Cacti poller, then it is possible that you have two different PHP installations. The Editor uses the same PHP that webpages on your server use, but the main weathermap tool uses the command-line PHP interpreter.') . '<p>';
            print '<p>' . __('You should also run <a href=\'check.php\'>check.php</a> to help make sure that there are no problems.') . '</p><hr/>';


            print '</p></div>';
            exit;
        }

        $tables = $this->manager->getTableList();
        if (!in_array('weathermap_maps', $tables)) {
            print '<div align="center" class="wm_warning"><p>';
            print __('The weathermap_maps table is missing completely from the database. Something went wrong with the installation process.');
            print '</p></div>';
        }

        $boostEnabled = $this->manager->application->getAppSetting('boost_rrd_update_enable', 'off');

        if ($boostEnabled == 'on') {
            $hasGlobalPollerOutput = $this->manager->getMapSettingByName(0, 'rrd_use_poller_output', false);

            if (!$hasGlobalPollerOutput) {
                print '<div align="center" class="wm_warning"><p>';
                print __(
                    'You are using the Boost plugin to update RRD files. Because this delays data being written to the files, it causes issues with Weathermap updates. You can resolve this by using Weathermap\'s \'poller_output\' support, which grabs data directly from the poller. <a href="%s">You can enable that globally by clicking here.</a>',
                    $this->makeURL(array("action" => "enable_poller_output"))
                );
                print '</p></div>';
            }
        }

        $lastStarted = $this->manager->application->getAppSetting('weathermap_last_started_file', true);
        $lastFinished = $this->manager->application->getAppSetting('weathermap_last_finished_file', true);
        $lastStartTime = intval($this->manager->application->getAppSetting('weathermap_last_start_time', true));
        $lastFinishTime = intval(
            $this->manager->application->getAppSetting(
                'weathermap_last_finish_time',
                true
            )
        );
        $pollerInterval = intval($this->manager->application->getAppSetting('poller_interval'));

        if (($lastFinishTime - $lastStartTime) > $pollerInterval) {
            if (($lastStarted != $lastFinished) && ($lastStarted != '')) {
                print '<div align="center" class="wm_warning"><p>';
                print __(
                    'Last time it ran, Weathermap did NOT complete its run. It failed during processing for \'%s\'',
                    $lastStarted
                );
                print __('This <b>may</b> have affected other plugins that run during the poller process.') . '</p><p>';
                print __('You should either disable this map, or fault-find. Possible causes include memory_limit issues. The log may have more information.');
                print '</p></div>';
            }
        }
    }

    protected function maplist()
    {
        \html_start_box(
            __('Weathermaps'),
            '100%',
            '',
            '3',
            'center',
            $this->makeURL(array("action" => "addmap_picker"))
        );

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
        $userlist = $this->manager->application->getUserList();
        $users[0] = __('Anyone');

        foreach ($userlist as $user) {
            $users[$user->id] = $user->username;
        }

        $i = 0;

        $maps = $this->manager->getMapsWithGroups();
        $hadWarnings = 0;

        \html_header($headers, 2);

        if (is_array($maps)) {
            $this->cactiRowStart($i);

            print '<td>' . __('ALL MAPS') . '</td><td>' . __('(special settings for all maps)') . '</td><td></td><td></td>';
            print '<td></td>';

            print '<td><a class="hyperLink" href="' . $this->makeURL(
                array(
                    "action" => "map_settings",
                    "id" => 0
                )
            ) . '">';
            $settingCount = $this->manager->getMapSettingCount(0, 0);
            if ($settingCount > 0) {
                print sprintf(__n('%d special', '%d specials', $settingCount), $settingCount);
            } else {
                print __('standard');
            }
            print '</a>';

            print '</td>';
            print '<td></td>';
            print '<td></td>';
            print '<td></td>';
            print '</tr>';

            $i++;

            foreach ($maps as $map) {
                $this->cactiRowStart($i);

                $editURL = $this->makeURL(
                    array("action" => "nothing", "mapname" => $map->configfile),
                    $this->editorURL
                );
                print '<td><a title="' . __('Click to start editor with this file') . '" href="' . $editURL . '">' . htmlspecialchars($map->configfile) . '</a>';
                print '</td>';

                print '<td>' . htmlspecialchars($map->titlecache) . '</td>';
                print '<td><a title="' . __('Click to change group') . '" class="hyperLink" href="' . $this->makeURL(
                    array(
                        "action" => "chgroup",
                        "id" => $map->id
                    )
                ) . '">' . htmlspecialchars($map->groupname) . '</a></td>';


                print "<td>";
                print sprintf("%.2gs", $map->runtime);
                if ($map->warncount > 0) {
                    $hadWarnings++;
                    print '<br><a href="../../utilities.php?tail_lines=500&message_type=2&action=view_logfile&filter=' . urlencode($map->configfile) . '" title="Check cacti.log for this map"><img border=0 src="cacti-resources/img/exclamation.png" title="' . $map->warncount . ' warnings last time this map was run. Check your logs.">' . $map->warncount . "</a>";
                }
                print "</td>";

                if ($map->active == 'on') {
                    print '<td class="wm_enabled"><a title="' . __('Click to Deactivate') . '" class="hyperLink" href="' . $this->makeURL(
                        array(
                            "action" => "deactivate_map",
                            "id" => $map->id
                        )
                    ) . '"><font color="green">' . __('Yes') . '</font></a>';
                } else {
                    print '<td class="wm_disabled"><a title="' . __('Click to Activate') . '" class="hyperLink" href="' . $this->makeURL(
                        array(
                            "action" => "activate_map",
                            "id" => $map->id
                        )
                    ) . '"><font color="red">' . __('No') . '</font></a>';
                }
                print '<td>';

                print '<a class="hyperLink" href="' . $this->makeURL(
                    array(
                        "action" => "map_settings",
                        "id" => $map->id
                    )
                ) . '">';
                $settingCount = $this->manager->getMapSettingCount($map->id);
                if ($settingCount > 0) {
                    print sprintf(__n('%s special', '%s specials', $settingCount), $settingCount);
                } else {
                    print __('standard');
                }
                print '</a>';
                print '</td>';

                print '<td>';

                print '<span class="remover fa fa-caret-up moveArrow" href="' . $this->makeURL(
                    array(
                        "action" => "move_map_up",
                        "id" => $map->id,
                        "order" => $map->sortorder
                    )
                ) . '" title="' . __('Move Map Up') . '"></span>';
                print '<span class="remover fa fa-caret-down moveArrow" href="' . $this->makeURL(
                    array(
                        "action" => "move_map_down",
                        "id" => $map->id,
                        "order" => $map->sortorder
                    )
                ) . '" title="' . __('Move Map Down') . '"></span>';
                print '</td>';

                print '<td>';

                $userlist = $this->manager->getMapAuthUsers($map->id);
                $mapusers = array();
                foreach ($userlist as $user) {
                    if (array_key_exists($user->userid, $users)) {
                        $mapusers[] = $users[$user->userid];
                    }
                }

                print '<a title="' . __('Click to edit permissions') . '" href="' . $this->makeURL(
                    array(
                        "action" => "perms_edit",
                        "id" => $map->id
                    )
                ) . '">';
                if (count($mapusers) == 0) {
                    print __('(no users)');
                } else {
                    print join(', ', $mapusers);
                }
                print '</a>';

                print '</td>';
                print '<td class="right">';

                print '<span class="remover fa fa-remove deleteMarker" href="' . $this->makeURL(
                    array(
                        "action" => "delete_map",
                        "id" => $map->id
                    )
                ) . '" title="' . __('Delete Map') . '"></span>';
                print '</td>';
                print '</tr>';
                $i++;
            }
        }

        if ($i == 0) {
            print '<tr><td colspan="4"><em>' . __('No Weathermaps Configured') . '</em></td></tr>';
        }

        \html_end_box();

        $lastStats = $this->manager->application->getAppSetting('weathermap_last_stats', '');

        if ($lastStats != '') {
            print '<div align="center">' . __('Last Completed Run: %s', $lastStats) . '</div>';
        }

        if ($hadWarnings > 0) {
            print '<div align="center" class="wm_warning">';
            print sprintf(
                __n(
                    '%s of your maps had warnings last time it ran. You can try to find these in your Cacti log file or by clicking on the warning sign next to that map (you might need to increase the log line count)',
                    '%s of your maps had warnings last time it ran. You can try to find these in your Cacti log file or by clicking on the warning sign next to that map (you might need to increase the log line count)',
                    $hadWarnings
                ),
                $hadWarnings
            );
            print '</div>';
        }

        print '<div class="break"></div>';
        print '<div align="center">';
        print '<input type="button" id="edit" value="' . __('Edit Groups') . '">';
        print '<input type="button" id="settings" value="' . __('Settings') . '">';

        print '</div>';
    }


    protected function previewConfig(
        $file
    ) {
        chdir($this->configPath);

        $pathParts = pathinfo($file);
        $fileDirectory = realpath($pathParts['dirname']);

        if ($fileDirectory != $this->configPath) {
            // someone is trying to read arbitrary files?
            // print '$file_dir != $weathermap_confdir';
            print '<h3>' . __('Path mismatch') . '</h3>';
        } else {
            \html_start_box(__('Preview of %s', $file), '100%', '', '3', 'center', '');

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
            \html_end_box();
        }
    }

    protected function addmapPicker(
        $showAllFiles = false
    ) {
        $loaded = array();
        $flags = array();
        $skipped = 0;

        // find out what maps are already in the database, so we can skip those
        $existingMaps = $this->manager->getMaps();
        if (is_array($existingMaps)) {
            foreach ($existingMaps as $map) {
                $loaded[] = $map->configfile;
            }
        }

        \html_start_box(__('Available Weathermap Configuration Files'), '100%', '', '1', 'center', '');

        if (is_dir($this->configPath)) {
            $dh = opendir($this->configPath);
            if ($dh) {
                $i = 0;
                $skipped = 0;
                \html_header(array('', '', __('Config File'), __('Title'), ''), 2);

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
                            if ($used && !$showAllFiles) {
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
                        $this->cactiRowStart($n);

                        print '<td><a class="hyperLink" href="' . $this->makeURL(
                            array(
                                "action" => "addmap",
                                "file" => $file
                            )
                        ) . '" title="' . __('Add the configuration file') . '">' . __('Add') . '</a></td>';
                        print '<td><a class="hyperLink" href="' . $this->makeURL(
                            array(
                                "action" => "viewconfig",
                                "file" => $file
                            )
                        ) . '" title="' . __('View the configuration file in a new window') . '" target="_blank">' . __('View') . '</a></td>';
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
                    print '<tr><td>' . __(
                        '(%s files weren\'t shown because they are already in the database.',
                        $skipped
                    ) . '</td></tr>';
                }
            } else {
                print '<tr><td>' . __(
                    'Can\'t open %s to read - you should set it to be readable by the webserver.',
                    $this->configPath
                ) . '</td></tr>';
            }
        } else {
            print '<tr><td>' . __(
                'There is no directory named %s - you will need to create it, and set it to be readable by the webserver. If you want to upload configuration files from inside Cacti, then it should be <i>writable</i> by the webserver too.',
                $this->configPath
            ) . '</td></tr>';
        }

        \html_end_box();

        if ($skipped > 0) {
            print '<p align="center">' . __(
                'Some files are not shown because they have already been added. You can <a href="%s">show these files too</a>, if you need to.',
                $this->makeURL(array("action" => "addmap_picker", "show_all" => "1"))
            ) . '</p>';
        }

        if ($showAllFiles) {
            print '<p align="center">' . __(
                'Some files are shown even though they have already been added. You can <a href="%s">hide those files too</a>, if you need to.',
                $this->makeURL(array("action" => "addmap_picker"))
            ) . '</p>';
        }
    }

    public function permissionsList(
        $id
    ) {
        $map = $this->manager->getMap($id);
        $title = $map->titlecache;

        $users = $this->manager->application->getUserList(true);
        $auth = $this->manager->getMapAuth($id);

        $mapuserids = array();

        // build an array of userids that are allowed to see this map (and that actually exist)
        foreach ($auth as $user) {
            if (isset($users[$user->userid])) {
                $mapuserids[] = $user->userid;
            }
        }

        // now build the list of users that exist but aren't currently allowed (for the picklist)
        $candidateUsers = array();
        foreach ($users as $uid => $user) {
            if (!in_array($uid, $mapuserids)) {
                $candidateUsers [] = $user;
            }
        }

        \html_start_box(
            __('Edit permissions for Weathermap %s: %s', $id, $title),
            '100%',
            '',
            '2',
            'center',
            ''
        );

        \html_header(array(__('Username'), ''));

        $n = 0;
        foreach ($mapuserids as $user) {
            $this->cactiRowStart($n);
            print '<td>' . htmlspecialchars($users[$user]->username) . '</td>';

            print '<td>';
            print '<a href="' . $this->makeURL(
                array(
                        "action" => "perms_delete_user",
                        "mapid" => $id,
                        "userid" => $user,
                        "header" => "false"
                    )
            ) . '">';
            print '<img src="../../images/delete_icon.gif" width="10" height="10" border="0" alt="' . __('Remove permissions for this user to see this map') . '">';
            print '</a></td>';

            print '</tr>';
            $n++;
        }

        if (count($mapuserids) == 0) {
            print '<tr><td><em>' . __('nobody can see this map') . '</em></td></tr>';
        }

        \html_end_box();

        \html_start_box('', '100%', '', '3', 'center', '');

        print '<tr>';

        if (count($candidateUsers) == 0) {
            print '<td><em>' . __('There aren\'t any users left to add!') . '</em></td></tr>';
        } else {
            print '<td><form action="">' . __('Allow') . ' <input type="hidden" name="action" value="perms_add_user"><input type="hidden" name="mapid" value="' . $id . '"><select name="userid">';
            foreach ($candidateUsers as $user) {
                printf('<option value="%s">%s</option>', $user->id, $user->username);
            }

            print '</select> ' . __('to see this map') . ' <input type="submit" value="' . __('Update') . '"></form></td>';
            print '</tr>';
        }

        \html_end_box();
    }


    public function mapSettings(
        $id
    ) {
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
            $this->mapReadOnlySettings(0, __('Global Settings'));
        } else {
            $map = $this->manager->getMap($id);
            $group = $this->manager->getGroup($map->group_id);

            $title = __('Edit per-map settings for Weathermap %s: %s', $id, $map->titlecache);
            $nonemsg = __('There are no per-map settings for this map yet. You can add some by clicking Add up in the top-right.');
            $type = 'map';
            $settingrows = $this->manager->getMapSettings(intval($id));

            print '<p>' . __('This map is also affected by the following GLOBAL and GROUP settings (group overrides global, map overrides group, but BOTH override SET commands within the map config file):') . '</p>';

            $this->mapReadOnlySettings(0, __('Global Settings'));
            $this->mapReadOnlySettings(
                -$map->group_id,
                __('Group Settings (%s)', htmlspecialchars($group->name))
            );
        }

        \html_start_box(
            $title,
            '100%',
            '',
            '2',
            'center',
            'weathermap-cacti10-plugin-mgmt.php?action=map_settings_form&mapid=' . intval($id)
        );
        \html_header(array(__('Actions'), __('Name'), __('Value')), 2);

        $n = 0;
        if (is_array($settingrows)) {
            if (count($settingrows) > 0) {
                foreach ($settingrows as $setting) {
                    $this->cactiRowStart($n);

                    print '<td style="width:4%"><a href="' . $this->makeURL(
                        array(
                            "action" => "map_settings_form",
                            "mapid" => $id,
                            "id" => intval($setting->id)
                        )
                    ) . '"><img src="../../images/graph_properties.gif" width="16" height="16" border="0" alt="' . __('Edit this definition') . '">' . __('Edit') . '</a></td>';
                    print "<td>" . htmlspecialchars($setting->optname) . "</td>";
                    print "<td>" . htmlspecialchars($setting->optvalue) . "</td>";
                    print '<td><a class="hyperLink" href="' . $this->makeURL(
                        array(
                            "action" => "map_settings_delete",
                            "mapid" => $id,
                            "header" => "false",
                            "id" => intval($setting->id)
                        )
                    ) . '"><img src="../../images/delete_icon_large.gif" width="12" height="12" border="0" alt="' . __('Remove this definition from this map') . '"></a></td>';
                    print "</tr>";
                    $n++;
                }
            } else {
                print '<tr>';
                print "<td colspan=2><em>$nonemsg</em></td>";
                print '</tr>';
            }
        }

        \html_end_box();

        print '<div align=center>';

        if ($type == 'group') {
            print '<a class="hyperLink" href="weathermap-cacti10-plugin-mgmt.php?action=groupadmin">' . __('Back to Group Admin') . '</a>';
        }

        if ($type == 'global') {
            print '<a class="hyperLink" href="weathermap-cacti10-plugin-mgmt.php?action=">' . __('Back to Map Admin') . '</a>';
        }

        print '</div>';
    }

    public function mapReadOnlySettings(
        $id,
        $title = ''
    ) {
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

        \html_start_box($title, '100%', '', '2', 'center', '');
        \html_header(array(__('Name'), __('Value')));

        $n = 0;
        if (count($settings) > 0) {
            foreach ($settings as $setting) {
                $this->cactiRowStart($n);
                print '<td>' . htmlspecialchars($setting->optname) . '</td>';
                print '<td>' . htmlspecialchars($setting->optvalue) . '</td>';
                print '</tr>';
                $n++;
            }
        } else {
            $this->cactiRowStart($n);
            print '<td colspan=4><em>' . __('No Settings') . '</em></td>';
            print '</tr>';
        }

        \html_end_box();
    }

    public function cactiFooter()
    {
        print "OVERRIDE ME";
    }

    public function cactiHeader()
    {
        print "OVERRIDE ME";
    }

    public function cactiRowStart(
        $i
    ) {
    }

    public function footerLinks()
    {
        $html = "";

        $html .= '<a class="linkOverDark" href="docs/index.html">Local Documentation</a>';
        $html .= ' -- <a class="linkOverDark" href="http://www.network-weathermap.com/">Weathermap Website</a>';
        $html .= ' -- <a class="linkOverDark" href="' . $this->makeURL(
            array(),
            $this->editorURL
        ) . '">Weathermap Editor</a>';
        $html .= " -- This is version " . WEATHERMAP_VERSION;

        print "<br />";
        \html_start_box($html, '78%', '', '4', 'center', '');
        \html_end_box();
    }
}
