<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 05/02/17
 * Time: 22:35
 */

function weathermap_setup_table()
{
    global $config, $database_default;
    include_once $config['library_path'] . DIRECTORY_SEPARATOR . 'database.php';

    $dbversion = read_config_option('weathermap_db_version');

    $myversioninfo = weathermap_version();
    $myversion = $myversioninfo['version'];

    $pdo = weathermap_get_pdo();

    // only bother with all this if it's a new install, a new version, or we're in a development version
    // - saves a handful of db hits per request!
    if (($dbversion == '') || (preg_match('/dev$/', $myversion)) || ($dbversion != $myversion)) {

        $statement = $pdo->query('show tables');
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);

        $tables = array();
        foreach ($result as $index => $arr) {
            foreach ($arr as $t) {
                $tables[] = $t;
            }
        }

        $database_updates = array();
        # $database_updates[] = 'UPDATE weathermap_maps SET sortorder=id WHERE sortorder IS NULL;';

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
                        );";
        } else {
            # Check that all the table columns exist for weathermap_maps
            # There have been a number of changes over versions.
            $statement = $pdo->query('show columns from weathermap_maps from ' . $database_default);
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
                $database_updates[] = 'ALTER TABLE weathermap_maps ADD sortorder INT(11) NOT NULL DEFAULT 0 AFTER id';
            }
            if (!$field_changes['filehash']) {
                $database_updates[] = 'ALTER TABLE weathermap_maps ADD filehash VARCHAR(40) NOT NULL DEFAULT "" AFTER titlecache';
            }
            if (!$field_changes['warncount']) {
                $database_updates[] = 'ALTER TABLE weathermap_maps ADD warncount INT(11) NOT NULL DEFAULT 0 AFTER filehash';
            }
            if (!$field_changes['config']) {
                $database_updates[] = 'ALTER TABLE weathermap_maps ADD config TEXT NOT NULL  DEFAULT "" AFTER warncount';
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
                        );";
        }

        if (!in_array('weathermap_groups', $tables)) {
            $database_updates[] = "CREATE TABLE  weathermap_groups (
                                `id` INT(11) NOT NULL AUTO_INCREMENT,
                                `name` VARCHAR( 128 ) NOT NULL DEFAULT '',
                                `sortorder` INT(11) NOT NULL DEFAULT 0,
                                PRIMARY KEY (id)
                                );";
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
                        );";
        }

        if (!in_array('weathermap_data', $tables)) {
            $database_updates[] = "CREATE TABLE IF NOT EXISTS weathermap_data (id INT(11) NOT NULL AUTO_INCREMENT,
                                rrdfile VARCHAR(255) NOT NULL,data_source_name VARCHAR(19) NOT NULL,
                                  last_time INT(11) NOT NULL,last_value VARCHAR(255) NOT NULL,
                                last_calc VARCHAR(255) NOT NULL, sequence INT(11) NOT NULL, local_data_id INT(11) NOT NULL DEFAULT 0, PRIMARY KEY  (id), KEY rrdfile (rrdfile),
                                  KEY local_data_id (local_data_id), KEY data_source_name (data_source_name) )";
        } else {
            $found_ldi = false;

            $statement = $pdo->query('SHOW COLUMNS FROM weathermap_data FROM ' . $database_default);
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

        $pagestyle = read_config_option('weathermap_pagestyle');
        if ($pagestyle == '' or $pagestyle < 0 or $pagestyle > 2) {
            $database_updates[] = "REPLACE INTO settings VALUES('weathermap_pagestyle',0)";
        }

        $cycledelay = read_config_option('weathermap_cycle_refresh');
        if ($cycledelay == '' or intval($cycledelay < 0)) {
            $database_updates[] = "REPLACE INTO settings VALUES('weathermap_cycle_refresh',0)";
        }

        $renderperiod = read_config_option('weathermap_render_period');
        if ($renderperiod == '' or intval($renderperiod < -1)) {
            $database_updates[] = "REPLACE INTO settings VALUES('weathermap_render_period',0)";
        }

        $quietlogging = read_config_option('weathermap_quiet_logging');
        if ($quietlogging == '' or intval($quietlogging < -1)) {
            $database_updates[] = "REPLACE INTO settings VALUES('weathermap_quiet_logging',0)";
        }

        $rendercounter = read_config_option('weathermap_render_counter');
        if ($rendercounter == '' or intval($rendercounter < 0)) {
            $database_updates[] = "REPLACE INTO settings VALUES('weathermap_render_counter',0)";
        }

        $outputformat = read_config_option('weathermap_output_format');
        if ($outputformat == '') {
            $database_updates[] = "REPLACE INTO settings VALUES('weathermap_output_format','png')";
        }

        $tsize = read_config_option('weathermap_thumbsize');
        if ($tsize == '' or $tsize < 1) {
            $database_updates[] = "REPLACE INTO settings VALUES('weathermap_thumbsize',250)";
        }

        $ms = read_config_option('weathermap_map_selector');
        if ($ms == '' or intval($ms) < 0 or intval($ms) > 1) {
            $database_updates[] = "REPLACE INTO settings VALUES('weathermap_map_selector',1)";
        }

        $at = read_config_option('weathermap_all_tab');
        if ($at == '' or intval($at) < 0 or intval($at) > 1) {
            $database_updates[] = "REPLACE INTO settings VALUES('weathermap_all_tab',0)";
        }

        // update the version, so we can skip this next time
        $database_updates[] = "REPLACE INTO settings VALUES('weathermap_db_version','$myversion')";

        // patch up the sortorder for any maps that don't have one.
        $database_updates[] = "UPDATE weathermap_maps SET sortorder=id WHERE sortorder IS NULL OR sortorder=0;";

        if (!empty($database_updates)) {
            for ($a = 0; $a < count($database_updates); $a++) {
                $result = $pdo->query($database_updates[$a]);
            }
        }
    }
}

function weathermap_page_title($t)
{
    if (preg_match('/plugins\/weathermap\//', $_SERVER['REQUEST_URI'], $matches)) {
        $t .= " - Weathermap";

        if (preg_match('/plugins\/weathermap\/weathermap-cacti\d\d-plugin.php\?action=viewmap&id=([^&]+)/',
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

function weathermap_page_head()
{
    if (preg_match('/plugins\/weathermap\//', $_SERVER['REQUEST_URI'], $matches)) {
        print '<LINK rel="stylesheet" type="text/css" media="screen" href="weathermap-cacti-plugin.css">';
    }
}

function weathermap_top_graph_refresh($refresh)
{
    if (basename($_SERVER['PHP_SELF']) != 'weathermap-cacti10-plugin.php') {
        return $refresh;
    }

    // if we're cycling maps, then we want to handle reloads ourselves, thanks
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'viewmapcycle') {
        return (86400);
    }
    return ($refresh);
}

function weathermap_show_tab()
{
    global $config, $user_auth_realm_filenames;
    $realm_id = 0;

    if (isset($user_auth_realm_filenames[basename('weathermap-cacti10-plugin.php')])) {
        $realm_id = $user_auth_realm_filenames[basename('weathermap-cacti10-plugin.php')];
    }

    $tabstyle = intval(read_config_option('superlinks_tabstyle'));
    $userid = (isset($_SESSION['sess_user_id']) ? intval($_SESSION['sess_user_id']) : 1);

    $pdo = weathermap_get_pdo();
    $stmt = $pdo->prepare('SELECT user_auth_realm.realm_id FROM user_auth_realm WHERE user_auth_realm.user_id=? AND user_auth_realm.realm_id=?');
    $stmt->execute(array($userid, $realm_id));
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ((sizeof($result) > 0) || (empty($realm_id))) {

        if ($tabstyle > 0) {
            $prefix = 's_';
        } else {
            $prefix = '';
        }
        $tab_name = $prefix . 'tab_weathermap.gif';
        $weathermap_base = $config['url_path'] . 'plugins/weathermap';
        $weathermap_url = $weathermap_base . '/weathermap-cacti10-plugin.php';

        if (preg_match('/plugins\/weathermap\/weathermap-cacti10-plugin.php/', $_SERVER['REQUEST_URI'], $matches)) {
            $tab_name = $prefix . 'tab_weathermap_red.gif';
        }
        $tab_url = $weathermap_base . '/images/' . $tab_name;

        printf('<a href="%s"><img src="%s" alt="' . __('Weathermap') . '" align="absmiddle" border="0" /></a>', $weathermap_url, $tab_url);

    }

    weathermap_setup_table();
}

function weathermap_config_arrays()
{
    global $menu;

    if (function_exists('api_plugin_register_realm')) {
        api_plugin_register_realm('weathermap', 'weathermap-cacti10-plugin.php', __('Weathermap: View'), 1);
        api_plugin_register_realm('weathermap', 'weathermap-cacti10-plugin-mgmt.php', __('Weathermap: Configure/Manage'), 1);
        api_plugin_register_realm('weathermap', 'weathermap-cacti-plugin-editor.php', __('Weathermap: Edit Maps'), 1);
    }

    $wm_menu = array(
        'plugins/weathermap/weathermap-cacti10-plugin-mgmt.php' => __('Weathermaps'),
        'plugins/weathermap/weathermap-cacti10-plugin-mgmt-groups.php' => __('Groups')
    );

    $menu[__('Management')]['plugins/weathermap/weathermap-cacti10-plugin-mgmt.php'] = $wm_menu;
}

function weathermap_config_settings()
{
    global $tabs, $settings;

    $tabs['maps'] = __('Maps');

    $temp = array(
        'weathermap_header' => array(
            'friendly_name' => __('Network Weathermap'),
            'method' => 'spacer',
        ),
        'weathermap_pagestyle' => array(
            'friendly_name' => __('Page style'),
            'description' => __('How to display multiple maps.'),
            'method' => 'drop_array',
            'array' => array(
                0 => __('Thumbnail Overview'),
                1 => __('Full Images'),
                2 => __('Show Only First')
            )
        ),
        'weathermap_thumbsize' => array(
            'friendly_name' => __('Thumbnail Maximum Size'),
            'description' => __('The maximum width or height for thumbnails in thumbnail view, in pixels. Takes effect after the next poller run.'),
            'method' => 'textbox',
            'max_length' => 5,
        ),
        'weathermap_cycle_refresh' => array(
            'friendly_name' => __('Refresh Time'),
            'description' => __('How often to refresh the page in Cycle mode. Automatic makes all available maps fit into 5 minutes.'),
            'method' => 'drop_array',
            'array' => array(
                0 => __('Automatic'),
                5 => __('%d Seconds', 5),
                15 => __('%d Seconds', 15),
                30 => __('%d Seconds', 30),
                60 => __('%d Minute', 1),
                120 => __('%d Minutes', 2),
                300 => __('%d Minutes', 5),
            )
        ),
        'weathermap_output_format' => array(
            'friendly_name' => __('Output Format'),
            'description' => __('What format do you prefer for the generated map images and thumbnails?'),
            'method' => 'drop_array',
            'array' => array(
                'png' => __('PNG (default)'),
                'jpg' => __('JPEG'),
                'gif' => __('GIF')
            )
        ),
        'weathermap_render_period' => array(
            'friendly_name' => __('Map Rendering Interval'),
            'description' => __('How often do you want Weathermap to recalculate it\'s maps? You should not touch this unless you know what you are doing! It is mainly needed for people with non-standard polling setups.'),
            'method' => 'drop_array',
            'array' => array(
                -1 => __('Never (manual updates)'),
                0 => __('Every Poller Cycle (default)'),
                2 => __('Every %d Poller Cycles', 2),
                3 => __('Every %d Poller Cycles', 3),
                4 => __('Every %d Poller Cycles', 4),
                5 => __('Every %d Poller Cycles', 5),
                10 => __('Every %d Poller Cycles', 10),
                12 => __('Every %d Poller Cycles', 12),
                24 => __('Every %d Poller Cycles', 24),
                36 => __('Every %d Poller Cycles', 36),
                48 => __('Every %d Poller Cycles', 48),
                72 => __('Every %d Poller Cycles', 72),
                288 => __('Every %d Poller Cycles', 288),
            ),
        ),

        'weathermap_all_tab' => array(
            'friendly_name' => __('Show \'All\' Tab'),
            'description' => __('When using groups, add an \'All Maps\' tab to the tab bar.'),
            'method' => 'drop_array',
            'array' => array(
                0 => __('No (default)'),
                1 => __('Yes')
            )
        ),
        'weathermap_map_selector' => array(
            'friendly_name' => __('Show Map Selector'),
            'description' => __('Show a combo-box map selector on the full-screen map view.'),
            'method' => 'drop_array',
            'array' => array(
                0 => __('No'),
                1 => __('Yes (default)')
            )
        ),
        'weathermap_quiet_logging' => array(
            'friendly_name' => __('Quiet Logging'),
            'description' => __('By default, even in LOW level logging, Weathermap logs normal activity. This makes it REALLY log only errors in LOW mode.'),
            'method' => 'drop_array',
            'array' => array(
                0 => __('Chatty (default)'),
                1 => __('Quiet')
            )
        )
    );
    if (isset($settings['maps'])) {
        $settings['maps'] = array_merge($settings['maps'], $temp);
    } else {
        $settings['maps'] = $temp;
    }
}

function weathermap_draw_navigation_text($nav)
{
    $nav['weathermap-cacti10-plugin.php:'] = array(
        'title' => __('Weathermaps'),
        'mapping' => '',
        'url' => 'weathermap-cacti10-plugin.php',
        'level' => '0'
    );
    $nav['weathermap-cacti10-plugin.php:viewmap'] = array(
        'title' => __('(View Map)'),
        'mapping' => 'weathermap-cacti10-plugin.php:',
        'url' => '',
        'level' => '1'
    );
    $nav['weathermap-cacti10-plugin.php:liveview'] = array(
        'title' => __('(Live View)'),
        'mapping' => 'weathermap-cacti10-plugin.php:',
        'url' => '',
        'level' => '1'
    );
    $nav['weathermap-cacti10-plugin.php:liveviewimage'] = array(
        'title' => __('(Live View Image)'),
        'mapping' => 'weathermap-cacti10-plugin.php:',
        'url' => '',
        'level' => '1'
    );
    $nav['weathermap-cacti10-plugin.php:viewmapcycle'] = array(
        'title' => __('(Cycle View)'),
        'mapping' => 'weathermap-cacti10-plugin.php:',
        'url' => '',
        'level' => '1'
    );
    $nav['weathermap-cacti10-plugin.php:mrss'] = array(
        'title' => __('(View MRSS)'),
        'mapping' => 'weathermap-cacti10-plugin.php:',
        'url' => '',
        'level' => '1'
    );
    $nav['weathermap-cacti10-plugin.php:viewimage'] = array(
        'title' => __('(View Image)'),
        'mapping' => 'weathermap-cacti10-plugin.php:',
        'url' => '',
        'level' => '1'
    );
    $nav['weathermap-cacti10-plugin.php:viewthumb'] = array(
        'title' => 'Weathermap',
        'mapping' => 'weathermap-cacti10-plugin.php:',
        'url' => '',
        'level' => '1'
    );

    // Management functions

    $nav['weathermap-cacti10-plugin-mgmt.php:'] = array(
        'title' => 'Weathermap Management',
        'mapping' => 'index.php:',
        'url' => 'weathermap-cacti10-plugin-mgmt.php',
        'level' => '1'
    );

    //   $nav['weathermap-cacti10-plugin-mgmt.php:addmap_picker'] = array('title' => 'Weathermap Management', 'mapping' => 'index.php:', 'url' => 'weathermap-cacti10-plugin-mgmt.php', 'level' => '1');

    $nav['weathermap-cacti10-plugin-mgmt.php:viewconfig'] = array(
        'title' => __('(View Config)'),
        'mapping' => 'index.php:, weathermap-cacti10-plugin-mgmt.php:',
        'url' => '',
        'level' => '2'
    );
    $nav['weathermap-cacti10-plugin-mgmt.php:addmap'] = array(
        'title' => __('(Add Map)'),
        'mapping' => 'index.php:,weathermap-cacti10-plugin-mgmt.php:',
        'url' => '',
        'level' => '2'
    );
    $nav['weathermap-cacti10-plugin-mgmt.php:editmap'] = array(
        'title' => __('(Edit Map)'),
        'mapping' => 'index.php:,weathermap-cacti10-plugin-mgmt.php:',
        'url' => '',
        'level' => '2'
    );
    $nav['weathermap-cacti10-plugin-mgmt.php:editor'] = array(
        'title' => __('(Editor)'),
        'mapping' => 'index.php:,weathermap-cacti10-plugin-mgmt.php:',
        'url' => 'weathermap-cacti10-plugin-mgmt.php',
        'level' => '2'
    );

    $nav['weathermap-cacti10-plugin-mgmt.php:perms_edit'] = array(
        'title' => __('(Edit Permissions)'),
        'mapping' => 'index.php:,weathermap-cacti10-plugin-mgmt.php:',
        'url' => '',
        'level' => '2'
    );
    $nav['weathermap-cacti10-plugin-mgmt.php:addmap_picker'] = array(
        'title' => __('(Add Map)'),
        'mapping' => 'index.php:,weathermap-cacti10-plugin-mgmt.php:',
        'url' => '',
        'level' => '2'
    );
    $nav['weathermap-cacti10-plugin-mgmt.php:map_settings'] = array(
        'title' => __('(Map Settings)'),
        'mapping' => 'index.php:,weathermap-cacti10-plugin-mgmt.php:',
        'url' => '',
        'level' => '2'
    );
    $nav['weathermap-cacti10-plugin-mgmt.php:map_settings_form'] = array(
        'title' => __('(Settings Form)'),
        'mapping' => 'index.php:,weathermap-cacti10-plugin-mgmt.php:',
        'url' => '',
        'level' => '2'
    );
    $nav['weathermap-cacti10-plugin-mgmt.php:map_settings_delete'] = array(
        'title' => __('(Settings Delete)'),
        'mapping' => 'index.php:,weathermap-cacti10-plugin-mgmt.php:',
        'url' => '',
        'level' => '2'
    );
    $nav['weathermap-cacti10-plugin-mgmt.php:map_settings_update'] = array(
        'title' => __('(Settings Update)'),
        'mapping' => 'index.php:,weathermap-cacti10-plugin-mgmt.php:',
        'url' => '',
        'level' => '2'
    );
    $nav['weathermap-cacti10-plugin-mgmt.php:map_settings_add'] = array(
        'title' => __('(Settings Add)'),
        'mapping' => 'index.php:,weathermap-cacti10-plugin-mgmt.php:',
        'url' => '',
        'level' => '2'
    );

    $nav['weathermap-cacti10-plugin-mgmt.php:perms_add_user'] = array(
        'title' => __('(User Add)'),
        'mapping' => 'index.php:,weathermap-cacti10-plugin-mgmt.php:',
        'url' => '',
        'level' => '2'
    );
    $nav['weathermap-cacti10-plugin-mgmt.php:perms_delete_user'] = array(
        'title' => __('(User Delete)'),
        'mapping' => 'index.php:,weathermap-cacti10-plugin-mgmt.php:',
        'url' => '',
        'level' => '2'
    );
    $nav['weathermap-cacti10-plugin-mgmt.php:delete_map'] = array(
        'title' => __('(Delete Map)'),
        'mapping' => 'index.php:,weathermap-cacti10-plugin-mgmt.php:',
        'url' => '',
        'level' => '2'
    );
    $nav['weathermap-cacti10-plugin-mgmt.php:move_map_down'] = array(
        'title' => __('(Map Move Down)'),
        'mapping' => 'index.php:,weathermap-cacti10-plugin-mgmt.php:',
        'url' => '',
        'level' => '2'
    );
    $nav['weathermap-cacti10-plugin-mgmt.php:move_map_up'] = array(
        'title' => __('(Map Move Up)'),
        'mapping' => 'index.php:,weathermap-cacti10-plugin-mgmt.php:',
        'url' => '',
        'level' => '2'
    );
    $nav['weathermap-cacti10-plugin-mgmt.php:move_group_down'] = array(
        'title' => __('(Group Move Down)'),
        'mapping' => 'index.php:,weathermap-cacti10-plugin-mgmt.php:',
        'url' => 'weathermap-cacti10-plugin-mgmt.php',
        'level' => '2'
    );
    $nav['weathermap-cacti10-plugin-mgmt.php:move_group_up'] = array(
        'title' => __('(Group Move Up)'),
        'mapping' => 'index.php:,weathermap-cacti10-plugin-mgmt.php:',
        'url' => '',
        'level' => '2'
    );
    $nav['weathermap-cacti10-plugin-mgmt.php:group_form'] = array(
        'title' => __('(Group Update)'),
        'mapping' => 'index.php:,weathermap-cacti10-plugin-mgmt.php:',
        'url' => '',
        'level' => '2'
    );
    $nav['weathermap-cacti10-plugin-mgmt.php:group_update'] = array(
        'title' => __('(Group Update)'),
        'mapping' => 'index.php:,weathermap-cacti10-plugin-mgmt.php:',
        'url' => '',
        'level' => '2'
    );
    $nav['weathermap-cacti10-plugin-mgmt.php:activate_map'] = array(
        'title' => __('(Activate Map)'),
        'mapping' => 'index.php:,weathermap-cacti10-plugin-mgmt.php:',
        'url' => '',
        'level' => '2'
    );
    $nav['weathermap-cacti10-plugin-mgmt.php:deactivate_map'] = array(
        'title' => __('(Deactivate Map)'),
        'mapping' => 'index.php:,weathermap-cacti10-plugin-mgmt.php:',
        'url' => '',
        'level' => '2'
    );
    $nav['weathermap-cacti10-plugin-mgmt.php:rebuildnow'] = array(
        'title' => __('(Rebuild Now)'),
        'mapping' => 'index.php:,weathermap-cacti10-plugin-mgmt.php:',
        'url' => '',
        'level' => '2'
    );
    $nav['weathermap-cacti10-plugin-mgmt.php:rebuildnow2'] = array(
        'title' => __('(Rebuild Now)'),
        'mapping' => 'index.php:,weathermap-cacti10-plugin-mgmt.php:',
        'url' => '',
        'level' => '2'
    );

    $nav['weathermap-cacti10-plugin-mgmt.php:chgroup'] = array(
        'title' => __('(Change Group)'),
        'mapping' => 'index.php:,weathermap-cacti10-plugin-mgmt.php:',
        'url' => '',
        'level' => '2'
    );
    $nav['weathermap-cacti10-plugin-mgmt.php:chgroup_update'] = array(
        'title' => __('(Group Update)'),
        'mapping' => 'index.php:,weathermap-cacti10-plugin-mgmt.php:',
        'url' => '',
        'level' => '2'
    );
    $nav['weathermap-cacti10-plugin-mgmt.php:groupadmin'] = array(
        'title' => __('(Group Admin)'),
        'mapping' => 'index.php:,weathermap-cacti10-plugin-mgmt.php:',
        'url' => '',
        'level' => '2'
    );
    $nav['weathermap-cacti10-plugin-mgmt.php:groupadmin_delete'] = array(
        'title' => __('(Group Delete)'),
        'mapping' => 'index.php:,weathermap-cacti10-plugin-mgmt.php:',
        'url' => '',
        'level' => '2'
    );

    return $nav;
}
