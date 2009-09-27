<?php
    error_reporting (E_ALL);
    require_once("Weathermap.class.php");

    $mapdir = "configs";

    if(isset($_REQUEST['map']))
    {
            $mapfile = $mapdir.'/'.$_REQUEST['map'];
    }
    
    $cmd = '';
    
    if( isset($_REQUEST['cmd']) )
    {
        $cmd = $_REQUEST['cmd'];
        process_editor_command($cmd);
    }
    else
    {
        // if someone tries to go here directly, give them a blank
        // editor to look at
        $fd = fopen('editor-frontend.html','r');
        while (!feof($fd))
        {
                $buffer = fgets($fd, 4096);
                echo $buffer;
        }
        fclose($fd);
    }

    // the business part of the editor
    function process_editor_command($cmd)
    {
        global $mapfile;
        global $cachedir;
        global $mapdir;
        global $weathermap_debugging;
        global $WEATHERMAP_VERSION;

        header("Content-type: text/plain");

        logentry("Command: $cmd");
        switch($cmd)
        {
            case 'version':
                $json = "{ status: 'OK', version: '$WEATHERMAP_VERSION' }";
                print $json;
                break;
            
            case 'imagemap':
                print fetch_cached_file("_imaphtml.json",$mapfile);

                break;
            
            case 'move_node':
                $x = intval($_REQUEST['x']);
		$y = intval($_REQUEST['y']);
		$node_name = $_REQUEST['nodename'];

                act_move_node($node_name, $x, $y);
                
                print "{ status: 'OK' }";    
                break;
            
            case 'move_via':
                $x = intval($_REQUEST['x']);
		$y = intval($_REQUEST['y']);
		$via_name = $_REQUEST['vianame'];

                if(preg_match('/(.*)_via_(\d+)/',$via_name,$matches))
                {
                    $vnum = $matches[2];
                    $lname = $matches[1];
                    logentry("Moving VIA $vnum for LINK $lname");
                }
                
                $map = new WeatherMap;
                $map->ReadConfig($mapfile);
                $map->links[$lname]->vialist[$vnum][0]=$x;
                $map->links[$lname]->vialist[$vnum][1]=$y;
                $map->WriteConfig($mapfile);
                
                print "{ status: 'OK' }";    
                break;
            
            case 'add_via':
                $x = intval($_REQUEST['x']);
		$y = intval($_REQUEST['y']);
		$linkname = $_REQUEST['linkname'];

                if(preg_match('/(.*):(\d+)$/',$linkname,$matches))
                {
                    $link = $matches[1];
                    logentry("Adding VIA to LINK $link");
                }
                
                $map = new WeatherMap;
                $map->ReadConfig($mapfile);
                
                $map->links[$link]->vialist[]=array($x, $y);
                $map->WriteConfig($mapfile);
                
                print "{ status: 'OK' }";    
                break;
            
            case 'maplist':
                $files = act_list_configs($mapdir);
                $json = "{ \"status\": 'OK', \"files\": [";
                foreach ($files as $file)
                {
                    $json .= "{\"file\": ".js_escape($file[0]).", \"title\":".js_escape($file[1]).", \"locked\":".$file[2]." },\n";
                }
                $json = rtrim($json,", \n");
                $json .=  "] }";
		print $json;
                break;
        
        case 'dump_map':
                act_dump_map();
                break;
            
            default:
                print "{ status: 'Error', detail: 'Unknown command'}";
                break;
        }
    }
    
    function act_dump_map()
    {
        global $mapfile;

        $serial = 0;
        if(isset($_REQUEST['serial'])) $serial = intval($_REQUEST['serial']);
        $newserial = $serial+1;

      //  header('Content-type: text/plain');

        $json = "{ \n";

        $json .= "\"map\": {  \n";
        // read cache
        $json .= fetch_cached_file("_map.json",$mapfile);
        $json .= "\n},\n";

        $json .= "\n\"nodes\": {\n";
        // read cache
        $json .= fetch_cached_file("_nodes_lite.json",$mapfile);
        $json .= "\n},\n";

        $json .= "\"links\": {\n";
        // read cache
        $json .= fetch_cached_file("_links_lite.json",$mapfile);
        $json .= "\n},\n";

		if(1==0)
		{
        $json .= "\"imap\": [\n";
        // read cache
        $json .= fetch_cached_file("_imap.json",$mapfile);
        $json .= "\n],\n";
		}
		
        $json .= "\"serial\": $newserial,\n";
        $json .= "\"valid\": 1}\n";

        print $json;

        logentry("Dumped map data as JSON");
    }
    
    function act_list_configs($mapdir)
    {
        $files = array();
        
        if (is_dir($mapdir))
	{
		$n=0;
		$dh=opendir($mapdir);

		if ($dh)
		{
			while ($file=readdir($dh))
			{
				$realfile=$mapdir . DIRECTORY_SEPARATOR . $file;

				if ( (is_file($realfile)) && (is_readable($realfile)) )
				{
					$title='(no title)';
                                        $readonly=1;
					$fd=fopen($realfile, "r");

					while (!feof($fd))
					{
						$buffer=fgets($fd, 4096);

						if (preg_match("/^\s*TITLE\s+(.*)$/i", $buffer, $matches)) { $title=rtrim($matches[1]); }
					}
					fclose ($fd);
                                        if(is_writable($realfile)) $readonly = 0;
                                        $files[] = array($file, $title,$readonly);
					$n++;
				}
			}
                        
			closedir ($dh);
		}
                else
                {
                    $files[] = array("__ERROR__","Could not read map directory");
                }
	}
        else
        {
            $files[] = array("__ERROR__","Map directory is not a directory");
        }
  
        usort($files, "filesort");
        
        return($files);
    }
    
    function act_move_node($node_name, $x, $y)
    {
        global $mapfile;
        
         $map = new WeatherMap;

        $map->ReadConfig($mapfile);
        
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
        
        $map->nodes[$node_name]->x = $x;
        $map->nodes[$node_name]->y = $y;

        $map->WriteConfig($mapfile);
    }
    
    function logentry($line)
    {
        $fd = fopen("ajaxlog.txt","a");
        fputs($fd,date('U',time())." $line\r\n");
        fclose($fd);
    }
    
    function coloursort($a, $b)
{
	if ($a['bottom'] == $b['bottom'])
	{
		if($a['top'] < $b['top']) { return -1; };
		if($a['top'] > $b['top']) { return 1; };
		return 0;
	}

	if ($a['bottom'] < $b['bottom']) { return -1; }

	return 1;
} 
    
    function filesort($a,$b)
    {
        if($a[0] < $b[0]) return -1;
        if($a[0] == $b[0]) return 0;
        return 1;
    }
    
 
        function fetch_cached_file($filename,$mapfile)
        {
                $cachefileprefix = "editcache".DIRECTORY_SEPARATOR.dechex(crc32($mapfile));
                $cachetestfile = $cachefileprefix.$filename;
        
                if( (isset($_REQUEST['develmode'])) || (!file_exists($cachetestfile)) || (filemtime($cachetestfile) < filemtime($mapfile )) )
                {
                        logentry("Updating Cache for $filename");
                        // re-write cache
        
                        $map = new Weathermap;
                        $map->context="editor2";
                        $map->cachefolder="editcache";
                        $map->ReadConfig($mapfile);
                        $map->DrawMap('null','',250,FALSE);
                        $map->htmlstyle='editor';
        
                        $weathermap_debugging = TRUE;
                        $map->CacheUpdate();
                        $map->CleanUp();
                }
        
                if( (!file_exists($cachetestfile)) || (filemtime($cachetestfile) < filemtime($mapfile )) )
                {
                        logentry("Cache still invalid after rewrite");
                }
        
                $content = file_get_contents($cachetestfile);
        
                return($content);
        }
    
?>