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
        'maplist' => array('handler' => 'handleMapListAPI', 'args' => array()),
        'settings' => array('handler' => 'handleSettingsAPI', 'args' => array()),

        'viewthumb' => array(
            'handler' => 'handleBigThumb',
            'args' => array(array("id", "maphash"), array("time", "int", true))
        ),
        'viewthumb48' => array('handler' => 'handleLittleThumb', 'args' => array(array("id", "maphash"))),
        'viewimage' => array('handler' => 'handleImage', 'args' => array(array("id", "maphash"))),

        'viewmap' => array(
            'handler' => 'handleViewMap',
            'args' => array(array("id", "maphash"), array("group_id", "int", true))
        ),

        'viewcycle_fullscreen' => array(
            'handler' => 'handleViewCycleFullscreen',
            'args' => array(array("id", "maphash"))
        ),
        'viewcycle_filtered_fullscreen' => array(
            'handler' => 'handleViewCycleFilteredFullscreen',
            'args' => array(array("id", "maphash"), array("group_id", "int", true))
        ),

        'viewcycle' => array('handler' => 'handleViewCycle', 'args' => array()),
        'viewcycle_filtered' => array(
            'handler' => 'handleViewCycleFiltered',
            'args' => array(array("group_id", "int", true))
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

        if ($this->validateRequest($action, $request)) {
            $result = $this->dispatchRequest($action, $request, null);
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


    public function handleDefaultView($request, $appObject)
    {
        global $wm_showOldUI, $config;

        $weathermapPath = $config['url_path'] . 'plugins/weathermap/';
        $cactiResourcePath = $weathermapPath . 'cacti-resources/';

        $this->cactiHeader();

        if ($wm_showOldUI) {

            print "This will all be replaced.";

            $pageStyle = $this->manager->application->getAppSetting("weathermap_pagestyle", 0);
            $userId = $this->manager->application->getCurrentUserId();
            $limitToGroup = $this->getGroupFilter($request);

            $mapList = $this->manager->getMapsForUser($userId, $limitToGroup);

            // "First-only" style
            if ($pageStyle == 2) {
                $mapList = array($mapList[0]);
            }
            $mapCount = count($mapList);

            $this->outputMapHeader($mapList, false, $limitToGroup);

            if ($pageStyle == 0 && $mapCount > 1) {
                $this->drawThumbnailView($mapList);
            } else {
                $this->drawFullMapView($mapList);
            }

            if ($mapCount == 0) {
                print "<div align=\"center\" style=\"padding:20px\"><em>You Have No Maps</em></div>\n";
            }
        }

        // get the locale from host app
        $locale = $this->manager->application->getLocale();

        print "<small>This is the React UI below here</small>";
        print "<h1>INCOMPLETE</h1>";
        print '<style>@import "' . $cactiResourcePath . 'user/main.css"</style>';
        print '<script type="text/javascript" src="' . $weathermapPath . 'overlib.js"></script>';

        print "<div id='weathermap-user-root' data-locale='" . $locale . "' data-url='" . $this->makeURL(array("action" => "settings")) . "'></div>";
        print '<script type="text/javascript" src="' . $cactiResourcePath . 'user/main.js"></script>';

        print "<hr />";

        if ($wm_showOldUI) {
            $this->outputVersionBox();
        }

        $this->cactiFooter();
    }


    // ***********************************************************************************************
    // Below here are the old server-side functions that are replaced by client components


    public function handleViewMap($request, $appObject)
    {
        print "Unimplemented map view";
    }

    public function handleViewCycleFullscreen($request, $appObject)
    {
        print "Unimplemented handleViewCycleFullscreen";
    }

    public function handleViewCycleFilteredFullscreen($request, $appObject)
    {
        print "Unimplemented handleViewCycleFilteredFullscreen";
    }

    public function handleViewCycle($request, $appObject)
    {
        print "Unimplemented handleViewCycle";
    }

    public function handleViewCycleFiltered($request, $appObject)
    {
        print "Unimplemented handleViewCycleFiltered";
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


        \html_start_box('Weathermap Info', '100%', '', '3', 'center', '');
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
        \html_end_box();
    }

    protected function outputMapViewHeader($pageTitle, $isCycling, $limitingToGroup)
    {
        \html_start_box($pageTitle, '100%', '', '3', 'center', '');
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
                                    $this->makeURL(
                                        array(
                                            "action" => "viewcycle_filtered",
                                            "group" => $limitingToGroup
                                        )
                                    );
                                    print '<a href = "' . $this->makeURL(
                                            array(
                                                "action" => "viewcycle_filtered",
                                                "group" => $limitingToGroup
                                            )
                                        ) . '">within this group</a>, or ';
                                }
                                print ' <a href = "' . $this->makeURL(array("action" => "viewcycle")) . '">all maps</a>';
                                ?>)
                                <?php
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <?php
        \html_end_box();

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

    private function drawOneFullMap($map)
    {
        $htmlFileName = $this->outputDirectory . DIRECTORY_SEPARATOR . $map->filehash . ".html";
        $mapTitle = $this->getMapTitle($map);
        print '<div class="weathermapholder" id="mapholder_' . $map->filehash . '">';

        \html_start_box(__($mapTitle), '100%', '', '3', 'center', '');

        ?>
        <tr class="even">
            <td colspan="3">
                <table width="100%" cellspacing="0" cellpadding="3" border="0">
                    <tr>
                        <td align="left" class="textHeaderDark">
                            <a name="map_<?php echo $map->filehash; ?>">
                            </a><?php print htmlspecialchars($mapTitle); ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
        <?php

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
}
