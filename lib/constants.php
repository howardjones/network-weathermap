<?php
/**
 * Constants used within Weathermap
 *
 * User: Howard Jones
 * Date: 28/05/2014
 * Time: 13:59
 */

define("WEATHERMAP_VERSION","0.98");

// parameterise the in/out stuff a bit
define("IN", 0);
define("OUT", 1);
define("WMCHANNELS", 2);

define('CONFIG_TYPE_LITERAL', 0);
define('CONFIG_TYPE_COLOR', 1);

// some strings that are used in more than one place
define('FMT_BITS_IN', "{link:this:bandwidth_in:%2k}");
define('FMT_BITS_OUT', "{link:this:bandwidth_out:%2k}");
define('FMT_UNFORM_IN', "{link:this:bandwidth_in}");
define('FMT_UNFORM_OUT', "{link:this:bandwidth_out}");
define('FMT_PERC_IN', "{link:this:inpercent:%.2f}%");
define('FMT_PERC_OUT', "{link:this:outpercent:%.2f}%");

// the fields within a spine triple
define("X", 0);
define("Y", 1);
define("DISTANCE", 2);

// ***********************************************
