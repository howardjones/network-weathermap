<?php
// PHP Weathermap 0.97b
// Copyright Howard Jones, 2005-2012 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License
namespace Weathermap\Editor;
/** Wrapper API around WeatherMap to provide the relevant operations to manipulate
 *  the map contents that an editor will need, without it needing to see inside the map object.
 *  (a second class, WeatherMapEditorUI, is concerned with the actual presentation of the supplied editor)
 */
class Editor
{
    /** @var WeatherMap $map */
    var $map;
    /** @var string $mapfile */
    var $mapfile;

    function __construct()
    {
        $this->map = null;
    }

    function newConfig()
    {
        $this->map = new WeatherMap();
        $this->map->context = "editor";
        $this->mapfile = "untitled";
    }

    function loadConfig($filename)
    {
        $this->map = new WeatherMap();
        $this->map->context = 'editor';
        $this->map->ReadConfig($filename);
        $this->mapfile = $filename;
    }

    /**
     * Save the map config file.
     *
     * Optionally, save to a different file from the one loaded.
     *
     * @param string $filename
     */
    function saveConfig($filename = "")
    {
        if ($filename != "") {
            $this->mapfile = $filename;
        }
        $this->map->writeConfig($this->mapfile);
    }

    /**
     * Return the config that would have been saved. Mainly for tests.
     *
     */
    function getConfig()
    {
        return $this->map->getConfig();
    }

    function getItemConfig($item_type, $item_name)
    {
        if ($item_type == 'node') {
            if ($this->map->nodeExists($item_name)) {
                $node = $this->map->getNode($item_name);
                return $node->WriteConfig();
            }
        }

        if ($item_type == 'link') {
            if ($this->map->linkExists($item_name)) {
                $link = $this->map->getlink($item_name);
                return $link->WriteConfig();
            }
        }

        return false;
    }

    function addNode($x, $y, $nodeName = "", $template = "DEFAULT")
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        $newNodeName = null;

        // Generate a node name for ourselves if one wasn't supplied
        if ($nodeName == "") {
            $newNodeName = sprintf("node%05d", time() % 10000);
            while ($this->map->nodeExists($newNodeName)) {
                $newNodeName .= "a";
            }
        } else {
            $newNodeName = $nodeName;
        }

        // Check again - if they are specifying a name, it's possible for it to exist
        if (!$this->map->nodeExists($newNodeName)) {
            $newNode = new WeatherMapNode($newNodeName, $template, $this->map);

            $newNode->setPosition(new Point($x, $y));
            $newNode->setDefined($this->map->configfile);

            // only insert a label if there's no LABEL in the DEFAULT node.
            // otherwise, respect the template.
            $default = $this->map->getNode("DEFAULT");
            $default_default = $this->map->getNode(":: DEFAULT ::");

            if ($default->label == $default_default->label) {
                $newNode->label = "Node";
            }

            $this->map->addNode($newNode);
            $log = "added a node called $newNodeName at $x,$y to $this->mapfile";
            $success = true;
        } else {
            $log = "Requested node name already exists";
            $success = false;
        }

        return array($newNodeName, $success, $log);
    }

    function isLoaded()
    {
        return !is_null($this->map);
    }

    /**
     * moveNode - move a node, taking into account any relative nodes, and any links that
     * join to it, dealing with VIAs in an attractive way.
     *
     * @param string $nodeName
     * @param number $newX
     * @param number $newY
     * @return array
     * @throws WeathermapInternalFail
     */
    function moveNode($nodeName, $newX, $newY)
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        // if the node doesn't exist, nothing will be changing
        if (!$this->map->nodeExists($nodeName)) {
            return array(0, 0, 0, 0);
        }

        $movingNode = $this->map->getNode($nodeName);

        $affected_nodes = array();
        $affected_links = array();

        // This is a complicated bit. Find out if this node is involved in any
        // links that have VIAs. If it is, we want to rotate those VIA points
        // about the *other* node in the link
        foreach ($this->map->links as $link) {
            if ((count($link->vialist) > 0) && (($link->a->name == $nodeName) || ($link->b->name == $nodeName))) {
                $affected_links[] = $link->name;

                // get the other node from us
                if ($link->a->name == $nodeName) {
                    $pivot = $link->b;
                }

                if ($link->b->name == $nodeName) {
                    $pivot = $link->a;
                }

                // this is a weird special case, but it is possible, with link offsets
                // if the link starts and ends on this node, translate any VIAs
                if (($link->a->name == $nodeName) && ($link->b->name == $nodeName)) {
                    $dx = $link->a->x - $newX;
                    $dy = $link->a->y - $newY;

                    for ($count = 0; $count < count($link->vialist); $count++) {
                        $link->vialist[$count][0] = $link->vialist[$count][0] - $dx;
                        $link->vialist[$count][1] = $link->vialist[$count][1] - $dy;
                    }
                } else {
                    $pivotX = $pivot->x;
                    $pivotY = $pivot->y;

                    $newPoint = new Point($newX, $newY);
                    $pivotPoint = $pivot->getPosition();
                    $movingPoint = $movingNode->getPosition();

                    $oldVector = $pivotPoint->vectorToPoint($movingPoint);
                    $newVector = $pivotPoint->vectorToPoint($newPoint);

//                    $dx_old = $pivotX - $movingNode->x;
//                    $dy_old = $pivotY - $movingNode->y;
//                    $dx_new = $pivotX - $newX;
//                    $dy_new = $pivotY - $newY;
//                    $l_old = sqrt($dx_old*$dx_old + $dy_old*$dy_old);
//                    $l_new = sqrt($dx_new*$dx_new + $dy_new*$dy_new);
//
//                    $angle_old = rad2deg(atan2(-$dy_old, $dx_old));
//                    $angle_new = rad2deg(atan2(-$dy_new, $dx_new));

                    $angle_old = $oldVector->getAngle();
                    $angle_new = $newVector->getAngle();
                    $l_new = $newVector->length();
                    $l_old = $oldVector->length();

                    # $log .= "$pivx,$pivy\n$dx_old $dy_old $l_old => $angle_old\n";
                    # $log .= "$dx_new $dy_new $l_new => $angle_new\n";

                    // the geometry stuff uses a different point format, helpfully
                    $points = array();
                    foreach ($link->vialist as $via) {
                        $points[] = $via[0];
                        $points[] = $via[1];
                    }

                    $scaleFactor = $l_new / $l_old;

                    // rotate so that link is along the axis
                    rotateAboutPoint($points, $pivotX, $pivotY, deg2rad($angle_old));
                    // do the scaling in here
                    for ($count = 0; $count < (count($points) / 2); $count++) {
                        $basex = ($points[$count * 2] - $pivotX) * $scaleFactor + $pivotX;
                        $points[$count * 2] = $basex;
                    }
                    // rotate back so that link is along the new direction
                    rotateAboutPoint($points, $pivotX, $pivotY, deg2rad(-$angle_new));

                    // now put the modified points back into the vialist again
                    $viaCount = 0;
                    $count = 0;
                    foreach ($points as $p) {
                        // skip a point if it positioned relative to a node. Those shouldn't be rotated (well, IMHO)
                        if (!isset($link->vialist[$viaCount][2])) {
                            $link->vialist[$viaCount][$count] = $p;
                        }
                        $count++;
                        if ($count == 2) {
                            $count = 0;
                            $viaCount++;
                        }
                    }
                }
            }
        }

        $movingNode->x = $newX;
        $movingNode->y = $newY;

        $n_links = count($affected_links);
        $n_nodes = count($affected_nodes);

        return array($n_nodes, $n_links, $affected_nodes, $affected_links);
    }

    function updateNode($nodename)
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        throw new WeathermapInternalFail("unimplemented");
    }

    function replaceNodeConfig($nodeName, $newConfig)
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        // if the node doesn't exist, nothing will be changing
        if (!$this->map->nodeExists($nodeName)) {
            return false;
        }

        $node = $this->map->getNode($nodeName);
        $node->replaceConfig($newConfig);
        return true;
    }

    function replaceLinkConfig($linkName, $newConfig)
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        // if the link doesn't exist, nothing will be changing
        if (!$this->map->linkExists($linkName)) {
            return false;
        }

        $link = $this->map->getLink($linkName);
        $link->replaceConfig($newConfig);
        return true;
    }

    function deleteNode($nodename)
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        $log = "";
        $n_links = 0;
        $n_nodes = 0;
        $affected_nodes = array();
        $affected_links = array();

        if (isset($this->map->nodes[$nodename])) {
            $affected_nodes[] = $nodename;

            $log = "delete node " . $nodename;

            foreach ($this->map->links as $link) {
                if (isset($link->a)) {
                    if (($nodename == $link->a->name) || ($nodename == $link->b->name)) {
                        $affected_links[] = $link->name;
                        unset($this->map->links[$link->name]);
                    }
                }
            }

            unset($this->map->nodes[$nodename]);
            $n_nodes++;
        }
        // TODO - look for relative positioned nodes, and un-relative them

        $n_nodes = count($affected_nodes);
        $n_links = count($affected_links);
        return array($n_nodes, $n_links, $affected_nodes, $affected_links, $log);
    }

    function cloneNode($sourceNodeName, $targetName = "", $or_fail = false)
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        if ($this->map->nodeExists($sourceNodeName)) {
            $log = "cloned node " . $sourceNodeName;
            $sourceNode = $this->map->nodes[$sourceNodeName];

            // Try to use the requested name, if possible, and if specified
            $newNodeName = ($targetName != "" ? $targetName : $sourceNodeName);

            if ($targetName != "" && $or_fail && $this->map->nodeExists($newNodeName)) {
                return array(false, null, "Requested name already exists");
            }

            if (isset($this->map->nodes[$newNodeName])) {
                $newNodeName = $sourceNodeName;
                do {
                    $newNodeName = $newNodeName . "_copy";
                } while (isset($this->map->nodes[$newNodeName]));
            }

            $log .= " into $newNodeName";

            $newNode = new WeatherMapNode($newNodeName, $sourceNode->template, $this->map);
            $newNode->copyFrom($sourceNode);

            # CopyFrom skips this one, because it's also the function used by template inheritance
            # - but for Clone, we DO want to copy the template too
            //  $node->template = $sourceNode->template;

            $now = $newNode->getPosition();
            $now->translate(30, 30);
            $newNode->setPosition($now);
            $newNode->setDefined($this->map->configfile);

            $this->map->addNode($newNode);

            return array(true, $newNodeName, $log);
        }

        return array(false, null, "Request source does not exist");
    }

    function addLink($nodeName1, $nodeName2, $linkName = "", $template = "DEFAULT")
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        $success = false;
        $log = "";
        $newLinkName = "";

        // XXX - do we care if node1==node2?
        if ($nodeName1 != $nodeName2 && $this->map->nodeExists($nodeName1) && $this->map->nodeExists($nodeName2)) {
            $newLinkName = ($linkName != "" ? $linkName : "$nodeName1-$nodeName2");

            // make sure the link name is unique. We can have multiple links between
            // the same nodes, these days
            while (array_key_exists($newLinkName, $this->map->links)) {
                $newLinkName .= "a";
            }

            $newLink = new WeatherMapLink($newLinkName, $template, $this->map);
            $newLink->defined_in = $this->map->configfile;

            $newLink->setEndNodes($this->map->getNode($nodeName1), $this->map->getNode($nodeName2));

            $this->map->addLink($newLink);

            $success = true;
            $log = "created link $newLinkName between $nodeName1 and $nodeName2";
        }

        return array($newLinkName, $success, $log);

    }

    function updateLink()
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        throw new WeathermapInternalFail("unimplemented");
    }

    function deleteLink($linkname)
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        if (isset($this->map->links[$linkname])) {
            unset($this->map->links[$linkname]);
            return true;
        }
        return false;
    }

    /**
     * cloneLink - create a copy of an existing link
     * Not as useful as cloneNode, but still sometimes handy.
     *
     * @param string $sourcename
     * @param string $targetname
     */
    function cloneLink($sourcename, $targetname = "")
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        throw new WeathermapInternalFail("unimplemented");
    }

    /**
     * setLinkVia - simple-minded add/replacement of a single VIA for a link.
     * Should be replaced by addLinkVia with intelligent handling of multiple VIAs
     *
     * @param string $linkname
     * @param number $x
     * @param number $y
     * @return boolean - successful or not
     */
    function setLinkVia($linkname, $x, $y)
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        if (isset($this->map->links[$linkname])) {
            $this->map->links[$linkname]->vialist = array(array(0 => $x, 1 => $y));

            return true;
        }
        return false;
    }

    function clearLinkVias($linkname)
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        if (isset($this->map->links[$linkname])) {
            $this->map->links[$linkname]->vialist = array();

            return true;
        }
        return false;
    }

    function tidyLink($linkName)
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        // draw a map and throw it away, to calculate all the bounding boxes
        $this->map->drawMapImage('null');
        $link = $this->map->getLink($linkName);
        $this->tidyOneLink($link);
    }

    /**
     * tidyOneLink - change link offsets so that link is horizonal or vertical, if possible.
     *  if not possible, change offsets to the closest facing compass points
     *
     * @param WeatherMapLink $link - the link to tidy
     * @param int $linknumber - if this is part of a group, which number in the group
     * @param int $linktotal - if this is part of a group, how many total in the group
     * @param bool $ignore_tidied - whether to take notice of the "_tidied" hint
     */
    private function tidyOneLink($link, $linknumber = 1, $linktotal = 1, $ignore_tidied = false)
    {
        if ($link->isTemplate()) {
            return;
        }

        $node_a = $link->a;
        $node_b = $link->b;

        // Update TODO: if the nodes are already directly left/right or up/down, then use compass-points, not pixel offsets
        // (e.g. N90) so if the label changes, they won't need to be re-tidied

        // First bounding box in the node's boundingbox array is the icon, if there is one, or the label if not.
        $bb_a = $node_a->boundingboxes[0];
        $bb_b = $node_b->boundingboxes[0];

        // figure out if they share any x or y coordinates
        $xOverlaps = $this->rangeOverlaps(array($bb_a[0], $bb_a[2]), array($bb_b[0], $bb_b[2]));
        $yOverlaps = $this->rangeOverlaps(array($bb_a[1], $bb_a[3]), array($bb_b[1], $bb_b[3]));

        $a_x_offset = 0;
        $a_y_offset = 0;
        $b_x_offset = 0;
        $b_y_offset = 0;

        // if they are side by side, and there's some common y coords, make link horizontal
        if (!$xOverlaps && $yOverlaps) {
            list($a_x_offset, $b_x_offset) = $this->tidySimpleDimension($bb_a, $bb_b, $node_a, $node_b, 0, "x");
            list($a_y_offset, $b_y_offset) = $this->tidyComplexDimension($bb_a, $bb_b, $node_a, $node_b, 1, "y", $linknumber, $linktotal);
        }

        // if they are above and below, and there's some common x coords, make link vertical
        if (!$yOverlaps && $xOverlaps) {
            list($a_x_offset, $b_x_offset) = $this->tidyComplexDimension($bb_a, $bb_b, $node_a, $node_b, 0, "x", $linknumber, $linktotal);
            list($a_y_offset, $b_y_offset) = $this->tidySimpleDimension($bb_a, $bb_b, $node_a, $node_b, 1, "y");
        }

        if (!$xOverlaps && !$yOverlaps) {
            // TODO - Do something clever here - nearest corners, or an angled-VIA link?
        }

        // unwritten/implied - if both overlap, you're doing something weird and you're on your own

        // make the simplest possible offset string and finally, update the offsets
        $link->a_offset = $this->simplifyOffset($a_x_offset, $a_y_offset);
        $link->b_offset = $this->simplifyOffset($b_x_offset, $b_y_offset);

        // and also add a note that this link was tidied, and is eligible for automatic retidying
        $link->add_hint("_tidied", 1);
    }

    /**
     * rangeOverlaps - check if two ranges have anything in common. Used for tidy()
     *
     * @param float[] $rangeA
     * @param float[] $rangeB
     * @return bool
     */
    static function rangeOverlaps($rangeA, $rangeB)
    {
        if ($rangeA[0] > $rangeB[1]) {
            return false;
        }
        if ($rangeB[0] > $rangeA[1]) {
            return false;
        }

        return true;
    }

    /**
     * @param $bb_a
     * @param $bb_b
     * @param $node_a
     * @param $node_b
     * @param $simpleIndex
     * @param $simpleCoordinate
     * @return array
     */
    private function tidySimpleDimension($bb_a, $bb_b, $node_a, $node_b, $simpleIndex, $simpleCoordinate)
    {
        // snap the easy coord to the appropriate edge of the node
        // [A] [B]
        if ($bb_a[$simpleIndex + 2] < $bb_b[$simpleIndex]) {
            $a_x_offset = $bb_a[$simpleIndex + 2] - $node_a->$simpleCoordinate;
            $b_x_offset = $bb_b[$simpleIndex] - $node_b->$simpleCoordinate;
        }

        // [B] [A]
        if ($bb_b[$simpleIndex + 2] < $bb_a[$simpleIndex]) {
            $a_x_offset = $bb_a[$simpleIndex] - $node_a->$simpleCoordinate;
            $b_x_offset = $bb_b[$simpleIndex + 2] - $node_b->$simpleCoordinate;
            return array($a_x_offset, $b_x_offset);
        }
        return array($a_x_offset, $b_x_offset);
    }

    /**
     * @param $bb_a
     * @param $bb_b
     * @param $node_a
     * @param $node_b
     * @param $hardIndex
     * @param $hardCoordinate
     * @param $linkIndex
     * @param $linkCount
     * @return array
     */
    private function tidyComplexDimension($bb_a, $bb_b, $node_a, $node_b, $hardIndex, $hardCoordinate, $linkIndex, $linkCount)
    {
        // find the overlapping span for the 'hard' coordinate, then divide it into $linkTotal equal steps
        // this should be true whichever way around they are
        list($min_overlap, $max_overlap) = $this->findCommonRange(array($bb_a[$hardIndex], $bb_a[$hardIndex + 2]), array($bb_b[$hardIndex], $bb_b[$hardIndex + 2]));
        $overlap = $max_overlap - $min_overlap;
        $stepPerLink = $overlap / ($linkCount + 1);

        $a_y_offset = $min_overlap + ($linkIndex * $stepPerLink) - $node_a->$hardCoordinate;
        $b_y_offset = $min_overlap + ($linkIndex * $stepPerLink) - $node_b->$hardCoordinate;

        return array($a_y_offset, $b_y_offset);
    }

    /**
     * findCommonRange - find the range of numbers where two ranges overlap. Used for tidy()
     *
     * @param number[] $rangeA
     * @param number[] $rangeB
     * @return number[] list($min,$max)
     */
    static function findCommonRange($rangeA, $rangeB)
    {
        $min_overlap = max($rangeA[0], $rangeB[0]);
        $max_overlap = min($rangeA[1], $rangeB[1]);

        return array($min_overlap, $max_overlap);
    }

    /**
     * Turn the offsets produced during Tidy into simpler ones, if possible.
     * (including ':0:0' into '')
     *
     * @param int $x_offset
     * @param int $y_offset
     * @return string
     */
    static function simplifyOffset($x_offset, $y_offset)
    {
        if ($x_offset == 0 && $y_offset == 0) {
            return "";
        }

        if ($x_offset == 0) {
            if ($y_offset < 0) {
                return "N95";
            } else {
                return "S95";
            }
        }

        if ($y_offset == 0) {
            if ($x_offset < 0) {
                return "W95";
            } else {
                return "E95";
            }
        }

        return sprintf("%d:%d", $x_offset, $y_offset);
    }

    function tidyAllLinks()
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        // draw a map and throw it away, to calculate all the bounding boxes
        $this->map->drawMapImage('null');
        $this->_retidyLinks(true);
    }

    /**
     * _retidy_links - find all links that have previously been tidied (_tidied hint) and tidy them again
     * UNLESS $ignore_tidied is set, then do every single link (for editor testing)
     *
     * @param boolean $ignoreTidied
     */
    function _retidyLinks($ignoreTidied = false)
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        // draw a map and throw it away, to calculate all the bounding boxes
        $this->map->drawMapImage('null');

        $routes = array();
//        $done = array();

        // build a list of non-template links with their route - a simple key that we can use to tell if two
        // links go between the same nodes
        foreach ($this->map->links as $link) {
            if (!$link->isTemplate()) {
                $route = $this->makeRouteKey($link);
                if ((!$ignoreTidied || $link->get_hint("_tidied") == 0)) {
                    $routes[$route][] = $link;
                }
            }
        }

        foreach ($routes as $route => $linkList) {
//            if (!$linkList->isTemplate()) {
//                $route = $link->a->name . " " . $link->b->name;
//
//                if (strcmp($link->a->name, $link->b->name) > 0) {
//                    $route = $link->b->name . " " . $link->a->name;
//                }

//                if (($ignoreTidied || $linkList->get_hint("_tidied") == 1)) {
            if (sizeof($linkList) == 1) {
                $this->tidyOneLink($linkList[0]);
//                        $done[$route] = 1;
            } else {
                # handle multi-links specially...
                $this->_tidy_links($linkList);
//                        $this->_tidy_links($routes[$route]);
                // mark it so we don't do it again when the other links come by
//                        $done[$route] = 1;
//                    }
            }
        }
//        }
    }

    /**
     * @param $linkList
     * @return string
     */
    private function makeRouteKey($linkList)
    {
        $route = $linkList->a->name . " " . $linkList->b->name;
        if (strcmp($linkList->a->name, $linkList->b->name) > 0) {
            $route = $linkList->b->name . " " . $linkList->a->name;
            return $route;
        }
        return $route;
    }

    /**
     * _tidy_links - for a group of links between the same two nodes, distribute them
     * nicely.
     *
     * @param WeatherMapLink[] $links - the links to treat as a group
     * @param bool $ignore_tidied - whether to take notice of the "_tidied" hint
     *
     */
    function _tidy_links($links, $ignore_tidied = false)
    {
        // not very efficient, but it saves looking for special cases (a->b & b->a together)
        $nTargets = count($links);

        $i = 1;
        foreach ($links as $link) {
            $this->tidyOneLink($link, $i, $nTargets, $ignore_tidied);
            $i++;
        }
    }

    function retidyAllLinks()
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        // draw a map and throw it away, to calculate all the bounding boxes
        $this->map->drawMapImage('null');
        $this->_retidyLinks(false);
    }

    function retidyLinks()
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        // draw a map and throw it away, to calculate all the bounding boxes
        $this->map->drawMapImage('null');
        $this->_retidyLinks();
    }

    /**
     * untidyLinks - remove all link offsets from the map. Used mainly for testing.
     *
     */
    function untidyLinks()
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        foreach ($this->map->links as $link) {
            $link->a_offset = "C";
            $link->b_offset = "C";
        }
    }

    function placeLegend($x, $y, $scalename = "DEFAULT")
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        $this->map->scales[$scalename]->setPosition(new Point($x, $y));
    }

    function placeTitle($x, $y)
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        $this->map->titlex = $x;
        $this->map->titley = $y;
    }

    function placeTimestamp($x, $y)
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        $this->map->timex = $x;
        $this->map->timey = $y;
    }

    function asJS()
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        throw new WeathermapInternalFail("unimplemented");
    }
}
