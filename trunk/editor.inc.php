<?php
function show_editor_startpage()
{
	global $mapdir, $WEATHERMAP_VERSION, $config_loaded, $cacti_found, $ignore_cacti,$configerror;

	$fromplug = FALSE;
        if(isset($_REQUEST['plug']) && (intval($_REQUEST['plug'])==1) ) { $fromplug = TRUE; }

	$matches=0;

	print '<html xmlns="http://www.w3.org/1999/xhtml"><head><link rel="stylesheet" type="text/css" media="screen" href="editor.css" />  <script src="editor.js" type="text/javascript"></script><title>PHP Weathermap Editor ' . $WEATHERMAP_VERSION
		. '</title></head><body>';

	print '<div id="nojs" class="alert"><b>WARNING</b> - ';
	print 'Sorry, it\'s partly laziness on my part, but you really need JavaScript enabled and DOM support in your browser to use this editor. It\'s a visual tool, so accessibility is already an issue, if it is, and from a security viewpoint, you\'re already running my ';
	print 'code on your <i>server</i> so either you trust it all having read it, or you\'re already screwed.<P>';
	print 'If it\'s a major issue for you, please feel free to complain. It\'s mainly laziness as I said, and there could be a fallback (not so smooth) mode for non-javascript browsers if it was seen to be worthwhile (I would take a bit of convincing, because I don\'t see a benefit, personally).</div>';
	
	$errormessage = "";

    if($configerror!='')
    {
        $errormessage .= $configerror.'<p>';
    }
		
	if(! $cacti_found && !$ignore_cacti)
	{
		$errormessage .= '$cacti_base is not set correctly. Cacti integration will be disabled in the editor.';
		if($config_loaded != 1) { $errormessage .= " You might need to copy editor-config.php-dist to editor-config.php and edit it."; }
	}
	
	if($errormessage != '')
	{
		print '<div class="alert" id="nocacti">'.$errormessage.'</div>';
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

	if (is_dir($mapdir))
	{
		$n=0;
		$dh=opendir($mapdir);

		if ($dh)
		{
			while ($file=readdir($dh))
			{
				$realfile=$mapdir . DIRECTORY_SEPARATOR . $file;
				$note = "";

				if ( (is_file($realfile)) && (is_readable($realfile)) && (!preg_match("/^\./",$file) ) )
				{
					if(!is_writable($realfile))
					{
						$note .= "(read-only)";
					}
					$title='(no title)';
					$fd=fopen($realfile, "r");
					if($fd)
					{
						while (!feof($fd))
						{
							$buffer=fgets($fd, 4096);
	
							if (preg_match("/^\s*TITLE\s+(.*)/i", $buffer, $matches)) { $title=$matches[1]; }
						}
	
						fclose ($fd);
						$titles[$file] = $title;
						$notes[$file] = $note;
						$n++;
					}
				}
			}

			closedir ($dh);
		}
		else { $errorstring = "Can't open mapdir to read."; }
		
		ksort($titles);
		
		if ($n == 0) { $errorstring = "No files in mapdir"; }
	}
	else { $errorstring = "NO DIRECTORY named $mapdir"; }


	print 'OR<br />Create A New Map as a copy of an existing map:<br>';
	print '<form method="GET">';
	print 'Named: <input type="text" name="mapname" size="20"> based on ';

	print '<input name="action" type="hidden" value="newmapcopy">';
	print '<input name="plug" type="hidden" value="'.$fromplug.'">';
	print '<select name="sourcemap">';
	
	if($errorstring == '')
	{
		foreach ($titles as $file=>$title)
		{
			$nicefile = htmlspecialchars($file);
			print "<option value=\"$nicefile\">$nicefile</option>\n";
		}
	}
	else
	{
		print '<option value="">'.$errorstring.'</option>';
	}
	
	print '</select>';
	print '<input type="submit" value="Create Copy">';
	print '</form>';
	print 'OR<br />';
	print 'Open An Existing Map (looking in ' . $mapdir . '):<ul class="filelist">';

	if($errorstring == '')
	{
		foreach ($titles as $file=>$title)
		{
			$title = $titles[$file];
			$note = $notes[$file];
			$nicefile = htmlspecialchars($file);
			print "<li>$note<a href=\"?mapname=$nicefile&plug=$fromplug\">$nicefile</a> - <span class=\"comment\">$title</span></li>\n";
		}
	}
	else
	{
		print '<li>'.$errorstring.'</li>';
	}

	print "</ul>";

	print "</div>"; // dlgbody
	print '<div class="dlgHelp" id="start_help">PHP Weathermap ' . $WEATHERMAP_VERSION
		. ' Copyright &copy; 2005-2007 Howard Jones - howie@thingy.com<br />The current version should always be <a href="http://www.network-weathermap.com/">available here</a>, along with other related software. PHP Weathermap is licensed under the GNU Public License, version 2. See COPYING for details. This distribution also includes the Overlib library by Erik Bosrup.</div>';

	print "</div>"; // dlgStart
	print "</div>"; // withjs
	print "</body></html>";
}

function snap($coord, $gridsnap = 0)
{
	if ($gridsnap == 0) { return ($coord); }
	else { return ($coord - ($coord % $gridsnap)); }
}

// Following function is based on code taken from here:
// http://uk2.php.net/manual/en/security.globals.php
//
// It extracts a set of named variables into the global namespace,
// validating them as they go. Returns True or False depending on if
// validation fails. If it does fail, then nothing is added to the
// global namespace.
//
function extract_with_validation($array, $paramarray, $prefix = "", $debug = FALSE)
{
	$all_present=TRUE;
	$candidates=array(
		);

	if ($debug)
		print '<pre>';

	if ($debug)
		print_r ($paramarray);

	if ($debug)
		print_r ($array);

	foreach ($paramarray as $var)
	{
		$varname=$var[0];
		$vartype=$var[1];
		$varreqd=$var[2];

		if ($varreqd == 'req' && !array_key_exists($varname, $array)) { $all_present=FALSE; }

		if (array_key_exists($varname, $array))
		{
			$varvalue=$array[$varname];

			if ($debug)
				print "Checking $varname...";

			$waspresent=$all_present;

			switch ($vartype)
			{
			case 'int':
				if (!preg_match('/^\-*\d+$/', $varvalue)) { $all_present=FALSE; }

				break;

			case 'float':
				if (!preg_match('/^\d+\.\d+$/', $varvalue)) { $all_present=FALSE; }

				break;

			case 'yesno':
				if (!preg_match('/^(y|n|yes|no)$/i', $varvalue)) { $all_present=FALSE; }

				break;

			case 'sqldate':
				if (!preg_match('/^\d\d\d\d\-\d\d\-\d\d$/i', $varvalue)) { $all_present=FALSE; }

				break;

			case 'any':
				// we don't care at all
				break;

			case 'ip':
				if (!preg_match(
					'/^((\d|[1-9]\d|2[0-4]\d|25[0-5]|1\d\d)(?:\.(\d|[1-9]\d|2[0-4]\d|25[0-5]|1\d\d)){3})$/',
					$varvalue)) { $all_present=FALSE; }

				break;

			case 'alpha':
				if (!preg_match('/^[A-Za-z]+$/', $varvalue)) { $all_present=FALSE; }

				break;

			case 'alphanum':
				if (!preg_match('/^[A-Za-z0-9]+$/', $varvalue)) { $all_present=FALSE; }

				break;

			case 'bandwidth':
				if (!preg_match('/^\d+\.?\d*[KMGT]*$/i', $varvalue)) { $all_present=FALSE; }

				break;

			default:
				// an unknown type counts as an error, really
				$all_present=FALSE;

				break;
			}

			if ($debug && $waspresent != $all_present) { print "Failed on $varname."; }

			if ($all_present)
			{
				$candidates["{$prefix}{$varname}"]=$varvalue;
				$candidates["{$prefix}{$varname}_slashes"]=addslashes($varvalue);
				$candidates["{$prefix}{$varname}_url"]=urlencode($varvalue);
				$candidates["{$prefix}{$varname}_html"]=htmlspecialchars($varvalue);
				$candidates["{$prefix}{$varname}_url_html"]=htmlspecialchars(urlencode($varvalue));
			}
		}
		else
		{
			if ($debug)
				print "Skipping $varname\n";
		}
	}

	if ($debug)
		print_r ($candidates);

	if ($all_present)
	{
		foreach ($candidates as $key => $value) { $GLOBALS[$key]=$value; }
	}

	if ($debug)
		print '</pre>';

	return ($all_present);
}

function get_imagelist($imagedir)
{
	$imagelist = array();

	if (is_dir($imagedir))
	{
		$n=0;
		$dh=opendir($imagedir);

		if ($dh)
		{
			while ($file=readdir($dh))
			{
				$realfile=$imagedir . DIRECTORY_SEPARATOR . $file;
                $uri = $imagedir . "/" . $file;

				if(is_file($realfile) && ( preg_match('/\.(gif|jpg|png)$/i',$file) ))
				{
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
	foreach ($inheritables as $inheritable)
	{
		$fieldname = $inheritable[1];
		$formname = $inheritable[2];
		
		$new = $_REQUEST[$formname];
		
		$old = ($inheritable[0]=='node' ? $map->defaultnode->$fieldname : $map->defaultlink->$fieldname);
		
		if($old != $new)
		{
			if($inheritable[0]=='node')
			{
				$map->defaultnode->$fieldname = $new;
				foreach ($map->nodes as $node)
				{
					if($node->$fieldname == $old)
					{
						$map->nodes[$node->name]->$fieldname = $new;
					}
				}
			}
			
			if($inheritable[0]=='link')
			{
				$map->defaultlink->$fieldname = $new;
				foreach ($map->links as $link)
				{
					if($link->$fieldname == $old)
					{
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
	
	# $sortedfonts = $map->fonts;
	# ksort ($sortedfonts);
	ksort($map->fonts);
	
	foreach ($map->fonts as $fontnumber => $font)
	{		
		$output .= '<option ';
		if($current == $fontnumber) $output .= 'SELECTED';
		$output .= ' value="'.$fontnumber.'">'.$fontnumber.' ('.$font->type.')</option>';
	}
		
	$output .= "</select>";
	
	return($output);
}

// vim:ts=4:sw=4:
?>
