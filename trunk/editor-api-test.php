<?php

require_once 'Console/Getopt.php';

require_once dirname(__FILE__).'/lib/Weathermap.class.php';
require_once dirname(__FILE__).'/lib/WeatherMapEditor.class.php';

if (!wm_module_checks()) {
	die ("Quitting: Module checks failed.\n");
}

$editor = new WeatherMapEditor();

print "Create a new map\n";
$editor->newConfig();
$editor->saveConfig("test-output-1.conf");

print "Add some nodes\n";
list($newname, $success, $log) = $editor->addNode(100,100);
print "  created $newname - $log\n";
list($newname, $success, $log) = $editor->addNode(100,200,"n1");
print "  $log\n";
list($newname, $success, $log) = $editor->addNode(200,100,"n2","n1");
print "  $log\n";
$editor->saveConfig("test-output-2.conf");

print "Add a link\n";
list($newname, $success, $log) = $editor->addLink("n1","n2");
print "  $log\n";
$editor->saveConfig("test-output-3.conf");

print "Add a via\n";
$editor->setLinkVia($newname, 120, 170);
$editor->saveConfig("test-output-4.conf");

print "Move a node\n";
$editor->moveNode("n1", 150, 150);
$editor->saveConfig("test-output-5.conf");


print "Clone two nodes\n";

list($success,$newname,$log) = $editor->cloneNode("n1");
if($success !== TRUE ) die( "Clone failed.");
print "  $log\n";

list($success,$newname,$log) = $editor->cloneNode("n2","mr_clone");
if($success !== TRUE ) die( "Clone failed.");
print "  $log\n";

$editor->saveConfig("test-output-6.conf");

