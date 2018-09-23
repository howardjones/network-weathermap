<?php
// PHP Weathermap 0.97b
// Copyright Howard Jones, 2005-2012 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License
namespace Weathermap\Editor;

use Weathermap\Core\Map;
use Weathermap\Core\MapNode;
use Weathermap\Core\MapLink;
use Weathermap\Core\Rectangle;
use Weathermap\Core\WeathermapInternalFail;
use Weathermap\Core\Point;
use Weathermap\Core\MathUtility;
use Weathermap\Core\Target;
use Weathermap\Core\StringUtility;
use Weathermap\UI\UIBase;

/** Wrapper API around Map to provide the relevant operations to manipulate
 *  the map contents that an editor will need, without it needing to see inside the map object.
 *  (a second class, EditorUI, is concerned with the actual presentation of the supplied editor)
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
        if ($itemType == 'node' && $this->map->nodeExists($itemName)) {
            $node = $this->map->getNode($itemName);
            return $node->getConfig();
        }

        if ($itemType == 'link' && $this->map->linkExists($itemName)) {
            $link = $this->map->getlink($itemName);
            return $link->getConfig();
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
            if (!$link->isTemplate()
                && count($link->viaList) > 0
                && (
                    ($link->endpoints[0]->node->name == $nodeName)
                    || ($link->endpoints[1]->node->name == $nodeName)
                )
            ) {
                $affectedLinks[] = $link->name;
                $pivot = null;

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

        // TODO: need to redraw image here, to get correct imagemap?
        // TODO: Also, recalculate any relative positioned nodes? (doesn't matter with current editor, but isn't intuitive)

        return array($nNodes, $nLinks, $affectedNodes, $affectedLinks);
    }

    public function updateNode($nodeName, $params)
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        if ($this->map->nodeExists($nodeName)) {
            // first check if there's a rename...
            if ($nodeName != $params['new_name']) {
                $nodeName = $this->renameNode($nodeName, $params['new_name']);
            }

            $node = $this->map->getNode($nodeName);

            $node->setPosition(new Point($params['x'], $params['y']));
            $node->label = $params['label'];

            // AICONs mess this up, because they're not fully supported by the editor, but it can still break them
            if ($params['iconfilename'] != '--AICON--') {
                $node->iconfile = stripslashes($params['iconfilename']);
            }

            $node->infourl[IN] = $params['infourl'];
            $urls = preg_split('/\s+/', $params['hover'], -1, PREG_SPLIT_NO_EMPTY);
            $node->overliburl[IN] = $urls;
            $node->overliburl[OUT] = $urls;

            if ($params['lock_to'] == "") {
                $node->positionRelativeTo = "";
            } else {
                if ($this->map->nodeExists($params['lock_to'])) {
                    $anchor = $this->map->getNode($params['lock_to']);

                    $node->positionRelativeTo = $anchor->name;
                    $node->originalX = $node->x - $anchor->x;
                    $node->originalY = $node->y - $anchor->y;
                }
            }
        }
    }

    public function renameNode($oldName, $newName)
    {
        if (!$this->map->nodeExists($oldName)) {
            return $oldName;
        }

        if ($this->map->nodeExists($newName)) {
            return $oldName;
        }

        // we need to rename the node first.
        $newNode = $this->map->getNode($oldName);
        $newNode->name = $newName;

        $this->map->nodes[$newName] = $newNode;
        unset($this->map->nodes[$oldName]);

        // find the references elsewhere to the old node name.
        // First, relatively-positioned NODEs
        foreach ($this->map->nodes as $movingNode) {
            if ($movingNode->positionRelativeTo == $oldName) {
                $movingNode->positionRelativeTo = $newName;
            }
        }

        // Next, LINKs that use this NODE as an end.
        foreach ($this->map->links as $link) {
            if (!$link->isTemplate()) {
                if ($link->endpoints[0]->node->name == $oldName) {
                    print "End[0] matches $oldName";
                    $link->endpoints[0]->node = $newNode;
                }
                if ($link->endpoints[1]->node->name == $oldName) {
                    print "End[1] matches $oldName";
                    $link->endpoints[1]->node = $newNode;
                }
                // while we're here, VIAs can also be relative to a NODE,
                // so check if any of those need to change
                $n = 0;
                foreach ($link->viaList as $via) {
                    if (isset($via[2]) && $via[2] == $oldName) {
                        $link->viaList[$n][2] = $newName;
                    }
                    $n++;
                }
            }
        }

        return $newName;
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

    public function updateLink($linkName, $params)
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        if ($this->map->linkExists($linkName)) {
            $link = $this->map->getLink($linkName);

            // Now deal with params

            $link->infourl[IN] = $params['infourl'];
            $link->infourl[OUT] = $params['infourl'];
            $urls = preg_split('/\s+/', $params['hover'], -1, PREG_SPLIT_NO_EMPTY);
            $link->overliburl[IN] = $urls;
            $link->overliburl[OUT] = $urls;

            $link->commentOffsets[IN] = intval($params['commentpos_in']);
            $link->commentOffsets[OUT] = intval($params['commentpos_out']);

            $link->comments[IN] = $params['comment_in'];
            $link->comments[OUT] = $params['comment_out'];


            $link->maxValuesConfigured[IN] = $params['bandwidth_in'];
            $link->maxValuesConfigured[OUT] = $params['bandwidth_out'];
            $link->maxValues[IN] = StringUtility::interpretNumberWithMetricSuffixOrNull(
                $params['bandwidth_in'],
                $this->map->kilo
            );
            $link->maxValues[OUT] = StringUtility::interpretNumberWithMetricSuffixOrNull(
                $params['bandwidth_out'],
                $this->map->kilo
            );

            $targets = preg_split('/\s+/', $params['target'], -1, PREG_SPLIT_NO_EMPTY);
            $newTargetList = array();

            foreach ($targets as $target) {
                $newTargetList[] = new Target($target, "", 0);
            }
            $link->targets = $newTargetList;

            $link->width = floatval($params['width']);
        }
    }

    public function deleteLink($linkName)
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        if ($this->map->linkExists($linkName)) {
            unset($this->map->links[$linkName]);
            return true;
        }
        return false;
    }

    public function renameLink($oldName, $newName)
    {
        if (!$this->map->linkExists($oldName)) {
            return $oldName;
        }

        if ($this->map->linkExists($newName)) {
            return $oldName;
        }

        // we need to rename the link first.
        $newLink = $this->map->getLink($oldName);
        $newLink->name = $newName;

        $this->map->links[$newName] = $newLink;
        unset($this->map->links[$oldName]);

        return $newName;
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

        if ($this->map->linkExists($linkName)) {
            $link = $this->map->getLink($linkName);
            $link->viaList = array(array(0 => $x, 1 => $y));

            return true;
        }
        return false;
    }

    public function clearLinkVias($linkName)
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        if ($this->map->linkExists($linkName)) {
            $link = $this->map->getLink($linkName);
            $link->viaList = array();

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
    private function tidyOneLink($link, $linknumber = 1, $linktotal = 1)
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
            return array($aOffset, $bOffset);
        }

        // [B] [A]
        if ($boundingBoxB[$simpleIndex + 2] < $boundingBoxA[$simpleIndex]) {
            $aOffset = $boundingBoxA[$simpleIndex] - $nodeA->$simpleCoordinate;
            $bOffset = $boundingBoxB[$simpleIndex + 2] - $nodeB->$simpleCoordinate;
            return array($aOffset, $bOffset);
        }
        return array(0, 0);
    }

    /**
     * @param $boundingBoxA
     * @param $boundingBoxB
     * @param $nodeA
     * @param $nodeB
     * @param $complexIndex
     * @param $complexCoordinate
     * @param $linkIndex
     * @param $linkCount
     * @return array
     */
    private function tidyComplexDimension(
        $boundingBoxA,
        $boundingBoxB,
        $nodeA,
        $nodeB,
        $complexIndex,
        $complexCoordinate,
        $linkIndex,
        $linkCount
    ) {
        // find the overlapping span for the 'hard' coordinate, then divide it into $linkTotal equal steps
        // this should be true whichever way around they are
        list($minimumOverlap, $maximumOverlap) = $this->findCommonRange(
            array(
                $boundingBoxA[$complexIndex],
                $boundingBoxA[$complexIndex + 2]
            ),
            array(
                $boundingBoxB[$complexIndex],
                $boundingBoxB[$complexIndex + 2]
            )
        );
        $overlap = $maximumOverlap - $minimumOverlap;
        $stepPerLink = $overlap / ($linkCount + 1);

        $aOffset = $minimumOverlap + ($linkIndex * $stepPerLink) - $nodeA->$complexCoordinate;
        $bOffset = $minimumOverlap + ($linkIndex * $stepPerLink) - $nodeB->$complexCoordinate;

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
        // $this->map->drawMap('null');

        $routes = array();

        // build a list of non-template links with their route - a simple key that we can use to tell if two
        // links go between the same nodes
        // not very efficient, but it saves looking for special cases (a->b & b->a together)
        foreach ($this->map->links as $link) {
            if (!$link->isTemplate()) {
                $route = $this->makeRouteKey($link);
                if (($ignoreTidied || $link->getHint("_tidied") == 1)) {
                    $routes[$route][] = $link;
                }
            }
        }

        foreach ($routes as $route => $linkList) {
            $this->tidyParallelLinks($linkList);
        }
    }

    /**
     * @param MapLink $link
     * @return string
     */
    private function makeRouteKey($link)
    {
        $route = $link->endpoints[0]->node->name . " " . $link->endpoints[1]->node->name;
        if (strcmp($link->endpoints[0]->node->name, $link->endpoints[1]->node->name) > 0) {
            $route = $link->endpoints[1]->node->name . " " . $link->endpoints[0]->node->name;
            return $route;
        }
        return $route;
    }

    /**
     * tidyParallelLinks - for a group of links between the same two nodes, distribute them
     * nicely.
     *
     * @param MapLink[] $links - the links to treat as a group
     *
     */
    public function tidyParallelLinks($links)
    {
        $nTargets = count($links);

        $i = 1;
        foreach ($links as $link) {
            $this->tidyOneLink($link, $i, $nTargets);
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
        $this->doRetidyLinks(true);
    }

    public function retidyLinks()
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        // draw a map and throw it away, to calculate all the bounding boxes
        $this->map->drawMap('null');
        $this->doRetidyLinks(false);
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

        $this->map->legends[$scaleName]->setPosition(new Point($x, $y));
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

    public function updateMapStyle($params)
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        $this->map->htmlstyle = $params['htmlstyle'];
        $this->map->keyfont = intval($params['legendfont']);

        $inheritables = array(
            array('link', 'labelStyle', 'bwlabels', ""),
            array('link', 'bwfont', 'linkfont', "int"),
            array('link', 'arrowStyle', 'arrowstyle', ""),
            array('node', 'labelfont', 'nodefont', "int")
        );
        $this->handleInheritance($inheritables, $params);
    }


    public function updateMapProperties($params)
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        $this->map->title = $params['title'];
        $this->map->legends['DEFAULT']->keytitle = $params['legend'];
        $this->map->stamptext = $params['stamp'];

        $this->map->htmloutputfile = $params['htmlfile'];
        $this->map->imageoutputfile = $params['pngfile'];

        $this->map->width = $params['width'];
        $this->map->height = $params['height'];

        $this->map->background = $params['bgfile'];


        $inheritables = array(
            array('link', 'width', 'linkdefaultwidth', "float"),
        );

        $this->handleInheritance($inheritables, $params);

        $defaultLink = $this->map->getLink("DEFAULT");

        $defaultLink->width = $params['linkdefaultwidth'];
        $defaultLink->addNote("my_width", $params['linkdefaultwidth']);


        $bwIn = $params['linkdefaultbwin'];
        $bwOut = $params['linkdefaultbwout'];

        $bwInOld = $defaultLink->maxValuesConfigured[IN];
        $bwOutOld = $defaultLink->maxValuesConfigured[OUT];

        // TODO - there are two methods doing this job in UIBase
        if (!UIBase::wmeValidateBandwidth($bwOut)) {
            $bwOut = $bwOutOld;
        }

        if (!UIBase::wmeValidateBandwidth($bwIn)) {
            $bwIn = $bwInOld;
        }

        if (($bwInOld != $bwIn) || ($bwOutOld != $bwOut)) {
            $defaultLink->maxValuesConfigured[IN] = $bwIn;
            $defaultLink->maxValuesConfigured[OUT] = $bwOut;
            $defaultLink->maxValues[IN] = StringUtility::interpretNumberWithMetricSuffix($bwIn, $this->map->kilo);
            $defaultLink->maxValues[OUT] = StringUtility::interpretNumberWithMetricSuffix($bwOut, $this->map->kilo);
        }

        foreach ($this->map->links as $link) {
            if (($link->maxValuesConfigured[IN] == $bwInOld) || ($link->maxValuesConfigured[OUT] == $bwOutOld)) {
                $link->maxValuesConfigured[IN] = $bwIn;
                $link->maxValuesConfigured[OUT] = $bwOut;
                $link->maxValues[IN] = StringUtility::interpretNumberWithMetricSuffix($bwIn, $this->map->kilo);
                $link->maxValues[OUT] = StringUtility::interpretNumberWithMetricSuffix($bwOut, $this->map->kilo);
            }
        }
    }

    public function asJS()
    {
        if (!$this->isLoaded()) {
            throw new WeathermapInternalFail("Map must be loaded before editing API called.");
        }

        throw new WeathermapInternalFail("unimplemented");
    }

    /**
     * Find all the items where a parameter matches the CURRENT default node/link
     * and change those to match the NEW default node/link, so that the default
     * settings in the mapstyle page act intuitively.
     *
     * @param $inheritables
     * @param $params
     */
    private function handleInheritance($inheritables, $params)
    {
        $defaultLink = $this->map->getLink("DEFAULT");
        $defaultNode = $this->map->getNode("DEFAULT");

        foreach ($inheritables as $inheritable) {
            $propertyName = $inheritable[1];
            $parameterName = $inheritable[2];
            $validationType = $inheritable[3];

            $new = $params[$parameterName];
            if ($validationType != "") {
                switch ($validationType) {
                    case "int":
                        $new = intval($new);
                        break;
                    case "float":
                        $new = floatval($new);
                        break;
                }
            }

            $default = null;
            $itemList = array();

            if ($inheritable[0] == 'node') {
                $itemList = $this->map->nodes;
                $default = $defaultNode;
            }
            if ($inheritable[0] == 'link') {
                $itemList = $this->map->links;
                $default = $defaultLink;
            }
            $old = $default->$propertyName;

            if ($old != $new) {
                $default->$propertyName = $new;
                foreach ($itemList as $item) {
                    if ($item->name != ":: DEFAULT ::" && $item->$propertyName == $old) {
                        $item->$propertyName = $new;
                    }
                }
            }
        }
    }
}
