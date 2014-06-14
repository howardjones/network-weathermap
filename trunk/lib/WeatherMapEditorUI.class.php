<?php
// PHP Weathermap 0.97b
// Copyright Howard Jones, 2005-2012 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License



// Grabbed from here: http://www.massassi.com/php/articles/template_engines/

class SimpleTemplate {
    var $vars; /// Holds all the template variables

    /**
     * Constructor
     *
     * @param $file string the file name you want to load
     */
    function Template($file = null)
    {
        $this->file = $file;
    }

    /**
     * Set a template variable.
     */
    function set($name, $value)
    {
        $this->vars[$name] = is_object($value) ? $value->fetch() : $value;
    }

    /**
     * Open, parse, and return the template file.
     *
     * @param $file string the template file name
     */
    function fetch($file = null) {
        if (!$file) {
            $file = $this->file;
        }

        extract($this->vars);          // Extract the vars to local namespace
        ob_start();                    // Start output buffering
        include($file);                // Include the file
        $contents = ob_get_contents(); // Get the contents of the buffer
        ob_end_clean();                // End buffering and discard
        return $contents;              // Return the contents
    }
}

/** The various functions concerned with the actual presentation of the supplied editor, and
 *  validation of input etc. Mostly class methods.
 */

class WeatherMapEditorUI {

    var $editor;

    var $selected;
    var $mapfile;
    var $mapname;

    var $fromPlugin;
    var $foundCacti = false;
    var $cactiBase = "../..";
    var $cactiURL = "/";
    var $ignoreCacti = false;
    var $configError = "";
    var $mapDirectory = "configs";
    
    var $useOverlay = false;
    var $useOverlayRelative = false;
    var $gridSnapValue = 0;


    // All the valid commands, and their expected parameters, so we can centralise the validation
    var $commands = array(
        "add_node" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("x", "int"),
                array("y", "int")
            ),
            "handler" => "cmdAddNode"
        ),
        "move_node" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("x", "int"),
                array("y", "int"),
                array("node_name", "name")
            ),
            "handler" => "cmdMoveNode"
        ),
        "newmap" => array(
            "args" => array(
                array("mapname", "mapfile"),
            ),
            "handler" => "cmdNewMap"
        ),
        "newmap_copy" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("sourcemap", "mapfile"),
            ),
            "handler" => "cmdNewMapCopy"
        ),
        "font_samples" => array(
            "args" => array(
                array("mapname", "mapfile"),
            ),
            "handler" => "cmdDrawFontSamples"
        ),
        "draw" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("selected", "jsname", true), // optional
            ),
            "handler" => "cmdDrawMap"
        ),
        "show_config" => array(
            "args" => array(
                array("mapname", "mapfile"),
            ),
            "handler" => "cmdShowConfig"
        ),
        "fetch_config" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("item_type", array("node", "link")),
                array("item_name", "name"),
            ),
            "handler" => "cmdGetItemConfig"
        ),
        "set_link_config" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("item_configtext", "text"),
                array("link_name", "name"),
            ),
            "handler" => "cmdReplaceLinkConfig"
        ),
        "set_node_config" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("item_configtext", "text"),
                array("node_name", "name"),
            ),
            "handler" => "cmdReplaceNodeConfig"
        ),
        "add_link" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("param", "name"),
            ),
            "handler" => "cmdAddLinkInitial"
        ),
        "add_link2" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("param", "name"),
                array("param2", "name"),
            ),
            "handler" => "cmdAddLinkFinal"
        ),
        "place_legend" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("param", "name"),
                array("x", "int"),
                array("y", "int"),
            ),
            "handler" => "cmdMoveLegend"
        ),
        "place_stamp" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("x", "int"),
                array("y", "int"),
            ),
            "handler" => "cmdMoveTimestamp"
        ),
        "via_link" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("link_name", "name"),
                array("x", "int"),
                array("y", "int"),
            ),
            "handler" => "cmdAddLinkVia"
        ),
        "delete_link" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("param", "name"),
            ),
            "handler" => "cmdDeleteLink"
        ),
        "delete_node" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("param", "name"),
            ),
            "handler"=>"cmdDeleteNode"
        ),
        "clone_node" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("param", "name"),
            ),
            "handler" => "cmdCloneNode"
        ),
        "link_tidy" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("param", "name"),
            ),
            "handler" => "cmdTidyLink"
        ),

        "editor_settings" => array(),
        "set_node_properties" => array(),
        "set_link_properties" => array(),
        "set_map_properties" => array(),
        "set_map_style" => array(),
        "nothing" => array("args" => array(), "handler"=>"cmdDoNothing")
    );

    
    
      
    /**
     * Given an array of request variables (usually $_REQUEST), check that the
     * request is a valid one. Does the action exist? Do the arguments match the action?
     * Do they all match the expected type?
     *
     * @param string $action
     * @param string[] $request
     *
     * @return bool
     *
     */
    function validateRequest($action, $request)
    {
        if (!array_key_exists($action, $this->commands)) {
            return false;
        }
            
        // Now check all the required arguments exist, and are appropriate types
        $validation = $this->commands[$action];
        foreach ($validation['args'] as $arg) {
            $required = true;
            // some args are optional (not many)
            if (isset($arg[2]) && $arg[2]===true) {
                $required = false;
            }
            // fail if a required arg is missing
            if ($required && !isset($request[$arg[0]])) {
                return false;
            }
            // Go through the args, and check they look right
            $type = $arg[1];
            if (isset($request[$arg[0]])) {
                $value = $request[$arg[0]];
                if (!$this->validateArgument($type, $value)) {
                    return false;
                }
            }
        }
        
        // if we're still here, then it looked OK
        return true;
    }
    
    /**
     * Validate that a single value matches the expected type
     *
     * @param string $type
     * @param string $value
     *
     * @returns bool
     */
    function validateArgument($type, $value) {
        switch ($type) {
            case "int":
                if (is_int($value)) {
                    return true;
                }
                if ((is_numeric($value)&&(intval($value)==floatval($value)))) {
                    return true;
                }
                break;
            case "name":
                return true;
            case "jsname":
                return true;
            case "mapfile":
                return true;
            case "string":
                return true;
            default:
                // a type was specified that we didn't know - probably a problem
                return false;
        }
        return false;
    }

    /**
     * Call the relevant function to handle this request.
     * Pass only the expected (and by now, validated) parameters
     * from the HTTP request
     *
     * @param string $action
     * @param string[] $request
     *
     * @returns bool
     */ 
    function dispatchRequest($action, $request, $editor)
    {
        if (!array_key_exists($action, $this->commands)) {
            return false;
        }

        $command_info = $this->commands[$action];

        $params = array();
        foreach ($command_info['args'] as $arg) {
            if (isset($request[$arg[0]])) {
                $params[$arg[0]] = $request[$arg[0]];
            }
        }

        if (isset($command_info['handler'])) {
            $handler = $command_info['handler'];
            $result = $this->$handler($params, $editor);

            return $result;
        }

        return false;
    }

    // cmd* methods below here translate form inputs into Editor API calls, which do the real work

    /**
     * @param string[] $params
     * @param WeatherMapEditor $editor
     */
    function cmdDoNothing($params, $editor)
    {
        return true;
    }

    /**
     * @param string[] $params
     * @param WeatherMapEditor $editor
     */
    function cmdDrawMap($params, $editor)
    {
        header("Content-type: image/png");

        // TODO - add in checks for overlays
        // TODO - add in checks for SELECTED node
        $editor->map->drawMapImage('', '', 250, true, false, false);

        exit();
    }

    /**
     * @param string[] $params
     * @param WeatherMapEditor $editor
     */
    function cmdAddNode($params, $editor)
    {
        $x = $this->snap($params['x']);
        $y = $this->snap($params['y']);

        $editor->addNode($x, $y);
    }

    /**
     * @param string[] $params
     * @param WeatherMapEditor $editor
     */
    function cmdDeleteNode($params, $editor)
    {
        $editor->deleteNode($params['param']);
    }

    /**
     * @param string[] $params
     * @param WeatherMapEditor $editor
     */
    function cmdMoveNode($params, $editor)
    {
        $x = $this->snap($params['x']);
        $y = $this->snap($params['y']);

        $editor->moveNode($params['node_name'], $x, $y);
    }

    /**
     * @param string[] $params
     * @param WeatherMapEditor $editor
     */
    function cmdNewMap($params, $editor)
    {
        $editor->newConfig();
        $editor->saveConfig($this->mapfile);
    }

    /**
     * @param string[] $params
     * @param WeatherMapEditor $editor
     */
    function cmdNewMapCopy($params, $editor)
    {
        $editor->loadConfig($this->mapDirectory."/".$params['sourcemap']);
        $editor->saveConfig($this->mapfile);
    }

    function snap($coord)
    {
        if ($this->gridSnapValue == 0) {
            return ($coord);
        } else {        
            $rest = $coord % $this->gridSnapValue;
            return ($coord - $rest + round($rest/$this->gridSnapValue) * $this->gridSnapValue );
        }
    }
    
    // Labels for Nodes, Links and Scales shouldn't have spaces in
    function sanitizeName($str)
    {
        return str_replace( array(" "), "", $str);
    }    
 
    function moduleChecks()
    {
        if (!wm_module_checks()) {
            print "<b>Required PHP extensions are not present in your mod_php/ISAPI PHP module. Please check your PHP setup to ensure you have the GD extension installed and enabled.</b><p>";
            print "If you find that the weathermap tool itself is working, from the command-line or Cacti poller, then it is possible that you have two different PHP installations. The Editor uses the same PHP that webpages on your server use, but the main weathermap tool uses the command-line PHP interpreter.<p>";
            print "<p>You should also run <a href=\"check.php\">check.php</a> to help make sure that there are no problems.</p><hr/>";
            print "Here is a copy of the phpinfo() from your PHP web module, to help debugging this...<hr>";
            phpinfo();
            exit();
        }
    }
 
    /**
     * Attempt to load Cacti's config file - we'll need this to do integration like
     * the target picker.
     */
    function loadCacti($cacti_base)
    {
        $this->foundCacti = false;
        // check if the goalposts have moved
        if ( is_dir($cacti_base) && file_exists($cacti_base."/include/global.php") ) {
            // include the cacti-config, so we know about the database
                include_once($cacti_base."/include/global.php");
                $config['base_url'] = $cacti_url;
                $cacti_found = true;
        } elseif ( is_dir($cacti_base) && file_exists($cacti_base."/include/config.php") ) {
            // include the cacti-config, so we know about the database
                include_once($cacti_base."/include/config.php");
        
                $config['base_url'] = $cacti_url;
                $cacti_found = true;
        } else {
            $cacti_found = false;
        }
        
        $this->foundCacti = $cacti_found;
        return $this->foundCacti;
    }
    
    function unpackCookie($cookiename="wmeditor")
    {       
        if ( isset($_COOKIE[$cookiename])) {
            $parts = explode(":",$_COOKIE[$cookiename]);

            if ((isset($parts[0])) && (intval($parts[0]) == 1)) {
                $this->useOverlay = true;
            }
            if ((isset($parts[1])) && (intval($parts[1]) == 1)) {
                $this->useOverlayRelative = true;
            }
            if ((isset($parts[2])) && (intval($parts[2]) != 0)) {
                $this->gridSnapValue = intval($parts[2]);
            }
        }
    }
    
    function WeatherMapEditorUI()
    {
        $this->unpackCookie();
        $this->editor = new WeatherMapEditor();
                
    }
        
    function getExistingConfigs($mapdir)
    {
        $titles = array();
        $notes = array();
        
        $errorstring="";
                       
        if (is_dir($mapdir)) {
            $n=0;
            $dh=opendir($mapdir);
        
            if ($dh) {
                while (false !== ($file = readdir($dh))) {
                    $realfile=$mapdir . DIRECTORY_SEPARATOR . $file;
                    $note = "";
        
                    // skip directories, unreadable files, .files and anything that doesn't come through the sanitiser unchanged
                    if ( (is_file($realfile)) && (is_readable($realfile)) && (!preg_match("/^\./",$file) )  
                    //    && ( wm_editor_sanitize_conffile($file) == $file )
                     ) {
                        if (!is_writable($realfile)) {
                            $note .= "(read-only)";
                        }
                        $title='(no title)';
                        $fd=fopen($realfile, "r");
                        if ($fd) {
                            while (!feof($fd)) {
                                $buffer=fgets($fd, 4096);
        
                                if (preg_match("/^\s*TITLE\s+(.*)/i", $buffer, $matches)) {
                                    // $title= wm_editor_sanitize_string($matches[1]);
                                    $title = $matches[1];
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
        
        return array($titles, $notes, $errorstring);
    }
    
    function showStartPage()
    {
        global $WEATHERMAP_VERSION;

        $tpl = new SimpleTemplate();
        
        $tpl->set("WEATHERMAP_VERSION", $WEATHERMAP_VERSION);
        $tpl->set("fromplug", 1);        
        
        list($titles, $notes, $errorstring) = $this->getExistingConfigs($this->mapDirectory);
        
        foreach ($titles as $file=>$title) {
            $nicenote = htmlspecialchars($notes[$file]);
            $nicefile = htmlspecialchars($file);
            $nicetitle = htmlspecialchars($title);
            
            $nicetitles[$nicefile] = $nicetitle;
            $nicenotes[$nicefile] = $nicenote; 
        }
        
        $tpl->set("errorstring", $errorstring);
        $tpl->set("titles", $nicetitles);
        $tpl->set("notes", $nicenotes);
        
        echo $tpl->fetch("editor-resources/templates/front.php");
    }
    
    function showMainPage($editor)
    {
        global $WEATHERMAP_VERSION;
        
        $tpl = new SimpleTemplate();
        $tpl->set("WEATHERMAP_VERSION", $WEATHERMAP_VERSION);
        $tpl->set("fromplug", 1);

        $tpl->set("map_json", "");
        $tpl->set("images_json", "");

        $tpl->set("imageurl", htmlspecialchars("?action=draw&mapname=" . $this->mapname));
        $tpl->set("mapname", htmlspecialchars($this->mapname));
        $tpl->set("newaction", htmlspecialchars($this->mapname));
        $tpl->set("param2", htmlspecialchars($this->mapname));

        $editor->map->drawMapImage('null');
        $editor->map->htmlstyle='editor';
        $editor->map->calculateImageMap();

        $tpl->set("imagemap", $editor->map->generateSortedImagemap("weathermap_imap"));
        $tpl->set("map_json", $editor->map->asJS());
        $tpl->set("images_json", "");

        $tpl->set("map_width", $editor->map->width);
        $tpl->set("map_height", $editor->map->height);
        $tpl->set("log", "last log message");

        echo $tpl->fetch("editor-resources/templates/main.php");
    }
    
    function main()
    {
        $mapname = "";
        $action = "";
        
        if (isset($_REQUEST['action'])) {
            $action = strtolower(trim($_REQUEST['action']));
        }
        if (isset($_REQUEST['mapname'])) { 
            $mapname = $_REQUEST['mapname'];

            if($action=="newmap" || $action=="newmap_copy") {
                $mapname .= ".conf";
            }

            $this->mapfile = $this->mapDirectory."/".$mapname;
            $this->mapname = $mapname;
        }
        
        if ($mapname == '') {
            $this->showStartPage();
        } else {
            if ($this->validateRequest($action, $_REQUEST)) {
                # print "DO ACTION";
                $editor = new WeatherMapEditor();
                $editor->loadConfig($this->mapfile);
                $result = $this->dispatchRequest($action, $_REQUEST, $editor);
                $editor->saveConfig();
                $this->showMainPage($editor);
            } else {
                print "FAIL";                
            }
        }
        exit();
    }
}