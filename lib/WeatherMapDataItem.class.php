<?php

/**
 * Class WeatherMapDataItem - Everything that collects data from DS plugins,
 * uses scales, etc, inherits from here.
 *
 */
class WeatherMapDataItem extends WeatherMapItem
{
    // arrays to replace a lot of what follows. Paving the way for >2 channels of data.
    // (and generally less duplicated code)
    public $maxValues = array();
    public $targets = array();
    public $percentUsages = array();
    public $absoluteUsages = array();
    public $maxValuesConfigured = array();
    public $channelScaleColours = array();

    public $bandwidth_in;
    public $bandwidth_out;
    public $inpercent;
    public $outpercent;
    public $max_bandwidth_in;
    public $max_bandwidth_out;
    public $max_bandwidth_in_cfg;
    public $max_bandwidth_out_cfg;

    public $inscalekey;
    public $outscalekey;
    public $inscaletag;
    public $outscaletag;

    public $usescale;
    public $scaletype;
    public $scalevar;

    public $infourl;
    public $overliburl;
    public $overlibwidth;
    public $overlibheight;
    public $overlibcaption;

    public $id;
    public $colours = array();
    public $template;

    public function __construct()
    {
        parent::__construct();

        $this->infourl = array();
        $this->overliburl = array();
        $this->scalevar = null;
        $this->duplex = null;
        $this->template = null;

        foreach ($this->getChannelList() as $channelName => $channelIndex) {
            $this->colours[$channelIndex] = new WMColour(192, 192, 192);
            $this->percentUsages[$channelIndex] = null;
            $this->absoluteUsages[$channelIndex] = null;
        }
    }

    protected function reset(&$newOwner)
    {
        $this->owner = $newOwner;
        $templateName = $this->template;

        if ($templateName == '') {
            $templateName = "DEFAULT";
            $this->template = $templateName;
        }

        wm_debug("Resetting $this with $templateName\n");

        // the internal default-default gets it's values from inherit_fieldlist
        // everything else comes from a node object - the template.
        if ($this->name == ':: DEFAULT ::') {
            $this->resetDefault();
        } else {
            $this->resetNormalObject();
        }

        if (null !== $newOwner) {
            $this->id = $newOwner->next_id++;
        }
    }

    /**
     * @param mixed $default_default
     * @return string
     */
    protected function getConfigHints($default_default)
    {
        $output = "";
        foreach ($this->hints as $hintname => $hint) {
            // all hints for DEFAULT node are for writing
            // only changed ones, or unique ones, otherwise
            if (($this->name == 'DEFAULT')
                || (isset($default_default->hints[$hintname])
                    && $default_default->hints[$hintname] != $hint)
                || (!isset($default_default->hints[$hintname]))
            ) {
                $output .= "\tSET $hintname $hint\n";
            }
        }
        return $output;
    }

    /**
     * @param mixed $default_default
     * @param string $configKeyword
     * @param string $fieldName
     * @return string
     */
    protected function getConfigInOutOrBoth($default_default, $configKeyword, $fieldName)
    {
        $output = "";
        $myArray = $this->$fieldName;
        $theirArray = $default_default->$fieldName;

        if ($myArray[IN] == $myArray[OUT]) {
            $dirs = array(IN => ""); // only use the IN value, since they're both the same, but don't prefix the output keyword
        } else {
            $dirs = array(IN => "IN", OUT => "OUT");// the full monty two-keyword version
        }

        foreach ($dirs as $dir => $dirText) {
            if ($myArray[$dir] != $theirArray[$dir]) {
                $value = $myArray[$dir];
                if (is_array($value)) {
                    $value = join(" ", $value);
                }
                $output .= "\t" . $dirText . $configKeyword . " " . $value . "\n";
            }
        }
        return $output;
    }

    /**
     * @param $output
     * @param $basic_params
     * @param $default_default
     * @return string
     */
    protected function getConfigSimple($basic_params, $default_default)
    {
        $output = "";

        # TEMPLATE must come first. DEFAULT
        if ($this->template != 'DEFAULT' && $this->template != ':: DEFAULT ::') {
            $output .= "\tTEMPLATE " . $this->template . "\n";
        }

        foreach ($basic_params as $param) {
            $field = $param["fieldName"];
            $keyword = $param["configKeyword"];

            if ($this->$field != $default_default->$field) {
                if ($param["type"] == CONFIG_TYPE_COLOR) {
                    $output .= "\t$keyword " . $this->$field->asConfig() . "\n";
                }
                if ($param["type"] == CONFIG_TYPE_LITERAL) {
                    $output .= "\t$keyword " . $this->$field . "\n";
                }
            }
        }
        return $output;
    }

    private function getDirectionList()
    {
        return array("in" => IN, "out" => OUT);
    }

    private function getChannelList()
    {
        return array("in" => IN, "out" => OUT);
    }

    public function updateMaxValues($kilo)
    {
        // while we're looping through, let's set the real bandwidths
        $this->maxValues[IN] = WMUtility::interpretNumberWithMetricSuffix($this->max_bandwidth_in_cfg, $kilo);
        $this->maxValues[OUT] = WMUtility::interpretNumberWithMetricSuffix($this->max_bandwidth_out_cfg, $kilo);

        $this->max_bandwidth_in = $this->maxValues[IN];
        $this->max_bandwidth_out = $this->maxValues[OUT];

        wm_debug(sprintf("   Setting bandwidth on %s (%s -> %d bps, %s -> %d bps, KILO = %d)\n", $this, $this->max_bandwidth_in_cfg, $this->max_bandwidth_in, $this->max_bandwidth_out_cfg, $this->max_bandwidth_out, $kilo));
    }

    public function prepareForDataCollection()
    {
        /** @var WMTarget $target */
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

    protected function getMaxValueConfig($default_default)
    {
        $output = "";

        if (($this->max_bandwidth_in != $default_default->max_bandwidth_in)
            || ($this->max_bandwidth_out != $default_default->max_bandwidth_out)
            || ($this->name == 'DEFAULT')
        ) {
            if ($this->max_bandwidth_in == $this->max_bandwidth_out) {
                $output .= "\tMAXVALUE " . $this->max_bandwidth_in_cfg . "\n";
            } else {
                $output .= "\tMAXVALUE " . $this->max_bandwidth_in_cfg . " " . $this->max_bandwidth_out_cfg . "\n";
            }
        }

        return $output;
    }

    public function performDataCollection()
    {
        $channels = $this->getChannelList();

        wm_debug("-------------------------------------------------------------\n");
        wm_debug("ReadData for $this: \n");

        foreach ($channels as $channelName => $channel) {
            $this->absoluteUsages[$channel] = 0;
        }

        $nTargets = count($this->targets);

        $nFails = $this->collectDataFromTargets($channels);

        // that was the only target, and it's failed
        if ($nFails == 1 && $nTargets == 1) {
            // this is to allow null to be passed through from DS plugins in the case of a single target
            // we've never defined what x + null is, so we'll treat that as a 0

            foreach ($channels as $channel) {
                $this->absoluteUsages[$channel] = null;
            }
        }

        // copy to the old named variables, for now. XXX - remove these
        foreach ($channels as $channelName => $channel) {
            $bwvar = "bandwidth_" . $channelName;
            $this->$bwvar = $this->absoluteUsages[$channel];
        }

        wm_debug("ReadData complete for %s: %s\n", $this, join(" ", $this->absoluteUsages));
        wm_debug(
            "ReadData: Setting %s,%s for %s\n",
            WMUtility::valueOrNull($this->absoluteUsages[IN]),
            WMUtility::valueOrNull($this->absoluteUsages[OUT]),
            $this
        );
    }

    public function aggregateDataResults()
    {
        $channels = $this->getChannelList();

        foreach ($channels as $channelName => $channel) {
            // bodge for now: TODO these should be set in Reset() and readConfig()
            $maxvar = "max_bandwidth_" . $channelName;
            $this->maxValues[$channel] = $this->$maxvar;

            $value = $this->absoluteUsages[$channel];

            if ($this->duplex == 'half') {
                wm_debug("Calculating percentage using half-duplex\n");
                // in a half duplex link, in and out share a common bandwidth pool, so percentages need to include both
                $value = ($this->absoluteUsages[IN] + $this->absoluteUsages[OUT]);
            }

            $this->percentUsages[$channel] = ($value / $this->maxValues[$channel]) * 100;
            $pcvar = $channelName . "percent";
            $this->$pcvar = $this->percentUsages[$channel];
        }

//            if ($this->max_bandwidth_out != $this->max_bandwidth_in) {
//                wm_warn("ReadData: $this: You're using asymmetric bandwidth AND half-duplex in the same link. That makes no sense. [WMWARN44]\n");
//            }
    }

    public function calculateScaleColours()
    {
        $channels = $this->getChannelList();

        $scale = $this->owner->getScale($this->usescale);

        // use absolute values, if that's what is requested
        foreach ($channels as $channelName => $channel) {
            $isPercent = true;
            $value = $this->percentUsages[$channel];

            if ($this->scaletype == 'absolute') {
                $value = $this->absoluteUsages[$channel];
                $isPercent = false;
            }

            $logWarnings = ($this->scalevar === null)
                || ($this->scalevar == 'in' && $channelName == 'out')
                || ($this->scalevar == 'out' && $channelName == 'in');

            list($col, $scalekey, $scaletag) = $scale->colourFromValue($value, $this->name, $isPercent, $logWarnings);
            $this->channelScaleColours[$channel] = $col;
            $this->colours[$channel] = $col;
            $this->add_note($channelName . "scalekey", $scalekey);
            $this->add_note($channelName . "scaletag", $scaletag);
            $this->add_note($channelName . "scalecolor", $col->asHTML());
        }
    }

    /**
     * @param $channels
     * @param $nFails
     * @return array
     */
    private function collectDataFromTargets($channels)
    {
        $nFails = 0;
        $dataTime = 0;

        foreach ($this->targets as $target) {
            wm_debug("ReadData: New Target: $target\n");
            $target->readData($this->owner, $this);

            if ($target->hasValidData()) {
                $results = $target->getData();
                $dataTime = array_shift($results);

                foreach ($channels as $channel) {
                    wm_debug("Adding %f to total for channel %d\n", $results[$channel], $channel);
                    $this->absoluteUsages[$channel] += $results[$channel];
                }
                wm_debug("Running totals: %s\n", join(" ", $this->absoluteUsages));
            } else {
                wm_debug("Invalid data?\n");
                $nFails++;
            }

            # keep a track of the range of dates for data sources (mainly for MRTG/textfile based DS)
            if ($dataTime > 0) {
                $this->owner->registerDataTime($dataTime);
            }
        }
        return array($nFails);
    }

    private function resetDefault()
    {
        foreach (array_keys($this->inherit_fieldlist) as $fld) {
            $this->$fld = $this->inherit_fieldlist[$fld];
        }
        $this->parent = null;
    }

    private function resetNormalObject()
    {
        $templateObject = $this->getTemplateObject();
        $this->copyFrom($templateObject);
        $this->parent = $templateObject;
        $this->parent->descendents [] = $this;
    }

    protected function asJSCore()
    {
        return "";
    }

    protected function asJS($type = "Thing", $prefix = "T")
    {
        $output = '';
        $output .= $type . "s[" . WMUtility::jsEscape($this->name) . "] = {";

        $output .= $this->asJSCore();

        $output .= "};\n";
        $output .= $type . "IDs[\"" . $prefix . $this->id . "\"] = " . WMUtility::jsEscape($this->name) . ";\n";

        return $output;
    }
}
