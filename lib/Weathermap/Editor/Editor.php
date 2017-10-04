<?php
// PHP Weathermap 0.97b
// Copyright Howard Jones, 2005-2012 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License
namespace Weathermap\Editor;

use Weathermap\Core\BoundingBox;
use Weathermap\Core\Map;
use Weathermap\Core\MapNode;
use Weathermap\Core\MapLink;
use Weathermap\Core\Rectangle;
use Weathermap\Core\WeathermapInternalFail;
use Weathermap\Core\Point;
use Weathermap\Core\MathUtility;

/** Wrapper API around WeatherMap to provide the relevant operations to manipulate
 *  the map contents that an editor will need, without it needing to see inside the map object.
 *  (a second class, WeatherMapEditorUI, is concerned with the actual presentation of the supplied editor)
 */
class Editor
{
    /** @var Map $map */
    public $map;
    /** @var string $mapFileName */
    public $mapFileName;

    public function __construct()
    {
        $this->map = null;
    }

    public function newConfig()
    {
        $this->map = new Map();
        $this->map->context = "editor";
        $this->mapFileName = "untitled";
    }

    public function loadConfig($fileName)
    {
        $this->map = new Map();
        $this->map->context = 'editor';
        $this->map->readConfig($fileName);
        $this->mapFileName = $fileName;
    }

    /**
     * Save the map config file.
     *
     * Optionally, save to a different file from the one loaded.
     *
     * @param string $fileName
     */
    public function saveConfig($fileName = "")
    {
        if ($fileName != "") {
            $this->mapFileName = $fileName;
        }
        $this->map->writeConfig($this->mapFileName);
    }

    /**
     * Return the config that would have been saved. Mainly for tests.
     *
     */
    public function getConfig()
    {
        return $this->map->getConfig();
    }

    public function getItemConfig($itemType, $itemName)
    {
        if ($itemType == 'node') {
            if ($this->map->nodeExists($itemName)) {
                $node = $this->map->getNode($itemName);
                return $node->getConfig();
            }
        }

        if ($itemType == 'link') {
            if ($this->map->linkExists($itemName)) {
                $link = $this->map->getlink($itemName);
                return $link->getConfig();
            }
        }

        return false;
    }

    public function addNode($x, $y, $nodeName = "", $template = "DEFAULT")
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
            $newNode = new MapNode($newNodeName, $template, $this->map);

            $newNode->setPosition(new Point($x, $y));
            $newNode->setDefined($this->map->configfile);

            // only insert a label if there's no LABEL in the DEFAULT node.
            // otherwise, respect the template.
            $default = $this->map->getNode("DEFAULT");
            $defaultDefault = $this->map->getNode(":: DEFAULT ::");

            if ($default->label == $defaultDefault->label) {
                $newNode->label = "Node";
            }

            $this->map->addNode($newNode);
            $log = "added a node called $newNodeName at $x,$y to $this->mapFileName";
            $success = true;
        } else {
            $log = "Requested node name already exists";
            $success = false;
        }

        return array($newNodeName, $success, $log);
    }

    public function isLoaded()
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
    public function moveNode($nodeName, $newX, $newY)
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        // if the node doesn't exist, nothing will be changing
        if (!$this->map->nodeExists($nodeName)) {
            return array(0, 0, 0, 0);
        }

        $movingNode = $this->map->getNode($nodeName);

        $affectedNodes = array();
        $affectedLinks = array();

        // This is a complicated bit. Find out if this node is involved in any
        // links that have VIAs. If it is, we want to rotate those VIA points
        // about the *other* node in the link
        foreach ($this->map->links as $link) {
            if ((count($link->viaList) > 0) && (($link->endpoints[0]->node->name == $nodeName) || ($link->endpoints[1]->node->name == $nodeName))) {
                $affectedLinks[] = $link->name;

                // get the other node from us
                if ($link->endpoints[0]->node->name == $nodeName) {
                    $pivot = $link->endpoints[1]->node;
                }

                if ($link->endpoints[1]->node->name == $nodeName) {
                    $pivot = $link->endpoints[0]->node;
                }

                // this is a weird special case, but it is possible, with link offsets
                // if the link starts and ends on this node, translate any VIAs
                if (($link->endpoints[0]->node->name == $nodeName) && ($link->endpoints[1]->node->name == $nodeName)) {
                    $dx = $link->endpoints[0]->node->x - $newX;
                    $dy = $link->endpoints[0]->node->y - $newY;

                    for ($count = 0; $count < count($link->viaList); $count++) {
                        $link->viaList[$count][0] = $link->viaList[$count][0] - $dx;
                        $link->viaList[$count][1] = $link->viaList[$count][1] - $dy;
                    }
                } else {
                    $pivotX = $pivot->x;
                    $pivotY = $pivot->y;

                    $newPoint = new Point($newX, $newY);
                    /** @var Point $pivotPoint */
                    $pivotPoint = $pivot->getPosition();
                    $movingPoint = $movingNode->getPosition();

                    $oldVector = $pivotPoint->vectorToPoint($movingPoint);
                    $newVector = $pivotPoint->vectorToPoint($newPoint);

                    $oldAngle = $oldVector->getAngle();
                    $newAngle = $newVector->getAngle();
                    $oldLength = $oldVector->length();
                    $newLength = $newVector->length();

                    // the geometry stuff uses a different point format, helpfully
                    $points = array();
                    foreach ($link->viaList as $via) {
                        $points[] = $via[0];
                        $points[] = $via[1];
                    }

                    $scaleFactor = $newLength / $oldLength;

                    // rotate so that link is along the axis
                    MathUtility::rotateAboutPoint($points, $pivotX, $pivotY, deg2rad($oldAngle));
                    // do the scaling in here
                    for ($count = 0; $count < (count($points) / 2); $count++) {
                        $basex = ($points[$count * 2] - $pivotX) * $scaleFactor + $pivotX;
                        $points[$count * 2] = $basex;
                    }
                    // rotate back so that link is along the new direction
                    MathUtility::rotateAboutPoint($points, $pivotX, $pivotY, deg2rad(-$newAngle));

                    // now put the modified points back into the vialist again
                    $viaCount = 0;
                    $count = 0;
                    foreach ($points as $p) {
                        // skip a point if it positioned relative to a node. Those shouldn't be rotated (well, IMHO)
                        if (!isset($link->viaList[$viaCount][2])) {
                            $link->viaList[$viaCount][$count] = $p;
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

        $nLinks = count($affectedLinks);
        $nNodes = count($affectedNodes);

        return array($nNodes, $nLinks, $affectedNodes, $affectedLinks);
    }

    public function updateNode($nodeName)
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        throw new WeathermapInternalFail("unimplemented");
    }

    public function replaceNodeConfig($nodeName, $newConfig)
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

    public function replaceLinkConfig($linkName, $newConfig)
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

    public function deleteNode($nodeName)
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        $log = "";
        $affectedNodes = array();
        $affectedLinks = array();

        if (isset($this->map->nodes[$nodeName])) {
            $affectedNodes[] = $nodeName;

            $log = "delete node " . $nodeName;

            foreach ($this->map->links as $link) {
                if (isset($link->endpoints[0]->node)) {
                    if (($nodeName == $link->endpoints[0]->node->name) || ($nodeName == $link->endpoints[1]->node->name)) {
                        $affectedLinks[] = $link->name;
                        unset($this->map->links[$link->name]);
                    }
                }
            }

            unset($this->map->nodes[$nodeName]);
        }
        // TODO - look for relative positioned nodes, and un-relative them

        $nNodes = count($affectedNodes);
        $nLinks = count($affectedLinks);
        return array($nNodes, $nLinks, $affectedNodes, $affectedLinks, $log);
    }

    public function cloneNode($sourceNodeName, $targetName = "", $orFail = false)
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        if ($this->map->nodeExists($sourceNodeName)) {
            $log = "cloned node " . $sourceNodeName;
            $sourceNode = $this->map->nodes[$sourceNodeName];

            // Try to use the requested name, if possible, and if specified
            $newNodeName = ($targetName != "" ? $targetName : $sourceNodeName);

            if ($targetName != "" && $orFail && $this->map->nodeExists($newNodeName)) {
                return array(false, null, "Requested name already exists");
            }

            if (isset($this->map->nodes[$newNodeName])) {
                $newNodeName = $sourceNodeName;
                do {
                    $newNodeName = $newNodeName . "_copy";
                } while (isset($this->map->nodes[$newNodeName]));
            }

            $log .= " into $newNodeName";

            $newNode = new MapNode($newNodeName, $sourceNode->template, $this->map);
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

    public function addLink($nodeName1, $nodeName2, $linkName = "", $template = "DEFAULT")
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

            $newLink = new MapLink($newLinkName, $template, $this->map);
            $newLink->definedIn = $this->map->configfile;

            $newLink->setEndNodes($this->map->getNode($nodeName1), $this->map->getNode($nodeName2));

            $this->map->addLink($newLink);

            $success = true;
            $log = "created link $newLinkName between $nodeName1 and $nodeName2";
        }

        return array($newLinkName, $success, $log);
    }

    public function updateLink()
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        throw new WeathermapInternalFail("unimplemented");
    }

    public function deleteLink($linkName)
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        if (isset($this->map->links[$linkName])) {
            unset($this->map->links[$linkName]);
            return true;
        }
        return false;
    }

    /**
     * cloneLink - create a copy of an existing link
     * Not as useful as cloneNode, but still sometimes handy.
     *
     * @param string $sourceName
     * @param string $targetName
     * @throws WeathermapInternalFail
     */
    public function cloneLink($sourceName, $targetName = "")
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
     * @param string $linkName
     * @param number $x
     * @param number $y
     * @return bool - successful or not
     * @throws WeathermapInternalFail
     */
    public function setLinkVia($linkName, $x, $y)
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        if (isset($this->map->links[$linkName])) {
            $this->map->links[$linkName]->viaList = array(array(0 => $x, 1 => $y));

            return true;
        }
        return false;
    }

    public function clearLinkVias($linkName)
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        if (isset($this->map->links[$linkName])) {
            $this->map->links[$linkName]->viaList = array();

            return true;
        }
        return false;
    }

    public function tidyLink($linkName)
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        // draw a map and throw it away, to calculate all the bounding boxes
        $this->map->drawMap('null');
        $link = $this->map->getLink($linkName);
        $this->tidyOneLink($link);
    }

    /**
     * tidyOneLink - change link offsets so that link is horizonal or vertical, if possible.
     *  if not possible, change offsets to the closest facing compass points
     *
     * @param MapLink $link - the link to tidy
     * @param int $linknumber - if this is part of a group, which number in the group
     * @param int $linktotal - if this is part of a group, how many total in the group
     * @param bool $ignoreTidied - whether to take notice of the "_tidied" hint
     */
    private function tidyOneLink($link, $linknumber = 1, $linktotal = 1, $ignoreTidied = false)
    {
        if ($link->isTemplate()) {
            return;
        }

        $nodeA = $link->endpoints[0]->node;
        $nodeB = $link->endpoints[1]->node;

        // Update TODO: if the nodes are already directly left/right or up/down, then use compass-points, not pixel offsets
        // (e.g. N90) so if the label changes, they won't need to be re-tidied

        // First bounding box in the node's boundingbox array is the icon, if there is one, or the label if not.
        $boundingBoxA = $nodeA->boundingboxes[0];
        $boundingBoxB = $nodeB->boundingboxes[0];

        // figure out if they share any x or y coordinates
        $xOverlaps = $this->rangeOverlaps(
            array($boundingBoxA[0], $boundingBoxA[2]),
            array($boundingBoxB[0], $boundingBoxB[2])
        );
        $yOverlaps = $this->rangeOverlaps(
            array($boundingBoxA[1], $boundingBoxA[3]),
            array($boundingBoxB[1], $boundingBoxB[3])
        );

        $aXOffset = 0;
        $aYOffset = 0;
        $bXOffset = 0;
        $bYOffset = 0;

        // if they are side by side, and there's some common y coords, make link horizontal
        if (!$xOverlaps && $yOverlaps) {
            list($aXOffset, $bXOffset) = $this->tidySimpleDimension(
                $boundingBoxA,
                $boundingBoxB,
                $nodeA,
                $nodeB,
                0,
                "x"
            );
            list($aYOffset, $bYOffset) = $this->tidyComplexDimension(
                $boundingBoxA,
                $boundingBoxB,
                $nodeA,
                $nodeB,
                1,
                "y",
                $linknumber,
                $linktotal
            );
        }

        // if they are above and below, and there's some common x coords, make link vertical
        if (!$yOverlaps && $xOverlaps) {
            list($aXOffset, $bXOffset) = $this->tidyComplexDimension(
                $boundingBoxA,
                $boundingBoxB,
                $nodeA,
                $nodeB,
                0,
                "x",
                $linknumber,
                $linktotal
            );
            list($aYOffset, $bYOffset) = $this->tidySimpleDimension(
                $boundingBoxA,
                $boundingBoxB,
                $nodeA,
                $nodeB,
                1,
                "y"
            );
        }

        if (!$xOverlaps && !$yOverlaps) {
            // TODO - Do something clever here - nearest corners, or an angled-VIA link?
        }

        // unwritten/implied - if both overlap, you're doing something weird and you're on your own

        // make the simplest possible offset string and finally, update the offsets
        $link->endpoints[0]->offset = $this->simplifyOffset($aXOffset, $aYOffset);
        $link->endpoints[1]->offset = $this->simplifyOffset($bXOffset, $bYOffset);

        // and also add a note that this link was tidied, and is eligible for automatic retidying
        $link->addHint("_tidied", 1);
    }

    /**
     * rangeOverlaps - check if two ranges have anything in common. Used for tidy()
     *
     * @param float[] $rangeA
     * @param float[] $rangeB
     * @return bool
     */
    public static function rangeOverlaps($rangeA, $rangeB)
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
     * @param Rectangle $boundingBoxA
     * @param Rectangle $boundingBoxB
     * @param MapNode $nodeA
     * @param MapNode $nodeB
     * @param $simpleIndex
     * @param string $simpleCoordinate
     * @return array
     */
    private function tidySimpleDimension($boundingBoxA, $boundingBoxB, $nodeA, $nodeB, $simpleIndex, $simpleCoordinate)
    {
        // snap the easy coord to the appropriate edge of the node
        // [A] [B]
        if ($boundingBoxA[$simpleIndex + 2] < $boundingBoxB[$simpleIndex]) {
            $aOffset = $boundingBoxA[$simpleIndex + 2] - $nodeA->$simpleCoordinate;
            $bOffset = $boundingBoxB[$simpleIndex] - $nodeB->$simpleCoordinate;
        }

        // [B] [A]
        if ($boundingBoxB[$simpleIndex + 2] < $boundingBoxA[$simpleIndex]) {
            $aOffset = $boundingBoxA[$simpleIndex] - $nodeA->$simpleCoordinate;
            $bOffset = $boundingBoxB[$simpleIndex + 2] - $nodeB->$simpleCoordinate;
            return array($aOffset, $bOffset);
        }
        return array($aOffset, $bOffset);
    }

    /**
     * @param $boundingBoxA
     * @param $boundingBoxB
     * @param $nodeA
     * @param $nodeB
     * @param $hardIndex
     * @param $hardCoordinate
     * @param $linkIndex
     * @param $linkCount
     * @return array
     */
    private function tidyComplexDimension(
        $boundingBoxA,
        $boundingBoxB,
        $nodeA,
        $nodeB,
        $hardIndex,
        $hardCoordinate,
        $linkIndex,
        $linkCount
    ) {
        // find the overlapping span for the 'hard' coordinate, then divide it into $linkTotal equal steps
        // this should be true whichever way around they are
        list($minimumOverlap, $maximumOverlap) = $this->findCommonRange(
            array(
                $boundingBoxA[$hardIndex],
                $boundingBoxA[$hardIndex + 2]
            ),
            array(
                $boundingBoxB[$hardIndex],
                $boundingBoxB[$hardIndex + 2]
            )
        );
        $overlap = $maximumOverlap - $minimumOverlap;
        $stepPerLink = $overlap / ($linkCount + 1);

        $aOffset = $minimumOverlap + ($linkIndex * $stepPerLink) - $nodeA->$hardCoordinate;
        $bOffset = $minimumOverlap + ($linkIndex * $stepPerLink) - $nodeB->$hardCoordinate;

        return array($aOffset, $bOffset);
    }

    /**
     * findCommonRange - find the range of numbers where two ranges overlap. Used for tidy()
     *
     * @param number[] $rangeA
     * @param number[] $rangeB
     * @return number[] list($min,$max)
     */
    public static function findCommonRange($rangeA, $rangeB)
    {
        $minimumOverlap = max($rangeA[0], $rangeB[0]);
        $maximumOverlap = min($rangeA[1], $rangeB[1]);

        return array($minimumOverlap, $maximumOverlap);
    }

    /**
     * Turn the offsets produced during Tidy into simpler ones, if possible.
     * (including ':0:0' into '')
     *
     * @param int $xOffset
     * @param int $yOffset
     * @return string
     */
    public static function simplifyOffset($xOffset, $yOffset)
    {
        if ($xOffset == 0 && $yOffset == 0) {
            return "";
        }

        if ($xOffset == 0) {
            if ($yOffset < 0) {
                return "N95";
            } else {
                return "S95";
            }
        }

        if ($yOffset == 0) {
            if ($xOffset < 0) {
                return "W95";
            } else {
                return "E95";
            }
        }

        return sprintf("%d:%d", $xOffset, $yOffset);
    }

    public function tidyAllLinks()
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        // draw a map and throw it away, to calculate all the bounding boxes
        $this->map->drawMap('null');
        $this->doRetidyLinks(true);
    }

    /**
     * _retidy_links - find all links that have previously been tidied (_tidied hint) and tidy them again
     * UNLESS $ignore_tidied is set, then do every single link (for editor testing)
     *
     * @param boolean $ignoreTidied
     * @throws WeathermapInternalFail
     */
    private function doRetidyLinks($ignoreTidied = false)
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        // draw a map and throw it away, to calculate all the bounding boxes
        $this->map->drawMap('null');

        $routes = array();
//        $done = array();

        // build a list of non-template links with their route - a simple key that we can use to tell if two
        // links go between the same nodes
        foreach ($this->map->links as $link) {
            if (!$link->isTemplate()) {
                $route = $this->makeRouteKey($link);
                if ((!$ignoreTidied || $link->getHint("_tidied") == 0)) {
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

//                if (($ignoreTidied || $linkList->getHint("_tidied") == 1)) {
            if (count($linkList) == 1) {
                $this->tidyOneLink($linkList[0]);
//                        $done[$route] = 1;
            } else {
                # handle multi-links specially...
                $this->doTidyLinks($linkList);
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
     * @param MapLink[] $links - the links to treat as a group
     * @param bool $ignoreTidied - whether to take notice of the "_tidied" hint
     *
     */
    public function doTidyLinks($links, $ignoreTidied = false)
    {
        // not very efficient, but it saves looking for special cases (a->b & b->a together)
        $nTargets = count($links);

        $i = 1;
        foreach ($links as $link) {
            $this->tidyOneLink($link, $i, $nTargets, $ignoreTidied);
            $i++;
        }
    }

    public function retidyAllLinks()
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        // draw a map and throw it away, to calculate all the bounding boxes
        $this->map->drawMap('null');
        $this->doRetidyLinks(false);
    }

    public function retidyLinks()
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        // draw a map and throw it away, to calculate all the bounding boxes
        $this->map->drawMap('null');
        $this->doRetidyLinks();
    }

    /**
     * untidyLinks - remove all link offsets from the map. Used mainly for testing.
     *
     */
    public function untidyLinks()
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        foreach ($this->map->links as $link) {
            $link->endpoints[0]->offset = "C";
            $link->endpoints[1]->offset = "C";
        }
    }

    public function placeLegend($x, $y, $scaleName = "DEFAULT")
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        $this->map->scales[$scaleName]->setPosition(new Point($x, $y));
    }

    public function placeTitle($x, $y)
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        $this->map->titlex = $x;
        $this->map->titley = $y;
    }

    public function placeTimestamp($x, $y)
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        $this->map->timex = $x;
        $this->map->timey = $y;
    }

    public function asJS()
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        throw new WeathermapInternalFail("unimplemented");
    }
}
