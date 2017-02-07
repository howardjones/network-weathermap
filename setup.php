<?php

if (substr($config['cacti_version'], 0, 3) == "0.8") {
    require_once "setup88.php";
}

if (substr($config['cacti_version'], 0, 2) == "1.") {
    require_once "setup10.php";
}