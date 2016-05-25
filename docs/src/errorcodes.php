<?php
        include "vars.php";
        $PAGE_TITLE="Error Code Reference";
        $PATH_EXTRA="../";
        include "common-page-head.php";
?>

            <h3>Error Code Reference</h3>

            <p>This page is mainly here so that Google can find it, but here's a
            complete list of all the error codes that Weathermap can produce, and what
            each one means.</p>

            <p>I added this after I noticed a lot of google searches for error messages,
            that were not hitting a useful page in the manual. This page should be the
            useful page.</p>

            <dl class = "errorcodes">
                <dt id="WMWARN01">[WMWARN01]</dt>

                <dd><p>"Skipping drawing very short link (<em>linkname</em>). Impossible
                to draw! Try changing WIDTH or ARROWSTYLE? "</p>

                <p>Weathermap draws the arrowheads on links in proportion to the width
                of the link. If your link is fat, then the arrowhead will be bigger. If
                you have a very short link (nodes close together), you can get a
                situation where there is not enough room to draw the
                arrowheads.</p><p>If you reduce the width of the link, using WIDTH then
                you might be able to make the arrowheads small enough so that they fit.
                You can also use ARROWSTYLE to choose a smaller style of arrowhead
                - ARROWSTYLE 1 1 is the smallest.</p>

                <p>The most common cause of this error is that you have accidentally
                placed two nodes on top of each other. In that case, move one of the
                nodes.</p>

                <p>In previous versions (0.8 to 0.82), this would result in the infamous
                "FELL THROUGH Howie's crappy binary search is wrong after all" error
                message. The solution is the same
                - move the nodes, or change the link width</p>

                </dd>

                <dt id="WMWARN02">[WMWARN02],
                <span id = "WMWARN04">[WMWARN04]</span></dt>

                <dd><p>"Angled text doesn't work with non-FreeType fonts "</p>

                <p>The standard fonts used by Weathermap are the ones bundled with the
                GD graphics library. These fonts (number 1-5 when you select fonts) can
                only be drawn 'flat', and not at an angle.</p>

                <p>If you are using link comments, or the 'angled' BWSTYLE, then
                Weathermap needs to be able to drawn text at any angle. To do this, it
                needs a Truetype font to be used for that part of the map (the bwlabel,
                or comment), which is drawn using the FreeType library.</p>

                <p>To do this, you need to find a Truetype font, put it in your
                weathermap folder, and then define a new font in your map config file:
                <code>FONTDEFINE 100 Vera 9</code></p>

                <p>Then you can use that new font (no. 100) in COMMENTFONT or BWFONT
                lines.</p>

                <p>You can find some TrueType fonts, and an example.conf that does this
                in the docs/example folder.</p><p>The reason the default comment font is
                not a TrueType font is that the FreeType library is an
                <em>optional</em> part of the GD library, so I can't assume that it will
                be available.</p>

                </dd>

                <dt id="WMWARN03">[WMWARN03]</dt>

                <dd><p>"Using a non-existent special font (<em>fontnumber</em>)
                - falling back to internal GD fonts "</p>

                <p>If you use a font number that doesn't exist by default(e.g. larger
                than 5), and you haven't got a valid FONTDEFINE line, then you will get
                this error. Weathermap will use font 5 instead of the font you asked
                for.</p>

                <p>Most common reason for this error is that the font file could not be
                found, or could not be loaded. Check the log for another error (WMWARN30
                or WMWARN31).</p>

                </dd>

                <dt id="WMWARN05">[WMWARN05]</dt>

                <dd><p>"ProcessString: <em>key</em> refers to unknown item "</p>

                <p>You used a 'Special Token' string, like {node:this:something}, but
                the 'something' you used isn't defined for the node or link you
                specified. This is usually a typographical error.</p>

                </dd>

                <dt id="WMWARN06">[WMWARN06]</dt>

                <dd><p>"Couldn't open <em>plugin-type</em> Plugin directory
                (<em>dir</em>). Things will probably go wrong. "</p>

                <p>Parts of Weathermap are loaded from plugins. If these plugin
                directories don't exist, or are not readable due to file permissions,
                then the plugins can't be loaded. All data-reading is done by plugins,
                so without the data-source plugin directory, Weathermap won't do very
                much.</p>

                <p>Check that lib/datasources, lib/pre and lib/post folders all exist
                inside your Weathermap folder. Check that they (and their contents) are
                readable by the user that Weathermap runs as (e.g. your Cacti poller
                user). On a Unix system, the directories should also be
                <em>executable</em> by that user, so that the contents of the directory
                can be seen.</p>

                </dd>

                <dt id="WMWARN07">[WMWARN07]</dt>

                <dd><p>"ReadData: <em>type</em> <em>name</em>, target:
                <em>targetstring</em> on config line
                <em>linenumber</em> was recognised as a valid TARGET by a plugin that is
                unable to run (<em>pluginname</em>) "</p>

                <p>When the datasource plugins are loaded, each one is given a chance to
                check if everything it needs is available. If something is missing, that
                plugin can decline to load. If you have a TARGET line that is aimed at a
                particular plugin, and that plugin declined to load, you will get this
                message.</p>

                <p>Examples of this type of problem would be: Using cactihost: TARGETs
                but with the command-line tool where cactihost: can't load, or using the
                rrdtool DS from the command-line, without correctly setting a path to
                rrdtool in the <tt>weathermap</tt> command-line tool.</p>

                </dd>

                <dt id="WMWARN08">[WMWARN08]</dt>

                <dd><p>"ReadData: <em>type</em> <em>name</em>, target:
                <em>targetstring</em>[4] on config line
                <em>linenumber</em> was not recognised as a valid TARGET "</p>

                <p>You have specified a TARGET that
                <em>none</em> of the datasource plugins recognised. This is usually a
                typographical error.</p>

                </dd>

                <dt id="WMWARN09">[WMWARN09]</dt>

                <dd><p>"ColourFromPercent: Attempted to use non-existent scale:
                <em>scalename</em> for <em>itemname</em> "</p>

                <p>You have added a USESCALE line to a NODE or LINK, but the scale name
                wasn't defined using SCALE lines first. Define the SCALE, or check your
                spelling.</p>

                </dd>

                <dt id="WMWARN10">[WMWARN10]</dt>

                <dd><p>"NODE
                <em>nodename</em> has a relative position to an unknown node! "</p>

                <p>You have used the relative POSITION for a NODE, but you did it
                relative to a node that doesn't exist. Probably you have a typographical
                error/spelling error...</p>

                </dd>

                <dt id="WMWARN11">[WMWARN11]</dt>

                <dd><p>"There are Circular dependencies in relative POSITION lines for
                <em>number</em> nodes. "</p>

                <p>You are using relative POSITION for nodes, but somewhere you have a
                node that is relative to another node that is relative to the first one
                again - a loop. None of the nodes in the loop can be positioned correctly.
                Solution: make at least one node in the loop have a normal absolute
                position.</p>

                </dd>

                <dt id="WMWARN12">[WMWARN12]</dt>

                <dd><p>"Failed to write map image. No function existed for the image
                format you requested. "</p>

                <p>You specified a particular format to write the map image with
                IMAGEFILE, but your php/GD installation doesn't have support for that
                image format.</p>

                <p>You'll need to either choose a different image format, or
                recompile/reinstall your php, php-gd and GD libraries to support that
                image format.</p>

                </dd>

                <dt id="WMWARN13">[WMWARN13],<span id="WMWARN15">[WMWARN15]</span>,<span id="WMWARN16">[WMWARN16]</span></dt>

                <dd><p>"Failed to overwrite existing image file
                <em>filename</em> - permissions of existing file are wrong? "</p>

                <p>Most likely when using the Cacti plugin. The files in the output/
                directory are owned by a different user from the one running weathermap,
                and so it can't overwrite those files.</p>

                <p>The most common way to get into this situation is by using the
                "Recalculate Now" button in the Cacti plugin. This creates files that
                are owned by the user that your webserver runs as. When the poller
                process comes along a few minutes later, it can no longer write over
                those files with the new data. Solution: make sure that the directory,
                and it's contents are owned by the poller user (or 'cactiuser') and
                don't use the 'Recalculate Now' button any more.</p>

                </dd>

                <dt id="WMWARN14">[WMWARN14]</dt>

                <dd><p>"Failed to create image file
                <em>filename</em> - permissions of output directory are wrong? "</p>

                <p>Again, usually in Cacti plugin.</p>

                <p>Weathermap is unable to create files in the directory that the output
                file should go
                - this is the plugins/weathermap/output/ directory if you are using the
                Cacti plugin. Make sure that directory is owned/writable by the user
                that runs your poller process. This is the same permissions that you
                would have set on the rra/ directory when installing Cacti itself.</p>

                </dd>

                <dt id="WMWARN17">[WMWARN17]</dt>

                <dd><p>"Skipping thumbnail creation, since we don't have the necessary
                function. "</p>

                <p>Your php/php-gd/GD library doesn't include the imagecopyresampled()
                function, which is required to make the thumbnail images used in the
                Cacti plugin. To get that function ,
                you'll need to update/recompile/reinstall your php, php-gd and GD
                libraries.</p>

                </dd>

                <dt id="WMWARN20">[WMWARN20]</dt>

                <dd><p>"No image (gd) extension is loaded. This is required by
                weathermap. "</p>

                <p>All the graphics work in Weathermap is done using the gd PHP
                extension. Your php installation doesn't have this extension, or it is
                not enabled.</p>

                <p>You'll need to install or enable that extension to use Weathermap.</p>

                </dd>

                <dt id="WMWARN21">[WMWARN21]</dt>

                <dd><p>"Your GD php module doesn't support PNG format. "</p>

                <p>You specified an ICON or BACKGROUND image in PNG format, and your
                php-gd extension doesn't support PNG format images.</p>

                <p>You'll need to either choose a different image format, or
                recompile/reinstall your php, php-gd and GD libraries to support that
                image format.</p>

                </dd>

                <dt id="WMWARN22">[WMWARN22]</dt>

                <dd><p>"Your GD php module doesn't support truecolor. "</p>

                <p>Weathermap requires that your GD library and php-gd extension support
                24-bit colour (or "TrueColor"). To get that function ,
                you'll need to update/recompile/reinstall your php, php-gd and GD
                libraries. Most likely, this is the result of an older version of GD.</p>

                </dd>

                <dt id="WMWARN23">[WMWARN23]</dt>

                <dd><p>"Your GD php module doesn't support thumbnail creation
                (imagecopyresampled). "</p>

                <p>See <a href = "#WMWARN17">WMWARN17</a></p>

                </dd>

                <dt id="WMWARN24">[WMWARN24]</dt>

                <dd><p>"Duplicate node name <em>nodename</em> at line
                <em>linenumber</em> - only the last one defined is used. "</p>

                <p>You have used the same name for two or more NODE lines in your
                config. This is probably a typo or cut &amp; paste error. One of the two
                NODEs will not show up in the map.</p>

                </dd>

                <dt id="WMWARN25">[WMWARN25]</dt>

                <dd><p>"Duplicate link name <em>linkname</em> at line
                <em>linenumber</em> - only the last one defined is used. "</p>

                <p>You have used the same name for two or more LINK lines in your
                config. This is probably a typo or cut &amp; paste error. One of the two
                LINKs will not show up in the map.</p>

                </dd>

                <dt id="WMWARN26">[WMWARN26]</dt>

                <dd><p>"LINK DEFAULT is not the first LINK. Defaults will not apply to
                earlier LINKs. "</p>

                <p>You should usually specify your DEFAULT LINK
                <em>before</em> all your 'real' LINKs. The reason is that when a new
                LINK is defined, it copies whatever the default settings are at that
                point in reading down the config file, so you will end up with
                inconsistent results.</p>

                </dd>

                <dt id="WMWARN27">[WMWARN27]</dt>

                <dd><p>"NODE DEFAULT is not the first NODE. Defaults will not apply to
                earlier NODEs. "</p>

                <p>You should usually specify your DEFAULT NODE
                <em>before</em> all your 'real' NODEs. The reason is that when a new
                NODE is defined, it copies whatever the default settings are at that
                point in reading down the config file, so you will end up with
                inconsistent results.</p>

                </dd>

                <dt id="WMWARN28">[WMWARN28]</dt>

                <dd><p>"Dropping LINK <em>linkname</em> - it hasn't got 2 NODES! "</p>

                <p>You defined a link where one or both of the nodes in the NODES line
                don't exist.</p>

                <p>Or you forgot to add a NODES line altogether.</p>

                <p>A link goes between two nodes, so you need to tell weathermap which
                two nodes, and they need to exist.</p>

                </dd>

                <dt id="WMWARN30">[WMWARN30]</dt>

                <dd><p>"Failed to load ttf font <em>filename</em> - at config line
                <em>linenumber</em>"</p>

                <p>You defined a TrueType/FreeType font with FONTDEFINE, but the file
                doesn't exist, or can't be loaded due to permissions issues.</p>

                </dd>

                <dt id="WMWARN31">[WMWARN31]</dt>

                <dd><p>"imagettfbbox() is not a defined function. You don't seem to have
                FreeType compiled into your gd module. "</p>

                <p>You tried to use a TrueType/FreeType font, but your GD library
                (and/or php-gd extension) don't understand FreeType. </p>

                <p>You'll need to either choose a different font format, or
                recompile/reinstall your php, php-gd, GD and perhaps freetype2 libraries
                to support that font format.</p>

                </dd>

                <dt id="WMWARN32">[WMWARN32]</dt>

                <dd><p>"Failed to load GD font: <em>filename</em> (<em>errorcode</em>)
                at config line <em>linenumber</em> "</p>

                <p>You defined a .gdf font with FONTDEFINE, but the file doesn't exist,
                or can't be loaded due to permissions issues.</p>

                </dd>

                <dt id="WMWARN33">[WMWARN33]</dt>

                <dd><p>"NewColourFromPercent: Clipped <em>number</em>% to 100% for item
                <em>itemname</em>"</p>

                <p>You are using a regular SCALE with a value that is calculated as a
                percentage of the MAXVALUE or BANDWIDTH. That calculated value was
                higher than 100%. You could fix this with one of:</p>

                <ul>
                    <li>Change BANDWIDTH or MAXVALUE to the correct value.</li>

                    <li>Use an absolute SCALE (see USESCALE)</li>

                    <li>Disable the warning by adding 'SET nowarn_clip 1' at the top of
                    the map config file.</li>
                </ul>

                </dd>

                <dt id="WMWARN34">[WMWARN34]</dt>

                <dd><p>"NewColourFromPercent: Clipped <em>number</em>% to 0% for item
                <em>itemname</em>"</p>

                <p>You are using a regular SCALE with a value that is calculated as a
                percentage of the MAXVALUE or BANDWIDTH. That calculated value was below
                0%. You could fix this with one of:</p>

                <ul>
                    <li>Use a '-' prefix on the TARGET to make the value positive, if it's
                    <em>always</em> negative, like attentuation readings.</li>

                    <li>Use an absolute SCALE (see USESCALE)</li>

                    <li>Disable the warning by adding 'SET nowarn_clip 1' at the top of
                    the map config file.</li>
                </ul>

                </dd>

                <dt id="WMWARN35">[WMWARN35]</dt>

                <dd><p>"LINK <em>linkname</em> uses a NODE with no POSITION!"</p>

                <p>Some nodes have no position, because they are used purely as a
                TEMPLATE for other nodes. You have used one of these for a real link,
                and as a result, Weathermap can't figure out where to draw the link.</p>

                </dd>

                <dt id="WMWARN36">[WMWARN36]</dt>

                <dd><p>"Using a non-existent special font (<em>number</em>)
                - falling back to internal GD fonts"</p>

                <p>You have used a command like COMMENTFONT, TITLEFONT or LABELFONT to
                change the font to a custom-defined one (number
                > 5) but there is no FONTDEFINE line for that font number, OR the font
                could not be loaded. Check back in the logs, for WMWARN30 or WMWARN31.</p>

                </dd>

                <dt id="WMWARN37">[WMWARN37]</dt>

                <dd><p>"Couldn't open ICON: '<em>filename</em>'
                - is it a PNG, JPEG or GIF?"</p>

                <p>The file you specified to use as an ICON does exist, but couldn't be
                loaded by the GD image library. Check that the file really is an image
                file.</p>

                </dd>

                <dt id="WMARN38">[WMARN38]</dt>

                <dd><p>"ICON '<em>filename</em>' does not exist, or is not readable.
                Check path and permissions."</p>

                <p>You specified an ICON, but the file couldn't be opened or couldn't be
                found at all.</p>

                </dd>

                <dt id="WMWARN39">[WMWARN39]</dt>

                <dd><p>"line
                <em>number</em>: TEMPLATE is not first line of object. Some data may be
                lost."</p>

                <p>The TEMPLATE command works by copying the settings from another node
                or link over the top of the existing items. If you use some other
                configuration commands, then use TEMPLATE, the results of those commands
                may be overwritten by the equivalent settings in the template item.</p>

                </dd>

                <dt id="WMWARN40">[WMWARN40]</dt>

                <dd><p>"line <em>number</em>: <em>NODE or LINK</em> TEMPLATE
                '<em>name</em>' doesn't exist! (if it does exist, check it's defined
                first)"</p>

                <p>You specified a TEMPLATE, but the node or link doesn't exist. Also,
                possibly, that the template node is further down in the config file.
                Best practice is to keep the templates together near the top of the
                file, along with DEFAULTs.</p>

                </dd>

                <dt id="WMWARN41">[WMWARN41]</dt>

                <dd><p>"OVERLIBGRAPH is used, but HTMLSTYLE is static. This is probably
                wrong."</p>

                <p>To generate the 'popup' graphs, the Overlib javascript is required.
                The default HTML output from Weathermap doesn't include this for
                historical reasons. If you specify OVERLIBGRAPH, you must also add
                'HTMLSTYLE overlib' to the top section of the config file.</p>

                </dd>

                <dt id="WMWARN42">[WMWARN42]</dt>

                <dd><p>"IN/OUTOVERLIBGRAPH make no sense for a NODE!"</p>

                <p>NODEs don't have a input and output area on the image, so you can't
                specify separate OVERLIBGRAPHs for each.</p>

                </dd>

                <dt id="WMWARN43">[WMWARN43]</dt>

                <dd><p>"FindScaleExtent: non-existent SCALE <em>scalename</em>"</p>

                <p>Somehow, Weathermap is trying to draw a legend for a scale that
                doesn't exist. This is probably a bug - please report.</p>

                </dd>

                <dt id="WMWARN43">[WMWARN43]</dt>

                <dd><p>"You can't make a contrast with 'none'."</p>

                <p>The 'contrast' option picks black or white depending on what colour
                the background is. When you set a background of 'none', there is no
                correct answer, so Weathermap complains. Change 'contrast' to '0 0 0' or
                '255 255 255' as appropriate.</p>

                </dd>

                <dt id="WMWARN44">[WMWARN44]</dt>

                <dd><p>"ReadData: <em>NODE/LINK</em>
                <em>name</em>: You're using asymmetric bandwidth AND half-duplex in the
                same link. That makes no sense."</p>

                <p>Half-duplex calculates the percentage usage based on
                (input+output)/max_bandwidth. You have specified different bandwidth in
                each direction, but also half-duplex. The calculation is not possible.</p>

                </dd>

                <dt id="WMWARN45">[WMWARN45]</dt>

                <dd><p>"Zero-length link <em>name</em> skipped."</p>

                <p>You have a link from a node to the same node without any VIAs. It
                isn't possible to draw.</p>

                </dd>
                
                <dt id="WMWARN50">[WMWARN50]</dt>
                <dd>
                <p>"Skipping too-short line"</p>
                <p>To draw a link correctly with arrows, requires a certain distance between nodes. If your nodes are too close together, Weathermap will just skip drawing the link. This distance depends on the width of
                the link (because the arrows are in proportion to the width). Try reducing the WIDTH of the link, changing to ARROWSTYLE compact, or moving the nodes further apart.</p>
                </dd>
               
				<dt id="WMWARN70">[WMWARN70]</dt>
                <dd>
                <p>"ReadData: {map item} target: {target} on config line {line} of {file} had no valid data, according to {plugin}"</p>
                <p>
                This tells you that for a given <em>map item</em> defined on that line of that config file, there is no data. Weathermap
                has figured out which datasource plugin should be used, but that plugin was unable to get data for this target. It could
                be a NaN in an rrd file, a non-existent file for several data sources, or a network timeout for some others. Usually the
                plugin will also log some detail just before this, but you might need to turn on DEBUG loggging to see that. 
                </p>
                </dd>
                
                
                <dt id="WMWARN99">[WMWARN99]</dt>

                <dd><p>"<em>something</em> not implemented yet"</p>

                <p>A planned feature has been partially implemented, and you've tried to
                use it. Wait for the next release.</p>

                <p>Because of the way I write Weathermap, there are sometimes parts of
                the code that are waiting ready for me to finish writing a new feature.
                If it's the only thing holding back a release, I'll just disable that
                feature for now.</p>

                </dd>

                <dt id="WMEDIT01">[WMEDIT01]</dt>

                <dd><p>"The map config directory is not writable by the web server user.
                You will not be able to edit any files until this is corrected."</p>
    <p></p>

                </dd>

                <dt id="WMEDIT02">[WMEDIT02]</dt>

                <dd><p>"OLD editor config file format. The format of this file changed
                in version 0.92
                - please check the new editor-config.php-dist and update your
                editor-config.php file."</p>
    <p></p>

                </dd>

                <dt id="WMIMG01">[WMIMG01]</dt>

                <dd><p>"Image file
                <em>filename</em> is GIF, but GIF is not supported by your GD library.
                "</p>
    <p></p>

                </dd>

                <dt id="WMIMG02">[WMIMG02]</dt>

                <dd><p>"Image file
                <em>filename</em> is JPEG, but JPEG is not supported by your GD library.
                "</p>
    <p></p>

                </dd>

                <dt id="WMIMG03">[WMIMG03]</dt>

                <dd><p>"Image file
                <em>filename</em> is PNG, but PNG is not supported by your GD library.
                "</p>
    <p></p>

                </dd>

                <dt id="WMIMG04">[WMIMG04]</dt>

                <dd><p>"Image file <em>filename</em> wasn't recognised
                (type=<em>type</em>). Check format is supported by your GD library. "</p>
    <p></p>

                </dd>

                <dt id="WMIMG05">[WMIMG05]</dt>

                <dd><p>"Image file
                <em>filename</em> is unreadable. Check permissions. "</p>
    <p></p>

                </dd>

                <dt id="WMPOLL01">[WMPOLL01]</dt>

                <dd><p>"About to write image file. If this is the last message in your
                log, increase memory_limit in php.ini"</p>

                <p>PHP has a hard memory limit, to stop runaway scripts from killing
                your server. Depending on the version of PHP, this can be quite low (8M
                in older versions). Weathermap uses a lot (relatively) of memory to
                produce the large image files. If it hits the PHP memory limit, then PHP
                just kills the script with no warning. Because it can't log that it is dead
                (because it is dead), it logs just before the 'risk' happens.
                <em>If this log message is
                <strong>not</strong> the last message in your log file, then
                <strong>nothing is wrong</strong>!</em></p>

                <p>You can disable some messages like this one by changing settings in
                Cacti. Go to Settings..Misc and set Weathermap Logging to 'Quiet'. If
                you suddenly get a mystery where Weathermap is only updating some maps,
                turn it back to 'Chatty' to see what is happening.</p>

                </dd>

                <dt id="WMPOLL02">[WMPOLL02]</dt>

                <dd><p>"Failed to overwrite
                <em>filename</em> - permissions of existing file are wrong? "</p>

                <p>Weathermap couldn't overwrite an output file. Files in the
                weathermap/output/ directory should be writable by the Cacti poller
                user.</p>

                </dd>

                <dt id="WMPOLL03">[WMPOLL03]</dt>

                <dd><p>"Failed to create
                <em>filename</em> - permissions of output directory are wrong?"</p>

                <p>The weathermap/output/ directory should be writable by the Cacti
                poller user.</p>

                </dd>

                <dt id="WMPOLL04">[WMPOLL04]</dt>

                <dd><p>"Mapfile <em>filename</em> is not readable or doesn't exist"</p>

                <p>The mapfile could not be opened. That could be because it is not
                readable by the Cacti poller user, or because the file no longer exists
                but is still in the database (check the Manage..Weathermaps screen in
                Cacti).</p>

                </dd>

                <dt id="WMPOLL05">[WMPOLL05]</dt>

                <dd><p>"No activated maps found. "</p>

                <p>There are no maps in the database, or no maps that are enabled at the
                moment. There is also a scheduling facility, so if no maps are due to be
                updated according to their schedule, you will get this message. The
                scheduling feature is not yet in use in 0.97.</p>

                </dd>

                <dt id="WMPOLL06">[WMPOLL06]</dt>

                <dd><p>"Output directory (<em>directory</em>) isn't writable (tried to
                create '<em>filename</em>'). No maps created. You probably need to make
                it writable by the poller process (like you did with the RRA
                directory)"</p>

                <p>The weathermap/output/ directory should be writable by the Cacti
                poller user.</p>

                </dd>

                <dt id="WMPOLL07">[WMPOLL07]</dt>

                <dd><p>"Output directory (<em>directory</em>) doesn't exist!. No maps
                created. You probably need to create that directory, and make it
                writable by the poller process (like you did with the RRA directory)"</p>

                <p>The weathermap/output/ directory doesn't exist. It is part of the
                standard zip package, so you probably deleted or renamed it.</p>

                </dd>

                <dt id="WMPOLL08">[WMPOLL08]</dt>

                <dd><p>"Required modules for PHP Weathermap
                <em>versionnumber</em> were not present. Not running."</p>

                <p>Run 'php check.php' from the command-line, to find out what modules
                are missing.</p>

                </dd>

                <dt id="WMFPING01">[WMFPING01]</dt>

                <dd><p>"RRD DS: RRDTool exists but is not executable? "</p>
    <p></p>

                </dd>

                <dt id="WMFPING02">[WMFPING02]</dt>

                <dd><p>"FPing ReadData: Can't find fping executable. Check path at line
                19 of WeatherMapDataSource_fping.php"</p>
    <p></p>

                </dd>

                <dt id="WMFPING03">[WMFPING03]</dt>

                <dd><p>"FPing ReadData: No lines read. Bad hostname?
                (<em>target</em>)"</p>
        <p></p>

                </dd>

                <dt id="WMRRD01">[WMRRD01]</dt>

                <dd><p>"RRD DS: RRDTool exists but is not executable? "</p>

                <p>Somehow you have the right path for rrdtool, but the rrdtool binary
                is not executable by the user that runs weathermap (e.g. your Cacti
                poller user). Check permissions on your rrdtool binary.</p>

                </dd>

                <dt id="WMRRD02">[WMRRD02]</dt>

                <dd><p>"RRD DS: Can't find RRDTOOL. Check line 29 of the 'weathermap'
                script. RRD-based TARGETs will fail. "</p>

                <p>You are using the command-line
                <tt>weathermap</tt> tool, but the path near the top of that file (around
                line 29) is not the correct path to your rrdtool binary. Edit that file
                and put in the correct path if you need Weathermap to be able to read
                .rrd files.</p>

                </dd>

                <dt id="WMRRD03">[WMRRD03]</dt>

                <dd><p>"RRD DS: Can't find RRDTOOL. Check your Cacti config. "</p>

                <p>You have Weathermap integrated into Cacti, but your Cacti settings
                include an incorrect path to rrdtool. Probably you have a Cacti that
                doesn't update properly at this stage too, so change the path in
                Console..Settings..Paths (in Cacti) to point to the right location.</p>

                </dd>

                <dt id="WMRRD04">[WMRRD04]</dt>

                <dd><p>"RRD ReadData: failed to open pipe to RRDTool:
                <em>phperrormsg</em> "</p>

                <p>This is probably a Weathermap bug. It was unable to get rrdtool to
                read a .rrd file even though the file exists. It
                <em>might</em> be that the user running Weathermap (or the poller)
                doesn't have execute permission for rrdtool.</p>

                </dd>

                <dt id="WMRRD06">[WMRRD06]</dt>

                <dd><p>"Target <em>rrdfile</em> doesn't exist. Is it a file? "</p>

                <p>You have given an RRD TARGET which is a directory instead of a file,
                or something else unusual like that...</p>

                </dd>

                <dt id="WMRRD07">[WMRRD07]</dt>

                <dd><p>"RRD ReadData: poller_output:
                <em>dsname</em> is not a valid DS name for
                <em>rrdfilename</em> - valid names are: <em>dsnames</em>"</p>

                <p>This is explained in
                <a href = "http://www.network-weathermap.com/articles/non-standard-ds-names">this
                article</a></p>

                </dd>

                <dt id="WMRRD08">[WMRRD08]</dt>

                <dd><p>"RRD ReadData: poller_output:
                <em>filename</em> is not a valid RRD filename within this Cacti install.
                &lt;path_rra&gt; is <em>pathname</em>"</p>

                <p>The poller_output support works by looking in the Cacti database for
                a filename that matches your TARGET line. However, Cacti stores the
                filenames relative to the rra/ directory. Weathermap tries to convert
                your filename to that format, so it can find it in the database, but it
                sometimes gets it wrong. The &lt;path_rra&gt; is the path that it is
                expecting to be on the beginning of absolute paths in your TARGET.</p>

                </dd>

                <dt id="WMRRD09">[WMRRD09]</dt>

                <dd><p>"Not enough output from RRDTool."</p>

                <p>Weathermap ran rrdtool to read the rrd file for your TARGET, but only
                got one line of output, probably an error message. Run with debug
                logging to see what the output was, which should help figuring out what
                is wrong.</p>

                </dd>

                <dt id="WMRRD10">[WMRRD10]</dt>

                <dd><p>"Can't use poller_output for rrd-aggregated data
                - disabling rrd_use_poller_output"</p>
        <p></p>

                </dd>

                <dt id="WMRRD12">[WMRRD12]</dt>

                <dd><p>"RRD ReadData: poller_output - Cacti environment is not right"</p>
    <p></p>

                </dd>

                <dt id="WMDSSTATS01">[WMDSSTATS01]</dt>

                <dd><p>"DSStats ReadData: Failed to find a filename for DS id
                <em>number</em>"</p>
    <p></p>

                </dd>

                <dt id="WMEXT01">[WMEXT01]</dt>

                <dd><p>"ExternalScript ReadData: Failed to run external script."</p>
        <p></p>

                </dd>

                <dt id="WMEXT02">[WMEXT02]</dt>

                <dd><p>"ExternalScript ReadData: Not enough lines read from external
                script (<em>number</em> read, 4 expected)"</p>
        <p></p>

                </dd>

                <dt id="WMSNMP01">[WMSNMP01]</dt>

                <dd><p>"SNMP for <em>hostname</em> has reached
                <em>number</em> failures. Skipping."</p>

                <p>The SNMP datasource has a special feature to keep track of how many
                failures each host has had. This is to avoid the Weathermap process
                timing out 400 times on a chassis switch that is down. After the first
                few, it assumes that all requests to that device will fail. See the
                <a href = "targets.html#snmp">SNMP DS documentation</a> for information
                on how to tune this.</p>

                </dd>

                <dt id="WMTIME01">[WMTIME01]</dt>

                <dd><p>"Time DS Plugin recognised a TARGET, but needs PHP5+ to run."</p>

                <p>The Time DS plugin uses a PHP function that was introduced in PHP 5,
                and can't run on PHP 4.</p>

                </dd>

                <dt id="WMTIME02">[WMTIME02]</dt>

                <dd><p>"Time ReadData: Couldn't recognize
                <em>string</em> as a valid timezone name"</p>

                <p>The timezone string you used was not correct. Probably a typo.</p>

                </dd>
	</dl>
<?php
        include "common-page-foot.php";
