<?php
    if(! isset($PATH_EXTRA)) {
        $PATH_EXTRA = "../";
    }
?><!DOCTYPE html>
<html lang = "en" xml:lang = "en" xmlns = "http://www.w3.org/1999/xhtml">
    <head>
        <link rel = "stylesheet" type = "text/css" media = "screen" href = "<?php echo $PATH_EXTRA; ?>../vendor/bootstrap/css/bootstrap.min.css" />        
        <link rel = "stylesheet" type = "text/css" href = "<?php echo $PATH_EXTRA; ?>manual.css" />
        <title>PHP Weathermap <?php echo $WEATHERMAP_VERSION; ?> - <?php echo $PAGE_TITLE; ?></title>
    </head>

    <body>
            <?php if(! isset($FRONT_PAGE)) {
                include "common-top-nav.php";
} ?>
    <sdiv id="content">
    <sdiv id="content2" class="wrapper">


<div class="container">

<div id = "header">
    <h1>PHP Weathermap <?php echo $WEATHERMAP_VERSION; ?> </h1>

    <h4>Copyright &copy; 2005-2016 Howard Jones,
    <tt><a href = "mailto:howie@thingy.com">howie@thingy.com</a></tt>. (<a
        href = "http://www.network-weathermap.com/">Website</a>)</h4>
</div>
