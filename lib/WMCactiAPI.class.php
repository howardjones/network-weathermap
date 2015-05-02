<?php

/*
 * Trying to move all our calls to Cacti-internal functions to pass through here. This should
 * remove all that Cacti database and internals knowledge from most of weathermap, and also
 * make it easier to make a plugin for some other host... this would then become a subclass of
 * a generic WMHostAPI class.
 */
class WMCactiAPI {
    static function getConfigOption($settingName, $defaultValue="")
    {
        $result = read_config_option($settingName);

        if ($result=="" && $defaultValue!="") {
            // TODO - wm_debug is not actually always available where this API is used.
            // wm_debug("No result for %s, using default\n", $defaultValue);
            $result = $defaultValue;
        }

        return $result;
    }

    static function setConfigOption($settingName, $newValue)
    {
        db_execute(
            sprintf(
                "replace into settings values('%s','%s')",
                mysql_real_escape_string($settingName),
                mysql_real_escape_string($newValue)
            )
        );
    }

    static function executeDBQuery($SQL)
    {
        return db_execute($SQL);
    }
}