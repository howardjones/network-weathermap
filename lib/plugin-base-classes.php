<?php

// template class for data sources. All data sources extend this class.
// I really wish PHP4 would just die overnight
class WeatherMapDataSource
{
    protected $owner;
    protected $regexpsHandled;
    protected $recognised;
    protected $name;
    protected $data;
    protected $dataTime;


    public function __construct()
    {
        $this->recognised = 0;
        $this->regexpsHandled = array();

        $this->data = array();
        $this->dataTime = 0;
        $this->data[IN] = null;
        $this->data[OUT] = null;
        $this->name = "Unnamed";
    }


// Initialize - called after config has been read (so SETs are processed)
// but just before ReadData. Used to allow plugins to verify their dependencies
// (if any) and bow out gracefully. Return FALSE to signal that the plugin is not
// in a fit state to run at the moment.
    function Init(&$map)
    {
        $this->owner = $map;

        return TRUE;
    }

    /**
     * called with the TARGET string by map->ReadData()
     *
     * Default implementation just checks the regexps in regexpsHandled[], so you may not need to implement at all.
     *
     * @return bool Returns true or false, depending on whether it wants to handle this TARGET
     *
     */
    public function Recognise($targetString)
    {
        foreach ($this->regexpsHandled as $regexp) {
            if (preg_match($regexp, $targetString)) {
                $this->recognised++;
                return true;
            }
        }
        return false;
    }


    /**
     * the actual ReadData
     * returns an array of two values (in,out). -1,-1 if it couldn't get valid data
     *
     * @param string $targetstring
     * @param WeatherMap $map
     * @param WeatherMapDataItem $item
     * @return array
     */
    function ReadData($targetstring, &$map, &$item)
    {
        return (array(-1, -1));
    }

    /**
     * Prepare data for returning, and log it.
     * Removing some move boilerplate from data plugins, and also making the move
     * to multiple channels (>2) easier in the future.
     *
     * @return array
     */
    function ReturnData()
    {
        wm_debug(
            sprintf(
                "%s ReadData: Returning (%s, %s, %s)\n",
                $this->name,
                WMUtility::valueOrNull($this->data[IN]),
                WMUtility::valueOrNull($this->data[OUT]),
                $this->dataTime
            )
        );

        return (array($this->data[IN], $this->data[OUT], $this->dataTime));
    }

    /**
     * pre-register a target + context, to allow a plugin to batch up queries to a slow database, or SNMP for example
     *
     * @param string $targetstring A clause from a TARGET line, after being processed by ProcessString
     * @param WeatherMap $map the WeatherMap main object
     * @param WeatherMapDataItem $item the specific WeatherMapItem that this target is for
     */
    public function Register($targetstring, &$map, &$item)
    {

    }

    /**
     * called before ReadData, to allow plugins to DO the prefetch of targets known from Register
     *
     * @param WeatherMap $map the WeatherMap main object
     */
    public function Prefetch(&$map)
    {

    }

    /**
     * Run after all data collection
     * some plugin might need to update a local cache, close files, or other state
     *
     * @param WeatherMap $map the WeatherMap main object
     */
    public function CleanUp(&$map)
    {

    }


}


/**
 * Class WeatherMapPreProcessor
 *
 * Base class for pre-processing plugins.
 */
class WeatherMapPreProcessor
{
    protected $owner;

    public function Init(&$map)
    {
        $this->owner = $map;

        return true;
    }

    /**
     * The only API for a PreProcessor - do whatever it is that you are supposed to do.
     *
     * @param WeatherMap $map the WeatherMap main object
     * @return bool Success/Failure? Not currently interpreted.
     */
    public function run()
    {
        return false;
    }
}

/**
 * Class WeatherMapPostProcessor
 *
 * Base class for post-processing plugins.
 */
class WeatherMapPostProcessor
{
    protected $owner;

    public function Init(&$map)
    {
        $this->owner = $map;

        return true;
    }

    /**
     * The only API for a PostProcessor - do whatever it is that you are supposed to do.
     *
     * @param WeatherMap $map the WeatherMap main object
     * @return bool Success/Failure? Not currently interpreted.
     */
    public function run()
    {
        return false;
    }
}

/**
 * Class WeatherMapDataPicker
 *
 * Future plan - the picker in the editor will use this class to present
 * TARGET and OVERLIBGRAPH options from sources other than Cacti.
 *
 */
class WeatherMapDataPicker
{
    // TBD

    private $owner;

    public function Init(&$map)
    {
        $this->owner = $map;

        return true;
    }
}