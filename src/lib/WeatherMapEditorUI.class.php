<?php
// PHP Weathermap 0.97b
// Copyright Howard Jones, 2005-2012 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License

/** The various functions concerned with the actual presentation of the supplied editor, and
 *  validation of input etc. Mostly class methods.
 */

class WeatherMapEditorUI extends WeatherMapUIBase
{
    var $editor;

    var $selected = "";
    var $mapfile;
    var $mapname;

    var $fromPlugin;
    var $foundCacti = false;
    var $cactiBase = "../..";
    var $cactiURL = "/";
    var $ignoreCacti = false;
    var $configError = "";
    var $next_action = "";
    var $log_message = "";
    var $param2 = "";
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
                array("param", "name")
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
            "handler" => "cmdDrawFontSamples",
            "late_load" => true,
            "no_save" => true
        ),
        "draw" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("selected", "jsname", true), // optional
            ),
            "handler" => "cmdDrawMap",
            "late_load" => true,
            "no_save" => true
        ),
        "show_config" => array(
            "args" => array(
                array("mapname", "mapfile"),
            ),
            "handler" => "cmdShowConfig",
            "no_save" => true
        ),
        "fetch_config" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("item_type", array("node", "link")),
                array("item_name", "name"),
            ),
            "handler" => "cmdGetItemConfig",
            "no_save" => true
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
                array("param", "name"),
                array("x", "int"),
                array("y", "int"),
            ),
            "handler" => "cmdAddLinkVia"
        ),
        "straight_link" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("param", "name")
            ),
            "handler" => "cmdLinkStraighten"
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
        "tidy_all" => array(
            "args" => array(
                array("mapname", "mapfile")
            ),
            "handler" => "cmdTidyAllLinks"
        ),
        "retidy" => array(
            "args" => array(
                array("mapname", "mapfile")
            ),
            "handler" => "cmdReTidyAllLinks"
        ),
        "untidy_all" => array(
            "args" => array(
                array("mapname", "mapfile")
            ),
            "handler" => "cmdUnTidyAllLinks"
        ),
        "edit_link" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("param", "name"),
                array("link_name", "name"),
                array("link_bandwidth_in", "string"),
                array("link_bandwidth_out", "string"),
                array("link_target", "string"),
                array("link_hover", "string"),
                array("link_infourl", "string"),
                array("link_commentin", "string"),
                array("link_commentout", "string"),
                array("link_commentposout", "string"),
                array("link_commentposin", "string")
            ),
            "handler" => "cmdEditLink"
        ),
//        "editor_settings" => array(),
//        "set_node_properties" => array(),
//        "set_link_properties" => array(),
//        "set_map_properties" => array(),
//        "set_map_style" => array(),
        "nothing" => array("args" => array(array("mapname","mapfile")), "handler"=>"cmdDoNothing", "no_save" => true)
    );


    function setLogMessage($message)
    {
        $this->log_message = $message;
    }

    function setNextAction($action)
    {
        $this->next_action = $action;
    }

    function setParam2($value)
    {
        $this->param2 = $value;
    }

    function setSelected($item)
    {
        $this->selected = $item;
    }

    function setEmbedded($state)
    {
        $this->fromPlugin = $state;
    }

    function isEmbedded()
    {
        return($this->fromPlugin);
    }

    // cmd* methods below here translate form inputs into Editor API calls, which do the real work

    /**
     * @param string[] $params
     * @param WeatherMapEditor $editor
     *
     * @return bool
     */
    function cmdDoNothing($params, $editor)
    {
        return true;
    }

    function cmdShowConfig($params, $editor)
    {
        header("Content-type: text/plain");

        print $editor->getConfig();

        return false;
    }

    /**
     * @param string[] $params
     * @param WeatherMapEditor $editor
     * @return bool
     */
    function cmdDrawMap($params, $editor)
    {
        header("Content-type: image/png");
        // If the config file hasn't changed, then the image produced shouldn't have, either
        $etag = md5_file($this->mapfile);
        header("Etag: $etag");

        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) {
            header("HTTP/1.1 304 Not Modified");
            return false;
        }
        /*
         * NOW load the config, if it's needed. This means a simple refresh skips all the config parsing with
         * a browser that understands ETag headers. We also never save after a DrawMap, so that saves time too.
         * (300ms -> 10ms on a simple test map)
         * Downside - technically the MD5 of the config file is not the perfect hash - any changes to external
         * image files or font files are not reflected. To counteract that, the URL used by the editor has a
         * random addition so that it only matches between multiple URLs on the same page.
         */
        $editor->loadConfig($this->mapfile);

        // TODO - add in checks for overlays

        if (isset($params['selected']) && substr($params['selected'], 0, 5) == 'NODE:') {
            $nodename = substr($params['selected'], 5);
            $editor->map->nodes[$nodename]->selected=1;
        }

        $editor->map->drawMapImage('', '', 250, true, false, false);

        return false;
    }

    /**
     * @param string[] $params
     * @param WeatherMapEditor $editor
     */
    function cmdAddNode($params, $editor)
    {
        $nodeX = $this->snap($params['x']);
        $nodeY = $this->snap($params['y']);

        list($newname, $success, $log) = $editor->addNode($nodeX, $nodeY);
        $this->setLogMessage($log);
    }

    /**
     * @param string[] $params
     * @param WeatherMapEditor $editor
     */
    function cmdCloneNode($params, $editor)
    {
        list($result, $affected, $log) = $editor->cloneNode($params['param']);
        $this->setLogMessage($log);

        return $result;
    }

    function cmdAddLinkInitial($params, $editor)
    {
        $selected = 'NODE:'.$params['param'];
        // mark the node so that it will be drawn in red next time
        $this->setSelected($selected);
        // pre-set an action, so we start in node-picking mode
        $this->setNextAction("add_link2");
        // store the first choice, so that on the next pick, both are available
        $this->setParam2($params['param']);
        $this->setLogMessage("Waiting for second node");
    }

    function cmdAddLinkFinal($params, $editor)
    {
        $editor->addLink($params['param'], $params['param2']);
    }

    function cmdAddLinkVia($params, $editor)
    {
        $editor->setLinkVia($params['param'], $params['x'], $params['y']);
    }

    function cmdLinkStraighten($params, $editor)
    {
        $editor->clearLinkVias($params['param']);
    }

    function cmdEditLink($params, $editor)
    {

    }

    /**
     * @param string[] $params
     * @param WeatherMapEditor $editor
     */
    function cmdDeleteLink($params, $editor)
    {
        $editor->deleteLink($params['param']);
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

        $editor->moveNode($params['param'], $x, $y);
    }

    /**
     * @param string[] $params
     * @param WeatherMapEditor $editor
     */
    function cmdTidyLink($params, $editor)
    {
        $editor->tidyLink($params['param']);
    }

    /**
     * @param string[] $params
     * @param WeatherMapEditor $editor
     */
    function cmdTidyAllLinks($params, $editor)
    {
        $editor->tidyAllLinks();
    }

    /**
     * @param string[] $params
     * @param WeatherMapEditor $editor
     */
    function cmdReTidyAllLinks($params, $editor)
    {
        $editor->retidyAllLinks();
    }

    /**
     * @param string[] $params
     * @param WeatherMapEditor $editor
     */
    function cmdUnTidyAllLinks($params, $editor)
    {
        $editor->untidyLinks();
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

    function cmdDrawFontSamples($params, $editor)
    {

    }

    function cmdGetItemConfig($params, $editor)
    {

    }

    function cmdReplaceNodeConfig($params, $editor)
    {

    }

    function cmdReplaceLinkConfig($params, $editor)
    {

    }

    function cmdMoveLegend($params, $editor)
    {

    }

    function cmdMoveTimestamp($params, $editor)
    {

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
        return str_replace(array(" "), "", $str);
    }
 
    function moduleChecks()
    {
        if (!wm_module_checks()) {
            print "<b>Required PHP extensions are not present in your mod_php/ISAPI PHP module. Please check your PHP setup to ensure you have the GD extension installed and enabled.</b><p>";
            print "If you find that the weathermap tool itself is working, from the command-line or Cacti poller, then it is possible that you have two different PHP installations. The Editor uses the same PHP that webpages on your server use, but the main weathermap tool uses the command-line PHP interpreter.<p>";
            print "<p>You should also run <a href=\"check.php\">check.php</a> to help make sure that there are no problems.</p><hr/>";

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
        if (is_dir($cacti_base) && file_exists($cacti_base."/include/global.php")) {
            // include the cacti-config, so we know about the database
                include_once($cacti_base."/include/global.php");
                $config['base_url'] = $cacti_url;
                $cacti_found = true;
        } elseif (is_dir($cacti_base) && file_exists($cacti_base."/include/config.php")) {
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
    
    function unpackCookie($cookiename = "wmeditor")
    {
        if (isset($_COOKIE[$cookiename])) {
            $parts = explode(":", $_COOKIE[$cookiename]);

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

    function getTitleFromConfig($filename, $defaultTitle = "")
    {
        $title = "";

        $fileHandle=fopen($filename, "r");
        if ($fileHandle) {
            while (!feof($fileHandle)) {
                $buffer = fgets($fileHandle, 4096);

                if (preg_match("/^\s*TITLE\s+(.*)/i", $buffer, $matches)) {
                    $title = $matches[1];
                }
            }
            fclose($fileHandle);
        }

        if ($title == "") {
            $title = $defaultTitle;
        }

        return $title;
    }

    function getExistingConfigs($mapDirectory)
    {
        $titles = array();
        $notes = array();

        $errorString = "";

        if (!is_dir($mapDirectory)) {
            $errorString = "NO DIRECTORY named $mapDirectory";
            return array($titles, $notes, $errorString);
        }

        $numListed = 0;
        $directoryHandle = opendir($mapDirectory);

        if (!$directoryHandle) {
            $errorString = "Can't open map directory to read.";
            return array($titles, $notes, $errorString);
        }

        while (false !== ($file = readdir($directoryHandle))) {
            $realfile = $mapDirectory . DIRECTORY_SEPARATOR . $file;
            $note = "";

            // skip directories, unreadable files, .files and anything that doesn't come through the sanitiser unchanged
            if ((is_file($realfile)) && (is_readable($realfile)) && (!preg_match('/^\./', $file)) && (wmeSanitizeConfigFile($file) == $file)) {
                if (!is_writable($realfile)) {
                    $note .= "(read-only)";
                }

                $titles[$file] = $this->getTitleFromConfig($realfile, "(no title)");
                $notes[$file] = $note;
                $numListed++;
            }
        }
        closedir($directoryHandle);

        ksort($titles);

        if ($numListed == 0) {
            $errorString = "No usable files in map directory";
        }

        return array($titles, $notes, $errorString);
    }
    
    function showStartPage()
    {
        global $WEATHERMAP_VERSION;

        $tpl = new SimpleTemplate();
        
        $tpl->set("WEATHERMAP_VERSION", $WEATHERMAP_VERSION);
        $tpl->set("fromplug", 1);
        
        list($titles, $notes, $errorstring) = $this->getExistingConfigs($this->mapDirectory);
        
        foreach ($titles as $file => $title) {
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

        /* add a random bit onto the URL so that the next request won't get the same URL/ETag combo
           even if the config file contents don't change, but the two requests for the same editor page WILL */

        $map_url = "?action=draw&mapname=" . $this->mapname . "&rand=" . rand(0, 100000);

        $tpl = new SimpleTemplate();
        $tpl->set("WEATHERMAP_VERSION", $WEATHERMAP_VERSION);
        $tpl->set("fromplug", ($this->isEmbedded() ? 1 : 0));

        $tpl->set("imageurl", htmlspecialchars($map_url));
        if ($this->selected != "") {
            $tpl->set("imageurl", htmlspecialchars($map_url . "&selected=" . urlencode($this->selected)));
        }
        $tpl->set("mapname", htmlspecialchars($this->mapname));
        $tpl->set("newaction", htmlspecialchars($this->next_action));
        $tpl->set("param2", htmlspecialchars($this->param2));

        // draw a map to throw away, just to get the imagemap updated
        $editor->map->drawMapImage('null');
        $editor->map->htmlstyle='editor';
        $editor->map->calculateImageMap();

        $tpl->set("imagemap", $editor->map->generateSortedImagemap("weathermap_imap"));
        $tpl->set("map_json", $editor->map->asJS());
        $tpl->set("images_json", "");

        $tpl->set("map_width", $editor->map->width);
        $tpl->set("map_height", $editor->map->height);
        $tpl->set("log", $this->log_message);

        echo $tpl->fetch("editor-resources/templates/main.php");
    }
    
    function main($request, $from_plugin = false)
    {
        $mapname = "";
        $action = "";
        
        if (isset($request['action'])) {
            $action = strtolower(trim($request['action']));
        }
        if (isset($request['mapname'])) {
            $mapname = $request['mapname'];

            if ($action=="newmap" || $action=="newmap_copy") {
                $mapname .= ".conf";
            }

            // If there's something funny with the config filename, just stop.
            if ($mapname != wmeSanitizeConfigFile($mapname)) {
                exit();
            }

            $this->mapfile = $this->mapDirectory."/".$mapname;
            $this->mapname = $mapname;
        }
        
        if ($mapname == '') {
            $this->showStartPage();
        } else {
            if ($this->validateRequest($action, $request)) {
                $editor = new WeatherMapEditor();
                $this->setEmbedded($from_plugin);
                if (!isset($this->commands[$action]['late_load'])) {
                    $editor->loadConfig($this->mapfile);
                }
                $result = $this->dispatchRequest($action, $request, $editor);
                if (!isset($this->commands[$action]['no_save'])) {
                    $editor->saveConfig();
                }
                if ($result !== false) {
                    $this->showMainPage($editor);
                }
            } else {
                print "FAIL";
            }
        }
    }
}
