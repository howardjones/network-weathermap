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

/**
 * This file now contains only the functions for registering the plugin and doing plugin-admin stuff
 *
 * All the real work is in lib/cacti88-plugin-hooks.php and lib/cacti88-plugin-poller.php
 */


require_once dirname(__FILE__) . "/lib/database.php";
require_once dirname(__FILE__) . "/lib/cacti10-plugin-hooks.php";
require_once dirname(__FILE__) . "/lib/cacti10-plugin-poller.php";


function plugin_weathermap_version()
{
    global $config;
    $info = parse_ini_file($config['base_path'] . '/plugins/weathermap/INFO', true);
    return $info['info'];
}


function plugin_weathermap_install()
{
    api_plugin_register_hook('weathermap', 'config_arrays', 'weathermap_config_arrays', 'setup10.php');
    api_plugin_register_hook('weathermap', 'config_settings', 'weathermap_config_settings', 'setup10.php');

    api_plugin_register_hook('weathermap', 'top_header_tabs', 'weathermap_show_tab', 'setup10.php');
    api_plugin_register_hook('weathermap', 'top_graph_header_tabs', 'weathermap_show_tab', 'setup10.php');
    api_plugin_register_hook('weathermap', 'draw_navigation_text', 'weathermap_draw_navigation_text', 'setup10.php');

    api_plugin_register_hook('weathermap', 'top_graph_refresh', 'weathermap_top_graph_refresh', 'setup10.php');
    api_plugin_register_hook('weathermap', 'page_title', 'weathermap_page_title', 'setup10.php');
    api_plugin_register_hook('weathermap', 'page_head', 'weathermap_page_head', 'setup10.php');

    api_plugin_register_hook('weathermap', 'poller_top', 'weathermap_poller_top', 'setup10.php');
    api_plugin_register_hook('weathermap', 'poller_output', 'weathermap_poller_output', 'setup10.php');
    api_plugin_register_hook('weathermap', 'poller_bottom', 'weathermap_poller_bottom', 'setup10.php');

    weathermap_setup_table();
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


function plugin_weathermap_uninstall()
{
    // This function doesn't seem to ever be called, in Cacti 0.8.8b
    // on the assumption that it will one day work, clear the stored version number from the settings
    // so that an uninstall/reinstall on the plugin would force the db schema to be checked
    $pdo = weathermap_get_pdo();
    $pdo->query("REPLACE INTO settings VALUES('weathermap_version','')");
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
