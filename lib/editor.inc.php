<?php

/** editor.inc.php
  *
  * All the functions used by the editor.
  */

/** @function fix_gpc_string 
  *
  * Take a string (that we got from $_REQUEST) and make it back to how the
  * user TYPED it, regardless of whether magic_quotes_gpc is turned on or off.
  *
  * @param string $input String to fix
  *
  * @returns string Fixed string
  *
  */
function fix_gpc_string($input) 
{
    if (true == function_exists('get_magic_quotes_gpc') && 1 == get_magic_quotes_gpc()) {
        $input = stripslashes($input);
    }
    return ($input);
}

/**
 * Clean up URI (function taken from Cacti) to protect against XSS
 */
function wm_editor_sanitize_uri($str) {
        static $drop_char_match =   array(' ','^', '$', '<', '>', '`', '\'', '"', '|', '+', '[', ']', '{', '}', ';', '!', '%');
        static $drop_char_replace = array('', '', '',  '',  '',  '',  '',   '',  '',  '',  '',  '',  '',  '',  '',  '', '');

        return str_replace($drop_char_match, $drop_char_replace, urldecode($str));
}

// much looser sanitise for general strings that shouldn't have HTML in them
function wm_editor_sanitize_string($str) {
        static $drop_char_match =   array('<', '>' );
        static $drop_char_replace = array('', '');

        return str_replace($drop_char_match, $drop_char_replace, urldecode($str));
}

function wm_editor_validate_bandwidth($bw) {
  
    if(preg_match( '/^(\d+\.?\d*[KMGT]?)$/', $bw) ) {
	return true;
    }
    return false;
}

function wm_editor_validate_one_of($input,$valid=array(),$case_sensitive=false) {
    if(! $case_sensitive ) $input = strtolower($input);
    
    foreach ($valid as $v) {
	if(! $case_sensitive ) $v = strtolower($v);
	if($v == $input) return true;
    }
    
    return false;
}

// Labels for Nodes, Links and Scales shouldn't have spaces in
function wm_editor_sanitize_name($str) {
    return str_replace( array(" "), "", $str);
}

function wm_editor_sanitize_selected($str) {        
	$res = urldecode($str);
	
	if( ! preg_match("/^(LINK|NODE):/",$res)) {
	    return "";
	}
	return wm_editor_sanitize_name($res);
}

function wm_editor_sanitize_file($filename,$allowed_exts=array()) {
    
    $filename = wm_editor_sanitize_uri($filename);
    
    if ($filename == "") return "";
        
    $ok = false;
    foreach ($allowed_exts as $ext) {
		$match = ".".$ext;

		if( substr($filename, -strlen($match),strlen($match)) == $match) {
			$ok = true;
		}
    }    
    if(! $ok ) return "";
    return $filename;
}

function wm_editor_sanitize_conffile($filename) {
    
    $filename = wm_editor_sanitize_uri($filename);
    
    # If we've been fed something other than a .conf filename, just pretend it didn't happen
    if ( substr($filename,-5,5) != ".conf" ) {
		$filename = "";
	}

	# on top of the url stuff, we don't ever need to see a / in a config filename (CVE-2013-3739)
	if (strstr($filename,"/") !== false ) {
		$filename = "";
	}

    return $filename;
}

function show_editor_startpage()
{
	global $mapdir, $WEATHERMAP_VERSION, $config_loaded, $cacti_found, $ignore_cacti,$configerror;

	$fromplug = false;
	if (isset($_REQUEST['plug']) && (intval($_REQUEST['plug'])==1) ) { 
	    $fromplug = true; 
	}

	$matches=0;

	print '<html xmlns="http://www.w3.org/1999/xhtml"><head><link rel="stylesheet" type="text/css" media="screen" href="editor-resources/oldeditor.css" /><script type="text/javascript" src="editor-resources/jquery-latest.min.js"></script><script src="editor-resources/editor.js" type="text/javascript"></script><title>PHP Weathermap Editor ' . $WEATHERMAP_VERSION
		. '</title></head><body>';

	print '<div id="nojs" class="alert"><b>WARNING</b> - ';
	print 'Sorry, it\'s partly laziness on my part, but you really need JavaScript enabled and DOM support in your browser to use this editor. It\'s a visual tool, so accessibility is already an issue, if it is, and from a security viewpoint, you\'re already running my ';
	print 'code on your <i>server</i> so either you trust it all having read it, or you\'re already screwed.<P>';
	print 'If it\'s a major issue for you, please feel free to complain. It\'s mainly laziness as I said, and there could be a fallback (not so smooth) mode for non-javascript browsers if it was seen to be worthwhile (I would take a bit of convincing, because I don\'t see a benefit, personally).</div>';
	
	$errormessage = "";

    if ($configerror!='') {
        $errormessage .= $configerror.'<p>';
    }
		
	if (! $cacti_found && !$ignore_cacti) {
		$errormessage .= '$cacti_base is not set correctly. Cacti integration will be disabled in the editor.';
		if ($config_loaded != 1) { 
            $errormessage .= " You might need to copy editor-config.php-dist to editor-config.php and edit it."; 
        }
	}
	
	if ($errormessage != '') {
		print '<div class="alert" id="nocacti">'.htmlspecialchars($errormessage).'</div>';
	}

	print '<div id="withjs">';
	print '<div id="dlgStart" class="dlgProperties" ><div class="dlgTitlebar">Welcome</div><div class="dlgBody">';
	print 'Welcome to the PHP Weathermap '.$WEATHERMAP_VERSION.' editor.<p>';
	print '<div style="border: 3px dashed red; background: #055; padding: 5px; font-size: 90%;"><b>NOTE:</b> This editor is not finished! There are many features of ';
	print 'Weathermap that you will be missing out on if you choose to use the editor only.';
	print 'These include: curves, node offsets, font definitions, colour changing, per-node/per-link settings and image uploading. You CAN use the editor without damaging these features if you added them by hand, however.</div><p>';
	
	print 'Do you want to:<p>';
	print 'Create A New Map:<br>';
	print '<form method="GET">';
	print 'Named: <input type="text" name="mapname" size="20">';

	print '<input name="action" type="hidden" value="newmap">';
	print '<input name="plug" type="hidden" value="'.$fromplug.'">';

	print '<input type="submit" value="Create">';

	print '<p><small>Note: filenames must contain no spaces and end in .conf</small></p>';
	print '</form>';

	$titles = array();

	$errorstring="";

	if (is_dir($mapdir)) {
		$n=0;
		$dh=opendir($mapdir);

		if ($dh) {
		    while (false !== ($file = readdir($dh))) {
			$realfile=$mapdir . DIRECTORY_SEPARATOR . $file;
			$note = "";
	
			// skip directories, unreadable files, .files and anything that doesn't come through the sanitiser unchanged
			if ( (is_file($realfile)) && (is_readable($realfile)) && (!preg_match("/^\./",$file) )  && ( wm_editor_sanitize_conffile($file) == $file ) ) {
				if (!is_writable($realfile)) {
					$note .= "(read-only)";
				}
				$title='(no title)';
				$fd=fopen($realfile, "r");
				if ($fd) {
					while (!feof($fd)) {
						$buffer=fgets($fd, 4096);
	
						if (preg_match('/^\s*TITLE\s+(.*)/i', $buffer, $matches)) {
						    $title= wm_editor_sanitize_string($matches[1]); 
						}
					}
	
					fclose ($fd);
					$titles[$file] = $title;
					$notes[$file] = $note;
					$n++;
				}
			}
		    }

		    closedir ($dh);
		} else { 
            $errorstring = "Can't open mapdir to read."; 
        }
		
		ksort($titles);
		
		if ($n == 0) { 
		    $errorstring = "No files in mapdir"; 
		}
	} else { 
	    $errorstring = "NO DIRECTORY named $mapdir"; 
	}

	print 'OR<br />Create A New Map as a copy of an existing map:<br>';
	print '<form method="GET">';
	print 'Named: <input type="text" name="mapname" size="20"> based on ';

	print '<input name="action" type="hidden" value="newmapcopy">';
	print '<input name="plug" type="hidden" value="'.$fromplug.'">';
	print '<select name="sourcemap">';
	
	if ($errorstring == '') {
		foreach ($titles as $file=>$title) {
			$nicefile = htmlspecialchars($file);
			print "<option value=\"$nicefile\">$nicefile</option>\n";
		}
	} else {
		print '<option value="">'.htmlspecialchars($errorstring).'</option>';
	}
	
	print '</select>';
	print '<input type="submit" value="Create Copy">';
	print '</form>';
	print 'OR<br />';
	print 'Open An Existing Map (looking in ' . htmlspecialchars($mapdir) . '):<ul class="filelist">';

	if ($errorstring == '') {
		foreach ($titles as $file=>$title) {			
			# $title = $titles[$file];
			$note = $notes[$file];
			$nicefile = htmlspecialchars($file);
			$nicetitle = htmlspecialchars($title);
			print "<li>$note<a href=\"?mapname=$nicefile&plug=$fromplug\">$nicefile</a> - <span class=\"comment\">$nicetitle</span></li>\n";
		}
	} else {
		print '<li>'.htmlspecialchars($errorstring).'</li>';
	}

	print "</ul>";

	print "</div>"; // dlgbody
	print '<div class="dlgHelp" id="start_help">PHP Weathermap ' . $WEATHERMAP_VERSION
		. ' Copyright &copy; 2005-2016 Howard Jones - howie@thingy.com<br />The current version should always be <a href="http://www.network-weathermap.com/">available here</a>, along with other related software. PHP Weathermap is licensed under the GNU Public License, version 2. See COPYING for details. This distribution also includes the Overlib library by Erik Bosrup.</div>';

	print "</div>"; // dlgStart
	print "</div>"; // withjs
	print "</body></html>";
}

function snap($coord, $gridsnap = 0)
{
    if ($gridsnap == 0) {
        return ($coord);
    } else {        
        $rest = $coord % $gridsnap;
        return ($coord - $rest + round($rest/$gridsnap) * $gridsnap );
    }
}


function extract_with_validation($array, $paramarray, $prefix = "")
{
	$all_present=true;
	$candidates=array( );

	foreach ($paramarray as $var) {
		$varname=$var[0];
		$vartype=$var[1];
		$varreqd=$var[2];

		if ($varreqd == 'req' && !array_key_exists($varname, $array)) { 
	            $all_present=false; 
	        }

		if (array_key_exists($varname, $array)) {
			$varvalue=$array[$varname];

			$waspresent=$all_present;

			switch ($vartype)
			{
			case 'int':
				if (!preg_match('/^\-*\d+$/', $varvalue)) { 
                    $all_present=false; 
                }

				break;

			case 'float':
				if (!preg_match('/^\d+\.\d+$/', $varvalue)) { 
                    $all_present=false; 
                }

				break;

			case 'yesno':
				if (!preg_match('/^(y|n|yes|no)$/i', $varvalue)) { 
                    $all_present=false; 
                }

				break;

			case 'sqldate':
				if (!preg_match('/^\d\d\d\d\-\d\d\-\d\d$/i', $varvalue)) { 
                    $all_present=false; 
                }

				break;

			case 'any':
				// we don't care at all
				break;

			case 'ip':
				if (!preg_match( '/^((\d|[1-9]\d|2[0-4]\d|25[0-5]|1\d\d)(?:\.(\d|[1-9]\d|2[0-4]\d|25[0-5]|1\d\d)){3})$/', $varvalue)) { 
                    $all_present=false; 
                }

				break;

			case 'alpha':
				if (!preg_match('/^[A-Za-z]+$/', $varvalue)) { 
                    $all_present=false; 
                }

				break;

			case 'alphanum':
				if (!preg_match('/^[A-Za-z0-9]+$/', $varvalue)) { 
                    $all_present=false; 
                }

				break;

			case 'bandwidth':
				if (!preg_match('/^\d+\.?\d*[KMGT]*$/i', $varvalue)) { 
                    $all_present=false; 
                }
				break;

			default:
				// an unknown type counts as an error, really
				$all_present=false;

				break;
			}
			
			if ($all_present) {
				$candidates["{$prefix}{$varname}"]=$varvalue;
			}
		}
	}

	if ($all_present) {
	    foreach ($candidates as $key => $value) { 
		$GLOBALS[$key]=$value; 
	    }
	}

	return array($all_present,$candidates);
}

function get_imagelist($imagedir)
{
	$imagelist = array();

	if (is_dir($imagedir)) {
		$n=0;
		$dh=opendir($imagedir);

		if ($dh) {
			while ($file=readdir($dh)) {
				$realfile=$imagedir . DIRECTORY_SEPARATOR . $file;
				$uri = $imagedir . "/" . $file;

				if (is_readable($realfile) && ( preg_match('/\.(gif|jpg|png)$/i',$file) )) {
					$imagelist[] = $uri;
					$n++;
				}
			}

			closedir ($dh);
		}
	}
	return ($imagelist);
}

function handle_inheritance(&$map, &$inheritables)
{
	foreach ($inheritables as $inheritable) {		
		$fieldname = $inheritable[1];
		$formname = $inheritable[2];
		$validation = $inheritable[3];
		
		$new = $_REQUEST[$formname];
		if($validation != "") {
		    switch($validation) {
			case "int":
			    $new = intval($new);
			    break;
			case "float":
			    $new = floatval($new);
			    break;
		    }
		}
		
		$old = ($inheritable[0]=='node' ? $map->nodes['DEFAULT']->$fieldname : $map->links['DEFAULT']->$fieldname);	
		
		if ($old != $new) {
			if ($inheritable[0]=='node') {
				$map->nodes['DEFAULT']->$fieldname = $new;
				foreach ($map->nodes as $node) {
					if ($node->name != ":: DEFAULT ::" && $node->$fieldname == $old) {
						$map->nodes[$node->name]->$fieldname = $new;
					}
				}
			}
			
			if ($inheritable[0]=='link') {
				$map->links['DEFAULT']->$fieldname = $new;
				foreach ($map->links as $link) {
					
					if ($link->name != ":: DEFAULT ::" && $link->$fieldname == $old) {
						$map->links[$link->name]->$fieldname = $new;
					}
				}
			}
		}
	}
}

function get_fontlist(&$map,$name,$current)
{
    $output = '<select class="fontcombo" name="'.$name.'">';
        
    ksort($map->fonts);

    foreach ($map->fonts as $fontnumber => $font) {		
        $output .= '<option ';
        if ($current == $fontnumber) {
            $output .= 'SELECTED';
        }
        $output .= ' value="'.$fontnumber.'">'.$fontnumber.' ('.$font->type.')</option>';
    }
        
    $output .= "</select>";

    return($output);
}


function range_overlaps($a_min, $a_max, $b_min, $b_max)
{
	if ($a_min > $b_max) {
		return false;
	}
	if ($b_min > $a_max) {
		return false;
	}

	return true;
}
function common_range ($a_min,$a_max, $b_min, $b_max)
{
	$min_overlap = max($a_min, $b_min);
	$max_overlap = min($a_max, $b_max);

	return array($min_overlap,$max_overlap);
}
/* distance - find the distance between two points
 *
 */
function distance ($ax,$ay, $bx,$by)
{
	$dx = $bx - $ax;
	$dy = $by - $ay;
	return sqrt( $dx*$dx + $dy*$dy );
}


function tidy_links(&$map,$targets, $ignore_tidied=FALSE)
{
	// not very efficient, but it saves looking for special cases (a->b & b->a together)
	$ntargets = count($targets);
	$i = 1;
	foreach ($targets as $target) {
		tidy_link($map, $target, $i, $ntargets, $ignore_tidied);
		$i++;
	}
}
/**
 * tidy_link - change link offsets so that link is horizonal or vertical, if possible.
 *             if not possible, change offsets to the closest facing compass points
 */
function tidy_link(&$map,$target, $linknumber=1, $linktotal=1, $ignore_tidied=FALSE)
{
	// print "\n-----------------------------------\nTidying $target...\n";
	if(isset($map->links[$target]) and isset($map->links[$target]->a) ) {

		$node_a = $map->links[$target]->a;
		$node_b = $map->links[$target]->b;

		$new_a_offset = "0:0";
		$new_b_offset = "0:0";

		// Update TODO: if the nodes are already directly left/right or up/down, then use compass-points, not pixel offsets
		// (e.g. N90) so if the label changes, they won't need to be re-tidied

		// First bounding box in the node's boundingbox array is the icon, if there is one, or the label if not.
		$bb_a = $node_a->boundingboxes[0];
		$bb_b = $node_b->boundingboxes[0];

		// figure out if they share any x or y coordinates
		$x_overlap = range_overlaps($bb_a[0], $bb_a[2], $bb_b[0], $bb_b[2]);
		$y_overlap = range_overlaps($bb_a[1], $bb_a[3], $bb_b[1], $bb_b[3]);

		$a_x_offset = 0; $a_y_offset = 0;
		$b_x_offset = 0; $b_y_offset = 0;

		// if they are side by side, and there's some common y coords, make link horizontal
		if ( !$x_overlap && $y_overlap ) {
			// print "SIDE BY SIDE\n";

			// snap the X coord to the appropriate edge of the node
			if ($bb_a[2] < $bb_b[0]) {
				$a_x_offset = $bb_a[2] - $node_a->x;
				$b_x_offset = $bb_b[0] - $node_b->x;
			}
			if ($bb_b[2] < $bb_a[0]) {
				$a_x_offset = $bb_a[0] - $node_a->x;
				$b_x_offset = $bb_b[2] - $node_b->x;
			}

			// this should be true whichever way around they are
			list($min_overlap,$max_overlap) = common_range($bb_a[1],$bb_a[3],$bb_b[1],$bb_b[3]);
			$overlap = $max_overlap - $min_overlap;
			$n = $overlap/($linktotal+1);

			$a_y_offset = $min_overlap + ($linknumber*$n) - $node_a->y;
			$b_y_offset = $min_overlap + ($linknumber*$n) - $node_b->y;

			$new_a_offset = sprintf("%d:%d", $a_x_offset,$a_y_offset);
			$new_b_offset = sprintf("%d:%d", $b_x_offset,$b_y_offset);
		}

		// if they are above and below, and there's some common x coords, make link vertical
		if ( !$y_overlap && $x_overlap ) {
			// print "ABOVE/BELOW\n";

			// snap the Y coord to the appropriate edge of the node
			if ($bb_a[3] < $bb_b[1]) {
				$a_y_offset = $bb_a[3] - $node_a->y;
				$b_y_offset = $bb_b[1] - $node_b->y;
			}
			if ($bb_b[3] < $bb_a[1]) {
				$a_y_offset = $bb_a[1] - $node_a->y;
				$b_y_offset = $bb_b[3] - $node_b->y;
			}

			list($min_overlap,$max_overlap) = common_range($bb_a[0],$bb_a[2],$bb_b[0],$bb_b[2]);
			$overlap = $max_overlap - $min_overlap;
			$n = $overlap/($linktotal+1);

			// move the X coord to the centre of the overlapping area
			$a_x_offset = $min_overlap + ($linknumber*$n) - $node_a->x;
			$b_x_offset = $min_overlap + ($linknumber*$n) - $node_b->x;

			$new_a_offset = sprintf("%d:%d", $a_x_offset,$a_y_offset);
			$new_b_offset = sprintf("%d:%d", $b_x_offset,$b_y_offset);
		}

		// if no common coordinates, figure out the best diagonal...
		if ( !$y_overlap && !$x_overlap ) {

			$pt_a = new WMPoint($node_a->x, $node_a->y);
			$pt_b = new WMPoint($node_b->x, $node_b->y);


			$line = new WMLineSegment($pt_a, $pt_b);

			$tangent = $line->vector;
			$tangent->normalise();

			$normal = $tangent->getNormal();

			$pt_a->AddVector( $normal, 15 * ($linknumber-1) );
			$pt_b->AddVector( $normal, 15 * ($linknumber-1) );

			$a_x_offset = $pt_a->x - $node_a->x;
			$a_y_offset = $pt_a->y - $node_a->y;

			$b_x_offset = $pt_b->x - $node_b->x;
			$b_y_offset = $pt_b->y - $node_b->y;

			$new_a_offset = sprintf("%d:%d", $a_x_offset,$a_y_offset);
			$new_b_offset = sprintf("%d:%d", $b_x_offset,$b_y_offset);


		}

		// if no common coordinates, figure out the best diagonal...
		// currently - brute force search the compass points for the shortest distance
		// potentially - intersect link line with rectangles to get exact crossing point
		if ( 1==0 && !$y_overlap && !$x_overlap ) {
			// print "DIAGONAL\n";

			$corners = array("NE","E","SE","S","SW","W","NW","N");

			// start with what we have now
			$best_distance = distance( $node_a->x, $node_a->y, $node_b->x, $node_b->y );
			$best_offset_a = "C";
			$best_offset_b = "C";

			foreach ($corners as $corner1) {
				list ($ax,$ay) = calc_offset($corner1, $bb_a[2] - $bb_a[0], $bb_a[3] - $bb_a[1]);

				$axx = $node_a->x + $ax;
				$ayy = $node_a->y + $ay;

				foreach ($corners as $corner2) {
					list($bx,$by) = calc_offset($corner2, $bb_b[2] - $bb_b[0], $bb_b[3] - $bb_b[1]);

					$bxx = $node_b->x + $bx;
					$byy = $node_b->y + $by;

					$d = distance($axx,$ayy, $bxx, $byy);
					if($d < $best_distance) {
						// print "from $corner1 ($axx, $ayy) to $corner2 ($bxx, $byy): ";
						// print "NEW BEST $d\n";
						$best_distance = $d;
						$best_offset_a = $corner1;
						$best_offset_b = $corner2;
					}
				}
			}
			// Step back a bit from the edge, to hide the corners of the link
			$new_a_offset = $best_offset_a."85";
			$new_b_offset = $best_offset_b."85";
		}

		// unwritten/implied - if both overlap, you're doing something weird and you're on your own
		// finally, update the offsets
		$map->links[$target]->a_offset = $new_a_offset;
		$map->links[$target]->b_offset = $new_b_offset;
		// and also add a note that this link was tidied, and is eligible for automatic tidying
		$map->links[$target]->add_hint("_tidied",1);
	}
}
function untidy_links(&$map)
{
	foreach ($map->links as $link)
	{
		$link->a_offset = "C";
		$link->b_offset = "C";
	}
}
function retidy_links(&$map, $ignore_tidied=FALSE)
{
	$routes = array();
	$done = array();
	foreach ($map->links as $link)
	{
		if(isset($link->a)) {
			$route = $link->a->name . " " . $link->b->name;
			if(strcmp( $link->a->name, $link->b->name) > 0) {
				$route = $link->b->name . " " . $link->a->name;
			}
			$routes[$route][] = $link->name;
		}
	}

	foreach ($map->links as $link)
	{
		if(isset($link->a)) {
			$route = $link->a->name . " " . $link->b->name;
			if(strcmp( $link->a->name, $link->b->name) > 0) {
				$route = $link->b->name . " " . $link->a->name;
			}

			if( ($ignore_tidied || $link->get_hint("_tidied")==1) && !isset($done[$route]) && isset( $routes[$route] ) ) {

				if( sizeof($routes[$route]) == 1) {
					tidy_link($map,$link->name);
					$done[$route] = 1;
				} else {
					# handle multi-links specially...
					tidy_links($map,$routes[$route]);
					// mark it so we don't do it again when the other links come by
					$done[$route] = 1;
				}
			}
		}
	}
}


function editor_log($str)
{
    // $f = fopen("editor.log","a");
    // fputs($f, $str);
    // fclose($f);
}

// vim:ts=4:sw=4:
