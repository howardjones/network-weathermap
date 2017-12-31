<?php

namespace Weathermap\Core;

// PHP Weathermap 0.98
// Copyright Howard Jones, 2005-2016 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License

use Weathermap\Core\HTMLImagemap;
use Weathermap\Core\PluginManager;


class Map extends MapBase
{
    /** @var MapNode[] $nodes */
    public $nodes = array();
    /** @var MapLink[] $links */
    public $links = array();

    // public $texts = array(); // an array containing all the extraneous text bits
    public $usedImages = array(); // an array of image filenames referred to (used by editor ONLY)
    public $seenZLayers = array(0 => array(), 1000 => array()); // 0 is the background, 1000 is the legends, title, etc

    public $nextAvailableID;

    public $background;
    public $kilo;
    public $width;
    public $height;
    public $htmlstyle;

    /** var HTMLImagemap $imap */
    public $imap;

    public $rrdtool;

    public $sizedebug;
    public $widthmod;
    public $debugging;
    public $keyfont;
    public $timefont;

    public $titlefont;
    public $timex;
    public $timey;

    public $keyx;
    public $keyy;

    public $titlex;
    public $titley;
    public $mintimex;
    public $maxtimex;
    public $mintimey;
    public $maxtimey;

//    public $min_ds_time;
//    public $max_ds_time;
    public $minstamptext;
    public $maxstamptext;
    public $stamptext;
    public $datestamp;
    public $title;

    public $keytext;
    public $htmloutputfile;
    public $imageoutputfile;
    public $dataoutputfile;
    public $htmlstylesheet;
    public $configfile;
    public $imagefile;

    public $imageuri;
    public $keystyle;
    public $keysize;

    public $minimumDataTime;
    public $maximumDataTime;
    public $context;

    // public $rrdtool_check;

    /** var  ImageLoader $imagecache */
    public $imagecache;
    public $selected;

    public $thumbWidth;
    public $thumbHeight;
    public $hasIncludes;
    public $hasOverlibs;
//    public $node_template_tree;
//    public $link_template_tree;
    public $dsinfocache = array();

    public $pluginManager;
    public $includedFiles = array();

    /** var Colour[] $colourtable  */
    public $colourtable = array();
    public $warncount = 0;

    /** var MapScale[] $scales */
    public $scales;
    public $fonts;

    /** var Stats $stats - a generic place to keep various statistics about the map */
    public $stats;

    public function __construct()
    {
        parent::__construct();

        $this->inheritedFieldList = array
        (
            'width' => 800,
            'height' => 600,
            'kilo' => 1000,
            'numscales' => array('DEFAULT' => 0),
            'datasourceclasses' => array(),
            'preprocessclasses' => array(),
            'postprocessclasses' => array(),
            'included_files' => array(),
            'context' => '',
            'dumpconfig' => false,
            'rrdtool_check' => '',
            'background' => '',
            'imageoutputfile' => '',
            'imageuri' => '',
            'htmloutputfile' => '',
            'dataoutputfile' => '',
            'htmlstylesheet' => '',
            'labelstyle' => 'percent', // redundant?
            'htmlstyle' => 'static',
            'keystyle' => array('DEFAULT' => 'classic'),
            'title' => 'Network Weathermap',
            'keytext' => array('DEFAULT' => 'Traffic Load'),
            'keyx' => array('DEFAULT' => -1),
            'keyy' => array('DEFAULT' => -1),
            'keyimage' => array(),
            'keysize' => array('DEFAULT' => 400),
            'stamptext' => 'Created: %b %d %Y %H:%M:%S',
            'keyfont' => 4,
            'titlefont' => 2,
            'timefont' => 2,
            'timex' => 0,
            'timey' => 0,

            'mintimex' => -10000,
            'mintimey' => -10000,
            'maxtimex' => -10000,
            'maxtimey' => -10000,
            'minstamptext' => 'Oldest Data: %b %d %Y %H:%M:%S',
            'maxstamptext' => 'Newest Data: %b %d %Y %H:%M:%S',

            'thumb_width' => 0,
            'thumb_height' => 0,
            'titlex' => -1,
            'titley' => -1,
            'cachefolder' => 'cached',
            'mapcache' => '',
            'sizedebug' => false,
            'debugging' => false,
            'widthmod' => false,
            'has_includes' => false,
            'has_overlibs' => false,
            'name' => 'MAP'
        );

//        $this->min_ds_time = null;
//        $this->max_ds_time = null;

        $this->scales = array();

        $this->colourtable = array();

        $this->configfile = '';
        $this->imagefile = '';
        $this->imageuri = '';

        $this->fonts = new FontTable();
        $this->fonts->init();

        $this->stats = new Stats();

        $this->pluginManager = new PluginManager($this);

        $this->reset();
    }


    public function myType()
    {
        return 'MAP';
    }

    public function __toString()
    {
        return 'MAP';
    }

    private function reset()
    {
        $this->imagecache = new ImageLoader();
        $this->nextAvailableID = 100;
        foreach (array_keys($this->inheritedFieldList) as $fld) {
            $this->$fld = $this->inheritedFieldList[$fld];
        }

//        $this->min_ds_time = null;
//        $this->max_ds_time = null;

        $this->nodes = array(); // an array of MapNodes
        $this->links = array(); // an array of MapLinks

        $this->createDefaultLinks();
        $this->createDefaultNodes();

//        $this->node_template_tree = array();
//        $this->link_template_tree = array();

//        $this->node_template_tree['DEFAULT'] = array();
//        $this->link_template_tree['DEFAULT'] = array();

        assert('is_object($this->nodes[":: DEFAULT ::"])');
        assert('is_object($this->links[":: DEFAULT ::"])');
        assert('is_object($this->nodes["DEFAULT"])');
        assert('is_object($this->links["DEFAULT"])');

        $this->imap = new HTMLImagemap('weathermap');

        $this->configfile = '';
        $this->imagefile = '';
        $this->imageuri = '';

        $this->pluginManager->loadAllPlugins();

        $this->scales['DEFAULT'] = new MapScale('DEFAULT', $this);
        $this->populateDefaultColours();

        MapUtility::debug("WeatherMap class Reset() complete\n");
    }

    // Simple accessors to stop the editor from reaching inside objects quite so much

    public function getRealNodes()
    {
        $nodeList = array();

        foreach ($this->nodes as $node) {
            // only show non-template nodes
            if (!$node->isTemplate()) {
                $nodeList[] = $node->name;
            }
        }
        sort($nodeList);

        return $nodeList;
    }

    public function getNode($name)
    {
        if (isset($this->nodes[$name])) {
            return $this->nodes[$name];
        }
        throw new WeathermapInternalFail('NoSuchNode');
    }

    public function addNode($newObject)
    {
        if ($this->nodeExists($newObject->name)) {
            throw new WeathermapInternalFail('NodeAlreadyExists');
        }
        $this->nodes[$newObject->name] = $newObject;
        $this->addItemToZLayer($newObject, $newObject->getZIndex());
    }

    public function getLink($name)
    {
        if (isset($this->links[$name])) {
            return $this->links[$name];
        }
        throw new WeathermapInternalFail('NoSuchLink');
    }

    public function addLink($newObject)
    {
        if ($this->linkExists($newObject->name)) {
            throw new WeathermapInternalFail('LinkAlreadyExists');
        }
        $this->links[$newObject->name] = $newObject;
        $this->addItemToZLayer($newObject, $newObject->getZIndex());
    }

    public function getScale($name)
    {
        if (isset($this->scales[$name])) {
            return $this->scales[$name];
        }
        MapUtility::warn("Scale $name doesn't exist. Returning DEFAULT");
        return $this->scales['DEFAULT'];
    }


    private function populateDefaultColours()
    {
        MapUtility::debug("Adding default map colour set.\n");
        $defaults = array(
            'KEYTEXT' => array('bottom' => -2, 'top' => -1, 'red' => 0, 'green' => 0, 'blue' => 0),
            'KEYOUTLINE' => array('bottom' => -2, 'top' => -1, 'red' => 0, 'green' => 0, 'blue' => 0),
            'KEYBG' => array('bottom' => -2, 'top' => -1, 'red' => 255, 'green' => 255, 'blue' => 255),
            'BG' => array('bottom' => -2, 'top' => -1, 'red' => 255, 'green' => 255, 'blue' => 255),
            'TITLE' => array('bottom' => -2, 'top' => -1, 'red' => 0, 'green' => 0, 'blue' => 0),
            'TIME' => array('bottom' => -2, 'top' => -1, 'red' => 0, 'green' => 0, 'blue' => 0)
        );

        foreach ($defaults as $key => $def) {
            $this->colourtable[$key] = new Colour($def['red'], $def['green'], $def['blue']);
        }
    }

    /**
     * @param string $input
     * @param MapItem|Map $context What is this in the input
     * @param bool $includeNotes Whether notes should be searched, or just hints
     * @param bool $multiline Whether to process \n
     * @return mixed|string
     */
    public function processString($input, &$context, $includeNotes = true, $multiline = false)
    {
        if ($input === '') {
            return '';
        }

        // don't bother with all this regexp rubbish if there's nothing to match
        if (false === strpos($input, '{')) {
            return $input;
        }

        $theItem = null;

        assert('is_scalar($input)');

        $contextType = $this->getProcessStringContextName($context);

        MapUtility::debug("Trace: ProcessString($input, $contextType)\n");

        if ($multiline == true) {
            $input = str_replace("\\n", "\n", $input);
        }

        $input = $this->applyProcessStringShortcuts($input, $context, $contextType);

        // check if we can now quit early before the regexp stuff
        if (false === strpos($input, '{')) {
            return $input;
        }

        $output = $input;
        while (preg_match('/(\{((?:node|map|link)[^}]+)\})/', $input, $matches)) {
            $keyContents = $matches[2];
            $key = "{" . $matches[2] . "}";

            MapUtility::debug('ProcessString: working on ' . $key . "\n");
            $value = $this->processStringToken($includeNotes, $keyContents, $key, $context);

            // We track the input and a clean output string separately, to stop people doing
            // weird things like setting variables to also include tokens
            $input = str_replace($key, '', $input);
            $output = str_replace($key, $value, $output);
        }

        return $output;
    }

    /**
     * Given a token from ProcessString(), and the context for it, figure out the actual value and format @inheritdoc
     *
     * @param $includeNotes
     * @param $keyContents
     * @param $key
     * @param $contextDescription
     * @param $value
     * @return string
     */
    private function processStringToken($includeNotes, $keyContents, $key, $context)
    {
        $value = '[UNKNOWN]';
        $format = "";
        $theItem = null;

        $contextDescription = $this->getProcessStringContextName($context);
        $parts = explode(":", $keyContents);
        $type = array_shift($parts);
        $args = join(":", $parts);

        $partCount = count($parts);

        if ($partCount > 0 && $type == 'map') {
            $theItem = $this;
            $args = $parts[0];
            $format = (isset($parts[1]) ? $parts[1] : "");
        }

        if ($partCount > 1 && (($type == 'link') || ($type == 'node'))) {
            $itemName = $parts[0];
            $args = $parts[1];
            $format = (isset($parts[2]) ? $parts[2] : "");

            $theItem = $this->processStringFindReferredObject($context, $itemName, $type);
        }

        if (is_null($theItem)) {
            MapUtility::warn("ProcessString: $key refers to unknown item (context is $contextDescription) [WMWARN05]\n");
            return $value;
        }

        MapUtility::debug("ProcessString: Found appropriate item: $theItem\n");


        $value = $this->findItemValue($theItem, $args, $value, $includeNotes);

        // format, and sanitise the value string here, before returning it
        MapUtility::debug("ProcessString: replacing %s with %s \n", $key, $value);

        if ($format != '') {
            $value = StringUtility::sprintf($format, $value, $this->kilo);
            MapUtility::debug("ProcessString: formatted $format to $value\n");
        }
        return $value;
    }

    /**
     * @param Map|MapLink|MapNode $mapItem
     * @param string $variableName
     * @param $currentValue
     * @param bool $includeNotes
     * @return mixed
     */
    private function findItemValue(&$mapItem, $variableName, $currentValue, $includeNotes = true)
    {
        // SET and notes have precedent over internal properties
        // this is my laziness - it saves me having a list of reserved words
        // which are currently used for internal props. You can just 'overwrite' any of them.
        if (array_key_exists($variableName, $mapItem->hints)) {
            MapUtility::debug("ProcessString: used hint\n");
            return $mapItem->hints[$variableName];
        }

        if ($includeNotes && array_key_exists($variableName, $mapItem->notes)) {
            // for some things, we don't want to allow notes to be considered.
            // mainly - TARGET (which can define command-lines), shouldn't be
            // able to get data from uncontrolled sources (i.e. data sources rather than SET in config files).
            MapUtility::debug("ProcessString: used note\n");
            return $mapItem->notes[$variableName];
        }

        // Previously this was directly accessing properties of map items
        try {
            $value = $mapItem->getProperty($variableName);
        } catch (WeathermapRuntimeWarning $e) {
            // give up, and pass back the current value
            return $currentValue;
        }

        MapUtility::debug("ProcessString: used internal property\n");
        return $value;
    }


    /**
     * @param $context
     * @param $itemname
     * @param $type
     * @return Map|MapDataItem
     */
    private function processStringFindReferredObject(&$context, $itemname, $type)
    {
        if (($itemname == "this") && ($type == strtolower($context->myType()))) {
            return $context;
        }

        if ($context->myType() == "LINK" && $type == 'node') {
            // this refers to the two nodes at either end of this link
            if ($itemname == '_linkstart_') {
                return $context->endpoints[0]->node;
            }

            if ($itemname == '_linkend_') {
                return $context->endpoints[1]->node;
            }
        }

        if (($itemname == "parent") && ($type == "node") && ($context->myType() == 'NODE') && ($context->isRelativePositioned())) {
            return $this->nodes[$context->getRelativeAnchor()];
        }

        if (($type == 'link') && isset($this->links[$itemname])) {
            return $this->links[$itemname];
        }

        if (($type == 'node') && isset($this->nodes[$itemname])) {
            return $this->nodes[$itemname];
        }
        return null;
    }


    /**
     * @param resource $imageRef
     * @param int $font
     * @param Colour $colour
     * @param string $which
     */
    private function drawTimestamp($imageRef, $font, $colour, $which = '')
    {
        // add a timestamp to the corner, so we can tell if it's all being updated

        $fontObject = $this->fonts->getFont($font);

        switch ($which) {
            case 'MIN':
                $stamp = strftime($this->minstamptext, $this->minimumDataTime);
                $posX = $this->mintimex;
                $posY = $this->mintimey;
                break;
            case 'MAX':
                $stamp = strftime($this->maxstamptext, $this->maximumDataTime);
                $posX = $this->maxtimex;
                $posY = $this->maxtimey;
                break;
            default:
                $stamp = $this->datestamp;
                $posX = $this->timex;
                $posY = $this->timey;
                break;
        }

        list($boxWidth, $boxHeight) = $fontObject->calculateImageStringSize($stamp);

        $x = $this->width - $boxWidth;
        $y = $boxHeight;

        if (($posX != 0) && ($posY != 0)) {
            $x = $posX;
            $y = $posY;
        }

        $fontObject->drawImageString($imageRef, $x, $y, $stamp, $colour->gdAllocate($imageRef));
        $areaName = $which . 'TIMESTAMP';
        $this->imap->addArea('Rectangle', $areaName, '', array($x, $y, $x + $boxWidth, $y - $boxHeight));
        $this->imagemapAreas[] = $areaName;
    }

    /**
     * @param resource $imageRef
     * @param int $font
     * @param Colour $colour
     */
    private function drawTitle($imageRef, $font, $colour)
    {
        $fontObject = $this->fonts->getFont($font);
        $string = $this->processString($this->title, $this);

        if ($this->getHint('screenshot_mode') == 1) {
            $string = StringUtility::stringAnonymise($string);
        }

        list($boxwidth, $boxheight) = $fontObject->calculateImageStringSize($string);

        $x = 10;
        $y = $this->titley - $boxheight;

        if (($this->titlex >= 0) && ($this->titley >= 0)) {
            $x = $this->titlex;
            $y = $this->titley;
        }

        $fontObject->drawImageString($imageRef, $x, $y, $string, $colour->gdAllocate($imageRef));

        $this->imap->addArea('Rectangle', 'TITLE', '', array($x, $y, $x + $boxwidth, $y - $boxheight));
        $this->imagemapAreas[] = 'TITLE';
    }


    /**
     * ReadConfig reads in either a file or part of a config and modifies the current map.
     *
     * @param $input string Either a filename or a fragment of config in a string
     * @return bool indicates success or failure     *
     *
     */
    public function readConfig($input)
    {
        $reader = new ConfigReader($this);

        // check if $input is more than one line. if it is, it's a text of a config file
        // if it isn't, it's the filename

        if ((strchr($input, "\n") != false) || (strchr($input, "\r") != false)) {
            MapUtility::debug("ReadConfig Detected that this is a config fragment.\n");
            // strip out any Windows line-endings that have gotten in here
            $input = str_replace("\r", '', $input);
            $lines = explode("\n", $input);
            $filename = '{text insert}';

            $reader->readConfigLines($lines);
        } else {
            MapUtility::debug("ReadConfig Detected that this is a config filename.\n");
            $reader->readConfigFile($input);
            $this->configfile = $input;
        }

        $this->postReadConfigTasks();

        return true;
    }

    private function postReadConfigTasks()
    {
        if ($this->hasOverlibs && $this->htmlstyle == 'static') {
            MapUtility::warn("OVERLIBGRAPH is used, but HTMLSTYLE is static. This is probably wrong. [WMWARN41]\n");
        }

        $this->populateDefaultScales();
        $this->replicateScaleSettings();
        $this->buildZLayers();
        $this->resolveRelativePositions();
        $this->updateMaxValues();

        $this->pluginManager->initialiseAllPlugins();
        $this->pluginManager->runProcessorPlugins('pre');
    }

    private function populateDefaultScales()
    {
        // load some default colouring, otherwise it all goes wrong

        $didPopulate = $this->scales['DEFAULT']->populateDefaultsIfNecessary();

        if ($didPopulate) {
            // we have a 0-0 line now, so we need to hide that.
            // (but respect the user's wishes if they defined a scale)
            $this->addHint('key_hidezero_DEFAULT', 1);
        }

        $this->scales['none'] = new MapScale('none', $this);
    }

    /**
     * Temporary function to bridge between the old and new
     * scale-worlds. Just until the ConfigReader updates these
     * directly.
     */
    private function replicateScaleSettings()
    {
        foreach ($this->scales as $scaleName => $scaleObject) {
            // These are currently global settings for a map, not per-scale
            $scaleObject->keyoutlinecolour = $this->colourtable['KEYOUTLINE'];
            $scaleObject->keytextcolour = $this->colourtable['KEYTEXT'];
            $scaleObject->keybgcolour = $this->colourtable['KEYBG'];
            $scaleObject->keyfont = $this->fonts->getFont($this->keyfont);

            if (isset($this->keyx[$scaleName])) {
                $scaleObject->keypos = new Point($this->keyx[$scaleName], $this->keyy[$scaleName]);
                $scaleObject->keystyle = $this->keystyle[$scaleName];
                $scaleObject->keytitle = $this->keytext[$scaleName];
                if (isset($this->keysize[$scaleName])) {
                    $scaleObject->keysize = $this->keysize[$scaleName];
                }
            }
        }
    }


    private function buildZLayers()
    {
        MapUtility::debug("Building cache of z-layers.\n");

        $allItems = $this->buildAllItemsList();

        foreach ($allItems as $item) {
            $zIndex = $item->getZIndex();
            $this->addItemToZLayer($item, $zIndex);
        }
        MapUtility::debug('Found ' . count($this->seenZLayers) . " z-layers including builtins (0,100).\n");
    }

    private function addItemToZLayer($item, $zIndex)
    {
        if (!isset($this->seenZLayers[$zIndex]) || !is_array($this->seenZLayers[$zIndex])) {
            $this->seenZLayers[$zIndex] = array();
        }
        array_push($this->seenZLayers[$zIndex], $item);
    }

    private function updateMaxValues()
    {
        MapUtility::debug("Finalising bandwidth.\n");

        $allItems = $this->buildAllItemsList();

        foreach ($allItems as $item) {
            $item->updateMaxValues($this->kilo);
        }
    }

    private function resolveRelativePositions()
    {
        // calculate any relative positions here - that way, nothing else
        // really needs to know about them

        MapUtility::debug("Resolving relative positions for NODEs...\n");
        // safety net for cyclic dependencies
        $maxIterations = 100;
        $iterations = $maxIterations;
        do {
            $nSkipped = 0;
            $nChanged = 0;

            foreach ($this->nodes as $node) {
                // if it's not relative, or already dealt with, skip to the next one
                if (!$node->isRelativePositioned() || $node->isRelativePositionResolved()) {
                    continue;
                }

                $anchorName = $node->getRelativeAnchor();

                MapUtility::debug("Resolving relative position for $node to $anchorName\n");

                if (!$this->nodeExists($anchorName)) {
                    MapUtility::warn('NODE ' . $node->name . " has a relative position to an unknown node ($anchorName)! [WMWARN10]\n");
                    continue;
                }

                $anchorNode = $this->getNode($anchorName);
                MapUtility::debug("Found anchor node: $anchorNode\n");

                // check if we are relative to another node which is in turn relative to something
                // we need to resolve that one before we can resolve this one!
                if (($anchorNode->isRelativePositioned()) && (!$anchorNode->isRelativePositionResolved())) {
                    MapUtility::debug("Skipping unresolved relative_to. Let's hope it's not a circular one\n");
                    $nSkipped++;
                    continue;
                }

                if ($node->resolveRelativePosition($anchorNode)) {
                    $nChanged++;
                }
            }
            MapUtility::debug("Relative Positions Cycle $iterations/$maxIterations - set $nChanged and Skipped $nSkipped for unresolved dependencies\n");
            $iterations--;
        } while (($nChanged > 0) && ($iterations > 0));

        if ($nSkipped > 0) {
            MapUtility::warn("There are probably Circular dependencies in relative POSITION lines for $nSkipped nodes (or $maxIterations levels of relative positioning). [WMWARN11]\n");
        }
    }


    public function writeDataFile($filename)
    {
        if ($filename == '') {
            return;
        }

        $fileHandle = fopen($filename, 'w');
        if (!$fileHandle) {
            return;
        }

        foreach ($this->nodes as $node) {
            if (!preg_match('/^::\s/', $node->name) && count($node->targets) > 0) {
                fputs(
                    $fileHandle,
                    sprintf("N_%s\t%f\t%f\r\n", $node->name, $node->absoluteUsages[IN], $node->absoluteUsages[OUT])
                );
            }
        }
        foreach ($this->links as $link) {
            if (!preg_match('/^::\s/', $link->name) && count($link->targets) > 0) {
                fputs(
                    $fileHandle,
                    sprintf("L_%s\t%f\t%f\r\n", $link->name, $link->absoluteUsages[IN], $link->absoluteUsages[OUT])
                );
            }
        }
        fclose($fileHandle);
    }

    private function getConfigForPosition($keyword, $fieldnames, $object1, $object2)
    {
        $write = false;
        $string = $keyword;

        for ($i = 0; $i < count($fieldnames); $i++) {
            $string .= ' ' . $object1->{$fieldnames[$i]};

            if ($object1->{$fieldnames[$i]} != $object2[$fieldnames[$i]]) {
                $write = true;
            }
        }
        $string .= "\n";

        if (!$write) {
            return '';
        }
        return $string;
    }


    public function getConfig()
    {
        $output = '';

        $output .= "# Automatically generated by php-weathermap v" . WEATHERMAP_VERSION . "\n\n";

        $output .= $this->fonts->getConfig();
        $output .= "\n";

        $simpleParameters = array(
            array('title', 'TITLE', self::CONFIG_TYPE_LITERAL),
            array('width', 'WIDTH', self::CONFIG_TYPE_LITERAL),
            array('height', 'HEIGHT', self::CONFIG_TYPE_LITERAL),
            array('background', 'BACKGROUND', self::CONFIG_TYPE_LITERAL),
            array('htmlstyle', 'HTMLSTYLE', self::CONFIG_TYPE_LITERAL),
            array('kilo', 'KILO', self::CONFIG_TYPE_LITERAL),
            array('keyfont', 'KEYFONT', self::CONFIG_TYPE_LITERAL),
            array('timefont', 'TIMEFONT', self::CONFIG_TYPE_LITERAL),
            array('titlefont', 'TITLEFONT', self::CONFIG_TYPE_LITERAL),
            array('htmloutputfile', 'HTMLOUTPUTFILE', self::CONFIG_TYPE_LITERAL),
            array('dataoutputfile', 'DATAOUTPUTFILE', self::CONFIG_TYPE_LITERAL),
            array('htmlstylesheet', 'HTMLSTYLESHEET', self::CONFIG_TYPE_LITERAL),
            array('imageuri', 'IMAGEURI', self::CONFIG_TYPE_LITERAL),
            array('imageoutputfile', 'IMAGEOUTPUTFILE', self::CONFIG_TYPE_LITERAL)
        );

        foreach ($simpleParameters as $param) {
            $field = $param[0];
            $keyword = $param[1];

            if ($this->$field != $this->inheritedFieldList[$field]) {
                if ($param[2] == self::CONFIG_TYPE_COLOR) {
                    $output .= "$keyword " . $this->$field->asConfig() . "\n";
                }
                if ($param[2] == self::CONFIG_TYPE_LITERAL) {
                    $output .= "$keyword " . $this->$field . "\n";
                }
            }
        }

        $output .= $this->getConfigForPosition(
            'TIMEPOS',
            array('timex', 'timey', 'stamptext'),
            $this,
            $this->inheritedFieldList
        );
        $output .= $this->getConfigForPosition(
            'MINTIMEPOS',
            array('mintimex', 'mintimey', 'minstamptext'),
            $this,
            $this->inheritedFieldList
        );
        $output .= $this->getConfigForPosition(
            'MAXTIMEPOS',
            array('maxtimex', 'maxtimey', 'maxstamptext'),
            $this,
            $this->inheritedFieldList
        );
        $output .= $this->getConfigForPosition('TITLEPOS', array('titlex', 'titley'), $this, $this->inheritedFieldList);

        $output .= "\n";

        foreach ($this->colourtable as $k => $colour) {
            $output .= sprintf("%sCOLOR %s\n", $k, $colour->asConfig());
        }
        $output .= "\n";

        foreach ($this->scales as $scaleName => $scale) {
            $output .= $scale->getConfig();
        }
        $output .= "\n";

        foreach ($this->hints as $hintname => $hint) {
            $output .= "SET $hintname $hint\n";
        }

        // this doesn't really work right, but let's try anyway
        if ($this->hasIncludes) {
            $output .= "\n# Included files\n";
            foreach ($this->includedFiles as $ifile) {
                $output .= "INCLUDE $ifile\n";
            }
        }

        $output .= "\n# End of global section\n\n";

        foreach (array('template', 'normal') as $which) {
            if ($which == 'template') {
                $output .= "\n# TEMPLATE-only NODEs:\n";
            }
            if ($which == 'normal') {
                $output .= "\n# regular NODEs:\n";
            }

            foreach ($this->nodes as $node) {
                if (!preg_match('/^::\s/', $node->name)) {
                    if ($node->definedIn == $this->configfile) {
                        if ($which == 'template' && $node->x === null) {
                            MapUtility::debug("TEMPLATE\n");
                            $output .= $node->getConfig();
                        }
                        if ($which == 'normal' && $node->x !== null) {
                            $output .= $node->getConfig();
                        }
                    }
                }
            }

            if ($which == 'template') {
                $output .= "\n# TEMPLATE-only LINKs:\n";
            }

            if ($which == 'normal') {
                $output .= "\n# regular LINKs:\n";
            }

            foreach ($this->links as $link) {
                if (!preg_match('/^::\s/', $link->name)) {
                    if ($link->definedIn == $this->configfile) {
                        if ($which == 'template' && $link->isTemplate()) {
                            $output .= $link->getConfig();
                        }
                        if ($which == 'normal' && !$link->isTemplate()) {
                            $output .= $link->getConfig();
                        }
                    }
                }
            }
        }

        $output .= "\n\n# That's All Folks!\n";

        return $output;
    }

    public function writeConfig($filename)
    {
        $fileHandle = fopen($filename, 'w');

        if ($fileHandle) {
            $output = $this->getConfig();
            fwrite($fileHandle, $output);
            fclose($fileHandle);
        } else {
            MapUtility::warn("Couldn't open config file $filename for writing");
            return false;
        }

        return true;
    }

    /**
     * @return resource
     */
    protected function prepareOutputImage()
    {
        $bgImageRef = $this->loadBackgroundImage();

        $imageRef = imagecreatetruecolor($this->width, $this->height);

        if (!$imageRef) {
            MapUtility::warn("Couldn't create output image in memory (" . $this->width . 'x' . $this->height . ').');
        } else {
            imagealphablending($imageRef, true);
            if ($this->getHint('antialias') == 1) {
                // Turn on anti-aliasing if it exists and it was requested
                if (function_exists('imageantialias')) {
                    imageantialias($imageRef, true);
                }
            }

            // by here, we should have a valid image handle
            $this->selected = ImageUtility::myImageColorAllocate($imageRef, 255, 0, 0); // for selections in the editor

            if ($bgImageRef) {
                imagecopy($imageRef, $bgImageRef, 0, 0, 0, 0, $this->width, $this->height);
                imagedestroy($bgImageRef);
            } else {
                // fill with background colour anyway, in case the background image failed to load
                imagefilledrectangle(
                    $imageRef,
                    0,
                    0,
                    $this->width,
                    $this->height,
                    $this->colourtable['BG']->gdAllocate($imageRef)
                );
            }
        }
        return $imageRef;
    }

    /**
     * @param $imageRef
     * @param $overlayColor
     */
    protected function drawRelativePositionOverlay($imageRef, $overlayColor)
    {
        foreach ($this->nodes as $node) {
            if ($node->positionRelativeTo != '') {
                $parentX = $this->nodes[$node->positionRelativeTo]->x;
                $parentY = $this->nodes[$node->positionRelativeTo]->y;
                imagearc(
                    $imageRef,
                    $node->x,
                    $node->y,
                    15,
                    15,
                    0,
                    360,
                    $overlayColor
                );
                imagearc(
                    $imageRef,
                    $node->x,
                    $node->y,
                    16,
                    16,
                    0,
                    360,
                    $overlayColor
                );

                imageline($imageRef, $node->x, $node->y, $parentX, $parentY, $overlayColor);
            }
        }
    }

    /**
     * @param $imageRef
     * @param $overlayColor
     */
    protected function drawViaOverlay($imageRef, $overlayColor)
    {
        foreach ($this->links as $link) {
            foreach ($link->viaList as $via) {
                if (isset($via[2])) {
                    $x = $this->nodes[$via[2]]->x + $via[0];
                    $y = $this->nodes[$via[2]]->y + $via[1];
                } else {
                    $x = $via[0];
                    $y = $via[1];
                }
                imagearc($imageRef, $x, $y, 10, 10, 0, 360, $overlayColor);
                imagearc($imageRef, $x, $y, 12, 12, 0, 360, $overlayColor);
            }
        }
    }

    protected function calculateDatestamp()
    {
        // if we're running tests, we force the time to a particular value,
        // so the output can be compared to a reference image more easily
        $testmode = intval($this->getHint('testmode'));

        if ($testmode == 1) {
            $maptime = 1270813792;
            date_default_timezone_set('UTC');
        } else {
            $maptime = time();
        }
        $this->datestamp = strftime($this->stamptext, $maptime);
    }

    /**
     * @param $showVIAOverlay
     * @param $showRelativeOverlay
     * @param $imageRef
     */
    protected function drawEditorOverlays($showVIAOverlay, $showRelativeOverlay, $imageRef)
    {
        $overlayColor = ImageUtility::myImageColorAllocate($imageRef, 200, 0, 0);

        if ($showRelativeOverlay) {
            // first, we can show relatively positioned NODEs
            $this->drawRelativePositionOverlay($imageRef, $overlayColor);
        }

        if ($showVIAOverlay) {
            // then overlay VIAs, so they can be seen
            $this->drawViaOverlay($imageRef, $overlayColor);
        }
    }

    /**
     * @param $imageFileName
     * @param $imageRef
     * @return bool
     */
    protected function writeImageFile($imageFileName, $imageRef)
    {
        $result = false;
        $functions = true;
        if (function_exists('imagejpeg') && preg_match('/\.jpg/i', $imageFileName)) {
            MapUtility::debug("Writing JPEG file to $imageFileName\n");
            $result = imagejpeg($imageRef, $imageFileName);
        } elseif (function_exists('imagegif') && preg_match('/\.gif/i', $imageFileName)) {
            MapUtility::debug("Writing GIF file to $imageFileName\n");
            $result = imagegif($imageRef, $imageFileName);
        } elseif (function_exists('imagepng') && preg_match('/\.png/i', $imageFileName)) {
            MapUtility::debug("Writing PNG file to $imageFileName\n");
            $result = imagepng($imageRef, $imageFileName);
        } else {
            MapUtility::warn("Failed to write map image. No function existed for the image format you requested. [WMWARN12]\n");
            $functions = false;
        }

        if (($result == false) && ($functions == true)) {
            if (file_exists($imageFileName)) {
                MapUtility::warn("Failed to overwrite existing image file $imageFileName - permissions of existing file are wrong? [WMWARN13]");
            } else {
                MapUtility::warn("Failed to create image file $imageFileName - permissions of output directory are wrong? [WMWARN14]");
            }
        }
        return $result;
    }

    /**
     * @param $thumbnailFileName
     * @param $thumbnailMaxSize
     * @param $imageRef
     */
    protected function createThumbnailFile($thumbnailFileName, $thumbnailMaxSize, $imageRef)
    {
        MapUtility::debug("Writing thumbnail to %s\n", $thumbnailFileName);

        if (!function_exists('imagecopyresampled')) {
            MapUtility::warn("Skipping thumbnail creation, since we don't have the necessary function. [WMWARN17]");
            return;
        }

        // if one is specified, and we can, write a thumbnail too
        if ($thumbnailFileName != '') {
            if ($this->width > $this->height) {
                $factor = ($thumbnailMaxSize / $this->width);
            } else {
                $factor = ($thumbnailMaxSize / $this->height);
            }

            $this->thumbWidth = $this->width * $factor;
            $this->thumbHeight = $this->height * $factor;

            $thumbImageRef = imagecreatetruecolor($this->thumbWidth, $this->thumbHeight);
            imagecopyresampled(
                $thumbImageRef,
                $imageRef,
                0,
                0,
                0,
                0,
                $this->thumbWidth,
                $this->thumbHeight,
                $this->width,
                $this->height
            );
            $result = imagepng($thumbImageRef, $thumbnailFileName);
            imagedestroy($thumbImageRef);

            if (($result == false)) {
                if (file_exists($thumbnailFileName)) {
                    MapUtility::warn("Failed to overwrite existing image file $thumbnailFileName - permissions of existing file are wrong? [WMWARN15]");
                } else {
                    MapUtility::warn("Failed to create image file $thumbnailFileName - permissions of output directory are wrong? [WMWARN16]");
                }
            }
        }
    }

    public function preCalculate()
    {
        MapUtility::debug("preCalculating everything\n");

        $allMapItems = $this->buildAllItemsList();

        foreach ($allMapItems as $item) {
            $item->preCalculate($this);
        }
    }

    public function drawMap(
        $imageFileName = '',
        $thumbnailFileName = '',
        $thumbnailMaxSize = 250,
        $includeNodes = true,
        $showVIAOverlay = false,
        $showRelativeOverlay = false
    ) {
        MapUtility::debug("Trace: DrawMap()\n");

        MapUtility::debug("=====================================\n");
        MapUtility::debug("Start of Map Drawing\n");

        $this->calculateDatestamp();

        // Create an imageRef to draw into
        $imageRef = $this->prepareOutputImage();

        // Now it's time to draw a map

        // do the node rendering stuff first, regardless of where they are actually drawn.
        // this is so we can get the size of the nodes, which links will need if they use offsets
        // TODO - the geometry part should be in preCalculate()
        foreach ($this->nodes as $node) {
            MapUtility::debug('Pre-rendering ' . $node->name . " to get bounding boxes.\n");
            if (!$node->isTemplate()) {
                $node->preCalculate($this);
                $node->preRender($this);
            }
        }

        $this->preCalculate();

        $allLayers = array_keys($this->seenZLayers);
        sort($allLayers);

        // stuff the scales into the seen-items list, so they are rendered along with everything else
        foreach ($this->scales as $scaleName => $scaleObject) {
            array_push($this->seenZLayers[1000], $scaleObject);
        }

        foreach ($allLayers as $z) {
            $zItems = $this->seenZLayers[$z];
            MapUtility::debug("Drawing layer $z\n");
            // all the map 'furniture' is fixed at z=1000
            if ($z == 1000) {
                $this->drawTimestamp($imageRef, $this->timefont, $this->colourtable['TIME']);
                if (!is_null($this->minimumDataTime)) {
                    $this->drawTimestamp($imageRef, $this->timefont, $this->colourtable['TIME'], 'MIN');
                    $this->drawTimestamp($imageRef, $this->timefont, $this->colourtable['TIME'], 'MAX');
                }
                $this->drawTitle($imageRef, $this->titlefont, $this->colourtable['TITLE']);
            }

            if (is_array($zItems)) {
                /** @var MapDataItem $it */
                foreach ($zItems as $it) {
                    MapUtility::debug('Drawing ' . $it->myType() . ' ' . $it->name . "\n");
                    $it->draw($imageRef);
                }
            }
        }

        // for the editor, we can optionally overlay some other stuff
        if ($this->context == 'editor') {
            $this->drawEditorOverlays($showVIAOverlay, $showRelativeOverlay, $imageRef);
        }

        // Ready to output the results...

        if ($imageFileName == 'null') {
            // do nothing at all - we just wanted the HTML AREAs for the editor or HTML output
        } else {
            // write to the standard output (for the editor)
            if ($imageFileName == '') {
                imagepng($imageRef);
            } else {
                $this->writeImageFile($imageFileName, $imageRef);
                $this->createThumbnailFile($thumbnailFileName, $thumbnailMaxSize, $imageRef);
            }
        }

        imagedestroy($imageRef);
    }

    public function cleanUp()
    {
        global $weathermap_error_suppress;

        parent::cleanUp();

        $allLayers = array_keys($this->seenZLayers);

        foreach ($allLayers as $z) {
            $this->seenZLayers[$z] = null;
        }

        foreach ($this->links as $link) {
            $link->cleanUp();
            unset($link);
        }

        foreach ($this->nodes as $node) {
            $node->cleanUp();
            unset($node);
        }

        // Clear up the other random hashes of information
        $this->dsinfocache = null;
        $this->colourtable = null;
        $this->scales = null;
        $weathermap_error_suppress = array();
    }

    public function calculateImagemap()
    {
        MapUtility::debug("Trace: calculateImagemap()\n");

        // loop through everything. Figure out along the way if it's a node or a link
        $allItems = $this->buildAllItemsList();

        foreach ($allItems as $mapItem) {
            $type = $mapItem->myType();

            $dirs = array();
            if ($type == 'LINK') {
                $dirs = array(IN => array(0, 2), OUT => array(1, 3));
            }
            if ($type == 'NODE') {
                $dirs = array(IN => array(0, 1, 2, 3));
            }

            // check to see if any of the relevant things have a value
            $change = '';
            foreach ($dirs as $d => $parts) {
                $change .= join('', $mapItem->overliburl[$d]);
                $change .= $mapItem->notestext[$d];
            }
            // skip all this if it's a template node
            if ($mapItem->isTemplate()) {
                $change = '';
            }

            if ($this->htmlstyle == 'overlib') {
                if ($change != '') {
                    // find the middle of the map
                    $mapCenterX = $this->width / 2;
                    $mapCenterY = $this->height / 2;

                    $type = $mapItem->myType();

                    if ($type == 'NODE') {
                        $midX = $mapItem->x;
                        $midY = $mapItem->y;
                    }
                    if ($type == 'LINK') {
                        $aX = $mapItem->endpoints[0]->node->x;
                        $aY = $mapItem->endpoints[0]->node->y;

                        $bX = $mapItem->endpoints[1]->node->x;
                        $bY = $mapItem->endpoints[0]->node->y;

                        $midX = ($aX + $bX) / 2;
                        $midY = ($aY + $bY) / 2;
                    }
                    $left = '';
                    $above = '';
                    $imageExtraHTML = '';

                    if ($mapItem->overlibwidth != 0) {
                        $left = 'WIDTH,' . $mapItem->overlibwidth . ',';
                        $imageExtraHTML .= " WIDTH=$mapItem->overlibwidth";

                        if ($midX > $mapCenterX) {
                            $left .= 'LEFT,';
                        }
                    }

                    if ($mapItem->overlibheight != 0) {
                        $above = 'HEIGHT,' . $mapItem->overlibheight . ',';
                        $imageExtraHTML .= " HEIGHT=$mapItem->overlibheight";

                        if ($midY > $mapCenterY) {
                            $above .= 'ABOVE,';
                        }
                    }

                    foreach ($dirs as $dir => $parts) {
                        $caption = ($mapItem->overlibcaption[$dir] != '' ? $mapItem->overlibcaption[$dir] : $mapItem->name);
                        $caption = $this->processString($caption, $mapItem);

                        $overlibhtml = "onmouseover=\"return overlib('";

                        $n = 0;
                        if (count($mapItem->overliburl[$dir]) > 0) {
                            // print "ARRAY:".is_array($link->overliburl[$dir])."\n";
                            foreach ($mapItem->overliburl[$dir] as $url) {
                                if ($n > 0) {
                                    $overlibhtml .= '&lt;br /&gt;';
                                }
                                $overlibhtml .= "&lt;img $imageExtraHTML src=";
                                $overlibhtml .= $this->processString(
                                    $url,
                                    $mapItem
                                );
                                $overlibhtml .= '&gt;';
                                $n++;
                            }
                        }
                        # print "Added $n for $dir\n";
                        if (trim($mapItem->notestext[$dir]) != '') {
                            # put in a linebreak if there was an image AND notes
                            if ($n > 0) {
                                $overlibhtml .= '&lt;br /&gt;';
                            }
                            $note = $this->processString($mapItem->notestext[$dir], $mapItem);
                            $note = htmlspecialchars($note, ENT_NOQUOTES);
                            $note = str_replace("'", '\\&apos;', $note);
                            $note = str_replace('"', '&quot;', $note);
                            $overlibhtml .= $note;
                        }
                        $overlibhtml .= "',DELAY,250,${left}${above}CAPTION,'" . $caption
                            . "');\"  onmouseout=\"return nd();\"";

                        foreach ($mapItem->imagemapAreas as $area) {
                            $area->extrahtml = $overlibhtml;
                        }
                    }
                } // if change
            } // overlib?

            // now look at infourls
            foreach ($dirs as $dir => $parts) {
                foreach ($parts as $part) {
                    if (($this->htmlstyle != 'editor') && ($mapItem->infourl[$dir] != '')) {
                        foreach ($mapItem->imagemapAreas as $area) {
                            $area->href = $this->processString($mapItem->infourl[$dir], $mapItem);
                        }
                    }
                }
            }
        }
    }

    public function asJS()
    {
        $newOutput = array("Nodes" => array(), "Links" => array(), "Areas" => array());

        foreach ($this->links as $link) {
            $newOutput['Links'][$link->name] = $link->editorData();
        }

        foreach ($this->nodes as $node) {
            $newOutput['Nodes'][$node->name] = $node->editorData();
        }

        return "var mapdata = " . json_encode($newOutput) . ";";
    }


    // This method MUST run *after* DrawMap. It relies on DrawMap to call the map-drawing bits
    // which will populate the Imagemap with regions.
    //
    // imagemapname is a parameter, so we can stack up several maps in the Cacti plugin with their own imagemaps
    public function makeHTML($imagemapname = 'weathermap_imap')
    {
        MapUtility::debug("Trace: MakeHTML()\n");
        // PreloadMapHTML fills in the Imagemap info, ready for the HTML to be created.
        $this->calculateImagemap();

        $html = '';

        $html .= '<div class="weathermapimage" style="margin-left: auto; margin-right: auto; width: ' . $this->width . 'px;" >';
        if ($this->imageuri != '') {
            $html .= sprintf(
                '<img id="wmapimage" src="%s" width="%d" height="%d" border="0" usemap="#%s"',
                $this->imageuri,
                $this->width,
                $this->height,
                $imagemapname
            );
            $html .= '/>';
        } else {
            $html .= sprintf(
                '<img id="wmapimage" src="%s" width="%d" height="%d" border="0" usemap="#%s"',
                $this->imagefile,
                $this->width,
                $this->height,
                $imagemapname
            );
            $html .= '/>';
        }
        $html .= '</div>';

        $html .= $this->generateSortedImagemap($imagemapname);

        return $html;
    }

    public function generateSortedImagemap($imagemapname)
    {
        $html = "\n<map name=\"" . $imagemapname . '" id="' . $imagemapname . "\">\n";

        $allLayers = array_keys($this->seenZLayers);
        rsort($allLayers);

        MapUtility::debug("Starting to dump imagemap in reverse Z-order...\n");
        foreach ($allLayers as $z) {
            MapUtility::debug("Writing HTML for layer $z\n");
            $zItems = $this->seenZLayers[$z];
            if (is_array($zItems)) {
                MapUtility::debug("   Found things for layer $z\n");

                // at z=1000, the legends and timestamps live
                if ($z == 1000) {
                    MapUtility::debug("     Builtins fit here.\n");

                    foreach ($this->imagemapAreas as $areaname) {
                        // skip the linkless areas if we are in the editor - they're redundant
                        $html .= $this->imap->exactHTML(
                            $areaname,
                            ($this->context != 'editor')
                        );
                        $html .= "\n";
                    }

                    foreach ($this->scales as $it) {
                        foreach ($it->getImagemapAreas() as $area) {
                            MapUtility::debug("$area\n");
                            // skip the linkless areas if we are in the editor - they're redundant
                            $html .= "\t" . $area->asHTML();
                            $html .= "\n";
                        }
                        $html .= "\n";
                    }
                }

                // we reverse the array for each zlayer so that the imagemap order
                // will match up with the draw order (last drawn should be first hit)
                /** @var MapDataItem $it */
                foreach (array_reverse($zItems) as $it) {
                    if ($it->name != 'DEFAULT' && $it->name != ':: DEFAULT ::') {
                        foreach ($it->getImagemapAreas() as $area) {
                            MapUtility::debug("$area\n");
                            // skip the linkless areas if we are in the editor - they're redundant
                            $html .= "\t" . $area->asHTML();
                            $html .= "\n";
                        }
                        $html .= "\n";
                    }
                }
            }
        }

        $html .= '</map>';

        return $html;
    }

    public function nodeExists($nodeName)
    {
        return array_key_exists($nodeName, $this->nodes);
    }

    public function linkExists($linkName)
    {
        return array_key_exists($linkName, $this->links);
    }

    /**
     * Create an array of all the nodes and links, mixed together.
     * readData() makes several passes through this list.
     *
     * @return MapDataItem[]
     */
    public function buildAllItemsList()
    {
        // TODO - this should probably be a static, or otherwise cached
        $allItems = array();

        $listOfItemLists = array(&$this->nodes, &$this->links);
        reset($listOfItemLists);

        while (list($outerListCount,) = each($listOfItemLists)) {
            unset($itemList);
            $itemList = &$listOfItemLists[$outerListCount];

            reset($itemList);
            while (list($innerListCount,) = each($itemList)) {
                unset($oneMapItem);
                $oneMapItem = &$itemList[$innerListCount];
                $allItems [] = $oneMapItem;
            }
        }
        return $allItems;
    }


    /**
     * For each mapitem, loop through all its targets and find a plugin
     * that recognises them. Then register the target with the plugin
     * so that it can potentially pre-fetch or optimise in some way.
     *
     * @param $itemList
     */
    private function preProcessTargets($itemList)
    {
        MapUtility::debug("Preprocessing targets\n");

        /** @var MapDataItem $mapItem */
        foreach ($itemList as $mapItem) {
            if ($mapItem->isTemplate()) {
                continue;
            }

            $mapItem->prepareForDataCollection();
        }
    }

    /**
     * Keep track of the current minimum and maximum timestamp for collected data
     *
     * @param $dataTime
     */
    public function registerDataTime($dataTime)
    {
        if ($dataTime == 0) {
            return;
        }

        if ($this->maximumDataTime == null || $dataTime > $this->maximumDataTime) {
            $this->maximumDataTime = $dataTime;
        }

        if ($this->minimumDataTime == null || $dataTime < $this->minimumDataTime) {
            $this->minimumDataTime = $dataTime;
        }
        MapUtility::debug('Current DataTime MINMAX: ' . $this->minimumDataTime . ' -> ' . $this->maximumDataTime . "\n");
    }

    private function readDataFromTargets($itemList)
    {
        MapUtility::debug("======================================\n");
        MapUtility::debug("Starting main collection loop\n");

        /** @var MapDataItem $mapItem */
        foreach ($itemList as $mapItem) {
            if ($mapItem->isTemplate()) {
                MapUtility::debug("ReadData: Skipping $mapItem that looks like a template\n.");
                continue;
            }

            $mapItem->performDataCollection();

            // NOTE - this part still happens even if there were no targets
            $mapItem->aggregateDataResults();
            $mapItem->calculateScaleColours();

            unset($mapItem);
        }
    }


    public function randomData()
    {
        foreach ($this->links as $link) {
            $this->links[$link->name]->absoluteUsages[IN] = rand(0, $link->maxValues[IN]);
            $this->links[$link->name]->absoluteUsages[OUT] = rand(0, $link->maxValues[OUT]);
        }
    }

    public function zeroData()
    {
        $allMapItems = $this->buildAllItemsList();

        foreach ($allMapItems as $mapItem) {
            if ($mapItem->isTemplate()) {
                MapUtility::debug("zeroData: Skipping $mapItem that looks like a template\n.");
                continue;
            }

            $mapItem->zeroData();

            $mapItem->aggregateDataResults();
            $mapItem->calculateScaleColours();

            unset($mapItem);
        }
    }

    public function readData()
    {
        // we skip readdata completely in sizedebug mode
        if ($this->sizedebug != 0) {
            MapUtility::debug("Size Debugging is on. Skipping readData.\n");
            return;
        }

        MapUtility::debug("======================================\n");
        MapUtility::debug("ReadData: Updating link data for all links and nodes\n");

        $allMapItems = $this->buildAllItemsList();

        // process all the targets and find a plugin for them
        $this->preProcessTargets($allMapItems);

        $this->pluginManager->prefetchPlugins();

        $this->readDataFromTargets($allMapItems);

        $this->pluginManager->cleanupPlugins('data');

        $this->pluginManager->runProcessorPlugins('post');

        MapUtility::debug("ReadData Completed.\n");
        MapUtility::debug("------------------------------\n");
    }

    public function createDefaultNodes()
    {
        MapUtility::debug("Creating ':: DEFAULT ::' DEFAULT NODE\n");
        $this->addNode(new MapNode(':: DEFAULT ::', ':: DEFAULT ::', $this));

        MapUtility::debug("Creating actual DEFAULT NODE from :: DEFAULT ::\n");
        $this->addNode(new MapNode('DEFAULT', ':: DEFAULT ::', $this));
    }

    public function createDefaultLinks()
    {
        // these are the default defaults
        // by putting them into a normal object, we can use the
        // same code for writing out LINK DEFAULT as any other link.
        MapUtility::debug("Creating ':: DEFAULT ::' DEFAULT LINK\n");
        // these two are used for default settings
        $this->addLink(new MapLink(':: DEFAULT ::', ':: DEFAULT ::', $this));

        MapUtility::debug("Creating actual DEFAULT LINK from :: DEFAULT ::\n");
        $this->addLink(new MapLink('DEFAULT', ':: DEFAULT ::', $this));
    }

    public function getValue($name)
    {
        MapUtility::debug("Fetching %s\n", $name);
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        throw new WeathermapInternalFail('NoSuchProperty');
    }

    /**
     * @return null|resource
     */
    protected function loadBackgroundImage()
    {
        $bgImageRef = null;

        // do the basic prep work
        if ($this->background != '') {
            if (is_readable($this->background)) {
                $bgImageRef = ImageUtility::imageCreateFromFile($this->background);

                if (!$bgImageRef) {
                    MapUtility::warn(
                        "Failed to open background image.  One possible reason: Is your BACKGROUND really a PNG?\n"
                    );
                    return $bgImageRef;
                }

                $this->width = imagesx($bgImageRef);
                $this->height = imagesy($bgImageRef);

                return $bgImageRef;
            }

            MapUtility::warn(
                'Your background image file could not be read. Check the filename, and permissions, for '
                . $this->background . "\n"
            );
        }

        return $bgImageRef;
    }

    public function asConfigData()
    {
        $conf = array();

        $conf['vars'] = $this->hints;
        $conf['fonts'] = $this->fonts->asConfigData();

        $conf['title'] = $this->title;
        $conf['width'] = $this->width;
        $conf['height'] = $this->height;

        $conf["htmlstyle"] = $this->htmlstyle;
        $conf["legendfont"] = $this->keyfont;
        $conf["stamptext"] = $this->stamptext;
        $conf["pngfile"] = $this->imageoutputfile;
        $conf["htmlfile"] = $this->htmloutputfile;
        $conf["bgfile"] = $this->background;
        $conf['imagemap'] = array();

        foreach ($this->imagemapAreas as $areaname) {
            $area = $this->imap->getByName($areaname);
            $conf['imagemap'] [] = $area->asJSONData();
        }

        // title font, pos
        // time font, pos

        return $conf;
    }

    public function getJSONConfig()
    {
        $conf = array(
            'global' => $this->asConfigData(),
            'scales' => array(),
            'nodes' => array(),
            'links' => array()
        );

        foreach ($this->scales as $scale) {
            $conf['scales'][$scale->name] = $scale->asConfigData();
        }

        foreach ($this->nodes as $node) {
            $conf['nodes'][$node->name] = $node->asConfigData();
        }

        foreach ($this->links as $link) {
            $conf['links'][$link->name] = $link->asConfigData();
        }

        return json_encode($conf);
    }

    public function getProperty($name)
    {
        MapUtility::debug("Map Fetching %s\n", $name);

        $translations = array(
            'max_data_time' => $this->maximumDataTime,
            'min_data_time' => $this->minimumDataTime
        );

        if (array_key_exists($name, $translations)) {
            return $translations[$name];
        }
        // TODO - at some point, we can remove this bit, and limit access to ONLY the things listed above
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        throw new WeathermapRuntimeWarning("NoSuchProperty");
    }

    /**
     * @param $context
     * @return string
     */
    public function getProcessStringContextName(&$context)
    {
        $contextDescription = strtolower($context->myType());
        if ($contextDescription != 'map') {
            $contextDescription .= ':' . $context->name;
        }
        return $contextDescription;
    }

    /**
     * @param $input
     * @param $context
     * @param $contextType
     * @return mixed
     */
    public function applyProcessStringShortcuts($input, &$context, $contextType)
    {
        if ($contextType === 'node') {
            $input = str_replace('{node:this:graph_id}', $context->getHint('graph_id'), $input);
            $input = str_replace('{node:this:name}', $context->name, $input);
        }

        if ($contextType === 'link') {
            $input = str_replace('{link:this:graph_id}', $context->getHint('graph_id'), $input);
        }
        return $input;
    }
}
// vim:ts=4:sw=4:
