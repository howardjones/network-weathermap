<?php

// This file is from Network Weathermap v0.98

require_once dirname(__FILE__).'/lib/all.php';
require_once dirname(__FILE__).'/lib/WeatherMapEditor.class.php';
require_once dirname(__FILE__).'/lib/WeatherMapEditorUI.class.php';

// so that you can't have the editor active, and not know about it.
$ENABLED = false;

// If we're embedded in the Cacti UI, then authentication has happened. Enable the editor.
if (isset($FROM_CACTI) && $FROM_CACTI == true) {
    $ENABLED = true;
} else {
    $FROM_CACTI = false;
}

if (! $ENABLED) {
    print "<p>The editor has not been enabled for standalone use yet.</p>";
    print "<h3>Cacti</h3><p>You <b>do not</b> need to do this to use the editor from within Cacti - just give users permission to edit maps in the <a href='../../user_admin.php'>User Management page</a>.</p>";
    print "<h3>Standalone Use</h3><p>For standalone use, you need to set ENABLED=true at the top of editor.php</p>";
    print "<p>Before you do that, you should consider using FilesMatch (in Apache) or similar to limit who can access the editor. There is more information in the install guide section of the manual.</p>";
    exit();
}

$ui = new WeatherMapEditorUI();
$ui->moduleChecks();

chdir(dirname(__FILE__));

$ui->main($_REQUEST, $FROM_CACTI);
