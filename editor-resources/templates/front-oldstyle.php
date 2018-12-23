<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN""http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
    <link rel="stylesheet" type="text/css" media="screen" href="editor-resources/oldeditor.css"/>
    <script type="text/javascript" src="node_modules/jquery/dist/jquery.min.js"></script>
    <script src="editor-resources/editor-front.js" type="text/javascript"></script>
    <title>PHP Weathermap Editor <?php echo WEATHERMAP_VERSION; ?></title></head>
<body>
<div id="nojs" class="alert"><b>WARNING</b> - Sorry, it's partly laziness on my part, but you really need JavaScript
    enabled and DOM support in your browser to use this editor. It's a visual tool, so accessibility is already an
    issue, if it is, and from a security viewpoint, you're already running my code on your <i>server</i> so either you
    trust it all having read it, or you're already screwed.<P>If it's a major issue for you, please feel free to
        complain. It's mainly laziness as I said, and there could be a fallback (not so smooth) mode for non-javascript
        browsers if it was seen to be worthwhile (I would take a bit of convincing, because I don't see a benefit,
        personally).</div>
<div id="withjs">
    <div id="dlgStart" class="dlgProperties">
        <div class="dlgTitlebar">Welcome</div>
        <div class="dlgBody">Welcome to the PHP Weathermap <?php echo WEATHERMAP_VERSION; ?> editor.<p>
            <div style="border: 3px dashed red; background: #055; padding: 5px; font-size: 90%;"><b>NOTE:</b> This
                editor is not finished! There are many features of Weathermap that you will be missing out on if you
                choose to use the editor only.These include: curves, node offsets, font definitions, colour changing,
                per-node/per-link settings and image uploading. You CAN use the editor without damaging these features
                if you added them by hand, however.
            </div>
            <p>Do you want to:
            <p>Create A New Map:<br>
                <form method="GET">Named: <input type="text" name="mapname" size="20"><input name="action" type="hidden"
                                                                                             value="newmap"><input
                            name="plug" type="hidden" value="<?php echo $fromplug ?>"><input type="submit" value="Create">
            <p>
                <small>Note: filenames must contain no spaces and end in .conf</small>
            </p>
            </form>OR<br/>Create A New Map as a copy of an existing map:<br>
            <form method="GET">Named: <input type="text" name="mapname" size="20"> based on <input name="action"
                                                                                                   type="hidden"
                                                                                                   value="newmapcopy"><input
                        name="plug" type="hidden" value="<?php echo $fromplug ?>"><select name="sourcemap">
                    <?php foreach ($titles as $file => $title): ?>
                        <option value="<?php echo $file ?>"><?php echo $file ?></option>
                    <?php endforeach ?>
                </select><input type="submit" value="Create Copy"></form>
            OR<br/>Open An Existing Map (looking in configs):
            <ul class="filelist">
                <?php foreach ($titles as $file => $title) : ?>
                    <li>
                        <?php echo $notes[$file] ?>
                        <a href="?mapname=<?php echo $file ?>&action=nothing&plug=<?php
                            echo $fromplug ?>"><?php echo $file ?></a>
                         - <span class="comment"><?php echo $title ?></span>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
        <div class="dlgHelp" id="start_help">PHP Weathermap <?php echo WEATHERMAP_VERSION; ?> Copyright &copy;
            2005-2017
            Howard Jones -
            howie@thingy.com<br/>The current version should always be <a href="http://www.network-weathermap.com/">available
                here</a>, along with other related software. PHP Weathermap is licensed under the GNU Public License,
            version 2. See COPYING for details. This distribution also includes the Overlib library by Erik Bosrup.
        </div>
    </div>
</div>
</body>
</html>
