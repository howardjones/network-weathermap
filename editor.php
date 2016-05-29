<?php
	
require_once 'lib/editor.inc.php';
require_once 'lib/Weathermap.class.php';
require_once 'lib/geometry.php';
require_once 'lib/WMPoint.class.php';
require_once 'lib/WMVector.class.php';
require_once 'lib/WMLine.class.php';

// so that you can't have the editor active, and not know about it.
$ENABLED=false;

// If we're embedded in the Cacti UI (included from weathermap-cacti-plugin-editor.php), then authentication has happened. Enable the editor.
if (isset($FROM_CACTI) && $FROM_CACTI == true) {
    $ENABLED = true;
	$editor_name = "weathermap-cacti-plugin-editor.php";
	$cacti_base = $config["base_path"];
	$cacti_url = $config['url_path'];
	$cacti_found = true;
} else {
    $FROM_CACTI = false;
	$editor_name = "editor.php";
	$cacti_base = '../../';
	$cacti_url = '/';
	$cacti_found = false;
}

if(! $ENABLED)
{
    print "<p>The editor has not been enabled yet. You need to set ENABLED=true at the top of editor.php</p>";
    print "<p>Before you do that, you should consider using FilesMatch (in Apache) or similar to limit who can access the editor. There is more information in the install guide section of the manual.</p>";
    exit();
}

// sensible defaults
$mapdir='configs';
$ignore_cacti=FALSE;
$configerror = '';

// these are all set via the Editor Settings dialog, in the editor, now.
$use_overlay = FALSE; // set to TRUE to enable experimental overlay showing VIAs
$use_relative_overlay = FALSE; // set to TRUE to enable experimental overlay showing relative-positioning
$grid_snap_value = 0; // set non-zero to snap to a grid of that spacing

if( isset($_COOKIE['wmeditor']))
{
    $parts = explode(":",$_COOKIE['wmeditor']);
    
    if( (isset($parts[0])) && (intval($parts[0]) == 1) ) { $use_overlay = TRUE; }
    if( (isset($parts[1])) && (intval($parts[1]) == 1) ) { $use_relative_overlay = TRUE; }
    if( (isset($parts[2])) && (intval($parts[2]) != 0) ) { $grid_snap_value = intval($parts[2]); }   
}

if ($FROM_CACTI==false) {

// check if the goalposts have moved
if( is_dir($cacti_base) && file_exists($cacti_base."/include/global.php") )
{
	// include the cacti-config, so we know about the database
	include_once($cacti_base."/include/global.php");
	$config['base_url'] = $cacti_url;
	$cacti_found = TRUE;
}
elseif( is_dir($cacti_base) && file_exists($cacti_base."/include/config.php") )
{
	// include the cacti-config, so we know about the database
	include_once($cacti_base."/include/config.php");

	$config['base_url'] = $cacti_url;
	$cacti_found = TRUE;
}
else
{
	$cacti_found = FALSE;
}
}

chdir(dirname(__FILE__));

if(! is_writable($mapdir))
{
	$configerror = "The map config directory ($mapdir) is not writable by the web server user. You will not be able to edit any files until this is corrected. [WMEDIT01]";
}


$action = '';
$mapname = '';
$selected = '';

$newaction = '';
$param = '';
$param2 = '';
$log = '';

if(!wm_module_checks())
{
	print "<b>Required PHP extensions are not present in your mod_php/ISAPI PHP module. Please check your PHP setup to ensure you have the GD extension installed and enabled.</b><p>";
	print "If you find that the weathermap tool itself is working, from the command-line or Cacti poller, then it is possible that you have two different PHP installations. The Editor uses the same PHP that webpages on your server use, but the main weathermap tool uses the command-line PHP interpreter.<p>";
	print "<p>You should also run <a href=\"check.php\">check.php</a> to help make sure that there are no problems.</p><hr/>";
	print "Here is a copy of the phpinfo() from your PHP web module, to help debugging this...<hr>";
	phpinfo();
	exit();
}

if(isset($_REQUEST['action'])) { $action = $_REQUEST['action']; }
if(isset($_REQUEST['mapname'])) { $mapname = $_REQUEST['mapname'];  $mapname = wm_editor_sanitize_conffile($mapname); }
if(isset($_REQUEST['selected'])) { $selected = wm_editor_sanitize_selected($_REQUEST['selected']); }

$weathermap_debugging=FALSE;

if($mapname == '')
{
	// this is the file-picker/welcome page
	show_editor_startpage();
}
else
{  
	// everything else in this file is inside this else
	$mapfile = $mapdir.'/'.$mapname;        

	wm_debug("==========================================================================================================\n");
	wm_debug("Starting Edit Run: action is $action on $mapname\n");
	wm_debug("==========================================================================================================\n");
	
	# editor_log("\n\n-----------------------------------------------------------------------------\nNEW REQUEST:\n\n");
	# editor_log(var_log($_REQUEST));
	
	$map = new WeatherMap;
	$map->context = 'editor';
	
	$fromplug = FALSE;
	if(isset($_REQUEST['plug']) && (intval($_REQUEST['plug'])==1) ) { $fromplug = TRUE; }
	if($FROM_CACTI) {
		$fromplug = TRUE;
	}
	
	switch($action)
	{
	case 'newmap':
		$map->WriteConfig($mapfile);
		break;

	case 'newmapcopy':
		if(isset($_REQUEST['sourcemap'])) { $sourcemapname = $_REQUEST['sourcemap']; }
		
		$sourcemapname = wm_editor_sanitize_conffile($sourcemapname);
		
		if($sourcemapname != "") {
		    $sourcemap = $mapdir.'/'.$sourcemapname;
		    if( file_exists($sourcemap) && is_readable($sourcemap) ) {
			$map->ReadConfig($sourcemap);
			$map->WriteConfig($mapfile);
		    }
		}
		break;

	case 'font_samples':
		$map->ReadConfig($mapfile);
		ksort($map->fonts);
		header('Content-type: image/png');

		$keyfont = 2;
		$keyheight = imagefontheight($keyfont)+2;
		
		$sampleheight = 32;
		// $im = imagecreate(250,imagefontheight(5)+5);
		$im = imagecreate(2000,$sampleheight);
		$imkey = imagecreate(2000,$keyheight);

		$white = imagecolorallocate($im,255,255,255);
		$black = imagecolorallocate($im,0,0,0);
		$whitekey = imagecolorallocate($imkey,255,255,255);
		$blackkey = imagecolorallocate($imkey,0,0,0);

		$x = 3;
		#for($i=1; $i< 6; $i++)
		foreach ($map->fonts as $fontnumber => $font)
		{
			$string = "Abc123%";
			$keystring = "Font $fontnumber";
			list($width,$height) = $map->myimagestringsize($fontnumber,$string);
			list($kwidth,$kheight) = $map->myimagestringsize($keyfont,$keystring);
			
			if($kwidth > $width) $width = $kwidth;
			
			$y = ($sampleheight/2) + $height/2;
			$map->myimagestring($im, $fontnumber, $x, $y, $string, $black);
			$map->myimagestring($imkey, $keyfont,$x,$keyheight,"Font $fontnumber",$blackkey);
						
			$x = $x + $width + 6;
		}
		$im2 = imagecreate($x,$sampleheight + $keyheight);
		imagecopy($im2,$im, 0,0, 0,0, $x, $sampleheight);
		imagecopy($im2,$imkey, 0,$sampleheight, 0,0,  $x, $keyheight);
		imagedestroy($im);
		imagepng($im2);
		imagedestroy($im2);

		exit();        
		break;

	case 'draw':
		header('Content-type: image/png');
		$map->ReadConfig($mapfile);

		if($selected != '')
		{
			if(substr($selected,0,5) == 'NODE:')
			{
				$nodename = substr($selected,5);
				$map->nodes[$nodename]->selected=1;
			}

			if(substr($selected,0,5) == 'LINK:')
			{
				$linkname = substr($selected,5);
				$map->links[$linkname]->selected=1;
			}
		}

		$map->sizedebug = TRUE;		
		$map->DrawMap('','',250,TRUE,$use_overlay,$use_relative_overlay);
		exit();
		break;

	case 'show_config':
		header('Content-type: text/plain');

		$fd = fopen($mapfile,'r');
		while (!feof($fd))
		{
			$buffer = fgets($fd, 4096);
			echo $buffer;
		}
		fclose($fd);

		exit();
		break;
        
    case 'fetch_config':
	    $map->ReadConfig($mapfile);
	    header('Content-type: text/plain');
	    $item_name = $_REQUEST['item_name'];
	    $item_type = $_REQUEST['item_type'];
	    
	    $ok=FALSE;

	    if($item_type == 'node'){
		if (isset($map->nodes[$item_name])) {
		    print $map->nodes[$item_name]->WriteConfig();
		    $ok = TRUE;
		}
	    }
	    if($item_type == 'link') {
		if(isset($map->links[$item_name])) {
		    print $map->links[$item_name]->WriteConfig();
		    $ok = TRUE;
		}
	    }
		
	    if (! $ok) { 
	        print "# the request item didn't exist. That's probably a bug.\n"; 
	    }
		
	    exit();
	    break;

	case "set_link_config":
		$map->ReadConfig($mapfile);

		$link_name = $_REQUEST['link_name'];
		$link_config = fix_gpc_string($_REQUEST['item_configtext']);

		if(isset($map->links[$link_name])) {		                
		    $map->links[$link_name]->config_override = $link_config;
		    
		    $map->WriteConfig($mapfile);
		    // now clear and reload the map object, because the in-memory one is out of sync
		    // - we don't know what changes the user made here, so we just have to reload.
		    unset($map);
		    $map = new WeatherMap;
		    $map->context = 'editor';
		    $map->ReadConfig($mapfile);
		}
		break;

	case "set_node_config":
		$map->ReadConfig($mapfile);

		$node_name = $_REQUEST['node_name'];
		$node_config = fix_gpc_string($_REQUEST['item_configtext']);
		
		if(isset($map->nodes[$node_name])) {		                
		    $map->nodes[$node_name]->config_override = $node_config;
		    
		    $map->WriteConfig($mapfile);
		    // now clear and reload the map object, because the in-memory one is out of sync
		    // - we don't know what changes the user made here, so we just have to reload.
		    unset($map);
		    $map = new WeatherMap;
		    $map->context = 'editor';
		    $map->ReadConfig($mapfile);
		}
		break;

	case "set_node_properties":
		$map->ReadConfig($mapfile);

		$node_name = $_REQUEST['node_name'];
		$new_node_name = $_REQUEST['node_new_name'];

		// first check if there's a rename...
		if($node_name != $new_node_name && strpos($new_node_name," ") === false)
		{
		    if(!isset($map->nodes[$new_node_name]))
		    {
			    // we need to rename the node first.					
			    $newnode = $map->nodes[$node_name];
			    $newnode->name = $new_node_name;
			    $map->nodes[$new_node_name] = $newnode;
			    unset($map->nodes[$node_name]);

			    // find the references elsewhere to the old node name.
			    // First, relatively-positioned NODEs	
			    foreach ($map->nodes as $node)
			    {
				    if($node->relative_to == $node_name)
				    {
					    $map->nodes[$node->name]->relative_to = $new_node_name;
				    }
			    }
			    // Next, LINKs that use this NODE as an end.	
			    foreach ($map->links as $link)
			    {
				if(isset($link->a))
				{
				    if($link->a->name == $node_name)
				    {
					    $map->links[$link->name]->a = $newnode;
				    }
				    if($link->b->name == $node_name)
				    {
					    $map->links[$link->name]->b = $newnode;
				    }
				    // while we're here, VIAs can also be relative to a NODE,
				    // so check if any of those need to change
				    if( (count($link->vialist)>0) )
				    {
					$vv=0;
					foreach($link->vialist as $v)
					{
					    if(isset($v[2]) && $v[2] == $node_name)
					    {
						// die PHP4, die!
						$map->links[$link->name]->vialist[$vv][2] = $new_node_name;
					    }
					    $vv++;
					}                                            
				    }
				}
			    }
		    }
		    else
		    {
			// silently ignore attempts to rename a node to an existing name
			$new_node_name = $node_name;
		    }
		}

		// by this point, and renaming has been done, and new_node_name will always be the right name
		$map->nodes[$new_node_name]->label = wm_editor_sanitize_string($_REQUEST['node_label']);
		$map->nodes[$new_node_name]->infourl[IN] = wm_editor_sanitize_string($_REQUEST['node_infourl']);
		
		$urls = preg_split('/\s+/', $_REQUEST['node_hover'], -1, PREG_SPLIT_NO_EMPTY);
		$map->nodes[$new_node_name]->overliburl[IN] = $urls;
		$map->nodes[$new_node_name]->overliburl[OUT] = $urls;
		
		$map->nodes[$new_node_name]->x = intval($_REQUEST['node_x']);
		$map->nodes[$new_node_name]->y = intval($_REQUEST['node_y']);

		if($_REQUEST['node_iconfilename'] == '--NONE--')
		{
		    $map->nodes[$new_node_name]->iconfile='';   
		}
		else
		{
		    // AICONs mess this up, because they're not fully supported by the editor, but it can still break them
		    if($_REQUEST['node_iconfilename'] != '--AICON--') {
			    $iconfile = stripslashes($_REQUEST['node_iconfilename']);			    
			    $map->nodes[$new_node_name]->iconfile = $iconfile;
		    }
		}

		$map->WriteConfig($mapfile);
		break;

	case "set_link_properties":
		$map->ReadConfig($mapfile);
		$link_name = $_REQUEST['link_name'];

		if(strpos($link_name," ") === false) {
		    $map->links[$link_name]->width = floatval($_REQUEST['link_width']);
		    $map->links[$link_name]->infourl[IN] = wm_editor_sanitize_string($_REQUEST['link_infourl']);
		    $map->links[$link_name]->infourl[OUT] = wm_editor_sanitize_string($_REQUEST['link_infourl']);
		    $urls = preg_split('/\s+/', $_REQUEST['link_hover'], -1, PREG_SPLIT_NO_EMPTY);
		    $map->links[$link_name]->overliburl[IN] = $urls;
		    $map->links[$link_name]->overliburl[OUT] = $urls;
		    
		    $map->links[$link_name]->comments[IN] =  wm_editor_sanitize_string($_REQUEST['link_commentin']);
		    $map->links[$link_name]->comments[OUT] = wm_editor_sanitize_string($_REQUEST['link_commentout']);
		    $map->links[$link_name]->commentoffset_in =  intval($_REQUEST['link_commentposin']);
		    $map->links[$link_name]->commentoffset_out = intval($_REQUEST['link_commentposout']); 

		    // $map->links[$link_name]->target = $_REQUEST['link_target'];

		    $targets = preg_split('/\s+/',$_REQUEST['link_target'],-1,PREG_SPLIT_NO_EMPTY); 
		    $new_target_list = array();

		    foreach ($targets as $target)
		    {
			// we store the original TARGET string, and line number, along with the breakdown, to make nicer error messages later
			$newtarget = array($target,'traffic_in','traffic_out',0,$target);

			// if it's an RRD file, then allow for the user to specify the
			// DSs to be used. The default is traffic_in, traffic_out, which is
			// OK for Cacti (most of the time), but if you have other RRDs...
			if(preg_match("/(.*\.rrd):([\-a-zA-Z0-9_]+):([\-a-zA-Z0-9_]+)$/i",$target,$matches))
			{
				$newtarget[0] = $matches[1];
				$newtarget[1] = $matches[2];
				$newtarget[2] = $matches[3];
			}
			// now we've (maybe) messed with it, we'll store the array of target specs
			$new_target_list[] = $newtarget;
		    }
		    $map->links[$link_name]->targets = $new_target_list;

		    $bwin = $_REQUEST['link_bandwidth_in'];
		    $bwout = $_REQUEST['link_bandwidth_out'];

		    if(isset($_REQUEST['link_bandwidth_out_cb']) && $_REQUEST['link_bandwidth_out_cb'] == 'symmetric')
		    {
			    $bwout = $bwin;
		    }

		    if(wm_editor_validate_bandwidth($bwin)) {
			$map->links[$link_name]->max_bandwidth_in_cfg = $bwin;
			$map->links[$link_name]->max_bandwidth_in = unformat_number($bwin, $map->kilo);
		    
		    }
		    if(wm_editor_validate_bandwidth($bwout)) {
			$map->links[$link_name]->max_bandwidth_out_cfg = $bwout;		    
			$map->links[$link_name]->max_bandwidth_out = unformat_number($bwout, $map->kilo);
		    }
		    // $map->links[$link_name]->SetBandwidth($bwin,$bwout);
		    
		    $map->WriteConfig($mapfile);
		}
		break;

	case "set_map_properties":
		$map->ReadConfig($mapfile);

		$map->title = wm_editor_sanitize_string($_REQUEST['map_title']);
		$map->keytext['DEFAULT'] = wm_editor_sanitize_string($_REQUEST['map_legend']);
		$map->stamptext = wm_editor_sanitize_string($_REQUEST['map_stamp']);

		$map->htmloutputfile = wm_editor_sanitize_file($_REQUEST['map_htmlfile'],array("html") );
		$map->imageoutputfile = wm_editor_sanitize_file($_REQUEST['map_pngfile'],array("png","jpg","gif","jpeg"));

		$map->width = intval($_REQUEST['map_width']);
		$map->height = intval($_REQUEST['map_height']);

		// XXX sanitise this a bit
		if($_REQUEST['map_bgfile'] == '--NONE--')
		{
		    $map->background='';    
		}
		else
		{		    
		    $map->background = wm_editor_sanitize_file(stripslashes($_REQUEST['map_bgfile']),array("png","jpg","gif","jpeg") );
		}

		$inheritables = array(
			array('link','width','map_linkdefaultwidth',"float"),
		);

		handle_inheritance($map, $inheritables);	
		$map->links['DEFAULT']->width = intval($_REQUEST['map_linkdefaultwidth']);
		$map->links['DEFAULT']->add_note("my_width", intval($_REQUEST['map_linkdefaultwidth']));
                
		$bwin = $_REQUEST['map_linkdefaultbwin'];
		$bwout = $_REQUEST['map_linkdefaultbwout'];
		
		$bwin_old = $map->links['DEFAULT']->max_bandwidth_in_cfg;
		$bwout_old = $map->links['DEFAULT']->max_bandwidth_out_cfg;
		
		if(! wm_editor_validate_bandwidth($bwin)) {
		    $bwin = $bwin_old;
		}

		if(! wm_editor_validate_bandwidth($bwout)) {
		    $bwout = $bwout_old;
		}		

		if( ($bwin_old != $bwin) || ($bwout_old != $bwout) )
		{
			$map->links['DEFAULT']->max_bandwidth_in_cfg = $bwin;
			$map->links['DEFAULT']->max_bandwidth_out_cfg = $bwout;
			$map->links['DEFAULT']->max_bandwidth_in = unformat_number($bwin, $map->kilo);
                        $map->links['DEFAULT']->max_bandwidth_out = unformat_number($bwout, $map->kilo);
			
			// $map->defaultlink->SetBandwidth($bwin,$bwout);
			foreach ($map->links as $link)
			{
			    if( ($link->max_bandwidth_in_cfg == $bwin_old) || ($link->max_bandwidth_out_cfg == $bwout_old) )
			    {
	    //			$link->SetBandwidth($bwin,$bwout);
				    $link_name = $link->name;
				    $map->links[$link_name]->max_bandwidth_in_cfg = $bwin;
				    $map->links[$link_name]->max_bandwidth_out_cfg = $bwout;
				    $map->links[$link_name]->max_bandwidth_in = unformat_number($bwin, $map->kilo);
				    $map->links[$link_name]->max_bandwidth_out = unformat_number($bwout, $map->kilo);
			    }
			}
		}

		$map->WriteConfig($mapfile);
		break; 

	case 'set_map_style':
		$map->ReadConfig($mapfile);

		if(wm_editor_validate_one_of($_REQUEST['mapstyle_htmlstyle'],array('static','overlib'),false)) {
		    $map->htmlstyle = strtolower($_REQUEST['mapstyle_htmlstyle']); 		    
		}

		$map->keyfont = intval($_REQUEST['mapstyle_legendfont']);

		$inheritables = array(
			array('link','labelstyle','mapstyle_linklabels',""),
			array('link','bwfont','mapstyle_linkfont',"int"),
			array('link','arrowstyle','mapstyle_arrowstyle',""),
			array('node','labelfont','mapstyle_nodefont',"int")
			);

		handle_inheritance($map, $inheritables);
		
		$map->WriteConfig($mapfile);
		break;

	case "add_link":
		$map->ReadConfig($mapfile);

		$param2 = $_REQUEST['param'];
		# $param2 = substr($param2,0,-2);
		$newaction = 'add_link2';
              #  print $newaction;
		$selected = 'NODE:'.$param2;
                
		break;

	case "add_link2":
		$map->ReadConfig($mapfile);
		$a = $_REQUEST['param2'];
		$b = $_REQUEST['param'];
		# $b = substr($b,0,-2);
		$log = "[$a -> $b]";

		if($a != $b && isset($map->nodes[$a]) && isset($map->nodes[$b]) )
		{
			$newlink = new WeatherMapLink;
			$newlink->Reset($map);
			
			$newlink->a = $map->nodes[$a];
			$newlink->b = $map->nodes[$b];
			
			// $newlink->SetBandwidth($map->defaultlink->max_bandwidth_in_cfg, $map->defaultlink->max_bandwidth_out_cfg);
						
			$newlink->width = $map->links['DEFAULT']->width;

			// make sure the link name is unique. We can have multiple links between
			// the same nodes, these days
			$newlinkname = "$a-$b";
			while(array_key_exists($newlinkname,$map->links))
			{
				$newlinkname .= "a";
			}
			$newlink->name = $newlinkname;
			$newlink->defined_in = $map->configfile;
			$map->links[$newlinkname] = $newlink;
			array_push($map->seen_zlayers[$newlink->zorder], $newlink);

			$map->WriteConfig($mapfile);
		}          
		break;

	case "place_legend":
		$x = snap( intval($_REQUEST['x']) ,$grid_snap_value);
		$y = snap( intval($_REQUEST['y']) ,$grid_snap_value);
		$scalename = wm_editor_sanitize_name($_REQUEST['param']);

		$map->ReadConfig($mapfile);

		$map->keyx[$scalename] = $x;
		$map->keyy[$scalename] = $y;

		$map->WriteConfig($mapfile);
		break;

	case "place_stamp":
		$x = snap( intval($_REQUEST['x']), $grid_snap_value);
		$y = snap( intval($_REQUEST['y']), $grid_snap_value);

		$map->ReadConfig($mapfile);

		$map->timex = $x;
		$map->timey = $y;

		$map->WriteConfig($mapfile);
		break;

		
	case "via_link":
		$x = intval($_REQUEST['x']);
		$y = intval($_REQUEST['y']);
		$link_name = wm_editor_sanitize_name($_REQUEST['link_name']);

		$map->ReadConfig($mapfile);

		if(isset($map->links[$link_name])) {
		    $map->links[$link_name]->vialist = array(array(0 =>$x, 1=>$y));
		    $map->WriteConfig($mapfile);
		}
		
		break;

		
	case "move_node":
		$x = snap( intval($_REQUEST['x']),  $grid_snap_value);
		$y = snap( intval($_REQUEST['y']), $grid_snap_value);
		$node_name = wm_editor_sanitize_name($_REQUEST['node_name']);

		$map->ReadConfig($mapfile);

		if(isset($map->nodes[$node_name])) {
		    // This is a complicated bit. Find out if this node is involved in any
		    // links that have VIAs. If it is, we want to rotate those VIA points
		    // about the *other* node in the link
		    foreach ($map->links as $link)
		    {
			    if( (count($link->vialist)>0)  && (($link->a->name == $node_name) || ($link->b->name == $node_name)) )
			    {	
				    // get the other node from us
				    if($link->a->name == $node_name) $pivot = $link->b;
				    if($link->b->name == $node_name) $pivot = $link->a; 
				    
				    if( ($link->a->name == $node_name) && ($link->b->name == $node_name) )
				    {
					    // this is a wierd special case, but it is possible
					    # $log .= "Special case for node1->node1 links\n";
					    $dx = $link->a->x - $x;
					    $dy = $link->a->y - $y;
					    
					    for($i=0; $i<count($link->vialist); $i++)
					    {
						    $link->vialist[$i][0] = $link->vialist[$i][0]-$dx;
						    $link->vialist[$i][1] = $link->vialist[$i][1]-$dy;
					    }
				    }
				    else
				    {
					    $pivx = $pivot->x;
					    $pivy = $pivot->y;
					    
					    $dx_old = $pivx - $map->nodes[$node_name]->x;
					    $dy_old = $pivy - $map->nodes[$node_name]->y;
					    $dx_new = $pivx - $x;
					    $dy_new = $pivy - $y;
					    $l_old = sqrt($dx_old*$dx_old + $dy_old*$dy_old);
					    $l_new = sqrt($dx_new*$dx_new + $dy_new*$dy_new);
					    
					    $angle_old = rad2deg(atan2(-$dy_old,$dx_old));
					    $angle_new = rad2deg(atan2(-$dy_new,$dx_new));
										    
					    # $log .= "$pivx,$pivy\n$dx_old $dy_old $l_old => $angle_old\n";
					    # $log .= "$dx_new $dy_new $l_new => $angle_new\n";
				    
					    // the geometry stuff uses a different point format, helpfully
					    $points = array();
					    foreach($link->vialist as $via)
					    {
						    $points[] = $via[0];
						    $points[] = $via[1];
					    }
					    
					    $scalefactor = $l_new/$l_old;
					    # $log .= "Scale by $scalefactor along link-line";
					    
					    // rotate so that link is along the axis
					    rotateAboutPoint($points,$pivx, $pivy, deg2rad($angle_old));
					    // do the scaling in here
					    for($i=0; $i<(count($points)/2); $i++)
					    {
						    $basex = ($points[$i*2] - $pivx) * $scalefactor + $pivx;
						    $points[$i*2] = $basex;
					    }
					    // rotate back so that link is along the new direction
					    rotateAboutPoint($points,$pivx, $pivy, deg2rad(-$angle_new));
					    
					    // now put the modified points back into the vialist again
					    $v = 0; $i = 0;
					    foreach($points as $p)
					    {
						    // skip a point if it positioned relative to a node. Those shouldn't be rotated (well, IMHO)
						    if(!isset($link->vialist[$v][2]))
						    {
							    $link->vialist[$v][$i]=$p;
						    }
						    $i++;
						    if($i==2) { $i=0; $v++;}					
					    }
				    }
			    }
		    }
		    
		    $map->nodes[$node_name]->x = $x;
		    $map->nodes[$node_name]->y = $y;
    
		    $map->WriteConfig($mapfile);
		}
		break;


	case "link_tidy":
		$map->ReadConfig($mapfile);

		$target = wm_editor_sanitize_name($_REQUEST['param']);

		if(isset($map->links[$target])) {
			// draw a map and throw it away, to calculate all the bounding boxes
			$map->DrawMap('null');

			tidy_link($map,$target);

			$map->WriteConfig($mapfile);
		}
		break;
	case "retidy":
		$map->ReadConfig($mapfile);

		// draw a map and throw it away, to calculate all the bounding boxes
		$map->DrawMap('null');
		retidy_links($map);

		$map->WriteConfig($mapfile);

		break;

	case "retidy_all":
		$map->ReadConfig($mapfile);

		// draw a map and throw it away, to calculate all the bounding boxes
		$map->DrawMap('null');
		retidy_links($map,TRUE);

		$map->WriteConfig($mapfile);

		break;

	case "untidy":
		$map->ReadConfig($mapfile);

		// draw a map and throw it away, to calculate all the bounding boxes
		$map->DrawMap('null');
		untidy_links($map);

		$map->WriteConfig($mapfile);

		break;


		case "delete_link":
		$map->ReadConfig($mapfile);

		$target = wm_editor_sanitize_name($_REQUEST['param']);
		$log = "delete link ".$target;

		if(isset($map->links[$target])) {
		    unset($map->links[$target]);
		    
		    $map->WriteConfig($mapfile);
		}
		break;

	case "add_node":
		$x = snap(intval($_REQUEST['x']), $grid_snap_value);
		$y = snap(intval($_REQUEST['y']), $grid_snap_value);

		$map->ReadConfig($mapfile);
		
		$newnodename = sprintf("node%05d",time()%10000);
		while(array_key_exists($newnodename,$map->nodes))
		{
			$newnodename .= "a";
		}
		
		$node = new WeatherMapNode;
		$node->name = $newnodename;
		$node->template = "DEFAULT";
		$node->Reset($map);
				
		$node->x = $x;
		$node->y = $y;
		$node->defined_in = $map->configfile;
				
		array_push($map->seen_zlayers[$node->zorder], $node);			
		
		// only insert a label if there's no LABEL in the DEFAULT node.
		// otherwise, respect the template.
		if($map->nodes['DEFAULT']->label == $map->nodes[':: DEFAULT ::']->label)
		{
			$node->label = "Node";
		}
		
		$map->nodes[$node->name] = $node;
		$log = "added a node called $newnodename at $x,$y to $mapfile";
						
		$map->WriteConfig($mapfile);
		break;

	case "editor_settings":
            // have to do this, otherwise the editor will be unresponsive afterwards - not actually going to change anything!
            $map->ReadConfig($mapfile);

	    $use_overlay = (isset($_REQUEST['editorsettings_showvias']) ? intval($_REQUEST['editorsettings_showvias']) : FALSE);
	    $use_relative_overlay = (isset($_REQUEST['editorsettings_showrelative']) ? intval($_REQUEST['editorsettings_showrelative']) : FALSE);
	    $grid_snap_value = (isset($_REQUEST['editorsettings_gridsnap']) ? intval($_REQUEST['editorsettings_gridsnap']) : 0);
			    
	    break;
		
	case "delete_node":
		$map->ReadConfig($mapfile);

		$target = wm_editor_sanitize_name($_REQUEST['param']);
		if(isset($map->nodes[$target])) {
		    $log = "delete node ".$target;
    
		    foreach ($map->links as $link)
		    {
			    if( isset($link->a) )
			    {
				    if( ($target == $link->a->name) || ($target == $link->b->name) )
				    {
					    unset($map->links[$link->name]);
				    }
			    }
		    }           
    
		    unset($map->nodes[$target]);
    
		    $map->WriteConfig($mapfile);
		}
		break;

	case "clone_node":
		$map->ReadConfig($mapfile);

		$target = wm_editor_sanitize_name($_REQUEST['param']);
		if(isset($map->nodes[$target])) {
		    $log = "clone node ".$target;
    
		    $newnodename = $target;
		    do
		    {
			    $newnodename = $newnodename."_copy";
		    } while(isset($map->nodes[$newnodename]));
		    
		    $node = new WeatherMapNode;
		    $node->Reset($map);
		    $node->CopyFrom($map->nodes[$target]);

			# CopyFrom skips this one, because it's also the function used by template inheritance
			# - but for Clone, we DO want to copy the template too
			$node->template = $map->nodes[$target]->template;
    
		    $node->name = $newnodename;
		    $node->x += 30;
		    $node->y += 30;
		    $node->defined_in = $mapfile;
    
    
		    $map->nodes[$newnodename] = $node;
		    array_push($map->seen_zlayers[$node->zorder], $node);
    
		    $map->WriteConfig($mapfile);
		}
		break;

		// no action was defined - starting a new map?
	default:
		$map->ReadConfig($mapfile);
		break;   
	}	
	
	//by here, there should be a valid $map - either a blank one, the existing one, or the existing one with requested changes
	wm_debug("Finished modifying\n");

	// now we'll just draw the full editor page, with our new knowledge

	$imageurl = '?mapname='.urlencode($mapname) . '&amp;action=draw';
	if($selected != '')
	{
		$imageurl .= '&amp;selected='.urlencode(wm_editor_sanitize_selected($selected));
	}

	$imageurl .= '&amp;unique='.time();

	// build up the editor's list of used images
	if($map->background != '') $map->used_images[] = $map->background;
	foreach ($map->nodes as $n)
	{
		if($n->iconfile != '' && ! preg_match("/^(none|nink|inpie|outpie|box|rbox|gauge|round)$/",$n->iconfile))
			$map->used_images[] = $n->iconfile;		
	}

	// get the list from the images/ folder too
	$imlist = get_imagelist("images");
	
	$fontlist = array();
	
	setcookie("wmeditor", ($use_overlay ? "1":"0") .":". ($use_relative_overlay ? "1":"0") . ":" . intval($grid_snap_value), time()+60*60*24*30 );

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<style type="text/css">
<?php
		// if the cacti config was included properly, then 
		// this will be non-empty, and we can unhide the cacti links in the Link Properties box
		if( ! isset($config['cacti_version']) )
		{
			echo "    .cactilink { display: none; }\n";
			echo "    .cactinode { display: none; }\n";
		}
?>
	</style>
  <link rel="stylesheet" type="text/css" media="screen" href="editor-resources/oldeditor.css" />
<script src="editor-resources/jquery-latest.min.js" type="text/javascript"></script>
<script src="editor-resources/editor.js" type="text/javascript"></script>
	<script type="text/javascript">
	
	var fromplug=<?php echo ($fromplug==TRUE ? 1:0); ?>;
	var editor_url = '<?php echo $editor_name; ?>';
	
	// the only javascript in here should be the objects representing the map itself
	// all code should be in editor.js
	<?php print $map->asJS() ?>
<?php

	// append any images used in the map that aren't in the images folder
	foreach ($map->used_images as $im)
	{
		if(! in_array($im,$imlist))
		{
			$imlist[]=$im;
		}
	}

	sort($imlist);
?>
	</script>
  <title>PHP Weathermap Editor <?php echo $WEATHERMAP_VERSION; ?></title>
</head>

<body id="mainview">
  <div id="toolbar">
	<ul>
          <li class="tb_active" id="tb_newfile">Change<br />File</li>
	  <li class="tb_active" id="tb_addnode">Add<br />Node</li>
	  <li class="tb_active" id="tb_addlink">Add<br />Link</li>
	  <li class="tb_active" id="tb_poslegend">Position<br />Legend</li>
	  <li class="tb_active" id="tb_postime">Position<br />Timestamp</li>
	  <li class="tb_active" id="tb_mapprops">Map<br />Properties</li>
	  <li class="tb_active" id="tb_mapstyle">Map<br />Style</li>
	  <li class="tb_active" id="tb_colours">Manage<br />Colors</li>
	  <li class="tb_active" id="tb_manageimages">Manage<br />Images</li>
	  <li class="tb_active" id="tb_prefs">Editor<br />Settings</li>
          <li class="tb_coords" id="tb_coords">Position<br />---, ---</li>
	  <li class="tb_help"><span id="tb_help">or click a Node or Link to edit it's properties</span></li>
	</ul>
  </div>
  <form action="<?php echo $editor_name ?>" method="post" name="frmMain">
	<div align="center" id="mainarea">
		<input type="hidden" name="plug" value="<?php echo ($fromplug==TRUE ? 1 : 0) ?>" />
	 <input style="display:none" type="image"
	  src="<?php echo $imageurl; ?>" id="xycapture" /><img src=
	  "<?php echo $imageurl; ?>" id="existingdata" alt="Weathermap" usemap="#weathermap_imap"
	   />
	   <div class="debug"><p><strong>Debug:</strong>
			   <a href="?<?php echo ($fromplug==TRUE ? 'plug=1&amp;' : ''); ?>action=retidy_all&amp;mapname=<?php echo  htmlspecialchars($mapname) ?>">Re-tidy ALL</a>
			   <a href="?<?php echo ($fromplug==TRUE ? 'plug=1&amp;' : ''); ?>action=retidy&amp;mapname=<?php echo  htmlspecialchars($mapname) ?>">Re-tidy</a>
			   <a href="?<?php echo ($fromplug==TRUE ? 'plug=1&amp;' : ''); ?>action=untidy&amp;mapname=<?php echo  htmlspecialchars($mapname) ?>">Un-tidy</a>


			   <a href="?<?php echo ($fromplug==TRUE ? 'plug=1&amp;' : ''); ?>action=nothing&amp;mapname=<?php echo  htmlspecialchars($mapname) ?>">Do Nothing</a>
	   <span><label for="mapname">mapfile</label><input type="text" name="mapname" value="<?php echo htmlspecialchars($mapname); ?>" /></span>
	   <span><label for="action">action</label><input type="text" id="action" name="action" value="<?php echo htmlspecialchars($newaction); ?>" /></span>
	  <span><label for="param">param</label><input type="text" name="param" id="param" value="" /></span>
            <span><label for="param2">param2</label><input type="text" name="param2" id="param2" value="<?php echo htmlspecialchars($param2); ?>" /></span> 
	  <span><label for="debug">debug</label><input id="debug" value="" name="debug" /></span> 
	  <a target="configwindow" href="?<?php echo ($fromplug==TRUE ? 'plug=1&amp;':''); ?>action=show_config&amp;mapname=<?php echo  urlencode($mapname) ?>">See config</a></p>
	<pre><?php echo  htmlspecialchars($log) ?></pre>
	  </div>
<?php        	
	// we need to draw and throw away a map, to get the
	// dimensions for the imagemap. Oh well.
	$map->DrawMap('null');
	$map->htmlstyle='editor';
	$map->PreloadMapHTML();

	print $map->SortedImagemap("weathermap_imap");

	#print $map->imap->subHTML("LEGEND:");
	#print $map->imap->subHTML("TIMESTAMP");
	#print $map->imap->subHTML("NODE:");
	#print $map->imap->subHTML("LINK:");
                
?>
	</div><!-- Node Properties -->

	<div id="dlgNodeProperties" class="dlgProperties">
	  <div class="dlgTitlebar">
		Node Properties
		<input size="6" name="node_name" type="hidden" />
		<ul>
		  <li><a id="tb_node_submit" class="wm_submit" title="Submit any changes made">Submit</a></li>
		  <li><a id="tb_node_cancel" class="wm_cancel" title="Cancel any changes">Cancel</a></li>
		</ul>
	  </div>

	  <div class="dlgBody">
		<table>
		<tr>
			<th>Position</th>
			<td><input id="node_x" name="node_x" size=4 type="text" />,<input id="node_y" name="node_y" size=4 type="text" /></td>
		</tr>
		  <tr>
			<th>Internal Name</th>
			<td><input id="node_new_name" name="node_new_name" type="text" /></td>
		  </tr>
		  <tr>
			<th>Label</th>
			<td><input id="node_label" name="node_label" type="text" /></td>
		  </tr>
		  <tr>
			<th>Info URL</th>
			<td><input id="node_infourl" name="node_infourl" type="text" /></td>
		  </tr>
		  <tr>
			<th>'Hover' Graph URL</th>
			<td><input id="node_hover" name="node_hover" type="text" />
			<span class="cactinode"><a id="node_cactipick">[Pick from Cacti]</a></span></td>
		  </tr>
		  <tr>
			<th>Icon Filename</th>
			<td><select id="node_iconfilename" name="node_iconfilename">

<?php
	if(count($imlist)==0)
	{
		print '<option value="--NONE--">(no images are available)</option>';
	}
	else
	{
		print '<option value="--NONE--">--NO ICON--</option>';
		print '<option value="--AICON--">--ARTIFICIAL ICON--</option>';
		foreach ($imlist as $im)
		{
			print "<option ";
			print "value=\"".htmlspecialchars($im)."\">".htmlspecialchars($im)."</option>\n";
		}
	}
?>
		</select></td>
		  </tr>
		  <tr>
			<th></th>
			<td>&nbsp;</td>
		  </tr>
		  <tr>
			<th></th>
			<td><a id="node_move" class="dlgTitlebar">Move</a><a class="dlgTitlebar" id="node_delete">Delete</a><a class="dlgTitlebar" id="node_clone">Clone</a><a class="dlgTitlebar" id="node_edit">Edit</a></td>
		  </tr>
		</table>
	  </div>

	  <div class="dlgHelp" id="node_help">
		Helpful text will appear here, depending on the current
		item selected. It should wrap onto several lines, if it's
		necessary for it to do that.
	  </div>
	</div><!-- Node Properties -->




	<!-- Link Properties -->

	<div id="dlgLinkProperties" class="dlgProperties">
	  <div class="dlgTitlebar">
		Link Properties

		<ul>
		  <li><a title="Submit any changes made"  class="wm_submit" id="tb_link_submit">Submit</a></li>
		  <li><a title="Cancel any changes" class="wm_cancel" id="tb_link_cancel">Cancel</a></li>
		</ul>
	  </div>

	  <div class="dlgBody">
		<div class="comment">
		  Link from '<span id="link_nodename1">%NODE1%</span>' to '<span id="link_nodename2">%NODE2%</span>'
		</div>

		<input size="6" name="link_name" type="hidden" />

		  <table width="100%">
			<tr>
			  <th>Maximum Bandwidth<br />
			  Into '<span id="link_nodename1a">%NODE1%</span>'</th>
			  <td><input size="8" id="link_bandwidth_in" name="link_bandwidth_in" type=
			  "text" /> bits/sec</td>
			</tr>
			<tr>
			  <th>Maximum Bandwidth<br />
			  Out of '<span id="link_nodename1b">%NODE1%</span>'</th>
			  <td><input type="checkbox" id="link_bandwidth_out_cb" name=
			  "link_bandwidth_out_cb" value="symmetric" />Same As
			  'In' or <input id="link_bandwidth_out" name="link_bandwidth_out"
			  size="8" type="text" /> bits/sec</td>
			</tr>
			<tr>
			  <th>Data Source</th>
			  <td><input id="link_target" name="link_target" type="text" /> <span class="cactilink"><a id="link_cactipick">[Pick
			  from Cacti]</a></span></td>
			</tr>
			<tr>
			  <th>Link Width</th>
			  <td><input id="link_width" name="link_width" size="3" type="text" />
			  pixels</td>
			</tr>
			<tr>
			  <th>Info URL</th>
			  <td><input id="link_infourl" size="30" name="link_infourl" type="text" /></td>
			</tr>
			<tr>
			  <th>'Hover' Graph URL</th>
			  <td><input id="link_hover"  size="30" name="link_hover" type="text" /></td>
			</tr>


                        <tr>
                               <th>IN Comment</th>
                               <td><input id="link_commentin" size="25" name="link_commentin" type="text" />
                                        <select id="link_commentposin" name="link_commentposin">
                                                    <option value=95>95%</option>
                                                    <option value=90>90%</option>
                                                    <option value=80>80%</option>
                                                    <option value=70>70%</option>
                                                    <option value=60>60%</option>
                                        </select>
                               </td>
                        </tr>
                        <tr>
                               <th>OUT Comment</th>
                               <td><input id="link_commentout" size="25" name="link_commentout" type="text" />
                                        <select id="link_commentposout" name="link_commentposout">
                                                    <option value=5>5%</option>
                                                    <option value=10>10%</option>
                                                    <option value=20>20%</option>
                                                    <option value=30>30%</option>
                                                    <option value=40>40%</option>
                                                    <option value=50>50%</option>
                                        </select>
                               </td>
                        </tr> 
		  
			<tr>
			  <th></th>
			  <td>&nbsp;</td>
			</tr>
			<tr>
			  <th></th>
			  <td><a class="dlgTitlebar" id="link_delete">Delete
			  Link</a><a class="dlgTitlebar" id="link_edit">Edit</a><a
					  class="dlgTitlebar" id="link_tidy">Tidy</a><a
							class="dlgTitlebar" id="link_via">Via</a> 
                        </td>
			</tr>
		  </table>
	  </div>

	  <div class="dlgHelp" id="link_help">
		Helpful text will appear here, depending on the current
		item selected. It should wrap onto several lines, if it's
		necessary for it to do that.
	  </div>
	</div><!-- Link Properties -->

	<!-- Map Properties -->

	<div id="dlgMapProperties" class="dlgProperties">
	  <div class="dlgTitlebar">
		Map Properties

		<ul>
		  <li><a title="Submit any changes made"  class="wm_submit" id="tb_map_submit">Submit</a></li>
		  <li><a title="Cancel any changes" class="wm_cancel" id="tb_map_cancel">Cancel</a></li>
		</ul>
	  </div>

	  <div class="dlgBody">
		<table>
		  <tr>
			<th>Map Title</th>
			<td><input id="map_title" name="map_title" size="25" type="text" value="<?php echo  htmlspecialchars($map->title) ?>"/></td>
		  </tr>
		<tr>
			<th>Legend Text</th>
			<td><input name="map_legend" size="25" type="text" value="<?php echo  htmlspecialchars($map->keytext['DEFAULT']) ?>" /></td>
		  </tr>
		<tr>
			<th>Timestamp Text</th>
			<td><input name="map_stamp" size="25" type="text" value="<?php echo  htmlspecialchars($map->stamptext) ?>" /></td>
		  </tr>

		<tr>
			<th>Default Link Width</th>
			<td><input name="map_linkdefaultwidth" size="6" type="text" value="<?php echo  htmlspecialchars($map->links['DEFAULT']->width) ?>" /> pixels</td>
		  </tr>

		<tr>
			<th>Default Link Bandwidth</th>
			<td><input name="map_linkdefaultbwin" size="6" type="text" value="<?php echo  htmlspecialchars($map->links['DEFAULT']->max_bandwidth_in_cfg) ?>" /> bit/sec in, <input name="map_linkdefaultbwout" size="6" type="text" value="<?php echo  htmlspecialchars($map->links['DEFAULT']->max_bandwidth_out_cfg) ?>" /> bit/sec out</td>
		  </tr>


		  <tr>
			<th>Map Size</th>
			<td><input name="map_width" size="5" type=
			"text"  value="<?php echo  htmlspecialchars($map->width) ?>" /> x <input name="map_height" size="5" type=
			"text"  value="<?php echo  htmlspecialchars($map->height) ?>" /> pixels</td>
		  </tr>
		   <tr>
			<th>Output Image Filename</th>
			<td><input name="map_pngfile" type="text"  value="<?php echo  htmlspecialchars($map->imageoutputfile) ?>" /></td>
		  </tr>
		  <tr>
			<th>Output HTML Filename</th>
			<td><input name="map_htmlfile" type="text" value="<?php echo  htmlspecialchars($map->htmloutputfile) ?>" /></td>
		  </tr>
		  <tr>
			<th>Background Image Filename</th>
			<td><select name="map_bgfile">

<?php
	if(count($imlist)==0)
	{
		print '<option value="--NONE--">(no images are available)</option>';
	}
	else
	{
		print '<option value="--NONE--">--NONE--</option>';
		foreach ($imlist as $im)
		{
			print "<option ";		
    			if($map->background == $im) print " selected ";
			print "value=\"".htmlspecialchars($im)."\">".htmlspecialchars($im)."</option>\n";
		}
	}
?>
			</select></td>
		  </tr>
		
		</table>
	  </div>

	  <div class="dlgHelp" id="map_help">
		Helpful text will appear here, depending on the current
		item selected. It should wrap onto several lines, if it's
		necessary for it to do that.
	  </div>
	</div><!-- Map Properties -->

	<!-- Map Style -->
	<div id="dlgMapStyle" class="dlgProperties">
	  <div class="dlgTitlebar">
		Map Style

		<ul>
		  <li><a title="Submit any changes made" id="tb_mapstyle_submit" class="wm_submit" >Submit</a></li>
		  <li><a title="Cancel any changes" class="wm_cancel" id="tb_mapstyle_cancel">Cancel</a></li>
		</ul>
	  </div>

	  <div class="dlgBody">
		<table>
		  <tr>
			<th>Link Labels</th>
			<td><select id="mapstyle_linklabels" name="mapstyle_linklabels">
			  <option <?php echo ($map->links['DEFAULT']->labelstyle=='bits' ? 'selected' : '') ?> value="bits">Bits/sec</option>
			  <option <?php echo ($map->links['DEFAULT']->labelstyle=='percent' ? 'selected' : '') ?> value="percent">Percentage</option>
			  <option <?php echo ($map->links['DEFAULT']->labelstyle=='none' ? 'selected' : '') ?> value="none">None</option>
			</select></td>
		  </tr>
		  <tr>
			<th>HTML Style</th>
			<td><select name="mapstyle_htmlstyle">
			  <option <?php echo ($map->htmlstyle=='overlib' ? 'selected' : '') ?> value="overlib">Overlib (DHTML)</option>
			  <option <?php echo ($map->htmlstyle=='static' ? 'selected' : '') ?> value="static">Static HTML</option>
			</select></td>
		  </tr>
		  <tr>
			<th>Arrow Style</th>
			<td><select name="mapstyle_arrowstyle">
			  <option <?php echo ($map->links['DEFAULT']->arrowstyle=='classic' ? 'selected' : '') ?> value="classic">Classic</option>
			  <option <?php echo ($map->links['DEFAULT']->arrowstyle=='compact' ? 'selected' : '') ?> value="compact">Compact</option>
			</select></td>
		  </tr>
		  <tr>
			<th>Node Font</th>
			<td><?php echo get_fontlist($map,'mapstyle_nodefont',$map->nodes['DEFAULT']->labelfont); ?></td>
		  </tr>
		  <tr>
			<th>Link Label Font</th>
			<td><?php echo get_fontlist($map,'mapstyle_linkfont',$map->links['DEFAULT']->bwfont); ?></td>
		  </tr>
		  <tr>
			<th>Legend Font</th>
			<td><?php echo get_fontlist($map,'mapstyle_legendfont',$map->keyfont); ?></td>
		  </tr>
		  <tr>
			<th>Font Samples:</th>
			<td><div class="fontsamples" ><img alt="Sample of defined fonts" src="?action=font_samples&mapname=<?php echo  $mapname?>" /></div><br />(Drawn using your PHP install)</td>
		  </tr>
		</table>
	  </div>

	  <div class="dlgHelp" id="mapstyle_help">
		Helpful text will appear here, depending on the current
		item selected. It should wrap onto several lines, if it's
		necessary for it to do that.
	  </div>
	</div><!-- Map Style -->



	<!-- Colours -->

	<div id="dlgColours" class="dlgProperties">
	  <div class="dlgTitlebar">
		Manage Colors

		<ul>
		  <li><a title="Submit any changes made" id="tb_colours_submit"  class="wm_submit" >Submit</a></li>
		  <li><a title="Cancel any changes" class="wm_cancel" id="tb_colours_cancel">Cancel</a></li>
		</ul>
	  </div>

	  <div class="dlgBody">
		Nothing in here works yet. The aim is to have a nice color picker somehow.
		<table>
		  <tr>
			<th>Background Color</th>
			<td></td>
		  </tr>

		  <tr>
			<th>Link Outline Color</th>
			<td></td>
		  </tr>
		<tr>
		<th>Scale Colors</th>
		<td>Some pleasant way to design the bandwidth color scale goes in here???</td>
		</tr>

		</table>
	  </div>

	  <div class="dlgHelp" id="colours_help">
		Helpful text will appear here, depending on the current
		item selected. It should wrap onto several lines, if it's
		necessary for it to do that.
	  </div>
	</div><!-- Colours -->


	<!-- Images -->

	<div id="dlgImages" class="dlgProperties">
	  <div class="dlgTitlebar">
		Manage Images

		<ul>
		  <li><a title="Submit any changes made" id="tb_images_submit"  class="wm_submit" >Submit</a></li>
		  <li><a title="Cancel any changes" class="wm_cancel" id="tb_images_cancel">Cancel</a></li>
		</ul>
	  </div>

	  <div class="dlgBody">
		<p>Nothing in here works yet. </p>
		The aim is to have some nice way to upload images which can be used as icons or backgrounds.
		These images are what would appear in the dropdown boxes that don't currently do anything in the Node and Map Properties dialogs. This may end up being a seperate page rather than a dialog box...       
	  </div>

	  <div class="dlgHelp" id="images_help">
		Helpful text will appear here, depending on the current
		item selected. It should wrap onto several lines, if it's
		necessary for it to do that.
	  </div>
	</div><!-- Images -->

        <div id="dlgTextEdit" class="dlgProperties">
	  <div class="dlgTitlebar">
		Edit Map Object
		<ul>
		  <li><a title="Submit any changes made" id="tb_textedit_submit"  class="wm_submit" >Submit</a></li>
		  <li><a title="Cancel any changes" class="wm_cancel" id="tb_textedit_cancel">Cancel</a></li>
		</ul>
	  </div>

	  <div class="dlgBody">
		<p>You can edit the map items directly here.</p>
                <textarea wrap="no" id="item_configtext" name="item_configtext" cols=40 rows=15></textarea>
	  </div>

	  <div class="dlgHelp" id="images_help">
		Helpful text will appear here, depending on the current
		item selected. It should wrap onto several lines, if it's
		necessary for it to do that.
	  </div>
	</div><!-- TextEdit -->
	
	
	<div id="dlgEditorSettings" class="dlgProperties">
	  <div class="dlgTitlebar">
		Editor Settings
		<ul>
		  <li><a title="Submit any changes made" id="tb_editorsettings_submit"  class="wm_submit" >Submit</a></li>
		  <li><a title="Cancel any changes" class="wm_cancel" id="tb_editorsettings_cancel">Cancel</a></li>
		</ul>
	  </div>

	  <div class="dlgBody">
		<table>
		    <tr>
			<th>Show VIAs overlay</th>
			<td><select id="editorsettings_showvias" name="editorsettings_showvias">
			  <option <?php echo ($use_overlay ? 'selected' : '') ?> value="1">Yes</option>
			  <option <?php echo ($use_overlay ? '' : 'selected') ?> value="0">No</option>
			    </select>
			</td>
		    </tr>
		    <tr>
			<th>Show Relative Positions overlay</th>
			<td><select id="editorsettings_showrelative" name="editorsettings_showrelative">
			  <option <?php echo ($use_relative_overlay ? 'selected' : '') ?> value="1">Yes</option>
			  <option <?php echo ($use_relative_overlay ? '' : 'selected') ?> value="0">No</option>
			    </select>
			</td>
		    </tr>
		    <tr>
			<th>Snap To Grid</th>
			<td><select id="editorsettings_gridsnap" name="editorsettings_gridsnap">
			    <option <?php echo ($grid_snap_value==0 ? 'selected' : '') ?> value="NO">No</option>
			    <option <?php echo ($grid_snap_value==5 ? 'selected' : '') ?> value="5">5 pixels</option>
			    <option <?php echo ($grid_snap_value==10 ? 'selected' : '') ?> value="10">10 pixels</option>
			    <option <?php echo ($grid_snap_value==15 ? 'selected' : '') ?> value="15">15 pixels</option>
			    <option <?php echo ($grid_snap_value==20 ? 'selected' : '') ?> value="20">20 pixels</option>
			    <option <?php echo ($grid_snap_value==50 ? 'selected' : '') ?> value="50">50 pixels</option>
			    <option <?php echo ($grid_snap_value==100 ? 'selected' : '') ?> value="100">100 pixels</option>
			    </select>
			</td>
		    </tr>
		</table>
                
	  </div>

	  <div class="dlgHelp" id="images_help">
		Helpful text will appear here, depending on the current
		item selected. It should wrap onto several lines, if it's
		necessary for it to do that.
	  </div>
	</div><!-- TextEdit -->
	
	
    </form>
</body>
</html>
<?php
} // if mapname != ''
// vim:ts=4:sw=4:
?>
