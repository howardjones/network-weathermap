<?php

namespace Weathermap\Core;

/**
 * Class WeatherMapDataItem - Everything that collects data from DS plugins,
 * uses scales, etc, inherits from here.
 *
 */
class MapDataItem extends MapItem
{
    // arrays to replace a lot of what follows. Paving the way for >2 channels of data.
    // (and generally less duplicated code)
    public $maxValues = array();
    /** @var Target[] $targets */
    public $targets = array();

    public $percentUsages = array();
    public $absoluteUsages = array();
    public $maxValuesConfigured = array();
    public $channelScaleColours = array();
    public $inheritedFieldList = array();

    public $scaleTags = array();
    public $scaleKeys = array();

    public $template;
    public $duplex;

    public $usescale;
    public $scaletype;
    public $scalevar;

    public $infourl;
    public $overliburl;
    public $overlibwidth;
    public $overlibheight;
    public $overlibcaption;

    public $id;
    /** @var Colour[] $colours */
    public $colours = array();
    public $notestext = array();

    public function __construct()
    {
        parent::__construct();

        $this->infourl = array();
        $this->overliburl = array();
        $this->scalevar = null;
        $this->duplex = null;
        $this->template = null;

        foreach ($this->getChannelList() as $channelName => $channelIndex) {
            $this->colours[$channelIndex] = new Colour(192, 192, 192);
            $this->percentUsages[$channelIndex] = null;
            $this->absoluteUsages[$channelIndex] = null;
        }
    }

    public function getChannelList()
    {
        return array('in' => IN, 'out' => OUT);
    }

    /**
     * @param string $templateName
     * @param Map $owner
     */
    public function setTemplate($templateName, $owner)
    {
        $this->template = $templateName;
        MapUtility::debug("Resetting to template %s %s\n", $this->myType(), $templateName);
        $this->reset($owner);
    }

    /**
     * Is this item a template or a 'real' one?
     *
     * @return bool
     */
    public function isTemplate()
    {
        return true;
    }

    public function draw($imageRef)
    {
    }

    // by tracking which objects depend on each other, we can reduce the number of full-table searches for a single object
    // (mostly in the editor for things like moving nodes)

    protected function reset(&$newOwner)
    {
        $this->owner = $newOwner;
        $templateName = $this->template;

        if ($templateName == '') {
            $templateName = 'DEFAULT';
            $this->template = $templateName;
        }

        MapUtility::debug("Resetting $this with $templateName\n");

        // the internal default-default gets it's values from inherit_fieldlist
        // everything else comes from a node object - the template.
        if ($this->name == ':: DEFAULT ::') {
            $this->resetDefault();
        } else {
            $this->resetNormalObject();
        }

        if (null !== $newOwner) {
            $this->id = $newOwner->nextAvailableID++;
        }
    }

    private function resetDefault()
    {
        foreach (array_keys($this->inheritedFieldList) as $fld) {
            $this->$fld = $this->inheritedFieldList[$fld];
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

    public function copyFrom(&$source)
    {
        MapUtility::debug("Initialising %s $this->name from $source->name\n", $this->myType());
        assert(is_object($source));

        foreach (array_keys($this->inheritedFieldList) as $fld) {
            if ($fld != 'template') {
                $this->$fld = $source->$fld;
                if ($fld == 'targets') {
                    $this->targets = array();
                    foreach ($source->targets as $tgt) {
                        $this->targets [] = clone $tgt;
                    }
                }
            }
        }
    }

    public function updateMaxValues($kilo)
    {
        foreach ($this->getChannelList() as $const) {
            $this->maxValues[$const] = StringUtility::interpretNumberWithMetricSuffix(
                $this->maxValuesConfigured[$const],
                $kilo
            );
        }

        MapUtility::debug(
            sprintf(
                "   Setting bandwidth on %s (%s -> %d bps, %s -> %d bps, KILO = %d)\n",
                $this,
                $this->maxValuesConfigured[IN],
                $this->maxValues[IN],
                $this->maxValuesConfigured[OUT],
                $this->maxValues[OUT],
                $kilo
            )
        );
    }

    public function prepareForDataCollection()
    {
        /** @var Target $target */
        foreach ($this->targets as $target) {
            MapUtility::debug("ProcessTargets: New Target: $target\n");

            $target->preProcess($this, $this->owner);
            $matchedBy = $target->findHandlingPlugin($this->owner->pluginManager);

            if ($matchedBy !== false) {
                if ($matchedBy->active) {
                    $target->registerWithPlugin($this, $this->owner);
                } else {
                    MapUtility::warn(
                        sprintf(
                            "ProcessTargets: %s, target: %s was recognised as a valid TARGET by a plugin that is unable to run (%s) [WMWARN07]\n",
                            $this,
                            $target,
                            $matchedBy->name
                        )
                    );
                }
            } else {
                MapUtility::warn(
                    sprintf(
                        "ProcessTargets: %s, target: %s was not recognised as a valid TARGET [WMWARN08]\n",
                        $this,
                        $target
                    )
                );
            }
        }
    }

    public function zeroData()
    {
        $channels = $this->getChannelList();

        foreach ($channels as $channelName => $channel) {
            $this->absoluteUsages[$channel] = 0;
        }
    }

    public function performDataCollection()
    {
        $channels = $this->getChannelList();

        MapUtility::debug("-------------------------------------------------------------\n");
        MapUtility::debug("ReadData for $this: \n");

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

        MapUtility::debug("ReadData complete for %s: %s\n", $this, join(' ', $this->absoluteUsages));
        MapUtility::debug(
            "ReadData: Setting %s,%s for %s\n",
            StringUtility::valueOrNull($this->absoluteUsages[IN]),
            StringUtility::valueOrNull($this->absoluteUsages[OUT]),
            $this
        );
    }

    /**
     * @param $channels
     * @return array
     */
    private function collectDataFromTargets($channels)
    {
        $nFails = 0;
        $dataTime = 0;

        foreach ($this->targets as $target) {
            MapUtility::debug("ReadData: New Target: $target\n");
            $target->readData($this->owner, $this);

            if ($target->hasValidData()) {
                $results = $target->getData();
                $dataTime = array_shift($results);

                foreach ($channels as $channel) {
                    MapUtility::debug("Adding %f to total for channel %d\n", $results[$channel], $channel);
                    $this->absoluteUsages[$channel] += $results[$channel];
                }
                MapUtility::debug("Running totals: %s\n", join(' ', $this->absoluteUsages));
            } else {
                MapUtility::debug("Invalid data?\n");
                $nFails++;
            }

            # keep a track of the range of dates for data sources (mainly for MRTG/textfile based DS)
            if ($dataTime > 0) {
                $this->owner->registerDataTime($dataTime);
            }
        }
        return array($nFails);
    }

    public function aggregateDataResults()
    {
        $channels = $this->getChannelList();

        foreach ($channels as $channelName => $channel) {
            // bodge for now: TODO these should be set in Reset() and readConfig()
            $value = $this->absoluteUsages[$channel];

            if ($this->duplex == 'half') {
                MapUtility::debug("Calculating percentage using half-duplex\n");
                // in a half duplex link, in and out share a common bandwidth pool, so percentages need to include both
                $value = ($this->absoluteUsages[IN] + $this->absoluteUsages[OUT]);

                if ($this->maxValues[OUT] != $this->maxValues[IN]) {
                    MapUtility::warn("ReadData: $this: You're using asymmetric bandwidth AND half-duplex in the same link. That makes no sense. [WMWARN44]\n");
                }
            }

            $this->percentUsages[$channel] = ($value / $this->maxValues[$channel]) * 100;
        }
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

            /** @var Colour $col */
            list($col, $scalekey, $scaletag) = $scale->colourFromValue($value, $this->name, $isPercent, $logWarnings);
            $this->channelScaleColours[$channel] = $col;
            $this->colours[$channel] = $col;
            $this->addNote($channelName . 'scalekey', $scalekey);
            $this->addNote($channelName . 'scaletag', $scaletag);
            $this->addNote($channelName . 'scalecolor', $col->asHTML());
        }
    }

//
//    /**
//     * @param mixed $comparison
//     * @return string
//     */
//    protected function getConfigHints($comparison)
//    {
//        $output = '';
//        foreach ($this->hints as $hintname => $hint) {
//            // all hints for DEFAULT node are for writing
//            // only changed ones, or unique ones, otherwise
//            if (($this->name == 'DEFAULT')
//                || (isset($comparison->hints[$hintname])
//                    && $comparison->hints[$hintname] != $hint)
//                || (!isset($comparison->hints[$hintname]))
//            ) {
//                $output .= "\tSET $hintname $hint\n";
//            }
//        }
//        return $output;
//    }

    /**
     * @param mixed $comparison
     * @param string $configKeyword
     * @param string $fieldName
     * @return string
     */
    protected function getConfigInOutOrBoth($comparison, $configKeyword, $fieldName)
    {
        $output = '';
        $myArray = $this->$fieldName;
        $theirArray = $comparison->$fieldName;

        if ($myArray[IN] == $myArray[OUT]) {
            $dirs = array(IN => ''); // only use the IN value, since they're both the same, but don't prefix the output keyword
        } else {
            $dirs = array(IN => 'IN', OUT => 'OUT');// the full monty two-keyword version
        }

        foreach ($dirs as $dir => $dirText) {
            if ($myArray[$dir] != $theirArray[$dir]) {
                $value = $myArray[$dir];
                if (is_array($value)) {
                    $value = join(' ', $value);
                }
                $output .= "\t" . $dirText . $configKeyword . ' ' . $value . "\n";
            }
        }
        return $output;
    }

    /**
     * @param $simpleParameters
     * @param $comparison
     * @return string
     */
    protected function getConfigSimple($simpleParameters, $comparison)
    {
        $output = '';

        # TEMPLATE must come first. DEFAULT
        if ($this->template != 'DEFAULT' && $this->template != ':: DEFAULT ::') {
            $output .= "\tTEMPLATE " . $this->template . "\n";
        }

        foreach ($simpleParameters as $param) {
            $field = $param['fieldName'];
            $keyword = $param['configKeyword'];

            if ($this->$field != $comparison->$field) {
                if ($param['type'] == CONFIG_TYPE_COLOR) {
                    $output .= "\t$keyword " . $this->$field->asConfig() . "\n";
                }
                if ($param['type'] == CONFIG_TYPE_LITERAL) {
                    $output .= "\t$keyword " . $this->$field . "\n";
                }
            }
        }
        return $output;
    }

    protected function getMaxValueConfig($comparison, $keyword = 'MAXVALUE')
    {
        $output = '';

        if (($this->maxValues[IN] != $comparison->maxValues[IN])
            || ($this->maxValues[OUT] != $comparison->maxValues[OUT])
            || ($this->name == 'DEFAULT')
        ) {
            if ($this->maxValues[IN] == $this->maxValues[OUT]) {
                $output .= "\t$keyword " . $this->maxValuesConfigured[IN] . "\n";
            } else {
                $output .= "\t$keyword " . $this->maxValuesConfigured[IN] . ' ' . $this->maxValuesConfigured[OUT] . "\n";
            }
        }

        return $output;
    }

//    private function getDirectionList()
//    {
//        return array('in' => IN, 'out' => OUT);
//    }

    public function getOverlibCentre()
    {
        return array(0, 0);
    }

    public function getOverlibDataKey()
    {
        // skip all this if it's a template node
        if ($this->isTemplate()) {
            return '';
        }

        // check to see if any of the relevant things have a value
        $key = '';
        $dirs = $this->getChannelList();
        foreach ($dirs as $name => $index) {
            $key .= join('', $this->overliburl[$index]);
            $key .= $this->notestext[$index];
        }

        return $key;
    }

    public function asConfigData()
    {
        $config = parent::asConfigData();

        $config['template'] = $this->template;

        return $config;
    }

    public function getProperty($name)
    {
        MapUtility::debug("MDI Fetching %s\n", $name);

        $translations = array();

        if (array_key_exists($name, $translations)) {
            return $translations[$name];
        }
        // TODO - at some point, we can remove this bit, and limit access to ONLY the things listed above
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        throw new WeathermapRuntimeWarning("NoSuchProperty");
    }
}
