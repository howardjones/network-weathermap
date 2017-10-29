<?php

function weathermap_page_head()
{
    if (preg_match('/plugins\/weathermap\//', $_SERVER['REQUEST_URI'], $matches)) {
        print '<LINK rel="stylesheet" type="text/css" media="screen" href="weathermap-cacti-plugin.css">';
    }
}

function weathermap_page_title($t)
{
    if (preg_match('/plugins\/weathermap\//', $_SERVER['REQUEST_URI'], $matches)) {
        $t .= " - Weathermap";

        if (preg_match(
            '/plugins\/weathermap\/weathermap-cacti\d\d-plugin.php\?action=viewmap&id=([^&]+)/',
            $_SERVER['REQUEST_URI'],
            $matches
        )) {
            $mapid = $matches[1];
            $pdo = weathermap_get_pdo();
            $cactiInterface = new Weathermap\Integrations\Cacti\CactiApplicationInterface();
            $manager = new Weathermap\Integrations\MapManager($pdo, "", $cactiInterface);

            // TODO: Should numeric ID ever happen?
            if (preg_match('/^\d+$/', $mapid)) {
                $title = $manager->getMapTitle($mapid);
            } else {
                $title = $manager->getMapTitleByHash($mapid);
            }
            if (isset($title)) {
                $t .= " - $title";
            }
        }
    }
    return $t;
}

function weathermap_top_graph_refresh($refresh)
{
    if (basename($_SERVER["PHP_SELF"]) != "weathermap-cacti88-plugin.php") {
        return $refresh;
    }

    // if we're cycling maps, then we want to handle reloads ourselves, thanks
    if (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'viewmapcycle') {
        return 86400;
    }
    return $refresh;
}

function weathermap_config_settings()
{
    global $tabs, $settings;
    $tabs["misc"] = "Misc";

    $temp = array(
        "weathermap_header" => array(
            "friendly_name" => "Network Weathermap",
            "method" => "spacer",
        ),
        "weathermap_pagestyle" => array(
            "friendly_name" => "Page style",
            "description" => "How to display multiple maps.",
            "method" => "drop_array",
            "array" => array(0 => "Thumbnail Overview", 1 => "Full Images", 2 => "Show Only First")
        ),
        "weathermap_thumbsize" => array(
            "friendly_name" => "Thumbnail Maximum Size",
            "description" => "The maximum width or height for thumbnails in thumbnail view, in pixels. Takes effect after the next poller run.",
            "method" => "textbox",
            "max_length" => 5,
        ),
        "weathermap_cycle_refresh" => array(
            "friendly_name" => "Refresh Time",
            "description" => "How often to refresh the page in Cycle mode. Automatic makes all available maps fit into 5 minutes.",
            "method" => "drop_array",
            "array" => array(
                0 => "Automatic",
                5 => "5 Seconds",
                15 => '15 Seconds',
                30 => '30 Seconds',
                60 => '1 Minute',
                120 => '2 Minutes',
                300 => '5 Minutes',
            )
        ),
        "weathermap_output_format" => array(
            "friendly_name" => "Output Format",
            "description" => "What format do you prefer for the generated map images and thumbnails?",
            "method" => "drop_array",
            "array" => array(
                'png' => "PNG (default)",
                'jpg' => "JPEG",
                'gif' => 'GIF'
            )
        ),
        "weathermap_render_period" => array(
            "friendly_name" => "Map Rendering Interval",
            "description" => "How often do you want Weathermap to recalculate it's maps? You should not touch this unless you know what you are doing! It is mainly needed for people with non-standard polling setups.",
            "method" => "drop_array",
            "array" => array(
                -1 => "Never (manual updates)",
                0 => "Every Poller Cycle (default)",
                2 => 'Every 2 Poller Cycles',
                3 => 'Every 3 Poller Cycles',
                4 => 'Every 4 Poller Cycles',
                5 => 'Every 5 Poller Cycles',
                10 => 'Every 10 Poller Cycles',
                12 => 'Every 12 Poller Cycles',
                24 => 'Every 24 Poller Cycles',
                36 => 'Every 36 Poller Cycles',
                48 => 'Every 48 Poller Cycles',
                72 => 'Every 72 Poller Cycles',
                288 => 'Every 288 Poller Cycles',
            ),
        ),

        "weathermap_all_tab" => array(
            "friendly_name" => "Show 'All' Tab",
            "description" => "When using groups, add an 'All Maps' tab to the tab bar.",
            "method" => "drop_array",
            "array" => array(0 => "No (default)", 1 => "Yes")
        ),
        "weathermap_map_selector" => array(
            "friendly_name" => "Show Map Selector",
            "description" => "Show a combo-box map selector on the full-screen map view.",
            "method" => "drop_array",
            "array" => array(0 => "No", 1 => "Yes (default)")
        ),
        "weathermap_quiet_logging" => array(
            "friendly_name" => "Quiet Logging",
            "description" => "By default, even in LOW level logging, Weathermap logs normal activity. This makes it REALLY log only errors in LOW mode.",
            "method" => "drop_array",
            "array" => array(0 => "Chatty (default)", 1 => "Quiet")
        )
    );
    if (isset($settings["misc"])) {
        $settings["misc"] = array_merge($settings["misc"], $temp);
    } else {
        $settings["misc"] = $temp;
    }
}

function weathermap_setup_table()
{
    $dbversion = read_config_option('weathermap_db_version');

    $myversioninfo = weathermap_version();
    $myversion = $myversioninfo['version'];

    $pdo = weathermap_get_pdo();
    $cactiInterface = new Weathermap\Integrations\Cacti\CactiApplicationInterface();
    $manager = new Weathermap\Integrations\MapManager($pdo, "", $cactiInterface);

    if (($dbversion == '') || (preg_match('/dev$/', $myversion)) || ($dbversion != $myversion)) {
        $manager->initializeDatabase();
        $manager->initializeAppSettings();
    }
}

function weathermap_config_arrays()
{
    global $menu;

    if (function_exists('api_plugin_register_realm')) {
        api_plugin_register_realm('weathermap', 'weathermap-cacti88-plugin.php', 'Plugin -> Weathermap: View', 1);
        api_plugin_register_realm(
            'weathermap',
            'weathermap-cacti88-plugin-mgmt.php',
            'Plugin -> Weathermap: Configure/Manage',
            1
        );
        api_plugin_register_realm(
            'weathermap',
            'weathermap-cacti88-plugin-editor.php',
            'Plugin -> Weathermap: Edit Maps',
            1
        );
    }

    $weathermapMenu = array(
        'plugins/weathermap/weathermap-cacti88-plugin-mgmt.php' => "Weathermaps",
        'plugins/weathermap/weathermap-cacti88-plugin-mgmt-groups.php' => "Groups"
    );

    $menu["Management"]['plugins/weathermap/weathermap-cacti88-plugin-mgmt.php'] = $weathermapMenu;
}

function weathermap_show_tab()
{
    global $config, $user_auth_realm_filenames;
    $realmID = 0;

    if (isset($user_auth_realm_filenames[basename('weathermap-cacti88-plugin.php')])) {
        $realmID = $user_auth_realm_filenames[basename('weathermap-cacti88-plugin.php')];
    }

    $tabstyle = intval(read_config_option("superlinks_tabstyle"));
    $userid = (isset($_SESSION["sess_user_id"]) ? intval($_SESSION["sess_user_id"]) : 1);

    $pdo = weathermap_get_pdo();
    $stmt = $pdo->prepare("select user_auth_realm.realm_id from user_auth_realm where user_auth_realm.user_id=? and user_auth_realm.realm_id=?");
    $stmt->execute(array($userid, $realmID));
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ((count($result) > 0) || (empty($realmID))) {
        if ($tabstyle > 0) {
            $prefix = "s_";
        } else {
            $prefix = "";
        }
        $tabName = $prefix . "tab_weathermap.gif";
        $weathermapBaseURL = $config['url_path'] . 'plugins/weathermap';
        $weathermapURL = $weathermapBaseURL . '/weathermap-cacti88-plugin.php';

        if (preg_match('/plugins\/weathermap\/weathermap-cacti88-plugin.php/', $_SERVER['REQUEST_URI'], $matches)) {
            $tabName = $prefix . "tab_weathermap_red.gif";
        }
        $tabURL = $weathermapBaseURL . "/images/" . $tabName;

        printf(
            '<a href="%s"><img src="%s" alt="Weathermap" align="absmiddle" border="0" /></a>',
            $weathermapURL,
            $tabURL
        );
    }

    weathermap_setup_table();
}

function weathermap_draw_navigation_text($nav)
{
    $nav["weathermap-cacti88-plugin.php:"] = array(
        "title" => "Weathermap",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti88-plugin.php",
        "level" => "1"
    );
    $nav["weathermap-cacti88-plugin.php:viewmap"] = array(
        "title" => "Weathermap",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti88-plugin.php",
        "level" => "1"
    );
    $nav["weathermap-cacti88-plugin.php:liveview"] = array(
        "title" => "Weathermap",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti88-plugin.php",
        "level" => "1"
    );
    $nav["weathermap-cacti88-plugin.php:liveviewimage"] = array(
        "title" => "Weathermap",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti88-plugin.php",
        "level" => "1"
    );
    $nav["weathermap-cacti88-plugin.php:viewmapcycle"] = array(
        "title" => "Weathermap",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti88-plugin.php",
        "level" => "1"
    );
    $nav["weathermap-cacti88-plugin.php:mrss"] = array(
        "title" => "Weathermap",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti88-plugin.php",
        "level" => "1"
    );
    $nav["weathermap-cacti88-plugin.php:viewimage"] = array(
        "title" => "Weathermap",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti88-plugin.php",
        "level" => "1"
    );
    $nav["weathermap-cacti88-plugin.php:viewthumb"] = array(
        "title" => "Weathermap",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti88-plugin.php",
        "level" => "1"
    );

    $nav["weathermap-cacti88-plugin-mgmt.php:"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti88-plugin-mgmt.php",
        "level" => "1"
    );
    //   $nav["weathermap-cacti88-plugin-mgmt.php:addmap_picker"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti88-plugin-mgmt.php", "level" => "1");
    $nav["weathermap-cacti88-plugin-mgmt.php:viewconfig"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti88-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti88-plugin-mgmt.php:addmap"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti88-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti88-plugin-mgmt.php:editmap"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti88-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti88-plugin-mgmt.php:editor"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti88-plugin-mgmt.php",
        "level" => "1"
    );

    //  "graphs.php:graph_edit" => array("title" => "(Edit)", "mapping" => "index.php:,graphs.php:", "url" => "", "level" => "2"),

    $nav["weathermap-cacti88-plugin-mgmt.php:perms_edit"] = array(
        "title" => "Edit Permissions",
        "mapping" => "index.php:,weathermap-cacti88-plugin-mgmt.php:",
        "url" => "",
        "level" => "2"
    );
    $nav["weathermap-cacti88-plugin-mgmt.php:addmap_picker"] = array(
        "title" => "Add Map",
        "mapping" => "index.php:,weathermap-cacti88-plugin-mgmt.php:",
        "url" => "",
        "level" => "2"
    );
    $nav["weathermap-cacti88-plugin-mgmt.php:map_settings"] = array(
        "title" => "Map Settings",
        "mapping" => "index.php:,weathermap-cacti88-plugin-mgmt.php:",
        "url" => "",
        "level" => "2"
    );
    $nav["weathermap-cacti88-plugin-mgmt.php:map_settings_form"] = array(
        "title" => "Map Settings",
        "mapping" => "index.php:,weathermap-cacti88-plugin-mgmt.php:",
        "url" => "",
        "level" => "2"
    );
    $nav["weathermap-cacti88-plugin-mgmt.php:map_settings_delete"] = array(
        "title" => "Map Settings",
        "mapping" => "index.php:,weathermap-cacti88-plugin-mgmt.php:",
        "url" => "",
        "level" => "2"
    );
    $nav["weathermap-cacti88-plugin-mgmt.php:map_settings_update"] = array(
        "title" => "Map Settings",
        "mapping" => "index.php:,weathermap-cacti88-plugin-mgmt.php:",
        "url" => "",
        "level" => "2"
    );
    $nav["weathermap-cacti88-plugin-mgmt.php:map_settings_add"] = array(
        "title" => "Map Settings",
        "mapping" => "index.php:,weathermap-cacti88-plugin-mgmt.php:",
        "url" => "",
        "level" => "2"
    );

    // $nav["weathermap-cacti88-plugin-mgmt.php:perms_edit"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti88-plugin-mgmt.php", "level" => "1");
    $nav["weathermap-cacti88-plugin-mgmt.php:perms_add_user"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti88-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti88-plugin-mgmt.php:perms_delete_user"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti88-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti88-plugin-mgmt.php:delete_map"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti88-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti88-plugin-mgmt.php:move_map_down"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti88-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti88-plugin-mgmt.php:move_map_up"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti88-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti88-plugin-mgmt.php:move_group_down"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti88-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti88-plugin-mgmt.php:move_group_up"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti88-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti88-plugin-mgmt.php:group_form"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti88-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti88-plugin-mgmt.php:group_update"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti88-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti88-plugin-mgmt.php:activate_map"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti88-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti88-plugin-mgmt.php:deactivate_map"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti88-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti88-plugin-mgmt.php:rebuildnow"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti88-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti88-plugin-mgmt.php:rebuildnow2"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti88-plugin-mgmt.php",
        "level" => "1"
    );

    $nav["weathermap-cacti88-plugin-mgmt.php:chgroup"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti88-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti88-plugin-mgmt.php:chgroup_update"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti88-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti88-plugin-mgmt.php:groupadmin"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti88-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti88-plugin-mgmt.php:groupadmin_delete"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti88-plugin-mgmt.php",
        "level" => "1"
    );

    return $nav;
}
