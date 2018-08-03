<?php
/**
 * A shim for the standalone editor so that it benefits from
 * Cacti authentication.
 *
 * The editor checks that $config is defined, to tell if it's 'inside'
 * Cacti. setup.php has the appropriate auth table entries for all the
 * editor commands. You can also leave the editor in the 'disabled' state
 * and still use it from Cacti, which is handy!
 */

chdir('../../');
require_once './include/auth.php';
require_once './include/config.php';

require_once $config['library_path'] . '/database.php';

// require_once dirname(__FILE__)."/lib/all.php";

$cameFromHost = true;
$hostPluginURL = "weathermap-cacti10-plugin-mgmt.php";
$hostEditorURL = "weathermap-cacti10-plugin-editor.php";
$hostType = 'cacti';

require_once dirname(__FILE__) . '/editor.php';
