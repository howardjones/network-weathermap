<?php 
// template class for data sources. All data sources extend this class.
// I really wish PHP4 would just die overnight
class WeatherMapDataSource
{
    // Initialize - called after config has been read (so SETs are processed)
    // but just before ReadData. Used to allow plugins to verify their dependencies
    // (if any) and bow out gracefully. Return false to signal that the plugin is not
    // in a fit state to run at the moment.
    function Init(&$map)
    {
        return true;
    }

    // called with the TARGET string. Returns true or false, depending on whether it wants to handle this TARGET
    // called by map->ReadData()
    function Recognise( $targetstring )
    {
        return false;
    }

    // the actual ReadData
    //   returns an array of two values (in,out). -1,-1 if it couldn't get valid data
    //   configline is passed in, to allow for better error messages
    //   itemtype and itemname may be used as part of the target (e.g. for TSV source line)
    // function ReadData($targetstring, $configline, $itemtype, $itemname, $map) { return (array(-1,-1)); }
    function ReadData($targetstring, &$map, &$item)
    {
        return(array(-1, -1));
    }

    // pre-register a target + context, to allow a plugin to batch up queries to a slow database, or snmp for example
    function Register($targetstring, &$map, &$item)
    {

    }

    // called before ReadData, to allow plugins to DO the prefetch of targets known from Register
    function Prefetch(&$map)
    {

    }

    // Run after all data collection
    // some plugin might need to update a local cache, or other state
    function CleanUp(&$map)
    {

    }
}
    
// template classes for the pre- and post-processor plugins
class WeatherMapPreProcessor
{
    function run(&$map)
    {
        return false;
    }
}

class WeatherMapPostProcessor
{
    function run(&$map)
    {
        return false;
    }
}
