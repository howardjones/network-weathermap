<?php
// PHP Weathermap 0.98
// Copyright Howard Jones, 2005-2014 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License


class WMException extends Exception
{

}

// Links, Nodes and the Map object inherit from this class ultimately.
// Just to make some common code common.
    
class WeatherMapBase
{
    public $name;

    protected $notes = array();
    protected $hints = array();
    protected $imap_areas = array();
    protected $config = array();
    protected $descendents = array();

    protected $inherit_fieldlist;

    function __construct()
    {
        $this->config = array();
        $this->descendents = array();
    }

    function __toString()
    {
        return $this->my_type() . " " . (isset($this->name) ? $this->name : "[unnamed]");
    }

    public function my_type()
    {
        return "BASE";
    }

    /**
     * Anything calling this should be doing it a better way!
     */
    public function getInternalMember($thing)
    {
        return $this->$thing;
    }

    /**
     * Anything calling this should be doing it a better way!
     */
    public function setInternalMember($thing, $value)
    {
        $this->$thing = $value;
    }

    public function add_note($name, $value)
    {
        wm_debug("Adding note $name='$value' to ".$this->name."\n");
        $this->notes[$name] = $value;
    }

    public function get_note($name)
    {
        if (isset($this->notes[$name])) {
            return($this->notes[$name]);
        }

        return(null);
    }

    public function delete_note($name)
    {
        unset($this->notes[$name]);
    }

    public function add_hint($name, $value)
    {
        wm_debug("Adding hint $name='$value' to ".$this->name."\n");
        $this->hints[$name] = $value;
    }

    public function get_hint($name)
    {
        if (isset($this->hints[$name])) {
            return($this->hints[$name]);
        }

        return(null);
    }

    public function delete_hint($name)
    {
        unset($this->hints[$name]);
    }

    /**
     * Get a value for a config variable. Follow the template inheritance tree if necessary.
     * Return an array with the value followed by the status (whether it came from the source object or
     * a template, or just didn't exist). This will replace all that CopyFrom stuff.
     *
     * @param $keyname
     * @return array
     */
    public function getConfig($keyname)
    {
        if (isset($this->config[$keyname])) {
            return array($this->config[$keyname], CONF_FOUND_DIRECT);
        } else {
            if (!is_null($this->parent)) {
                list($value, $direct) = $this->parent->getConfig($keyname);
                if ($direct != CONF_NOT_FOUND) {
                    $direct = CONF_FOUND_INHERITED;
                }
            } else {
                $value = null;
                $direct = CONF_NOT_FOUND;
            }

            // if we got to the top of the tree without finding it, that's probably a typo in the original getConfig()
            if (is_null($value) && is_null($this->parent)) {
                wm_warn("Tried to get config keyword '$keyname' with no result. [WMWARN300]");
            }
            return array($value, $direct);
        }
    }

    public function getConfigWithoutInheritance($keyname)
    {
        if (isset($this->config[$keyname])) {
            return $this->config[$keyname];
        }
        return array(null);
    }

    /*
     * Set a new value for a config variable. If $recalculate is true (after the initial readConfig)
     * then also recursively tell all objects that have us as a template that their state has changed
     *
     * return an array of the objects that were notified
     */
    public function setConfig($keyname, $value, $recalculate = false)
    {
        wm_debug("Settings config %s = %s\n", $keyname, $value);
        if (is_null($value)) {
            unset($this->config[$keyname]);
        } else {
            $this->config[$keyname] = $value;
        }

        if ($recalculate) {
            $affected = $this->recalculate();
            return $affected;
        }
        return array($this->name);
    }

    public function addConfig($keyname, $value, $recalculate = false)
    {
        wm_debug("Appending config %s = %s\n", $keyname, $value);
        if (is_null($this->config[$keyname])) {
            // create a new array, with this as the only item
            $this->config[$keyname] = array($value);
        } else {
            if (is_array($this->config[$keyname])) {
                // append the new item to the existing array
                $this->config[$keyname] []= $value;
            } else {
                // This is the second value, so make a new array of the old one, and this one
                $this->config[$keyname] = array( $this->config[$keyname], $value);
            }
        }

        if ($recalculate) {
            $affected = $this->recalculate();
            return $affected;
        }
        return array($this->name);
    }

    /**
     * Do any pre-drawing calculations needed, then let any items that use us as a template
     * do theirs, too. Recursively build up a list of the affected objects so we could
     * tell the editor to do selective updates
     */
    public function recalculate()
    {
        $notified = array();
        $notified []= $this->name;
        wm_debug("Recalculating %s\n", $this);
        $this->preCalculate();

        foreach ($this->descendents as $child) {
            wm_debug("  %s notifying %s\n", $this, $child);
            $new_notified = $child->preCalculate();
            foreach ($new_notified as $n) {
                $notified []= $n;
            }
        }
        return $notified;
    }

    public function setTemplate($template_name, $owner)
    {
        $this->template = $template_name;
        wm_debug("Resetting to template %s %s\n", $this->my_type(), $template_name);
        $this->reset($owner);
    }
}

/**
 * Class WeatherMapItem - anything drawn on the map inherits from this.
 */
class WeatherMapItem extends WeatherMapBase
{
    // TODO - we should be able to make most of these protected
    public $owner;
    public $configline;

    public $parent;
    public $my_default;
    public $defined_in;
    public $config_override;   # used by the editor to allow text-editing

    public $imageMapAreas;
    public $zIndex;
    public $zorder;

    function __construct()
    {
        parent::__construct();

        $this->zIndex = 1000;
        $this->imageMapAreas = array();
        $this->descendents = array();
        $this->parent = null;
    }

    public function my_type()
    {
        return "ITEM";
    }

    public function setDefined($source)
    {
        $this->defined_in = $source;
    }

    public function getDefined()
    {
        return $this->defined_in;
    }

    /**
     * Accessor for the variables that should be visible to ProcessString {} tokens.
     * Side-effect - none of the others are available anymore, and ALL are decoupled from the
     * actual implementation names, so we can refactor/rename more easily.
     *
     * @param $name#
     */
    public function getValue($name)
    {

    }

    public function preChecks($owner)
    {

    }

    public function preCalculate($owner)
    {

    }

    public function draw($imageRef, $owner)
    {

    }

    public function isTemplate()
    {
        return false;
    }

    public function getImageMapAreas()
    {
        return $this->imageMapAreas;
    }

    public function getZIndex()
    {
        return $this->zorder;
    }
}

/**
 * Class WeatherMapDataItem - Everything that collects data from DS plugins,
 * uses scales, etc, inherits from here.
 *
 */
class WeatherMapDataItem extends WeatherMapItem
{
    // arrays to replace a lot of what follows. Paving the way for >2 channels of data.
    // (and generally less duplicated code)
    public $percentValues = array();
    public $absoluteValues = array();
    public $maxValues = array();
    public $targets = array();

    public $bandwidth_in;
    public $bandwidth_out;
    public $inpercent;
    public $outpercent;
    public $max_bandwidth_in;
    public $max_bandwidth_out;
    public $max_bandwidth_in_cfg;
    public $max_bandwidth_out_cfg;
    public $usescale;
    public $scaletype;
    public $inscalekey;
    public $outscalekey;
    public $inscaletag;
    public $outscaletag;
    public $percentUsages = array();
    public $absoluteUsages = array();
    public $maxValuesConfigured = array();
    public $channelScaleColours = array();

    public $infourl;
    public $overliburl;
    public $overlibwidth;
    public $overlibheight;
    public $overlibcaption;

    function __construct()
    {
        parent::__construct();

        $this->infourl = array();
        $this->overliburl = array();
    }

    private function getDirectionList()
    {
        return array(IN, OUT);
    }

    private function getChannelList()
    {
        return array(IN, OUT);
    }

    public function prepareForDataCollection()
    {
        foreach ($this->targets as $target) {
            wm_debug("ProcessTargets: New Target: $target\n");

            $target->preProcess($this, $this->owner);
            $matchedBy = $target->findHandlingPlugin($this->owner->plugins['data']);

            if ($matchedBy != "") {
                if ($this->owner->plugins['data'][$matchedBy]['active']) {
                    $target->registerWithPlugin($this, $this->owner);
                } else {
                    wm_warn(sprintf(
                        "ProcessTargets: %s, target: %s was recognised as a valid TARGET by a plugin that is unable to run (%s) [WMWARN07]\n",
                        $this,
                        $target,
                        $matchedBy
                    ));
                }
            }

            if ($matchedBy == "") {
                wm_warn(sprintf(
                    "ProcessTargets: %s, target: %s was not recognised as a valid TARGET [WMWARN08]\n",
                    $this,
                    $target
                ));
            }
        }
    }

    public function performDataCollection()
    {
        $channels = $this->getChannelList();

        wm_debug("-------------------------------------------------------------\n");
        wm_debug("ReadData for $this: \n");

        $totals = array();
        foreach ($channels as $channel) {
            $totals[$channel] = 0;
            $this->absoluteUsages[$channel] = 0;
        }
        $dataTime = 0;

        $nTargets = count($this->targets);

        foreach ($this->targets as $target) {
            wm_debug("ReadData: New Target: $target\n");
            $target->readData($this->owner, $this);

            if ($target->hasValidData()) {
                $results = $target->getData();
                $dataTime = array_shift($results);

                foreach ($channels as $channel) {
                    wm_debug("Adding %f to total for channel %d\n", $results[$channel], $channel);
                    $totals[$channel] += $results[$channel];
                }
                wm_debug("Running totals: %s\n", join(" ", $totals));
            } else {
                wm_debug("Invalid data?\n");
            }

            // this->owner was the only target, and it's failed
            if (!$target->hasValidData() && $nTargets == 1) {
                // this->owner is to allow null to be passed through from DS plugins in the case of a single target
                // we've never defined what x + null is, so we'll treat that as a 0

                foreach ($channels as $channel) {
                    $totals[$channel] = null;
                }
            }

            # keep a track of the range of dates for data sources (mainly for MRTG/textfile based DS)
            if ($dataTime > 0) {
                $this->owner->registerDataTime($dataTime);
            }
        }

        $this->bandwidth_in = $totals[IN];
        $this->bandwidth_out = $totals[OUT];

        foreach ($channels as $channel) {
            $this->absoluteUsages[$channel] = $totals[$channel];
        }

        wm_debug("ReadData complete for %s: %s\n", $this, join(" ", $totals));
        wm_debug("ReadData: Setting %s,%s for %s\n", wm_value_or_null($totals[IN]), wm_value_or_null($totals[OUT]), $this);
    }

    public function aggregateDataResults()
    {
        $channels = $this->getChannelList();

        // bodge for now: TODO these should be set in Reset() and readConfig()
        $this->maxValues[IN] = $this->max_bandwidth_in;
        $this->maxValues[OUT] = $this->max_bandwidth_out;

        foreach ($channels as $channel) {
            $this->percentUsages[$channel] = ($this->absoluteUsages[$channel] / $this->maxValues[$channel]) * 100;
        }

        $this->outpercent = $this->percentUsages[OUT];
        $this->inpercent = $this->percentUsages[IN];

        if ($this->my_type() == 'LINK' && $this->duplex == 'half') {
            // in a half duplex link, in and out share a common bandwidth pool, so percentages need to include both
            wm_debug("Calculating percentage using half-duplex\n");
            $this->outpercent = (($this->absoluteUsages[IN] + $this->absoluteUsages[OUT]) / ($this->maxValues[OUT])) * 100;
            $this->inpercent = (($this->absoluteUsages[IN] + $this->absoluteUsages[OUT]) / ($this->maxValues[IN])) * 100;

            if ($this->max_bandwidth_out != $this->max_bandwidth_in) {
                wm_warn("ReadData: $this: You're using asymmetric bandwidth AND half-duplex in the same link. That makes no sense. [WMWARN44]\n");
            }

            $this->percentUsages[OUT] = $this->outpercent;
            $this->percentUsages[IN] = $this->inpercent;
        }
    }

    public function calculateScaleColours()
    {
        $channels = $this->getChannelList();

        $warn_in = true;
        $warn_out = true;

        // Nodes only use one channel, so don't warn for the unused channel
        if ($this->my_type() == 'NODE') {
            if ($this->scalevar == 'in') {
                $warn_out = false;
            }
            if ($this->scalevar == 'out') {
                $warn_in = false;
            }
        }

        if ($this->scaletype == 'percent') {
            list($incol, $inscalekey, $inscaletag) = $this->owner->scales[$this->usescale]->colourFromValue($this->inpercent, $this->name, true, $warn_in);
            list($outcol, $outscalekey, $outscaletag) = $this->owner->scales[$this->usescale]->colourFromValue($this->outpercent, $this->name, true, $warn_out);

            foreach ($channels as $channel) {
                list($col, $scalekey, $scaletag) = $this->owner->scales[$this->usescale]->colourFromValue($this->percentUsages[$channel], $this->name, true, $warn_out);
                $this->channelScaleColours[$channel] = $col;
                $this->colours[$channel] = $col;
            }
        } else {
            // use absolute values, if that's what is requested
            list($incol, $inscalekey, $inscaletag) = $this->owner->scales[$this->usescale]->colourFromValue($this->bandwidth_in, $this->name, false, $warn_in);
            list($outcol, $outscalekey, $outscaletag) = $this->owner->scales[$this->usescale]->colourFromValue($this->bandwidth_out, $this->name, false, $warn_out);

            foreach ($channels as $channel) {
                list($col, $scalekey, $scaletag) = $this->owner->scales[$this->usescale]->colourFromValue($this->absoluteUsages[$channel], $this->name, false, $warn_out);
                $this->channelScaleColours[$channel] = $col;
                $this->colours[$channel] = $col;
            }
        }

        $this->add_note("inscalekey", $inscalekey);
        $this->add_note("outscalekey", $outscalekey);

        $this->add_note("inscaletag", $inscaletag);
        $this->add_note("outscaletag", $outscaletag);

        $this->add_note("inscalecolor", $incol->asHTML());
        $this->add_note("outscalecolor", $outcol->asHTML());

        $this->colours[IN] = $incol;
        $this->colours[OUT] = $outcol;
    }

}