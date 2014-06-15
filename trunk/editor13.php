<?php

// This file is from Network Weathermap v0.98

require_once dirname(__FILE__).'/lib/all.php';
require_once dirname(__FILE__).'/lib/WeatherMapEditor.class.php';
require_once dirname(__FILE__).'/lib/WeatherMapEditorUI.class.php';

// so that you can't have the editor active, and not know about it.
$ENABLED = true;

if (! $ENABLED) {
    print "<p>The editor has not been enabled yet. You need to set ENABLED=true at the top of editor.php</p>";
    print "<p>Before you do that, you should consider using FilesMatch (in Apache) or similar to limit who can access the editor. There is more information in the install guide section of the manual.</p>";
    exit();
}

$ui = new WeatherMapEditorUI();
$ui->moduleChecks();

chdir(dirname(__FILE__));

$ui->main($_REQUEST);
