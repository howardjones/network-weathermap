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
    var $min_ds_time;
    var $max_ds_time;
    var $background;
    var $htmlstyle;
    var $imap;
    var $colours;
    var $configfile;
    var $imagefile,
        $imageuri;
    var $rrdtool;
    var $title,
        $titlefont;
    var $kilo;
    var $sizedebug,
        $widthmod,
        $debugging;
    var $linkfont,
        $nodefont,
        $keyfont,
        $timefont;
    var $timex,
        $timey;
    var $width,
        $height;
    var $keyx,
        $keyy, $keyimage;
    var $titlex,
        $titley;
    var $keytext,
        $stamptext, $datestamp;
    var $min_data_time, $max_data_time;
    var $htmloutputfile,
        $imageoutputfile;
    var $dataoutputfile;
    var $htmlstylesheet;
    var $defaultlink,
        $defaultnode;
    var $need_size_precalc;
    var $keystyle, $keysize;
    var $rrdtool_check;
    var $inherit_fieldlist;
    var $mintimex, $maxtimex;
    var $mintimey, $maxtimey;
    var $minstamptext, $maxstamptext;
    var $context;
    var $cachefolder, $mapcache, $cachefile_version;
    var $name;
    var $black,
        $white,
        $grey,
        $selected;
    var $scalesseen;

    var $datasourceclasses;
    var $preprocessclasses;
    var $postprocessclasses;
    var $activedatasourceclasses;
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

    var $config = array();
    var $runtime = array();

    function WeatherMap()
    {
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
            'fonts' => null,

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

        $this->reset();

    }

    function my_type()
    {
        return "MAP";
    }

    function reset()
    {
        $this->scalesseen = 0;
        $this->next_id = 100;

        foreach (array_keys($this->inherit_fieldlist) as $fld) {
            $this->$fld = $this->inherit_fieldlist[$fld];
        }

        $this->min_ds_time = null;
        $this->max_ds_time = null;

        $this->need_size_precalc = false;

        $this->nodes = array(); // an array of WeatherMapNodes
        $this->links = array(); // an array of WeatherMapLinks

        // these are the default defaults
        // by putting them into a normal object, we can use the
        // same code for writing out LINK DEFAULT as any other link.
        wm_debug("Creating ':: DEFAULT ::' DEFAULT LINK\n");

        // these two are used for default settings
        $deflink = new WeatherMapLink;
        $deflink->name = ":: DEFAULT ::";
        $deflink->template = ":: DEFAULT ::";
        $deflink->reset($this);

        $this->links[':: DEFAULT ::'] = & $deflink;

        wm_debug("Creating ':: DEFAULT ::' DEFAULT NODE\n");
        $defnode = new WeatherMapNode;
        $defnode->name = ":: DEFAULT ::";
        $defnode->template = ":: DEFAULT ::";
        $defnode->reset($this);

        $this->nodes[':: DEFAULT ::'] = & $defnode;

        $this->scales = array();
        $this->scales['DEFAULT'] = new WeatherMapScale("DEFAULT", $this);

        $this->colourtable = array();

        // ************************************
        // now create the DEFAULT link and node, based on those.
        // these can be modified by the user, but their template (and therefore comparison in WriteConfig) is ':: DEFAULT ::'
        wm_debug("Creating actual DEFAULT NODE from :: DEFAULT ::\n");
        $defnode2 = new WeatherMapNode;
        $defnode2->name = "DEFAULT";
        $defnode2->template = ":: DEFAULT ::";
        $defnode2->reset($this);

        $this->nodes['DEFAULT'] = & $defnode2;

        wm_debug("Creating actual DEFAULT LINK from :: DEFAULT ::\n");
        $deflink2 = new WeatherMapLink;
        $deflink2->name = "DEFAULT";
        $deflink2->template = ":: DEFAULT ::";
        $deflink2->reset($this);

        $this->links['DEFAULT'] = & $deflink2;

        // for now, make the old defaultlink and defaultnode work too.
        //                $this->defaultlink = $this->links['DEFAULT'];
        //                $this->defaultnode = $this->nodes['DEFAULT'];

        assert('is_object($this->nodes[":: DEFAULT ::"])');
        assert('is_object($this->links[":: DEFAULT ::"])');
        assert('is_object($this->nodes["DEFAULT"])');
        assert('is_object($this->links["DEFAULT"])');

        // ************************************

        $this->imap = new HTML_ImageMap('weathermap');
        $this->colours = array();

        wm_debug("Adding default map colour set.\n");
        $defaults = array(
            'KEYTEXT' => array('bottom' => -2, 'top' => -1, 'red1' => 0, 'green1' => 0, 'blue1' => 0, 'special' => 1),
            'KEYOUTLINE' => array('bottom' => -2, 'top' => -1, 'red1' => 0, 'green1' => 0, 'blue1' => 0, 'special' => 1),
            'KEYBG' => array('bottom' => -2, 'top' => -1, 'red1' => 255, 'green1' => 255, 'blue1' => 255, 'special' => 1),
            'BG' => array('bottom' => -2, 'top' => -1, 'red1' => 255, 'green1' => 255, 'blue1' => 255, 'special' => 1),
            'TITLE' => array('bottom' => -2, 'top' => -1, 'red1' => 0, 'green1' => 0, 'blue1' => 0, 'special' => 1),
            'TIME' => array('bottom' => -2, 'top' => -1, 'red1' => 0, 'green1' => 0, 'blue1' => 0, 'special' => 1)
        );

        foreach ($defaults as $key => $def) {
            $this->colours['DEFAULT'][$key] = $def;
            $this->colourtable[$key] = new WMColour($def['red1'], $def['green1'], $def['blue1']);
        }

        $this->configfile = '';
        $this->imagefile = '';
        $this->imageuri = '';

        $this->fonts = new WMFontTable();
        $this->fonts->init();

        $this->loadPlugins('data', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'datasources');
        $this->loadPlugins('pre', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'pre');
        $this->loadPlugins('post', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'post');

        wm_debug("WeatherMap class Reset() complete\n");
    }

    function myimagestring($image, $fontnumber, $x, $y, $string, $colour, $angle = 0)
    {
        $fontObject = $this->fonts->getFont($fontnumber);
        return $fontObject->drawImageString($image, $x, $y, $string, $colour, $angle);

    }

    function myimagestringsize($fontNumber, $string)
    {
        $fontObject = $this->fonts->getFont($fontNumber);
        return $fontObject->calculateImageStringSize($string);
    }

    function processString($input, &$context, $include_notes = true, $multiline = false)
    {
        if ($input === '') {
            return '';
        }

        // don't bother with all this regexp rubbish if there's nothing to match
        if (false === strpos($input, "{")) {
            return $input;
        }

        $context_type = strtolower($context->my_type());

        if ($context_type != "map") {
            $context_description = $context_type . ":" . $context->name;
        } else {
            $context_description = $context_type;
        }

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
        }

        // check if we can now quit early before the regexp stuff
        if (false === strpos($input, "{")) {
            return $input;
        }

        $output = $input;

        while (preg_match('/(\{(?:node|map|link)[^}]+\})/', $input, $matches)) {
            $value = "[UNKNOWN]";
            $format = "";
            $key = $matches[1];

            if (preg_match('/\{(node|map|link):([^}]+)\}/', $key, $matches)) {
                $type = $matches[1];
                $args = $matches[2];

                if ($type == 'map') {
                    $the_item = $this;
                    if (preg_match('/map:([^:]+):*([^:]*)/', $args, $matches)) {
                        $args = $matches[1];
                        $format = $matches[2];
                    }
                }

                if (($type == 'link') || ($type == 'node')) {
                    if (preg_match("/([^:]+):([^:]+):*([^:]*)/", $args, $matches)) {
                        $itemname = $matches[1];
                        $args = $matches[2];
                        $format = $matches[3];

                        $the_item = null;
                        if (($itemname == "this") && ($type == strtolower($context->my_type()))) {
                            $the_item = $context;
                        } elseif (strtolower($context->my_type()) == "link" && $type == 'node' && ($itemname == '_linkstart_' || $itemname == '_linkend_')) {
                            // this refers to the two nodes at either end of this link
                            if ($itemname == '_linkstart_') {
                                $the_item = $context->a;
                            }

                            if ($itemname == '_linkend_') {
                                $the_item = $context->b;
                            }
                        } elseif (($itemname == "parent") && ($type == strtolower($context->my_type())) && ($type == 'node') && ($context->relative_to != '')) {
                            $the_item = $this->nodes[$context->relative_to];
                        } else {
                            if (($type == 'link') && isset($this->links[$itemname])) {
                                $the_item = $this->links[$itemname];
                            }
                            if (($type == 'node') && isset($this->nodes[$itemname])) {
                                $the_item = $this->nodes[$itemname];
                            }
                        }
                    }
                }

                if (is_null($the_item)) {
                    wm_warn("ProcessString: $key refers to unknown item (context is $context_description) [WMWARN05]\n");
                } else {
                    wm_debug("ProcessString: Found appropriate item: " . get_class($the_item) . " " . $the_item->name . "\n");

                    // SET and notes have precedent over internal properties
                    // this is my laziness - it saves me having a list of reserved words
                    // which are currently used for internal props. You can just 'overwrite' any of them.
                    if (array_key_exists($args, $the_item->hints)) {
                        $value = $the_item->hints[$args];
                        wm_debug("ProcessString: used hint\n");
                    } elseif ($include_notes && array_key_exists($args, $the_item->notes)) {
                        // for some things, we don't want to allow notes to be considered.
                        // mainly - TARGET (which can define command-lines), shouldn't be
                        // able to get data from uncontrolled sources (i.e. data sources rather than SET in config files).
                        $value = $the_item->notes[$args];
                        wm_debug("ProcessString: used note\n");
                    } elseif (property_exists($the_item, $args)) {
                        // REQUIRES PHP 5.1.0+ or PHP_Compat
                        $value = $the_item->$args;
                        wm_debug("ProcessString: used internal property\n");
                    } else {
                        wm_debug("ProcessString: fell off the bottom.\n");
                    }
                }
            }

            // format, and sanitise the value string here, before returning it

            // if ($value===null) $value='null';

            wm_debug("ProcessString: replacing %s with %s \n", $key, $value);

            if ($format != '') {
                $value = wmSprintf($format, $value, $this->kilo);
                wm_debug("ProcessString: formatted $format to $value\n");
            }

            $input = str_replace($key, '', $input);
            $output = str_replace($key, $value, $output);
        }
        return ($output);
    }

    function populateRandomData()
    {
        foreach ($this->links as $link) {
            $this->links[$link->name]->bandwidth_in = rand(0, $link->max_bandwidth_in);
            $this->links[$link->name]->bandwidth_out = rand(0, $link->max_bandwidth_out);
        }
    }

    function loadPlugins($type = "data", $dir = "lib/datasources")
    {
        wm_debug("Beginning to load $type plugins from $dir\n");

        if (!file_exists($dir)) {
            $dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . $dir;
            wm_debug("Relative path didn't exist. Trying $dir\n");
        }
        $dh = @opendir($dir);

        if (!$dh) { // try to find it with the script, if the relative path fails
            $srcdir = substr($_SERVER['argv'][0], 0, strrpos($_SERVER['argv'][0], DIRECTORY_SEPARATOR));
            $dh = opendir($srcdir . DIRECTORY_SEPARATOR . $dir);
            if ($dh) {
                $dir = $srcdir . DIRECTORY_SEPARATOR . $dir;
            }
        }

        if ($dh) {
            while ($file = readdir($dh)) {
                $realfile = $dir . DIRECTORY_SEPARATOR . $file;

                if (is_file($realfile) && preg_match('/\.php$/', $realfile)) {
                    wm_debug("Loading $type Plugin class from $file\n");

                    include_once($realfile);
                    $class = preg_replace("/\.php$/", "", $file);
                    if ($type == 'data') {
                        $this->datasourceclasses [$class] = $class;
                        $this->activedatasourceclasses[$class] = 1;
                    }
                    if ($type == 'pre') {
                        $this->preprocessclasses [$class] = $class;
                    }
                    if ($type == 'post') {
                        $this->postprocessclasses [$class] = $class;
                    }

                    wm_debug("Loaded $type Plugin class $class from $file\n");
                    $this->plugins[$type][$class] = new $class;
                    if (!isset($this->plugins[$type][$class])) {
                        wm_debug("** Failed to create an object for plugin $type/$class\n");
                    } else {
                        wm_debug("Instantiated $class.\n");
                    }
                } else {
                    wm_debug("Skipping $file\n");
                }
            }
        } else {
            wm_warn("Couldn't open $type Plugin directory ($dir). Things will probably go wrong. [WMWARN06]\n");
        }
    }

    function initialiseDatasourcePlugins()
    {
        wm_debug("Running Init() for Data Source Plugins...\n");
        foreach ($this->datasourceclasses as $ds_class) {
            // make an instance of the class
            # $dsplugins[$ds_class] = new $ds_class;
            $this->plugins['data'][$ds_class] = new $ds_class;
            wm_debug("Running $ds_class" . "->Init()\n");
            assert('isset($this->plugins["data"][$ds_class])');

            $ret = $this->plugins['data'][$ds_class]->Init($this);

            if (!$ret) {
                wm_debug("Removing $ds_class from Data Source list, since Init() failed\n");
                $this->activedatasourceclasses[$ds_class] = 0;
            }
        }
        wm_debug("Finished Initialising Plugins...\n");
    }

    function preProcessTargets()
    {
        wm_debug("Preprocessing targets\n");

        $allitems = array(&$this->links, &$this->nodes);
        reset($allitems);

        wm_debug("Preprocessing targets\n");

        while (list($kk,) = each($allitems)) {
            unset($objects);
            $objects = & $allitems[$kk];

            reset($objects);
            while (list($k,) = each($objects)) {
                unset($myobj);
                $myobj = & $objects[$k];

                $type = $myobj->my_type();
                $name = $myobj->name;

                if (!$myobj->isTemplate()) {
                    if (count($myobj->targets) > 0) {
                        $tindex = 0;
                        foreach ($myobj->targets as $target) {
                            wm_debug("ProcessTargets: New Target: $target[4]\n");
                            // processstring won't use notes (only hints) for this string

                            $targetstring = $this->processString($target[4], $myobj, false, false);
                            if ($target[4] != $targetstring) {
                                wm_debug("Targetstring is now $targetstring\n");
                            }

                            // if the targetstring starts with a -, then we're taking this value OFF the aggregate
                            $multiply = 1;
                            if (preg_match("/^-(.*)/", $targetstring, $matches)) {
                                $targetstring = $matches[1];
                                $multiply = -1 * $multiply;
                            }

                            // if the remaining targetstring starts with a number and a *-, then this is a scale factor
                            if (preg_match("/^(\d+\.?\d*)\*(.*)/", $targetstring, $matches)) {
                                $targetstring = $matches[2];
                                $multiply = $multiply * floatval($matches[1]);
                            }

                            $matched = false;
                            $matched_by = '';
                            foreach ($this->datasourceclasses as $ds_class) {
                                if (!$matched) {
                                    $recognised = $this->plugins['data'][$ds_class]->Recognise($targetstring);

                                    if ($recognised) {
                                        $matched = true;
                                        $matched_by = $ds_class;

                                        if ($this->activedatasourceclasses[$ds_class]) {
                                            $this->plugins['data'][$ds_class]->Register($targetstring, $this, $myobj);
                                            if ($type == 'NODE') {
                                                $this->nodes[$name]->targets[$tindex][1] = $multiply;
                                                $this->nodes[$name]->targets[$tindex][0] = $targetstring;
                                                $this->nodes[$name]->targets[$tindex][5] = $matched_by;
                                            }
                                            if ($type == 'LINK') {
                                                $this->links[$name]->targets[$tindex][1] = $multiply;
                                                $this->links[$name]->targets[$tindex][0] = $targetstring;
                                                $this->links[$name]->targets[$tindex][5] = $matched_by;
                                            }
                                        } else {
                                            wm_warn("ProcessTargets: $type $name, target: $targetstring on config line $target[3] of $target[2] was recognised as a valid TARGET by a plugin that is unable to run ($ds_class) [WMWARN07]\n");
                                        }
                                    }
                                }
                            }
                            if (!$matched) {
                                wm_warn("ProcessTargets: $type $name, target: $target[4] on config line $target[3] of $target[2] was not recognised as a valid TARGET [WMWARN08]\n");
                            }

                            $tindex++;
                        }
                    }
                }
            }
        }
    }

    function readData()
    {
        $this->initialiseDatasourcePlugins();

        wm_debug("======================================\n");
        wm_debug("ReadData: Updating link data for all links and nodes\n");

        // we skip readdata completely in sizedebug mode
        if ($this->sizedebug == 0) {
            $this->preProcessTargets();

            wm_debug("======================================\n");
            wm_debug("Starting prefetch\n");
            foreach ($this->datasourceclasses as $ds_class) {
                $this->plugins['data'][$ds_class]->Prefetch($this);
            }

            wm_debug("======================================\n");
            wm_debug("Starting main collection loop\n");

            $allitems = array(&$this->links, &$this->nodes);
            reset($allitems);

            while (list($kk,) = each($allitems)) {
                unset($objects);
                $objects = & $allitems[$kk];

                reset($objects);
                while (list($k,) = each($objects)) {
                    unset($myobj);
                    $myobj = & $objects[$k];

                    $type = $myobj->my_type();

                    $total_in = 0;
                    $total_out = 0;
                    $name = $myobj->name;
                    wm_debug("\n");
                    wm_debug("ReadData for $type $name: \n");

                    if (($type == 'LINK' && isset($myobj->a)) || ($type == 'NODE' && !is_null($myobj->x))) {
                        $ntargets = count($myobj->targets);

                        if ($ntargets > 0) {
                            $tindex = 0;
                            foreach ($myobj->targets as $target) {
                                wm_debug("ReadData: New Target: $target[4]\n");

                                $targetstring = $target[0];
                                $multiply = $target[1];

                                $in = 0;
                                $out = 0;
                                $datatime = 0;
                                if ($target[4] != '') {
                                    // processstring won't use notes (only hints) for this string

                                    $targetstring = $this->processString($target[0], $myobj, false, false);
                                    if ($target[0] != $targetstring) {
                                        wm_debug("Targetstring is now $targetstring\n");
                                    }
                                    if ($multiply != 1) {
                                        wm_debug("Will multiply result by $multiply\n");
                                    }

                                    if ($target[0] != "") {
                                        $matched_by = $target[5];
                                        list($in, $out, $datatime) = $this->plugins['data'][$target[5]]->ReadData($targetstring, $this, $myobj);
                                    }

                                    // TODO - untangle this mess!

                                    if (($in === null) && ($out === null)) {
                                        $in = 0;
                                        $out = 0;
                                        wm_warn("ReadData: $type $name, target: $targetstring on config line $target[3] of $target[2] had no valid data, according to $matched_by [WMWARN70]\n");

                                        // this is to allow null to be passed through from DS plugins in the case of a single target
                                        // we've never defined what x + null is, so we'll treat that as a 0
                                        if ($ntargets == 1) {
                                            $total_in = null;
                                            $total_out = null;
                                        }
                                    } else {
                                        // force null to 0, for the purpose of totals
                                        // if ($in === null) $in = 0;
                                        // if ($out === null) $out = 0;

                                        if ($multiply != 1) {
                                            wm_debug("Pre-multiply: $in $out\n");

                                            $in = $multiply * $in;
                                            $out = $multiply * $out;

                                            wm_debug("Post-multiply: $in $out\n");
                                        }

                                        if ($ntargets > 1) {
                                            $total_in = $total_in + $in;
                                            $total_out = $total_out + $out;
                                        } else {
                                            $total_in = $in;
                                            $total_out = $out;
                                        }
                                    }
                                    wm_debug(sprintf("Aggregate so far: %s,%s\n", wm_value_or_null($total_in), wm_value_or_null($total_out)));
                                    # keep a track of the range of dates for data sources (mainly for MRTG/textfile based DS)
                                    if ($datatime > 0) {
                                        if ($this->max_data_time == null || $datatime > $this->max_data_time) {
                                            $this->max_data_time = $datatime;
                                        }
                                        if ($this->min_data_time == null || $datatime < $this->min_data_time) {
                                            $this->min_data_time = $datatime;
                                        }

                                        wm_debug("DataTime MINMAX: " . $this->min_data_time . " -> " . $this->max_data_time . "\n");
                                    }
                                }
                                $tindex++;
                            }

                            wm_debug("ReadData complete for $type $name: $total_in $total_out\n");
                        } else {
                            wm_debug("ReadData: No targets for $type $name\n");
                        }
                    } else {
                        wm_debug("ReadData: Skipping $type $name that looks like a template\n.");
                    }

                    $myobj->bandwidth_in = $total_in;
                    $myobj->bandwidth_out = $total_out;

                    if ($type == 'LINK' && $myobj->duplex == 'half') {
                        // in a half duplex link, in and out share a common bandwidth pool, so percentages need to include both
                        wm_debug("Calculating percentage using half-duplex\n");
                        $myobj->outpercent = (($total_in + $total_out) / ($myobj->max_bandwidth_out)) * 100;
                        $myobj->inpercent = (($total_out + $total_in) / ($myobj->max_bandwidth_in)) * 100;
                        if ($myobj->max_bandwidth_out != $myobj->max_bandwidth_in) {
                            wm_warn("ReadData: $type $name: You're using asymmetric bandwidth AND half-duplex in the same link. That makes no sense. [WMWARN44]\n");
                        }
                    } else {
                        $myobj->outpercent = (($total_out) / ($myobj->max_bandwidth_out)) * 100;
                        $myobj->inpercent = (($total_in) / ($myobj->max_bandwidth_in)) * 100;
                    }

                    $warn_in = true;
                    $warn_out = true;
                    if ($type == 'NODE' && $myobj->scalevar == 'in') {
                        $warn_out = false;
                    }
                    if ($type == 'NODE' && $myobj->scalevar == 'out') {
                        $warn_in = false;
                    }

                    if ($myobj->scaletype == 'percent') {
                        list($incol, $inscalekey, $inscaletag) = $this->colourFromValue($myobj->inpercent, $myobj->usescale, $myobj->name, true, $warn_in);
                        list($outcol, $outscalekey, $outscaletag) = $this->colourFromValue($myobj->outpercent, $myobj->usescale, $myobj->name, true, $warn_out);
                    } else {
                        // use absolute values, if that's what is requested
                        list($incol, $inscalekey, $inscaletag) = $this->colourFromValue($myobj->bandwidth_in, $myobj->usescale, $myobj->name, false, $warn_in);
                        list($outcol, $outscalekey, $outscaletag) = $this->colourFromValue($myobj->bandwidth_out, $myobj->usescale, $myobj->name, false, $warn_out);
                    }

                    $myobj->add_note("inscalekey", $inscalekey);
                    $myobj->add_note("outscalekey", $outscalekey);

                    $myobj->add_note("inscaletag", $inscaletag);
                    $myobj->add_note("outscaletag", $outscaletag);

                    $myobj->add_note("inscalecolor", $incol->asHTML());
                    $myobj->add_note("outscalecolor", $outcol->asHTML());

                    $myobj->colours[IN] = $incol;
                    $myobj->colours[OUT] = $outcol;

                    ### warn("TAGS (setting) |$inscaletag| |$outscaletag| \n");

                    wm_debug(sprintf("ReadData: Setting %s,%s for %s\n", wm_value_or_null($total_in), wm_value_or_null($total_out), $name));
                    unset($myobj);
                }
            }

            wm_debug("======================================\n");
            wm_debug("Starting cleanup\n");

            foreach ($this->datasourceclasses as $ds_class) {
                $this->plugins['data'][$ds_class]->CleanUp($this);
            }

            wm_debug("ReadData Completed.\n");
            wm_debug("------------------------------\n");
        }
    }

    // nodename is a vestigal parameter, from the days when nodes were just big labels
    function drawLabelRotated($im, $centre_x, $centre_y, $angle, $text, $font, $padding, $linkname, $textcolour, $bgcolour, $outlinecolour, &$map, $direction)
    {
        $fontObject = $this->fonts->getFont($font);
        list($strwidth, $strheight) = $fontObject->calculateImageStringSize($text);

        if (abs($angle) > 90) {
            $angle -= 180;
        }
        if ($angle < -180) {
            $angle += 360;
        }

        $rangle = -deg2rad($angle);

        $extra = 3;

        $topleft_x = $centre_x - ($strwidth / 2) - $padding - $extra;
        $topleft_y = $centre_y - ($strheight / 2) - $padding - $extra;

        $botright_x = $centre_x + ($strwidth / 2) + $padding + $extra;
        $botright_y = $centre_y + ($strheight / 2) + $padding + $extra;

        // a box. the last point is the start point for the text.
        $points = array($topleft_x, $topleft_y, $topleft_x, $botright_y, $botright_x, $botright_y, $botright_x, $topleft_y, $centre_x - $strwidth / 2, $centre_y + $strheight / 2 + 1);

        if ($rangle != 0) {
            rotateAboutPoint($points, $centre_x, $centre_y, $rangle);
        }

        if ($bgcolour->isRealColour()) {
            #X  $bgcol=myimagecolorallocate($im, $bgcolour[0], $bgcolour[1], $bgcolour[2]);
            $bgcol = $bgcolour->gdAllocate($im);
            imagefilledpolygon($im, $points, 4, $bgcol);
        }

        if ($outlinecolour->isRealColour()) {
            #X $outlinecol=myimagecolorallocate($im, $outlinecolour[0], $outlinecolour[1], $outlinecolour[2]);
            $outlinecol = $outlinecolour->gdAllocate($im);
            imagepolygon($im, $points, 4, $outlinecol);
        }

        #X $textcol=myimagecolorallocate($im, $textcolour[0], $textcolour[1], $textcolour[2]);
        $textcol = $textcolour->gdallocate($im);


        $fontObject->drawImageString($im, $points[8], $points[9], $text, $textcol, $angle);
        // $this->myimagestring($im, $font, $points[8], $points[9], $text, $textcol, $angle);

        $areaname = "LINK:L" . $map->links[$linkname]->id . ':' . ($direction + 2);

        // the rectangle is about half the size in the HTML, and easier to optimise/detect in the browser
        if ($angle == 0) {
            // TODO: We can also optimise for 90, 180, 270 degrees
            $map->imap->addArea("Rectangle", $areaname, '', array($topleft_x, $topleft_y, $botright_x, $botright_y));
            wm_debug("Adding Rectangle imagemap for $areaname\n");
        } else {
            $map->imap->addArea("Polygon", $areaname, '', $points);
            wm_debug("Adding Poly imagemap for $areaname\n");
        }
        // Make a note that we added this area
        $this->links[$linkname]->imap_areas[] = $areaname;
    }

    // This should be in WeatherMapScale - All scale lookups are done here
    function colourFromValue($value, $scalename = "DEFAULT", $name = "", $is_percent = true, $scale_warning = true)
    {
        $tag = '';
        $matchsize = null;
        $matchkey = null;

        $nowarn_clipping = intval($this->get_hint("nowarn_clipping"));
        $nowarn_scalemisses = (!$scale_warning) || intval($this->get_hint("nowarn_scalemisses"));

        if (isset($this->colours[$scalename])) {
            // $col = new WMColour(0,0,0);
            $colours = $this->colours[$scalename];

            if ($is_percent && $value > 100) {
                if ($nowarn_clipping == 0) {
                    wm_warn("ColourFromValue: Clipped $value% to 100% for item $name [WMWARN33]\n");
                }
                $value = 100;
            }

            if ($is_percent && $value < 0) {
                if ($nowarn_clipping == 0) {
                    wm_warn("ColourFromValue: Clipped $value% to 0% for item $name [WMWARN34]\n");
                }
                $value = 0;
            }

            foreach ($colours as $key => $colour) {
                if ((!isset($colour['special']) || $colour['special'] == 0)
                    and ($value >= $colour['bottom'])
                    and ($value <= $colour['top'])
                ) {

                    $range = $colour['top'] - $colour['bottom'];

                    // change in behaviour - with multiple matching ranges for a value, the smallest range wins
                    if (is_null($matchsize) || ($range < $matchsize)) {
                        $matchsize = $range;
                        $matchkey = $key;

                        # wm_debug("CFV $name $scalename $value '$tag' $key $r $g $b\n");
                    }
                }
            }

            // if we have a match, figure out the actual
            // colour (for gradients) and return it
            if (!is_null($matchsize)) {

                $colour = $colours[$matchkey];

                if (isset($colour['red2'])) {
                    if ($colour["bottom"] == $colour["top"]) {
                        $ratio = 0;
                    } else {
                        $ratio = ($value - $colour["bottom"]) / ($colour["top"] - $colour["bottom"]);
                    }

                    $red = $colour["red1"] + ($colour["red2"] - $colour["red1"]) * $ratio;
                    $green = $colour["green1"] + ($colour["green2"] - $colour["green1"]) * $ratio;
                    $blue = $colour["blue1"] + ($colour["blue2"] - $colour["blue1"]) * $ratio;
                } else {
                    $red = $colour["red1"];
                    $green = $colour["green1"];
                    $blue = $colour["blue1"];
                }

                if (isset($colour['tag'])) {
                    $tag = $colour['tag'];
                }
                $col = new WMColour($red, $green, $blue);
                return (array($col, $matchkey, $tag));
            }

        } else {
            if ($scalename != 'none') {
                wm_warn("ColourFromValue: Attempted to use non-existent scale: $scalename for item $name [WMWARN09]\n");
            } else {
                return array(new WMColour(255, 255, 255), '', '');
            }
        }

        // shouldn't really get down to here if there's a complete SCALE

        // you'll only get grey for a COMPLETELY quiet link if there's no 0 in the SCALE lines
        if ($value == 0) {
            return array(new WMColour(192, 192, 192), '', '');
        }

        if ($nowarn_scalemisses == 0) {
            wm_warn("ColourFromValue: Scale $scalename doesn't include a line for $value" . ($is_percent ? "%" : "") . " while drawing item $name [WMWARN29]\n");
        }

        // and you'll only get white for a link with no colour assigned
        return array(new WMColour(255, 255, 255), '', '');
    }


    function colourSort($a, $b)
    {
        if ($a['bottom'] == $b['bottom']) {
            if ($a['top'] < $b['top']) {
                return -1;
            }
            if ($a['top'] > $b['top']) {
                return 1;
            }
            return 0;
        }

        if ($a['bottom'] < $b['bottom']) {
            return -1;
        }

        return 1;
    }


    function drawLegendHorizontal($im, $scalename = "DEFAULT", $width = 400)
    {
        $title = $this->keytext[$scalename];

        $nscales = $this->numscales[$scalename];

        wm_debug("Drawing $nscales colours into SCALE\n");

        $font = $this->keyfont;

        $x = 0;
        $y = 0;

        $scalefactor = $width / 100;

        list($tilewidth, $tileheight) = $this->myimagestringsize($font, "100%");
        $box_left = $x;
        $scale_left = $box_left + 4 + $scalefactor / 2;
        $box_right = $scale_left + $width + $tilewidth + 4 + $scalefactor / 2;

        $box_top = $y;
        $scale_top = $box_top + $tileheight + 6;
        $scale_bottom = $scale_top + $tileheight * 1.5;
        $box_bottom = $scale_bottom + $tileheight * 2 + 6;

        $scale_im = imagecreatetruecolor($box_right + 1, $box_bottom + 1);
        $scale_ref = 'gdref_legend_' . $scalename;

        // Start with a transparent box, in case the fill or outline colour is 'none'
        imageSaveAlpha($scale_im, true);
        $nothing = imagecolorallocatealpha($scale_im, 128, 0, 0, 127);
        imagefill($scale_im, 0, 0, $nothing);

        $this->preAllocateScaleColours($scale_im, $scale_ref);

        $bgcol = new WMColour($this->colours['DEFAULT']['KEYBG']['red1'], $this->colours['DEFAULT']['KEYBG']['green1'], $this->colours['DEFAULT']['KEYBG']['blue1']);
        $outlinecol = new WMColour($this->colours['DEFAULT']['KEYOUTLINE']['red1'], $this->colours['DEFAULT']['KEYOUTLINE']['green1'], $this->colours['DEFAULT']['KEYOUTLINE']['blue1']);

        if ($bgcol->isRealColour()) {
            imagefilledrectangle($scale_im, $box_left, $box_top, $box_right, $box_bottom, $this->colours['DEFAULT']['KEYBG'][$scale_ref]);
        }

        if ($outlinecol->isRealColour()) {
            imagerectangle($scale_im, $box_left, $box_top, $box_right, $box_bottom, $this->colours['DEFAULT']['KEYOUTLINE'][$scale_ref]);
        }

        $this->myimagestring($scale_im, $font, $scale_left, $scale_bottom + $tileheight * 2 + 2, $title, $this->colours['DEFAULT']['KEYTEXT'][$scale_ref]);

        for ($p = 0; $p <= 100; $p++) {
            $dx = $p * $scalefactor;

            if (($p % 25) == 0) {
                imageline($scale_im, $scale_left + $dx, $scale_top - $tileheight, $scale_left + $dx, $scale_bottom + $tileheight, $this->colours['DEFAULT']['KEYTEXT'][$scale_ref]);
                $labelstring = sprintf("%d%%", $p);
                $this->myimagestring($scale_im, $font, $scale_left + $dx + 2, $scale_top - 2, $labelstring, $this->colours['DEFAULT']['KEYTEXT'][$scale_ref]);
            }

            list($col,) = $this->colourFromValue($p, $scalename);
            if ($col->isRealColour()) {
                $cc = $col->gdAllocate($scale_im);
                imagefilledrectangle($scale_im, $scale_left + $dx - $scalefactor / 2, $scale_top, $scale_left + $dx + $scalefactor / 2, $scale_bottom, $cc);
            }
        }

        imagecopy($im, $scale_im, $this->keyx[$scalename], $this->keyy[$scalename], 0, 0, imagesx($scale_im), imagesy($scale_im));
        $this->keyimage[$scalename] = $scale_im;

        $rx = $this->keyx[$scalename];
        $ry = $this->keyy[$scalename];

        $areaname = "LEGEND:" . $scalename;
        $this->imap->addArea("Rectangle", $areaname, '', array($rx + $box_left, $ry + $box_top, $rx + $box_right, $ry + $box_bottom));
        $this->imap_areas[] = $areaname;

    }

    function drawLegendVertical($im, $scalename = "DEFAULT", $height = 400, $inverted = false)
    {
        $title = $this->keytext[$scalename];

        # $colours=$this->colours[$scalename];
        $nscales = $this->numscales[$scalename];

        wm_debug("Drawing $nscales colours into SCALE\n");

        $font = $this->keyfont;

        $scalefactor = $height / 100;

        list($tilewidth, $tileheight) = $this->myimagestringsize($font, "100%");

        $box_left = 0;
        $box_top = 0;

        $scale_left = $box_left + $scalefactor * 2 + 4;
        $scale_right = $scale_left + $tileheight * 2;
        $box_right = $scale_right + $tilewidth + $scalefactor * 2 + 4;

        list($titlewidth,) = $this->myimagestringsize($font, $title);
        if (($box_left + $titlewidth + $scalefactor * 3) > $box_right) {
            $box_right = $box_left + $scalefactor * 4 + $titlewidth;
        }

        $scale_top = $box_top + 4 + $scalefactor + $tileheight * 2;
        $scale_bottom = $scale_top + $height;
        $box_bottom = $scale_bottom + $scalefactor + $tileheight / 2 + 4;

        $scale_im = imagecreatetruecolor($box_right + 1, $box_bottom + 1);
        $scale_ref = 'gdref_legend_' . $scalename;

        // Start with a transparent box, in case the fill or outline colour is 'none'
        imageSaveAlpha($scale_im, true);
        $nothing = imagecolorallocatealpha($scale_im, 128, 0, 0, 127);
        imagefill($scale_im, 0, 0, $nothing);

        $this->preAllocateScaleColours($scale_im, $scale_ref);

        $bgcol = new WMColour($this->colours['DEFAULT']['KEYBG']['red1'], $this->colours['DEFAULT']['KEYBG']['green1'], $this->colours['DEFAULT']['KEYBG']['blue1']);
        $outlinecol = new WMColour($this->colours['DEFAULT']['KEYOUTLINE']['red1'], $this->colours['DEFAULT']['KEYOUTLINE']['green1'], $this->colours['DEFAULT']['KEYOUTLINE']['blue1']);

        if ($bgcol->isRealColour()) {
            imagefilledrectangle($scale_im, $box_left, $box_top, $box_right, $box_bottom, $this->colours['DEFAULT']['KEYBG'][$scale_ref]);
        }

        if ($outlinecol->isRealColour()) {
            imagerectangle($scale_im, $box_left, $box_top, $box_right, $box_bottom, $this->colours['DEFAULT']['KEYOUTLINE'][$scale_ref]);
        }

        $this->myimagestring($scale_im, $font, $scale_left - $scalefactor, $scale_top - $tileheight, $title, $this->colours['DEFAULT']['KEYTEXT']['gdref1']);

        $updown = 1;
        if ($inverted) {
            $updown = -1;
        }


        for ($p = 0; $p <= 100; $p++) {
            if ($inverted) {
                $delta_y = (100 - $p) * $scalefactor;
            } else {
                $delta_y = $p * $scalefactor;
            }

            if (($p % 25) == 0) {
                imageline($scale_im, $scale_left - $scalefactor, $scale_top + $delta_y, $scale_right + $scalefactor, $scale_top + $delta_y, $this->colours['DEFAULT']['KEYTEXT'][$scale_ref]);
                $labelstring = sprintf("%d%%", $p);
                $this->myimagestring($scale_im, $font, $scale_right + $scalefactor * 2, $scale_top + $delta_y + $tileheight / 2, $labelstring, $this->colours['DEFAULT']['KEYTEXT'][$scale_ref]);
            }

            list($col,) = $this->colourFromValue($p, $scalename);
            if ($col->isRealColour()) {
                # $cc = $col->gdallocate($scale_im);
                imagefilledrectangle($scale_im, $scale_left, $scale_top + $delta_y - $scalefactor / 2, $scale_right, $scale_top + $delta_y + $scalefactor / 2, $col->gdAllocate($scale_im));
            }
        }

        imagecopy($im, $scale_im, $this->keyx[$scalename], $this->keyy[$scalename], 0, 0, imagesx($scale_im), imagesy($scale_im));
        $this->keyimage[$scalename] = $scale_im;

        $origin_x = $this->keyx[$scalename];
        $origin_y = $this->keyy[$scalename];

        $areaname = "LEGEND:$scalename";
        $this->imap->addArea("Rectangle", $areaname, '', array($origin_x + $box_left, $origin_y + $box_top, $origin_x + $box_right, $origin_y + $box_bottom));
        $this->imap_areas[] = $areaname;
    }

    function drawLegendClassic($im, $scalename = "DEFAULT", $use_tags = false)
    {
        $title = $this->keytext[$scalename];

        $colours = $this->colours[$scalename];
        usort($colours, array("Weathermap", "colourSort"));

        $nscales = $this->numscales[$scalename];

        wm_debug("Drawing $nscales colours into SCALE\n");

        $hide_zero = intval($this->get_hint("key_hidezero_" . $scalename));
        $hide_percent = intval($this->get_hint("key_hidepercent_" . $scalename));

        // did we actually hide anything?
        $hid_zero = false;
        if (($hide_zero == 1) && isset($colours['0_0'])) {
            $nscales--;
            $hid_zero = true;
        }

        $font = $this->keyfont;

        $x = $this->keyx[$scalename];
        $y = $this->keyy[$scalename];

        list($tilewidth, $tileheight) = $this->myimagestringsize($font, "MMMM");
        $tileheight = $tileheight * 1.1;
        $tilespacing = $tileheight + 2;

        if (($this->keyx[$scalename] >= 0) && ($this->keyy[$scalename] >= 0)) {
            list($minwidth,) = $this->myimagestringsize($font, 'MMMM 100%-100%');
            list($minminwidth,) = $this->myimagestringsize($font, 'MMMM ');
            list($boxwidth,) = $this->myimagestringsize($font, $title);

            if ($use_tags) {
                $max_tag = 0;
                foreach ($colours as $colour) {
                    if (isset($colour['tag'])) {
                        list($w,) = $this->myimagestringsize($font, $colour['tag']);
                        if ($w > $max_tag) {
                            $max_tag = $w;
                        }
                    }
                }

                // now we can tweak the widths, appropriately to allow for the tag strings
                if (($max_tag + $minminwidth) > $minwidth) {
                    $minwidth = $minminwidth + $max_tag;
                }
            }

            $minwidth += 10;
            $boxwidth += 10;

            if ($boxwidth < $minwidth) {
                $boxwidth = $minwidth;
            }

            $boxheight = $tilespacing * ($nscales + 1) + 10;

            $boxx = 0;
            $boxy = 0;

            wm_debug("Scale Box is %dx%d\n", $boxwidth + 1, $boxheight + 1);

            $scale_im = imagecreatetruecolor($boxwidth + 1, $boxheight + 1);

            // Start with a transparent box, in case the fill or outline colour is 'none'
            imageSaveAlpha($scale_im, true);
            $nothing = imagecolorallocatealpha($scale_im, 128, 0, 0, 127);
            imagefill($scale_im, 0, 0, $nothing);

            $scale_ref = 'gdref_legend_' . $scalename;
            $this->preAllocateScaleColours($scale_im, $scale_ref);

            $bgcol = new WMColour($this->colours['DEFAULT']['KEYBG']['red1'], $this->colours['DEFAULT']['KEYBG']['green1'], $this->colours['DEFAULT']['KEYBG']['blue1']);
            $outlinecol = new WMColour($this->colours['DEFAULT']['KEYOUTLINE']['red1'], $this->colours['DEFAULT']['KEYOUTLINE']['green1'], $this->colours['DEFAULT']['KEYOUTLINE']['blue1']);


            if ($bgcol->isRealColour()) {
                imagefilledrectangle($scale_im, $boxx, $boxy, $boxx + $boxwidth, $boxy + $boxheight, $this->colours['DEFAULT']['KEYBG'][$scale_ref]);
            }

            if ($outlinecol->isRealColour()) {
                imagerectangle($scale_im, $boxx, $boxy, $boxx + $boxwidth, $boxy + $boxheight, $this->colours['DEFAULT']['KEYOUTLINE'][$scale_ref]);
            }

            $this->myimagestring($scale_im, $font, $boxx + 4, $boxy + 4 + $tileheight, $title, $this->colours['DEFAULT']['KEYTEXT'][$scale_ref]);

            $i = 1;

            foreach ($colours as $colour) {
                if (!isset($colour['special']) || $colour['special'] == 0) {
                    // pick a value in the middle...
                    $value = ($colour['bottom'] + $colour['top']) / 2;
                    wm_debug(sprintf("%f-%f (%f)  %d %d %d\n", $colour['bottom'], $colour['top'], $value, $colour['red1'], $colour['green1'], $colour['blue1']));

                    #  debug("$i: drawing\n");
                    if (($hide_zero == 0) || $colour['key'] != '0_0') {
                        $y = $boxy + $tilespacing * $i + 8;
                        $x = $boxx + 6;

                        $fudgefactor = 0;
                        if ($hid_zero && $colour['bottom'] == 0) {
                            // calculate a small offset that can be added, which will hide the zero-value in a
                            // gradient, but not make the scale incorrect. A quarter of a pixel should do it.
                            $fudgefactor = ($colour['top'] - $colour['bottom']) / ($tilewidth * 4);
                        }

                        // if it's a gradient, red2 is defined, and we need to sweep the values
                        if (isset($colour['red2'])) {
                            for ($n = 0; $n <= $tilewidth; $n++) {
                                $value = $fudgefactor + $colour['bottom'] + ($n / $tilewidth) * ($colour['top'] - $colour['bottom']);
                                list($ccol,) = $this->colourFromValue($value, $scalename, "", false);
                                $col = $ccol->gdallocate($scale_im);
                                imagefilledrectangle($scale_im, $x + $n, $y, $x + $n, $y + $tileheight, $col);
                            }
                        } else {
                            // pick a value in the middle...
                            list($ccol,) = $this->colourFromValue($value, $scalename, "", false);
                            $col = $ccol->gdallocate($scale_im);
                            imagefilledrectangle($scale_im, $x, $y, $x + $tilewidth, $y + $tileheight, $col);
                        }

                        if ($use_tags) {
                            $labelstring = "";
                            if (isset($colour['tag'])) {
                                $labelstring = $colour['tag'];
                            }
                        } else {
                            $labelstring = sprintf("%s-%s", $colour['bottom'], $colour['top']);
                            if ($hide_percent == 0) {
                                $labelstring .= "%";
                            }
                        }

                        $this->myimagestring($scale_im, $font, $x + 4 + $tilewidth, $y + $tileheight, $labelstring, $this->colours['DEFAULT']['KEYTEXT'][$scale_ref]);
                        $i++;
                    }
                }
            }

            imagecopy($im, $scale_im, $this->keyx[$scalename], $this->keyy[$scalename], 0, 0, imagesx($scale_im), imagesy($scale_im));
            $this->keyimage[$scalename] = $scale_im;

            $areaname = "LEGEND:$scalename";

            $this->imap->addArea("Rectangle", $areaname, '', array($this->keyx[$scalename], $this->keyy[$scalename], $this->keyx[$scalename] + $boxwidth, $this->keyy[$scalename] + $boxheight));
            $this->imap_areas[] = $areaname;
        }
    }

    function drawTimestamp($gdImage, $fontNumber, $colour, $which = "")
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

    function drawTitle($gdImage, $fontNumber, $colour)
    {
        $string = $this->processString($this->title, $this);
        $fontObject = $this->fonts->getFont($fontNumber);

        if ($this->get_hint('screenshot_mode') == 1) {
            $string = wmStringAnonymise($string);
        }

        list($boxwidth, $boxheight) = $fontObject->calculateImageStringSize($string);

        $x = 10;
        $y = $this->titley - $boxheight;

        if (($this->titlex >= 0) && ($this->titley >= 0)) {
            $x = $this->titlex;
            $y = $this->titley;
        }

        $fontObject->drawImageString($gdImage, $x, $y, $string, $colour);

        $this->imap->addArea("Rectangle", "TITLE", '', array($x, $y, $x + $boxwidth, $y - $boxheight));
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
        $reader = new WeatherMapConfigReader();
        $reader->Init($this);

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
        $this->buildZLayers();
        $this->resolveRelativePositions();
        $this->runProcessorPlugins("pre");
    }

    // *************************************

    function populateDefaultScales()
    {
        // load some default colouring, otherwise it all goes wrong
        if ($this->scalesseen == 0) {
            wm_debug("Adding default SCALE colour set (no SCALE lines seen).\n");
            $defaults = array(
                '0_0' => array(
                    'bottom' => 0,
                    'top' => 0,
                    'red1' => 192,
                    'green1' => 192,
                    'blue1' => 192,
                    'special' => 0
                ),
                '0_1' => array(
                    'bottom' => 0,
                    'top' => 1,
                    'red1' => 255,
                    'green1' => 255,
                    'blue1' => 255,
                    'special' => 0
                ),
                '1_10' => array(
                    'bottom' => 1,
                    'top' => 10,
                    'red1' => 140,
                    'green1' => 0,
                    'blue1' => 255,
                    'special' => 0
                ),
                '10_25' => array(
                    'bottom' => 10,
                    'top' => 25,
                    'red1' => 32,
                    'green1' => 32,
                    'blue1' => 255,
                    'special' => 0
                ),
                '25_40' => array(
                    'bottom' => 25,
                    'top' => 40,
                    'red1' => 0,
                    'green1' => 192,
                    'blue1' => 255,
                    'special' => 0
                ),
                '40_55' => array(
                    'bottom' => 40,
                    'top' => 55,
                    'red1' => 0,
                    'green1' => 240,
                    'blue1' => 0,
                    'special' => 0
                ),
                '55_70' => array(
                    'bottom' => 55,
                    'top' => 70,
                    'red1' => 240,
                    'green1' => 240,
                    'blue1' => 0,
                    'special' => 0
                ),
                '70_85' => array(
                    'bottom' => 70,
                    'top' => 85,
                    'red1' => 255,
                    'green1' => 192,
                    'blue1' => 0,
                    'special' => 0
                ),
                '85_100' => array(
                    'bottom' => 85,
                    'top' => 100,
                    'red1' => 255,
                    'green1' => 0,
                    'blue1' => 0,
                    'special' => 0
                )
            );

            foreach ($defaults as $key => $def) {
                $this->colours['DEFAULT'][$key] = $def;
                $this->colours['DEFAULT'][$key]['key'] = $key;
                $this->scalesseen++;
                $this->numscales['DEFAULT']++;
            }
            // we have a 0-0 line now, so we need to hide that.
            $this->add_hint("key_hidezero_DEFAULT", 1);
        } else {
            wm_debug("Already have $this->scalesseen scales, no defaults added.\n");
        }
    }

    function buildZLayers()
    {
        wm_debug("Building cache of z-layers and finalising bandwidth.\n");

        $allitems = array();
        foreach ($this->nodes as $node) {
            $allitems[] = $node;
        }
        foreach ($this->links as $link) {
            $allitems[] = $link;
        }

        while (list($ky,) = each($allitems)) {
            # foreach ($allitems as $ky=>$vl)
            # {
            $item =& $allitems[$ky];
            $z = $item->zorder;
            if (!isset($this->seen_zlayers[$z]) || !is_array($this->seen_zlayers[$z])) {
                $this->seen_zlayers[$z] = array();
            }
            array_push($this->seen_zlayers[$z], $item);

            // while we're looping through, let's set the real bandwidths
            if ($item->my_type() == "LINK") {
                $this->links[$item->name]->max_bandwidth_in = wmInterpretNumberWithMetricPrefix($item->max_bandwidth_in_cfg, $this->kilo);
                $this->links[$item->name]->max_bandwidth_out = wmInterpretNumberWithMetricPrefix($item->max_bandwidth_out_cfg, $this->kilo);
            } elseif ($item->my_type() == "NODE") {
                $this->nodes[$item->name]->max_bandwidth_in = wmInterpretNumberWithMetricPrefix($item->max_bandwidth_in_cfg, $this->kilo);
                $this->nodes[$item->name]->max_bandwidth_out = wmInterpretNumberWithMetricPrefix($item->max_bandwidth_out_cfg, $this->kilo);
            } else {
                wm_warn("Internal bug - found an item of type: " . $item->my_type() . "\n");
            }

            wm_debug(sprintf("   Setting bandwidth on " . $item->my_type() . " $item->name (%s -> %d bps, %s -> %d bps, KILO = %d)\n", $item->max_bandwidth_in_cfg, $item->max_bandwidth_in, $item->max_bandwidth_out_cfg, $item->max_bandwidth_out, $this->kilo));
        }
        wm_debug("Found " . sizeof($this->seen_zlayers) . " z-layers including builtins (0,100).\n");
    }

    function runProcessorPlugins($stage="pre")
    {
        $classes =array();
        if ($stage == 'pre') {
            $classes = $this->preprocessclasses;
        }
        if ($stage == 'post') {
            $classes = $this->postprocessclasses;
        }
        wm_debug("Running $stage-processing plugins...\n");
        foreach ($classes as $plugin_class) {
            wm_debug("Running $plugin_class" . "->run()\n");
            $this->plugins[$stage][$plugin_class]->run($this);
        }
        wm_debug("Finished $stage-processing plugins...\n");
    }

    function resolveRelativePositions()
    {
        // calculate any relative positions here - that way, nothing else
        // really needs to know about them

        wm_debug("Resolving relative positions for NODEs...\n");
        // safety net for cyclic dependencies
        $i = 100;
        do {
            $skipped = 0;
            $set = 0;
            foreach ($this->nodes as $node) {
                if (($node->relative_to != '') && (!$node->relative_resolved)) {
                    wm_debug("Resolving relative position for NODE " . $node->name . " to " . $node->relative_to . "\n");
                    if (array_key_exists($node->relative_to, $this->nodes)) {
                        // check if we are relative to another node which is in turn relative to something
                        // we need to resolve that one before we can resolve this one!
                        if (($this->nodes[$node->relative_to]->relative_to != '') && (!$this->nodes[$node->relative_to]->relative_resolved)) {
                            wm_debug("Skipping unresolved relative_to. Let's hope it's not a circular one\n");
                            $skipped++;
                        } else {
                            $rx = $this->nodes[$node->relative_to]->x;
                            $ry = $this->nodes[$node->relative_to]->y;

                            if ($node->polar) {
                                // treat this one as a POLAR relative coordinate.
                                // - draw rings around a node!
                                $angle = $node->x;
                                $distance = $node->y;
                                $newpos_x = $rx + $distance * sin(deg2rad($angle));
                                $newpos_y = $ry - $distance * cos(deg2rad($angle));
                                wm_debug("->$newpos_x,$newpos_y\n");
                                $this->nodes[$node->name]->x = $newpos_x;
                                $this->nodes[$node->name]->y = $newpos_y;
                                $this->nodes[$node->name]->relative_resolved = true;
                                $set++;
                            } elseif ($node->pos_named) {
                                $off_name = $node->relative_name;
                                if (isset($this->nodes[$node->relative_to]->named_offsets[$off_name])) {
                                    $this->nodes[$node->name]->x = $rx + $this->nodes[$node->relative_to]->named_offsets[$off_name][0];
                                    $this->nodes[$node->name]->y = $ry + $this->nodes[$node->relative_to]->named_offsets[$off_name][1];
                                } else {
                                    $skipped++;
                                }
                            } else {
                                // save the relative coords, so that WriteConfig can work
                                // resolve the relative stuff

                                $newpos_x = $rx + $this->nodes[$node->name]->x;
                                $newpos_y = $ry + $this->nodes[$node->name]->y;
                                wm_debug("->$newpos_x,$newpos_y\n");
                                $this->nodes[$node->name]->x = $newpos_x;
                                $this->nodes[$node->name]->y = $newpos_y;
                                $this->nodes[$node->name]->relative_resolved = true;
                                $set++;
                            }
                        }
                    } else {
                        wm_warn("NODE " . $node->name . " has a relative position to an unknown node (" . $node->relative_to . ")! [WMWARN10]\n");
                    }
                }
            }
            wm_debug("Relative Positions Cycle $i - set $set and Skipped $skipped for unresolved dependencies\n");
            $i--;
        } while (($set > 0) && ($i != 0));

        if ($skipped > 0) {
            wm_warn("There are Circular dependencies in relative POSITION lines for $skipped nodes. [WMWARN11]\n");
        }

        wm_debug("-----------------------------------\n");

    }

    function ReadConfig_Commit(&$curobj)
    {
        if (is_null($curobj)) {
            return;
        }

        $last_seen = $curobj->my_type();

        // first, save the previous item, before starting work on the new one
        if ($last_seen == "NODE") {
            $this->nodes[$curobj->name] = $curobj;
            wm_debug("Saving Node: " . $curobj->name . "\n");
        }

        if ($last_seen == "LINK") {
            $this->links[$curobj->name] = $curobj;
            wm_debug("Saving Link: " . $curobj->name . "\n");
        }
    }

    function writeJSONFile($filename)
    {

        if ($filename != "") {

            $fd = fopen($filename, 'w');
            # $output = '';

            if ($fd) {

                $data = array();
                $data['nodes'] = array();
                $data['links'] = array();
                $data['scales'] = array();

                foreach ($this->scales as $scale) {
                    $data['scales'][$scale->name] = array();
                }

                foreach ($this->nodes as $node) {
                    $data['nodes'][$node->name] = array();

                    $data['nodes'][$node->name]["x"] = $node->x;
                    $data['nodes'][$node->name]["y"] = $node->y;

                    $data['nodes'][$node->name]["bounding_boxes"] = array();
                    foreach ($node->boundingboxes as $bb) {
                        $data['nodes'][$node->name]["bounding_boxes"][] = array($bb[0], $bb[1], $bb[2], $bb[3]);
                    }

                    $data['nodes'][$node->name]["template"] = false;
                    if ($node->x === null) {
                        $data['nodes'][$node->name]["template"] = true;
                    }
                }

                foreach ($this->links as $link) {
                    $data['links'][$link->name] = array();
                    $data['links'][$link->name]["width"] = $link->width;
                    $data['links'][$link->name]["nodes"] = array($link->a->name, $link->b->name);

                    $data['links'][$link->name]["template"] = false;
                    if ($link->a === null) {
                        $data['links'][$link->name]["template"] = true;
                    }
                }

                fputs($fd, json_encode($data, JSON_PRETTY_PRINT));

            }
        }
    }

    function writeDataFile($filename)
    {
        if ($filename != "") {
            $fd = fopen($filename, 'w');

            if ($fd) {
                foreach ($this->nodes as $node) {
                    if (!preg_match("/^::\s/", $node->name) && sizeof($node->targets) > 0) {
                        fputs($fd, sprintf("N_%s\t%f\t%f\r\n", $node->name, $node->bandwidth_in, $node->bandwidth_out));
                    }
                }

                foreach ($this->links as $link) {
                    if (!preg_match("/^::\s/", $link->name) && sizeof($link->targets) > 0) {
                        fputs($fd, sprintf("L_%s\t%f\t%f\r\n", $link->name, $link->bandwidth_in, $link->bandwidth_out));
                    }
                }

                fclose($fd);
            }
        }
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
                if ($param[2] == CONFIG_TYPE_COLOR) {
                    $output.="$keyword " . render_colour($this->$field) . "\n";
                }
                if ($param[2] == CONFIG_TYPE_LITERAL) {
                    $output.="$keyword " . $this->$field . "\n";
                }
            }
        }

        if (($this->timex != $this->inherit_fieldlist['timex'])
            || ($this->timey != $this->inherit_fieldlist['timey'])
            || ($this->stamptext != $this->inherit_fieldlist['stamptext'])) {
            $output .= "TIMEPOS " . $this->timex . " " . $this->timey . " " . $this->stamptext . "\n";
        }

        if (($this->mintimex != $this->inherit_fieldlist['mintimex'])
            || ($this->mintimey != $this->inherit_fieldlist['mintimey'])
            || ($this->minstamptext != $this->inherit_fieldlist['minstamptext'])) {
            $output .= "MINTIMEPOS " . $this->mintimex . " " . $this->mintimey . " " . $this->minstamptext . "\n";
        }

        if (($this->maxtimex != $this->inherit_fieldlist['maxtimex'])
            || ($this->maxtimey != $this->inherit_fieldlist['maxtimey'])
            || ($this->maxstamptext != $this->inherit_fieldlist['maxstamptext'])) {
            $output .= "MAXTIMEPOS " . $this->maxtimex . " " . $this->maxtimey . " " . $this->maxstamptext . "\n";
        }

        if (($this->titlex != $this->inherit_fieldlist['titlex'])
            || ($this->titley != $this->inherit_fieldlist['titley'])) {
            $output .= "TITLEPOS " . $this->titlex . " " . $this->titley . "\n";
        }

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
            $locale = localeconv();
            $decimal_point = $locale['decimal_point'];

            foreach ($colours as $k => $colour) {
                if (!isset($colour['special']) || ! $colour['special']) {
                    $top = rtrim(rtrim(sprintf("%f", $colour['top']), "0"), $decimal_point);
                    $bottom= rtrim(rtrim(sprintf("%f", $colour['bottom']), "0"), $decimal_point);

                    if ($bottom > 1000) {
                        $bottom = wmFormatNumberWithMetricPrefix($colour['bottom'], $this->kilo);
                    }

                    if ($top > 1000) {
                        $top = wmFormatNumberWithMetricPrefix($colour['top'], $this->kilo);
                    }

                    $tag = (isset($colour['tag'])? $colour['tag']:'');

                    if (($colour['red1'] == -1) && ($colour['green1'] == -1) && ($colour['blue1'] == -1)) {
                        $output.=sprintf("SCALE %s %-4s %-4s   none   %s\n", $scalename, $bottom, $top, $tag);
                    } elseif (!isset($colour['red2'])) {
                        $output.=sprintf(
                            "SCALE %s %-4s %-4s %3d %3d %3d  %s\n",
                            $scalename,
                            $bottom,
                            $top,
                            $colour['red1'],
                            $colour['green1'],
                            $colour['blue1'],
                            $tag
                        );
                    } else {
                        $output.=sprintf(
                            "SCALE %s %-4s %-4s %3d %3d %3d   %3d %3d %3d    %s\n",
                            $scalename,
                            $bottom,
                            $top,
                            $colour['red1'],
                            $colour['green1'],
                            $colour['blue1'],
                            $colour['red2'],
                            $colour['green2'],
                            $colour['blue2'],
                            $tag
                        );
                    }
                } else {
                    $c = new WMColour($colour['red1'], $colour['green1'], $colour['blue1']);
                    $output .= sprintf("%sCOLOR %s\n", $k, $c->asConfig());
                }
            }
            $output .= "\n";
        }


        if (1==0) {
            // TODO - These should replace the stuff above
            $output .= "# new colourtable stuff (duplicated above right now TODO)\n";
            foreach ($this->colourtable as $k => $c) {
                $output .= sprintf("%sCOLOR %s\n", $k, $c->as_config());
            }
            $output .= "\n";

            foreach ($this->scales as $s) {
                $output .= $s->WriteConfig();
            }

            $output .= "\n";
        }

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

        foreach (array("template","normal") as $which) {
            if ($which == "template") {
                $output .= "\n# TEMPLATE-only NODEs:\n";
            }
            if ($which == "normal") {
                $output .= "\n# regular NODEs:\n";
            }

            foreach ($this->nodes as $node) {
                if (!preg_match("/^::\s/", $node->name)) {
                    wm_debug("WriteConfig: Considering Node $node->name defined in $node->defined_in\n");
                    if ($node->defined_in == $this->configfile) {
                        if ($which=="template" && $node->x === null) {
                            wm_debug("TEMPLATE\n");
                            $output .= $node->WriteConfig();
                        }
                        if ($which=="normal" && $node->x !== null) {
                            $output .= $node->WriteConfig();
                        }
                    } else {
                        wm_debug("Not writing Node $node->name - defined in another file");
                    }
                }
            }

            if ($which == "template") {
                $output .= "\n# TEMPLATE-only LINKs:\n";
            }
            if ($which == "normal") {
                $output .= "\n# regular LINKs:\n";
            }

            foreach ($this->links as $link) {
                if (!preg_match("/^::\s/", $link->name)) {
                    wm_debug("WriteConfig: Considering Link $link->name\n");
                    if ($link->defined_in == $this->configfile) {
                        if ($which=="template" && $link->a === null) {
                            $output .= $link->WriteConfig();
                        }
                        if ($which=="normal" && $link->a !== null) {
                            $output .= $link->WriteConfig();
                        }
                    }
                }
            }
        }

        $output .= "\n\n# That's All Folks!\n";

        return $output;
    }

    function writeConfig($filename)
    {
        wm_debug("Writing config to $filename (read from $this->configfile)\n");

        $fd=fopen($filename, "w");

        if ($fd) {
            $output = $this->getConfig();
            fwrite($fd, $output);
            fclose($fd);
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
    function preAllocateScaleColours($im, $refname = 'gdref1')
    {
        foreach ($this->colours as $scalename => $colours) {
            foreach ($colours as $key => $colour) {
                if ((!isset($this->colours[$scalename][$key]['red2'])) && (!isset($this->colours[$scalename][$key][$refname]))) {
                    $r=$colour['red1'];
                    $g=$colour['green1'];
                    $b=$colour['blue1'];
                    wm_debug("AllocateScaleColours: $scalename/$refname $key ($r,$g,$b)\n");
                    $this->colours[$scalename][$key][$refname]=myimagecolorallocate($im, $r, $g, $b);
                }
            }
        }
    }

    function drawMapImage($filename = '', $thumbnailfile = '', $thumbnailmax = 250, $withnodes = true, $use_via_overlay = false, $use_rel_overlay = false)
    {
        wm_debug("Trace: DrawMap()\n");

        $bgimage=null;
        if ($this->configfile != "") {
            $this->cachefile_version = crc32(file_get_contents($this->configfile));
        } else {
            $this->cachefile_version = crc32("........");
        }

        wm_debug("Running Post-Processing Plugins...\n");
        foreach ($this->postprocessclasses as $post_class) {
            wm_debug("Running $post_class"."->run()\n");
            //call_user_func_array(array($post_class, 'run'), array(&$this));
            $this->plugins['post'][$post_class]->run($this);
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

        // do the basic prep work
        if ($this->background != '') {
            if (is_readable($this->background)) {
                $bgimage=imagecreatefromfile($this->background);

                if (!$bgimage) {
                    wm_warn("Failed to open background image.  One possible reason: Is your BACKGROUND really a PNG?\n");
                } else {
                    $this->width=imagesx($bgimage);
                    $this->height=imagesy($bgimage);
                }
            } else {
                wm_warn("Your background image file could not be read. Check the filename, and permissions, for " . $this->background . "\n");
            }
        }

        $image=imagecreatetruecolor($this->width, $this->height);

        if (!$image) {
            wm_warn("Couldn't create output image in memory (" . $this->width . "x" . $this->height . ").");
        } else {
            ImageAlphaBlending($image, true);

            if ($this->get_hint("antialias") == 1) {
                    // Turn on anti-aliasing if it exists and it was requested
                    if (function_exists("imageantialias")) {
                        imageantialias($image, true);
                    }
            }

            // by here, we should have a valid image handle

            // save this away, now
            $this->image=$image;

            $this->white=myimagecolorallocate($image, 255, 255, 255);
            $this->black=myimagecolorallocate($image, 0, 0, 0);
            $this->grey=myimagecolorallocate($image, 192, 192, 192);
            $this->selected=myimagecolorallocate($image, 255, 0, 0); // for selections in the editor

            $this->preAllocateScaleColours($image);

            if ($bgimage) {
                imagecopy($image, $bgimage, 0, 0, 0, 0, $this->width, $this->height);
                imagedestroy($bgimage);
            } else {
                // fill with background colour anyway, since the background image failed to load
                imagefilledrectangle($image, 0, 0, $this->width, $this->height, $this->colourtable['BG']->gdallocate($image));
            }

            // Now it's time to draw a map

            // do the node rendering stuff first, regardless of where they are actually drawn.
            // this is so we can get the size of the nodes, which links will need if they use offsets
            foreach ($this->nodes as $node) {
                // don't try and draw template nodes
                wm_debug("Pre-rendering ".$node->name." to get bounding boxes.\n");
                if (!is_null($node->x)) {
                    $this->nodes[$node->name]->preRender($image, $this);
                }
            }

            $all_layers = array_keys($this->seen_zlayers);
            sort($all_layers);

            foreach ($all_layers as $z) {
                $z_items = $this->seen_zlayers[$z];
                wm_debug("Drawing layer $z\n");
                // all the map 'furniture' is fixed at z=1000
                if ($z==1000) {
                    foreach ($this->colours as $scalename => $colours) {
                        wm_debug("Drawing KEY for $scalename if necessary.\n");

                        if ((isset($this->numscales[$scalename])) && (isset($this->keyx[$scalename])) && ($this->keyx[$scalename] >= 0) && ($this->keyy[$scalename] >= 0)) {
                            if ($this->keystyle[$scalename]=='classic') {
                                $this->drawLegendClassic($image, $scalename, false);
                            }
                            if ($this->keystyle[$scalename]=='horizontal') {
                                $this->drawLegendHorizontal($image, $scalename, $this->keysize[$scalename]);
                            }
                            if ($this->keystyle[$scalename]=='vertical') {
                                $this->drawLegendVertical($image, $scalename, $this->keysize[$scalename]);
                            }
                            if ($this->keystyle[$scalename]=='inverted') {
                                $this->drawLegendVertical($image, $scalename, $this->keysize[$scalename], true);
                            }
                            if ($this->keystyle[$scalename]=='tags') {
                                $this->drawLegendClassic($image, $scalename, true);
                            }
                        }
                    }

                    $this->drawTimestamp($image, $this->timefont, $this->colourtable['TIME']->gdallocate($image));
                    if (! is_null($this->min_data_time)) {
                        $this->drawTimestamp($image, $this->timefont, $this->colourtable['TIME']->gdallocate($image), "MIN");
                        $this->drawTimestamp($image, $this->timefont, $this->colourtable['TIME']->gdallocate($image), "MAX");
                    }
                    $this->drawTitle($image, $this->titlefont, $this->colourtable['TITLE']->gdallocate($image));
                }

                if (is_array($z_items)) {
                    foreach ($z_items as $it) {
                        if (strtolower(get_class($it))=='weathermaplink') {
                            // only draw LINKs if they have NODES defined (not templates)
                            // (also, check if the link still exists - if this is in the editor, it may have been deleted by now)
                            if (isset($this->links[$it->name]) && isset($it->a) && isset($it->b)) {
                                wm_debug("Drawing LINK ".$it->name."\n");
                                $this->links[$it->name]->Draw($image, $this);
                            }
                        }
                        if (strtolower(get_class($it))=='weathermapnode') {
                            // if (!is_null($it->x)) $it->pre_render($image, $this);
                            if ($withnodes) {
                                // don't try and draw template nodes
                                if (isset($this->nodes[$it->name]) && !is_null($it->x)) {
                                    # print "::".get_class($it)."\n";
                                    wm_debug("Drawing NODE ".$it->name."\n");
                                    $this->nodes[$it->name]->Draw($image, $this);
                                    $ii=0;
                                    foreach ($this->nodes[$it->name]->boundingboxes as $bbox) {
                                        # $areaname = "NODE:" . $it->name . ':'.$ii;
                                        $areaname = "NODE:N". $it->id . ":" . $ii;
                                        $this->imap->addArea("Rectangle", $areaname, '', $bbox);
                                        wm_debug("Adding imagemap area");
                                        $this->nodes[$it->name]->imap_areas[] = $areaname;
                                        $ii++;
                                    }
                                    wm_debug("Added $ii bounding boxes too\n");
                                }
                            }
                        }
                    }
                }
            }

            $overlay = myimagecolorallocate($image, 200, 0, 0);

            // for the editor, we can optionally overlay some other stuff
            if ($this->context == 'editor') {
                if ($use_rel_overlay) {
                    // first, we can show relatively positioned NODEs
                    foreach ($this->nodes as $node) {
                        if ($node->relative_to != '') {
                            $rel_x = $this->nodes[$node->relative_to]->x;
                            $rel_y = $this->nodes[$node->relative_to]->y;
                            imagearc($image, $node->x, $node->y, 15, 15, 0, 360, $overlay);
                            imagearc($image, $node->x, $node->y, 16, 16, 0, 360, $overlay);

                            imageline($image, $node->x, $node->y, $rel_x, $rel_y, $overlay);
                        }
                    }
                }

                if ($use_via_overlay) {
                    // then overlay VIAs, so they can be seen
                    foreach ($this->links as $link) {
                        foreach ($link->vialist as $via) {
                            if (isset($via[2])) {
                                $x = $this->nodes[$via[2]]->x + $via[0];
                                $y = $this->nodes[$via[2]]->y + $via[1];
                            } else {
                                $x = $via[0];
                                $y = $via[1];
                            }
                            imagearc($image, $x, $y, 10, 10, 0, 360, $overlay);
                            imagearc($image, $x, $y, 12, 12, 0, 360, $overlay);
                        }
                    }

                    // also (temporarily) draw all named offsets for each node
                    foreach ($this->nodes as $node) {
                        if (!is_null($node->x)) {
                            foreach ($node->named_offsets as $offsets) {
                                $x = $node->x + $offsets[0];
                                $y = $node->y + $offsets[1];
                                imagearc($image, $x, $y, 4, 4, 0, 360, $overlay);
                            }
                        }
                    }
                }
            }

            // Ready to output the results...

            if ($filename == 'null') {
                // do nothing at all - we just wanted the HTML AREAs for the editor or HTML output
            } else {
                if ($filename == '') {
                    imagepng($image);
                } else {
                    $result = false;
                    $functions = true;
                    if (function_exists('imagejpeg') && preg_match("/\.jpg/i", $filename)) {
                        wm_debug("Writing JPEG file to $filename\n");
                        $result = imagejpeg($image, $filename);
                    } elseif (function_exists('imagegif') && preg_match("/\.gif/i", $filename)) {
                        wm_debug("Writing GIF file to $filename\n");
                        $result = imagegif($image, $filename);
                    } elseif (function_exists('imagepng') && preg_match("/\.png/i", $filename)) {
                        wm_debug("Writing PNG file to $filename\n");
                        $result = imagepng($image, $filename);
                    } else {
                        wm_warn("Failed to write map image. No function existed for the image format you requested ($filename). [WMWARN12]\n");
                        $functions = false;
                    }

                    if (($result===false) && ($functions===true)) {
                        if (file_exists($filename)) {
                            wm_warn("Failed to overwrite existing image file $filename - permissions of existing file are wrong? [WMWARN13]");
                        } else {
                            wm_warn("Failed to create image file $filename - permissions of output directory are wrong? [WMWARN14]");
                        }
                    }
                }
            }

            if ($this->context == 'editor2') {
                $cachefile = $this->cachefolder.DIRECTORY_SEPARATOR.dechex(crc32($this->configfile))."_bg.".$this->cachefile_version.".png";
                imagepng($image, $cachefile);
                $cacheuri = $this->cachefolder.'/'.dechex(crc32($this->configfile))."_bg.".$this->cachefile_version.".png";
                $this->mapcache = $cacheuri;
            }

            if (function_exists('imagecopyresampled')) {
                // if one is specified, and we can, write a thumbnail too
                if ($thumbnailfile != '') {
                    $result = false;
                    if ($this->width > $this->height) {
                        $factor=($thumbnailmax / $this->width);
                    } else {
                        $factor =($thumbnailmax / $this->height);
                    }

                    $this->thumb_width = $this->width * $factor;
                    $this->thumb_height = $this->height * $factor;

                    $imagethumb=imagecreatetruecolor($this->thumb_width, $this->thumb_height);
                    imagecopyresampled($imagethumb, $image, 0, 0, 0, 0, $this->thumb_width, $this->thumb_height, $this->width, $this->height);
                    $result = imagepng($imagethumb, $thumbnailfile);
                    imagedestroy($imagethumb);



                    if (($result===false)) {
                        if (file_exists($filename)) {
                            wm_warn("Failed to overwrite existing image file $filename - permissions of existing file are wrong? [WMWARN15]");
                        } else {
                            wm_warn("Failed to create image file $filename - permissions of output directory are wrong? [WMWARN16]");
                        }
                    }
                }
            } else {
                wm_warn("Skipping thumbnail creation, since we don't have the necessary function. [WMWARN17]");
            }
            imagedestroy($image);
        }
    }

    /**
     * So that memory is deallocated correctly, go through and remove all the
     * references to other objects. The PHP GC doesn't deal with circular
     * references, so this stops the memory usage from ballooning up.
     */
    function cleanUp()
    {
        $all_layers = array_keys($this->seen_zlayers);

        foreach ($all_layers as $z) {
            $this->seen_zlayers[$z] = null;
        }

        foreach ($this->links as $link) {
            $link->owner = null;
            $link->a = null;
            $link->b = null;
            $link->parent = null;
            $link->descendents = null;

            unset($link);
        }

        foreach ($this->nodes as $node) {
            // destroy all the images we created, to prevent memory leaks

            if (isset($node->image)) {
                imagedestroy($node->image);
            }
            $node->owner = null;
            $node->parent = null;
            $node->descendents = null;
            unset($node);
        }

        // Clear up the other random hashes of information
        $this->dsinfocache = null;
        $this->colourtable = null;
        $this->usage_stats = null;
        $this->scales = null;
    }

    function calculateImageMap()
    {
        wm_debug("Trace: PreloadMapHTML()\n");

        // find the middle of the map
        $center_x = $this->width / 2;
        $center_y = $this->height / 2;

        // loop through everything. Figure out along the way if it's a node or a link
        $allitems = array(&$this->nodes, &$this->links);
        reset($allitems);

        while (list($kk,) = each($allitems)) {
            unset($objects);
            # $objects = &$this->links;
            $objects = &$allitems[$kk];

            reset($objects);
            while (list($k,) = each($objects)) {
                unset($myobj);
                $myobj = &$objects[$k];

                $type = $myobj->my_type();
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
                    $change .= join('', $myobj->overliburl[$d]);
                    $change .= $myobj->notestext[$d];
                }

                if ($this->htmlstyle == "overlib") {
                    // skip all this if it's a template node
                    if ($type=='LINK' && ! isset($myobj->a->name)) {
                        $change = '';
                    }
                    if ($type=='NODE' && ! isset($myobj->x)) {
                        $change = '';
                    }

                    if ($change != '') {
                        if ($type=='NODE') {
                            $mid_x = $myobj->x;
                            $mid_y = $myobj->y;
                        }

                        if ($type=='LINK') {
                            $a_x = $this->nodes[$myobj->a->name]->x;
                            $a_y = $this->nodes[$myobj->a->name]->y;

                            $b_x = $this->nodes[$myobj->b->name]->x;
                            $b_y = $this->nodes[$myobj->b->name]->y;

                            $mid_x=($a_x + $b_x) / 2;
                            $mid_y=($a_y + $b_y) / 2;
                        }
                        $left="";
                        $above="";
                        $img_extra = "";

                        if ($myobj->overlibwidth != 0) {
                            $left="WIDTH," . $myobj->overlibwidth . ",";
                            $img_extra .= " WIDTH=$myobj->overlibwidth";

                            if ($mid_x > $center_x) {
                                $left.="LEFT,";
                            }
                        }

                        if ($myobj->overlibheight != 0) {
                            $above="HEIGHT," . $myobj->overlibheight . ",";
                            $img_extra .= " HEIGHT=$myobj->overlibheight";

                            if ($mid_y > $center_y) {
                                $above.="ABOVE,";
                            }
                        }

                        foreach ($dirs as $dir => $parts) {
                            $caption = ($myobj->overlibcaption[$dir] != '' ? $myobj->overlibcaption[$dir] : $myobj->name);
                            $caption = $this->processString($caption, $myobj);

                            $overlibhtml = "onmouseover=\"return overlib('";

                            $n = 0;
                            if (sizeof($myobj->overliburl[$dir]) > 0) {
                                // print "ARRAY:".is_array($link->overliburl[$dir])."\n";
                                foreach ($myobj->overliburl[$dir] as $url) {
                                    if ($n>0) {
                                        $overlibhtml .= '&lt;br /&gt;';
                                    }
                                    $overlibhtml .= "&lt;img $img_extra src=" . $this->processString($url, $myobj) . "&gt;";
                                    $n++;
                                }
                            }

                            if (trim($myobj->notestext[$dir]) != '') {
                                # put in a linebreak if there was an image AND notes
                                if ($n>0) {
                                    $overlibhtml .= '&lt;br /&gt;';
                                }
                                $note = $this->processString($myobj->notestext[$dir], $myobj);
                                $note = htmlspecialchars($note, ENT_NOQUOTES);
                                $note=str_replace("'", "\\&apos;", $note);
                                $note=str_replace('"', "&quot;", $note);
                                $overlibhtml .= $note;
                            }
                            $overlibhtml .= "',DELAY,250,${left}${above}CAPTION,'" . $caption . "');\"  onmouseout=\"return nd();\"";

                            foreach ($parts as $part) {
                                $areaname = $type.":" . $prefix . $myobj->id. ":" . $part;

                                $this->imap->setProp("extrahtml", $overlibhtml, $areaname);
                            }
                        }
                    } // if change
                } // overlib?

                // now look at infourls
                foreach ($dirs as $dir => $parts) {
                    foreach ($parts as $part) {
                        $areaname = $type.":" . $prefix . $myobj->id. ":" . $part;

                        if (($this->htmlstyle != 'editor') && ($myobj->infourl[$dir] != '')) {
                            $this->imap->setProp("href", $this->processString($myobj->infourl[$dir], $myobj), $areaname);
                        }
                    }
                }
            }
        }
    }

    function asJS()
    {
        $js='';

        $js .= "var Links = new Array();\n";
        $js .= "var LinkIDs = new Array();\n";

        foreach ($this->links as $link) {
            $js.=$link->asJS();
        }

        $js .= "var Nodes = new Array();\n";
        $js .= "var NodeIDs = new Array();\n";

        foreach ($this->nodes as $node) {
            $js.=$node->asJS();
        }


        $mapname = array_pop(explode("/", $this->configfile));
        $js .= "var Map = {\n";
        $js .= sprintf('  "file": %s,', jsEscape($mapname));
        $js .= sprintf('  "width": %s,', jsEscape($this->width));
        $js .= sprintf('  "height": %s,', jsEscape($this->height));
        $js .= sprintf('  "title": %s,', jsEscape($this->title));
        $js .= "\n};\n";

        return $js;
    }

    function asJSON()
    {
        $json = '';

        $json .= "{ \n";

        $json .= "\"map\": {  \n";
        foreach (array_keys($this->inherit_fieldlist) as $fld) {
            $json .= jsEscape($fld).": ";
            $json .= jsEscape($this->$fld);
            $json .= ",\n";
        }
        $json = rtrim($json, ", \n");
        $json .= "\n},\n";

        $json .= "\"nodes\": {\n";
        $json .= $this->defaultnode->asJSON();
        foreach ($this->nodes as $node) {
            $json .= $node->asJSON();
        }
        $json = rtrim($json, ", \n");
        $json .= "\n},\n";

        $json .= "\"links\": {\n";
        $json .= $this->defaultlink->asJSON();

        foreach ($this->links as $link) {
            $json .= $link->asJSON();
        }
        $json = rtrim($json, ", \n");
        $json .= "\n},\n";

        $json .= "'imap': [\n";
        $json .= $this->imap->subJSON("NODE:");
        // should check if there WERE nodes...
        $json .= ",\n";
        $json .= $this->imap->subJSON("LINK:");
        $json .= "\n]\n";
        $json .= "\n";

        $json .= ", 'valid': 1}\n";

        return($json);
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
        $html="\n".'<map name="' . $imagemapname . '" id="' . $imagemapname . '">';

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
                        // skip the linkless areas if we are in the editor - they're redundant
                        $html .= $this->imap->exactHTML($areaname, true, ($this->context != 'editor'));
                    }
                }

                // we reverse the array for each zlayer so that the imagemap order
                // will match up with the draw order (last drawn should be first hit)
                foreach (array_reverse($z_items) as $it) {
                    if ($it->name != 'DEFAULT' && $it->name != ":: DEFAULT ::") {
                        $name = "";

                        foreach ($it->imap_areas as $areaname) {
                            // skip the linkless areas if we are in the editor - they're redundant
                            $html .= $this->imap->exactHTML($areaname, true, ($this->context != 'editor'));
                        }
                    }
                }
            }
        }

        $html .= "</map>\n";

        return($html);
    }
}

// vim:ts=4:sw=4:
