<?php

$guest_account = true;

chdir('../../');
require_once('./include/auth.php');

// include the weathermap class so that we can get the version
require_once(dirname(__FILE__) . '/lib/Weathermap.class.php');
require_once(dirname(__FILE__) . '/lib/database.php');
require_once(dirname(__FILE__) . '/lib/WeathermapManager.class.php');

$weathermap_confdir = realpath(dirname(__FILE__) . '/configs');

set_default_action();

$manager = new WeathermapManager(weathermap_get_pdo(), $weathermap_confdir);

switch(get_request_var('action')) {
case 'viewthumb': // FALL THROUGH
case 'viewimage':
	$id = -1;
	if (isset_request_var('id') && (!is_numeric(get_nfilter_request_var('id')) || strlen(get_nfilter_request_var('id') == 20))) {
		$id = $manager->translateFileHash(get_nfilter_request_var('id'));
	}elseif (isset_request_var('id')) {
		$id = get_filter_request_var('id');
	}
	
	if ($id >= 0) {
		$imageformat = strtolower(read_config_option('weathermap_output_format'));
		
		$userid = (isset($_SESSION['sess_user_id']) ? intval($_SESSION['sess_user_id']) : 1);

		$map = $manager->getMapWithAccess($userid, $id);

		if (sizeof($map)) {
			$imagefile = dirname(__FILE__) . '/output/' . '/' . $map[0]->filehash . '.' . $imageformat;
			if ($action == 'viewthumb') {
				$imagefile = dirname(__FILE__) . '/output/' . $map[0]->filehash . '.thumb.' . $imageformat;
			}
			$orig_cwd = getcwd();
			chdir(dirname(__FILE__));

			header('Content-type: image/png');
			
			readfile($imagefile);
					
			dir($orig_cwd);	
		} else {
			// no permission to view this map
		}
	}
	
	break;
case 'viewmapcycle':
	$fullscreen = 0;
	if (isset_request_var('fullscreen')) {
		$fullscreen = get_filter_request_var('fullscreen');
	}
		
	if ($fullscreen==1) {
	    print "<!DOCTYPE html>\n";
		print "<html><head>";
		print '<LINK rel="stylesheet" type="text/css" media="screen" href="cacti-resources/weathermap.css">';		
		print "</head><body id='wm_fullscreen'>";
	} else {
		general_header();
	}		

	print "<div id=\"overDiv\" style=\"position:absolute; visibility:hidden; z-index:1000;\"></div>\n";
	print "<script type=\"text/javascript\" src=\"overlib.js\"><!-- overLIB (c) Erik Bosrup --></script> \n";

	$groupid = -1;
	if (isset_request_var('group')) {
		$groupid = get_filter_request_var('group');
	}

	weathermap_fullview(true, false, $groupid, $fullscreen);

	if ($fullscreen == 0) {
		weathermap_versionbox();
	}

	bottom_footer();

	break;
case 'viewmap':
	general_header();

	print "<div id=\"overDiv\" style=\"position:absolute; visibility:hidden; z-index:1000;\"></div>\n";
	print "<script type=\"text/javascript\" src=\"overlib.js\"><!-- overLIB (c) Erik Bosrup --></script> \n";

	$id = -1;

	if (isset_request_var('id') && (!is_numeric(get_nfilter_request_var('id')) || strlen(get_nfilter_request_var('id')) == 20)) {
		$id = $manager->translateFileHash(get_nfilter_request_var('id'));
	}elseif (isset_request_var('id')) {
		$id = get_filter_request_var('id');
	}
	
	if ($id >= 0) {
		weathermap_singleview($id);
	}	
	
	weathermap_versionbox();

	bottom_footer();

	break;
default:
	general_header();

	print "<div id=\"overDiv\" style=\"position:absolute; visibility:hidden; z-index:1000;\"></div>\n";
	print "<script type=\"text/javascript\" src=\"overlib.js\"><!-- overLIB (c) Erik Bosrup --></script> \n";

	$group_id = -1;
	if (isset_request_var('group_id')) {
		$group_id = get_filter_request_var('group_id');
		$_SESSION['wm_last_group'] = $group_id;
	}else{
		if (isset($_SESSION['wm_last_group'])) {
			$group_id = intval($_SESSION['wm_last_group']);
		}
	}

	$userid = (isset($_SESSION['sess_user_id']) ? intval($_SESSION['sess_user_id']) : 1);
	$tabs = $manager->getTabs($userid);

	$tab_ids = array_keys($tabs);
    if (($group_id == -1) && (sizeof($tab_ids) > 0)) {
        $group_id = $tab_ids[0];
    }

    if (read_config_option('weathermap_pagestyle') == 0) {
        weathermap_thumbview($group_id);
    }

    if (read_config_option('weathermap_pagestyle') == 1) {
        weathermap_fullview(false, false, $group_id);
    }

    if (read_config_option('weathermap_pagestyle') == 2) {
        weathermap_fullview(false, true, $group_id);
    }

	weathermap_versionbox();

	bottom_footer();

	break;
}

function weathermap_singleview($mapid) {
	global $manager;

	$is_wm_admin = false;

	$outdir = dirname(__FILE__).'/output/';

	$userid = (isset($_SESSION['sess_user_id']) ? intval($_SESSION['sess_user_id']) : 1);
	$map = $manager->getMapWithAccess($userid, $mapid);


	if (sizeof($map) > 0) {
 		# print do_hook_function ('weathermap_page_top', array($map[0]['id'], $map[0]['titlecache']) );
 		print do_hook_function ('weathermap_page_top', '' );

		$htmlfile = $outdir . $map[0]->filehash . '.html';
		$maptitle = $map[0]->titlecache;
		if ($maptitle == '') {
            $maptitle= __('Map for config file: %s', $map[0]->configfile);
        }

		weathermap_mapselector($mapid);

		html_start_box(__('Weathermaps'), '100%', '', '3', 'center', '');

		?>
		<tr class="even"><td><table width="100%" cellpadding="0" cellspacing="0"><tr><td class="textHeader nowrap"><?php print $maptitle; 

        if ($is_wm_admin) {
            print "<span style='font-size: 80%'>";
            print "[ <a class='hyperLink' href='weathermap-cacti-plugin-mgmt.php?action=map_settings&id=".$mapid."'>" . __('Map Settings') . "</a> |";
            print "<a class='hyperLink' href='weathermap-cacti-plugin-mgmt.php?action=perms_edit&id=".$mapid."'>" . __('Map Permissions') . "</a> |";
            print "<a class='hyperLink' href=''>" . __('Edit Map') . "</a> ]";
            print "</span>";
        } ?>
		</td></tr></table></td></tr>

		<?php
		print '<tr><td>';

		if (file_exists($htmlfile)) {
			include($htmlfile);
		} else {
			print "<div align=\"center\" style=\"padding:20px\"><em>" . __('This map hasn\'t been created yet.');

			if (weathermap_is_admin()) {
                print __(' (If this message stays here for more than one poller cycle, then check your cacti.log file for errors!)');
            }

			print '</em></div>';
		}

		print '</td></tr>';

		html_end_box();
	}
}

function weathermap_is_admin() {
	global $user_auth_realm_filenames;
	global $manager;

	$realm_id = 0;

	if (isset($user_auth_realm_filenames['weathermap-cacti-plugin-mgmt.php'])) {
		$realm_id = $user_auth_realm_filenames['weathermap-cacti-plugin-mgmt.php'];
	}

	$userid  = (isset($_SESSION['sess_user_id']) ? intval($_SESSION['sess_user_id']) : 1);
	$allowed = $manager->checkUserForRealm($userid, $realm_id);

	if ($allowed || (empty($realm_id))) {
		return true;
	}

	return false;
}

function weathermap_show_manage_tab() {
	global $config;

	if (weathermap_is_admin()) {
		print '<a class="hyperLink" href="' . $config['url_path'] . 'plugins/weathermap/weathermap-cacti-plugin-mgmt.php">' . __('Manage Maps') . '</a>';
	}
}

function weathermap_thumbview($limit_to_group = -1) {
	global $manager;

	$total_map_count = $manager->getMapTotalCount();

	$userid = (isset($_SESSION['sess_user_id']) ? intval($_SESSION['sess_user_id']) : 1);

	if ($limit_to_group > 0) {
		$maplist = $manager->getMapsForUser($userid, $limit_to_group);
	} else {
		$maplist = $manager->getMapsForUser($userid);
	}

	// if there's only one map, ignore the thumbnail setting and show it fullsize
	if (sizeof($maplist) == 1) {
		$pagetitle = __('Network Weathermap');
		weathermap_fullview(false, false, $limit_to_group);
	} else {
		$pagetitle = __('Network Weathermaps');

		html_start_box($pagetitle, '100%', '', '3', 'center', '');
		?>
		<tr class='even'>
			<td>
				<table width='100%' cellpadding='0' cellspacing='0'>
					<tr>
						<td class='textHeader' nowrap> <?php print $pagetitle; ?></td>
						<td align='right'><?php print __('<a class="hyperLink" href="?action=viewmapcycle">automatically cycle</a> between full-size maps');?></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td>
				<i><?php print __('Click on thumbnails for a full view (or you can <a class="hyperLink" href="?action=viewmapcycle">automatically cycle</a> between full-size maps');?></i>
			</td>
		</tr>
		<?php
		html_end_box();

		weathermap_tabs($limit_to_group);

		if (sizeof($maplist) > 0) {
			$outdir = dirname(__FILE__) . '/output/';

			$imageformat = strtolower(read_config_option('weathermap_output_format'));

			html_start_box($pagetitle, '100%', '', '3', 'center', '');
			print "<tr><td class='wm_gallery'>";
			foreach ($maplist as $map) {
				$imgsize   = '';
				$thumbfile = $outdir . $map->filehash . '.thumb.' . $imageformat;
				$thumburl  = '?action=viewthumb&id=' . $map->filehash . '&time=' . time();

				if ($map->thumb_width > 0) {
					$imgsize = ' width="' . $map->thumb_width . '" height="' . $map->thumb_height . '" ';
				}

				$maptitle = $map->titlecache;
				if ($maptitle == '') {
					$maptitle = __('Map for config file: %s', $map->configfile);
				}

				print '<div class="wm_thumbcontainer" style="margin: 2px; border: 1px solid #bbbbbb; padding: 2px; float:left;">';
				if (file_exists($thumbfile)) {
					print '<div class="wm_thumbtitle" style="font-size: 1.2em; font-weight: bold; text-align: center">' . $maptitle . '</div><a class="hyperLink" href="weathermap-cacti-plugin.php?action=viewmap&id=' . $map->filehash . '"><img class="wm_thumb" ' . $imgsize . 'src="' . $thumburl . '" alt="' . $maptitle . '" border="0" hspace="5" vspace="5" title="' . $maptitle . '"/></a>';
				} else {
					print __('(thumbnail for map not created yet)');
				}

				print '</div>';
			}

			print '</td></tr>';

			html_end_box();
		} else {
			print "<div align=\"center\" style=\"padding:20px\"><em>" . __('You Have No Maps') . "</em>";

			if ($total_map_count == 0) {
				print '<p>' . __('To add a map to the schedule, go to the <a class="hyperLink" href="weathermap-cacti-plugin-mgmt.php">Manage...Weathermaps page</a> and add one.') . '</p>';
			}

			print '</div>';
		}
	}
}

function weathermap_fullview($cycle=FALSE, $firstonly=FALSE, $limit_to_group = -1, $fullscreen = 0) {
	global $manager;

	$_SESSION['custom']=false;
	$userid = (isset($_SESSION['sess_user_id']) ? intval($_SESSION['sess_user_id']) : 1);

	if ($limit_to_group >0) {
		$maplist = $manager->getMapsForUser($userid, $limit_to_group);
	} else {
		$maplist = $manager->getMapsForUser($userid);
	}

// TODO deal with this
//	if ($firstonly) {
//		$maplist_SQL .= " LIMIT 1";
//	}

	$pagetitle = __n('Network Weathermap', 'Network Weathermaps', sizeof($maplist));

	$class = '';
	if ($cycle) {
		$class = 'inplace';
	}
	if ($fullscreen) {
		$class = 'fullscreen';
	}

	if ($cycle) {
        print "<script src='vendor/idle-timer.min.js'></script>";

        if ($limit_to_group > 0) {
			$description = __('Showing <span id="wm_current_map">1</span> of <span id="wm_total_map">1</span>.  Cycling all available maps in this group.');
		}else{
			$description = __('Showing <span id="wm_current_map">1</span> of <span id="wm_total_map">1</span>.  Cycling all available maps.');
		}

		?>
		<div id='wmcyclecontrolbox' class='<?php print $class ?>'>
			<div id='wm_progress'></div>
			<div id='wm_cyclecontrols'>
				<a id='cycle_stop' href='?action='><img src='cacti-resources/img/control_stop_blue.png' width='16' height='16' /></a>
				<a id='cycle_prev' href='#'><img src='cacti-resources/img/control_rewind_blue.png' width='16' height='16' /></a>
				<a id='cycle_pause' href='#'><img src='cacti-resources/img/control_pause_blue.png' width='16' height='16' /></a>
				<a id='cycle_next' href='#'><img src='cacti-resources/img/control_fastforward_blue.png' width='16' height='16' /></a>
				<a id='cycle_fullscreen' href='?action=viewmapcycle&fullscreen=1&group=<?php echo $limit_to_group; ?>'><img src='cacti-resources/img/arrow_out.png' width='16' height='16' /></a>
				<?php print $description;?>
			</div>
		</div>
		<?php
	}

	// only draw the whole screen if we're not cycling, or we're cycling without fullscreen mode
	if ($cycle == false || $fullscreen==0) {
		html_start_box($pagetitle, '100%', '', '3', 'center', '');
		?>
		<tr class='even'>
			<td>
				<table width='100%' cellpadding='0' cellspacing='0'>
					<tr>
					   	<td class='textHeader' nowrap> <?php print $pagetitle; ?> </td>
						<td align = 'right'>
                        <?php 
						if (!$cycle) { 
							if ($limit_to_group > 0) {
								print __('(automatically cycle between full-size maps (<a class="hyperLink" href="?action=viewmapcycle&group=%s">within this group</a>, or <a class="hyperLink" href="?action=viewmapcycle">all maps</a>)', intval($limit_to_group));
							}else{
								print __('(automatically cycle between full-size maps (<a class="hyperLink" href="?action=viewmapcycle">all maps</a>)');
							}
                        } ?>
                   		</td>
					</tr>
				</table>
			</td>
		</tr>
		<?php
		html_end_box();

		weathermap_tabs($limit_to_group);
	}

	$i = 0;
	if (sizeof($maplist) > 0) {
		print "<div class='all_map_holder $class'>";

		$outdir  = dirname(__FILE__) . '/output/';
		$confdir = dirname(__FILE__) . '/configs/';

		foreach ($maplist as $map) {
			if ($firstonly && $i > 0) {
				break;
			}

			$i++;

			$htmlfile = $outdir . $map->filehash . '.html';
			$maptitle = $map->titlecache;

			if ($maptitle == '') {
				$maptitle = __('Map for config file: %s', $map->configfile);
			}

			print '<div class="weathermapholder" id="mapholder_' . $map->filehash . '">';

			if ($cycle == false || $fullscreen==0) {
				html_start_box(__('Map for config file: %s', $map->configfile), '100%', '', '3', 'center', '');

				?>
				<tr class='even'>
					<td colspan='3'>
						<table width='100%' cellspacing='0' cellpadding='3' border='0'>
							<tr>
								<td align='left' class='textHeaderDark'>
                                   	<a name='map_<?php echo $map->filehash; ?>'></a>
									<?php print htmlspecialchars($maptitle); ?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td><?php 
			}

			if (file_exists($htmlfile)) {
				include($htmlfile);
			} else {
				print "<div align=\"center\" style=\"padding:20px\"><em>" . __('This map hasn\'t been created yet.') . "</em></div>";
			}

			if ($cycle == false || $fullscreen==0) {
				print '</td></tr>';
				html_end_box();
			}

			print '</div>';
		}

		print '</div>';

		if ($cycle) {
			$refreshtime  = read_config_option('weathermap_cycle_refresh');
			$poller_cycle = read_config_option('poller_interval');

			?>
			<script type='text/javascript' src='cacti-resources/map-cycle.js'></script>
			<script type = 'text/javascript'>
			$(function() {
				WMcycler.start({ fullscreen: <?php echo ($fullscreen ? '1' : '0'); ?>,
				    poller_cycle: <?php echo $poller_cycle * 1000; ?>,
				    period: <?php echo $refreshtime  * 1000; ?>});
			});
			</script><?php
		}
	} else {
		print '<div align="center" style="padding:20px"><em>' . __('You Have No Maps') . '</em></div>';
	}
}

function weathermap_versionbox() {
	global $WEATHERMAP_VERSION;

	$pagefoot = __('Powered by <a href="http://www.network-weathermap.com/?v=%s">PHP Weathermap version %s</a>', $WEATHERMAP_VERSION, $WEATHERMAP_VERSION);
	
	if (weathermap_is_admin()) {
		$pagefoot .= ' --- <a href="weathermap-cacti-plugin-mgmt.php" title="' . __('Go to the map management page') . '">' . __('Weathermap Management') . "</a>";
		$pagefoot .= ' | <a target="_blank" href="docs/">' . __('Local Documentation') . '</a>';
		$pagefoot .= ' | <a target="_blank" href="weathermap-cacti-plugin-editor.php">' . __('Editor') . '</a>';
	}

	html_start_box('Weathermap Info', '100%', '', '3', 'center', '');

	?>
	<tr class='even'>
		<td>
			<table width='100%' cellpadding='0' cellspacing='0'>
				<tr>
				   <td class='textHeader' nowrap> <?php print $pagefoot; ?> </td>
				</tr>
			</table>
		</td>
	</tr>
	<?php

	html_end_box();
}

function weathermap_footer_links() {
	global $WEATHERMAP_VERSION;
	print '<br />'; 
    html_start_box('<center><a target="_blank" class="linkOverDark" href="docs/">' . __('Local Documentation') . '</a> -- <a target="_blank" class="linkOverDark" href="http://www.network-weathermap.com/">' . __('Weathermap Website') . '</a> -- <a target="_target" class="linkOverDark" href="weathermap-cacti-plugin-editor.php?plug=1">' . __('Weathermap Editor') . '</a>' . __(' -- This is version %s', $WEATHERMAP_VERSION) . '</center>', '100%', '', '2', 'center', '');
	html_end_box(); 
}

function weathermap_mapselector($current_id = 0) {
	global $manager;
	
    $show_selector = intval(read_config_option('weathermap_map_selector'));

	if ($show_selector == 0) {
        return false;
    }

	$userid = (isset($_SESSION['sess_user_id']) ? intval($_SESSION['sess_user_id']) : 1);
    $maps = $manager->getMapsWithAccessAndGroups($userid);

	if (sizeof($maps)>1) {
		/* include graph view filter selector */
		 html_start_box('Weathermap', '100%', '', '3', 'center', '');

		?>
		<tr class='even' class='noprint'>
			<td class='noprint'>
				<form name='weathermap_select' method='post' action=''>
					<input name='action' value='viewmap' type='hidden'>
					<table width='100%' cellpadding='0' cellspacing='0'>
						<tr class='noprint'>
							<td nowrap style='white-space: nowrap;' width='40'>
								<?php print __('Jump To Map:');?>
							</td>
							<td>
								<select name='id'>
		<?php

		$ngroups = 0;
		$lastgroup = '------lasdjflkjsdlfkjlksdjflksjdflkjsldjlkjsd';
		foreach ($maps as $map) {
			if ($current_id == $map->id) {
			    $nullhash = $map->filehash;
            }

			if ($map->name != $lastgroup) {
				$ngroups++;
				$lastgroup = $map->name;
			}
		}

		$lastgroup = '------lasdjflkjsdlfkjlksdjflksjdflkjsldjlkjsd';
		foreach ($maps as $map) {
			if ($ngroups>1 && $map->name != $lastgroup) {
				print "<option style='font-weight: bold; font-style: italic' value='$nullhash'>" . htmlspecialchars($map->name) . '</option>';
				$lastgroup = $map->name;
			}

			print '<option ';

			if ($current_id == $map->id) {
                print ' SELECTED ';
            }

			print 'value="' . $map->filehash . '">';

			// if we're showing group headings, then indent the map names
			if ($ngroups > 1) {
                print ' - ';
            }
			print htmlspecialchars($map->titlecache).'</option>';
		}

		?>
								</select>
							</td>
							<td>
								<input type='submit' value='<?php print __('Go');?>'>
							</td>
						</tr>
					</table>
				</form>
			</td>
		</tr>
		<?php

		html_end_box(FALSE);
	}
}

function weathermap_tabs($current_tab) {
	global $manager, $config;

	// $current_tab=2;
	$userid = (isset($_SESSION['sess_user_id']) ? intval($_SESSION['sess_user_id']) : 1);

	$tabs = $manager->getTabs($userid);

	if (sizeof($tabs) > 1) {
		$show_all = intval(read_config_option('weathermap_all_tab'));
		if ($show_all == 1) {
			$tabs['-2'] = __('All Maps');
		}

		/* draw the tabs */
		print "<div class='tabs'><nav><ul>\n";

		foreach ($tabs as $tab_short_name => $tab_name) {
			print '<li><a class="tab' . (($tab_short_name == $current_tab) ? ' selected"' : '"') . " href='" . htmlspecialchars($config['url_path'] .
				'plugins/weathermap/weathermap-cacti-plugin.php?group_id=' . $tab_short_name) . "'>$tab_name</a></li>\n";
		}

		print "</ul></nav></div>\n";

		return true;
	}else{
		return false;
	}
}

// vim:ts=4:sw=4:
