<?php
// PHP Weathermap 0.98
// Copyright Howard Jones, 2005-2014 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License

class WeatherMap extends WeatherMapBase
{
    var $nodes = array(); // an array of WeatherMapNodes
    var $links = array(); // an array of WeatherMapLinks
    var $texts = array(); // an array containing all the extraneous text bits
    var $used_images = array(); // an array of image filenames referred to (used by editor)
    var $seen_zlayers = array(0 => array(), 1000 => array()); // 0 is the background, 1000 is the legends, title, etc

    var $scales = array(); // the new array of WMScale objects

    var $next_id;

    var $background;
    var $htmlstyle;
    var $imap;
    var $colours;
    var $configfile;
    var $imagefile;
    var $imageuri;
    var $rrdtool;

    var $kilo;
    var $sizedebug;
    var $widthmod;
    var $debugging;
    var $keyfont;
    var $timefont;

    var $width;
    var $height;

    var $keyx, $keyy, $keyimage, $keytext; // arrays

    var $titlex,
        $titley;
    var $title,
        $titlefont;

    var $htmloutputfile;
    var $imageoutputfile;
    var $dataoutputfile;

    var $htmlstylesheet;

    var $defaultlink;
    var $defaultnode;

    var $need_size_precalc;
    var $keystyle, $keysize;
    var $rrdtool_check;

    var $min_ds_time;
    var $max_ds_time;
    var $min_data_time, $max_data_time;
    var $timex, $timey;
    var  $stamptext, $datestamp;
    var $mintimex, $maxtimex;
    var $mintimey, $maxtimey;
    var $minstamptext, $maxstamptext;

    var $context;
    var $selected;

    var $thumb_width, $thumb_height;
    var $has_includes;
    var $has_overlibs;
    var $dsinfocache = array();

    var $plugins = array();
    var $included_files = array();
    var $usage_stats = array();
    var $colourtable = array();
    var $fonts = null;
    var $warncount = 0;
    var $jsincludes = array();
    var $parent = null;

    var $runtime = array();

    public function __construct()
    {
        parent::__construct();

        $this->inherit_fieldlist = array
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
            'dataoutputfile' => '',
            'imageuri' => '',
            'htmloutputfile' => '',
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

        $this->min_ds_time = null;
        $this->max_ds_time = null;

        $this->scales = array();

        $this->colourtable = array();

        $this->configfile = '';
        $this->imagefile = '';
        $this->imageuri = '';

        $this->fonts = new WMFontTable();
        $this->fonts->init();

        $this->reset();
        $this->loadAllPlugins();
    }

    public function __toString()
    {
        return "MAP";
    }

    function my_type()
    {
        return "MAP";
    }

    // Simple accessors to stop the editor from reaching inside objects quite so much

    function getNode($name)
    {
        if (isset($this->nodes[$name])) {
            return $this->nodes[$name];
        }
        throw new WMException("NoSuchNode");
    }

    function addNode($newObject)
    {
        if ($this->nodeExists($newObject->name)) {
            throw new WMException("NodeAlreadyExists");
        }
        $this->nodes[$newObject->name] = $newObject;
        $this->addItemToZLayer($newObject, $newObject->getZIndex());
    }

    function getLink($name)
    {
        if (isset($this->links[$name])) {
            return $this->links[$name];
        }
        throw new WMException("NoSuchLink");
    }

    function addLink($newObject)
    {
        if ($this->linkExists($newObject->name)) {
            throw new WMException("LinkAlreadyExists");
        }
        $this->links[$newObject->name] = $newObject;
        $this->addItemToZLayer($newObject, $newObject->getZIndex());
    }

    function getScale($name)
    {
        if (isset($this->scales[$name])) {
            return $this->scales[$name];
        }
        throw new WMException("NoSuchScale");
    }

    function reset()
    {
        $this->next_id = 100;

        foreach (array_keys($this->inherit_fieldlist) as $fld) {
            $this->$fld = $this->inherit_fieldlist[$fld];
        }

        $this->nodes = array(); // an array of WeatherMapNodes
        $this->links = array(); // an array of WeatherMapLinks

        // these are the default defaults
        // by putting them into a normal object, we can use the
        // same code for writing out LINK DEFAULT as any other link.

        $this->createDefaultNodes();
        $this->createDefaultLinks();

        $this->imap = new HTML_ImageMap('weathermap');
        $this->colours = array();

        $this->scales['DEFAULT'] = new WeatherMapScale("DEFAULT", $this);
        $this->populateDefaultColours();

        wm_debug("WeatherMap class Reset() complete\n");
    }

    private function loadAllPlugins()
    {
        $this->loadPlugins('data', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'datasources');
        $this->loadPlugins('pre', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'pre');
        $this->loadPlugins('post', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'post');
    }

    private function populateDefaultColours()
    {
        wm_debug("Adding default map colour set.\n");
        $defaults = array(
            'KEYTEXT' => array('bottom' => -2, 'top' => -1, 'red' => 0, 'green' => 0, 'blue' => 0),
            'KEYOUTLINE' => array('bottom' => -2, 'top' => -1, 'red' => 0, 'green' => 0, 'blue' => 0),
            'KEYBG' => array('bottom' => -2, 'top' => -1, 'red' => 255, 'green' => 255, 'blue' => 255),
            'BG' => array('bottom' => -2, 'top' => -1, 'red' => 255, 'green' => 255, 'blue' => 255),
            'TITLE' => array('bottom' => -2, 'top' => -1, 'red' => 0, 'green' => 0, 'blue' => 0),
            'TIME' => array('bottom' => -2, 'top' => -1, 'red' => 0, 'green' => 0, 'blue' => 0)
        );

        foreach ($defaults as $key => $def) {
            $this->colourtable[$key] = new WMColour($def['red'], $def['green'], $def['blue']);
        }
    }

    /**
     * The old way to render strings in a particular font.
     * @deprecated use WMFont instead
     *
     * @param $image
     * @param $fontnumber
     * @param $x
     * @param $y
     * @param $string
     * @param $colour
     * @param int $angle
     * @return mixed
     */
    function myimagestring($image, $fontnumber, $x, $y, $string, $colour, $angle = 0)
    {
        // throw new WMException("Still using myimagestring");
        $fontObject = $this->fonts->getFont($fontnumber);
        return $fontObject->drawImageString($image, $x, $y, $string, $colour, $angle);

    }

    /**
     * The old way to get font metrics.
     * @deprecated use WMFont instead
     *
     * @param $fontNumber
     * @param $string
     * @return mixed
     */
    function myimagestringsize($fontNumber, $string)
    {
        // throw new WMException("Still using myimagestring");
        $fontObject = $this->fonts->getFont($fontNumber);
        return $fontObject->calculateImageStringSize($string);
    }

    function processString($input, &$context, $include_notes = true, $multiline = false)
    {
        // don't bother with all this regexp rubbish if there's nothing to match
        if (false === strpos($input, "{")) {
            return $input;
        }

        $the_item = null;
        $context_type = $this->getContextDescription($context);

        $input = $this->applyProcessStringShortcuts($input, $context, $context_type);

        // check if we can now quit early before the regexp stuff
        if (false === strpos($input, "{")) {
            return $input;
        }

        $output = $input;

        while (preg_match('/(\{((?:node|map|link)[^}]+)\})/', $input, $matches)) {
            $value = "[UNKNOWN]";
            $format = "";
            $keyContents = $matches[2];
            $key = "{" . $matches[2] . "}";

            $value = $this->processStringToken($include_notes, $keyContents, $key, $context);

            // We track the input and a clean output string separately, to stop people doing
            // weird things like setting variables to also include tokens
            $input = str_replace($key, '', $input);
            $output = str_replace($key, $value, $output);
        }
        return ($output);
    }

    /**
     * Given a token from ProcessString(), and the context for it, figure out the actual value and format @inheritdoc
     *
     * @param $include_notes
     * @param $keyContents
     * @param $key
     * @param $contextDescription
     * @param $value
     * @return array
     */
    private function processStringToken($include_notes, $keyContents, $key, $context)
    {
        $value = "";

        $contextDescription = $this->getContextDescription($context);
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
            wm_warn("ProcessString: $key refers to unknown item (context is $contextDescription) [WMWARN05]\n");
            return "";
        }

        wm_debug("ProcessString: Found appropriate item: $theItem\n");

        $value = $this->findItemValue($theItem, $args, $value, $include_notes);

        // format, and sanitise the value string here, before returning it
        wm_debug("ProcessString: replacing %s with %s \n", $key, $value);

        if ($format != '') {
            $value = WMUtility::sprintf($format, $value, $this->kilo);
            wm_debug("ProcessString: formatted $format to $value\n");
        }
        return $value;
    }

    /**
     * @param $context
     * @return string
     */
    private function getContextDescription(&$context)
    {
        $context_type = strtolower($context->my_type());

        if ($context_type != "map") {
            $contextDescription = $context_type . ":" . $context->name;
        } else {
            $contextDescription = $context_type;
        }
        return $contextDescription;
    }


    /**
     * @param $input
     * @param $context
     * @param $context_type
     * @return mixed
     */
    private function applyProcessStringShortcuts($input, &$context, $context_type)
    {
// next, shortcut all the regexps for very common tokens
        if ($context_type === 'node') {
            $input = str_replace("{node:this:graph_id}", $context->get_hint("graph_id"), $input);
            $input = str_replace("{node:this:name}", $context->name, $input);
        }

        if ($context_type === 'link') {
            $input = str_replace("{link:this:graph_id}", $context->get_hint("graph_id"), $input);
            $input = str_replace("{link:this:name}", $context->name, $input);

            if (false !== strpos($input, "{")) {
                // TODO - shortcuts for standard bwlabel formats, if there is still a token

                //if (strpos($input,"{link:this:bandwidth_in:%2k}") !== false) {
                //    $temp = mysprintf("%2k",$context->hints['bandwidth_in']);
                //    $input = str_replace("{link:this:bandwidth_in:%2k}", $temp, $input);
                //}

                // define('FMT_BITS_IN',"{link:this:bandwidth_in:%2k}");
                // define('FMT_BITS_OUT',"{link:this:bandwidth_out:%2k}");
                // define('FMT_UNFORM_IN',"{link:this:bandwidth_in}");
                // define('FMT_UNFORM_OUT',"{link:this:bandwidth_out}");
                // define('FMT_PERC_IN',"{link:this:inpercent:%.2f}%");
                // define('FMT_PERC_OUT',"{link:this:outpercent:%.2f}%");
            }
            return $input;
        }
        return $input;
    }

    /**
     * @param $context
     * @param $itemname
     * @param $type
     * @return mixed
     */
    private function processStringFindReferredObject(&$context, $itemname, $type)
    {
        if (($itemname == "this") && ($type == strtolower($context->my_type()))) {
            return $context;
        }

        if ($context->my_type() == "LINK" && $type == 'node') {
            // this refers to the two nodes at either end of this link
            if ($itemname == '_linkstart_') {
                return $context->a;
            }

            if ($itemname == '_linkend_') {
                return $context->b;
            }
        }

        if (($itemname == "parent") && ($type == "node") && ($context->my_type() == 'NODE') && ($context->isRelativePositioned())) {
            return $this->getRelativeAnchor();
        }

        if (($type == 'link') && isset($this->links[$itemname])) {
            return $this->links[$itemname];
        }

        if (($type == 'node') && isset($this->nodes[$itemname])) {
            return $this->nodes[$itemname];
        }
        return null;

    }

    function findItemValue(&$mapItem, $variableName, $currentValue, $includeNotes = true)
    {
        // SET and notes have precedent over internal properties
        // this is my laziness - it saves me having a list of reserved words
        // which are currently used for internal props. You can just 'overwrite' any of them.
        if (array_key_exists($variableName, $mapItem->hints)) {
            wm_debug("ProcessString: used hint\n");
            return $mapItem->hints[$variableName];
        }

        if ($includeNotes && array_key_exists($variableName, $mapItem->notes)) {
            // for some things, we don't want to allow notes to be considered.
            // mainly - TARGET (which can define command-lines), shouldn't be
            // able to get data from uncontrolled sources (i.e. data sources rather than SET in config files).
            wm_debug("ProcessString: used note\n");
            return $mapItem->notes[$variableName];
        }

        // Previously this was directly accessing properties of map items
        try {
            $value = $mapItem->getValue($variableName);
        } catch (WMException $e) {
            // give up, and pass back the current value
            return $currentValue;
        }

        wm_debug("ProcessString: used internal property\n");
        return $value;
    }

    /**
     * Fill in the links with random bandwidth data. Basically for a "demo mode" for
     * the command-line tool.
     */
    function populateRandomData()
    {
        foreach ($this->links as $link) {
            $link->bandwidth_in = rand(0, $link->max_bandwidth_in);
            $link->bandwidth_out = rand(0, $link->max_bandwidth_out);
        }
    }

    /**
     * Search a directory for plugin class files, and load them. Each one is then
     * instantiated once, and saved into the map object.
     *
     * @param string $pluginType - Which kind of plugin are we loading?
     * @param string $searchDirectory - Where to load from?
     */
    private function loadPlugins($pluginType = "data", $searchDirectory = "lib/datasources")
    {
        wm_debug("Beginning to load $pluginType plugins from $searchDirectory\n");


        $pluginList = $this->getPluginFileList($pluginType, $searchDirectory);

        foreach ($pluginList as $fullFilePath => $file) {
            wm_debug("Loading $pluginType Plugin class from $file\n");

            $class = preg_replace("/\\.php$/", "", $file);
            include_once($fullFilePath);

            wm_debug("Loaded $pluginType Plugin class $class from $file\n");

            $this->plugins[$pluginType][$class]['object'] = new $class;
            $this->plugins[$pluginType][$class]['active'] = true;

            if (!isset($this->plugins[$pluginType][$class])) {
                wm_debug("** Failed to create an object for plugin $pluginType/$class\n");
                $this->plugins[$pluginType][$class]['active'] = false;
            }

        }
        wm_debug("Finished loading plugins.\n");
    }

    /**
     * @param $pluginType
     * @param $searchDirectory
     * @return array
     */
    private function getPluginFileList($pluginType, $searchDirectory)
    {
        $directoryHandle = $this->resolveDirectoryAndOpen($searchDirectory);

        $pluginList = array();
        if (!$directoryHandle) {
            wm_warn("Couldn't open $pluginType Plugin directory ($searchDirectory). Things will probably go wrong. [WMWARN06]\n");
        }

        while ($file = readdir($directoryHandle)) {
            $fullFilePath = $searchDirectory . DIRECTORY_SEPARATOR . $file;

            if (!is_file($fullFilePath) || !preg_match('/\.php$/', $fullFilePath)) {
                continue;
            }

            $pluginList[$fullFilePath] = $file;
        }
        return $pluginList;
    }

    private function resolveDirectoryAndOpen($dir)
    {
        if (!file_exists($dir)) {
            $dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . $dir;
            wm_debug("Relative path didn't exist. Trying $dir\n");
        }
        $directoryHandle = @opendir($dir);

        // XXX - is this ever necessary?
        if (!$directoryHandle) { // try to find it with the script, if the relative path fails
            $srcdir = substr($_SERVER['argv'][0], 0, strrpos($_SERVER['argv'][0], DIRECTORY_SEPARATOR));
            $directoryHandle = opendir($srcdir . DIRECTORY_SEPARATOR . $dir);
            if ($directoryHandle) {
                $dir = $srcdir . DIRECTORY_SEPARATOR . $dir;
            }
        }

        return $directoryHandle;
    }

    /**
     * Loop through the datasource plugins, allowing them to initialise any internals.
     * The plugins can also refuse to run, if resources they need aren't available.
     */
    private function initialiseAllPlugins()
    {
        wm_debug("Running Init() for Data Source Plugins...\n");

        foreach (array('data','pre','post') as $type) {
            wm_debug("Initialising $type Plugins...\n");

            foreach ($this->plugins[$type] as $name => $pluginEntry) {
                wm_debug("Running $name" . "->Init()\n");

                $ret = $pluginEntry['object']->Init($this);

                if (!$ret) {
                    wm_debug("Marking $name plugin as inactive, since Init() failed\n");
                    // $pluginEntry['active'] = false;
                    $this->plugins[$type][$name]['active'] = false;
                    wm_debug("State is now %s\n", $this->plugins['data'][$name]['active']);
                }
            }
        }
        wm_debug("Finished Initialising Plugins...\n");
    }

    /**
     * Create an array of all the nodes and links, mixed together.
     * readData() makes several passes through this list.
     *
     * @return array
     */
    private function buildAllItemsList()
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
        wm_debug("Preprocessing targets\n");

        foreach ($itemList as $oneMapItem) {
            if ($oneMapItem->isTemplate()) {
                continue;
            }

            $oneMapItem->prepareForDataCollection();
        }
    }

    /**
     * Keep track of the current minimum and maximum timestamp for collected data
     *
     * @param $dataTime
     */
    public function registerDataTime($dataTime)
    {
        if ($dataTime==0) {
            return;
        }

        if ($this->max_data_time == null || $dataTime > $this->max_data_time) {
            $this->max_data_time = $dataTime;
        }

        if ($this->min_data_time == null || $dataTime < $this->min_data_time) {
            $this->min_data_time = $dataTime;
        }
        wm_debug("Current DataTime MINMAX: " . $this->min_data_time . " -> " . $this->max_data_time . "\n");
    }

    private function readDataFromTargets($itemList)
    {
        wm_debug("======================================\n");
        wm_debug("Starting main collection loop\n");

        foreach ($itemList as $mapItem) {
            if ($mapItem->isTemplate()) {
                wm_debug("ReadData: Skipping $mapItem that looks like a template\n.");
                continue;
            }

            $mapItem->performDataCollection();

            // NOTE - this part still happens even if there were no targets
            $mapItem->aggregateDataResults();
            $mapItem->calculateScaleColours();

            unset($mapItem);
        }
    }

    private function prefetchPlugins()
    {
        // give all the plugins a chance to prefetch their results
        wm_debug("======================================\n");
        wm_debug("Starting prefetch\n");
        foreach ($this->plugins['data'] as $name => $pluginEntry) {
            $pluginEntry['object']->Prefetch($this);
        }
    }

    private function cleanupPlugins($type)
    {
        wm_debug("======================================\n");
        wm_debug("Starting cleanup\n");

        foreach ($this->plugins[$type] as $name => $pluginEntry) {
            $pluginEntry['object']->CleanUp($this);
        }
    }

    function readData()
    {
        // we skip readdata completely in sizedebug mode
        if ($this->sizedebug != 0) {
            wm_debug("Size Debugging is on. Skipping readData.\n");
            return;
        }

        wm_debug("======================================\n");
        wm_debug("ReadData: Updating link data for all links and nodes\n");

        $allMapItems = $this->buildAllItemsList();

       // $this->initialiseAllPlugins();

        // process all the targets and find a plugin for them
        $this->preProcessTargets($allMapItems);

        $this->prefetchPlugins();

        $this->readDataFromTargets($allMapItems);

        $this->cleanupPlugins('data');

        wm_debug("ReadData Completed.\n");
        wm_debug("------------------------------\n");

    }

    private function drawTimestamp($gdImage, $fontNumber, $colour, $which = "")
    {
        // add a timestamp to the corner, so we can tell if it's all being updated
        $fontObject = $this->fonts->getFont($fontNumber);

        switch ($which) {
            case "MIN":
                $stamp = strftime($this->minstamptext, $this->min_data_time);
                $pos_x = $this->mintimex;
                $pos_y = $this->mintimey;
                break;
            case "MAX":
                $stamp = strftime($this->maxstamptext, $this->max_data_time);
                $pos_x = $this->maxtimex;
                $pos_y = $this->maxtimey;
                break;
            default:
                $stamp = $this->datestamp;
                $pos_x = $this->timex;
                $pos_y = $this->timey;
                break;
        }

        list($boxwidth, $boxheight) = $fontObject->calculateImageStringSize($stamp);

        $x = $this->width - $boxwidth;
        $y = $boxheight;

        if (($pos_x != 0) && ($pos_y != 0)) {
            $x = $pos_x;
            $y = $pos_y;
        }

        $areaname = $which . "TIMESTAMP";
        $fontObject->drawImageString($gdImage, $x, $y, $stamp, $colour);

        $this->imap->addArea("Rectangle", $areaname, '', array($x, $y, $x + $boxwidth, $y - $boxheight));
        $this->imap_areas[] = $areaname;
    }

    private function drawTitle($gdImage, $fontNumber, $colour)
    {
        $string = $this->processString($this->title, $this);
        $fontObject = $this->fonts->getFont($fontNumber);

        if ($this->get_hint('screenshot_mode') == 1) {
            $string = WMUtility::stringAnonymise($string);
        }

        list($textWidth, $textHeight) = $fontObject->calculateImageStringSize($string);

        // XXX what is this supposed to do?
        $x = 10;
        $y = $this->titley - $textHeight;

        if (($this->titlex >= 0) && ($this->titley >= 0)) {
            $x = $this->titlex;
            $y = $this->titley;
        }

        $fontObject->drawImageString($gdImage, $x, $y, $string, $colour);

        $this->imap->addArea("Rectangle", "TITLE", '', array($x, $y, $x + $textWidth, $y - $textHeight));
        $this->imap_areas[] = 'TITLE';
    }

    /**
     * ReadConfig reads in either a file or part of a config and modifies the current map.
     *
     * Temporary new ReadConfig, using the new table of keywords
     * However, this also expects a bunch of other internal change (WMColour, WMScale etc)
     *
     * @param $input string Either a filename or a fragment of config in a string
     * @return bool indicates success or failure     *
     *
     */
    function ReadConfig($input)
    {
        $reader = new WeatherMapConfigReader($this);

        // check if $input is more than one line. if it is, it's a text of a config file
        // if it isn't, it's the filename

        if ((strchr($input, "\n") != false) || (strchr($input, "\r") != false)) {
            wm_debug("ReadConfig Detected that this is a config fragment.\n");
            // strip out any Windows line-endings that have gotten in here
            $input = str_replace("\r", "", $input);
            $lines = explode("\n", $input);
            $filename = "{text insert}";

            $reader->readConfigLines($lines);

        } else {
            wm_debug("ReadConfig Detected that this is a config filename.\n");
            $reader->readConfigFile($input);
            $this->configfile = $input;
        }

        $this->postReadConfigTasks();

        return (true);
    }

    function postReadConfigTasks()
    {
        if ($this->has_overlibs && $this->htmlstyle == 'static') {
            wm_warn("OVERLIBGRAPH is used, but HTMLSTYLE is static. This is probably wrong. [WMWARN41]\n");
        }

        $this->populateDefaultScales();
        $this->replicateScaleSettings();
        $this->buildZLayers();
        $this->resolveRelativePositions();
        $this->updateMaxValues();

        $this->initialiseAllPlugins();
        $this->runProcessorPlugins("pre");
    }

    // *************************************

    private function populateDefaultScales()
    {
        // load some default colouring, otherwise it all goes wrong

        wm_debug("Hello!\n");

        $count = $this->scales['DEFAULT']->spanCount();

        $this->scales['DEFAULT']->populateDefaultsIfNecessary();

        $this->scales['none'] = new WeatherMapScale("none", $this);

        if ($count == 0) {
            wm_debug("Adding default SCALE colour set (no SCALE lines seen).\n");
            $defaults = array(
                '0_0' => array(
                    'bottom' => 0,
                    'top' => 0,
                    'red1' => 192,
                    'green1' => 192,
                    'blue1' => 192
                ),
                '0_1' => array(
                    'bottom' => 0,
                    'top' => 1,
                    'red1' => 255,
                    'green1' => 255,
                    'blue1' => 255
                ),
                '1_10' => array(
                    'bottom' => 1,
                    'top' => 10,
                    'red1' => 140,
                    'green1' => 0,
                    'blue1' => 255
                ),
                '10_25' => array(
                    'bottom' => 10,
                    'top' => 25,
                    'red1' => 32,
                    'green1' => 32,
                    'blue1' => 255
                ),
                '25_40' => array(
                    'bottom' => 25,
                    'top' => 40,
                    'red1' => 0,
                    'green1' => 192,
                    'blue1' => 255
                ),
                '40_55' => array(
                    'bottom' => 40,
                    'top' => 55,
                    'red1' => 0,
                    'green1' => 240,
                    'blue1' => 0
                ),
                '55_70' => array(
                    'bottom' => 55,
                    'top' => 70,
                    'red1' => 240,
                    'green1' => 240,
                    'blue1' => 0
                ),
                '70_85' => array(
                    'bottom' => 70,
                    'top' => 85,
                    'red1' => 255,
                    'green1' => 192,
                    'blue1' => 0
                ),
                '85_100' => array(
                    'bottom' => 85,
                    'top' => 100,
                    'red1' => 255,
                    'green1' => 0,
                    'blue1' => 0
                )
            );

            foreach ($defaults as $key => $def) {
                $this->colours['DEFAULT'][$key] = $def;
                $this->colours['DEFAULT'][$key]['key'] = $key;
                $this->colours['DEFAULT'][$key]['c1'] = new WMColour($def['red1'], $def['green1'], $def['blue1']);
                $this->colours['DEFAULT'][$key]['c2'] = null;
                // $this->scalesseen++;
                $this->numscales['DEFAULT']++;
            }
            // we have a 0-0 line now, so we need to hide that.
            $this->add_hint("key_hidezero_DEFAULT", 1);
        } else {
            wm_debug("Already have %d scales, no defaults added.\n", $this->scales['DEFAULT']->spanCount());
        }
    }

    /**
     * Temporary function to bridge between the old and new
     * scale-worlds. Just until the ConfigReader updates these
     * directly.
     */
    private function replicateScaleSettings()
    {
        foreach ($this->scales as $scaleName => $scaleObject) {
            $scaleObject->keyoutlinecolour = $this->colourtable['KEYOUTLINE'];
            $scaleObject->keytextcolour = $this->colourtable['KEYTEXT'];
            $scaleObject->keybgcolour = $this->colourtable['KEYBG'];
            $scaleObject->keyfont = $this->fonts->getFont($this->keyfont);


            if ((isset($this->numscales[$scaleName])) && isset($this->keyx[$scaleName])) {
                $scaleObject->keypos = new WMPoint($this->keyx[$scaleName], $this->keyy[$scaleName]);
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
        wm_debug("Building cache of z-layers.\n");

        $allItems = $this->buildAllItemsList();

        foreach ($allItems as $item) {
            $zIndex = $item->getZIndex();
            $this->addItemToZLayer($item, $zIndex);
        }
        wm_debug("Found " . sizeof($this->seen_zlayers) . " z-layers including builtins (0,100).\n");
    }

    private function addItemToZLayer($item, $zIndex)
    {
        if (!isset($this->seen_zlayers[$zIndex]) || !is_array($this->seen_zlayers[$zIndex])) {
            $this->seen_zlayers[$zIndex] = array();
        }
        array_push($this->seen_zlayers[$zIndex], $item);
    }

    private function updateMaxValues()
    {
        wm_debug("Finalising bandwidth.\n");

        $allItems = $this->buildAllItemsList();

        foreach ($allItems as $item) {
            $item->updateMaxValues($this->kilo);
        }
    }

    public function runProcessorPlugins($stage = "pre")
    {
        wm_debug("Running $stage-processing plugins...\n");
        foreach ($this->plugins[$stage] as $name => $pluginEntry) {
            wm_debug("Running %s->run()\n", $name);
            $pluginEntry['object']->run($this);
        }
        wm_debug("Finished $stage-processing plugins...\n");
    }

    public function nodeExists($nodeName)
    {
        return array_key_exists($nodeName, $this->nodes);
    }

    public function linkExists($linkName)
    {
        return array_key_exists($linkName, $this->links);
    }

    private function resolveRelativePositions()
    {
        // calculate any relative positions here - that way, nothing else
        // really needs to know about them

        wm_debug("Resolving relative positions for NODEs...\n");
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

                wm_debug("Resolving relative position for $node to $anchorName\n");

                if (! $this->nodeExists($anchorName)) {
                    wm_warn("NODE " . $node->name . " has a relative position to an unknown node ($anchorName)! [WMWARN10]\n");
                    continue;
                }

                $anchorNode = $this->getNode($anchorName);
                wm_debug("Found anchor node: $anchorNode\n");

                // check if we are relative to another node which is in turn relative to something
                // we need to resolve that one before we can resolve this one!
                if (($anchorNode->isRelativePositioned()) && (!$anchorNode->isRelativePositionResolved())) {
                    wm_debug("Skipping unresolved relative_to. Let's hope it's not a circular one\n");
                    $nSkipped++;
                    continue;
                }

                if ($node->resolveRelativePosition($anchorNode)) {
                    $nChanged++;
                }
            }
            wm_debug("Relative Positions Cycle $iterations/$maxIterations - set $nChanged and Skipped $nSkipped for unresolved dependencies\n");
            $iterations--;
        } while (($nChanged > 0) && ($iterations > 0));

        if ($nSkipped > 0) {
            wm_warn("There are probably Circular dependencies in relative POSITION lines for $nSkipped nodes (or $maxIterations levels of relative positioning). [WMWARN11]\n");
        }
    }

    function writeDataFile($filename)
    {
        if ($filename != "") {
            $fileHandle = fopen($filename, 'w');

            if ($fileHandle) {
                foreach ($this->nodes as $node) {
                    if (!preg_match("/^::\s/", $node->name) && sizeof($node->targets) > 0) {
                        fputs($fileHandle, sprintf("N_%s\t%f\t%f\r\n", $node->name, $node->bandwidth_in, $node->bandwidth_out));
                    }
                }

                foreach ($this->links as $link) {
                    if (!preg_match("/^::\s/", $link->name) && sizeof($link->targets) > 0) {
                        fputs($fileHandle, sprintf("L_%s\t%f\t%f\r\n", $link->name, $link->bandwidth_in, $link->bandwidth_out));
                    }
                }

                fclose($fileHandle);
            }
        }
    }

    private function getConfigForPosition($keyword, $fieldnames, $object1, $object2)
    {
        $write = false;
        $string = $keyword;

        for ($i=0; $i<count($fieldnames); $i++) {
            $string .= " " . $object1->{$fieldnames[$i]};

            if ($object1->{$fieldnames[$i]} != $object2[$fieldnames[$i]]) {
                $write = true;
            }
        }
        $string .= "\n";

        if (! $write) {
            return "";
        }
        return $string;
    }

    function getConfig()
    {
        global $WEATHERMAP_VERSION;

        $output = "# Automatically generated by php-weathermap v$WEATHERMAP_VERSION\n\n";

        $output .= $this->fonts->getConfig();

        $basic_params = array(
            array('background','BACKGROUND',CONFIG_TYPE_LITERAL),
            array('width','WIDTH',CONFIG_TYPE_LITERAL),
            array('height','HEIGHT',CONFIG_TYPE_LITERAL),
            array('htmlstyle','HTMLSTYLE',CONFIG_TYPE_LITERAL),
            array('kilo','KILO',CONFIG_TYPE_LITERAL),
            array('keyfont','KEYFONT',CONFIG_TYPE_LITERAL),
            array('timefont','TIMEFONT',CONFIG_TYPE_LITERAL),
            array('titlefont','TITLEFONT',CONFIG_TYPE_LITERAL),
            array('title','TITLE',CONFIG_TYPE_LITERAL),
            array('htmloutputfile','HTMLOUTPUTFILE',CONFIG_TYPE_LITERAL),
            array('dataoutputfile','DATAOUTPUTFILE',CONFIG_TYPE_LITERAL),
            array('htmlstylesheet','HTMLSTYLESHEET',CONFIG_TYPE_LITERAL),
            array('imageuri','IMAGEURI',CONFIG_TYPE_LITERAL),
            array('imageoutputfile','IMAGEOUTPUTFILE',CONFIG_TYPE_LITERAL)
        );

        foreach ($basic_params as $param) {
            $field = $param[0];
            $keyword = $param[1];

            if ($this->$field != $this->inherit_fieldlist[$field]) {
                $type = $param[2];

                if ($type == CONFIG_TYPE_COLOR) {
                    $output.="$keyword " . render_colour($this->$field) . "\n";
                }
                if ($type == CONFIG_TYPE_LITERAL) {
                    $output.="$keyword " . $this->$field . "\n";
                }
            }
        }

        $output .= $this->getConfigForPosition("TIMEPOS", array("timex","timey","stamptext"), $this, $this->inherit_fieldlist);
        $output .= $this->getConfigForPosition("MINTIMEPOS", array("mintimex","mintimey","minstamptext"), $this, $this->inherit_fieldlist);
        $output .= $this->getConfigForPosition("MAXTIMEPOS", array("maxtimex","maxtimey","maxstamptext"), $this, $this->inherit_fieldlist);
        $output .= $this->getConfigForPosition("TITLEPOS", array("titlex","titley"), $this, $this->inherit_fieldlist);

        $output.="\n";

        foreach ($this->colours as $scalename => $colours) {
            // not all keys will have keypos but if they do, then all three vars should be defined
            if ((isset($this->keyx[$scalename])) && (isset($this->keyy[$scalename])) && (isset($this->keytext[$scalename]))
                && (($this->keytext[$scalename] != $this->inherit_fieldlist['keytext'])
                    || ($this->keyx[$scalename] != $this->inherit_fieldlist['keyx'])
                    || ($this->keyy[$scalename] != $this->inherit_fieldlist['keyy']))) {
                // sometimes a scale exists but without defaults. A proper scale object would sort this out...
                if ($this->keyx[$scalename] == '') {
                    $this->keyx[$scalename] = -1;
                }
                if ($this->keyy[$scalename] == '') {
                    $this->keyy[$scalename] = -1;
                }
                $output.="KEYPOS " . $scalename." ". $this->keyx[$scalename] . " " . $this->keyy[$scalename] . " " . $this->keytext[$scalename] . "\n";
            }

            if ((isset($this->keystyle[$scalename])) &&  ($this->keystyle[$scalename] != $this->inherit_fieldlist['keystyle']['DEFAULT'])) {
                $extra='';
                if ((isset($this->keysize[$scalename])) &&  ($this->keysize[$scalename] != $this->inherit_fieldlist['keysize']['DEFAULT'])) {
                    $extra = " ".$this->keysize[$scalename];
                }
                $output.="KEYSTYLE  " . $scalename." ". $this->keystyle[$scalename] . $extra . "\n";
            }
        }

        $output .= "# new colourtable stuff (duplicated above right now TODO)\n";
        foreach ($this->colourtable as $k => $c) {
            $output .= sprintf("%sCOLOR %s\n", $k, $c->asConfig());
        }
        $output .= "\n";

        foreach ($this->scales as $s) {
            $output .= $s->getConfig();
        }

        $output .= "\n";

        foreach ($this->hints as $hintname => $hint) {
            $output .= "SET $hintname $hint\n";
        }

        // this doesn't really work right, but let's try anyway
        if ($this->has_includes) {
            $output .= "\n# Included files\n";
            foreach ($this->included_files as $ifile) {
                $output .= "INCLUDE $ifile\n";
            }
        }

        $output.="\n# End of global section\n\n";

        $allMapItems = $this->buildAllItemsList();

        usort($allMapItems, array($this, 'getConfigSort'));

        foreach ($allMapItems as $mapItem) {
            if ($mapItem->getDefined() == $this->configfile) {
                if (!preg_match("/^::\s/", $mapItem->name)) {
                    $output .= $mapItem->getConfig();
                }
            }
        }

        $output .= "\n\n# That's All Folks!\n";

        return $output;
    }

    function getConfigSort($a, $b)
    {
        $type_diff = strcmp($a->my_type(), $b->my_type());
        $template_diff = strcmp(($a->isTemplate() ? 1 : 2), ($b->isTemplate() ? 1 : 2));
        $name_diff = strcmp($a->name, $b->name);

        // templates come first
        if ($template_diff != 0) {
            return $template_diff;
        }

        // DEFAULT before all else
        if ($a->name == "DEFAULT") {
            return 1;
        }

        if ($b->name == "DEFAULT") {
            return -1;
        }

        // NODEs before LINKs
        if ($type_diff != 0) {
            return -$type_diff;
        }

        // Then alpha for the rest
        return $name_diff;
    }

    function writeConfig($filename)
    {
        wm_debug("Writing config to $filename (read from $this->configfile)\n");

        $fileHandle = fopen($filename, "w");

        if ($fileHandle) {
            $output = $this->getConfig();
            fwrite($fileHandle, $output);
            fclose($fileHandle);
        } else {
            wm_warn("Couldn't open config file $filename for writing");
            return (false);
        }

        return (true);
    }

    // pre-allocate colour slots for the colours used by the arrows
    // this way, it's the pretty icons that suffer if there aren't enough colours, and
    // not the actual useful data
    // we skip any gradient scales
    private function preAllocateScaleColours($im, $refname = 'gdref1')
    {
        foreach ($this->colours as $scalename => $colours) {
            foreach ($colours as $key => $colour) {
                if ((!isset($this->colours[$scalename][$key]['c2'])) && (!isset($this->colours[$scalename][$key][$refname]))) {
                    $this->colours[$scalename][$key][$refname] = $this->colours[$scalename][$key]['c1']->gdAllocate($im);
                    wm_debug("AllocateScaleColours: %s/%s %s %s\n", $scalename, $refname, $key, $this->colours[$scalename][$key]['c1']);
                }
            }
        }
    }

    function drawMapImage($imageFileName = '', $thumbnailFileName = '', $thumbnailMaxSize = 250, $includeNodes = true, $showVIAOverlay = false, $showRelativeOverlay = false)
    {
        wm_debug("Trace: DrawMap()\n");

        wm_debug("Running Post-Processing Plugins...\n");
        foreach ($this->plugins['post'] as $pluginName => $pluginEntry) {
            wm_debug("Running $pluginName"."->run()\n");
            $pluginEntry['object']->run($this);
        }
        wm_debug("Finished Post-Processing Plugins...\n");

        wm_debug("=====================================\n");
        wm_debug("Start of Map Drawing\n");

        // if we're running tests, we force the time to a particular value,
        // so the output can be compared to a reference image more easily
        $testmode = intval($this->get_hint("testmode"));

        if ($testmode == 1) {
            $maptime = 1270813792;
            date_default_timezone_set('UTC');
        } else {
            $maptime = time();
        }
        $this->datestamp = strftime($this->stamptext, $maptime);

        // Create an imageRef to draw into
        $imageRef = $this->prepareOutputImage();

        // XXX - why is this ever needed outside this method?
        // $this->image = $image;

        // Now it's time to draw a map

        // do the node rendering stuff first, regardless of where they are actually drawn.
        // this is so we can get the size of the nodes, which links will need if they use offsets
        foreach ($this->nodes as $node) {
            // don't try and draw template nodes
            wm_debug("Pre-rendering ".$node->name." to get bounding boxes.\n");
            if (!$node->isTemplate()) {
                $node->preRender($imageRef, $this);
            }
        }

        $all_layers = array_keys($this->seen_zlayers);
        sort($all_layers);

        foreach ($all_layers as $z) {
            $z_items = $this->seen_zlayers[$z];
            wm_debug("Drawing layer $z\n");
            // all the map 'furniture' is fixed at z=1000
            if ($z==1000) {
                // TODO - these legends should just be other objects in the zlayer, not special cases
                foreach ($this->scales as $scaleName => $scaleObject) {
                    wm_debug("Drawing KEY for $scaleName if necessary.\n");

                    // the new scale object draws its own legend
                    $this->scales[$scaleName]->drawLegend($imageRef);
                }

                // TODO - these textitems should just be other objects in the zlayer, not special cases
                $this->drawTimestamp($imageRef, $this->timefont, $this->colourtable['TIME']->gdallocate($imageRef));
                if (! is_null($this->min_data_time)) {
                    $this->drawTimestamp($imageRef, $this->timefont, $this->colourtable['TIME']->gdallocate($imageRef), "MIN");
                    $this->drawTimestamp($imageRef, $this->timefont, $this->colourtable['TIME']->gdallocate($imageRef), "MAX");
                }
                $this->drawTitle($imageRef, $this->titlefont, $this->colourtable['TITLE']->gdallocate($imageRef));
            }

            if (is_array($z_items)) {
                foreach ($z_items as $it) {
                    $it->preChecks($this);
                    $it->preCalculate($this);
                    $it->draw($imageRef, $this);

                    foreach ($it->getImageMapAreas() as $name => $area) {
                        $this->imap->addArea($area);
                    }
                }
            }
        }

        // $overlay = myimagecolorallocate($image, 200, 0, 0);

        // for the editor, we can optionally overlay some other stuff
        if ($showRelativeOverlay) {
            $this->drawRelativePositionOverlay($imageRef, $this->selected);
        }

        if ($showVIAOverlay) {
            $this->drawViaOverlay($imageRef, $this->selected);
        }

        // Ready to output the results...

        if ($imageFileName == 'null') {
            // do nothing at all - we just wanted the HTML AREAs for the editor or HTML output
        } else {
            // write to the standard output (for the editor)
            if ($imageFileName == '') {
                imagepng($imageRef);
            } else {
                // write to a file
                $this->writeImageFile($imageFileName, $imageRef);
                $this->createThumbnailFile($thumbnailFileName, $imageRef, $thumbnailMaxSize);
            }
        }

        imagedestroy($imageRef);
    }

    // if one is specified, and we can, write a thumbnail too
    protected function createThumbnailFile($outputFileName, $sourceImageRef, $maximumDimension)
    {
        wm_debug("Writing thumbnail to %s\n", $outputFileName);

        if (!function_exists('imagecopyresampled')) {
            wm_warn("Skipping thumbnail creation, since we don't have the necessary function. [WMWARN17]");
            return;
        }

        if ($outputFileName == '') {
            return;
        }

        if ($this->width > $this->height) {
            $scaleFactor = ($maximumDimension / $this->width);
        } else {
            $scaleFactor = ($maximumDimension / $this->height);
        }

        $this->thumb_width = $this->width * $scaleFactor;
        $this->thumb_height = $this->height * $scaleFactor;

        wm_debug("Creating thumbnail %dx%d...(factor was %f)\n", $this->thumb_width, $this->thumb_height, $scaleFactor);
        $thumbImageRef = imagecreatetruecolor($this->thumb_width, $this->thumb_height);
        imagecopyresampled($thumbImageRef, $sourceImageRef, 0, 0, 0, 0, $this->thumb_width, $this->thumb_height, $this->width, $this->height);
        $result = imagepng($thumbImageRef, $outputFileName);
        imagedestroy($thumbImageRef);

        if ($result !== false) {
            return;
        }

        if (file_exists($outputFileName)) {
            wm_warn("Failed to overwrite existing thumbnail image file $outputFileName - permissions of existing file are wrong? [WMWARN15]");
        } else {
            wm_warn("Failed to create thumbnail image file $outputFileName - permissions of output directory are wrong? [WMWARN16]");
        }
    }

    /**
     * So that memory is deallocated correctly, go through and remove all the
     * references to other objects. The PHP GC doesn't deal with circular
     * references, so this stops the memory usage from ballooning up.
     */
    function cleanUp()
    {
        parent::cleanUp();

        $all_layers = array_keys($this->seen_zlayers);

        foreach ($all_layers as $z) {
            $this->seen_zlayers[$z] = null;
        }

        foreach ($this->links as $link) {
            $link->cleanUp();
            unset($link);
        }

        foreach ($this->nodes as $node) {
            // destroy all the images we created, to prevent memory leaks
            $node->cleanUp();
            unset($node);
        }

        // Clear up the other random hashes of information
        $this->dsinfocache = null;
        $this->colourtable = null;
        $this->usage_stats = null;
        $this->scales = null;
    }

    function processOverlib($mapItem, $dirs, $prefix)
    {
        // skip all this if it's a template node
        if ($mapItem->isTemplate()) {
            return;
        }

        $type = $mapItem->my_type();

        // find the middle of the map
        $centre_x = $this->width / 2;
        $centre_y = $this->height / 2;

        if ($type=='NODE') {
            $mid_x = $mapItem->x;
            $mid_y = $mapItem->y;
        }

        if ($type=='LINK') {
            $a_x = $this->nodes[$mapItem->a->name]->x;
            $a_y = $this->nodes[$mapItem->a->name]->y;

            $b_x = $this->nodes[$mapItem->b->name]->x;
            $b_y = $this->nodes[$mapItem->b->name]->y;

            $mid_x=($a_x + $b_x) / 2;
            $mid_y=($a_y + $b_y) / 2;
        }
        $left="";
        $above="";
        $img_extra = "";

        if ($mapItem->overlibwidth != 0) {
            $left="WIDTH," . $mapItem->overlibwidth . ",";
            $img_extra .= " WIDTH=$mapItem->overlibwidth";

            if ($mid_x > $centre_x) {
                $left.="LEFT,";
            }
        }

        if ($mapItem->overlibheight != 0) {
            $above="HEIGHT," . $mapItem->overlibheight . ",";
            $img_extra .= " HEIGHT=$mapItem->overlibheight";

            if ($mid_y > $centre_y) {
                $above.="ABOVE,";
            }
        }

        foreach ($dirs as $dir => $parts) {
            $caption = ($mapItem->overlibcaption[$dir] != '' ? $mapItem->overlibcaption[$dir] : $mapItem->name);
            $caption = $this->processString($caption, $mapItem);

            $overlibhtml = "onmouseover=\"return overlib('";

            $index = 0;
            if (sizeof($mapItem->overliburl[$dir]) > 0) {
                foreach ($mapItem->overliburl[$dir] as $url) {
                    if ($index>0) {
                        $overlibhtml .= '&lt;br /&gt;';
                    }
                    $overlibhtml .= "&lt;img $img_extra src=" . $this->processString($url, $mapItem) . "&gt;";
                    $index++;
                }
            }

            if (trim($mapItem->notestext[$dir]) != '') {
                # put in a linebreak if there was an image AND notes
                if ($index>0) {
                    $overlibhtml .= '&lt;br /&gt;';
                }
                $note = $this->processString($mapItem->notestext[$dir], $mapItem);
                $note = htmlspecialchars($note, ENT_NOQUOTES);
                $note=str_replace("'", "\\&apos;", $note);
                $note=str_replace('"', "&quot;", $note);
                $overlibhtml .= $note;
            }
            $overlibhtml .= "',DELAY,250,${left}${above}CAPTION,'" . $caption . "');\"  onmouseout=\"return nd();\"";

            foreach ($parts as $part) {
                $areaname = $type.":" . $prefix . $mapItem->id. ":" . $part;

                $this->imap->setProp("extrahtml", $overlibhtml, $areaname);
            }
        }
    }

    function calculateImageMap()
    {
        wm_debug("Trace: calculateImageMap()\n");

        $allItems = $this->buildAllItemsList();

        // loop through everything. Figure out along the way if it's a node or a link

        foreach ($allItems as $mapItem) {
            $type = $mapItem->my_type();
            $prefix = substr($type, 0, 1);

            $dirs = array();
            if ($type == 'LINK') {
                $dirs = array(IN=>array(0, 2), OUT=>array(1, 3));
            }
            if ($type == 'NODE') {
                $dirs = array(IN=>array(0, 1, 2, 3));
            }

            // check to see if any of the relevant things have a value
            $change = "";
            foreach ($dirs as $d => $parts) {
                $change .= join('', $mapItem->overliburl[$d]);
                $change .= $mapItem->notestext[$d];
            }

            if ($this->htmlstyle == "overlib") {
                $this->processOverlib($mapItem, $dirs, $prefix);
            }

            // now look at infourls
            foreach ($dirs as $dir => $parts) {
                foreach ($parts as $part) {
                    $areaname = $type.":" . $prefix . $mapItem->id. ":" . $part;

                    if (($this->htmlstyle != 'editor') && ($mapItem->infourl[$dir] != '')) {
                        $this->imap->setProp("href", $this->processString($mapItem->infourl[$dir], $mapItem), $areaname);
                    }
                }
            }
        }

    }

    function asJS()
    {
        $javascript = '';

        $javascript .= "var Links = new Array();\n";
        $javascript .= "var LinkIDs = new Array();\n";

        foreach ($this->links as $link) {
            $javascript.=$link->asJS();
        }

        $javascript .= "var Nodes = new Array();\n";
        $javascript .= "var NodeIDs = new Array();\n";

        foreach ($this->nodes as $node) {
            $javascript.=$node->asJS();
        }


        $mapName = array_pop(explode("/", $this->configfile));

        $javascript .= "var Map = {\n";
        $javascript .= sprintf('  "file": %s,', WMUtility::jsEscape($mapName));
        $javascript .= sprintf('  "width": %s,', WMUtility::jsEscape($this->width));
        $javascript .= sprintf('  "height": %s,', WMUtility::jsEscape($this->height));
        $javascript .= sprintf('  "title": %s,', WMUtility::jsEscape($this->title));
        $javascript .= "\n};\n";

        return $javascript;
    }

    // This method MUST run *after* DrawMap. It relies on DrawMap to call the map-drawing bits
    // which will populate the ImageMap with regions.
    //
    // imagemapname is a parameter, so we can stack up several maps in the Cacti plugin with their own imagemaps
    function makeHTML($imagemapname = "weathermap_imap")
    {
        wm_debug("Trace: MakeHTML()\n");
        // calculateImageMap fills in the ImageMap info, ready for the HTML to be created.
        $this->calculateImageMap();

        $html='';

        $html .= '<div class="weathermapimage" style="margin-left: auto; margin-right: auto; width: '.$this->width.'px;" >';
        if ($this->imageuri != '') {
            $html.=sprintf(
                '<img id="wmapimage" src="%s" width="%d" height="%d" style="border: 0;" usemap="#%s"',
                $this->imageuri,
                $this->width,
                $this->height,
                $imagemapname
            );
            //$html .=  'alt="network weathermap" ';
            $html .= '/>';
        } else {
            $html.=sprintf(
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

        return ($html);
    }

    function generateSortedImagemap($imagemapname)
    {
        $html = "\n" . '<map name="' . $imagemapname . '" id="' . $imagemapname . '">';

        $all_layers = array_keys($this->seen_zlayers);
        rsort($all_layers);

        wm_debug("Starting to dump imagemap in reverse Z-order...\n");
        // this is not precisely efficient, but it'll get us going
        // XXX - get Imagemap to store Z order, or map items to store the imagemap
        foreach ($all_layers as $z) {
            wm_debug("Writing HTML for layer $z\n");
            $z_items = $this->seen_zlayers[$z];
            if (is_array($z_items)) {
                wm_debug("   Found things for layer $z\n");

                // at z=1000, the legends and timestamps live
                if ($z == 1000) {
                    wm_debug("     Builtins fit here.\n");
                    // $html .= $this->imap->subHTML("LEGEND:",true,($this->context != 'editor'));
                    // $html .= $this->imap->subHTML("TIMESTAMP",true,($this->context != 'editor'));
                    foreach ($this->imap_areas as $areaname) {
                        // skip the linkless areas if we are not in the editor - they're redundant
                        $html .= $this->imap->exactHTML($areaname, true, ($this->context != 'editor'));
                    }
                }

                // we reverse the array for each zlayer so that the imagemap order
                // will match up with the draw order (last drawn should be first hit)
                foreach (array_reverse($z_items) as $it) {
                    if ($it->name != 'DEFAULT' && $it->name != ":: DEFAULT ::") {
                        foreach ($it->getImageMapAreas() as $area) {
                            // skip the linkless areas if we are not in the editor - they're redundant
                            // $html .= $this->imap->exactHTML($areaname, true, ($this->context != 'editor'));
                            $html .= $area->asHTML();
                        }
                    }
                }
            }
        }

        $html .= "</map>\n";

        return($html);
    }

    public function getValue($name)
    {
        wm_debug("Fetching %s\n", $name);
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        throw new WMException("NoSuchProperty");
    }

    /**
     * @param $imageRef
     * @param $overlayColour
     * @return mixed
     */
    private function drawRelativePositionOverlay($imageRef, $overlayColour)
    {
        foreach ($this->nodes as $node) {
            if (! $node->isTemplate() && $node->isRelativePositioned()) {
                $rel_x = $this->nodes[$node->relative_to]->x;
                $rel_y = $this->nodes[$node->relative_to]->y;
                imagearc($imageRef, $node->x, $node->y, 15, 15, 0, 360, $overlayColour);
                imagearc($imageRef, $node->x, $node->y, 16, 16, 0, 360, $overlayColour);

                imageline($imageRef, $node->x, $node->y, $rel_x, $rel_y, $overlayColour);
            }
        }
    }

    /**
     * @param $imageRef
     * @param $overlayColour
     */
    private function drawViaOverlay($imageRef, $overlayColour)
    {
        foreach ($this->links as $link) {
            foreach ($link->vialist as $via) {
                if (isset($via[2])) {
                    $x = $this->nodes[$via[2]]->x + $via[0];
                    $y = $this->nodes[$via[2]]->y + $via[1];
                } else {
                    $x = $via[0];
                    $y = $via[1];
                }
                imagearc($imageRef, $x, $y, 10, 10, 0, 360, $overlayColour);
                imagearc($imageRef, $x, $y, 12, 12, 0, 360, $overlayColour);
            }
        }

        // also (temporarily) draw all named offsets for each node
        foreach ($this->nodes as $node) {
            if (! $node->isTemplate()) {
                foreach ($node->named_offsets as $offsets) {
                    $x = $node->x + $offsets[0];
                    $y = $node->y + $offsets[1];
                    imagearc($imageRef, $x, $y, 4, 4, 0, 360, $overlayColour);
                }
            }
        }
    }

    /**
     * @param $filename
     * @param $imageRef
     */
    private function writeImageFile($filename, $imageRef)
    {
        $result = false;
        $functions = true;

        if (function_exists('imagejpeg') && preg_match('/\.jpg/i', $filename)) {
            wm_debug("Writing JPEG file to $filename\n");
            $result = imagejpeg($imageRef, $filename);
        } elseif (function_exists('imagegif') && preg_match('/\.gif/i', $filename)) {
            wm_debug("Writing GIF file to $filename\n");
            $result = imagegif($imageRef, $filename);
        } elseif (function_exists('imagepng') && preg_match('/\.png/i', $filename)) {
            wm_debug("Writing PNG file to $filename\n");
            $result = imagepng($imageRef, $filename);
        } else {
            wm_warn("Failed to write map image. No function existed for the image format you requested ($filename). [WMWARN12]\n");
            $functions = false;
        }

        if (($result === false) && ($functions === true)) {
            if (file_exists($filename)) {
                wm_warn("Failed to overwrite existing image file $filename - permissions of existing file are wrong? [WMWARN13]");
            } else {
                wm_warn("Failed to create image file $filename - permissions of output directory are wrong? [WMWARN14]");
            }
        }
    }

    /**
     * @return array
     * @throws WMException
     */
    private function prepareOutputImage()
    {
        $bgImageRef = $this->loadBackgroundImage($this->background);

        $outputImageRef = imagecreatetruecolor($this->width, $this->height);

        if (!$outputImageRef) {
            wm_warn("Couldn't create output image in memory (" . $this->width . "x" . $this->height . ").");
            throw new WMException("ImageCreationError");
        }

        ImageAlphaBlending($outputImageRef, true);
        $this->enableAntiAliasing($outputImageRef);

        // by here, we should have a valid image handle

        $this->selected = myimagecolorallocate($outputImageRef, 255, 0, 0); // for selections in the editor

        if ($bgImageRef) {
            imagecopy($outputImageRef, $bgImageRef, 0, 0, 0, 0, $this->width, $this->height);
            imagedestroy($bgImageRef);
        } else {
            // fill with background colour anyway, since the background image failed to load
            imagefilledrectangle($outputImageRef, 0, 0, $this->width, $this->height, $this->colourtable['BG']->gdallocate($outputImageRef));
        }

        return $outputImageRef;
    }

    /**
     * @return null|resource
     */
    private function loadBackgroundImage($background)
    {
        $bgImageRef = null;

        // do the basic prep work
        if ($background != '') {
            if (is_readable($background)) {
                $bgImageRef = imagecreatefromfile($this->background);

                if (!$bgImageRef) {
                    wm_warn("Failed to open background image.  One possible reason: Is your BACKGROUND really a PNG?\n");
                } else {
                    $this->width = imagesx($bgImageRef);
                    $this->height = imagesy($bgImageRef);
                    wm_debug("Resizing map to background image (%dx%d)\n", $this->width, $this->height);
                }
            } else {
                wm_warn("Your background image file could not be read. Check the filename, and permissions, for $background\n");
            }
        }
        return $bgImageRef;
    }

    /**
     * @param $outputImageRef
     */
    private function enableAntiAliasing($outputImageRef)
    {
        if ($this->get_hint("antialias") == 1) {
            // Turn on anti-aliasing if it exists and it was requested
            if (function_exists("imageantialias")) {
                imageantialias($outputImageRef, true);
            }
        }
    }

    private function createDefaultLinks()
    {
        wm_debug("Creating ':: DEFAULT ::' DEFAULT LINK\n");

        // these two are used for default settings
        $deflink = new WeatherMapLink(":: DEFAULT ::", ":: DEFAULT ::", $this);
        $this->addLink($deflink);
//        $deflink->name = ":: DEFAULT ::";
//        $deflink->template = ":: DEFAULT ::";
//        $deflink->reset($this);

//        $this->links[':: DEFAULT ::'] = & $deflink;

        wm_debug("Creating actual DEFAULT LINK from :: DEFAULT ::\n");
        $deflink2 = new WeatherMapLink("DEFAULT", ":: DEFAULT ::", $this);
        $this->addLink($deflink2);
//        $deflink2->name = "DEFAULT";
//        $deflink2->template = ":: DEFAULT ::";
//        $deflink2->reset($this);

//        $this->links['DEFAULT'] = &$deflink2;
    }

    private function createDefaultNodes()
    {
        wm_debug("Creating ':: DEFAULT ::' DEFAULT NODE\n");
        $defnode = new WeatherMapNode(":: DEFAULT ::", ":: DEFAULT ::", $this);
        $this->addNode($defnode);
       // $defnode->name = ":: DEFAULT ::";
       // $defnode->template = ":: DEFAULT ::";
       // $defnode->reset($this);

//        $this->nodes[':: DEFAULT ::'] = &$defnode;

        // ************************************
        // now create the DEFAULT link and node, based on those.
        // these can be modified by the user, but their template (and therefore comparison in WriteConfig) is ':: DEFAULT ::'
        wm_debug("Creating actual DEFAULT NODE from :: DEFAULT ::\n");
        $defnode2 = new WeatherMapNode("DEFAULT", ":: DEFAULT ::", $this);
        $this->addNode($defnode2);
//        $defnode2->name = "DEFAULT";
//        $defnode2->template = ":: DEFAULT ::";
//        $defnode2->reset($this);

//        $this->nodes['DEFAULT'] = &$defnode2;
    }
}

// vim:ts=4:sw=4:
