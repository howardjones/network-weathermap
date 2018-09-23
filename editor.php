<?php

// This file is from Network Weathermap v0.98

require_once dirname(__FILE__) . '/lib/all.php';

use Weathermap\Editor\EditorUI;

// so that you can't have the editor active, and not know about it.
$editorEnabled = false;

// For Cacti, this file is included in weathermap-cactiXX-plugin-editor.php - that will define these variables

// If we're embedded in the Cacti UI (included from weathermap-cacti-plugin-editor.php), then authentication has happened. Enable the editor.
if (isset($cameFromHost) && $cameFromHost == true) {
    $editorEnabled = true;
} else {
    $cameFromHost = false;
}

$hostType = isset($hostType) ? $hostType : '';
$hostEditorURL = isset($hostEditorURL) ? $hostEditorURL : 'editor.php';
$hostPluginURL = isset($hostPluginURL) ? $hostPluginURL : '';

if (!$editorEnabled) {
    print "<p>The editor has not been enabled for standalone use yet.</p>";
    print "<h3>Cacti</h3><p>You <b>do not</b> need to do this to use the editor from within Cacti - just give users permission to edit maps in the <a href='../../user_admin.php'>User Management page</a>.</p>";
    print "<h3>Standalone Use</h3><p>For standalone use, you need to set ENABLED=true at the top of " . basename(__FILE__) . "</p>";
    print "<p>Before you do that, you should consider using FilesMatch (in Apache) or similar to limit who can access the editor. Not limiting access in some way can be a security risk. There is more information in the install guide section of the manual.</p>";
    exit;
}

$ui = new EditorUI();
$ui->moduleChecks();

chdir(dirname(__FILE__));

$ui->main($_REQUEST, $_COOKIE, $cameFromHost);
