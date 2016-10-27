<?php

class WeatherMapUIBase
{
    public $commands;

    const ARG_OPTIONAL = 2;
    const ARG_TYPE = 1;
    const ARG_NAME = 0;

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
        $types = array(
            "int" => "validateArgInt",
            "name" => "validateArgName",
            "jsname" => "validateArgJavascriptName",
            "mapfile" => "validateArgMapFilename",
            "string" => "validateArgString",
            "bool" => "validateArgBool",
            "maphash" => "validateArgMapHash"
        );

        $handler = null;

        if (array_key_exists($type, $types)) {
            $handler = $types[$type];
            return $this->$handler($value);
        }

        if ($type != "") {
            throw new WeathermapInternalFail("ValidateArgs saw unknown type");
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
        return true;
    }

    private function validateArgMapFilename($value)
    {
        if ($value == wmeSanitizeConfigFile($value)) {
            return true;
        }
        return false;
    }

    private function validateArgJavascriptName($value)
    {
        if ($value == wmeSanitizeName($value)) {
            return true;
        }
        return false;
    }

    private function validateArgName($value)
    {
        if ($value == wmeSanitizeName($value)) {
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

    private function validateArgBool($value)
    {
        if ($value == "0" || $value == "1") {
            return true;
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

        $command_info = $this->commands[$action];

        $params = array();
        foreach ($command_info['args'] as $arg) {
            if (isset($request[$arg[self::ARG_NAME]])) {
                $params[$arg[self::ARG_NAME]] = $request[$arg[self::ARG_NAME]];
            }
        }

        if (isset($command_info['handler'])) {
            $handler = $command_info['handler'];
            $result = $this->$handler($params, $appObject);

            return $result;
        }

        print "NOPE";

        return false;
    }

    public function dispatch($action, $request)
    {
        $handler = null;

        if (array_key_exists($action, $this->commands)) {
            $handler = $this->commands[$action];
        }
        if (array_key_exists(":: DEFAULT ::", $this->commands)) {
            $handler = $this->commands[":: DEFAULT ::"];
        }
        if (null === $handler) {
            return;
        }

        // TODO - add argument parse/validation in here

        $handlerMethod = $handler['handler'];
        $this->$handlerMethod($request);
    }
}


/**
 * Clean up URI (function taken from Cacti) to protect against XSS
 *
 * @param string $str
 * @return string
 *
 */
function wmeSanitizeURI($str)
{
    static $drop_char_match =   array(' ','^', '$', '<', '>', '`', '\'', '"', '|', '+', '[', ']', '{', '}', ';', '!', '%');
    static $drop_char_replace = array('', '', '',  '',  '',  '',  '',   '',  '',  '',  '',  '',  '',  '',  '',  '', '');

    return str_replace($drop_char_match, $drop_char_replace, urldecode($str));
}

// much looser sanitise for general strings that shouldn't have HTML in them
function wmeSanitizeString($str)
{
    static $drop_char_match =   array('<', '>' );
    static $drop_char_replace = array('', '');

    return str_replace($drop_char_match, $drop_char_replace, urldecode($str));
}

function wmeValidateBandwidth($bandwidth)
{
    if (preg_match('/^(\d+\.?\d*[KMGT]?)$/', $bandwidth)) {
        return true;
    }
    return false;
}

function wmeValidateOneOf($input, $validChoices = array(), $caseSensitive = false)
{
    if (! $caseSensitive) {
        $input = strtolower($input);
    }

    foreach ($validChoices as $choice) {
        if (! $caseSensitive) {
            $choice = strtolower($choice);
        }
        if ($choice == $input) {
            return true;
        }
    }

    return false;
}

// Labels for Nodes, Links and Scales shouldn't have spaces in
function wmeSanitizeName($str)
{
    return str_replace(array(" "), "", $str);
}

function wmeSanitizeSelected($str)
{
    $result = urldecode($str);

    if (! preg_match('/^(LINK|NODE):/', $result)) {
        return "";
    }
    return wmeSanitizeName($result);
}

function wmeSanitizeFile($filename, $allowed_exts = array())
{
    $filename = wmeSanitizeURI($filename);

    if ($filename == "") {
        return "";
    }

    $clean = false;
    foreach ($allowed_exts as $ext) {
        $match = ".".$ext;

        if (substr($filename, -strlen($match), strlen($match)) == $match) {
            $clean = true;
        }
    }
    if (! $clean) {
        return "";
    }
    return $filename;
}

function wmeSanitizeConfigFile($filename)
{
    # If we've been fed something other than a .conf filename, just pretend it didn't happen
    $filename = wmeSanitizeFile($filename, array("conf"));

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
