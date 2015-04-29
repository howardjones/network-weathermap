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
