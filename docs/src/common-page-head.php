<?php
if (!isset($PATH_EXTRA)) {
    $PATH_EXTRA = "../";
}
?><!DOCTYPE html>
<html lang="en" xml:lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" type="text/css" media="screen" href="<?php echo $PATH_EXTRA; ?>bootstrap.min.css"/>
    <link rel="stylesheet" type="text/css" href="<?php echo $PATH_EXTRA; ?>manual.css"/>
    <title>PHP Weathermap <?php echo $WEATHERMAP_VERSION; ?> - <?php echo $PAGE_TITLE; ?></title>
</head>

<body>
<?php if (!isset($FRONT_PAGE)) {
    include "common-top-nav.php";
} ?>

<div class="container">

    <header id="header">
        <div class="container">
            <h1>PHP Weathermap <?php echo $WEATHERMAP_VERSION; ?> </h1>
            <p class="lead">
                Copyright &copy; 2005-2018 Howard Jones,
                <tt><a href="mailto:howie@thingy.com">howie@thingy.com</a></tt>. (<a
                        href="http://www.network-weathermap.com/">Website</a>)
            </p>
        </div>
    </header>
    <main role="main">
