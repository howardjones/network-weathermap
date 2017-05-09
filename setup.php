<?php

// if weathermap is in the plugins folder while the Cacti installer is doing its thing,
// then it loads this setup.php but without the normal cacti $config defined, which breaks things.
if (isset($config) && array_key_exists("cacti_version", $config )) {
    if (substr($config['cacti_version'], 0, 3) == "0.8") {
        require_once "setup88.php";
    }

    if (substr($config['cacti_version'], 0, 2) == "1.") {
        require_once "setup10.php";
    }
}