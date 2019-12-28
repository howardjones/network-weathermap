<?php

namespace Weathermap\Integrations\Cacti;

require_once dirname(__FILE__) . "/database.php";

use Weathermap\UI\UIBase;
use Weathermap\Integrations\MapManager;

/**
 * The common parts of the Cacti 'user' plugin (map display)
 *
 * @package Weathermap\Integrations\Cacti
 */
class WeatherMapCactiUserPlugin extends UIBase
{
    /** @var MapManager $manager */
    public $manager;
    public $myURL;
    public $editorURL;
    public $managementURL;
    private $outputDirectory;
    private $imageFormat;
    public $cactiConfig;
    public $configPath;
    public $managementRealm;
    public $editorRealm;

    public $commands = array(
        // These were used by the React UI, but might as well stay
        'maplist' => array('handler' => 'handleMapListAPI', 'args' => array()),
        'settings' => array('handler' => 'handleSettingsAPI', 'args' => array()),

        'viewthumb' => array(
            'handler' => 'handleBigThumb',
            'args' => array(array("id", "maphash"), array("time", "int", true))
        ),
        'viewthumb48' => array('handler' => 'handleLittleThumb', 'args' => array(array("id", "maphash"))),
        'viewimage' => array('handler' => 'handleImage', 'args' => array(array("id", "maphash"))),
        'viewhtml' => array('handler' => 'handleHTML', 'args' => array(array("id", "maphash"))),


        // BELOW HERE NEED PORTING FROM OLDER PLUGIN
        'viewmap' => array(
            'handler' => 'handleViewMap',
            'args' => array(array("id", "maphash"))
        ),

        'viewcycle' => array(
            'handler' => 'handleViewCycle',
            'args' => array(
                array("fullscreen", "int", true),
                array("group", "int", true)
            )
        ),

        ':: DEFAULT ::' => array(
            'handler' => 'handleDefaultView',
            'args' => array(
                array("group_id", "int", true)
            )
        )
    );

    public function __construct($config, $imageFormat, $basePath)
    {
        parent::__construct();

        $this->cactiConfig = $config;
        $this->myURL = "SHOULD-BE-OVERRIDDEN";
        $this->managementURL = "SHOULD-BE-OVERRIDDEN";
        $this->editorURL = "SHOULD-BE-OVERRIDDEN";
        $this->managementRealm = "SHOULD-BE-OVERRIDDEN";
        $this->editorRealm = "SHOULD-BE-OVERRIDDEN";
        $this->configPath = $basePath . '/configs';
        $this->outputDirectory = $basePath . '/output/';
        $this->imageFormat = $imageFormat;
        $pdo = weathermap_get_pdo();
        $cactiInterface = new CactiApplicationInterface($pdo);
        $this->manager = new MapManager($pdo, $this->configPath, $cactiInterface);
    }

    public function main($request)
    {
        $action = ":: DEFAULT ::";
        if (isset($request['action'])) {
            $action = strtolower(trim($request['action']));
        }
        if ($action == "") {
            $action = ":: DEFAULT ::";
        }


        if ($this->validateRequest($action, $request)) {
            $this->dispatchRequest($action, $request, null);
        } else {
            print "INPUT VALIDATION FAIL";
        }
    }

    public function makeURL($params, $altURL = "")
    {
        $baseURL = $this->myURL;
        if ($altURL != "") {
            $baseURL = $altURL;
        }
        $url = $baseURL . (strpos($this->myURL, '?') === false ? '?' : '&');

        $parts = array();
        foreach ($params as $name => $value) {
            $parts [] = urlencode($name) . "=" . urlencode($value);
        }
        $url .= join("&", $parts);

        return $url;
    }

    public function handleMapListAPI($request, $appObject)
    {
        header('Content-type: application/json');

        $userId = $this->manager->application->getCurrentUserId();
        $mapList = $this->manager->getMapsForUser($userId);
        $groups = $this->manager->getGroups();

        // filter groups to only contain groups used in $mapList
        // (no leaking other groups - could be things like customer or project names)

        $seenGroup = array();
        foreach ($mapList as $map) {
            $seenGroup[$map->group_id] = 1;
        }
        $newGroups = array();
        foreach ($groups as $group) {
            if (array_key_exists($group->id, $seenGroup)) {
                $newGroups [] = $group;
            }
        }

        $data = array(
            'maps' => $mapList,
            'groups' => $newGroups
        );

        print json_encode($data);
    }

    public function handleSettingsAPI($request, $appObject)
    {
        header('Content-type: application/json');

        $styleTextOptions = array("thumbs", "full", "full-first-only");
        $trueFalseLookup = array(false, true);

        $style = $this->manager->application->getAppSetting("weathermap_pagestyle", 0);

        $cycleTime = $this->manager->application->getAppSetting("weathermap_cycle_refresh", 0);
        if ($cycleTime == 0) {
            $cycleTime = 'auto';
        }

        $showAllMapsTab = $this->manager->application->getAppSetting("weathermap_all_tab", 0);
        $showMapSelector = $this->manager->application->getAppSetting("weathermap_map_selector", 0);

        $data = array(
            'wm_version' => WEATHERMAP_VERSION,
            'page_style' => $styleTextOptions[$style],
            'cycle_time' => (string)$cycleTime,
            'show_all_tab' => $trueFalseLookup[$showAllMapsTab],
            'map_selector' => $trueFalseLookup[$showMapSelector],
            'thumb_url' => $this->makeURL(array("action" => "viewthumb")) . "&id=",
            'image_url' => $this->makeURL(array("action" => "viewimage")) . "&id=",
            'html_url' => $this->makeURL(array("action" => "viewhtml")) . "&id=",
            'editor_url' => $this->editorURL,
            'maps_url' => $this->makeURL(array("action" => "maplist")),
            'docs_url' => 'docs/index.html',
            'management_url' => null
        );

        // only pass the managementURL if the user can manage
        if ($this->isWeathermapAdmin()) {
            $data['management_url'] = $this->managementURL;
        }

        print json_encode($data);
    }

    public function handleBigThumb($request, $appObject)
    {
        $this->outputMapImage($request['id'], ".thumb.");
    }

    public function handleLittleThumb($request, $appObject)
    {
        $this->outputMapImage($request['id'], ".thumb48.");
    }

    public function handleImage($request, $appObject)
    {
        $this->outputMapImage($request['id'], ".");
    }

    public function handleHTML($request, $appObject)
    {
        $this->outputMapHTML($request['id']);
    }

    protected function isWeathermapAdmin()
    {
        global $user_auth_realm_filenames;

        $realmId = 0;
        $realmName = $this->managementRealm;

        if (isset($user_auth_realm_filenames[$realmName])) {
            $realmId = $user_auth_realm_filenames[$realmName];
        }
        $userid = (isset($_SESSION["sess_user_id"]) ? intval($_SESSION["sess_user_id"]) : 1);
        $allowed = $this->manager->application->checkUserAccess($userid, $realmId);

        if ($allowed || (empty($realmId))) {
            return true;
        }

        return false;
    }

    /***
     * The main view. Draw maps or thumbs for the selected group ('all' is a special group)
     *
     * @param $request
     * @param $appObject
     */
    public function handleDefaultView($request, $appObject)
    {
        global $config;

        $weathermapPath = $config['url_path'] . 'plugins/weathermap/';
        $cactiResourcePath = $weathermapPath . 'cacti-resources/';

        $this->cactiGraphHeader();

        $pageStyle = $this->manager->application->getAppSetting("weathermap_pagestyle", 0);
        $userId = $this->manager->application->getCurrentUserId();
        $limitToGroup = $this->getGroupFilter($request);
        if ($limitToGroup == -2) {
            $limitToGroup = null;
        }

        $mapList = $this->manager->getMapsForUser($userId, $limitToGroup);

        // "First-only" style
        if ($pageStyle == 2) {
            $mapList = array($mapList[0]);
        }
        $mapCount = count($mapList);

        $this->outputMapHeader($mapList, false, $limitToGroup);

        // "thumbnail" style
        if ($pageStyle == 0 && $mapCount > 1) {
            $this->drawThumbnailView($mapList);
        } else {
            $this->drawFullMapView($mapList);
        }

        if ($mapCount == 0) {
            print "<div align=\"center\" style=\"padding:20px\"><em>You Have No Maps</em></div>\n";
        }
        $this->outputVersionBox();
        $this->outputOverlib();

        $this->cactiFooter();
    }


    // ***********************************************************************************************
    // Below here are the old server-side functions that are replaced by client components


    /***
     * View a map or group of maps in normal mode.
     *
     * @param $request
     * @param $appObject
     */
    public function handleViewMap($request, $appObject)
    {
        global $config;

        $weathermapPath = $config['url_path'] . 'plugins/weathermap/';
        $cactiResourcePath = $weathermapPath . 'cacti-resources/';
        $userId = $this->manager->application->getCurrentUserId();
        $mapId = $this->manager->translateFileHash($request['id']);

        $this->cactiGraphHeader();

        $map = $this->manager->getMapWithAccess($userId, $mapId);
        if (sizeof($map) == 1) {
            $map = $map[0];
            $this->outputMapSelector($mapId);
            $this->drawOneFullMap($map);
        }

        $this->outputVersionBox();
        $this->outputOverlib();

        $this->cactiFooter();
    }

    public function handleViewCycle($request, $appObject)
    {
        $userId = $this->manager->application->getCurrentUserId();
        $fullscreen = false;
        $group = null;

        if (isset($request['fullscreen']) && $request['fullscreen'] == 1) {
            $fullscreen = true;
        }
        if (isset($request['group']) && intval($request['group']) > 0) {
            $group = intval($request['group']);
        }

        $maplist = $this->manager->getMapsForUser($userId, $group);

        $class = $fullscreen ? "fullscreen" : "inplace";

        if ($fullscreen) {
            print "<!DOCTYPE html>\n";
            print "<html><head>";
            print '<LINK rel="stylesheet" type="text/css" media="screen" href="cacti-resources/weathermap.css">';
            print "<title>Network Weathermap</title>";
            print "</head><body id='wm_fullscreen'>";
        } else {
            $this->cactiGraphHeader();
            $this->outputMapHeader($maplist, false, $group);
        }

        print "<div id=\"overDiv\" style=\"position:absolute; visibility:hidden; z-index:1000;\"></div>\n";
        print "<script type=\"text/javascript\" src=\"overlib.js\"><!-- overLIB (c) Erik Bosrup --></script> \n";

        $this->cactiEnableGraphRefresh();

        $extraText = "";
        if ($group > 0) {
            $extraText = " in this group";
        }
        $this->outputCycleControls($class, $group, $extraText);

        print "<div class='all_map_holder $class'>";

        foreach ($maplist as $map) {
            $this->drawOneFullMap($map, !$fullscreen, false);
        }

        print "</div>";

        if (!$fullscreen) {
            $this->outputVersionBox();
            $this->cactiFooter();
        }

        $this->outputCycleCode($fullscreen);
    }


    /**
     * Figure out which tab to show in the main view. If one was requested, use that. Otherwise use the first one.
     *
     * @param $request
     * @return int
     */
    protected function getGroupFilter($request)
    {
        $tabs = $this->getValidTabs();
        $tabIDs = array_keys($tabs);

        $limitToGroup = $this->getRequiredGroup($request);
        // XXX - will this ever be true?
        if (($limitToGroup == -1) && (count($tabIDs) > 0)) {
            $limitToGroup = $tabIDs[0];
        }
        return $limitToGroup;
    }

    /**
     * If a request has a group specified, use it.
     * If it doesn't see if we have stored a previously requested group.
     *
     * @param $request
     * @return int
     */
    protected function getRequiredGroup($request)
    {
        $groupID = -1;
        if (isset($request['group_id'])) {
            $groupID = $request['group_id'];
            $this->manager->application->setAppSetting("wm_last_group", $groupID);
            return $groupID;
        }

        return $this->manager->application->getAppSetting("wm_last_group", $groupID);
    }

    protected function getValidTabs()
    {
        $tabs = array();

        $maps = $this->manager->getMapsWithAccessAndGroups($this->manager->application->getCurrentUserId());

        foreach ($maps as $map) {
            $tabs[$map->group_id] = $map->name;
        }

        return $tabs;
    }


    protected function outputVersionBox()
    {
        $pageFooter = "Powered by <a href=\"http://www.network-weathermap.com/?v=" . WEATHERMAP_VERSION . "\">"
            . "PHP Weathermap version " . WEATHERMAP_VERSION . "</a>";

        $isAdmin = $this->isWeathermapAdmin();

        if ($isAdmin) {
            $pageFooter .= ' --- <a href="' . $this->managementURL . '" title="Go to the map management page">';
            $pageFooter .= 'Weathermap Management</a> | <a target="_blank" href="docs/index.html">Local Documentation</a>';
            $pageFooter .= ' | <a target="_blank" href="' . $this->editorURL . '">Editor</a>';
        }


        \html_graph_start_box(1, true);
        ?>
        <tr class='even'>
            <td>
                <table width='100%' cellpadding='0' cellspacing='0'>
                    <tr>
                        <td class='textHeader' nowrap> <?php print $pageFooter; ?> </td>
                    </tr>
                </table>
            </td>
        </tr>
        <?php
        \html_graph_end_box();
    }

    protected function outputOverlib()
    {
        print "<div id=\"overDiv\" style=\"position:absolute; visibility:hidden; z-index:1000;\"></div>\n";
        print "<script type=\"text/javascript\" src=\"overlib.js\"><!-- overLIB (c) Erik Bosrup --></script> \n";
    }

    protected function outputMapViewHeader($pageTitle, $isCycling, $limitingToGroup)
    {
        $groupCycleURL = $this->makeURL(
            array(
                "action" => "viewcycle",
                "group" => $limitingToGroup
            )
        );
        $allCycleURL = $this->makeURL(array("action" => "viewcycle"));

        \html_graph_start_box($pageTitle, '100%', '', '3', 'center', '');
        ?>
        <tr class="even">
            <td>
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="textHeader" nowrap> <?php print $pageTitle; ?> </td>
                        <td align="right">
                            <?php
                            if (!$isCycling) {
                                ?>
                                (automatically cycle between full-size maps (<?php

                                if ($limitingToGroup > 0) {

                                    print '<a href = "' . $groupCycleURL . '">within this group</a>, or ';
                                }
                                print ' <a href = "' . $allCycleURL . '">all maps</a>';
                                ?>)
                                <?php
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td><i>Click on thumbnails for a full view (or you can <a href="<?php echo $groupCycleURL ?>">automatically
                        cycle</a> between full-size maps)</i></td>
        </tr>
        <?php
        \html_graph_end_box();

        $this->outputGroupTabs($limitingToGroup);
    }

    protected function outputGroupTabs($currentTab)
    {
        $tabs = $this->getValidTabs();

        if (count($tabs) > 1) {
            /* draw the categories tabs on the top of the page */
            print "<p></p><table class='tabs' width='100%' cellspacing='0' cellpadding='3' align='center'><tr>\n";

            if (count($tabs) > 0) {
                $showAll = intval($this->manager->application->getAppSetting("weathermap_all_tab", 0));
                if ($showAll == 1) {
                    $tabs['-2'] = "All Maps";
                }

                foreach (array_keys($tabs) as $tabShortName) {
                    print "<td " . (($tabShortName == $currentTab) ? "bgcolor='silver'" : "bgcolor='#DFDFDF'") . " nowrap='nowrap' width='" . (strlen($tabs[$tabShortName]) * 9) . "' align='center' class='tab'>                    <span class='textHeader'><a                    href='" . $this->makeURL(array("group_id" => $tabShortName)) . "'>$tabs[$tabShortName]</a></span>                    </td>\n                    <td width='1'></td>\n";
                }
            }

            print "<td></td>\n</tr></table>\n";

            return true;
        }

        return false;
    }

    protected function outputMapSelector($current_id = 0)
    {
        $showMapSelector = $this->manager->application->getAppSetting("weathermap_map_selector", 0);

        if ($showMapSelector == 0) {
            return;
        }

        $userId = $this->manager->application->getCurrentUserId();
        $maps = $this->manager->getMapsWithAccessAndGroups($userId);

        if (sizeof($maps) > 1) {

            $nullhash = "xxxx";
            $all_groups = array_map(function ($x) {
                return $x->name;
            }, $maps);
            $known_groups = array_unique($all_groups);
            $ngroups = sizeof($known_groups);

            $current_map = array_filter($maps, function ($x) use ($current_id) {
                return ($x->id == $current_id);
            });

            if (sizeof($current_map) == 1) {
                $nullhash = array_shift($current_map)->filehash;
            }

            $select_box = "";

            $lastgroup = "------lasdjflkjsdlfkjlksdjflksjdflkjsldjlkjsd";
            foreach ($maps as $map) {
                if ($ngroups > 1 && $map->name != $lastgroup) {
                    $select_box .= "<option style='font-weight: bold; font-style: italic' value='$nullhash'>" . htmlspecialchars($map->name) . "</option>";
                    $lastgroup = $map->name;
                }
                $select_box .= '<option ';
                if ($current_id == $map->id) {
                    $select_box .= " SELECTED ";
                }
                $select_box .= 'value="' . $map->filehash . '">';
                // if we're showing group headings, then indent the map names
                if ($ngroups > 1) {
                    $select_box .= " - ";
                }
                $select_box .= htmlspecialchars($map->titlecache) . '</option>';
            }
            \html_graph_start_box(3, true);
            $color = $this->colours["panel"];
            print "<tr bgcolor=\"$color\" class=\"noprint\"> <form name=\"weathermap_select\" method=\"post\" action=\"\"> <input name=\"action\" value=\"viewmap\" type=\"hidden\"> <td class=\"noprint\"> <table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\"> <tr class=\"noprint\"> <td nowrap style='white-space: nowrap;' width=\"40\"> &nbsp;<strong>Jump To Map:</strong>&nbsp; </td> <td> ";
            print "<select name=\"id\">" . $select_box . "</select>";
            print "&nbsp;<input type=\"image\" src=\"../../images/button_go.gif\" alt=\"Go\" border=\"0\" align=\"absmiddle\">                                                                               ";
            print " </td> </tr> </table> </td> </form> </tr> ";
            \html_graph_end_box(false);

        }
    }

    private function drawThumbnailView($mapList)
    {
        if (count($mapList) > 0) {
            \html_start_box("", '100%', '', '3', 'center', '');

            print "<tr><td class='wm_gallery'>";
            foreach ($mapList as $map) {
                $this->drawOneThumbnail($map);
            }
            print "</td></tr>";
            \html_end_box();
        }
    }

    private function drawFullMapView($mapList)
    {
        // make sure that we use the Cacti refresh meta tags
        $this->cactiEnableGraphRefresh();

        if (count($mapList) == 0) {
            return;
        }


        print "<div class='all_map_holder'>";

        foreach ($mapList as $map) {
            $this->drawOneFullMap($map);
        }

        print "</div>";
    }

    /**
     * @param $map
     */
    private function drawOneThumbnail($map)
    {
        $imgSize = "";
        $thumbnailFilename = $this->outputDirectory . DIRECTORY_SEPARATOR . $map->filehash . ".thumb." . $this->imageFormat;
        $thumbnailImageURL = $this->makeURL(array("action" => "viewthumb", "id" => $map->filehash, "time" => time()));

        if ($map->thumb_width > 0) {
            $imgSize = sprintf(' WIDTH="%d" HEIGHT="%d" ', $map->thumb_width, $map->thumb_height);
        }
        $mapTitle = $this->getMapTitle($map);
        print '<div class="wm_thumbcontainer" style="margin: 2px; border: 1px solid #bbbbbb; padding: 2px; float:left;">';
        if (file_exists($thumbnailFilename)) {
            print '<div class="wm_thumbtitle" style="font-size: 1.2em; font-weight: bold; text-align: center">' . $mapTitle;

            print '</div><a href="' . $this->makeURL(array("action" => "viewmap", "id" => $map->filehash));
            print '"><img class="wm_thumb" ' . $imgSize . 'src="' . $thumbnailImageURL . '" alt="' . $mapTitle;
            print '" border="0" hspace="5" vspace="5" title="' . $mapTitle . '"/></a>';
        } else {
            print "(thumbnail for map not created yet)";
        }

        print '</div> ';
    }

    private function drawOneFullMap($map, $includeHeaderBar = true, $includeAdminLinks = true)
    {
        $htmlFileName = $this->outputDirectory . DIRECTORY_SEPARATOR . $map->filehash . ".html";
        $mapTitle = $this->getMapTitle($map);
        print '<div class="weathermapholder" id="mapholder_' . $map->filehash . '">';

        \html_graph_start_box(1, true);

        if ($includeHeaderBar) {
            $color = $this->colours['header_panel'];
            print "<tr bgcolor=\"$color\"><td><table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\"><tr><td class=\"textHeader\" nowrap>$mapTitle";

            if ($includeAdminLinks && $this->isWeathermapAdmin()) {

                $editURL = $this->makeURL(array("action" => "nothing", "mapname" => $map->configfile),
                    $this->editorURL);
                $permURL = $this->makeURL(array("action" => "perms_edit", "id" => $map->id), $this->managementURL);
                $settingsURL = $this->makeURL(array("action" => "map_settings", "id" => $map->id),
                    $this->managementURL);

                print "<span style='font-size: 80%'>";
                print "[ <a href='$settingsURL'>Map Settings</a> |";
                print "<a href='$permURL'>Map Permissions</a> |";
                print "<a href='$editURL'>Edit Map</a> ]";
                print "</span>";
            }

            print "</td></tr></table></td></tr>";
        }

        print "<tr><td>";

        if (file_exists($htmlFileName)) {
            readfile($htmlFileName);
        } else {
            print "<div align=\"center\" style=\"padding:20px\"><em>This map hasn't been created yet.</em></div>";
        }

        print '</td></tr>';
        \html_end_box();

        print '</div>';
    }

    private function getMapTitle($map)
    {
        $mapTitle = $map->titlecache;
        if ($mapTitle == '') {
            $mapTitle = "Map for config file: " . $map->configfile;
        }
        return $mapTitle;
    }

    /**
     * @param $mapList
     */
    protected function outputMapHeader($mapList, $cycle, $limitToGroup)
    {
        $pageTitle = __n("Network Weathermap", "Network Weathermaps", count($mapList));

        $this->outputMapViewHeader($pageTitle, $cycle, $limitToGroup);
    }

    /**
     * @param $filehash
     */
    private function outputMapHTML($filehash)
    {
        $mapId = $this->manager->translateFileHash($filehash);
        $userId = $this->manager->application->getCurrentUserId();

        $map = $this->manager->getMapWithAccess($mapId, $userId);

        header('Content-type: text/html');
        if (null === $map) {
            // in the management view, a disabled map will fail the query above, so generate *something*
            print "--";
            return;
        }

        $htmlFileName = $this->outputDirectory . '/' . $filehash . ".html";

        if (file_exists($htmlFileName)) {
            readfile($htmlFileName);
            return;
        }
        print "--";
        return;
    }

    /**
     * @param $filehash
     * @param $fileNameInsert
     */
    private function outputMapImage($filehash, $fileNameInsert)
    {
        $mapId = $this->manager->translateFileHash($filehash);
        $userId = $this->manager->application->getCurrentUserId();

        $map = $this->manager->getMapWithAccess($mapId, $userId);

        header('Content-type: image/png');

        if (null === $map) {
            // in the management view, a disabled map will fail the query above, so generate *something*
            header('Content-type: image/png');
            $this->outputGreyPNG(48, 48);
        }

        $imageFileName = $this->outputDirectory . '/' . $filehash . $fileNameInsert . $this->imageFormat;

        header('Content-type: image/png');

        if (file_exists($imageFileName)) {
            readfile($imageFileName);
            return;
        }

        $this->outputGreyPNG(48, 48);
    }

    private function outputGreyPNG($width, $height)
    {
        $imageRef = imagecreate($width, $height);
        $shade = 240;
        // The first colour allocated becomes the background colour of the image. No need to fill
        imagecolorallocate($imageRef, $shade, $shade, $shade);
        imagepng($imageRef);
    }

    public function cactiEnableGraphRefresh()
    {
        $_SESSION['custom'] = false;
    }

    public function cactiGraphHeader()
    {
        print "OVERRIDE ME";
    }

    public function cactiFooter()
    {
        print "OVERRIDE ME";
    }

    public function cactiHeader()
    {
        print "OVERRIDE ME";
    }

    public function cactiRowStart($i)
    {
    }

    private function outputCycleControls($controlMode, $groupFilter, $extraText)
    {
        ?>
        <div id="wmcyclecontrolbox" class="<?php print $controlMode ?>">
            <div id="wm_progress"></div>
            <div id="wm_cyclecontrols">
                <a id="cycle_stop" href="?action="><img src="cacti-resources/img/control_stop_blue.png" width="16"
                                                        height="16"/></a>
                <a id="cycle_prev" href="#"><img src="cacti-resources/img/control_rewind_blue.png" width="16"
                                                 height="16"/></a>
                <a id="cycle_pause" href="#"><img src="cacti-resources/img/control_pause_blue.png" width="16"
                                                  height="16"/></a>
                <a id="cycle_next" href="#"><img src="cacti-resources/img/control_fastforward_blue.png" width="16"
                                                 height="16"/></a>
                <a id="cycle_fullscreen"
                   href="?action=viewcycle&fullscreen=1&group=<?php echo($groupFilter === null ? -1 : $groupFilter); ?>"><img
                        src="cacti-resources/img/arrow_out.png" width="16" height="16"/></a>
                Showing <span id="wm_current_map">1</span> of <span id="wm_total_map">1</span>.
                Cycling all available maps<?php echo $extraText; ?>.
            </div>
        </div>
        <?php
    }

    private function outputCycleCode($fullscreen)
    {
        $refreshtime = $this->manager->application->getAppSetting("weathermap_cycle_refresh", 10);
        $poller_cycle = $this->manager->application->getAppSetting("poller_interval", 300);
        ?>
        <script src='vendor/jquery/dist/jquery.min.js'></script>
        <script src='vendor/jquery-idletimer/dist/idle-timer.min.js'></script>
        <script type="text/javascript" src="cacti-resources/map-cycle.js"></script>
        <script type="text/javascript">
            $(document).ready(function () {
                WMcycler.start({
                    fullscreen: <?php echo($fullscreen ? "1" : "0"); ?>,
                    poller_cycle: <?php echo $poller_cycle * 1000; ?>,
                    period: <?php echo $refreshtime * 1000; ?>});
            });
        </script>
        <?php
    }
}
