<?php

/**
 * Weathermap 0.98
 *
 * cacti-pick.php - data-source selector for the editor
 *
 */

// ******************************************
// sensible defaults
$cacti_base = '../../';
$cacti_base = '/var/www/html/cacti-0.8.8c';

$config['base_url'] = $cacti_url;

if (file_exists("editor-config.php")) {
    include_once 'editor-config.php';
}

require_once dirname(__FILE__).'/lib/SimpleTemplate.class.php';
require_once "lib/cacti-pick.php";

// Load Cacti's config, so we can get the database details
if (is_dir($cacti_base) && file_exists($cacti_base."/include/global.php")) {
    // include the cacti-config, so we know about the database
    include_once $cacti_base."/include/global.php";
    $config['base_url'] = (isset($config['url_path'])? $config['url_path'] : $cacti_url);
} else {
    throw new Exception("Can't run Cacti Picker without Cacti");
}

pickerDispatch($_GET);
