<?php

namespace Weathermap\Integrations\Cacti;

// ******************************************
// sensible defaults
$mapdir = 'configs';
$cacti_base = '../../';
$cacti_url = '/';
$ignore_cacti = false;

$config['base_url'] = $cacti_url;

# if your installation keeps plugins separate from the cacti install, you might need to manually set this
# (e.g. Debian/ubuntu package-based installs probably need it)

# $cacti_base = "/var/www/html/cacti-0.8.8h";

@include_once 'editor-config.php';

if (is_dir($cacti_base) && file_exists($cacti_base . "/include/global.php")) {
    // include the cacti-config, so we know about the database
    include_once $cacti_base . "/include/global.php";

    $config['base_url'] = (isset($config['url_path']) ? $config['url_path'] : $cacti_url);
    $cacti_found = true;

    require_once dirname(__FILE__) . "/lib/Weathermap/Integrations/Cacti/database.php";
    require_once dirname(__FILE__) . "/lib/all.php";
} else {
    $cacti_found = false;
    print "NO CACTI";
    exit();
}

$jquery = '<script type="text/javascript" src="node_modules/jquery/dist/jquery.min.js"></script>';
if (isset($config['cacti_version'])) {
    if (substr($config['cacti_version'], 0, 2) == "1.") {
        $jquery = "";
    }
}

$ui = new EditorDataPicker();
$ui->pdo = weathermap_get_pdo();
$ui->main($_REQUEST);
