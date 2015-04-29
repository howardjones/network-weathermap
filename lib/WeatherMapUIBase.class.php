<?php

class WeatherMapUIBase {
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
     *
     * @returns bool
     */
    public function validateArgument($type, $value)
    {
        switch ($type) {
            case "int":
                if (is_int($value)) {
                    return true;
                }
                if ((is_numeric($value) && (intval($value) == floatval($value)))) {
                    return true;
                }
                break;
            case "name":
                if ($value == wmeSanitizeName($value)) {
                    return true;
                }
                return false;
            case "jsname":
                if ($value == wmeSanitizeName($value)) {
                    return true;
                }
                return false;
            case "mapfile":
                if ($value == wmeSanitizeConfigFile($value)) {
                    return true;
                }
                return false;
            case "string":
                return true;
            default:
                // a type was specified that we didn't know - probably a problem
                return false;
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
     *
     * @returns bool
     */
    public function dispatchRequest($action, $request, $editor)
    {
        if (!array_key_exists($action, $this->commands)) {
            return false;
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
            $result = $this->$handler($params, $editor);

            return $result;
        }

        return false;
    }

}