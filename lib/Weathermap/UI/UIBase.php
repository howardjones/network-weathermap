<?php

namespace Weathermap\UI;

use Weathermap\Core\WeathermapInternalFail;
use Weathermap\Core\MapUtility;

/**
 * A simple base class for all the web UI tools (editor, cacti plugin parts, picker), containing a standard
 * dispatch table, and input validation
 *
 * @package Weathermap\UI
 */
class UIBase
{
    const ARG_OPTIONAL = 2;
    const ARG_TYPE = 1;
    const ARG_NAME = 0;

    private $types = array(
        "int" => "validateArgInt",
        "float" => "validateArgFloat",
        "bandwidth" => "validateArgBandwidth",
        "name" => "validateArgName",
        "jsname" => "validateArgJavascriptName",
        "mapfile" => "validateArgMapFilename",
        "mapfilelist" => "validateArgMapFilenameList",
        "string" => "validateArgString",
        "non-empty-string" => "validateArgNonEmptyString",
        "bool" => "validateArgBool",
        "item_type" => "validateArgItemType",
        "maphash" => "validateArgMapHash"
    );
    public $commands;


    public function __construct()
    {
    }

    /**
     * Given an array of request variables (usually $_REQUEST), check that the
     * request is a valid one. Does the action exist? Do the arguments match the action?
     * Do they all match the expected type?
     *
     * @param string $action
     * @param string[] $request
     *
     * @return bool
     *
     */
    public function validateRequest($action, $request)
    {
        if (!array_key_exists($action, $this->commands)) {
            error_log("Given action does not exist!");
            return false;
        }

        // Now check all the required arguments exist, and are appropriate types
        $validation = $this->commands[$action];
        foreach ($validation['args'] as $arg) {
            $required = true;
            // some args are optional (not many)
            if (isset($arg[self::ARG_OPTIONAL]) && $arg[self::ARG_OPTIONAL] === true) {
                $required = false;
            }
            // fail if a required arg is missing
            if ($required && !isset($request[$arg[self::ARG_NAME]])) {
                return false;
            }
            // Go through the args, and check they look right
            $type = $arg[self::ARG_TYPE];
            if (isset($request[$arg[self::ARG_NAME]])) {
                $value = $request[$arg[self::ARG_NAME]];
                if (!$this->validateArgument($type, $value)) {
                    return false;
                }
            }
        }

        // if we're still here, then it looked OK
        return true;
    }

    /**
     * Validate that a single value matches the expected type
     *
     * @param string $type
     * @param string $value
     * @return bool
     * @throws WeathermapInternalFail
     */
    public function validateArgument($type, $value)
    {
        $handler = null;

        if (array_key_exists($type, $this->types)) {
            $handler = $this->types[$type];
            return $this->$handler($value);
        }

        if ($type != "") {
            throw new WeathermapInternalFail("ValidateArgs saw unknown type $type");
        }
        return false;
    }

    /**
     * Call the relevant function to handle this request.
     * Pass only the expected (and by now, validated) parameters
     * from the HTTP request
     *
     * @param string $action
     * @param string[] $request
     * @param object $appObject - a reference to a relevant object (Editor in EditorUI)
     *
     * @returns bool
     */
    public function dispatchRequest($action, $request, $appObject)
    {
        if (!array_key_exists($action, $this->commands)) {
            if (array_key_exists(":: DEFAULT ::", $this->commands)) {
                $action = ":: DEFAULT ::";
            } else {
                return false;
            }
        }

        $command = $this->commands[$action];

        $params = array();
        foreach ($command['args'] as $arg) {
            if (isset($request[$arg[self::ARG_NAME]])) {
                $params[$arg[self::ARG_NAME]] = $request[$arg[self::ARG_NAME]];
            }
        }

        if (isset($command['handler'])) {
            $handler = $command['handler'];
            $result = $this->$handler($params, $appObject);

            return $result;
        }

        print "NOPE";

        return false;
    }

    /**
     * Run through the command list, making sure all the handler functions actually exist
     * and the type list, checking that the validators do too
     */
    public function selfValidate()
    {
        $result = true;

        foreach ($this->types as $type => $validator) {
            if (!method_exists($this, $validator)) {
                MapUtility::warn("$type has a missing validator ($validator)");
                $result = false;
            }
        }

        foreach ($this->commands as $command => $detail) {
            if (!isset($detail['handler'])) {
                $result = false;
                MapUtility::warn("$command doesn't specify handler");
            } else {
                $handler = $detail['handler'];
                if (!method_exists($this, $handler)) {
                    MapUtility::warn("$command has a missing handler ($handler)");
                    $result = false;
                }
            }
            foreach ($detail['args'] as $spec) {
                $type = $spec[1];
                if (!array_key_exists($type, $this->types)) {
                    MapUtility::warn("$command uses type $type which isn't defined");
                    $result = false;
                }
            }
        }
        return $result;
    }

    private function validateArgItemType($value)
    {
        if ($value == "node" || $value == "link") {
            return true;
        }
        return false;
    }

    private function validateArgMaphash($value)
    {
        // a map hash is an MD5 hash - 20 hex characters
        if (strlen($value) != 20) {
            return false;
        }
        $result = preg_match('/[^0-9a-f]/', $value);
        if ($result) {
            return false;
        }
        return true;
    }

    private function validateArgString($value)
    {
        // everything is a string
        return true;
    }

    private function validateArgNonEmptyString($value)
    {
        if (strlen($value) > 0) {
            return true;
        }

        return false;
    }

    private function validateArgMapFilename($value)
    {
        if ($value == self::wmeSanitizeConfigFile($value)) {
            return true;
        }
        return false;
    }

    private function validateArgMapFilenameList($value)
    {
        $confs = explode(",", $value);
        foreach ($confs as $conf) {
            if ($conf != self::wmeSanitizeConfigFile($conf)) {
                return false;
            }
        }
        return true;
    }

    private function validateArgJavascriptName($value)
    {
        if ($value == self::wmeSanitizeName($value)) {
            return true;
        }
        return false;
    }

    private function validateArgName($value)
    {
        if ($value == self::wmeSanitizeName($value)) {
            return true;
        }
        return false;
    }

    private function validateArgInt($value)
    {
        if (is_int($value)) {
            return true;
        }

        if ((is_numeric($value) && (intval($value) == floatval($value)))) {
            return true;
        }

        return false;
    }

    private function validateArgFloat($value)
    {
        if (is_numeric($value)) {
            return true;
        }

        return false;
    }


    private function validateArgBandwidth($value)
    {
        if (preg_match('/^(\d+\.?\d*[KMGT]?)$/', $value)) {
            return true;
        }
        return false;
    }

    private function validateArgBool($value)
    {
        if ($value == "0" || $value == "1") {
            return true;
        }

        return false;
    }


    /**
     * Clean up URI (function taken from Cacti) to protect against XSS
     *
     * @param string $str
     * @return string
     *
     */
    public static function wmeSanitizeURI($str)
    {
        static $dropMatchingCharacters = array(
            ' ',
            '^',
            '$',
            '<',
            '>',
            '`',
            '\'',
            '"',
            '|',
            '+',
            '[',
            ']',
            '{',
            '}',
            ';',
            '!',
            '%'
        );
        static $dropCharactersReplacements = array('', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '');

        return str_replace($dropMatchingCharacters, $dropCharactersReplacements, urldecode($str));
    }


    public static function wmeValidateBandwidth($bandwidth)
    {
        if (preg_match('/^(\d+\.?\d*[KMGT]?)$/', $bandwidth)) {
            return true;
        }
        return false;
    }

    public static function wmeValidateOneOf($input, $validChoices = array(), $caseSensitive = false)
    {
        if (!$caseSensitive) {
            $input = strtolower($input);
        }

        foreach ($validChoices as $choice) {
            if (!$caseSensitive) {
                $choice = strtolower($choice);
            }
            if ($choice == $input) {
                return true;
            }
        }

        return false;
    }

    // Labels for Nodes, Links and Scales shouldn't have spaces in
    public static function wmeSanitizeName($str)
    {
        return str_replace(array(" "), "", $str);
    }


    public static function wmeSanitizeFile($filename, $allowedExtensions = array())
    {
        $filename = self::wmeSanitizeURI($filename);

        if ($filename == "") {
            return "";
        }

        $clean = false;
        foreach ($allowedExtensions as $ext) {
            $match = "." . $ext;

            if (substr($filename, -strlen($match), strlen($match)) == $match) {
                $clean = true;
            }
        }
        if (!$clean) {
            return "";
        }
        return $filename;
    }

    public static function wmeSanitizeConfigFile($filename)
    {
        # If we've been fed something other than a .conf filename, just pretend it didn't happen
        $filename = self::wmeSanitizeFile($filename, array("conf"));

        # on top of the url stuff, we don't ever need to see a / in a config filename
        # (CVE-2013-3739)
        if (strstr($filename, "/") !== false) {
            $filename = "";
        }
        if (strstr($filename, "?") !== false) {
            $filename = "";
        }
        if (strstr($filename, "*") !== false) {
            $filename = "";
        }
        return $filename;
    }
}
