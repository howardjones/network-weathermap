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

    public $scalevar;

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
        $this->scalevar = null;
        $this->duplex = null;
    }

    private function getDirectionList()
    {
        return array("in"=>IN, "out"=>OUT);
    }

    private function getChannelList()
    {
        return array("in"=>IN, "out"=>OUT);
    }

    public function updateMaxValues($kilo)
    {
        // while we're looping through, let's set the real bandwidths
        $this->maxValues[IN] = wmInterpretNumberWithMetricPrefix($this->max_bandwidth_in_cfg, $kilo);
        $this->maxValues[OUT] = wmInterpretNumberWithMetricPrefix($this->max_bandwidth_out_cfg, $kilo);

        $this->max_bandwidth_in = $this->maxValues[IN];
        $this->max_bandwidth_out = $this->maxValues[OUT];

        wm_debug(sprintf("   Setting bandwidth on %s (%s -> %d bps, %s -> %d bps, KILO = %d)\n", $this, $this->max_bandwidth_in_cfg, $this->max_bandwidth_in, $this->max_bandwidth_out_cfg, $this->max_bandwidth_out, $kilo));
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
        foreach ($channels as $channelName => $channel) {
            $totals[$channel] = 0;
            $this->absoluteUsages[$channel] = 0;
        }
        $dataTime = 0;

        $nTargets = count($this->targets);
        $nFails = 0;

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
                $nFails++;
            }

            # keep a track of the range of dates for data sources (mainly for MRTG/textfile based DS)
            if ($dataTime > 0) {
                $this->owner->registerDataTime($dataTime);
            }
        }

        // that was the only target, and it's failed
        if ($nFails == 1 && $nTargets == 1) {
            // this is to allow null to be passed through from DS plugins in the case of a single target
            // we've never defined what x + null is, so we'll treat that as a 0

            foreach ($channels as $channel) {
                $totals[$channel] = null;
            }
        }

        foreach ($channels as $channelName => $channel) {
            $bwvar = "bandwidth_" . $channelName;
            $this->$bwvar = $totals[$channel];
            $this->absoluteUsages[$channel] = $totals[$channel];
        }

        wm_debug("ReadData complete for %s: %s\n", $this, join(" ", $totals));
        wm_debug("ReadData: Setting %s,%s for %s\n", wm_value_or_null($totals[IN]), wm_value_or_null($totals[OUT]), $this);
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

        $warnings[IN] = true;
        $warnings[OUT] = true;

        // Nodes only use one channel, so don't warn for the unused channel
        if ($this->scalevar !== null) {
            if ($this->scalevar == 'in') {
                $warnings[OUT] = false;
            }
            if ($this->scalevar == 'out') {
                $warnings[IN] = false;
            }
        }

        // use absolute values, if that's what is requested
        foreach ($channels as $channelName => $channel) {
            $isPercent = true;
            $value = $this->percentUsages[$channel];

            if ($this->scaletype == 'absolute') {
                $value = $this->absoluteUsages[$channel];
                $isPercent = false;
            }

            list($col, $scalekey, $scaletag) = $this->owner->scales[$this->usescale]->colourFromValue($value, $this->name, $isPercent, $warnings[$channel]);
            $this->channelScaleColours[$channel] = $col;
            $this->colours[$channel] = $col;
            $this->add_note($channelName . "scalekey", $scalekey);
            $this->add_note($channelName . "scaletag", $scaletag);
            $this->add_note($channelName . "scalecolor", $col->asHTML());
        }
    }
}
