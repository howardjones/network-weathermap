<!doctype html>
<html lang="en" xml:lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <link rel="stylesheet" type="text/css" media="screen" href="../docs/kube101/css/kube.css"/>
    <link rel="stylesheet" type="text/css" href="../docs/manual.css"/>
    <title>PHP Weathermap - Testing</title>
</head>

<body class="kubepage">
<div id="content">
    <div id="content2" class="wrapper">


        <h1>Test Results</h1>

        <ul>
            <li><?php file_link("summary.html", "Summary of all image-comparison test results") ?>(updated by
                'test.sh')
            </li>
            <li><?php file_link("summary-failing.html", "Summary of FAILING image-comparison test results") ?>(updated
                by 'test.sh')
            </li>
            <li><?php file_link("code-coverage/index.html", "Code coverage report for unit tests") ?>(updated by
                'test.sh -f')
            </li>
            <li><?php file_link("code-coverage-codeonly/index.html", "Code coverage report for unit tests, excluding config-based tests") ?>
                (updated by 'test.sh -f')
            </li>
        </ul>
        <ul>
            <li><?php file_link("md-unused.html", "PHPMD unused code report") ?>(updated by 'test.sh -f')</li>
            <li><?php file_link("md-rest.html", "PHPMD everything-else code report") ?>(updated by 'test.sh -f')</li>
        </ul>
        <ul>
            <li><?php file_link("phpcs-report-PSR-1-2.txt", "PHPCS PSR-2(ish) report") ?>(updated by 'test.sh -f')</li>
        </ul>
        <ul>
            <li><?php file_link("cut-paste.txt", "Cut & paste detector") ?>(updated by 'test.sh -f')</li>
        </ul>
        <ul>
            <li><a href="https://codeclimate.com/github/howardjones/network-weathermap">Code Climate</a></li>
        </ul>

    </div>
</div>
</body>
</html>

<?php

/**
 * Create a link to a file, annotated with when it was last updated
 *
 * @param $filename - what file are we checking?
 * @param $description - what should the link say
 */
function file_link($filename, $description)
{
    printf("<a href='%s'>%s</a> (%s) ", $filename, htmlspecialchars($description), file_age($filename));
}

/**
 * Find the age of a file, in words
 *
 * @param $filename - the file to check
 * @return string
 */
function file_age($filename) 
{
    $last_modified = filemtime($filename);
    $age = time() - $last_modified;

    if ($age<20) {
        return "Just now";
    }
    if ($age < 300) {
        return sprintf("%d seconds ago", $age);
    }
    if ($age < 3600) {
        return sprintf("%d minutes ago", $age/60);
    }
    if ($age < 86400) {
        return sprintf("%.1f hours ago", $age/3600);
    }
    return sprintf("%.1f days ago", $age/86400);
}
