<?php

function weathermap_setup_table()
{
    $dbversion = \read_config_option('weathermap_db_version');

    $myversioninfo = weathermap_version();
    $myversion = $myversioninfo['version'];

    $pdo = weathermap_get_pdo();
    $cactiInterface = new Weathermap\Integrations\Cacti\CactiApplicationInterface($pdo);
    $manager = new Weathermap\Integrations\MapManager($pdo, "", $cactiInterface);

    if (($dbversion == '') || (preg_match('/dev$/', $myversion)) || ($dbversion != $myversion)) {
        $manager->initializeDatabase();
        $manager->initializeAppSettings();
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
            $cactiInterface = new Weathermap\Integrations\Cacti\CactiApplicationInterface($pdo);
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

function weathermap_page_head()
{
    if (preg_match('/plugins\/weathermap\//', $_SERVER['REQUEST_URI'])) {
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
        return 86400;
    }
    return $refresh;
}

function weathermap_show_tab()
{
    global $config, $user_auth_realm_filenames;

    if (api_plugin_user_realm_auth('weathermap-cacti10-plugin.php')) {
        $tabstyle = intval(read_config_option('superlinks_tabstyle'));

        if ($tabstyle > 0) {
            $prefix = 's_';
        } else {
            $prefix = '';
        }
        $tabName = $prefix . 'tab_weathermap.gif';
        $weathermapBaseURL = $config['url_path'] . 'plugins/weathermap';
        $weathermapURL = $weathermapBaseURL . '/weathermap-cacti10-plugin.php';

        if (preg_match('/plugins\/weathermap\/weathermap-cacti10-plugin.php/', $_SERVER['REQUEST_URI'], $matches)) {
            $tabName = $prefix . 'tab_weathermap_red.gif';
        }
        $tabURL = $weathermapBaseURL . '/images/' . $tabName;

        printf(
            '<a href="%s"><img src="%s" alt="' . __('Weathermap', 'weathermap') . '" align="absmiddle" border="0" /></a>',
            $weathermapURL,
            $tabURL
        );
    }

    weathermap_setup_table();
}

function weathermap_config_arrays()
{
    global $menu;

    $ourMenu = array(
        'plugins/weathermap/weathermap-cacti10-plugin-mgmt.php' => __('Weathermaps', 'weathermap'),
        'plugins/weathermap/weathermap-cacti10-plugin-mgmt-groups.php' => __('Groups', 'weathermap')
    );

    $menu[__('Management')]['plugins/weathermap/weathermap-cacti10-plugin-mgmt.php'] = $ourMenu;
}

function weathermap_config_settings()
{
    global $tabs, $settings;

    $tabs['weathermap'] = __('Maps', 'weathermap');

    $temp = array(
        'weathermap_header' => array(
            'friendly_name' => __('Network Weathermap', 'weathermap'),
            'method' => 'spacer',
        ),
        'weathermap_pagestyle' => array(
            'friendly_name' => __('Page style', 'weathermap'),
            'description' => __('How to display multiple maps.', 'weathermap'),
            'method' => 'drop_array',
            'array' => array(
                0 => __('Thumbnail Overview', 'weathermap'),
                1 => __('Full Images', 'weathermap'),
                2 => __('Show Only First', 'weathermap')
            )
        ),
        'weathermap_thumbsize' => array(
            'friendly_name' => __('Thumbnail Maximum Size', 'weathermap'),
            'description' => __('The maximum width or height for thumbnails in thumbnail view, in pixels. Takes effect after the next poller run.', 'weathermap'),
            'method' => 'textbox',
            'max_length' => 5,
        ),
        'weathermap_cycle_refresh' => array(
            'friendly_name' => __('Refresh Time', 'weathermap'),
            'description' => __('How often to refresh the page in Cycle mode. Automatic makes all available maps fit into 5 minutes.', 'weathermap'),
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
            'friendly_name' => __('Output Format', 'weathermap'),
            'description' => __('What format do you prefer for the generated map images and thumbnails?', 'weathermap'),
            'method' => 'drop_array',
            'array' => array(
                'png' => __('PNG (default)', 'weathermap'),
                'jpg' => __('JPEG', 'weathermap'),
                'gif' => __('GIF', 'weathermap')
            )
        ),
        'weathermap_render_period' => array(
            'friendly_name' => __('Map Rendering Interval', 'weathermap'),
            'description' => __('How often do you want Weathermap to recalculate its maps? You should not touch this unless you know what you are doing! It is mainly needed for people with non-standard polling setups.', 'weathermap'),
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
            'friendly_name' => __('Show \'All\' Tab', 'weathermap'),
            'description' => __('When using groups, add an \'All Maps\' tab to the tab bar.', 'weathermap'),
            'method' => 'drop_array',
            'array' => array(
                0 => __('No (default)', 'weathermap'),
                1 => __('Yes', 'weathermap')
            )
        ),
        'weathermap_map_selector' => array(
            'friendly_name' => __('Show Map Selector', 'weathermap'),
            'description' => __('Show a combo-box map selector on the full-screen map view.', 'weathermap'),
            'method' => 'drop_array',
            'array' => array(
                0 => __('No', 'weathermap'),
                1 => __('Yes (default)', 'weathermap')
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
    if (isset($settings['weathermap'])) {
        $settings['weathermap'] = array_merge($settings['weathermap'], $temp);
    } else {
        $settings['weathermap'] = $temp;
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
