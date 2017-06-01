<?php

// A few functions to make it possible to share code between cacti 0.8.8 and cacti 1.x

function __($string)
{
    // TODO: needs to use varargs
    return $string;

}

function __n($singular, $plural, $number)
{
    if ($number == 1) {
        return $singular;
    }
    return $plural;
}

// TODO:
//   isset_request_var()
//   get_nfilter_request_var
//   get_filter_request_var
