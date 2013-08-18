<?php
// PHP Weathermap 0.97b
// Copyright Howard Jones, 2005-2012 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License

require_once dirname(__FILE__).'/WeatherMapEditor.class.php';

/** The various functions concerned with the actual presentation of the supplied editor, and
 *  validation of input etc. Mostly class methods.
 */

class WeatherMapEditorUI {

    var $editor;

    var $selected;
    var $mapfile;

    var $fromPlugin;
    var $foundCacti = FALSE;
    var $cactiBase = "../..";
    var $cactiURL = "/";
    var $ignoreCacti = FALSE;
    var $configError = "";
    var $mapDirectory = "configs";
    
    var $useOverlay = FALSE;
    var $useOverlayRelative = FALSE;
    var $gridSnapValue = 0;
    
    
    // All the valid commands, and their expected parameters, so we can centralise the validation
    var $commands = array(
        "add_node" => array(
                        args=>array(
                            array("mapname","mapfile"),
                            array("x","int"),
                            array("y","int")
                        )
                    ),
        "move_node" => array(
                        args=>array(
                            array("mapname","mapfile"),
                            array("x","int"),
                            array("y","int"),
                            array("node_name","name")
                        )
                    ),
        "newmap" => array(
                    args=>array(
                            array("mapname","mapfile"),
                        )
                    ),
        "newmap_copy" => array(
                    args=>array(
                            array("mapname","mapfile"),
                            array("sourcemap","mapfile"),
                        )
                    ),
        "font_samples" => array(
                    args=>array(
                            array("mapname","mapfile"),
                        )
                    ),
        "draw" => array(
                    args=>array(
                            array("mapname","mapfile"),
                            array("selected","jsname",TRUE), // optional
                        )
                    ),
        "show_config" => array(
                    args=>array(
                            array("mapname","mapfile"),
                        )
                    ),
        "fetch_config" => array(
                    args=>array(
                            array("mapname","mapfile"),
                            array("item_type",array("node","link")),
                            array("item_name","name"),
                        )
                    ),
        "set_link_config" => array(
                    args=>array(
                            array("mapname","mapfile"),
                            array("item_configtext","text"),
                            array("link_name","name"),
                        )
                    ),
        "set_node_config" => array(
                    args=>array(
                            array("mapname","mapfile"),
                            array("item_configtext","text"),
                            array("node_name","name"),
                        )
                    ),
        "add_link" => array(
                    args=>array(
                            array("mapname","mapfile"),
                            array("param","name"),
                        )
                    ),
        "add_link2" => array(
                    args=>array(
                            array("mapname","mapfile"),
                            array("param","name"),
                            array("param2","name"),
                        )
                    ),
        "place_legend" => array(
                    args=>array(
                            array("mapname","mapfile"),
                            array("param","name"),
                            array("x","int"),
                            array("y","int"),
                        )
                    ),
        "place_stamp" => array(
                    args=>array(
                            array("mapname","mapfile"),
                            array("x","int"),
                            array("y","int"),
                        )
                    ),
        "via_link" => array(
                    args=>array(
                            array("mapname","mapfile"),
                            array("link_name","name"),
                            array("x","int"),
                            array("y","int"),
                        )
                    ),
        "delete_link" => array(
                    args=>array(
                            array("mapname","mapfile"),
                            array("param","name"),
                        )
                    ),
        "delete_node" => array(
                    args=>array(
                            array("mapname","mapfile"),
                            array("param","name"),
                        )
                    ),
        "clone_node" => array(
                    args=>array(
                            array("mapname","mapfile"),
                            array("param","name"),
                        )
                    ),
        "link_align_horizontal" => array(
                    args=>array(
                            array("mapname","mapfile"),
                            array("param","name"),
                        )
                    ),
        "link_align_vertical" => array(
                    args=>array(
                            array("mapname","mapfile"),
                            array("param","name"),
                        )
                    ),
        
        "editor_settings" => array(),
        "set_node_properties" => array(),
        "set_link_properties" => array(),
        "set_map_properties" => array(),
        "set_map_style" => array(),
    );

    
    
      
    /**
     * Given an array of request variables (usually $_REQUEST), check that the
     * request is a valid one. Does the action exist? Do the arguments match the action?
     * Do they all match the expected type?
     *
     */
    function validateRequest($request)
    {
        if (!isset($request['action'])) {
            return FALSE;
        } else {
            $action = strtolower(trim($request['action']));
        }
        if (!array_key_exists($this->commands,$action)) {
            return FALSE;
        }
        
        // Now check all the required arguments exist, and are appropriate types
        $validation = $this->commands[$action];
        foreach ($action['args'] as $arg) {
            $required = TRUE;
            // some args are optional (not many)
            if(isset($arg[2]) && $arg[2]===TRUE) {
                $required = FALSE;
            }
            // fail if a required arg is missing
            if ($required && !isset($request[$arg[0]])) {
                return FALSE;
            }
            // Go through the args, and check they look right
            $type = $arg[1];
            $value = $request[$arg[0]];
            if(!$this->validateArgument($type, $value)) {
                return FALSE;
            }
        }
        
        // if we're still here, then it looked OK
        return TRUE;
    }
    
    /**
     * Validate that a single value matches the expected type
     */
    function validateArgument($type, $value) {
        switch ($type) {
            case "int":
                if ($value != intval($value)) {
                    return FALSE;
                }
                break;

            case "name":
                return TRUE;
                break;

            case "mapfile":
                return TRUE;
                break;

            case "string":
                return TRUE;
                break;

            default:
                // a type was specified that we didn't know - probably a problem
                return FALSE;
        }
    }
    
    /**
     * Call the relevant function to handle this request
     */ 
    function dispatchRequest($request)
    {
        return FALSE;
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
        $this->foundCacti = FALSE;
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
        
        $this->foundCacti = $cacti_found;
        return $this->foundCacti;
    }
    
    function unpackCookie($cookiename="wmeditor")
    {       
        if( isset($_COOKIE[$cookiename])) {
            $parts = explode(":",$_COOKIE[$cookiename]);
            
            if( (isset($parts[0])) && (intval($parts[0]) == 1) ) { $this->useOverlay = TRUE; }
            if( (isset($parts[1])) && (intval($parts[1]) == 1) ) { $this->useOverlayRelative = TRUE; }
            if( (isset($parts[2])) && (intval($parts[2]) != 0) ) { $this->gridSnapValue = intval($parts[2]); }   
        }
    }
    
    function WeatherMapEditorUI()
    {
        $this->unpackCookie();
        $this->editor = new WeatherMapEditor();
    }
    
    function showStartPage()
    {
        print "START";
    }
    
    function main()
    {
        $mapname = "";
        $action = "";
        
        if (isset($_REQUEST['action'])) { $action = $_REQUEST['action']; }
        if (isset($_REQUEST['mapname'])) { $mapname = $_REQUEST['mapname']; $mapname = wm_editor_sanitize_conffile($mapname); }
        
        if($mapname == '') {
            $this->showStartPage();
        } else {
            if($this->validateRequest($_REQUEST)) {
                $this->dispatchRequest($_REQUEST);
                print "ACTION";
            } else {
                print "FAIL";                
            }
        }
        exit();
    }
}