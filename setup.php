<?php
/*******************************************************************************
 *
 * Author ......... Howard Jones
 * Contact ........ howie@thingy.com
 * Home Site ...... http://wotsit.thingy.com/haj/
 * Program ........ Network Weathermap for Cacti
 * Version ........ See code below
 * Purpose ........ Network Usage Overview
 *******************************************************************************/

require_once dirname(__FILE__) . "/lib/database.php";

function plugin_weathermap_install()
{
    api_plugin_register_hook('weathermap', 'config_arrays', 'weathermap_config_arrays', 'setup.php');
    api_plugin_register_hook('weathermap', 'config_settings', 'weathermap_config_settings', 'setup.php');

    api_plugin_register_hook('weathermap', 'top_header_tabs', 'weathermap_show_tab', 'setup.php');
    api_plugin_register_hook('weathermap', 'top_graph_header_tabs', 'weathermap_show_tab', 'setup.php');
    api_plugin_register_hook('weathermap', 'draw_navigation_text', 'weathermap_draw_navigation_text', 'setup.php');

    api_plugin_register_hook('weathermap', 'top_graph_refresh', 'weathermap_top_graph_refresh', 'setup.php');
    api_plugin_register_hook('weathermap', 'page_title', 'weathermap_page_title', 'setup.php');
    api_plugin_register_hook('weathermap', 'page_head', 'weathermap_page_head', 'setup.php');

    api_plugin_register_hook('weathermap', 'poller_top', 'weathermap_poller_top', 'setup.php');
    api_plugin_register_hook('weathermap', 'poller_output', 'weathermap_poller_output', 'setup.php');
    api_plugin_register_hook('weathermap', 'poller_bottom', 'weathermap_poller_bottom', 'setup.php');

    weathermap_setup_table();
}

function plugin_weathermap_uninstall()
{
    // This function doesn't seem to ever be called, in Cacti 0.8.8b
    // on the assumption that it will one day work, clear the stored version number from the settings
    // so that an uninstall/reinstall on the plugin would force the db schema to be checked
    $pdo = weathermap_get_pdo();
    $pdo->query("REPLACE INTO settings VALUES('weathermap_version','')");
}

function plugin_weathermap_version()
{
    return array(
        'name' => 'weathermap',
        'version' => '0.98',
        'longname' => 'PHP Network Weathermap',
        'author' => 'Howard Jones',
        'homepage' => 'http://www.network-weathermap.com/',
        'webpage' => 'http://www.network-weathermap.com/',
        'email' => 'howie@thingy.com',
        'url' => 'http://www.network-weathermap.com/versions.php'
    );
}

/* somehow this function is still required in PA 3.x, even though it checks for plugin_weathermap_version() */
function weathermap_version()
{
    return plugin_weathermap_version();
}

function plugin_weathermap_check_config()
{
    return true;
}

function plugin_weathermap_upgrade()
{
    return false;
}

function plugin_init_weathermap()
{
    global $plugin_hooks;
    $plugin_hooks['top_header_tabs']['weathermap'] = 'weathermap_show_tab';
    $plugin_hooks['top_graph_header_tabs']['weathermap'] = 'weathermap_show_tab';
    $plugin_hooks['config_arrays']['weathermap'] = 'weathermap_config_arrays';
    $plugin_hooks['draw_navigation_text']['weathermap'] = 'weathermap_draw_navigation_text';
    $plugin_hooks['config_settings']['weathermap'] = 'weathermap_config_settings';

    $plugin_hooks['poller_bottom']['weathermap'] = 'weathermap_poller_bottom';
    $plugin_hooks['poller_top']['weathermap'] = 'weathermap_poller_top';
    $plugin_hooks['poller_output']['weathermap'] = 'weathermap_poller_output';

    $plugin_hooks['top_graph_refresh']['weathermap'] = 'weathermap_top_graph_refresh';
    $plugin_hooks['page_title']['weathermap'] = 'weathermap_page_title';
    $plugin_hooks['page_head']['weathermap'] = 'weathermap_page_head';
}

// figure out if this poller run is hitting the 'cron' entry for any maps.
function weathermap_poller_top()
{
    global $weathermap_poller_start_time;

    $n = time();

    // round to the nearest minute, since that's all we need for the crontab-style stuff
    $weathermap_poller_start_time = $n - ($n % 60);

}

function weathermap_page_head()
{
    global $config;

    // Add in a Media RSS link on the thumbnail view
    // - format isn't quite right, so it's disabled for now.
    //	if(preg_match('/plugins\/weathermap\/weathermap\-cacti\-plugin\.php/',$_SERVER['REQUEST_URI'] ,$matches))
    //	{
    //		print '<link id="media-rss" title="My Network Weathermaps" rel="alternate" href="?action=mrss" type="application/rss+xml">';
    //	}
    if (preg_match('/plugins\/weathermap\//', $_SERVER['REQUEST_URI'], $matches)) {
        print '<LINK rel="stylesheet" type="text/css" media="screen" href="weathermap-cacti-plugin.css">';
    }
}

function weathermap_page_title($t)
{
    if (preg_match('/plugins\/weathermap\//', $_SERVER['REQUEST_URI'], $matches)) {
        $t .= " - Weathermap";

        if (preg_match('/plugins\/weathermap\/weathermap-cacti-plugin.php\?action=viewmap&id=([^&]+)/',
            $_SERVER['REQUEST_URI'], $matches)) {
            $mapid = $matches[1];
            $pdo = weathermap_get_pdo();
            if (preg_match('/^\d+$/', $mapid)) {
                $statement = $pdo->prepare("SELECT titlecache FROM weathermap_maps WHERE ID=?");
                $statement->execute(array(intval($mapid)));
                $title = $statement->fetchColumn();
            } else {
                $statement = $pdo->prepare("SELECT titlecache FROM weathermap_maps WHERE filehash=?");
                $statement->execute(array($mapid));
                $title = $statement->fetchColumn();
            }
            if (isset($title)) {
                $t .= " - $title";
            }
        }

    }
    return ($t);
}


function weathermap_top_graph_refresh($refresh)
{
    if (basename($_SERVER["PHP_SELF"]) != "weathermap-cacti-plugin.php") {
        return $refresh;
    }

    // if we're cycling maps, then we want to handle reloads ourselves, thanks
    if (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'viewmapcycle') {
        return (86400);
    }
    return ($refresh);
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
    global $config, $database_default;
    global $WEATHERMAP_VERSION;
    include_once($config["library_path"] . DIRECTORY_SEPARATOR . "database.php");

    $dbversion = read_config_option("weathermap_db_version");

    $myversioninfo = weathermap_version();
    $myversion = $myversioninfo['version'];

    $pdo = weathermap_get_pdo();

    // only bother with all this if it's a new install, a new version, or we're in a development version
    // - saves a handful of db hits per request!
    if (($dbversion == "") || (preg_match("/dev$/", $myversion)) || ($dbversion != $myversion)) {

        $statement = $pdo->query("show tables");
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);

        $tables = array();
        foreach ($result as $index => $arr) {
            foreach ($arr as $t) {
                $tables[] = $t;
            }
        }

        $database_updates = array();
        $database_updates[] = "UPDATE weathermap_maps SET sortorder=id WHERE sortorder IS NULL;";

        if (!in_array('weathermap_maps', $tables)) {
            $database_updates[] = "CREATE TABLE weathermap_maps (
				id INT(11) NOT NULL AUTO_INCREMENT,
				sortorder INT(11) NOT NULL DEFAULT 0,
				group_id INT(11) NOT NULL DEFAULT 1,
				active SET('on','off') NOT NULL DEFAULT 'on',
				configfile TEXT NOT NULL,
				imagefile TEXT NOT NULL,
				htmlfile TEXT NOT NULL,
				titlecache TEXT NOT NULL,
				filehash VARCHAR (40) NOT NULL DEFAULT '',
				warncount INT(11) NOT NULL DEFAULT 0,
				config TEXT NOT NULL,
				thumb_width INT(11) NOT NULL DEFAULT 0,
				thumb_height INT(11) NOT NULL DEFAULT 0,
				schedule VARCHAR(32) NOT NULL DEFAULT '*',
				archiving SET('on','off') NOT NULL DEFAULT 'off',
				PRIMARY KEY  (id)
			) ENGINE=MyISAM;";
        } else {
            # Check that all the table columns exist for weathermap_maps
            # There have been a number of changes over versions.
            $statement = $pdo->query("show columns from weathermap_maps from " . $database_default);
            $result = $statement->fetchAll(PDO::FETCH_ASSOC);

            $field_changes = array(
                'sortorder' => false,
                'filehash' => false,
                'warncount' => false,
                'config' => false,
                'thumb_width' => false,
                'group_id' => false
            );

            foreach ($result as $row) {
                if (array_key_exists($row['Field'], $field_changes)) {
                    $field_changes[$row['Field']] = true;
                }
            }

            if (!$field_changes['sortorder']) {
                $database_updates[] = "ALTER TABLE weathermap_maps ADD sortorder INT(11) NOT NULL DEFAULT 0 AFTER id";
            }
            if (!$field_changes['filehash']) {
                $database_updates[] = "ALTER TABLE weathermap_maps ADD filehash VARCHAR(40) NOT NULL DEFAULT '' AFTER titlecache";
            }
            if (!$field_changes['warncount']) {
                $database_updates[] = "ALTER TABLE weathermap_maps ADD warncount INT(11) NOT NULL DEFAULT 0 AFTER filehash";
            }
            if (!$field_changes['config']) {
                $database_updates[] = "ALTER TABLE weathermap_maps ADD config TEXT NOT NULL  DEFAULT '' AFTER warncount";
            }
            if (!$field_changes['thumb_width']) {
                $database_updates[] = "ALTER TABLE weathermap_maps ADD thumb_width INT(11) NOT NULL DEFAULT 0 AFTER config";
                $database_updates[] = "ALTER TABLE weathermap_maps ADD thumb_height INT(11) NOT NULL DEFAULT 0 AFTER thumb_width";
                $database_updates[] = "ALTER TABLE weathermap_maps ADD schedule VARCHAR(32) NOT NULL DEFAULT '*' AFTER thumb_height";
                $database_updates[] = "ALTER TABLE weathermap_maps ADD archiving SET('on','off') NOT NULL DEFAULT 'off' AFTER schedule";
            }
            if (!$field_changes['group_id']) {
                $database_updates[] = "ALTER TABLE weathermap_maps ADD group_id INT(11) NOT NULL DEFAULT 1 AFTER sortorder";
                $database_updates[] = "ALTER TABLE `weathermap_settings` ADD `groupid` INT NOT NULL DEFAULT '0' AFTER `mapid`";
            }
        }

        $database_updates[] = "UPDATE weathermap_maps SET filehash=LEFT(MD5(concat(id,configfile,rand())),20) WHERE filehash = '';";

        if (!in_array('weathermap_auth', $tables)) {
            $database_updates[] = "CREATE TABLE weathermap_auth (
				userid MEDIUMINT(9) NOT NULL DEFAULT '0',
				mapid INT(11) NOT NULL DEFAULT '0'
			) ENGINE=MyISAM;";
        }


        if (!in_array('weathermap_groups', $tables)) {
            $database_updates[] = "CREATE TABLE  weathermap_groups (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`name` VARCHAR( 128 ) NOT NULL DEFAULT '',
				`sortorder` INT(11) NOT NULL DEFAULT 0,
				PRIMARY KEY (id)
				) ENGINE=MyISAM;";
            $database_updates[] = "INSERT INTO weathermap_groups (id,name,sortorder) VALUES (1,'Weathermaps',1)";
        }

        if (!in_array('weathermap_settings', $tables)) {
            $database_updates[] = "CREATE TABLE weathermap_settings (
				id INT(11) NOT NULL AUTO_INCREMENT,
				mapid INT(11) NOT NULL DEFAULT '0',
				groupid INT(11) NOT NULL DEFAULT '0',
				optname VARCHAR(128) NOT NULL DEFAULT '',
				optvalue VARCHAR(128) NOT NULL DEFAULT '',
				PRIMARY KEY  (id)
			) ENGINE=MyISAM;";
        }

        if (!in_array('weathermap_data', $tables)) {
            $database_updates[] = "CREATE TABLE IF NOT EXISTS weathermap_data (id INT(11) NOT NULL AUTO_INCREMENT,
				rrdfile VARCHAR(255) NOT NULL,data_source_name VARCHAR(19) NOT NULL,
				  last_time INT(11) NOT NULL,last_value VARCHAR(255) NOT NULL,
				last_calc VARCHAR(255) NOT NULL, sequence INT(11) NOT NULL, local_data_id INT(11) NOT NULL DEFAULT 0, PRIMARY KEY  (id), KEY rrdfile (rrdfile),
				  KEY local_data_id (local_data_id), KEY data_source_name (data_source_name) ) ENGINE=MyISAM";
        } else {
            $found_ldi = false;

            $statement = $pdo->query("show columns from weathermap_data from " . $database_default);
            $result = $statement->fetchAll(PDO::FETCH_ASSOC);

            foreach ($result as $row) {
                if ($row['Field'] == 'local_data_id') {
                    $found_ldi = true;
                }
            }
            if (!$found_ldi) {
                $database_updates[] = "ALTER TABLE weathermap_data ADD local_data_id INT(11) NOT NULL DEFAULT 0 AFTER sequence";
                $database_updates[] = "ALTER TABLE weathermap_data ADD INDEX ( `local_data_id` )";
                # if there is existing data without a local_data_id, ditch it
                $database_updates[] = "DELETE FROM weathermap_data";
            }
        }

        // create the settings entries, if necessary

        $pagestyle = read_config_option("weathermap_pagestyle");
        if ($pagestyle == '' or $pagestyle < 0 or $pagestyle > 2) {
            $database_updates[] = "REPLACE INTO settings VALUES('weathermap_pagestyle',0)";
        }

        $cycledelay = read_config_option("weathermap_cycle_refresh");
        if ($cycledelay == '' or intval($cycledelay < 0)) {
            $database_updates[] = "REPLACE INTO settings VALUES('weathermap_cycle_refresh',0)";
        }

        $renderperiod = read_config_option("weathermap_render_period");
        if ($renderperiod == '' or intval($renderperiod < -1)) {
            $database_updates[] = "REPLACE INTO settings VALUES('weathermap_render_period',0)";
        }

        $quietlogging = read_config_option("weathermap_quiet_logging");
        if ($quietlogging == '' or intval($quietlogging < -1)) {
            $database_updates[] = "REPLACE INTO settings VALUES('weathermap_quiet_logging',0)";
        }

        $rendercounter = read_config_option("weathermap_render_counter");
        if ($rendercounter == '' or intval($rendercounter < 0)) {
            $database_updates[] = "REPLACE INTO settings VALUES('weathermap_render_counter',0)";
        }

        $outputformat = read_config_option("weathermap_output_format");
        if ($outputformat == '') {
            $database_updates[] = "REPLACE INTO settings VALUES('weathermap_output_format','png')";
        }

        $tsize = read_config_option("weathermap_thumbsize");
        if ($tsize == '' or $tsize < 1) {
            $database_updates[] = "REPLACE INTO settings VALUES('weathermap_thumbsize',250)";
        }

        $ms = read_config_option("weathermap_map_selector");
        if ($ms == '' or intval($ms) < 0 or intval($ms) > 1) {
            $database_updates[] = "REPLACE INTO settings VALUES('weathermap_map_selector',1)";
        }

        $at = read_config_option("weathermap_all_tab");
        if ($at == '' or intval($at) < 0 or intval($at) > 1) {
            $database_updates[] = "REPLACE INTO settings VALUES('weathermap_all_tab',0)";
        }

        // update the version, so we can skip this next time
        $database_updates[] = "replace into settings values('weathermap_db_version','$myversion')";

        // patch up the sortorder for any maps that don't have one.
        $database_updates[] = "UPDATE weathermap_maps SET sortorder=id WHERE sortorder IS NULL OR sortorder=0;";

        if (!empty($database_updates)) {
            for ($a = 0; $a < count($database_updates); $a++) {
                $result = $pdo->query($database_updates[$a]);
            }
        }
    }
}

function weathermap_config_arrays()
{
    global $menu;

    if (function_exists('api_plugin_register_realm')) {
        api_plugin_register_realm('weathermap', 'weathermap-cacti-plugin.php', 'Plugin -> Weathermap: View', 1);
        api_plugin_register_realm('weathermap', 'weathermap-cacti-plugin-mgmt.php',
            'Plugin -> Weathermap: Configure/Manage', 1);
        api_plugin_register_realm('weathermap', 'weathermap-cacti-plugin-editor.php', 'Plugin -> Weathermap: Edit Maps',
            1);
    }

    $wm_menu = array(
        'plugins/weathermap/weathermap-cacti-plugin-mgmt.php' => "Weathermaps",
        'plugins/weathermap/weathermap-cacti-plugin-mgmt-groups.php' => "Groups"
    );

    $menu["Management"]['plugins/weathermap/weathermap-cacti-plugin-mgmt.php'] = $wm_menu;
}


function weathermap_show_tab()
{
    global $config, $user_auth_realm_filenames;
    $realm_id = 0;

    if (isset($user_auth_realm_filenames[basename('weathermap-cacti-plugin.php')])) {
        $realm_id = $user_auth_realm_filenames[basename('weathermap-cacti-plugin.php')];
    }

    $tabstyle = intval(read_config_option("superlinks_tabstyle"));
    $userid = (isset($_SESSION["sess_user_id"]) ? intval($_SESSION["sess_user_id"]) : 1);

    $pdo = weathermap_get_pdo();
    $stmt = $pdo->prepare("select user_auth_realm.realm_id from user_auth_realm where user_auth_realm.user_id=? and user_auth_realm.realm_id=?");
    $stmt->execute(array($userid, $realm_id));
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ((sizeof($result) > 0) || (empty($realm_id))) {

        if ($tabstyle > 0) {
            $prefix = "s_";
        } else {
            $prefix = "";
        }

        print '<a href="' . $config['url_path'] . 'plugins/weathermap/weathermap-cacti-plugin.php"><img src="' . $config['url_path'] . 'plugins/weathermap/images/' . $prefix . 'tab_weathermap';
        // if we're ON a weathermap page, print '_red'
        if (preg_match('/plugins\/weathermap\/weathermap-cacti-plugin.php/', $_SERVER['REQUEST_URI'], $matches)) {
            print "_red";
        }
        print '.gif" alt="weathermap" align="absmiddle" border="0"></a>';

    }

    weathermap_setup_table();
}

function weathermap_draw_navigation_text($nav)
{
    $nav["weathermap-cacti-plugin.php:"] = array(
        "title" => "Weathermap",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti-plugin.php",
        "level" => "1"
    );
    $nav["weathermap-cacti-plugin.php:viewmap"] = array(
        "title" => "Weathermap",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti-plugin.php",
        "level" => "1"
    );
    $nav["weathermap-cacti-plugin.php:liveview"] = array(
        "title" => "Weathermap",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti-plugin.php",
        "level" => "1"
    );
    $nav["weathermap-cacti-plugin.php:liveviewimage"] = array(
        "title" => "Weathermap",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti-plugin.php",
        "level" => "1"
    );
    $nav["weathermap-cacti-plugin.php:viewmapcycle"] = array(
        "title" => "Weathermap",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti-plugin.php",
        "level" => "1"
    );
    $nav["weathermap-cacti-plugin.php:mrss"] = array(
        "title" => "Weathermap",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti-plugin.php",
        "level" => "1"
    );
    $nav["weathermap-cacti-plugin.php:viewimage"] = array(
        "title" => "Weathermap",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti-plugin.php",
        "level" => "1"
    );
    $nav["weathermap-cacti-plugin.php:viewthumb"] = array(
        "title" => "Weathermap",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti-plugin.php",
        "level" => "1"
    );

    $nav["weathermap-cacti-plugin-mgmt.php:"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti-plugin-mgmt.php",
        "level" => "1"
    );
    //   $nav["weathermap-cacti-plugin-mgmt.php:addmap_picker"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
    $nav["weathermap-cacti-plugin-mgmt.php:viewconfig"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti-plugin-mgmt.php:addmap"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti-plugin-mgmt.php:editmap"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti-plugin-mgmt.php:editor"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti-plugin-mgmt.php",
        "level" => "1"
    );

    //  "graphs.php:graph_edit" => array("title" => "(Edit)", "mapping" => "index.php:,graphs.php:", "url" => "", "level" => "2"),

    $nav["weathermap-cacti-plugin-mgmt.php:perms_edit"] = array(
        "title" => "Edit Permissions",
        "mapping" => "index.php:,weathermap-cacti-plugin-mgmt.php:",
        "url" => "",
        "level" => "2"
    );
    $nav["weathermap-cacti-plugin-mgmt.php:addmap_picker"] = array(
        "title" => "Add Map",
        "mapping" => "index.php:,weathermap-cacti-plugin-mgmt.php:",
        "url" => "",
        "level" => "2"
    );
    $nav["weathermap-cacti-plugin-mgmt.php:map_settings"] = array(
        "title" => "Map Settings",
        "mapping" => "index.php:,weathermap-cacti-plugin-mgmt.php:",
        "url" => "",
        "level" => "2"
    );
    $nav["weathermap-cacti-plugin-mgmt.php:map_settings_form"] = array(
        "title" => "Map Settings",
        "mapping" => "index.php:,weathermap-cacti-plugin-mgmt.php:",
        "url" => "",
        "level" => "2"
    );
    $nav["weathermap-cacti-plugin-mgmt.php:map_settings_delete"] = array(
        "title" => "Map Settings",
        "mapping" => "index.php:,weathermap-cacti-plugin-mgmt.php:",
        "url" => "",
        "level" => "2"
    );
    $nav["weathermap-cacti-plugin-mgmt.php:map_settings_update"] = array(
        "title" => "Map Settings",
        "mapping" => "index.php:,weathermap-cacti-plugin-mgmt.php:",
        "url" => "",
        "level" => "2"
    );
    $nav["weathermap-cacti-plugin-mgmt.php:map_settings_add"] = array(
        "title" => "Map Settings",
        "mapping" => "index.php:,weathermap-cacti-plugin-mgmt.php:",
        "url" => "",
        "level" => "2"
    );


    // $nav["weathermap-cacti-plugin-mgmt.php:perms_edit"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
    $nav["weathermap-cacti-plugin-mgmt.php:perms_add_user"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti-plugin-mgmt.php:perms_delete_user"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti-plugin-mgmt.php:delete_map"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti-plugin-mgmt.php:move_map_down"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti-plugin-mgmt.php:move_map_up"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti-plugin-mgmt.php:move_group_down"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti-plugin-mgmt.php:move_group_up"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti-plugin-mgmt.php:group_form"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti-plugin-mgmt.php:group_update"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti-plugin-mgmt.php:activate_map"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti-plugin-mgmt.php:deactivate_map"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti-plugin-mgmt.php:rebuildnow"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti-plugin-mgmt.php:rebuildnow2"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti-plugin-mgmt.php",
        "level" => "1"
    );

    $nav["weathermap-cacti-plugin-mgmt.php:chgroup"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti-plugin-mgmt.php:chgroup_update"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti-plugin-mgmt.php:groupadmin"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti-plugin-mgmt.php",
        "level" => "1"
    );
    $nav["weathermap-cacti-plugin-mgmt.php:groupadmin_delete"] = array(
        "title" => "Weathermap Management",
        "mapping" => "index.php:",
        "url" => "weathermap-cacti-plugin-mgmt.php",
        "level" => "1"
    );

    return $nav;
}

function weathermap_poller_output($rrd_update_array)
{
    global $config;

    $pdo = weathermap_get_pdo();

    $weathermap_data_update = $pdo->prepare("\"UPDATE weathermap_data SET last_time=?, last_calc='?', last_value='?',sequence=sequence+1  where id = ?");

    $log_verbosity = read_config_option("log_verbosity");

    if ($log_verbosity >= POLLER_VERBOSITY_DEBUG) {
        cacti_log("WM poller_output: STARTING\n", true, "WEATHERMAP");
    }

    // partially borrowed from Jimmy Conner's THold plugin.
    // (although I do things slightly differently - I go from filenames, and don't use the poller_interval)

    // new version works with *either* a local_data_id or rrdfile in the weathermap_data table, and returns BOTH

    $stmt = $pdo->query("SELECT DISTINCT weathermap_data.id, weathermap_data.last_value, weathermap_data.last_time, weathermap_data.data_source_name, data_template_data.data_source_path, data_template_data.local_data_id, data_template_rrd.data_source_type_id");
    $requiredlist = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $path_rra = $config["rra_path"];

    # especially on Windows, it seems that filenames are not reliable (sometimes \ and sometimes / even though path_rra is always /) .
    # let's make an index from local_data_id to filename, and then use local_data_id as the key...

    foreach (array_keys($rrd_update_array) as $key) {
        if (isset($rrd_update_array[$key]['times']) && is_array($rrd_update_array[$key]['times'])) {
            $knownfiles[$rrd_update_array[$key]["local_data_id"]] = $key;
        }
    }

    foreach ($requiredlist as $required) {
        $file = str_replace("<path_rra>", $path_rra, $required['data_source_path']);
        $dsname = $required['data_source_name'];
        $local_data_id = $required['local_data_id'];

        if (isset($knownfiles[$local_data_id])) {
            $file2 = $knownfiles[$local_data_id];
            if ($file2 != '') {
                $file = $file2;
            }
        }

        if ($log_verbosity >= POLLER_VERBOSITY_DEBUG) {
            cacti_log("WM poller_output: Looking for $file ($local_data_id) (" . $required['data_source_path'] . ")\n",
                true, "WEATHERMAP");
        }

        if (isset($rrd_update_array[$file]) && is_array($rrd_update_array[$file]) && isset($rrd_update_array[$file]['times']) && is_array($rrd_update_array[$file]['times']) && isset($rrd_update_array{$file}['times'][key($rrd_update_array[$file]['times'])]{$dsname})) {
            $value = $rrd_update_array{$file}['times'][key($rrd_update_array[$file]['times'])]{$dsname};
            $time = key($rrd_update_array[$file]['times']);
            if ($log_verbosity >= POLLER_VERBOSITY_MEDIUM) {
                cacti_log("WM poller_output: Got one! $file:$dsname -> $time $value\n", true, "WEATHERMAP");
            }

            $period = $time - $required['last_time'];
            $lastval = $required['last_value'];

            // if the new value is a NaN, we'll give 0 instead, and pretend it didn't happen from the point
            // of view of the counter etc. That way, we don't get those enormous spikes. Still doesn't deal with
            // reboots very well, but it should improve it for drops.
            if ($value == 'U') {
                $newvalue = 0;
                $newlastvalue = $lastval;
                $newtime = $required['last_time'];
            } else {
                $newlastvalue = $value;
                $newtime = $time;

                switch ($required['data_source_type_id']) {
                    case 1: //GAUGE
                        $newvalue = $value;
                        break;

                    case 2: //COUNTER
                        if ($value >= $lastval) {
                            // Everything is normal
                            $newvalue = $value - $lastval;
                        } else {
                            // Possible overflow, see if its 32bit or 64bit
                            if ($lastval > 4294967295) {
                                $newvalue = (18446744073709551615 - $lastval) + $value;
                            } else {
                                $newvalue = (4294967295 - $lastval) + $value;
                            }
                        }
                        $newvalue = $newvalue / $period;
                        break;

                    case 3: //DERIVE
                        $newvalue = ($value - $lastval) / $period;
                        break;

                    case 4: //ABSOLUTE
                        $newvalue = $value / $period;
                        break;

                    default: // do something somewhat sensible in case something odd happens
                        $newvalue = $value;
                        wm_warn("poller_output found an unknown data_source_type_id for $file:$dsname");
                        break;
                }
            }

            // db_execute("UPDATE weathermap_data SET last_time=$newtime, last_calc='$newvalue', last_value='$newlastvalue',sequence=sequence+1  where id = " . $required['id']);

            $weathermap_data_update->execute(array($newtime, $newvalue, $newlastvalue, $required['id']));
            if ($log_verbosity >= POLLER_VERBOSITY_DEBUG) {
                cacti_log("WM poller_output: Final value is $newvalue (was $lastval, period was $period)\n", true,
                    "WEATHERMAP");
            }
        }
    }

    if ($log_verbosity >= POLLER_VERBOSITY_DEBUG) {
        cacti_log("WM poller_output: ENDING\n", true, "WEATHERMAP");
    }

    return $rrd_update_array;
}

function weathermap_poller_bottom()
{
    global $config;
    global $WEATHERMAP_VERSION;

    include_once($config["library_path"] . DIRECTORY_SEPARATOR . "database.php");
    include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "poller-common.php");

    $pdo = weathermap_get_pdo();

    weathermap_setup_table();

    $renderperiod = read_config_option("weathermap_render_period");
    $rendercounter = read_config_option("weathermap_render_counter");
    $quietlogging = read_config_option("weathermap_quiet_logging");

    if ($renderperiod < 0) {
        // manual updates only
        if ($quietlogging == 0) {
            cacti_log("Weathermap $WEATHERMAP_VERSION - no updates ever", true, "WEATHERMAP");
        }
        return;
    } else {
        // if we're due, run the render updates
        if (($renderperiod == 0) || (($rendercounter % $renderperiod) == 0)) {
            weathermap_run_maps(dirname(__FILE__));
        } else {
            if ($quietlogging == 0) {
                cacti_log("Weathermap $WEATHERMAP_VERSION - no update in this cycle ($rendercounter)", true,
                    "WEATHERMAP");
            }
        }
        // increment the counter
        $newcount = ($rendercounter + 1) % 1000;
        $statement = $pdo->prepare("REPLACE INTO settings VALUES('weathermap_render_counter',?)");
        $statement->execute(array($newcount));
    }
}

// vim:ts=4:sw=4:
