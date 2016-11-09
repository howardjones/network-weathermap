<?php

chdir('../../');
include_once "./include/auth.php";
include_once "./include/config.php";

include_once $config["library_path"] . "/database.php";

$weathermap_confdir = realpath(dirname(__FILE__) . '/configs');

// include the weathermap class so that we can get the version
include_once dirname(__FILE__) . "/lib/Weathermap.class.php";
include_once dirname(__FILE__) . "/lib/database.php";
include_once dirname(__FILE__) . "/lib/WeathermapManager.class.php";

$i_understand_file_permissions_and_how_to_fix_them = FALSE;
$my_name = "weathermap-cacti-plugin-mgmt.php";

$manager = new WeathermapManager(weathermap_get_pdo(), $weathermap_confdir);

$action = "";
if (isset($_POST['action'])) {
    $action = $_POST['action'];
} else if (isset($_GET['action'])) {
    $action = $_GET['action'];
}

switch ($action) {

    case 'dump_maps':
       	Header("Content-type: application/json"); 
	$data = array(
		"maps"=>$manager->getMaps(),
		"groups"=>$manager->getGroups()
	);
	print json_encode($data);
	break;

    case 'enable_poller_output':
        weathermap_setting_save(0, 'rrd_use_poller_output', 1);
        header("Location: ?action=map_settings&id=0");
        break;

    case 'group_update':
        $id = -1;
        $newname = "";
        if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
            $id = intval($_REQUEST['id']);
        }
        if (isset($_REQUEST['gname']) && (strlen($_REQUEST['gname']) > 0)) {
            $newname = $_REQUEST['gname'];
        }

        if ($id >= 0 && $newname != "") weathermap_group_update($id, $newname);
        if ($id < 0 && $newname != "") weathermap_group_create($newname);
        header("Location: ?action=groupadmin");

        break;

    case 'groupadmin_delete':

        $id = -1;

        if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
            $id = intval($_REQUEST['id']);
        }

        if ($id >= 1) {
            weathermap_group_delete($id);
        }
        header("Location: ?action=groupadmin");
        break;

    case 'group_form':

        $id = -1;

        include_once $config["base_path"] . "/include/top_header.php";
        if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
            $id = intval($_REQUEST['id']);
        }

        if ($id >= 0) {
            weathermap_group_form($id);
        }

        weathermap_footer_links();
        include_once $config["base_path"] . "/include/bottom_footer.php";
        break;

    case 'groupadmin':
        include_once $config["base_path"] . "/include/top_header.php";
        weathermap_group_editor();
        weathermap_footer_links();
        include_once $config["base_path"] . "/include/bottom_footer.php";
        break;

    case 'chgroup_update':
        $mapid = -1;
        $groupid = -1;

        if (isset($_REQUEST['map_id']) && is_numeric($_REQUEST['map_id'])) {
            $mapid = intval($_REQUEST['map_id']);
        }
        if (isset($_REQUEST['new_group']) && is_numeric($_REQUEST['new_group'])) {
            $groupid = intval($_REQUEST['new_group']);
        }

        if (($groupid > 0) && ($mapid >= 0)) {
            weathermap_set_group($mapid, $groupid);
        }

        header("Location: " . $my_name);
        break;

    case 'chgroup':
        if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
            include_once $config["base_path"] . "/include/top_header.php";
            weathermap_chgroup(intval($_REQUEST['id']));
            include_once $config["base_path"] . "/include/bottom_footer.php";
        } else {
            print "Something got lost back there.";
        }
        break;

    case 'map_settings_delete':
        $mapid = NULL;
        $settingid = NULL;
        if (isset($_REQUEST['mapid']) && is_numeric($_REQUEST['mapid'])) {
            $mapid = intval($_REQUEST['mapid']);
        }
        if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
            $settingid = intval($_REQUEST['id']);
        }

        if (!is_null($mapid) && !is_null($settingid)) {
            // create setting
            weathermap_setting_delete($mapid, $settingid);
        }
        header("Location: ?action=map_settings&id=" . $mapid);
        break;

// this is the save option from the map_settings_form
    case 'save':
        $mapid = NULL;
        $settingid = NULL;
        $name = '';
        $value = '';

        if (isset($_REQUEST['mapid']) && is_numeric($_REQUEST['mapid'])) {
            $mapid = intval($_REQUEST['mapid']);
        }
        if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
            $settingid = intval($_REQUEST['id']);
        }

        if (isset($_REQUEST['name']) && $_REQUEST['name']) {
            $name = $_REQUEST['name'];
        }
        if (isset($_REQUEST['value']) && $_REQUEST['value']) {
            $value = $_REQUEST['value'];
        }

        if (!is_null($mapid) && $settingid == 0) {
            // create setting
            weathermap_setting_save($mapid, $name, $value);
        } elseif (!is_null($mapid) && !is_null($settingid)) {
            // update setting
            weathermap_setting_update($mapid, $settingid, $name, $value);
        }
        header("Location: ?action=map_settings&id=" . $mapid);
        break;

    case 'map_settings_form':
        if (isset($_REQUEST['mapid']) && is_numeric($_REQUEST['mapid'])) {
            include_once $config["base_path"] . "/include/top_header.php";

            if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
                weathermap_map_settings_form(intval($_REQUEST['mapid']), intval($_REQUEST['id']));
            } else {
                weathermap_map_settings_form(intval($_REQUEST['mapid']));
            }

            weathermap_footer_links();
            include_once $config["base_path"] . "/include/bottom_footer.php";
        }
        break;
    case 'map_settings':
        if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
            include_once $config["base_path"] . "/include/top_header.php";
            weathermap_map_settings(intval($_REQUEST['id']));
            weathermap_footer_links();
            include_once $config["base_path"] . "/include/bottom_footer.php";
        }
        break;

    case 'perms_add_user':
        if (isset($_REQUEST['mapid']) && is_numeric($_REQUEST['mapid'])
            && isset($_REQUEST['userid']) && is_numeric($_REQUEST['userid'])
        ) {
            perms_add_user(intval($_REQUEST['mapid']), intval($_REQUEST['userid']));
            header("Location: ?action=perms_edit&id=" . intval($_REQUEST['mapid']));
        }
        break;
    case 'perms_delete_user':
        if (isset($_REQUEST['mapid']) && is_numeric($_REQUEST['mapid'])
            && isset($_REQUEST['userid']) && is_numeric($_REQUEST['userid'])
        ) {
            perms_delete_user($_REQUEST['mapid'], $_REQUEST['userid']);
            header("Location: ?action=perms_edit&id=" . $_REQUEST['mapid']);
        }
        break;
    case 'perms_edit':
        if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
            include_once $config["base_path"] . "/include/top_header.php";
            perms_list($_REQUEST['id']);
            include_once $config["base_path"] . "/include/bottom_footer.php";
        } else {
            print "Something got lost back there.";
        }
        break;


    case 'delete_map':
        if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) map_delete($_REQUEST['id']);
        header("Location: weathermap-cacti-plugin-mgmt.php");
        break;

    case 'deactivate_map':
        if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) map_deactivate($_REQUEST['id']);
        header("Location: " . $my_name);
        break;

    case 'activate_map':
        if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) map_activate($_REQUEST['id']);
        header("Location: " . $my_name);
        break;

    case 'move_map_up':
        if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id']))
            map_move($_REQUEST['id'], null, -1);

        header("Location: " . $my_name);
        break;
    case 'move_map_down':
        if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id']))
            map_move($_REQUEST['id'], null, +1);

        header("Location: " . $my_name);
        break;

    case 'move_group_up':
        if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])
        )
            weathermap_group_move(intval($_REQUEST['id']), -1);
        header("Location: ?action=groupadmin");
        break;
    case 'move_group_down':
        if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])
        )
            weathermap_group_move(intval($_REQUEST['id']), 1);
        header("Location: ?action=groupadmin");
        break;

    case 'viewconfig':
        include_once $config["base_path"] . "/include/top_graph_header.php";
        if (isset($_REQUEST['file'])) {
            preview_config($_REQUEST['file']);
        } else {
            print "No such file.";
        }
        include_once $config["base_path"] . "/include/bottom_footer.php";
        break;

    case 'addmap_picker':

        include_once $config["base_path"] . "/include/top_header.php";
        if (isset($_REQUEST['show']) && $_REQUEST['show'] == 'all') {
            addmap_picker(true);
        } else {
            addmap_picker(false);
        }
        include_once $config["base_path"] . "/include/bottom_footer.php";
        break;

    case 'addmap':
        if (isset($_REQUEST['file'])) {
            add_config($_REQUEST['file']);
            header("Location: " . $my_name);
        } else {
            print "No such file.";
        }

        break;

    case 'rebuildnow':

        include_once $config["base_path"] . "/include/top_header.php";

        print "<h3>REALLY Rebuild all maps?</h3><strong>NOTE: Because your Cacti poller process probably doesn't run as the same user as your webserver, it's possible this will fail with file permission problems even though the normal poller process runs fine. In some situations, it MAY have memory_limit problems, if your mod_php/ISAPI module uses a different php.ini to your command-line PHP.</strong><hr>";

        print "<p>It is recommended that you don't use this feature, unless you understand and accept the problems it may cause.</p>";
        print "<h4><a href=\"?action=rebuildnow2\">YES</a></h4>";
        print "<h1><a href=\"weathermap-cacti-plugin-mgmt.php\">NO</a></h1>";
        include_once $config["base_path"] . "/include/bottom_footer.php";
        break;

    case 'rebuildnow2':
        include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "Weathermap.class.php";
        include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "poller-common.php";

        include_once $config["base_path"] . "/include/top_header.php";
        print "<h3>Rebuilding all maps</h3><strong>NOTE: Because your Cacti poller process probably doesn't run as the same user as your webserver, it's possible this will fail with file permission problems even though the normal poller process runs fine. In some situations, it MAY have memory_limit problems, if your mod_php/ISAPI module uses a different php.ini to your command-line PHP.</strong><hr><pre>";
        weathermap_run_maps(dirname(__FILE__));
        print "</pre>";
        print "<hr /><h3>Done.</h3>";
        include_once $config["base_path"] . "/include/bottom_footer.php";

        break;

    // by default, just list the map setup
    default:
        include_once $config["base_path"] . "/include/top_header.php";
        maplist();
        weathermap_footer_links();
        include_once $config["base_path"] . "/include/bottom_footer.php";
        break;
}

///////////////////////////////////////////////////////////////////////////

function weathermap_footer_links()
{
    global $colors;
    global $WEATHERMAP_VERSION;
    print '<br />';
    html_start_box("<center><a target=\"_blank\" class=\"linkOverDark\" href=\"docs/\">Local Documentation</a> -- <a target=\"_blank\" class=\"linkOverDark\" href=\"http://www.network-weathermap.com/\">Weathermap Website</a> -- <a target=\"_target\" class=\"linkOverDark\" href=\"weathermap-cacti-plugin-editor.php\">Weathermap Editor</a> -- This is version $WEATHERMAP_VERSION</center>", "78%", $colors["header"], "2", "center", "");
    html_end_box();
}


function maplist_warnings()
{
    global $manager;

    if (!wm_module_checks()) {

        print '<div align="center" class="wm_warning"><p>';

        print "<b>Required PHP extensions are not present in your mod_php/ISAPI PHP module. Please check your PHP setup to ensure you have the GD extension installed and enabled.</b><p>";
        print "If you find that the weathermap tool itself is working, from the command-line or Cacti poller, then it is possible that you have two different PHP installations. The Editor uses the same PHP that webpages on your server use, but the main weathermap tool uses the command-line PHP interpreter.<p>";
        print "<p>You should also run <a href=\"check.php\">check.php</a> to help make sure that there are no problems.</p><hr/>";


        print '</p></div>';
        exit();
    }

    $tables = weathermap_get_table_list(weathermap_get_pdo());
    if (!in_array('weathermap_maps', $tables)) {
        print '<div align="center" class="wm_warning"><p>';
        print 'The weathermap_maps table is missing completely from the database. Something went wrong with the installation process.';
        print '</p></div>';
    }

    $boost_enabled = $manager->getAppSetting('boost_rrd_update_enable', 'off');

    if ($boost_enabled == 'on') {

        $has_global_poller_output = $manager->getMapSettingByName(0, "rrd_use_poller_output", false);

        if (!$has_global_poller_output) {
            print '<div align="center" class="wm_warning"><p>';
            print "You are using the Boost plugin to update RRD files. Because this delays data being written to the files, it causes issues with Weathermap updates. You can resolve this by using Weathermap's 'poller_output' support, which grabs data directly from the poller. <a href=\"?action=enable_poller_output\">You can enable that globally by clicking here</a>";
            print '</p></div>';
        }
    }

    $last_started = $manager->getAppSetting("weathermap_last_started_file", true);
    $last_finished = $manager->getAppSetting("weathermap_last_finished_file", true);
    $last_start_time = intval($manager->getAppSetting("weathermap_last_start_time", true));
    $last_finish_time = intval($manager->getAppSetting("weathermap_last_finish_time", true));
    $poller_interval = intval($manager->getAppSetting("poller_interval"));

    maplist_warnings();


    if (($last_finish_time - $last_start_time) > $poller_interval) {
        if (($last_started != $last_finished) && ($last_started != "")) {
            print '<div align="center" class="wm_warning"><p>';
            print "Last time it ran, Weathermap did NOT complete it's run. It failed during processing for '$last_started'. ";
            print "This <strong>may</strong> have affected other plugins that run during the poller process. </p><p>";
            print "You should either disable this map, or fault-find. Possible causes include memory_limit issues. The log may have more information.";
            print '</p></div>';
        }
    }

}


function maplist()
{
    global $colors;
    global $i_understand_file_permissions_and_how_to_fix_them;
    global $manager;


    html_start_box("<strong>Weathermaps</strong>", "78%", $colors["header"], "3", "center", "?action=addmap_picker");

    html_header(array("Config File", "Title", "Group", "Active", "Settings", "Sort Order", "Accessible By", ""));

    $userlist = $manager->getUserList();
    $users[0] = 'Anyone';

    foreach ($userlist as $user) {
        $users[$user->id] = $user->username;
    }

    $i = 0;

    $maps = $manager->getMapsWithGroups();

    $previous_id = -2;
    $had_warnings = 0;
    if (is_array($maps)) {
        form_alternate_row_color($colors["alternate"], $colors["light"], $i);
        print "<td>ALL MAPS</td><td>(special settings for all maps)</td><td></td><td></td>";

        print "<td><a href='?action=map_settings&id=0'>";
        $setting_count = $manager->getMapSettingCount(0, 0);
        if ($setting_count > 0) {
            print $setting_count . " special";
            if ($setting_count > 1) {
                print "s";
            }
        } else {
            print "standard";
        }
        print "</a>";

        print "</td>";
        print "<td></td>";
        print "<td></td>";
        print "<td></td>";
        print "</tr>";
        $i++;

        foreach ($maps as $map) {
            form_alternate_row_color($colors["alternate"], $colors["light"], $i);

            print '<td><a title="Click to start editor with this file" href="weathermap-cacti-plugin-editor.php?action=nothing&mapname=' . htmlspecialchars($map->configfile) . '">' . htmlspecialchars($map->configfile) . '</a>';
            if ($map->warncount > 0) {
                $had_warnings++;

                print '<a href="../../utilities.php?tail_lines=500&message_type=2&action=view_logfile&filter=' . urlencode($map->configfile) . '" title="Check cacti.log for this map"><img border=0 src="images/exclamation.png" title="' . $map->warncount . ' warnings last time this map was run. Check your logs.">' . $map->warncount . "</a>";
            }
            print "</td>";

            print '<td>' . htmlspecialchars($map->titlecache) . '</td>';
            print '<td><a title="Click to change group" href="?action=chgroup&id=' . $map->id . '">' . htmlspecialchars($map->groupname) . '</a></td>';

            if ($map->active == 'on') {
                print '<td class="wm_enabled"><a title="Click to Deactivate" href="?action=deactivate_map&id=' . $map->id . '"><font color="green">Yes</font></a>';
            } else {
                print '<td class="wm_disabled"><a title="Click to Activate" href="?action=activate_map&id=' . $map->id . '"><font color="red">No</font></a>';
            }
            print "<td>";

            print "<a href='?action=map_settings&id=" . $map->id . "'>";
            $setting_count = $manager->getMapSettingCount($map->id);
            if ($setting_count > 0) {
                print $setting_count . " special";
                if ($setting_count > 1) {
                    print "s";
                }
            } else {
                print "standard";
            }
            print "</a>";

            print "</td>";

            print '</td>';

            print '<td>';

            print '<a href="?action=move_map_up&order=' . $map->sortorder . '&id=' . $map->id . '"><img src="../../images/move_up.gif" width="14" height="10" border="0" alt="Move Map Up" title="Move Map Up"></a>';
            print '<a href="?action=move_map_down&order=' . $map->sortorder . '&id=' . $map->id . '"><img src="../../images/move_down.gif" width="14" height="10" border="0" alt="Move Map Down" title="Move Map Down"></a>';
            print $map->sortorder;

            print "</td>";

            print '<td>';

            $userlist = $manager->
            getMapAuthUsers($map->id);
            $mapusers = array();
            foreach ($userlist as $user) {
                if (array_key_exists($user->userid, $users)) {
                    $mapusers[] = $users[$user->userid];
                }
            }

            print '<a title="Click to edit permissions" href="?action=perms_edit&id=' . $map->id . '">';
            if (count($mapusers) == 0) {
                print "(no users)";
            } else {
                print join(", ", $mapusers);
            }
            print '</a>';

            print '</td>';
            print '<td>';
            print '<a href="?action=delete_map&id=' . $map->id . '"><img src="../../images/delete_icon.gif" width="10" height="10" border="0" alt="Delete Map" title="Delete Map"></a>';
            print '</td>';

            print '</tr>';
            $i++;
        }
    }

    if ($i == 0) {
        print "<tr><td><em>No Weathermaps Configured</em></td></tr>\n";
    }

    html_end_box();

    $last_stats = $manager->getAppSetting("weathermap_last_stats", "");

    if ($last_stats != "") {
        print "<div align='center'><strong>Last Completed Run:</strong> $last_stats</div>";
    } else {

    }

    if ($had_warnings > 0) {
        print '<div align="center" class="wm_warning">' . $had_warnings . ' of your maps had warnings last time ' . ($had_warnings > 1 ? "they" : "it") . ' ran. You can try to find these in your Cacti log file or by clicking on the warning sign next to that map (you might need to increase the log line count).</div>';
    }

    print "<div align='center'>";
    print "<a href='?action=groupadmin'><img src='images/button_editgroups.png' border=0 alt='Edit Groups' /></a>";
    print "&nbsp;<a href='../../settings.php?tab=misc'><img src='images/button_settings.gif' border=0 alt='Settings' /></a>";
    if ($i > 0 && $i_understand_file_permissions_and_how_to_fix_them) {
        print '<br /><a href="?action=rebuildnow"><img src="images/btn_recalc.png" border="0" alt="Rebuild All Maps Right Now"><br />(Experimental - You should NOT need to use this normally)</a><br />';
    }
    print "</div>";

}

function addmap_picker($show_all = false)
{
    global $weathermap_confdir;
    global $colors;
    global $manager;

    $loaded = array();
    $flags = array();

    // find out what maps are already in the database, so we can skip those
    $existing_maps = $manager->getMaps();
    if (is_array($existing_maps)) {
        foreach ($existing_maps as $map) {
            $loaded[] = $map->configfile;

        }
    }

    html_start_box("<strong>Available Weathermap Configuration Files</strong>", "78%", $colors["header"], "1", "center", "");

    if (is_dir($weathermap_confdir)) {
        $n = 0;
        $dh = opendir($weathermap_confdir);
        if ($dh) {
            $i = 0;
            $skipped = 0;
            html_header(array("", "", "Config File", "Title", ""), 2);

            while ($file = readdir($dh)) {
                $realfile = $weathermap_confdir . '/' . $file;

                // skip .-prefixed files like .htaccess, since it seems
                // that otherwise people will add them as map config files.
                // and the index.php too - for the same reason
                if (substr($file, 0, 1) != '.' && $file != "index.php") {
                    $used = in_array($file, $loaded);
                    $flags[$file] = '';
                    if ($used) $flags[$file] = 'USED';

                    if (is_file($realfile)) {
                        if ($used && !$show_all) {
                            $skipped++;
                        } else {
                            $title = $manager->extractMapTitle($realfile);
                            $titles[$file] = $title;
                            $i++;
                        }
                    }
                }
            }
            closedir($dh);

            if ($i > 0) {
                ksort($titles);

                $i = 0;
                foreach ($titles as $file => $title) {
                    $title = $titles[$file];
                    form_alternate_row_color($colors["alternate"], $colors["light"], $i);
                    print '<td><a href="?action=addmap&amp;file=' . $file . '" title="Add the configuration file">Add</a></td>';
                    print '<td><a href="?action=viewconfig&amp;file=' . $file . '" title="View the configuration file in a new window" target="_blank">View</a></td>';
                    print '<td>' . htmlspecialchars($file);
                    if ($flags[$file] == 'USED') print ' <b>(USED)</b>';
                    print '</td>';
                    print '<td><em>' . htmlspecialchars($title) . '</em></td>';
                    print '</tr>';
                    $i++;
                }
            }

            if (($i + $skipped) == 0) {
                print "<tr><td>No files were found in the configs directory.</td></tr>";
            }

            if (($i == 0) && $skipped > 0) {
                print "<tr><td>($skipped files weren't shown because they are already in the database</td></tr>";
            }
        } else {
            print "<tr><td>Can't open $weathermap_confdir to read - you should set it to be readable by the webserver.</td></tr>";
        }
    } else {
        print "<tr><td>There is no directory named $weathermap_confdir - you will need to create it, and set it to be readable by the webserver. If you want to upload configuration files from inside Cacti, then it should be <i>writable</i> by the webserver too.</td></tr>";
    }

    html_end_box();

    if ($skipped > 0) {
        print "<p align=center>Some files are not shown because they have already been added. You can <a href='?action=addmap_picker&show=all'>show these files too</a>, if you need to.</p>";
    }
    if ($show_all) {
        print "<p align=center>Some files are shown even though they have already been added. You can <a href='?action=addmap_picker'>hide those files too</a>, if you need to.</p>";
    }

}

function preview_config($file)
{
    global $weathermap_confdir;
    global $colors;

    chdir($weathermap_confdir);

    $path_parts = pathinfo($file);
    $file_dir = realpath($path_parts['dirname']);

    if ($file_dir != $weathermap_confdir) {
        // someone is trying to read arbitrary files?
        // print "$file_dir != $weathermap_confdir";
        print "<h3>Path mismatch</h3>";
    } else {
        html_start_box("<strong>Preview of $file</strong>", "98%", $colors["header"], "3", "center", "");

        print '<tr><td valign="top" bgcolor="#' . $colors["light"] . '" class="textArea">';
        print '<pre>';
        $realfile = $weathermap_confdir . '/' . $file;
        if (is_file($realfile)) {
            $fd = fopen($realfile, "r");
            while (!feof($fd)) {
                $buffer = fgets($fd, 4096);
                print $buffer;
            }
            fclose($fd);
        }
        print '</pre>';
        print '</td></tr>';
        html_end_box();
    }
}


function perms_list($id)
{
    global $colors;
    global $manager;

    $map = $manager->getMap($id);
    $title = $map->titlecache;

    $users = $manager->getUserList(true);
    $auth = $manager->getMapAuth($id);

    $mapuserids = array();

    // build an array of userids that are allowed to see this map (and that actually exist)
    foreach ($auth as $user) {
        if (isset($users[$user->userid])) {
            $mapuserids[] = $user->userid;
        }
    }

    // now build the list of users that exist but aren't currently allowed (for the picklist)
    $candidate_users = array();
    foreach ($users as $uid => $user) {
        if (!in_array($uid, $mapuserids)) {
            $candidate_users [] = $user;
        }
    }

    html_start_box("<strong>Edit permissions for Weathermap $id: $title</strong>", "70%", $colors["header"], "2",
        "center", "");
    html_header(array("Username", ""));

    $n = 0;
    foreach ($mapuserids as $user) {
        form_alternate_row_color($colors["alternate"], $colors["light"], $n);
        print "<td>" . htmlspecialchars($users[$user]->username) . "</td>";
        print '<td><a href="?action=perms_delete_user&mapid=' . $id . '&userid=' . $user . '"><img src="../../images/delete_icon.gif" width="10" height="10" border="0" alt="Remove permissions for this user to see this map"></a></td>';

        print "</tr>";
        $n++;
    }

    if (sizeof($mapuserids) == 0) {
        print "<tr><td><em><strong>nobody</strong> can see this map</em></td></tr>";
    }

    html_end_box();

    html_start_box("", "70%", $colors["header"], "3", "center", "");
    print "<tr>";
    if (sizeof($candidate_users) == 0) {
        print "<td><em>There aren't any users left to add!</em></td></tr>";
    } else {
        print "<td><form action=\"\">Allow <input type=\"hidden\" name=\"action\" value=\"perms_add_user\"><input type=\"hidden\" name=\"mapid\" value=\"$id\"><select name=\"userid\">";
        foreach ($candidate_users as $user) {
            printf("<option value=\"%s\">%s</option>\n", $user->id, $user->username);
        }

        print "</select> to see this map <input type=\"submit\" value=\"Update\"></form></td>";
        print "</tr>";
    }
    html_end_box();
}

function weathermap_map_settings($id)
{
    global $colors;
    global $manager;

    if ($id == 0) {
        $title = "Additional settings for ALL maps";
        $nonemsg = "There are no settings for all maps yet. You can add some by clicking Add up in the top-right, or choose a single map from the management screen to add settings for that map.";
        $type = "global";
        $settingrows = $manager->getMapSettings(0);
    } elseif ($id < 0) {
        $group_id = -intval($id);
        $group = $manager->getGroup($group_id);

        $title = "Edit per-map settings for Group " . $group->id . ": " . $group->name;
        $nonemsg = "There are no per-group settings for this group yet. You can add some by clicking Add up in the top-right.";
        $type = "group";
        $settingrows = $manager->getMapSettings(-$group_id);

        print "<p>All maps in this group are also affected by the following GLOBAL settings (group overrides global, map overrides group, but BOTH override SET commands within the map config file):</p>";
        weathermap_readonly_settings(0, "Global Settings");

    } else {
        $map = $manager->getMap($id);
        $group = $manager->getGroup($map->group_id);

        $title = "Edit per-map settings for Weathermap $id: " . $map->titlecache;
        $nonemsg = "There are no per-map settings for this map yet. You can add some by clicking Add up in the top-right.";
        $type = "map";
        $settingrows = $manager->getMapSettings(intval($id));

        print "<p>This map is also affected by the following GLOBAL and GROUP settings (group overrides global, map overrides group, but BOTH override SET commands within the map config file):</p>";

        weathermap_readonly_settings(0, "Global Settings");

        weathermap_readonly_settings(-$map->group_id, "Group Settings (" . htmlspecialchars($group->name) . ")");
    }

    html_start_box("<strong>$title</strong>", "70%", $colors["header"], "2", "center",
        "?action=map_settings_form&mapid=" . intval($id));
    html_header(array("", "Name", "Value", ""));

    $n = 0;

    if (is_array($settingrows)) {
        if (sizeof($settingrows) > 0) {
            foreach ($settingrows as $setting) {
                form_alternate_row_color($colors["alternate"], $colors["light"], $n);
                print '<td><a href="?action=map_settings_form&mapid=' . $id . '&id=' . intval($setting->id) . '"><img src="../../images/graph_properties.gif" width="16" height="16" border="0" alt="Edit this definition">Edit</a></td>';
                print "<td>" . htmlspecialchars($setting->optname) . "</td>";
                print "<td>" . htmlspecialchars($setting->optvalue) . "</td>";
                print '<td><a href="?action=map_settings_delete&mapid=' . $id . '&id=' . intval($setting->id) . '"><img src="../../images/delete_icon_large.gif" width="12" height="12" border="0" alt="Remove this definition from this map"></a></td>';
                print "</tr>";
                $n++;
            }
        } else {
            print "<tr>";
            print "<td colspan=2>$nonemsg</td>";
            print "</tr>";
        }
    }

    html_end_box();

    print "<div align=center>";
    if ($type == "group") {
        print "<a href='?action=groupadmin'>Back to Group Admin</a>";
    }
    if ($type == "global") {
        print "<a href='?action='>Back to Map Admin</a>";
    }
    print "</div>";
}

function weathermap_readonly_settings($id, $title = "Settings")
{
    global $colors;
    global $manager;

    if ($id == 0) {
        $settings = $manager->getMapSettings(0);
    }
    if ($id < 0) {
        $settings = $manager->getMapSettings(-intval($id));
    }
    if ($id > 0) {
        $settings = $manager->getMapSettings(intval($id));
    }

    html_start_box("<strong>$title</strong>", "70%", $colors["header"], "2", "center", "");
    html_header(array("", "Name", "Value", ""));

    $n = 0;

    if (sizeof($settings) > 0) {
        foreach ($settings as $setting) {
            form_alternate_row_color($colors["alternate"], $colors["light"], $n);
            print "<td></td>";
            print "<td>" . htmlspecialchars($setting->optname) . "</td><td>" . htmlspecialchars($setting->optvalue) . "</td>";
            print "<td></td>";
            print "</tr>";
            $n++;
        }
    } else {
        form_alternate_row_color($colors["alternate"], $colors["light"], $n);
        print "<td colspan=4><em>No Settings</em></td>";
        print "</tr>";
    }

    html_end_box();
}

function weathermap_map_settings_form($mapId = 0, $settingId = 0)
{
    global $colors, $config;
    global $manager;

    if ($mapId < 0) {
        $item = $manager->getGroup(-$mapId);
        $title = $item->name;
    }
    if ($mapId > 0) {
        $item = $manager->getMap($mapId);
        $title = $item->titlecache;
    }

    $name = "";
    $value = "";

    if ($settingId != 0) {

        $setting = $manager->getMapSettingById($settingId);

        if ($setting !== false) {
            $name = $setting->optname;
            $value = $setting->optvalue;
        }
    }

    # print "$mapid $settingid |$name| |$value|";

    $values_ar = array();

    $field_ar = array(
        "mapid" => array("friendly_name" => "Map ID", "method" => "hidden_zero", "value" => $mapId),
        "id" => array("friendly_name" => "Setting ID", "method" => "hidden_zero", "value" => $settingId),
        "name" => array(
            "friendly_name" => "Name",
            "method" => "textbox",
            "max_length" => 128,
            "description" => "The name of the map-global SET variable",
            "value" => $name
        ),
        "value" => array(
            "friendly_name" => "Value",
            "method" => "textbox",
            "max_length" => 128,
            "description" => "What to set it to",
            "value" => $value
        )
    );

    $action = "Edit";
    if ($settingId == 0) {
        $action = "Create";
    }

    if ($mapId == 0) {
        $title = "setting for ALL maps";
    } elseif ($mapId < 0) {
        $groupId = -$mapId;
        $title = "per-group setting for Group $groupId: $title";
    } else {
        $title = "per-map setting for Weathermap $mapId: $title";
    }

    html_start_box("<strong>$action $title</strong>", "98%", $colors["header"], "3", "center", "");
    draw_edit_form(array("config" => $values_ar, "fields" => $field_ar));
    html_end_box();

    form_save_button("?action=map_settings&id=" . $mapId);

}


function weathermap_chgroup($id)
{
    global $colors;
    global $manager;

    $map = $manager->getMap($id);
    $title = $map->titlecache;
    $curgroup = $map->group_id;

    $n = 0;

    print "<form>";
    print "<input type=hidden name='map_id' value='" . $id . "'>";
    print "<input type=hidden name='action' value='chgroup_update'>";
    html_start_box("<strong>Edit map group for Weathermap $id: $title</strong>", "70%", $colors["header"], "2", "center", "");

    form_alternate_row_color($colors["alternate"], $colors["light"], $n++);
    print "<td><strong>Choose an existing Group:</strong><select name='new_group'>";

    $groups = $manager->getGroups();

    foreach ($groups as $grp) {
        print "<option ";
        if ($grp->id == $curgroup) print " SELECTED ";
        print "value=" . $grp->id . ">" . htmlspecialchars($grp->name) . "</option>";
    }

    print "</select>";
    print '<input type="image" src="../../images/button_save.gif"  border="0" alt="Change Group" title="Change Group" />';
    print "</td>";
    print "</tr>\n";
    print "<tr><td></td></tr>";

    print "<tr><td><p>or create a new group in the <strong><a href='?action=groupadmin'>group management screen</a></strong></p></td></tr>";

    html_end_box();
    print "</form>\n";
}

function weathermap_group_form($id = 0)
{
    global $colors, $config;
    global $manager;

    $groupName = "";
    // if id==0, it's an Add, otherwise it's an editor.
    if ($id == 0) {
        print "Adding a group...";
    } else {
        print "Editing group $id\n";
        $group = $manager->getGroup($id);
        $groupName = $group->name;
    }

    print "<form action=weathermap-cacti-plugin-mgmt.php>\n<input type=hidden name=action value=group_update />\n";

    print "Group Name: <input name=gname value='" . htmlspecialchars($groupName) . "'/>\n";
    if ($id > 0) {
        print "<input type=hidden name=id value=$id />\n";
        print "Group Name: <input type=submit value='Update' />\n";
    } else {
        print "Group Name: <input type=submit value='Add' />\n";
    }

    print "</form>\n";

}

function weathermap_group_editor()
{
    global $colors, $config;
    global $manager;

    html_start_box("<strong>Edit Map Groups</strong>", "70%", $colors["header"], "2", "center",
        "?action=group_form&id=0");
    html_header(array("", "Group Name", "Settings", "Sort Order", ""));

//    $groups = db_fetch_assoc("select * from weathermap_groups order by sortorder");
    $groups = $manager->getGroups();
    $n = 0;

    if (is_array($groups)) {
        if (sizeof($groups) > 0) {
            foreach ($groups as $group) {
                form_alternate_row_color($colors["alternate"], $colors["light"], $n);
                print '<td><a href="?action=group_form&id=' . intval($group->id) . '"><img src="../../images/graph_properties.gif" width="16" height="16" border="0" alt="Rename This Group" title="Rename This Group">Rename</a></td>';
                print "<td>" . htmlspecialchars($group->name) . "</td>";

                print "<td>";

                print "<a href='?action=map_settings&id=-" . $group->id . "'>";
                $setting_count = $manager->getMapSettingCount(0, $group->id);
                if ($setting_count > 0) {
                    print $setting_count . " special";
                    if ($setting_count > 1) {
                        print "s";
                    }
                } else {
                    print "standard";
                }
                print "</a>";

                print "</td>";


                print '<td>';

                print '<a href="?action=move_group_up&id=' . $group->id . '"><img src="../../images/move_up.gif" width="14" height="10" border="0" alt="Move Group Up" title="Move Group Up"></a>';
                print '<a href="?action=move_group_down&id=' . $group->id . '"><img src="../../images/move_down.gif" width="14" height="10" border="0" alt="Move Group Down" title="Move Group Down"></a>';
                print $group->sortorder;
                print "</td>";

                print '<td>';
                if ($group->id > 1) {
                    print '<a href="?action=groupadmin_delete&id=' . intval($group->id) . '"><img src="../../images/delete_icon.gif" width="10" height="10" border="0" alt="Remove this definition from this map"></a>';
                }
                print '</td>';

                print "</tr>";
                $n++;
            }
        } else {
            print "<tr>";
            print "<td colspan=2>No groups are defined.</td>";
            print "</tr>";
        }
    }

    html_end_box();
}

function weathermap_group_create($newname)
{
    global $manager;

    $manager->createGroup($newname);
}

function weathermap_group_update($id, $newname)
{
    global $manager;

    $manager->renameGroup($id, $newname);
}

function weathermap_group_delete($id)
{
    global $manager;

    $manager->deleteGroup($id);
}

function weathermap_setting_save($mapid, $name, $value)
{
    global $manager;

    $manager->saveMapSetting($mapid, $name, $value);
}

function weathermap_setting_update($mapid, $settingid, $name, $value)
{
    global $manager;

    $manager->updateMapSetting($settingid, $name, $value);
}

function weathermap_setting_delete($mapid, $settingid)
{
    global $manager;

    $manager->deleteMapSetting($mapid, $settingid);
}

function map_deactivate($id)
{
    global $manager;

    $manager->disableMap($id);
}

function map_activate($id)
{
    global $manager;

    $manager->activateMap($id);
}

function map_delete($id)
{
    global $manager;

    $manager->deleteMap($id);
}

function weathermap_set_group($mapid, $groupid)
{
    global $manager;

    $manager->setMapGroup($mapid, $groupid);
}

function perms_add_user($mapid, $userid)
{
    global $manager;
    $manager->addPermission($mapid, $userid);
}

function perms_delete_user($mapid, $userid)
{
    global $manager;
    $manager->removePermission($mapid, $userid);
}

// Repair the sort order column (for when something is deleted or inserted, or moved between groups)
// our primary concern is to make the sort order consistent, rather than any special 'correctness'
function map_resort()
{
    global $manager;

    $manager->resortMaps();
}

// Repair the sort order column (for when something is deleted or inserted)
function weathermap_group_resort()
{
    global $manager;

    $manager->resortGroups();
}

function map_move($mapid, $junk, $direction)
{
    global $manager;

    $manager->moveMap($mapid, $direction);
}

function weathermap_group_move($id, $direction)
{
    global $manager;

    $manager->moveGroup($id, $direction);
}


function add_config($file)
{
    global $weathermap_confdir;
    global $colors;
    global $manager;

    $manager->addMap($file);
}


// vim:ts=4:sw=4:
