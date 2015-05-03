<?php
/*
 * Trying to move all our calls to Cacti-internal functions to pass through here. This should
 * remove all that Cacti database and internals knowledge from most of weathermap, and also
 * make it easier to make a plugin for some other host... this would then become a subclass of
 * a generic WMHostAPI class.
 */
class WMCactiAPI
{
    public static function getConfigOption($settingName, $defaultValue = "")
    {
        $result = read_config_option($settingName);

        if ($result=="" && $defaultValue!="") {
            self::log("No result for %s, using default\n", $defaultValue);
            $result = $defaultValue;
        }

        return $result;
    }

    public static function setConfigOption($settingName, $newValue)
    {
        db_execute(
            sprintf(
                "replace into settings values('%s','%s')",
                mysql_real_escape_string($settingName),
                mysql_real_escape_string($newValue)
            )
        );
    }

    public static function executeDBQuery($SQL)
    {
        return db_execute($SQL);
    }

    public static function log($message)
    {
        if (func_num_args() > 1) {
            $args = func_get_args();
            $message = call_user_func_array('sprintf', $args);
        }

        if (function_exists('debug_log_insert')) {
            cacti_log("DEBUG: " . rtrim($message), true, "WEATHERMAP");
        }
    }

    public static function pageTop()
    {
        global $config;
        require_once $config["base_path"] . "/include/top_graph_header.php";
    }

    public static function pageTopConsole()
    {
        global $config;
        require_once $config["base_path"] . "/include/top_header.php";
    }

    public static function pageBottom()
    {
        global $config;
        require_once $config["base_path"] . "/include/bottom_footer.php";
    }

    public static function getUserID()
    {
        $userID = (isset($_SESSION["sess_user_id"]) ? intval($_SESSION["sess_user_id"]) : 1);

        return $userID;
    }
}
