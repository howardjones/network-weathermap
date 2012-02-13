<?php
$guest_account = true;

chdir('../../');
include_once("./include/auth.php");
// include_once("./include/config.php");

// include the weathermap class so that we can get the version
include_once(dirname(__FILE__) . "/Weathermap.class.php");

$action = "";

if (isset($_POST['action'])) {
    $action = $_POST['action'];
} else if (isset($_GET['action'])) {
    $action = $_GET['action'];
}

switch ($action) {
    case 'viewthumb': // FALL THROUGH
    case 'viewimage':
        $id = -1;

        if (isset($_REQUEST['id'])
            && (!is_numeric($_REQUEST['id']) || strlen($_REQUEST['id']) == 20)) {
            $id = weathermap_translate_id($_REQUEST['id']);
        }

        if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
            $id = intval($_REQUEST['id']);
        }

        if ($id >= 0) {
            $imageformat = strtolower(read_config_option("weathermap_output_format"));

            $userid = (isset($_SESSION["sess_user_id"])
                ? intval($_SESSION["sess_user_id"]) : 1);
            $map =
                db_fetch_assoc(
                    "select weathermap_maps.* from weathermap_auth,weathermap_maps where weathermap_maps.id=weathermap_auth.mapid and active='on' and (userid="
                . $userid . " or userid=0) and weathermap_maps.id=" . $id);

            if (sizeof($map)) {
                $imagefile =
                    dirname(__FILE__) . '/output/' . '/' . $map[0]['filehash'] . "."
                    . $imageformat;

                if ($action == 'viewthumb')
                    $imagefile =
                        dirname(__FILE__) . '/output/' . $map[0]['filehash'] . ".thumb."
                        . $imageformat;

                $orig_cwd = getcwd();
                chdir(dirname(__FILE__));

                header('Content-type: image/png');

                // readfile_chunked($imagefile);
                readfile($imagefile);

                dir($orig_cwd);
            } else {
            // no permission to view this map
            }
        }

        break;

    case 'viewmapcycle':
        include_once($config["base_path"] . "/include/top_graph_header.php");
        print
            "<div id=\"overDiv\" style=\"position:absolute; visibility:hidden; z-index:1000;\"></div>\n";
        print
            "<script type=\"text/javascript\" src=\"overlib.js\"><!-- overLIB (c) Erik Bosrup --></script> \n";
        
        $groupid = -1;
        if ( (isset($_REQUEST['group']) && is_numeric($_REQUEST['group'] ) )) {
            $groupid = intval($_REQUEST['group']);
        }
        
        weathermap_fullview(true,false,$groupid);
        weathermap_versionbox();

        include_once($config["base_path"] . "/include/bottom_footer.php");
        break;

    case 'viewmap':
        include_once($config["base_path"] . "/include/top_graph_header.php");
        print
            "<div id=\"overDiv\" style=\"position:absolute; visibility:hidden; z-index:1000;\"></div>\n";
        print
            "<script type=\"text/javascript\" src=\"overlib.js\"><!-- overLIB (c) Erik Bosrup --></script> \n";

        $id = -1;

        if (isset($_REQUEST['id'])
            && (!is_numeric($_REQUEST['id']) || strlen($_REQUEST['id']) == 20)) {
            $id = weathermap_translate_id($_REQUEST['id']);
        }

        if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
            $id = intval($_REQUEST['id']);
        }

        if ($id >= 0) {
            weathermap_singleview($id);
        }

        weathermap_versionbox();

        include_once($config["base_path"] . "/include/bottom_footer.php");
        break;

    default:
        include_once($config["base_path"] . "/include/top_graph_header.php");
        print
            "<div id=\"overDiv\" style=\"position:absolute; visibility:hidden; z-index:1000;\"></div>\n";
        print
            "<script type=\"text/javascript\" src=\"overlib.js\"><!-- overLIB (c) Erik Bosrup --></script> \n";

        $group_id = -1;

        if (isset($_REQUEST['group_id']) && (is_numeric($_REQUEST['group_id']))) {
            $group_id = intval($_REQUEST['group_id']);
            $_SESSION['wm_last_group'] = $group_id;
        } else {
            if (isset($_SESSION['wm_last_group'])) {
                $group_id = intval($_SESSION['wm_last_group']);
            }
        }

        $tabs = weathermap_get_valid_tabs();
        $tab_ids = array_keys($tabs);

        if (($group_id == -1) && (sizeof($tab_ids) > 0)) {
            $group_id = $tab_ids[0];
        }

        if (read_config_option("weathermap_pagestyle") == 0) {
            weathermap_thumbview($group_id);
        }

        if (read_config_option("weathermap_pagestyle") == 1) {
            weathermap_fullview(false, false, $group_id);
        }

        if (read_config_option("weathermap_pagestyle") == 2) {
            weathermap_fullview(false, true, $group_id);
        }

        weathermap_versionbox();
        include_once($config["base_path"] . "/include/bottom_footer.php");
        break;
}

function weathermap_cycleview() { }

function weathermap_singleview($mapid)
{
    global $colors;

    $is_wm_admin = false;

    $outdir = dirname(__FILE__) . '/output/';
    $confdir = dirname(__FILE__) . '/configs/';

    $userid = (isset($_SESSION["sess_user_id"]) ? intval($_SESSION["sess_user_id"]) : 1);
    $map =
        db_fetch_assoc(
            "select weathermap_maps.* from weathermap_auth,weathermap_maps where weathermap_maps.id=weathermap_auth.mapid and active='on' and (userid="
        . $userid . " or userid=0) and weathermap_maps.id=" . $mapid);

    if (sizeof($map)) {
# print do_hook_function ('weathermap_page_top', array($map[0]['id'], $map[0]['titlecache']) );
        print do_hook_function('weathermap_page_top', '');

        $htmlfile = $outdir . $map[0]['filehash'] . ".html";
        $maptitle = $map[0]['titlecache'];

        if ($maptitle == '')
            $maptitle = "Map for config file: " . $map[0]['configfile'];

        weathermap_mapselector($mapid);

        html_graph_start_box(1, true);
?>

        <tr bgcolor = "<?php print $colors["panel"];?>"><td>
                <table width = "100%" cellpadding = "0" cellspacing = "0">
                    <tr><td class = "textHeader" nowrap><?php
                            print $maptitle;

                            if ($is_wm_admin) {

                                print "<span style='font-size: 80%'>";
                                print
                                    "[ <a href='weathermap-cacti-plugin-mgmt.php?action=map_settings&id="
                                    . $mapid . "'>Map Settings</a> |";
                                print
                                    "<a href='weathermap-cacti-plugin-mgmt.php?action=perms_edit&id="
                                    . $mapid . "'>Map Permissions</a> |";
                                print "<a href=''>Edit Map</a> ]";
                                print "</span>";
                            }
                            ?></td>
                    </tr>
                </table></td>
        </tr>

<?php
                        print "<tr><td>";

                        if (file_exists($htmlfile)) {
                            include($htmlfile);
                        } else {
                            print
                                "<div align=\"center\" style=\"padding:20px\"><em>This map hasn't been calculated yet.";

                            global $config, $user_auth_realms, $user_auth_realm_filenames;
                            $realm_id2 = 0;

                            if (isset($user_auth_realm_filenames[basename(
                                'weathermap-cacti-plugin.php')])) {
                                $realm_id2 = $user_auth_realm_filenames[basename(
                                    'weathermap-cacti-plugin.php')];
                            }

                            $userid = (isset($_SESSION["sess_user_id"])
                                ? intval($_SESSION["sess_user_id"]) : 1);

                            if ((db_fetch_assoc("select user_auth_realm.realm_id from user_auth_realm where user_auth_realm.us
				er_id='" . $userid . "' and user_auth_realm.realm_id='$realm_id2'"))
                                || (empty($realm_id2))) {

                                print
                                    " (If this message stays here for more than one poller cycle, then check your cacti.log file for errors!)";
                            }
                            print "</em></div>";
                        }
                        print "</td></tr>";
                        html_graph_end_box();
    }
}

function weathermap_show_manage_tab()
{
    global $config, $user_auth_realms, $user_auth_realm_filenames;
    $realm_id2 = 0;

    if (isset($user_auth_realm_filenames['weathermap-cacti-plugin-mgmt.php'])) {
        $realm_id2 = $user_auth_realm_filenames['weathermap-cacti-plugin-mgmt.php'];
    }
    $userid = (isset($_SESSION["sess_user_id"]) ? intval($_SESSION["sess_user_id"]) : 1);

    if ((db_fetch_assoc(
        "select user_auth_realm.realm_id from user_auth_realm where user_auth_realm.user_id='"
        . $userid . "' and user_auth_realm.realm_id='$realm_id2'"))
        || (empty($realm_id2))) {

        print '<a href="' . $config['url_path']
            . 'plugins/weathermap/weathermap-cacti-plugin-mgmt.php">Manage Maps</a>';
    }
}

function weathermap_thumbview($limit_to_group = -1)
{
    global $colors;

    $userid = (isset($_SESSION["sess_user_id"]) ? intval($_SESSION["sess_user_id"]) : 1);
    $maplist_SQL =
        "select distinct weathermap_maps.* from weathermap_auth,weathermap_maps where weathermap_maps.id=weathermap_auth.mapid and active='on' and ";

    if ($limit_to_group > 0)
        $maplist_SQL .= " weathermap_maps.group_id=" . $limit_to_group . " and ";
    $maplist_SQL .= " (userid=" . $userid . " or userid=0) order by sortorder, id";

    $maplist = db_fetch_assoc($maplist_SQL);

    // if there's only one map, ignore the thumbnail setting and show it fullsize
    if (sizeof($maplist) == 1) {
        $pagetitle = "Network Weathermap";
        weathermap_fullview(false, false, $limit_to_group);
    } else {
        $pagetitle = "Network Weathermaps";

        html_graph_start_box(2, true);
?>

        <tr bgcolor = "<?php print $colors["panel"];?>">
            <td>
                <table width = "100%" cellpadding = "0" cellspacing = "0">
                    <tr>
                        <td class = "textHeader" nowrap><?php print $pagetitle; ?></td>

                        <td align = "right">
                            automatically cycle between full-size maps (<?php
                                if ($limit_to_group > 0) {
                                    print '<a href = "?action=viewmapcycle&group='.intval($limit_to_group).'">within this group</a>, or ';
                                } 
                                print ' <a href = "?action=viewmapcycle">all maps</a>';                                
                            ?>)
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td><i>Click on thumbnails for a full view (or you can
            <a href = "?action=viewmapcycle">automatically cycle</a> between full-size
            maps)</i></td>
        </tr>

<?php
        html_graph_end_box();

        weathermap_tabs($limit_to_group);
        $i = 0;

        if (sizeof($maplist) > 0) {

            $outdir = dirname(__FILE__) . '/output/';
            $confdir = dirname(__FILE__) . '/configs/';

            $imageformat = strtolower(read_config_option("weathermap_output_format"));

            html_graph_start_box(1, false);
            print "<tr><td class='wm_gallery'>";

            foreach ($maplist as $map) {
                $i++;

                $imgsize = "";
# $thumbfile = $outdir."weathermap_thumb_".$map['id'].".".$imageformat;
# $thumburl = "output/weathermap_thumb_".$map['id'].".".$imageformat."?time=".time();
                $thumbfile = $outdir . $map['filehash'] . ".thumb." . $imageformat;
                $thumburl =
                    "?action=viewthumb&id=" . $map['filehash'] . "&time=" . time();

                if ($map['thumb_width'] > 0) {
                    $imgsize = ' WIDTH="' . $map['thumb_width'] . '" HEIGHT="'
                        . $map['thumb_height'] . '" ';
                }
                $maptitle = $map['titlecache'];

                if ($maptitle == '')
                    $maptitle = "Map for config file: " . $map['configfile'];

                print
                    '<div class="wm_thumbcontainer" style="margin: 2px; border: 1px solid #bbbbbb; padding: 2px; float:left;">';

                if (file_exists($thumbfile)) {
                    print
                        '<div class="wm_thumbtitle" style="font-size: 1.2em; font-weight: bold; text-align: center">'
                        . $maptitle
                            . '</div><a href="weathermap-cacti-plugin.php?action=viewmap&id='
                        . $map['filehash'] . '"><img class="wm_thumb" ' . $imgsize
                            . 'src="' . $thumburl . '" alt="' . $maptitle
                            . '" border="0" hspace="5" vspace="5" title="' . $maptitle
                            . '"/></a>';
                } else {
                    print "<div class='wm_notyet'>Thumbnail for this map is not calculated yet</div>";
                }

                print '</div> ';
            }
            print "</td></tr>";
            html_graph_end_box();
        } else {            
            print "<div class='wm_nomaps'><em>You Have No Maps</em><br />";
            if(weathermap_is_mapadmin()) {
                print "(Visit the <a href='weathermap-cacti-plugin-mgmt.php'>Weathermap Management</a> page to get started)\n";
            }
            print "</div>";
        }
    }
}

function weathermap_fullview($cycle = false, $firstonly = false, $limit_to_group = -1)
{
    global $colors;

    $_SESSION['custom'] = false;

    $userid = (isset($_SESSION["sess_user_id"]) ? intval($_SESSION["sess_user_id"]) : 1);
# $query = "select distinct weathermap_maps.* from weathermap_auth,weathermap_maps where weathermap_maps.id=weathermap_auth.mapid and active='on' and (userid=".$userid." or userid=0) order by sortorder, id";
    $maplist_SQL =
        "select distinct weathermap_maps.* from weathermap_auth,weathermap_maps where weathermap_maps.id=weathermap_auth.mapid and active='on' and ";

    if ($limit_to_group > 0) {
        $maplist_SQL .= " weathermap_maps.group_id=" . $limit_to_group . " and ";
    }
    $maplist_SQL .= " (userid=" . $userid . " or userid=0) order by sortorder, id";

    if ($firstonly) {
        $maplist_SQL .= " LIMIT 1";
    }

    $maplist = db_fetch_assoc($maplist_SQL);
    html_graph_start_box(2, true);

    if (sizeof($maplist) == 1) {
        $pagetitle = "Network Weathermap";
    } else {
        $pagetitle = "Network Weathermaps";
    }
    
    $extra = "";
?>
    <tr bgcolor = "<?php print $colors["panel"];?>">
        <td>
            <table width = "100%" cellpadding = "0" cellspacing = "0">
                <tr>
                    <td class = "textHeader" nowrap><?php print $pagetitle; ?>
                    </td>

                    <td align = "right">
                        <?php if (!$cycle) { ?>
                        (automatically cycle between full-size maps (<?php
                                
                                if ($limit_to_group > 0) {
                                    
                                    print '<a href = "?action=viewmapcycle&group='.intval($limit_to_group).'">within this group</a>, or ';
                                } 
                                print ' <a href = "?action=viewmapcycle">all maps</a>';                                
                            ?>)                        

                        <?php
                        } else {
                            if($limit_to_group > 0) {
                                $extra = " in this group";
                            }
                        ?>
                            Cycling all available maps<?php echo $extra; ?>. <a href = "?action=">Stop.</a>
                        <?php
                        }
                        ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <?php
    html_graph_end_box();

    weathermap_tabs($limit_to_group);

    $i = 0;

    if (sizeof($maplist) > 0) {

        $outdir = dirname(__FILE__) . '/output/';
        $confdir = dirname(__FILE__) . '/configs/';

        foreach ($maplist as $map) {
            $i++;
            $htmlfile = $outdir . $map['filehash'] . ".html";
            $maptitle = $map['titlecache'];

            if ($maptitle == '')
                $maptitle = "Map for config file: " . $map['configfile'];

            print '<div class="weathermapholder" id="mapholder_' . $map['filehash']
                . '">';
            html_graph_start_box(1, true);
            print '<tr bgcolor="#' . $colors["header_panel"] . '">';
    ?>

            <td colspan = "3">
                <table width = "100%" cellspacing = "0" cellpadding = "3" border = "0">
                    <tr>
                        <td align = "left" class = "textHeaderDark">
                            <a name = "map_<?php echo $map['filehash']; ?>"></a>

                            <?php print htmlspecialchars($maptitle); ?>
                        </td>
                    </tr>
                </table>
            </td>

            </tr>

            <tr>
                <td>
    <?php
                    if (file_exists($htmlfile)) {
                        include($htmlfile);
                    } else {
                        print
                            "<div align=\"center\" class='wm_notyet_larger'>This map hasn't been calculated yet.</div>";
                    }

                    print '</td></tr>';
                    html_graph_end_box();
                    print '</div>';
        }

        if ($cycle) {
            $refreshtime = read_config_option("weathermap_cycle_refresh");
// OK, so the Cycle plugin does all this with a <META> tag at the bottom of the body
// that overrides the one at the top (that Cacti puts there). Unfortunately, that
// isn't valid HTML! So here's a Javascript driven way to do it

// It has the advantage that the image switching is cleaner, too.
// We also do a nice thing of taking the poller-period (5 mins), and the
// available maps, and making sure each one gets equal time in the 5 minute period.
    ?>

        <script type = "text/javascript">
            function addEvent(obj, evType, fn)
                {
                if (obj.addEventListener) {
                    obj.addEventListener(evType, fn, false);
                    return true;
                }

                else if (obj.attachEvent) {
                    var r = obj.attachEvent("on" + evType, fn);
                    return r;
                }

                else {
                    return false;
                }
            }

            wm_maps = new Array;
            wm_current = 0;

            function wm_tick()
            {
                document.getElementById(wm_maps[wm_current]).style.display = 'none';
                wm_current++;

                if (wm_current >= wm_maps.length)
                    wm_current = 0;
                document.getElementById(wm_maps[wm_current]).style.display = 'block';
            }

            function wm_reload() {
                window.location.reload();
            }

            function wm_initJS()
            {
                var i, j;
                var possible_maps = document.getElementsByTagName('div');

                j = 0;

                for (i = 0; i < possible_maps.length; ++i) {
                    if (possible_maps[i].className == 'weathermapholder') {
                        wm_maps[j] = possible_maps[i].id;
                        j++;
                    }
                }

                // stop here if there were no maps
                if (j > 0) {
                    wm_current = 0;

                    // hide all but the first one
                    if (j > 1) {
                        for (i = 1; i < j; i++) {
                            document.getElementById(wm_maps[i]).style.display = 'none';
                        }
                    }
                    // figure out how long the refresh is, so that we get
                    // through all the maps in exactly 5 minutes

                    var period = <?php echo $refreshtime ?> * 1000;

                    if (period == 0) {
                        var period = 300000 / j;
                    }

                    // our map-switching clock
                    setInterval(wm_tick, period);
                    // when to reload the whole page (with new map data)
                    setTimeout(wm_reload, 300000);
                }
            }

            addEvent(window, 'load', wm_initJS);
        </script>

<?php
        }
    } else {
        print
            "<div align=\"center\" style=\"padding:20px\"><em>You Have No Maps</em></div>\n";
    }
}

function weathermap_translate_id($idname)
{
    $SQL = "select id from weathermap_maps where configfile='"
        . mysql_real_escape_string($idname) . "' or filehash='"
        . mysql_real_escape_string($idname) . "'";
    $map = db_fetch_assoc($SQL);

    return ($map[0]['id']);
}

function weathermap_is_mapadmin()
{
    global $config, $user_auth_realms, $user_auth_realm_filenames;

    $realm_id2 = 0;

    if (isset($user_auth_realm_filenames['weathermap-cacti-plugin-mgmt.php'])) {
        $realm_id2 = $user_auth_realm_filenames['weathermap-cacti-plugin-mgmt.php'];
    }
    $userid = (isset($_SESSION["sess_user_id"]) ? intval($_SESSION["sess_user_id"]) : 1);

    if ((db_fetch_assoc(
        "select user_auth_realm.realm_id from user_auth_realm where user_auth_realm.user_id='"
        . $userid . "' and user_auth_realm.realm_id='$realm_id2'"))
        || (empty($realm_id2))) {
        return true;
    }

    return false;
}

function weathermap_versionbox()
{
    global $WEATHERMAP_VERSION, $colors;

    $pagefoot =
        "Powered by <a href=\"http://www.network-weathermap.com/?v=$WEATHERMAP_VERSION\">PHP Weathermap version $WEATHERMAP_VERSION</a>";

    if(weathermap_is_mapadmin()) {
        $pagefoot
            .= " --- <a href='weathermap-cacti-plugin-mgmt.php' title='Go to the map management page'>Weathermap Management</a>";
        $pagefoot .= " | <a target=\"_blank\" href=\"docs/\">Local Documentation</a>";
        $pagefoot .= " | <a target=\"_blank\" href=\"editor.php\">Editor</a>";
    }

    html_graph_start_box(1, true);
?>

    <tr bgcolor = "<?php print $colors["panel"];?>">
        <td>
            <table width = "100%" cellpadding = "0" cellspacing = "0">
                <tr>
                    <td class = "textHeader" nowrap><?php print $pagefoot; ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

<?php
                    html_graph_end_box();
}

function readfile_chunked($filename)
{
    $chunksize = 1 * (1024 * 1024); // how many bytes per chunk
    $buffer = '';
    $cnt = 0;

    $handle = fopen($filename, 'rb');

    if ($handle === false)
        return false;

    while (!feof($handle)) {
        $buffer = fread($handle, $chunksize);

        echo $buffer;
    }

    $status = fclose($handle);
    return $status;
}

function weathermap_footer_links()
{
    global $colors;
    global $WEATHERMAP_VERSION;
    print '<br />';
    html_start_box(
        "<center><a target=\"_blank\" class=\"linkOverDark\" href=\"docs/\">Local Documentation</a> -- <a target=\"_blank\" class=\"linkOverDark\" href=\"http://www.network-weathermap.com/\">Weathermap Website</a> -- <a target=\"_target\" class=\"linkOverDark\" href=\"weathermap-cacti-plugin-editor.php?plug=1\">Weathermap Editor</a> -- This is version $WEATHERMAP_VERSION</center>",
        "78%", $colors["header"], "2", "center", "");
    html_end_box();
}

function weathermap_mapselector($current_id = 0)
{
    global $colors;

    $show_selector = intval(read_config_option("weathermap_map_selector"));

    if ($show_selector == 0)
        return false;

    $userid = (isset($_SESSION["sess_user_id"]) ? intval($_SESSION["sess_user_id"]) : 1);
    $maps =
        db_fetch_assoc(
            "select distinct weathermap_maps.*,weathermap_groups.name, weathermap_groups.sortorder as gsort from weathermap_groups,weathermap_auth,weathermap_maps where weathermap_maps.group_id=weathermap_groups.id and weathermap_maps.id=weathermap_auth.mapid and active='on' and (userid="
        . $userid . " or userid=0) order by gsort, sortorder");

    if (sizeof($maps) > 1) {

        /* include graph view filter selector */
        html_graph_start_box(3, true);
?>

        <tr bgcolor = "<?php print $colors["panel"];?>" class = "noprint">
            <form name = "weathermap_select" method = "post" action = "">
                <input name = "action" value = "viewmap" type = "hidden">
                <td class = "noprint">
                    <table width = "100%" cellpadding = "0" cellspacing = "0">
                        <tr class = "noprint">
                            <td nowrap style = 'white-space: nowrap;' width = "40">
                                &nbsp;<strong>Jump To Map:</strong>&nbsp;
                            </td>

                            <td>
                                <select name = "id">
                                    <?php
                                    $ngroups = 0;
                                    $lastgroup =
                                        "------lasdjflkjsdlfkjlksdjflksjdflkjsldjlkjsd";

                                    foreach ($maps as $map) {
                                        if ($current_id == $map['id'])
                                            $nullhash = $map['filehash'];

                                        if ($map['name'] != $lastgroup) {
                                            $ngroups++;
                                            $lastgroup = $map['name'];
                                        }
                                    }

                                    $lastgroup =
                                        "------lasdjflkjsdlfkjlksdjflksjdflkjsldjlkjsd";

                                    foreach ($maps as $map) {
                                        if ($ngroups > 1 && $map['name'] != $lastgroup) {
                                            print
                                                "<option style='font-weight: bold; font-style: italic' value='$nullhash'>"
                                                . htmlspecialchars($map['name'])
                                                    . "</option>";
                                            $lastgroup = $map['name'];
                                        }
                                        print '<option ';

                                        if ($current_id == $map['id'])
                                            print " SELECTED ";
                                        print 'value="' . $map['filehash'] . '">';

// if we're showing group headings, then indent the map names
                                        if ($ngroups > 1) {
                                            print " - ";
                                        }
                                        print htmlspecialchars($map['titlecache'])
                                            . '</option>';
                                    }
                                    ?>
                                </select>

                                &nbsp;<input type = "image"
                                    src = "../../images/button_go.gif" alt = "Go"
                                    border = "0" align = "absmiddle">
                            </td>
                        </tr>
                    </table>
                </td>
            </form>
        </tr>

<?php
        html_graph_end_box(false);
    }
}

function weathermap_get_valid_tabs()
{
    $tabs = array ();
    $userid = (isset($_SESSION["sess_user_id"]) ? intval($_SESSION["sess_user_id"]) : 1);
    $maps =
        db_fetch_assoc(
            "select weathermap_maps.*, weathermap_groups.name as group_name from weathermap_auth,weathermap_maps, weathermap_groups where weathermap_groups.id=weathermap_maps.group_id and weathermap_maps.id=weathermap_auth.mapid and active='on' and (userid="
        . $userid . " or userid=0) order by weathermap_groups.sortorder");

	if($maps) {
    foreach ($maps as $map) {
        $tabs[$map['group_id']] = $map['group_name'];
    }
	}

    return ($tabs);
}

function weathermap_tabs($current_tab)
{
    global $colors;

    // $current_tab=2;

    $tabs = weathermap_get_valid_tabs();

    # print "Limiting to $current_tab\n";

    if (sizeof($tabs) > 1) {
        /* draw the categories tabs on the top of the page */
        print
            "<p></p><table class='tabs' width='100%' cellspacing='0' cellpadding='3' align='center'><tr>\n";

        if (sizeof($tabs) > 0) {
            $show_all = intval(read_config_option("weathermap_all_tab"));

            if ($show_all == 1) {
                $tabs['-2'] = "All Maps";
            }

            foreach (array_keys($tabs) as $tab_short_name) {
                print "<td "
                    . (($tab_short_name == $current_tab)
                        ? "bgcolor='silver'" : "bgcolor='#DFDFDF'")
                        . " nowrap='nowrap' width='"
                    . (strlen($tabs[$tab_short_name]) * 9)
                        . "' align='center' class='tab'>
	                                <span class='textHeader'><a href='weathermap-cacti-plugin.php?group_id=$tab_short_name'>$tabs[$tab_short_name]</a></span>
	                                </td>\n
	                                <td width='1'></td>\n";
            }
        }

        print "<td></td>\n</tr></table>\n";

        return (true);
    } else {
        return (false);
    }
}

// vim:ts=4:sw=4:
?>
