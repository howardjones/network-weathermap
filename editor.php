<?php
	$use_jquery = FALSE;

require_once 'editor.inc.php';
require_once 'Weathermap.class.php';

// sensible defaults
$mapdir='configs';
$cacti_base = '../../';
$cacti_url = '/';
$ignore_cacti=FALSE;

$config_loaded = @include_once 'editor-config.php';

// XXX - if something from the old-style config is already defined by here, we need to warn.

if( is_dir($cacti_base) && file_exists($cacti_base."/include/config.php") )
{
	// include the cacti-config, so we know about the database
	include_once($cacti_base."/include/config.php");

	// CHANGE: this to be the URL of the base of your Cacti install
	// it MUST end with a / character!
	$config['base_url']=$cacti_url;
	$cacti_found=TRUE;
}
else
{
	$cacti_found = FALSE;
}

chdir(dirname(__FILE__));

$action = '';
$mapname = '';
$selected = '';

$newaction = '';
$param = '';
$param2 = '';
$log = '';

if(!module_checks())
{
	print "<b>Required PHP extensions are not present in your mod_php/ISAPI PHP module. Please check your PHP setup to ensure you have the GD extension installed and enabled.</b><p>";
	print "If you find that the weathermap tool itself is working, from the command-line or Cacti poller, then it is possible that you have two different PHP installations. The Editor uses the same PHP that webpages on your server use, but the main weathermap tool uses the command-line PHP interpreter.<p>";
	print "<p>You should also run <a href=\"check.php\">check.php</a> to help make sure that there are no problems.</p><hr/>";
	print "Here is a copy of the phpinfo() from your PHP web module, to help debugging this...<hr>";
	phpinfo();
	exit();
}

if(isset($_REQUEST['action'])) { $action = $_REQUEST['action']; }
if(isset($_REQUEST['mapname'])) { $mapname = $_REQUEST['mapname'];  $mapname = str_replace('/','',$mapname); }
if(isset($_REQUEST['selected'])) { $selected = $_REQUEST['selected']; }

if($mapname == '')
{
	// this is the file-picker/welcome page
	show_editor_startpage();
}
else
{  
	// everything else in this file is inside this else
	$mapfile = $mapdir.'/'.$mapname;        

	$map = new WeatherMap;
	$map->context = 'editor';

	$fromplug = FALSE;
	if(isset($_REQUEST['plug']) && (intval($_REQUEST['plug'])==1) ) { $fromplug = TRUE; }

	switch($action)
	{
	case 'newmap':
		$map->WriteConfig($mapfile);
		break;

	case 'newmapcopy':
		if(isset($_REQUEST['sourcemap'])) { $sourcemapname = $_REQUEST['sourcemap']; }		
		$sourcemap = $mapdir.'/'.$sourcemapname;
		$map->ReadConfig($sourcemap);
		$map->WriteConfig($mapfile);
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
		//            $map->RandomData();
		$map->DrawMap();
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

	case "set_node_properties":
		$map->ReadConfig($mapfile);

		$node_name = $_REQUEST['node_name'];
		$new_node_name = $_REQUEST['node_new_name'];

		if($node_name != $new_node_name)
		{
			if(!isset($map->nodes[$new_node_name]))
			{
				// we need to rename the node first.					
				$newnode = $map->nodes[$node_name];
				$newnode->name = $new_node_name;
				$map->nodes[$new_node_name] = $newnode;
				unset($map->nodes[$node_name]);

				foreach ($map->links as $link)
				{
					if($link->a->name == $node_name)
					{
						$map->links[$link->name]->a = $newnode;
					}
					if($link->b->name == $node_name)
					{
						$map->links[$link->name]->b = $newnode;
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
		$map->nodes[$new_node_name]->label = $_REQUEST['node_label'];
		$map->nodes[$new_node_name]->infourl = $_REQUEST['node_infourl'];
		$map->nodes[$new_node_name]->overliburl = $_REQUEST['node_hover'];
		
		$map->nodes[$new_node_name]->x = intval($_REQUEST['node_x']);
		$map->nodes[$new_node_name]->y = intval($_REQUEST['node_y']);

		if($_REQUEST['node_iconfilename'] == '--NONE--')
		{
			$map->nodes[$new_node_name]->iconfile='';    
		}
		else
		{
			$map->nodes[$new_node_name]->iconfile = stripslashes($_REQUEST['node_iconfilename']);
		}

		$map->WriteConfig($mapfile);
		break;

	case "set_link_properties":
		$map->ReadConfig($mapfile);
		$link_name = $_REQUEST['link_name'];

		$map->links[$link_name]->width = intval($_REQUEST['link_width']);
		$map->links[$link_name]->infourl = $_REQUEST['link_infourl'];
		$map->links[$link_name]->overliburl = $_REQUEST['link_hover'];

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

		$map->links[$link_name]->SetBandwidth($bwin,$bwout);

		$map->WriteConfig($mapfile);
		break;

	case "set_map_properties":
		$map->ReadConfig($mapfile);

		$map->title = $_REQUEST['map_title'];
		$map->keytext['DEFAULT'] = $_REQUEST['map_legend'];
		$map->stamptext = $_REQUEST['map_stamp'];

		$map->htmloutputfile = $_REQUEST['map_htmlfile'];
		$map->imageoutputfile = $_REQUEST['map_pngfile'];

		$map->width = intval($_REQUEST['map_width']);
		$map->height = intval($_REQUEST['map_height']);

		// XXX sanitise this a bit
		if($_REQUEST['map_bgfile'] == '--NONE--')
		{
			$map->background='';    
		}
		else
		{
			$map->background = stripslashes($_REQUEST['map_bgfile']);
		}


		$inheritables = array(
			array('link','width','map_linkdefaultwidth'),
		);

		handle_inheritance($map, $inheritables);	
                
		$bwin = $_REQUEST['map_linkdefaultbwin'];
		$bwout = $_REQUEST['map_linkdefaultbwout'];

		$bwin_old = $map->defaultlink->max_bandwidth_in_cfg;
		$bwout_old = $map->defaultlink->max_bandwidth_out_cfg;

		if( ($bwin_old != $bwin) || ($bwout_old != $bwout) )
		{
			$map->defaultlink->SetBandwidth($bwin,$bwout);
			foreach ($map->links as $link)
			{
				if( ($link->max_bandwidth_in_cfg == $bwin_old) || ($link->max_bandwidth_out_cfg == $bwout_old) )
				{
					$link->SetBandwidth($bwin,$bwout);
				}
			}
		}

		

		$map->WriteConfig($mapfile);
		break; 

	case 'set_map_style':
		$map->ReadConfig($mapfile);

		$map->htmlstyle = $_REQUEST['mapstyle_htmlstyle'];
		$map->keyfont = intval($_REQUEST['mapstyle_legendfont']);

		$inheritables = array(
			array('link','labelstyle','mapstyle_linklabels'),
			array('link','bwfont','mapstyle_linkfont'),
			array('link','arrowstyle','mapstyle_arrowstyle'),
			array('node','labelfont','mapstyle_nodefont')
			);

		handle_inheritance($map, $inheritables);
		
		$map->WriteConfig($mapfile);
		break;

	case "add_link":
		$map->ReadConfig($mapfile);

		$param2 = $_REQUEST['param'];
		$newaction = 'add_link2';
		$selected = 'NODE:'.$param2;         
		break;

	case "add_link2":
		$map->ReadConfig($mapfile);
		$a = $_REQUEST['param2'];
		$b = $_REQUEST['param'];
		$log = "[$a -> $b]";

		if($a != $b)
		{
			$newlink = new WeatherMapLink;
			$newlink->Reset($map);
			$newlink->a = $map->nodes[$a];
			$newlink->b = $map->nodes[$b];
			$newlink->SetBandwidth($map->defaultlink->max_bandwidth_in_cfg, $map->defaultlink->max_bandwidth_out_cfg);
			$newlink->width = $map->defaultlink->width;

			// make sure the link name is unique. We can have multiple links between
			// the same nodes, these days
			$newlinkname = "$a-$b";
			while(array_key_exists($newlinkname,$map->links))
			{
				$newlinkname .= "a";
			}
			$newlink->name = $newlinkname;
			$map->links[$newlinkname] = $newlink;

			$map->WriteConfig($mapfile);
		}          
		break;

	case "place_legend":
		$x = intval($_REQUEST['x']);
		$y = intval($_REQUEST['y']);
		$scalename = $_REQUEST['param'];

		$map->ReadConfig($mapfile);

		$map->keyx[$scalename] = $x;
		$map->keyy[$scalename] = $y;

		$map->WriteConfig($mapfile);
		break;

	case "place_stamp":
		$x = intval($_REQUEST['x']);
		$y = intval($_REQUEST['y']);

		$map->ReadConfig($mapfile);

		$map->timex = $x;
		$map->timey = $y;

		$map->WriteConfig($mapfile);
		break;

	case "move_node":
		$x = intval($_REQUEST['x']);
		$y = intval($_REQUEST['y']);
		$node_name = $_REQUEST['node_name'];

		$map->ReadConfig($mapfile);

		if(1==1)
		{
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
						RotateAboutPoint($points,$pivx, $pivy, deg2rad($angle_old));
						// do the scaling in here
						for($i=0; $i<(count($points)/2); $i++)
						{
							$basex = ($points[$i*2] - $pivx) * $scalefactor + $pivx;
							$points[$i*2] = $basex;
						}
						// rotate back so that link is along the new direction
						RotateAboutPoint($points,$pivx, $pivy, deg2rad(-$angle_new));
						
						// now put the modified points back into the vialist again
						$v = 0; $i = 0;
						foreach($points as $p)
						{
							$link->vialist[$v][$i]=$p;
							$i++;
							if($i==2) { $i=0; $v++;}
						}
					}
				}
			}
		}

		$map->nodes[$node_name]->x = $x;
		$map->nodes[$node_name]->y = $y;

		$map->WriteConfig($mapfile);
		break;

	case "add_node":
		$x = intval($_REQUEST['x']);
		$y = intval($_REQUEST['y']);

		$map->ReadConfig($mapfile);

		$node = new WeatherMapNode;
		$node->Reset($map);

		$node->x = snap($x);
		$node->y = snap($y);
		
		$newnodename = sprintf("node%05d",time()%10000);
		while(array_key_exists($newnodename,$map->nodes))
		{
			$newnodename .= "a";
		}
		
		$node->name = $newnodename;
		$node->label = "Node";

		$map->nodes[$node->name] = $node;

		$map->WriteConfig($mapfile);
		break;

	case "delete_link":
		$map->ReadConfig($mapfile);

		$target = $_REQUEST['param'];
		$log = "delete link ".$target;

		unset($map->links[$target]);

		$map->WriteConfig($mapfile);
		break;

	case "delete_node":
		$map->ReadConfig($mapfile);

		$target = $_REQUEST['param'];
		$log = "delete node ".$target;

		foreach ($map->links as $link)
		{
			if( ($target == $link->a->name) || ($target == $link->b->name) )
			{
				unset($map->links[$link->name]);
			}
		}           

		unset($map->nodes[$target]);

		$map->WriteConfig($mapfile);
		break;

		// no action was defined - starting a new map?
	default:
		$map->ReadConfig($mapfile);
		break;   
	}


	// now we'll just draw the full editor page, with our
	// new knowledge

	$imageurl = '?mapname='.$mapname . '&amp;action=draw';
	if($selected != '')
	{
		$imageurl .= '&amp;selected='.$selected;
	}

	$imageurl .= '&amp;unique='.time();

	$imlist = get_imagelist("images");
	
	$fontlist = array();
	

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<style>
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
  <link rel="stylesheet" type="text/css" media="screen" href="editor.css" />
  <script src="editor.js" type="text/javascript"></script>
<?php
if($use_jquery)
{
?>
  <script src="lib/javascript/jquery-latest.pack.js" type="text/javascript"></script>
  <script src="lib/javascript/jquery.shortkeys.js" type="text/javascript"></script>
  <script src="lib/javascript/jquery.contextmenu.packed.js" type="text/javascript"></script>
<?php
}
?>
	<script type="text/javascript">
	
	var fromplug=<?php echo ($fromplug==TRUE ? 1:0); ?>;
	
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

	if($use_jquery)
	{
?>
	$(document).ready(function(){

		$.contextMenu.defaults({
			   itemStyle : {
			fontFamily : "arial narrow",
			fontSize: "10px"
			
		      }
		});

	   $("area[@id^=NODE:]").contextMenu("#nodeMenu1", { });
	   $("area[@id^=LINK:]").contextMenu("#linkMenu1", { });
	   $("area[@id^=LEGEND:]").contextMenu("#legendMenu1", { });
	   $("area#TIMESTAMP").contextMenu("#timeMenu1", { }); 
	   $("img#existingdata").contextMenu("#mapMenu1", { } );
	
	 });
<?php
	}
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
	  <li class="tb_help"><span id="tb_help">or click a Node or Link to edit it's properties</span></li>
	</ul>
  </div>

  <form action="editor.php" method="post" name="frmMain">
	<div align="center">
		<input type="hidden" name="plug" value="<?php echo ($fromplug==TRUE ? 1 : 0) ?>">
	 <input style="display:none" type="image"
	  src="<?php echo  $imageurl; ?>" id="xycapture" /><img src=
	  "<?php echo  $imageurl; ?>" id="existingdata" alt="Weathermap" usemap="#weathermap_imap"
	   /><br />
	   <div class="debug"><p><strong>Debug:</strong> <a href="?<?php echo ($fromplug==TRUE ? 'plug=1&amp;' : ''); ?>action=nothing&amp;mapname=<?php echo  $mapname ?>">Do Nothing</a> 
	   <span><label for="mapname">mapfile</label><input type="text" name="mapname" value="<?php echo  $mapname; ?>" /></span>
	   <span><label for="action">action</label><input type="text" id="action" name="action" value="<?php echo  $newaction ?>" /></span>
	  <span><label for="param">param</label><input type="text" name="param" id="param" value="" /></span> 
	  <span><label for="param2">param2</label><input type="text" name="param2" id="param2" value="<?php echo  $param2 ?>" /></span> 
	  <span><label for="debug">debug</label><input id="debug" value="" name="debug" /></span> 
	  <a target="configwindow" href="?<?php echo ($fromplug==TRUE ? 'plug=1&amp;':''); ?>action=show_config&amp;mapname=<?php echo  $mapname ?>">See config</a></p>
	<pre><?php echo  $log ?></pre>
	  </div>
	   <map name="weathermap_imap">
<?php        	
	// we need to draw and throw away a map, to get the
	// dimensions for the imagemap. Oh well.
	$map->DrawMap('null');
	$map->htmlstyle='editor';
	$map->PreloadMapHTML();

	print $map->imap->subHTML("LEGEND:");
	print $map->imap->subHTML("TIMESTAMP");
	print $map->imap->subHTML("NODE:");
	print $map->imap->subHTML("LINK:");
?>
		   </map>
	</div><!-- Node Properties -->

	<div id="dlgNodeProperties" class="dlgProperties">
	  <div class="dlgTitlebar">
		Node Properties
		<input size="6" name="node_name" type="hidden" />
		<ul>
		  <li><a id="tb_node_submit" title="Submit any changes made">Submit</a></li>
		  <li><a id="tb_node_cancel" title="Cancel any changes">Cancel</a></li>
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
		foreach ($imlist as $im)
		{
			print "<option ";
			print "value=\"$im\">$im</option>\n";
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
			<td><a id="node_move" class="dlgTitlebar">Move Node</a><a class="dlgTitlebar" id="node_delete">Delete Node</a></td>
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
		  <li><a title="Submit any changes made" id="tb_link_submit">Submit</a></li>
		  <li><a title="Cancel any changes" id="tb_link_cancel">Cancel</a></li>
		</ul>
	  </div>

	  <div class="dlgBody">
		<div class="comment">
		  Link from '<span id="link_nodename1">%NODE1%</span>' to '<span id="link_nodename2">%NODE2%</span>'
		</div>

		<input size="6" name="link_name" type="hidden" />

		  <table>
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
			  <td><input id="link_infourl" size="20" name="link_infourl" type="text" /></td>
			</tr>
			<tr>
			  <th>'Hover' Graph URL</th>
			  <td><input id="link_hover"  size="20" name="link_hover" type="text" /></td>
			</tr>
			<tr>
			  <th></th>
			  <td>&nbsp;</td>
			</tr>
			<tr>
			  <th></th>
			  <td><a class="dlgTitlebar" id="link_delete">Delete
			  Link</a></td>
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
		  <li><a title="Submit any changes made" id="tb_map_submit">Submit</a></li>
		  <li><a title="Cancel any changes" id="tb_map_cancel">Cancel</a></li>
		</ul>
	  </div>

	  <div class="dlgBody">
		<table>
		  <tr>
			<th>Map Title</th>
			<td><input id="map_title" name="map_title" size="25" type="text" value="<?php echo  $map->title ?>"/></td>
		  </tr>
		<tr>
			<th>Legend Text</th>
			<td><input name="map_legend" size="25" type="text" value="<?php echo  $map->keytext['DEFAULT'] ?>" /></td>
		  </tr>
		<tr>
			<th>Timestamp Text</th>
			<td><input name="map_stamp" size="25" type="text" value="<?php echo  $map->stamptext ?>" /></td>
		  </tr>

		<tr>
			<th>Default Link Width</th>
			<td><input name="map_linkdefaultwidth" size="6" type="text" value="<?php echo  $map->defaultlink->width ?>" /> pixels</td>
		  </tr>

		<tr>
			<th>Default Link Bandwidth</th>
			<td><input name="map_linkdefaultbwin" size="6" type="text" value="<?php echo  $map->defaultlink->max_bandwidth_in_cfg ?>" /> bit/sec in, <input name="map_linkdefaultbwout" size="6" type="text" value="<?php echo  $map->defaultlink->max_bandwidth_out_cfg ?>" /> bit/sec out</td>
		  </tr>


		  <tr>
			<th>Map Size</th>
			<td><input name="map_width" size="5" type=
			"text"  value="<?php echo  $map->width ?>" /> x <input name="map_height" size="5" type=
			"text"  value="<?php echo  $map->height ?>" /> pixels</td>
		  </tr>
		   <tr>
			<th>Output Image Filename</th>
			<td><input name="map_pngfile" type="text"  value="<?php echo  $map->imageoutputfile ?>" /></td>
		  </tr>
		  <tr>
			<th>Output HTML Filename</th>
			<td><input name="map_htmlfile" type="text" value="<?php echo  $map->htmloutputfile ?>" /></td>
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
			print "value=\"$im\">$im</option>\n";

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
		  <li><a title="Submit any changes made" id="tb_mapstyle_submit">Submit</a></li>
		  <li><a title="Cancel any changes" id="tb_mapstyle_cancel">Cancel</a></li>
		</ul>
	  </div>

	  <div class="dlgBody">
		<table>
		  <tr>
			<th>Link Labels</th>
			<td><select id="mapstyle_linklabels" name="mapstyle_linklabels">
			  <option <?php echo ($map->defaultlink->labelstyle=='bits' ? 'selected' : '') ?> value="bits">Bits/sec</option>
			  <option <?php echo ($map->defaultlink->labelstyle=='percent' ? 'selected' : '') ?> value="percent">Percentage</option>
			  <option <?php echo ($map->defaultlink->labelstyle=='none' ? 'selected' : '') ?> value="none">None</option>
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
			  <option <?php echo ($map->defaultlink->arrowstyle=='classic' ? 'selected' : '') ?> value="classic">Classic</option>
			  <option <?php echo ($map->defaultlink->arrowstyle=='compact' ? 'selected' : '') ?> value="compact">Compact</option>
			</select></td>
		  </tr>
		  <tr>
			<th>Node Font</th>
			<td><?php echo get_fontlist($map,'mapstyle_nodefont',$map->defaultnode->labelfont); ?></td>
		  </tr>
		  <tr>
			<th>Link Label Font</th>
			<td><?php echo get_fontlist($map,'mapstyle_linkfont',$map->defaultlink->bwfont); ?></td>
		  </tr>
		  <tr>
			<th>Legend Font</th>
			<td><?php echo get_fontlist($map,'mapstyle_legendfont',$map->keyfont); ?></td>
		  </tr>
		  <tr>
			<th>Font Samples:</th>
			<td><div class="fontsamples" ><img src="?action=font_samples&mapname=<?php echo  $mapname?>" /></div><br />(Drawn using your PHP install)</td>
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
		  <li><a title="Submit any changes made" id="tb_colours_submit">Submit</a></li>
		  <li><a title="Cancel any changes" id="tb_colours_cancel">Cancel</a></li>
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
		  <li><a title="Submit any changes made" id="tb_images_submit">Submit</a></li>
		  <li><a title="Cancel any changes" id="tb_images_cancel">Cancel</a></li>
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

  </form>
  	<?php
if($use_jquery)
{
	?>
     <div class="contextMenu" id="linkMenu1">
      <ul>
	<li id="properties"><img src="editor-resources/page_white_text.png" /> Link Properties</li>
	<li id="delete"><img src="editor-resources/cross.png" /> Delete Link</li>
      </ul>
    </div>
     <div class="contextMenu" id="nodeMenu1">
      <ul>
	<li id="properties"><img src="editor-resources/page_white_text.png" /> Node Properties</li>
      	<li id="move"><img src="editor-resources/arrow_out.png" /> Move Node</li>
        <li id="delete"><img src="editor-resources/cross.png" /> Delete Node</li>
      </ul>
    </div>
     <div class="contextMenu" id="mapMenu1">
      <ul>
	<li id="properties"><img src="editor-resources/page_white_text.png" /> Map Properties</li>
      </ul>
    </div>
        <div class="contextMenu" id="legendMenu1">
      <ul>
	<li id="scaleproperties"><img src="editor-resources/page_white_text.png" /> Edit Scale</li>
	<li id="legendproperties"><img src="editor-resources/page_white_text.png" /> Legend Properties</li>
      </ul>
    </div>
	<div class="contextMenu" id="timeMenu1">
      <ul>
		<li id="stampproperties"><img src="editor-resources/page_white_text.png" /> Timestamp Properties</li>
      </ul>
    </div>
	<?php
}
	?>
</body>
</html>
<?php
} // if mapname != ''
// vim:ts=4:sw=4:
?>
