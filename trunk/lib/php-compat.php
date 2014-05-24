<?php

/**
 * Parts of the PEAR PHP_Compat library, to avoid another dependency for a handful of functions.
 * 
 * http://pear.php.net/package/PHP_Compat/
 * 
 */





/**
 * Replace property_exists()
 *
 * @category    PHP
 * @package     PHP_Compat
 * @license     LGPL - http://www.gnu.org/licenses/lgpl.html
 * @copyright   2004-2007 Aidan Lister <aidan@php.net>, Arpad Ray <arpad@php.net>
 * @link        http://php.net/property_exists
 * @author      Christian Stadler <webmaster@ragnarokonline.de>
 * @version     $Revision: 269597 $
 * @since       PHP 5.1.0
 * @require     PHP 4.0.0 (user_error)
 */
function php_compat_property_exists($class, $property)
{
    if (!is_string($property)) {
        user_error('property_exists() expects parameter 2 to be a string, ' .
            gettype($property) . ' given', E_USER_WARNING);
        return false;
    }

    if (is_object($class) || is_string($class)) {
        if (is_string($class)) {
            if (!class_exists($class)) {
                return false;
            }

            $vars = get_class_vars($class);
        } else {
            $vars = get_object_vars($class);
        }

        // Bail out early if get_class_vars or get_object_vars didnt work
        // or returned an empty array           
        if (!is_array($vars) || count($vars) <= 0) {
            return false;
        }

        $property = strtolower($property);
        foreach (array_keys($vars) AS $varname) {
            if (strtolower($varname) == $property) {
                return true;
            }
        }
                
        return false;
    }

    user_error('property_exists() expects parameter 1 to be a string or ' .
        'an object, ' . gettype($class) . ' given', E_USER_WARNING);
    return false;

}


// Define
if (!function_exists('property_exists')) {
    function property_exists($class, $property)
    {
        return php_compat_property_exists($class, $property);
    }
}

if (!function_exists('microtime')) {
    function microtime($as_float)
    {
        if($as_float) {
            return time();
        } else {
            return sprtinf("%d 0", time());
        }
    }
}
