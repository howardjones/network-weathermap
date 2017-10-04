<?php
$criticalCount = 0;
$nonCriticalCount = 0;

// This constant was added in PHP 5.2 - since we're actually checking that the version is > 5.3, it might
// not exist in the user's version, so define it, if not
if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);

    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}

// get some basics...
$phpVersion = phpversion();
$memoryLimit = ini_get("memory_limit");
$phpOS = php_uname();

$phpWarningText = "";
if (PHP_VERSION_ID < 50300) {
    $phpWarningText = "Weathermap makes use of language features that are only available in PHP 5.3.0 and newer. You will need to upgrade.";
    $criticalCount++;
}

$memoryWarningText = "";
$memoryLimitInt = expandSuffixes($memoryLimit);
if (($memoryLimitInt > 0) && ($memoryLimitInt < 32000000)) {
    $memoryWarningText = 'You should increase this value to at least 32M. ';
}

function expandSuffixes($val)
{
    $val = trim($val);

    if ($val != '') {
        $last = strtolower($val{strlen($val) - 1});
        $val = intval($val);
        switch ($last) {
            // The 'G' modifier is available since PHP 5.1.0
            case 'g':
                $val *= 1024;
            // FALL THROUGH
            case 'm':
                $val *= 1024;
            // FALL THROUGH
            case 'k':
                $val *= 1024;
            // FALL THROUGH
        }
        return $val;
    }

    return 0;
}

/**
 * @return mixed
 */
function getPhpGeneralInfo()
{
    $phpGeneral = array();

    // capture the PHP "General Info" table
    ob_start();
    phpinfo(INFO_GENERAL);
    $capturedString = ob_get_contents();
    ob_end_clean();

    // since preg_* are potentially missing, we'll have to do this without regexps.
    foreach (explode("\n", $capturedString) as $line) {
        $line = str_replace('<tr><td class="e">', '', $line);
        $line = str_replace('</td></tr>', '', $line);
        $line = str_replace(' </td><td class="v">', ' => ', $line);
        $separatorPosition = strpos($line, " => ");
        if ($separatorPosition !== false) {
            // by here, it should be a straight "name => value"
            $name = substr($line, 0, $separatorPosition);
            $value = substr($line, $separatorPosition + 4);
            $phpGeneral[$name] = $value;
        }
    }
    return $phpGeneral;
}

$phpGeneralConfiguration = getPhpGeneralInfo();


$iniFile = $phpGeneralConfiguration['Loaded Configuration File'];
$extraIniFiles = php_ini_scanned_files();
if ($extraIniFiles != '') {
    $extraIniFiles = "The following additional ini files were read: $extraIniFiles";
} else {
    $extraIniFiles = "There were no additional ini files, according to PHP.";
}

$gdVersion = "";
$gdIsBuiltin = false;
$gdWarningText = "";
if (function_exists('gd_info')) {
    $gdinfo = gd_info();
    $gdVersion = $gdinfo['GD Version'];
    if (strpos($gdVersion, "bundled") !== false) {
        $gdIsBuiltin = true;
        $gdWarningText = "This PHP uses the 'bundled' GD library, which doesn't have alpha-blending bugs. That's good!\n";
    } else {
        $gdWarningText = "This PHP uses the system GD library, which MIGHT have alpha-blending bugs. Check that you have at least GD 2.0.34 installed, if you see problems with weathermap segfaulting.\n";
        $gdWarningText .= "You can test for this specific fault by running check-gdbug.php\n";
    }
} else {
    $gdWarningText = "The gdinfo() function is not available, which means that either the GD extension is not available, not enabled, or not installed.\n";
}

if (isset($argv)) {
    $environment = "CLI";
    print "\n----------------------------------------------------\nWeathermap Pre-Install Checker\n\n";
    print "This script checks for some common problems with your PHP and server\nenvironment that may stop Weathermap or the Editor from working.\n\n";
    print "NOTE: You should run this script as both a web page AND from the\ncommand-line, as the environment can be different in each.\n";
    print "\nThis is the PHP version that is responsible for \n* creating maps from the Cacti poller\n* the command-line weathermap tool\n\n";
    print "PHP Basics\n----------\n";
    print wordwrap("This is PHP Version $phpVersion running on \"$phpOS\" with a memory_limit of '$memoryLimit'. $memoryWarningText $phpWarningText\n");
    print "\nThe php.ini file was $iniFile\n$extraIniFiles\n\n";
    print "";
    print "PHP Functions\n-------------\n";
    print "Some parts of Weathermap need special support in your PHP\ninstallation to work.\n\n";
    print wordwrap($gdWarningText) . "\n";
} else {
    $environment = "web";
    ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <title>Weathermap Pre-Install Checker</title>
        <style type="text/css">
            body {
                font-family: 'Lucida Grande', Arial, sans-serif;
                font-size: 10pt;
            }

            p {
                margin-bottom: 10px;
                margin-top: 10px;
            }

            table {
                margin: 20px;
            }

            .critical {
                width: 400px;
                padding: 10px;
                background: #fee;
                border: 1px solid #f88;
                padding-left: 20px;
                background: left no-repeat url(images/exclamation.png);
            }

            .noncritical {
                width: 400px;
                padding: 10px;
                background: #ffe;
                border: 1px solid #fb8;
            }

            .ok {
                width: 400px;
                padding: 10px;
                background: #efe;
                border: 1px solid #8f8;
            }
        </style>
    </head>
<body>

    <h1>Weathermap Pre-install Checker</h1>

    <p>This page checks for some common problems with your PHP and server environment that may stop Weathermap or the
        Editor
        from working.</p>
    <p>NOTE: You should run this script as a web page AND from the command-line, as the environment can be different in
        each.</p>
    <h2>PHP Basics</h2>
    <p>This is the PHP version that is responsible for</p>
    <ul>
        <li>The web-based editor</li>
        <li>Building maps with Rebuild Now from Cacti</li>
    </ul>
    <p>This is PHP Version <?php echo $phpVersion ?> running on "<?php echo $phpOS ?>" with a memory_limit of
        '<?php echo $memoryLimit ?>'. <?php echo $memoryWarningText ?><?php echo $phpWarningText ?></p>
    <p>The php.ini file was <?php echo $iniFile ?></p>
    <p><?php echo $extraIniFiles ?></p>
    <h2>PHP Functions</h2>
    <p>Some parts of Weathermap need special support in your PHP installation to work.</p>
    <?php echo $gdWarningText; ?>
    <table>
    <?php
}


# critical, what-it-affects, what-it-is
$functions = array(
    'imagepng' => array(true, false, 'all of Weathermap', 'part of the GD library and the "gd" PHP extension'),
    'imagecreatetruecolor' => array(
        true,
        false,
        'all of Weathermap',
        'part of the GD library and the "gd" PHP extension'
    ),
    'imagealphablending' => array(
        true,
        false,
        'all of Weathermap',
        'part of the GD library and the "gd" PHP extension'
    ),
    'imageSaveAlpha' => array(true, false, 'all of Weathermap', 'part of the GD library and the "gd" PHP extension'),
    'preg_match' => array(true, false, 'configuration reading', 'provided by the "pcre" extension'),
    'json_encode' => array(true, false, 'editor', 'provided by the "json" extension'),
    'imagecreatefrompng' => array(
        true,
        false,
        'all of Weathermap',
        'part of the GD library and the "gd" PHP extension'
    ),
    'imagecreatefromjpeg' => array(
        false,
        false,
        'JPEG input support for ICON and BACKGROUND',
        'an optional part of the GD library and the "gd" PHP extension'
    ),
    'imagecreatefromgif' => array(
        false,
        false,
        'GIF input support for ICON and BACKGROUND',
        'an optional part of the GD library and the "gd" PHP extension'
    ),
    'imagejpeg' => array(
        false,
        false,
        'JPEG output support',
        'an optional part of the GD library and the "gd" PHP extension'
    ),
    'imagegif' => array(
        false,
        false,
        'GIF output support',
        'an optional part of the GD library and the "gd" PHP extension'
    ),
    # 'imagefilter' => array(false, false, 'colorizing icons','a special function of the PHP-supplied GD library ONLY (not the external GD library'.($gdbuiltin?'':' that you are using').')'),
    'imagecopyresampled' => array(
        false,
        false,
        'Thumbnail creation in the Cacti plugin',
        'an optional part of the GD library and the "gd" PHP extension'
    ),
    'imagettfbbox' => array(
        false,
        false,
        'trueType font support',
        'an optional part of the GD library and the "gd" PHP extension'
    ),
    'memory_get_usage' => array(
        false,
        true,
        'memory-usage debugging',
        'not supported on all PHP versions and platforms'
    )
);

$results = array();

if ($environment == 'CLI') {
    // Console_Getopt is only needed by the CLI tool.
    $included = @include_once 'Console/Getopt.php';

    if ($included != 1) {
        $nonCriticalCount++;
        print wordwrap("The Console_Getopt PEAR module is not available. The CLI weathermap tool will not run without it (that may not be a problem, if you only intend to use Cacti).\n\n");
    } else {
        print wordwrap("The Console_Getopt PEAR module is available. That's good!\n\n");
    }
}

foreach ($functions as $function => $details) {
    $exists = "";
    $notes = "";
    if ($environment == 'web') {
        print "<tr><td align=right>$function()</td>";
    }

    if (function_exists($function)) {
        $exists = "YES";
        if ($environment == 'web') {
            print "<td><img alt=\"YES\" src=\"images/tick.png\" /></td>";
        }
    } else {
        $exists = "NO";
        if ($details[0]) {
            $notes .= "CRITICAL.   ";
            if ($environment == 'web') {
                print "<td><img alt=\"NO\" src=\"images/exclamation.png\" /><b>CRITICAL</b> ";
            }
            $criticalCount++;
        } else {
            if (!$details[1]) {
                $notes .= "Non-Critical.   ";
                if ($environment == 'web') {
                    print "<td><img  alt=\"NO\" src=\"images/cross.png\" /><i>non-critical</i>  ";
                }
                $nonCriticalCount++;
            } else {
                $notes .= "Minor.   ";
                if ($environment == 'web') {
                    print "<td><img alt=\"NO\" src=\"images/cross.png\" /><i>minor</i>  ";
                }
            }
        }
        $explanation = "This is required for " . $details[2] . ". It is " . $details[3] . ".";
        $notes .= $explanation;

        if ($environment == 'web') {
            print "$explanation</td>";
        }
    }
    if ($environment == 'web') {
        print "</tr>\n";
    } else {
        $wnotes = wordwrap($notes, 50);
        $lines = explode("\n", $wnotes);
        $i = 0;
        foreach ($lines as $noteline) {
            if ($i == 0) {
                print sprintf("%20s %5s %-52s\n", $function, $exists, $noteline);
                $i++;
            } else {
                print sprintf("%20s %5s %-52s\n", "", "", $noteline);
                $i++;
            }
        }
    }
}

if ($environment == 'web') {
    print "</table>";
}

if (($criticalCount + $nonCriticalCount) > 0) {
    if ($environment == 'web') {
        print "<p>If these functions are not found, you may need to <ul><li>check that the 'extension=' line for that extension is uncommented in your php.ini file (then restart your webserver), or<li>install the extension, if it isn't installed already</ul>On Debian/Ubuntu systems, you may also need to use the php5enmod command to enable the extension.</p>";
    } else {
        print "\nIf these functions are not found, you may need to\n * check that the 'extension=' line for that extension is uncommented in\n   your php.ini file (then restart your webserver), or\n * install the extension, if it isn't installed already\n\n";
    }

    print wordwrap("The details of how this is done will depend on your operating system, and on where you installed (or compiled) your PHP from originally. Usually, you would install an RPM, or other package on Linux systems, a port on *BSD, or a DLL on Windows. If you build PHP from source, you need to add extra options to the './configure' line. Consult your PHP documention for more information.\n");
    if ($environment == 'web') {
        print "</p>";
    }
}

if ($environment == "CLI") {
    print "\n---------------------------------------------------------------------\n";
}

if ($criticalCount > 0) {
    if ($environment == 'web') {
        print "<div class=\"critical\">";
    }
    print wordwrap("There are problems with your PHP or server environment that will stop Weathermap from working. You need to correct these issues if you wish to use Weathermap.\n");
    if ($environment == 'web') {
        print "</div>";
    }
} else {
    if ($nonCriticalCount > 0) {
        if ($environment == 'web') {
            print "<div class=\"noncritical\">";
        }
        print wordwrap("Some features of Weathermap will not be available to you, due to lack of support in your PHP installation. You can still proceed with Weathermap though.\n");
        if ($environment == 'web') {
            print "</div>";
        }
    } else {
        if ($environment == 'web') {
            print "<div class=\"ok\">";
        }
        print wordwrap("OK! Your PHP and server environment *seems* to have support for ALL of the Weathermap features. Make sure you have run this script BOTH as a web page and from the CLI to be sure, however.\n");
        if ($environment == 'web') {
            print "</div>";
        }
    }
}
if ($environment == 'web') {
    print "</table></body></html>";
}



