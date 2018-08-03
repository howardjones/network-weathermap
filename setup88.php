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
 * All the real work is in lib/Weathermap/Integrations/Cacti/cacti88-plugin-hooks.php
 * and lib/Weathermap/Integrations/Cacti/cacti-plugin-poller.php
 */


// Load our autoloader
require_once dirname(__FILE__) . "/lib/all.php";

// These four are not classes which can be autoloaded. They're the global functions that Cacti is looking
// for to use as plugin hook functions
require_once dirname(__FILE__) . "/lib/Weathermap/Integrations/Cacti/database.php";
require_once dirname(__FILE__) . "/lib/Weathermap/Integrations/Cacti/cacti88-plugin-hooks.php";
require_once dirname(__FILE__) . "/lib/Weathermap/Integrations/Cacti/cacti-plugin-poller.php";
// and the old (to be replaced) Giant Function Of Everything for drawing maps
require_once dirname(__FILE__) . "/lib/Weathermap/Poller/poller-common.php";


//require_once dirname(__FILE__) . "/lib/Weathermap/Integrations/Cacti/database.php";
//require_once dirname(__FILE__) . "/lib/Weathermap/Integrations/Cacti/cacti88-plugin-hooks.php";
//require_once dirname(__FILE__) . "/lib/Weathermap/Integrations/Cacti/cacti-plugin-poller.php";
//require_once dirname(__FILE__) . "/lib/Weathermap/Integrations/Cacti/CactiApplicationInterface.php";
//require_once dirname(__FILE__) . "/lib/Weathermap/Integrations/MapManager.php";
//require_once dirname(__FILE__) . "/lib/Weathermap/Core/constants.php";

function plugin_weathermap_version()
{
    return array(
        'name' => 'weathermap',
        'version' => '1.0.0',
        'longname' => 'PHP Network Weathermap for Cacti 0.8.x',
        'author' => 'Howard Jones',
        'homepage' => 'http://www.network-weathermap.com/',
        'webpage' => 'http://www.network-weathermap.com/',
        'email' => 'howie@thingy.com',
        'url' => 'http://www.network-weathermap.com/versions.php'
    );
}


function plugin_weathermap_install()
{
    \api_plugin_register_hook('weathermap', 'config_arrays', 'weathermap_config_arrays', 'setup88.php');
    \api_plugin_register_hook('weathermap', 'config_settings', 'weathermap_config_settings', 'setup88.php');

    \api_plugin_register_hook('weathermap', 'top_header_tabs', 'weathermap_show_tab', 'setup88.php');
    \api_plugin_register_hook('weathermap', 'top_graph_header_tabs', 'weathermap_show_tab', 'setup88.php');
    \api_plugin_register_hook('weathermap', 'draw_navigation_text', 'weathermap_draw_navigation_text', 'setup88.php');

    \api_plugin_register_hook('weathermap', 'top_graph_refresh', 'weathermap_top_graph_refresh', 'setup88.php');
    \api_plugin_register_hook('weathermap', 'page_title', 'weathermap_page_title', 'setup88.php');
    \api_plugin_register_hook('weathermap', 'page_head', 'weathermap_page_head', 'setup88.php');

    \api_plugin_register_hook('weathermap', 'poller_top', 'weathermap_poller_top', 'setup88.php');
    \api_plugin_register_hook('weathermap', 'poller_output', 'weathermap_poller_output', 'setup88.php');
    \api_plugin_register_hook('weathermap', 'poller_bottom', 'weathermap_poller_bottom', 'setup88.php');

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
// vim:ts=4:sw=4:
