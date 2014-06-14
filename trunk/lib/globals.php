<?php
    $WEATHERMAP_VERSION="0.98";
    $weathermap_debugging=false;
    $weathermap_debugging_readdata=false;
    $weathermap_map="";
    $weathermap_warncount=0;
    $weathermap_lazycounter=0;
    
    // don't produce debug output for these functions
    $weathermap_debug_suppress = array (
    #    'processstring',
        'mysprintf'
    );
    
    // don't output warnings/errors for these codes (WMxxx)
    $weathermap_error_suppress = array();
    
    // Turn on ALL error reporting for now.
    // error_reporting (E_ALL|E_STRICT);
    error_reporting(E_ALL);
    error_reporting(E_ALL & ~E_STRICT);
