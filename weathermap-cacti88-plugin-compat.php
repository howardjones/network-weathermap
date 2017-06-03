<?php

// A few functions to make it possible to share code between cacti 0.8.8 and cacti 1.x

function __()
{
    $args = func_get_args();

    if (func_num_args() == 1) {
        return $args[0];
    }

    return call_user_func("sprintf", $args);
}

function __n($singular, $plural, $number)
{
    if ($number == 1) {
        return $singular;
    }
    return $plural;
}

function get_nfilter_request_var($name, $default = '')
{
    return $_REQUEST[$name];
}

function get_filter_request_var($name, $default = '')
{
    // TODO: This is supposed to be filtering the input
    return $_REQUEST[$name];
}

function isset_request_var($variable)
{
    return isset($_REQUEST[$variable]);
}

function get_request_var($name, $default = '')
{
    if (isset_request_var($name)) {
        return $_REQUEST['$name'];
    }
    return $default;
}