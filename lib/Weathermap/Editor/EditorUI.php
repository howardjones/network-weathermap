<?php
// PHP Weathermap 0.97b
// Copyright Howard Jones, 2005-2012 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License


namespace Weathermap\Editor;

use Weathermap\UI\UIBase;

use Weathermap\UI\SimpleTemplate;
use Weathermap\Core\WeathermapInternalFail;
use Weathermap\Core\Map;
use Weathermap\Core\MapUtility;

/** The various functions concerned with the actual presentation of the supplied editor, and
 *  validation of input etc. Mostly class methods.
 */
class EditorUI extends UIBase
{
    private $editor;

    private $selected = "";
    private $mapFileName;
    private $mapShortName;


    private $fromPlugin;
    private $foundHost = false;
    private $cactiBase = "../..";
    private $hostURL = "/";
    private $ignoreCacti = false;
    private $configError = "";
    private $nextAction = "";
    private $logMessage = "";
    private $param2 = "";
    private $mapDirectory = "configs";

    private $useOverlayVIA = false;
    private $useOverlayRelative = false;
    private $gridSnapValue = 0;

    private $minBGImageSize = 80;

    // All the valid commands, and their expected parameters, so we can centralise the validation
    public $commands = array(
        "add_node" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("x", "int"),
                array("y", "int")
            ),
            "working" => true,
            "handler" => "cmdAddNode"
        ),
        "move_node" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("x", "int"),
                array("y", "int"),
                array("param", "name")
            ),
            "working" => true,
            "handler" => "cmdMoveNode"
        ),
        "newmap" => array(
            "args" => array(
                array("mapname", "mapfile"),
            ),
            "working" => true,
            "handler" => "cmdNewMap"
        ),
        "newmapcopy" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("sourcemap", "mapfile"),
            ),
            "late_load" => true,
            "working" => true,
            "handler" => "cmdNewMapCopy"
        ),
        "font_samples" => array(
            "args" => array(
                array("mapname", "mapfile"),
            ),
            "working" => true,
            "handler" => "cmdDrawFontSamples",
            "late_load" => true,
            "no_save" => true
        ),
        "draw" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("selected", "jsname", true), // optional
            ),
            "working" => true,
            "handler" => "cmdDrawMap",
            "late_load" => true,
            "no_save" => true
        ),
        "show_config" => array(
            "args" => array(
                array("mapname", "mapfile"),
            ),
            "working" => true,
            "handler" => "cmdShowConfig",
            "no_save" => true
        ),
        "fetch_config" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("item_type", "item_type"),
                array("item_name", "name"),
            ),
            "working" => true,
            "handler" => "cmdGetItemConfig",
            "no_save" => true
        ),
        "set_link_config" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("item_configtext", "string"),
                array("link_name", "name"),
            ),
            "working" => true,
            "reload_after_action" => true,
            "handler" => "cmdReplaceLinkConfig"
        ),
        "set_node_config" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("item_configtext", "string"),
                array("node_name", "name"),
            ),
            "working" => true,
            "reload_after_action" => true,
            "handler" => "cmdReplaceNodeConfig"
        ),
        "add_link" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("param", "name"),
            ),
            "working" => true,
            "handler" => "cmdAddLinkInitial"
        ),
        "add_link2" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("param", "name"),
                array("param2", "name"),
            ),
            "working" => true,
            "handler" => "cmdAddLinkFinal"
        ),
        "place_legend" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("param", "name"),
                array("x", "int"),
                array("y", "int"),
            ),
            "working" => true,
            "handler" => "cmdMoveLegend"
        ),
        "place_stamp" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("x", "int"),
                array("y", "int"),
            ),
            "working" => true,
            "handler" => "cmdMoveTimestamp"
        ),
        "place_title" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("x", "int"),
                array("y", "int"),
            ),
            "working" => true,
            "handler" => "cmdMoveTitle"
        ),
        "via_link" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("param", "name"),
                array("x", "int"),
                array("y", "int"),
            ),
            "working" => true,
            "handler" => "cmdAddLinkVia"
        ),
        "straight_link" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("param", "name")
            ),
            "working" => true,
            "handler" => "cmdLinkStraighten"
        ),
        "delete_link" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("param", "name"),
            ),
            "working" => true,
            "handler" => "cmdDeleteLink"
        ),
        "delete_node" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("param", "name"),
            ),
            "working" => true,
            "handler" => "cmdDeleteNode"
        ),
        "clone_node" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("param", "name"),
            ),
            "working" => true,
            "handler" => "cmdCloneNode"
        ),
        "link_tidy" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("param", "name"),
            ),
            "working" => true,
            "handler" => "cmdTidyLink"
        ),
        "tidy_all" => array(
            "args" => array(
                array("mapname", "mapfile")
            ),
            "working" => true,
            "handler" => "cmdTidyAllLinks"
        ),
        "retidy" => array(
            "args" => array(
                array("mapname", "mapfile")
            ),
            "working" => true,
            "handler" => "cmdReTidyLinks"
        ),
        "untidy" => array(
            "args" => array(
                array("mapname", "mapfile")
            ),
            "working" => true,
            "handler" => "cmdUnTidyAllLinks"
        ),
        "set_link_properties" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("param", "name"),
                array("link_name", "name"),
                array("link_bandwidth_in", "string"),
                array("link_bandwidth_out", "string", true),
                array("link_bandwidth_out_cb", "string", true),
                array("link_width", "int"),
                array("link_target", "string"),
                array("link_hover", "string"),
                array("link_infourl", "string"),
                array("link_commentin", "string"),
                array("link_commentout", "string"),
                array("link_commentposout", "int"),
                array("link_commentposin", "int")
            ),
            "working" => true,
            "handler" => "cmdLinkProperties"
        ),
        "set_node_properties" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("param", "name"),
                array("node_x", "int"),
                array("node_y", "int"),
                array("node_lock_to", "name"),
                array("node_name", "name"),
                array("node_new_name", "name"),
                array("node_label", "string"),
                array("node_infourl", "string"),
                array("node_hover", "string"),
                array("node_iconfilename", "string")
            ),
            "working" => true,
            "handler" => "cmdNodeProperties"
        ),
        "set_map_style" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array('mapstyle_linklabels', 'string'),
                array('mapstyle_arrowstyle', 'string'),
                array('mapstyle_nodefont', 'int'),
                array('mapstyle_linkfont', 'int'),
                array('mapstyle_legendfont', 'int'),
                array('mapstyle_htmlstyle', 'string'),
            ),
            "working" => true,
            "handler" => "cmdMapStyle"
        ),
        "set_map_properties" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array("map_title", "string"),
                array("map_legend", "string"),
                array("map_width", "int"),
                array("map_height", "int"),
                array("map_stamp", "string"),
                array("map_bgfile", "string"),
                array("map_pngfile", "string"),
                array("map_htmlfile", "string"),
                array("map_linkdefaultwidth", "float"),
                array("map_linkdefaultbwin", "bandwidth"),
                array("map_linkdefaultbwout", "bandwidth"),
            ),
            "handler" => "cmdMapProperties",
            "working" => true
        ),
        "editor_settings" => array(
            "args" => array(
                array("mapname", "mapfile"),
                array('editorsettings_showvias', 'int'),
                array('editorsettings_showrelative', 'int'),
                array('editorsettings_gridsnap', 'int'),
            ),
            "handler" => "cmdEditorSettings",
            "working" => true,
        ),
        "nothing" => array(
            "args" => array(
                array("mapname", "mapfile")
            ),
            "handler" => "cmdDoNothing",
            "working" => true,
            "no_save" => true
        )
    );

    public function __construct()
    {
        $this->editor = new Editor();
    }

    /**
     * @param string[] $params
     * @param Editor $editor
     *
     * @return bool
     */
    public function cmdDoNothing($params, $editor)
    {
        return true;
    }

    /**
     * @param string[] $params
     * @param Editor $editor
     * @return bool
     */
    public function cmdShowConfig($params, $editor)
    {
        header("Content-type: text/plain");

        print $editor->getConfig();

        return false;
    }

    /**
     * @param string[] $params
     * @param Editor $editor
     * @return bool
     */
    public function cmdDrawMap($params, $editor)
    {
        header("Content-type: image/png");
        // If the config file hasn't changed, then the image produced shouldn't have, either
        $etag = md5_file($this->mapFileName);
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
        $editor->loadConfig($this->mapFileName);

        if (isset($params['selected']) && substr($params['selected'], 0, 5) == 'NODE:') {
            $nodename = substr($params['selected'], 5);
            if ($editor->map->nodeExists($nodename)) {
                $editor->map->nodes[$nodename]->selected = 1;
            }
        }

        $editor->map->drawMap('', '', 250, true, $this->useOverlayVIA, $this->useOverlayRelative);

        return false;
    }

    /**
     * @param string[] $params
     * @param Editor $editor
     * @return bool
     */
    public function cmdDrawFontSamples($params, $editor)
    {
        header("Content-type: image/png");

        // If the config file hasn't changed, then the image produced shouldn't have, either
        $etag = md5_file($this->mapFileName);
        header("Etag: $etag");

        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) {
            header("HTTP/1.1 304 Not Modified");
            return false;
        }
        $editor->loadConfig($this->mapFileName);

        $imageRef = $this->generateFontSampleImage($editor->map);
        imagepng($imageRef);
        imagedestroy($imageRef);

        return false;
    }

    // cmd* methods below here translate form inputs into Editor API calls, which do the real work

    /**
     * @param Map $map
     * @return resource
     */
    public function generateFontSampleImage($map)
    {
        $keyFontNumber = 2;
        $keyFont = $map->fonts->getFont($keyFontNumber);
        $keyHeight = imagefontheight($keyFontNumber) + 2;
        $sampleHeight = 32;

        $fontsImageRef = imagecreate(2000, $sampleHeight);
        $keyImageRef = imagecreate(2000, $keyHeight);

//        $white = imagecolorallocate($fontsImageRef, 255, 255, 255);
        $black = imagecolorallocate($fontsImageRef, 0, 0, 0);

//        $whitekey = imagecolorallocate($keyImageRef, 255, 255, 255);
        $blackkey = imagecolorallocate($keyImageRef, 0, 0, 0);

        $fonts = $map->fonts->getList();
        ksort($fonts);

        $x = 3;
        foreach ($fonts as $fontNumber => $font) {
            $string = "Abc123%";
            $keyString = "Font $fontNumber";

            $font = $map->fonts->getFont($fontNumber);

            list($width, $height) = $font->calculateImageStringSize($string);
            list($kwidth, $kheight) = $keyFont->calculateImageStringSize($keyString);

            $width = max($kwidth, $width);

            $y = ($sampleHeight / 2) + $height / 2;
            $font->drawImageString($fontsImageRef, $x, $y, $string, $black);
            $keyFont->drawImageString($keyImageRef, $x, $keyHeight, "Font $fontNumber", $blackkey);

            $x = $x + $width + 6;
        }

        $finalImageRef = imagecreate($x, $sampleHeight + $keyHeight);
        imagecopy($finalImageRef, $fontsImageRef, 0, 0, 0, 0, $x, $sampleHeight);
        imagecopy($finalImageRef, $keyImageRef, 0, $sampleHeight, 0, 0, $x, $keyHeight);
        imagedestroy($fontsImageRef);

        return $finalImageRef;
    }

    /**
     * @param string[] $params
     * @param Editor $editor
     */
    public function cmdAddNode($params, $editor)
    {
        $nodeX = $this->snap($params['x']);
        $nodeY = $this->snap($params['y']);

        list($newName, $success, $log) = $editor->addNode($nodeX, $nodeY);
        $this->setLogMessage($log);
    }

    public function snap($coord)
    {
        if ($this->gridSnapValue == 0) {
            return $coord;
        } else {
            $rest = $coord % $this->gridSnapValue;
            return $coord - $rest + round($rest / $this->gridSnapValue) * $this->gridSnapValue;
        }
    }

    public function setLogMessage($message)
    {
        $this->logMessage = $message;
    }

    /**
     * @param string[] $params
     * @param Editor $editor
     */
    public function cmdCloneNode($params, $editor)
    {
        list($result, $affected, $log) = $editor->cloneNode($params['param']);
        $this->setLogMessage($log);

        return $result;
    }

    /**
     * @param string[] $params
     * @param Editor $editor
     */
    public function cmdAddLinkInitial($params, $editor)
    {
        $selected = 'NODE:' . $params['param'];
        // mark the node so that it will be drawn in red next time
        $this->setSelected($selected);
        // pre-set an action, so we start in node-picking mode
        $this->setNextAction("add_link2");
        // store the first choice, so that on the next pick, both are available
        $this->setParam2($params['param']);
        $this->setLogMessage("Waiting for second node");
    }

    public function setSelected($item)
    {
        $this->selected = $item;
    }

    public function setNextAction($action)
    {
        $this->nextAction = $action;
    }


    public function setParam2($value)
    {
        $this->param2 = $value;
    }

    /**
     * @param string[] $params
     * @param Editor $editor
     */
    public function cmdAddLinkFinal($params, $editor)
    {
        $editor->addLink($params['param'], $params['param2']);
    }

    /**
     * @param string[] $params
     * @param Editor $editor
     */
    public function cmdAddLinkVia($params, $editor)
    {
        $editor->setLinkVia($params['param'], floatval($params['x']), floatval($params['y']));
    }

    /**
     * @param string[] $params
     * @param Editor $editor
     */
    public function cmdLinkStraighten($params, $editor)
    {
        $editor->clearLinkVias($params['param']);
    }

    /**
     * @param string[] $params
     * @param Editor $editor
     */
    public function cmdLinkProperties($params, $editor)
    {
        $update = array(
            "name" => $params['link_name'],
            "width" => $params['link_width'],
            "target" => $params['link_target'],
            "bandwidth_in" => $params['link_bandwidth_in'],
            "bandwidth_out" => $params['link_bandwidth_out'],
            "commentpos_in" => $params['link_commentposin'],
            "commentpos_out" => $params['link_commentposout'],
            "comment_in" => $params['link_commentin'],
            "comment_out" => $params['link_commentout'],
            "infourl" => $params['link_infourl'],
            "hover" => $params['link_hover']
        );

        if (isset($params['link_bandwidth_out_cb']) && $params['link_bandwidth_out_cb'] == 'symmetric') {
            $update['bandwidth_out'] = $update['bandwidth_in'];
        }

        $editor->updateLink($params['param'], $update);
    }

    /**
     * @param string[] $params
     * @param Editor $editor
     */
    public function cmdDeleteLink($params, $editor)
    {
        $editor->deleteLink($params['param']);
    }

    /**
     * @param string[] $params
     * @param Editor $editor
     */
    public function cmdDeleteNode($params, $editor)
    {
        $editor->deleteNode($params['param']);
    }

    /**
     * @param string[] $params
     * @param Editor $editor
     */
    public function cmdMoveNode($params, $editor)
    {
        $x = $this->snap($params['x']);
        $y = $this->snap($params['y']);

        $editor->moveNode($params['param'], $x, $y);
    }

    /**
     * @param string[] $params
     * @param Editor $editor
     */
    public function cmdNodeProperties($params, $editor)
    {
        $update = array(
            "name" => $params['node_name'],
            "x" => $params['node_x'],
            "y" => $params['node_y'],
            "lock_to" => $params['node_lock_to'],
            "new_name" => $params['node_new_name'],
            "label" => $params['node_label'],
            "infourl" => $params['node_infourl'],
            "hover" => $params['node_hover'],
            "iconfilename" => $params['node_iconfilename']
        );

        if ($update['iconfilename'] == '--NONE--') {
            $update['iconfilename'] = '';
        }

        if ($update['lock_to'] == '-- NONE --') {
            $update['lock_to'] = '';
        }

        $editor->updateNode($params['param'], $update);
    }

    /**
     * @param string[] $params
     * @param Editor $editor
     */
    public function cmdMapProperties($params, $editor)
    {
        $update = array(
            "title" => $params["map_title"],
            "legend" => $params["map_legend"],
            "width" => $params["map_width"],
            "height" => $params["map_height"],
            "stamp" => $params["map_stamp"],
            "bgfile" => ($params["map_bgfile"] == '--NONE--' ? '' : $params["map_bgfile"]),
            "pngfile" => $params["map_pngfile"],
            "htmlfile" => $params["map_htmlfile"],
            "linkdefaultwidth" => $params["map_linkdefaultwidth"],
            "linkdefaultbwin" => $params["map_linkdefaultbwin"],
            "linkdefaultbwout" => $params["map_linkdefaultbwout"],
        );


        $editor->updateMapProperties($update);
    }

    /**
     * @param string[] $params
     * @param Editor $editor
     * @throws WeathermapInternalFail
     */
    public function cmdMapStyle($params, $editor)
    {
        $update = array(
            "bwlabels" => $params['mapstyle_linklabels'],
            "htmlstyle" => $params['mapstyle_htmlstyle'],
            "arrowstyle" => $params['mapstyle_arrowstyle'],
            "nodefont" => $params['mapstyle_nodefont'],
            "linkfont" => $params['mapstyle_linkfont'],
            "legendfont" => $params['mapstyle_legendfont']
        );

        $editor->updateMapStyle($update);
    }

    /**
     * @param string[] $params
     * @param Editor $editor
     */
    public function cmdEditorSettings($params, $editor)
    {
        $this->useOverlayVIA = $params['editorsettings_showvias'] == 1 ? true : false;
        $this->useOverlayRelative = $params['editorsettings_showrelative'] == 1 ? true : false;
        $this->gridSnapValue = intval($params['editorsettings_gridsnap']);
    }


    /**
     * @param string[] $params
     * @param Editor $editor
     */
    public function cmdTidyLink($params, $editor)
    {
        $editor->tidyLink($params['param']);
    }

    /**
     * @param string[] $params
     * @param Editor $editor
     */
    public function cmdTidyAllLinks($params, $editor)
    {
        $editor->tidyAllLinks();
    }

    /**
     * @param string[] $params
     * @param Editor $editor
     */
    public function cmdReTidyLinks($params, $editor)
    {
        $editor->retidyLinks();
    }

    /**
     * @param string[] $params
     * @param Editor $editor
     */
    public function cmdReTidyAllLinks($params, $editor)
    {
        $editor->retidyAllLinks();
    }

    /**
     * @param string[] $params
     * @param Editor $editor
     */
    public function cmdUnTidyAllLinks($params, $editor)
    {
        $editor->untidyLinks();
    }

    /**
     * @param string[] $params
     * @param Editor $editor
     */
    public function cmdNewMap($params, $editor)
    {
        $editor->newConfig();
        $editor->saveConfig($this->mapFileName);
    }

    /**
     * @param string[] $params
     * @param Editor $editor
     */
    public function cmdNewMapCopy($params, $editor)
    {
        $editor->loadConfig($this->mapDirectory . "/" . $params['sourcemap']);
        $editor->saveConfig($this->mapFileName);
    }

    /**
     * @param string[] $params
     * @param Editor $editor
     *
     * @returns bool
     */
    public function cmdGetItemConfig($params, $editor)
    {
        header('Content-type: text/plain');

        $itemName = $params['item_name'];
        $itemType = $params['item_type'];

        print $editor->getItemConfig($itemType, $itemName);

        return false;
    }

    /**
     * @param string[] $params
     * @param Editor $editor
     */
    public function cmdReplaceNodeConfig($params, $editor)
    {
        $editor->replaceNodeConfig($params['node_name'], $params['item_configtext']);
    }

    /**
     * @param string[] $params
     * @param Editor $editor
     */
    public function cmdReplaceLinkConfig($params, $editor)
    {
        $editor->replaceLinkConfig($params['link_name'], $params['item_configtext']);
    }

    /**
     * @param string[] $params
     * @param Editor $editor
     */
    public function cmdMoveLegend($params, $editor)
    {
        $x = $this->snap($params['x']);
        $y = $this->snap($params['y']);
        $scaleName = $params['param'];

        $editor->placeLegend($x, $y, $scaleName);
    }

    /**
     * @param string[] $params
     * @param Editor $editor
     */
    public function cmdMoveTimestamp($params, $editor)
    {
        $x = $this->snap($params['x']);
        $y = $this->snap($params['y']);

        $editor->placeTimestamp($x, $y);
    }

    public function cmdMoveTitle($params, $editor)
    {
        $x = $this->snap($params['x']);
        $y = $this->snap($params['y']);

        $editor->placeTitle($x, $y);
    }

    // Labels for Nodes, Links and Scales shouldn't have spaces in
    public function sanitizeName($str)
    {
        return str_replace(array(" "), "", $str);
    }

    public function moduleChecks()
    {
        if (!MapUtility::moduleChecks()) {
            print "<b>Required PHP extensions are not present in your mod_php/ISAPI PHP module. Please check your PHP setup to ensure you have the GD extension installed and enabled.</b><p>";
            print "If you find that the weathermap tool itself is working, from the command-line or Cacti poller, then it is possible that you have two different PHP installations. The Editor uses the same PHP that webpages on your server use, but the main weathermap tool uses the command-line PHP interpreter.<p>";
            print "<p>You should also run <a href=\"check.php\">check.php</a> to help make sure that there are no problems.</p><hr/>";

            exit;
        }
    }


    public function extractSettingsCookie($cookies)
    {
        // these are all set via the Editor Settings dialog, in the editor, now.

        $this->useOverlayVIA = false; // set to TRUE to enable experimental overlay showing VIAs
        $this->useOverlayRelative = false; // set to TRUE to enable experimental overlay showing relative-positioning
        $this->gridSnapValue = 0; // set non-zero to snap to a grid of that spacing

        if (isset($cookies['wmeditor'])) {
            $parts = explode(":", $cookies['wmeditor']);

            if ((isset($parts[0])) && (intval($parts[0]) == 1)) {
                $this->useOverlayVIA = true;
            }

            if ((isset($parts[1])) && (intval($parts[1]) == 1)) {
                $this->useOverlayRelative = true;
            }

            if ((isset($parts[2])) && (intval($parts[2]) != 0)) {
                $this->gridSnapValue = intval($parts[2]);
            }
        }
    }

    public function createSettingsCookie()
    {
        $value = "";
        $value .= ($this->useOverlayVIA ? "1" : "0");
        $value .= ":";
        $value .= ($this->useOverlayRelative ? "1" : "0");
        $value .= ":";
        $value .= intval($this->gridSnapValue);

        setcookie("wmeditor", $value, time() + 60 * 60 * 24 * 30);
    }

    public function main($request, $cookies, $fromPlugin = false)
    {
        $mapFileName = "";
        $action = "";

        $this->extractSettingsCookie($cookies);

        if (isset($request['action'])) {
            $action = strtolower(trim($request['action']));
        }

        if (isset($request['mapname'])) {
            $mapFileName = $request['mapname'];

            if ($action == "newmap" || $action == "newmap_copy") {
                if (substr($mapFileName, -5, 5) != '.conf' && strlen($mapFileName) > 0) {
                    $mapFileName .= ".conf";

                    // put it back into the request, so that the validation doesn't fail
                    $request['mapname'] = $mapFileName;
                }
            }

            // If there's something funny with the config filename, just stop.
            if ($mapFileName != UIBase::wmeSanitizeConfigFile($mapFileName)) {
                throw new WeathermapInternalFail("Don't like this config filename $mapFileName");
            }

            $this->mapFileName = $this->mapDirectory . "/" . $mapFileName;
            $this->mapShortName = $mapFileName;
        }

        $this->setEmbedded($fromPlugin);

        if ($mapFileName == '') {
            $this->showStartPage();
            return;
        }

        if ($this->validateRequest($action, $request)) {
            $editor = new Editor();

            // if ($this->isEmbedded()) {}

            if (!isset($this->commands[$action]['late_load'])) {
                $editor->loadConfig($this->mapFileName);
            }

            $result = $this->dispatchRequest($action, $request, $editor);

            if (!isset($this->commands[$action]['no_save'])) {
                $editor->saveConfig();
            }

            // reload the new config if we directly set config using the Edit button in a node or link
            // otherwise it won't be reflected in the editor dialogs (even though it IS in the image)
            if (isset($this->commands[$action]['reload_after_action'])) {
                $editor->loadConfig($this->mapFileName);
            }

            if ($result !== false) {
                $this->showMainPage($editor);
            }
            return;
        }

        print "VALIDATION FAIL";
    }

    public function showStartPage()
    {
        $tpl = new SimpleTemplate();

        $tpl->set("WEATHERMAP_VERSION", WEATHERMAP_VERSION);
        $tpl->set("fromplug", $this->fromPlugin ? "1" : "0");

        list($titles, $notes, $errorstring) = $this->getExistingConfigs($this->mapDirectory);

        $niceTitles = array();
        $niceNotes = array();

        foreach ($titles as $file => $title) {
            $niceNote = htmlspecialchars($notes[$file]);
            $niceFileName = htmlspecialchars($file);
            $niceTitle = htmlspecialchars($title);

            $niceTitles[$niceFileName] = $niceTitle;
            $niceNotes[$niceFileName] = $niceNote;
        }

        $tpl->set("errorstring", $errorstring);
        $tpl->set("titles", $niceTitles);
        $tpl->set("notes", $niceNotes);

        echo $tpl->fetch("editor-resources/templates/front-oldstyle.php");
    }

    public function getExistingConfigs($mapDirectory)
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
            $fullFileName = $mapDirectory . DIRECTORY_SEPARATOR . $file;
            $note = "";

            // skip directories, unreadable files, .files and anything that doesn't come through the sanitiser unchanged
            if ($this->isEditableConfigFile($fullFileName)) {
                if (!is_writable($fullFileName)) {
                    $note .= "(read-only)";
                }

                $titles[$file] = $this->getTitleFromConfig($fullFileName, "(no title)");
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

    public static function getTitleFromConfig($filename, $defaultTitle = "")
    {
        $title = "";

        $fileHandle = fopen($filename, "r");
        if ($fileHandle) {
            while (!feof($fileHandle)) {
                $buffer = fgets($fileHandle, 4096);

                if (preg_match("/^\s*TITLE\s+(.*)/i", $buffer, $matches)) {
                    $title = trim($matches[1]);
                }
            }
            fclose($fileHandle);
        }

        if ($title == "") {
            $title = $defaultTitle;
        }

        return $title;
    }

    public function setEmbedded($state)
    {
        $this->fromPlugin = $state;
    }

    /**
     * @param Editor $editor
     */
    public function showMainPage($editor)
    {
        global $hostPluginURL;
        global $hostEditorURL;
        global $hostType;

        /* add a random bit onto the URL so that the next request won't get the same URL/ETag combo
           even if the config file contents don't change, but the two requests for the same editor page WILL */

        $mapURL = "?action=draw&mapname=" . $this->mapShortName . "&rand=" . rand(0, 100000);

        $tpl = new SimpleTemplate();
        $tpl->set("WEATHERMAP_VERSION", WEATHERMAP_VERSION);
        $tpl->set("fromplug", ($this->isEmbedded() ? 1 : 0));

        $tpl->set("host_url", $hostPluginURL);
        // TODO - this should come from Cacti or other host
        $tpl->set("host_type", $hostType);

        $tpl->set("imageurl", htmlspecialchars($mapURL));
        if ($this->selected != "") {
            $tpl->set("imageurl", htmlspecialchars($mapURL . "&selected=" . urlencode($this->selected)));
        }
        $tpl->set("mapname", htmlspecialchars($this->mapShortName));
        $tpl->set("newaction", htmlspecialchars($this->nextAction));
        $tpl->set("param2", htmlspecialchars($this->param2));

        // draw a map to throw away, just to get the imagemap updated
        $editor->map->DrawMap('null');
        $realHTMLStyle = $editor->map->htmlstyle;
        $editor->map->htmlstyle = 'editor';
        $editor->map->calculateImageMap();

        $tpl->set("imagemap", $editor->map->generateSortedImagemap("weathermap_imap"));
        $tpl->set("map_json", $editor->map->asJS());

        $editorSettings = array(
            'via_overlay' => $this->useOverlayVIA ? "1" : "0",
            'rel_overlay' => $this->useOverlayRelative ? "1" : "0",
            'grid_snap' => $this->gridSnapValue
        );

        $tpl->set("editor_settings", "var editor_settings = " . json_encode($editorSettings) . ";\n");

        $tpl->set("map_width", $editor->map->width);
        $tpl->set("map_height", $editor->map->height);
        $tpl->set("log", $this->logMessage);
        $tpl->set("editor_name", $hostEditorURL);

        $tpl->set("nodeselection", $this->makeNodeSelector($editor->map));

        $imageList = $this->getAvailableImages("images", $editor->map);

        $imagesJSON = "\nvar imlist = " . json_encode($imageList) . ";\n";
        $tpl->set("images_json", $imagesJSON);

        $todo = "";
        foreach ($this->commands as $cmdname => $cmd) {
            if (!isset($cmd['working']) || !$cmd['working']) {
                $todo .= "$cmdname ";
            }
        }
        if ($todo != "") {
            $todo = "Remaining to fix: " . $todo;
        }

        $tpl->set('internal', $todo);

        $fonts = $editor->map->fonts->getList();
        ksort($fonts);
        $fontsJSON = "\nvar fontlist = " . json_encode(array_keys($fonts)) . ";\n";
        $tpl->set("fonts_json", $fontsJSON);

        $globalData = array(
            "linklabels" => $editor->map->links['DEFAULT']->labelStyle,
            "linkfont" => $editor->map->links['DEFAULT']->bwfont,
            "nodefont" => $editor->map->nodes['DEFAULT']->labelfont,
            "arrowstyle" => $editor->map->links['DEFAULT']->arrowStyle,
            "htmlstyle" => $realHTMLStyle,
            "legendfont" => $editor->map->keyfont,
            "map_width" => $editor->map->width,
            "map_height" => $editor->map->height,
            "map_legendtext" => $editor->map->keytext['DEFAULT'],
            "map_stamp" => $editor->map->stamptext,
            "link_width" => $editor->map->links['DEFAULT']->width,
            "link_defaultbwin" => $editor->map->links['DEFAULT']->maxValuesConfigured[IN],
            "link_defaultbwout" => $editor->map->links['DEFAULT']->maxValuesConfigured[OUT],
            "map_pngfile" => $editor->map->imageoutputfile,
            "map_htmlfile" => $editor->map->htmloutputfile,
            "map_bgfile" => ($editor->map->background == '' ? '--NONE--' : $editor->map->background),
            "title" => $editor->map->title
        );
        $tpl->set("global", json_encode($globalData, JSON_PRETTY_PRINT));
        $tpl->set("global2", $editor->map->getJSONConfig());

        $this->createSettingsCookie();

        echo $tpl->fetch("editor-resources/templates/main-oldstyle.php");
    }

    /**
     * @param Map $map
     * @return string
     */
    private function makeNodeSelector($map)
    {
        $nodeList = $map->getRealNodes();

        $result = "";
        foreach ($nodeList as $node) {
            $result .= sprintf("<option>%s</option>", htmlspecialchars($node));
        }

        return $result;
    }

    public function isEmbedded()
    {
        return $this->fromPlugin;
    }

    /**
     * @param string $imageDirectory
     * @param Map $map
     * @return array
     */
    public function getAvailableImages($imageDirectory, $map)
    {
        $imageList = array();

        if (is_dir($imageDirectory)) {
            $dirHandle = opendir($imageDirectory);

            if ($dirHandle) {
                while ($file = readdir($dirHandle)) {
                    $realFile = $imageDirectory . DIRECTORY_SEPARATOR . $file;
                    $uri = $imageDirectory . "/" . $file;

                    if (is_readable($realFile) && (preg_match('/\.(gif|jpg|png)$/i', $file))) {
                        $size = getimagesize($realFile);
                        $bg = false;
                        if ($size[0] > $this->minBGImageSize && $size[1] > $this->minBGImageSize) {
                            $bg = true;
                        }
                        $imageList[] = array($uri, $bg);
                    }
                }

                closedir($dirHandle);
            }
        }

        $map->updateUsedImages();

        foreach ($map->usedImages as $im) {
            if (!in_array($im, $imageList)) {
                $bg = false;
                if ($map->background == $im) {
                    $bg = true;
                }

                $imageList[] = array($im, $bg);
            }
        }
        sort($imageList);

        return $imageList;
    }

    /**
     * @param string $fullFileName
     * @return bool
     */
    private function isEditableConfigFile($fullFileName)
    {
        $file = basename($fullFileName);

        return is_file($fullFileName)
            && is_readable($fullFileName)
            && !preg_match('/^\./', $file)
            && UIBase::wmeSanitizeConfigFile($file) == $file;
    }
}
