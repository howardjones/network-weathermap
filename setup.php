<?php
/*******************************************************************************

    Author ......... Howard Jones
    Contact ........ howie@thingy.com
    Home Site ...... http://www.network-weathermap.com/
    Program ........ Network Weathermap for Cacti
    Version ........ See code below
    Purpose ........ Network Usage Overview

*******************************************************************************/

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

}

function plugin_weathermap_version()
{
    return array( 	'name'    	=> 'weathermap',
        'version'       => '0.97d',
        'longname'      => 'PHP Network Weathermap',
        'author'        => 'Howard Jones',
        'homepage'      => 'http://www.network-weathermap.com/',
        'webpage'      => 'http://www.network-weathermap.com/',
        'email' 	=> 'howie@thingy.com',
        'url'           => 'http://www.network-weathermap.com/versions.php'
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

require "lib/cacti-plugin-hooks.php";

// vim:ts=4:sw=4: