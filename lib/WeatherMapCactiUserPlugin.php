<?php

require_once "database.php";
require_once "Weathermap.class.php";
require_once "WeatherMap.functions.php";
require_once "WeatherMapUIBase.class.php";
include_once 'WeathermapManager.class.php';

class WeatherMapCactiUserPlugin extends WeatherMapUIBase
{
    public $manager;
    public $my_url;
    public $editor_url;
    public $management_url;
    private $outputDirectory;
    private $imageFormat;
    public $cacti_config;
    public $configPath;
    public $management_realm;
    public $editor_realm;

    public $commands = array(
        'maplist' => array('handler' => 'handleMapList', 'args' => array()),
        'settings' => array('handler' => 'handleSettings', 'args' => array()),

        'viewthumb' => array('handler' => 'handleBigThumb', 'args' => array(array("id", "maphash"), array("time", "int", true))),
        'viewthumb48' => array('handler' => 'handleLittleThumb', 'args' => array(array("id", "maphash"))),
        'viewimage' => array('handler' => 'handleImage', 'args' => array(array("id", "maphash"))),

        'viewmap' => array('handler' => 'handleViewMap', 'args' => array(array("id", "maphash"), array("group_id", "int", true))),

        'viewcycle_fullscreen' => array('handler' => 'handleViewCycleFullscreen', 'args' => array(array("id", "maphash"))),
        'viewcycle_filtered_fullscreen' => array('handler' => 'handleViewCycleFilteredFullscreen', 'args' => array(array("id", "maphash"), array("group_id", "int", true))),

        'viewcycle' => array('handler' => 'handleViewCycle', 'args' => array()),
        'viewcycle_filtered' => array('handler' => 'handleViewCycleFiltered', 'args' => array(array("group_id", "int", true))),
        ':: DEFAULT ::' => array(
            'handler' => 'handleDefaultView',
            'args' => array(
                array("group_id", "int", true)
            )
        )
    );

    public function __construct($config, $imageFormat)
    {
        parent::__construct();

        $this->cacti_config = $config;
        $this->my_url = "SHOULD-BE-OVERRIDDEN";
        $this->management_url = "SHOULD-BE-OVERRIDDEN";
        $this->editor_url = "SHOULD-BE-OVERRIDDEN";
        $this->management_realm = "SHOULD-BE-OVERRIDDEN";
        $this->editor_realm = "SHOULD-BE-OVERRIDDEN";
        $this->configPath = realpath(dirname(__FILE__) . '/../configs');
        $this->outputDirectory = realpath(dirname(__FILE__) . '/../output/');
        $this->imageFormat = $imageFormat;
        $this->manager = new WeathermapManager(weathermap_get_pdo(), $this->configPath);
    }

    function main($request)
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

    public function make_url($params, $alt_url = "")
    {
        $base_url = $this->my_url;
        if ($alt_url != "") {
            $base_url = $alt_url;
        }
        $url = $base_url . (strpos($this->my_url, '?') === false ? '?' : '&');

        $parts = array();
        foreach ($params as $name => $value) {
            $parts [] = urlencode($name) . "=" . urlencode($value);
        }
        $url .= join("&", $parts);

        return $url;
    }

    public function handleMapList($request, $appObject)
    {
        header('Content-type: application/json');

        $userId = $this->manager->getUserId();
        $mapList = $this->manager->getMapsForUser($userId);
        $groups = $this->manager->getGroups();

        // filter groups to only contain groups used in $mapList
        // (no leaking other groups - could be things like customer or project names)

        $seen_group = array();
        foreach ($mapList as $map) {
            $seen_group[$map->group_id] = 1;
        }
        $new_groups = array();
        foreach ($groups as $group) {
            if (array_key_exists($group->id, $seen_group)) {
                $new_groups []= $group;
            }
        }

        $data = array(
            'maps' => $mapList,
            'groups' => $new_groups
        );

        print json_encode($data);
    }

    public function handleSettings($request, $appObject)
    {
        global $WEATHERMAP_VERSION;

        header('Content-type: application/json');

        $style_text_options = array("thumbs", "full", "full-first-only");
        $true_false= array(false, true);

        $style = $this->manager->getAppSetting("weathermap_pagestyle", 0);

        $cycle_time = $this->manager->getAppSetting("weathermap_cycle_refresh", 0);
        if ($cycle_time == 0) {
            $cycle_time = 'auto';
        }

        $show_all_tab = $this->manager->getAppSetting("weathermap_all_tab", 0);
        $show_map_selector = $this->manager->getAppSetting("weathermap_map_selector", 0);

        $data = array(
            'wm_version' => $WEATHERMAP_VERSION,
            'page_style' => $style_text_options[$style],
            'cycle_time' => (string)$cycle_time,
            'show_all_tab' => $true_false[$show_all_tab],
            'map_selector' => $true_false[$show_map_selector],
            'thumb_url' => $this->make_url(array("action" => "viewthumb")),
            'image_url' => $this->make_url(array("action" => "viewimage")),
            'editor_url' => $this->editor_url,
            'docs_url' => 'docs/',
            'management_url' => $this->management_url
        );

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

    public function handleDefaultView($request, $appObject)
    {
        $this->cacti_header();

        $pageStyle = $this->manager->getAppSetting("weathermap_pagestyle", 0);
        $userId = $this->manager->getUserId();
        $limitToGroup = $this->getGroupFilter($request);

        $mapList = $this->manager->getMapsForUser($userId, $limitToGroup);

        // "First-only" style
        if ($pageStyle == 2) {
            $mapList = array($mapList[0]);
        }
        $mapCount = sizeof($mapList);

        $this->outputMapHeader($mapList, false, $limitToGroup);

        if ($pageStyle == 0 && $mapCount > 1) {
            $this->drawThumbnailView($mapList);
        } else {
            $this->drawFullMapView($mapList);
        }

        if ($mapCount == 0) {
            print "<div align=\"center\" style=\"padding:20px\"><em>You Have No Maps</em></div>\n";
        }
        $this->outputVersionBox();

        $this->cacti_footer();
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
        $tab_ids = array_keys($tabs);

        $limitToGroup = $this->getRequiredGroup($request);
        // XXX - will this ever be true?
        if (($limitToGroup == -1) && (sizeof($tab_ids) > 0)) {
            $limitToGroup = $tab_ids[0];
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
            $this->manager->setAppSetting("wm_last_group", $groupID);
            return $groupID;
        }

        return $this->manager->getAppSetting("wm_last_group", $groupID);
    }

    protected function getValidTabs()
    {
        $tabs = array();

        $maps = $this->manager->getMapsWithAccessAndGroups($this->manager->getUserId());

        foreach ($maps as $map) {
            $tabs[$map->group_id] = $map->name;
        }

        return $tabs;
    }

    protected function isWeathermapAdmin()
    {
        global $user_auth_realm_filenames;

        $realm_id = 0;
        $realm_name = $this->management_realm;

        if (isset($user_auth_realm_filenames[$realm_name])) {
            $realm_id = $user_auth_realm_filenames[$realm_name];
        }
        $userid = (isset($_SESSION["sess_user_id"]) ? intval($_SESSION["sess_user_id"]) : 1);
        $allowed = $this->manager->checkUserForRealm($userid, $realm_id);

        if ($allowed || (empty($realm_id))) {
            return true;
        }

        return false;
    }

    protected function outputVersionBox()
    {
        global $WEATHERMAP_VERSION;

        $pageFooter = "Powered by <a href=\"http://www.network-weathermap.com/?v=$WEATHERMAP_VERSION\">"
            . "PHP Weathermap version $WEATHERMAP_VERSION</a>";

        $isAdmin = $this->isWeathermapAdmin();

        if ($isAdmin) {
            $pageFooter .= ' --- <a href="' . $this->management_url . '" title="Go to the map management page">';
            $pageFooter .= 'Weathermap Management</a> | <a target="_blank" href="docs/">Local Documentation</a>';
            $pageFooter .= ' | <a target="_blank" href="' . $this->editor_url . '">Editor</a>';
        }


        html_start_box('Weathermap Info', '100%', '', '3', 'center', '');
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
        html_end_box();
    }

    protected function outputMapViewHeader($pageTitle, $isCycling, $limitingToGroup)
    {
        html_start_box($pageTitle, '100%', '', '3', 'center', '');
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
                                    $this->make_url(array("action" => "viewcycle_filtered", "group" => $limitingToGroup));
                                    print '<a href = "' . $this->make_url(array("action" => "viewcycle_filtered", "group" => $limitingToGroup)) . '">within this group</a>, or ';
                                }
                                print ' <a href = "' . $this->make_url(array("action" => "viewcycle")) . '">all maps</a>';
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
        html_end_box();

        $this->outputGroupTabs($limitingToGroup);
    }

    protected function outputGroupTabs($current_tab)
    {
        $tabs = $this->getValidTabs();

        if (sizeof($tabs) > 1) {
            /* draw the categories tabs on the top of the page */
            print "<p></p><table class='tabs' width='100%' cellspacing='0' cellpadding='3' align='center'><tr>\n";

            if (sizeof($tabs) > 0) {
                $showAll = intval($this->manager->getAppSetting("weathermap_all_tab", 0));
                if ($showAll == 1) {
                    $tabs['-2'] = "All Maps";
                }

                foreach (array_keys($tabs) as $tab_short_name) {


                    print "<td " . (($tab_short_name == $current_tab) ? "bgcolor='silver'" : "bgcolor='#DFDFDF'")
                        . " nowrap='nowrap' width='" . (strlen($tabs[$tab_short_name]) * 9) . "' align='center' class='tab'>
                    <span class='textHeader'><a
                    href='" . $this->make_url(array("group_id" => $tab_short_name)) . "'>$tabs[$tab_short_name]</a></span>
                    </td>\n
                    <td width='1'></td>\n";
                }
            }

            print "<td></td>\n</tr></table>\n";

            return true;
        }

        return false;
    }

    private function drawThumbnailView($mapList)
    {
        if (sizeof($mapList) > 0) {
            html_start_box("", '100%', '', '3', 'center', '');

            print "<tr><td class='wm_gallery'>";
            foreach ($mapList as $map) {
                $this->drawOneThumbnail($map);
            }
            print "</td></tr>";
            html_end_box();
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
        $thumbnailImageURL = $this->make_url(array("action" => "viewthumb", "id" => $map->filehash, "time" => time()));

        if ($map->thumb_width > 0) {
            $imgSize = sprintf(' WIDTH="%d" HEIGHT="%d" ', $map->thumb_width, $map->thumb_height);
        }
        $mapTitle = $this->getMapTitle($map);
        print '<div class="wm_thumbcontainer" style="margin: 2px; border: 1px solid #bbbbbb; padding: 2px; float:left;">';
        if (file_exists($thumbnailFilename)) {
            print '<div class="wm_thumbtitle" style="font-size: 1.2em; font-weight: bold; text-align: center">' . $mapTitle;

            print '</div><a href="' . $this->make_url(array("action" => "viewmap", "id" => $map->filehash));
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

        html_start_box(__('Map for config file: %s', $map->configfile), '100%', '', '3', 'center', '');

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
        html_end_box();

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
        $pageTitle = __n("Network Weathermap", "Network Weathermaps", sizeof($mapList));

        $this->outputMapViewHeader($pageTitle, $cycle, $limitToGroup);
    }

    /**
     * @param $filehash
     * @param $fileNameInsert
     */
    private function outputMapImage($filehash, $fileNameInsert)
    {
        $mapId = $this->manager->translateFileHash($filehash);
        $userId = $this->manager->getUserId();

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
        } else {
            $this->outputGreyPNG(48, 48);
        }
    }

    private function outputGreyPNG($w, $h)
    {
        $imageRef = imagecreate($w, $h);
        $shade = 240;
        // The first colour allocated becomes the background colour of the image. No need to fill
        imagecolorallocate($imageRef, $shade, $shade, $shade);
        imagepng($imageRef);
    }

    public function cactiEnableGraphRefresh()
    {
        $_SESSION['custom'] = false;
    }

    public function cacti_footer()
    {
        print "OVERRIDE ME";
    }

    public function cacti_header()
    {
        print "OVERRIDE ME";
    }

    public function cacti_row_start($i)
    {
    }
}