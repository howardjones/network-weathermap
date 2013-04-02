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
  
    if(preg_match( "/^(\d+\.?\d*[KMGT]?)$/", $bw) ) {
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
	
						if (preg_match("/^\s*TITLE\s+(.*)/i", $buffer, $matches)) { 
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
		. ' Copyright &copy; 2005-2013 Howard Jones - howie@thingy.com<br />The current version should always be <a href="http://www.network-weathermap.com/">available here</a>, along with other related software. PHP Weathermap is licensed under the GNU Public License, version 2. See COPYING for details. This distribution also includes the Overlib library by Erik Bosrup.</div>';

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


function extract_with_validation($array, $paramarray)
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

function editor_log($str)
{
    // $f = fopen("editor.log","a");
    // fputs($f, $str);
    // fclose($f);
}

// vim:ts=4:sw=4:
?>
