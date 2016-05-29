<?php
include "vars.php";
$PAGE_TITLE = "Changes for this version";
include "common-page-head.php";
?>

    <h2 id="changes098">Changes For This Version (0.97c to 0.98)</h2>

    <p>This is a roll-up of all the small bugfixes for the last couple of years, especially those that stop Weathermap
        working with newer MySQL or PHP versions. There is a more substantial 1.0 release in the works, but I will
        backport
        any simple improvements or fixes to 0.98(abcd) releases. There are also small usability changes aimed at
        reducing
        repeat "error reports" in the forums.</p>

    <p>Another part of this change is that the Weathermap code is now hosted on <a
            href="https://github.com/howardjones/network-weathermap/">github</a>. There is
        also an <a href="https://github.com/howardjones/network-weathermap/issues">issue tracker</a>. There
        are two branches of code there, one (<a
            href="https://github.com/howardjones/network-weathermap/tree/0.97-maintenance">0.97-maintenance</a>) is for
        this 0.98 series of smaller changes, and the other (<a
            href="https://github.com/howardjones/network-weathermap/tree/master">master</a>) branch
        is for the ongoing rewrite work. For now, if you want to track useful work, that will be on the 0.97-maintenance
        branch.</p>

    <p>IMPORTANT NOTE - you may need to reset permissions on your users after upgrading, as I've
        finally switched to the "new-style" plugin API that's been around for 5+ years. This handles
        permissions differently, unfortunately. The new permissions have been in a few releases of Weathermap already
        so you should check if this affects you after installation.</p>

    <h3>Fixes</h3>
    <ul>
        <li>MySQL error in table-creation. MySQL 5.6 is fussier.
        <li>Editor 'delete link' broken
        <li>KILO was ignored when processing %k in special tokens
        <li>Various fixes for PHP deprecated or strict-mode warnings
        <li>Line-ending trimming in 'external script' data source
        <li>rounding error 'kinks' in angled VIA links
        <li>Config file path validation issue in editor (CVE-2013-3739)
        <li>Cloning a templated node in editor retains the template in the clone
        <li>cacti_use_ifspeed incorrect when interfaces > 20M and ifhighspeed available
        <li>More PHP 5.3/5.4/strict related errors (split -> explode)
    </ul><h3>Changes</h3>
    <ul>
        <li>Finally switch to "new-style" plugin API.
        <li>Editor data picker improved sort (thanks shd)
        <li>(Cacti plugin only) images are written to a temporary file first, to avoid displaying half-written images
        <li>Editor no longer uses editor-config.php
    </ul><h3>New Features</h3>
    <ul>
        <li>Weathermap will use anti-aliasing if your GD supports it (php-bundled GD doesn't) (thanks shd)
        <li>Special token formatting can handle timeticks and time_t formatting (%T and %t respectively)
        <li>new DATAOUTPUTFILE allows collected data to be written to a file for later use (automatically enabled in
            Cacti)
        <li>new wmdata: datasource plugin can read data from files produced by DATAOUTPUTFILE
        <li>IMAGEOUTPUTFILE and HTMLOUTPUTFILE are honoured in Cacti poller as a location for a second copy of those
            files.
        <li>Editor 'tidy link' function replaces Vert & Horiz, and does much nicer job
        <li>'retidy' option in editor to recalculate all links previously positioned with 'tidy'.
        <li>KEYBGCOLOR and KEYOUTLINECOLOR both accept 'none'
        <li>command-line weathermap has new --no-warn option to disable warnings
        <li>AICONFILLCOLOR accepts 'none' for drawing giant transparent shapes.
        <li>Extra warning for Boost users about poller_output
        <li>if there are actually 0 maps in the database, the 'Weathermaps' tab gives some basic instructions.
        <li>Cacti data picker in editor tracks most recently used hosts (thanks Zdolny)
        <li>New permission in Cacti: edit maps. Maps can be edited by authorized users without needing to enable the
            editor in the source code.

    </ul>

    <h2 id="changes097c">Changes For This Version (0.97b to 0.97c)</h2>

    <p>0.97c had no additional changes - it's just 0.97b with some silly errors fixed, and all
        the CSS files included with the correct paths. There were also reports of problems with the
        actual zip file.</p>

    <h2 id="changes097b">Changes For This Version (0.97a to 0.97b)</h2>
    <p>0.97b is a special release starting from 0.97a and backporting all the quick bugfixes from the 0.98 code.
        There are larger structural changes in 0.98 and new features, but these bugfixes were useful enough to
        warrant a new 0.97 release (especially the mysql schema change). A real 0.98 release will follow in
        due course.</p>
    <p>Also late addition to 0.97b - a couple of security analysts have pointed out flaws in the editor.
        First, the ability to remotely create .php files and then cross-site-scripting vulnerabilities. Both
        are really facets of the same thing - lack of input validation. 0.97b improves this a great deal.</p>
    <p>Thanks to Gerry Eisenhaur and Daniel Ricardo dos Santos respectively for their security bug reports.</p>
    <h3>Fixes</h3>
    <ul>
        <li>absolute SCALE definitions didn't support K (thanks wwwdrich)</li>
        <li>memory leak in poller code. Memory usage is MUCH lower now.</li>
        <li>updated mysql schema commands to use modern ENGINE instead of TYPE</li>
        <li>static datasource plugin honours KILO</li>
        <li>check-gdbug.php shouldn't complain about empty ob_flush buffers anymore</li>
        <li>SNMP DS should deal better with non-numeric (and blank) return values</li>
        <li>NINK colours were exchanged (thanks Deathwing00!)</li>
        <li>WriteConfig (i.e. editor) won't 'lose' absolute keyword from</li>
        <li>fixed some function-name clashes with other plugins</li>
        <li>PHP 5.3/5.4/strict related errors ("Creating default object from empty value")</li>
    </ul>
    <h3>Changes</h3>
    <ul>
        <li>no longer shows .-prefixed files in map config picker</li>
        <li>editor ignores attempts to rename nodes to have space in names</li>
        <li>Manual updates for changes in Cacti, and improvements in styling.</li>

        <li>The Cacti UI will warn you about fundamental file permissions problems</li>
        <li>Moved all PHP that doesn't need to be web-accessible into lib. See <a href="main.html#security">Security
                notes</a>.
        </li>
        <li>Editor won't deal with config files that don't have a .conf extension</li>
        <li>General improvements in input validation and output escaping in editor</li>
        <li>'External Script' datasource plugin is disabled by default in new installs (NOT upgrades though!)</li>
    </ul>
    <h3>New Features</h3>
    <ul>
        <li>Caching for cacti data fetched by dsstats and rrdtool/poller_output DS plugins</li>
    </ul>

    <h2 id="changes097a">Changes For This Version (0.97 to 0.97a)</h2>

    <p>Lots of bugs appeared the day after 0.97 was released! There are a few
        small features added, but mainly this is bugfixes.</p>

    <h3>Fixes</h3>

    <ul>
        <li>Incorrect action URL in 'map selector' combo box for Cacti users.</li>

        <li>cacti_graph_id set to 0 instead of ID, by rrd/poller_output and
            dsstats plugins (thanks sh0x)
        </li>

        <li>'classic' legend drew 'hidden' colour values for things like key
            background colour. (thanks jmayniac)
        </li>

        <li>PHP 5.3 deprecated code in HTML_Imagemap.class.php</li>

        <li>'Show Only First' option ignored in Cacti (thanks inko_nick)</li>

        <li>Editor deals with overlapping nodes on different ZORDERS
            properly.
        </li>

        <li>"property of non-object in editor.php line 466" while editing map
            properties (thanks to iNeo)
        </li>

        <li>no-data option on command-line didn't work</li>

        <li>Clone Node was broken in 0.97</li>

        <li>Maps with per-user permissions show up multiple times in map
            selector
        </li>

        <li>Removed incorrect warning about imagefilter and USEICONSCALE.</li>

        <li>string escaping bug with editor and direct config changes (thanks
            uhtred)
        </li>

        <li>--imageuri was ignored on command-line (thanks Marcus Stögbauer)</li>

        <li>links with targets containing spaces are broken by the editor
            (thanks Andreas Braun)
        </li>

        <li>deprecated jQuery function call in cacti-pick.php (thanks again
            Andreas Braun)
        </li>
    </ul>

    <h3>Changes</h3>

    <ul>
        <li>Group sorting is a bit more logical and the presentation nicer.</li>

        <li>cacti-integrate.php uses getopt to take more command-line params</li>

        <li>Updated jQuery to latest</li>

        <li>Number formatting will pick 1G over 1000M (and similar) (thanks
            cerbum)
        </li>

        <li>The editor is disabled by default
            - see top of editor.php (and install guide)
        </li>
    </ul>

    <h3>New Features</h3>

    <ul>
        <li>LINK WIDTH accepts decimals</li>

        <li>cacti-integrate.php can generate DSStats TARGETs too</li>

        <li>Simple VIA editing in editor (thanks to Zdolny)</li>

        <li>SCALE can accept G,M,K,T,m,u,n suffixes (for absolute scales)</li>
    </ul>

    <h2 id="changes097">Changes For This Version (0.96a to 0.97)</h2>

    <h3>Fixes</h3>

    <ul>
        <li>RRD Aggregation regexp was failing (thanks to shd)</li>

        <li>Scale numerals honour locale (thanks again, shd)</li>

        <li>THold plugin check failed with Thold 0.4.1 (PA 2.x, actually)</li>

        <li>Uninitialized variable in ReadData when plugin is disabled</li>

        <li>Zero-length link check didn't include offsets (thanks Ryan
            Botoluzzi)
        </li>

        <li>Cacti-pick should get right rra path for packagers that move the rra
            directory (e.g. Ubuntu, Debian *again*)
        </li>

        <li>DS plugins that return one value and a null should work properly</li>

        <li>"Strange" characters (e.g. /) in NODE and LINK names broke the
            imagemap.
        </li>

        <li>Map Style settings in editor were broken after internal defaults
            changes
        </li>

        <li>Imagemap no longer contains areas with no href defined</li>

        <li>SPLITPOS was ignored with VIASTYLE angled (thanks to uhtred)</li>

        <li>'AICONOUTLINECOLOR none' is actually valid now (thanks to mgb &
            Leathon)
        </li>

        <li>readdir() loop never stops, on some systems (thanks to
            jerebernard)
        </li>

        <li>bad regexp in the MRTG DS plugin (thanks to Matt McMahon)</li>

        <li>0.96 had a new 'time' DS plugin - now documented!</li>

        <li>NCFPC only complains about missing scale lines on NODEs for the
            variable that is in use.
        </li>
    </ul>

    <h3>New Features</h3>

    <ul>
        <li>USEICONSCALE no longer has special dependencies
            - and the colours are nicer too.
        </li>

        <li>Option of a dropdown selector to navigate between maps (in full-size
            view)
        </li>

        <li>Maps can be organised into groups in Cacti plugin. These appear
            as tabs in the UI for viewing maps.
        </li>

        <li>Extra variables can be defined per-group, so all maps in a group can
            have similar settings (e.g. a "24hr average" tab).
        </li>

        <li>INCLUDE keyword to include a file of common definitions (based on
            work by BorisL)
            (NOTE: this can confuse the editor sometimes
            - see the manual page for INCLUDE)
        </li>

        <li>Warning for maps that contain OVERLIBGRAPH but not 'HTMLSTYLE
            overlib'
        </li>

        <li>Warning for use of TEMPLATE not as the first line of an object
            (overwrites settings otherwise)
        </li>

        <li>SCALE will accept values below 0, and also above 100</li>

        <li>USESCALE has two new options: absolute and percent, which allows you
            to have a SCALE of absolute values
        </li>

        <li>New datasource plugin to support statistics from TheWitness's
            DSStats Cacti Plugin. This gets you daily,weekly,monthly and annual
            stats with no complicated rrdtool stuff.
        </li>

        <li>New converter to take a rrdtool-based map config and make it into a
            DSStats-based one
        </li>

        <li>static datasource can be used for negative values</li>

        <li>SNMP datasource has configurable timeout and retry values.</li>

        <li>SNMP datasource has option to give up on a failing host</li>

        <li>LABELOFFSET supports percentage compass offsets and radial offsets,
            like NODES does.
        </li>

        <li>Percentage compass offsets (NODES and LABELOFFSET) support &gt; 100%
            offsets
        </li>
    </ul>

    <h2 id="changes096a">Changes For This Version (0.96 to 0.96a)</h2>

    <p>Just the usual post-release bug reports...</p>

    <h3>Fixes</h3>

    <ul>
        <li>New z-ordering code did not work correctly on PHP4. This broke (at
            least) the editor. (thanks toe_cutter)
        </li>

        <li> \n is no longer treated as a newline in TARGETs (thanks
            NetAdmin)
        </li>

        <li> KILO was broken completely between 0.95b and 0.96 (thanks Jethro
            Binks)
        </li>

        <li> Link comments in certain positions could cause div-by-zero errors.
            (thanks again Jethro)
        </li>

        <li> USEICONSCALE didn't colorise (broken between 0.95b and 0.96 again)
            (thanks colejv)
        </li>

        <li> Managed to make LABELOFFSET case-sensitive.</li>
    </ul>

    <h2 id="changes096">Changes For This Version (0.95b to 0.96)</h2>

    <h3>Fixes</h3>

    <ul>
        <li>Cacti poller_output support works more reliably/at all on Windows</li>

        <li>Renaming a node in the editor correctly handles other
            relatively-positioned nodes and vias
        </li>

        <li>Minor issue with CRLF in map title for Cacti Plugin</li>

        <li>CLI tool set --define options incorrectly.</li>

        <li>Oneway links don't draw the INCOMMENT anymore</li>

        <li>negative TIMEPOS didn't hide the timestamp</li>

        <li>DEFAULT SCALE covers 0-100 properly (Thanks Dan Fusselman)</li>

        <li>Scaled ICON in DEFAULT didn't get overwritten properly in nodes
            (Thanks Fabrizio Carusi)
        </li>

        <li>No more floating-point imagemap coords (Thanks Trond Aspelund)</li>

        <li>RRDtool regional output (. vs ,) workaround</li>

        <li>Cacti poller_output handles NaN more gracefully now</li>

        <li>SNMP datasource should work with Windows SNMP again</li>

        <li>MRTG datasource tried to stat() URLs</li>

        <li>Error reporting for CLI --define was bad. --help text was out of
            date.
        </li>

        <li>Editor will honour LABEL from NODE DEFAULT, if it is set.</li>
    </ul>

    <h3>Changes</h3>

    <ul>
        <li>Cacti plugin uses "processed" map title now (allows {} tokens in the
            title)
        </li>

        <li>A NODE with no POSITION is not drawn, instead of drawn at 0,0.
            Useful for templates.
        </li>

        <li>A LINK with no NODES is no longer an error. Also for templates.</li>

        <li>The link_bulge secret mode bulges each side of a link independently
            now
        </li>

        <li>whitespace is stripped from the beginning and end of each line
            before parsing
        </li>
    </ul>

    <h3>New Features</h3>

    <ul>
        <li>TEMPLATE allows a node or link to copy it's settings from another,
            instead of from DEFAULT.
        </li>

        <li>RRD datasource can take SET rrd_default_path to make configs a
            little easier to read.
        </li>

        <li>RRD datasource can take SET rrd_default_in_ds and rrd_default_out_ds
            for non-Cacti users.
        </li>

        <li>RRD datasource can get Cacti query information (in poller_output
            mode ONLY) - like ifAlias, ifSpeed etc
        </li>

        <li>RRD datasource can take the ifSpeed/ifHighSpeed from the above, and
            use it in the map.
        </li>

        <li>RRD datasource fills in Cacti cacti_path_rra and cacti_url with
            Cacti base path and URL
        </li>

        <li>RRD datasource can take global SET rrd_options to add extra options
            to rrdtool command lines
        </li>

        <li>SNMP datasource also stores the raw data from the SNMP agent in
            snmp_raw_in/snmp_raw_out
        </li>

        <li>SNMP datasource allows '-' as an OID, similar to '-' targets in
            RRDs.
        </li>

        <li>Control the drawing order with ZORDER.</li>

        <li>New artificial icons: nink, inpie and outpie. See ICON in manual.</li>

        <li>Warning for probably-incorrect BWLABELPOS where in&lt;out</li>

        <li>New global SET variables to disable some common warnings you may not
            care about :-)
        </li>

        <li>Cacti management screen shows number of warnings for each map last
            time it ran
        </li>

        <li>Cacti management screen also has a link to the log entries for the
            map in question
        </li>

        <li>The TARGET aggregation thing can also take scale factors now:
            -5.5*myrrdfile.rrd
        </li>

        <li>Cacti plugin caches thumbnail sizes, improving thumbnail view
            rendering
        </li>

        <li>Cacti plugin allows adding the same map twice (more useful than it
            sounds)
        </li>

        <li>Cacti plugin allows setting of map-global variables in the
            management UI
        </li>

        <li>Cacti plugin allows settings of global map-global (across all maps)
            variables too
        </li>

        <li>Cacti plugin adds links in 'user' pages to management screen (if you
            are an admin)
        </li>

        <li>HTMLSTYLESHEET keyword allows you to specify a URL for a CSS
            stylesheet (CLI tool only)
        </li>

        <li>A few extra CSS id and class attributes, to make styling the page
            easier.
        </li>

        <li>New token: in/outscalecolor contains HTML colour code of node/link
            colours for use in NOTES
        </li>

        <li>New NODES offset type - angle+radius</li>

        <li>New NODES offset type - compass-point+percentage</li>

        <li>"KEYSTYLE inverted" - to get a thermometer-style vertical legend.</li>

        <li>"COMMENTSTYLE center" to make comments run along the centre of a
            link arrow. (and 'edge' for the usual)
        </li>

        <li>COMMENTFONTCOLOR accepts 'contrast' as an option, for when it's over
            a link
        </li>

        <li>VIASTYLE angled (or curved) - you can turn sharp corners now</li>

        <li>Comment (and pos) editing in editor (based on code by Zdolny)</li>

        <li>Editor Settings dialog works, and allows you to set grid-snap and
            some overlays
        </li>

        <li>SCALE allows 'none' as a colour (for non-gradients). Only affects
            LINKs so far.
        </li>

        <li>fping plugin allows for changing the number of pings.</li>

        <li>TARGET strings can be enclosed in quotes, to allow spaces in them
            (mainly for external ! scripts)
        </li>

        <li>"KEYSTYLE tags"
            - like classic, but uses the scale tags instead of percentages.
        </li>

        <li>scripts in random-bits to help with automatic/assisted mapping.</li>

        <li>lots more pretty pictures in the manual, so you can see what I
            mean.
        </li>

        <li>IMAGEURI keyword to match --image-uri command-line option (ignored
            in Cacti plugin)
        </li>

        <li>MINTIMEPOS and MAXTIMEPOS to track data source times</li>
    </ul>

    <h2 id="changes095">Changes For This Version (0.941 to 0.95)</h2>

    <p>This release has a lot of changes
        - most of them are small 'polishing' tweaks, but there are a few interesting
        bigger ones too. Happily, although there are a quite a few more features,
        this release actually has
        <em>less</em> lines of code than 0.941, due to some internal cleaning up.</p>

    <h3>Known Issues</h3>

    <ul>
        <li>ININFOURL / OUTINFOURL, INOVERLIBGRAPH
            / OUTOVERLIBGRAPH are not handled well by the editor. If you edit a map
            that uses these, then the 'in' side of the link will be copied to the
            'out' side. New editor will handle this better.
        </li>
    </ul>

    <h3>Fixes</h3>

    <ul>
        <li>KEYOUTLINECOLOR is actually used now (thanks to llow once more)</li>

        <li>Editor doesn't throw away WIDTH and HEIGHT with no BG image</li>

        <li>Cacti Data-source and Graph picker doesn't restrict scrolling or
            resizing anymore
        </li>

        <li>weathermap-cacti-rebuild.php to work on both Cacti 0.8.6 and
            0.8.7
        </li>

        <li>weathermap-cacti-rebuild.php to flat-out fail if Cacti environment
            is wrong.
        </li>

        <li>SNMP DS plugin had a typo that stopped it working at all (and no-one
            noticed for almost a year :-) ). (thanks to Fratissier Christophe for
            pointing it out)
        </li>

        <li>Added some better controls into SNMP DS plugin. You can correctly
            pull interface oper/admin status, for example, now.
        </li>
    </ul>

    <h3>Changes</h3>

    <ul>
        <li>DS plugins are able to return negative results now
            <strong>(breaks user-developed DS plugins)</strong></li>

        <li>the scale: prefix for the RRD DS plugin can take negative scale
            factors
        </li>

        <li>(internal) plugins are each created as a single object now. Result:
            the plugin can cache results internally.
        </li>

        <li>(internal) broke out some of the larger classes (node, link) into
            separate files.
        </li>
    </ul>

    <h3>New Features</h3>

    <ul>
        <li>You can add a 'tag' to a SCALE line, to be used in ICON or LABELs
            later.
        </li>

        <li>USEICONSCALE - colorize icon images (based on patches from llow)</li>

        <li>screenshot mode. "SET screenshot_mode 1" at the top of the map will
            anonymise all labels, comments and bwlabels.
        </li>

        <li>LABELFONTCOLOR can use a special value of 'contrast' to always
            contrast with the label colour.
        </li>

        <li>Artificial Icons. Special icon 'filenames'
            - 'box' 'round' 'rbox' create a shaped icon without any file. See ICON
            and AICONFILLCOLOR for more.
        </li>

        <li>Map titles show up in browser title now.</li>

        <li>a basic 'live view' function which generates a map on demand.
            Sometimes. It's not very useful.
        </li>

        <li>LABELANGLE allows you to rotate node labels to 90,180,270 degrees.
            Needs truetype font.
        </li>

        <li>improved data-source picker in editor: host filter</li>

        <li>improved data-source picker in editor: option to aggregate data
            sources
        </li>

        <li>Moved data-source picker changes across into the graph-picker for
            NODEs too.
        </li>

        <li>SPLITPOS keyword to control position of midpoint in links</li>

        <li>VIA can be positioned relative to NODEs (like NODEs can) (thanks
            again to llow)
        </li>

        <li>Weathermap has a hook in the map viewing page to allow other plugins
            to add code there
        </li>

        <li>.htaccess files bundled with Weathermap to restrict direct access to
            configs and output
        </li>

        <li>filenames for output are much less guessable now
            <strong>(may break external references to maps)</strong></li>

        <li>You can use 'DUPLEX half' on a link to make the bandwidth percentage
            calculate work for half-duplex links
        </li>

        <li>ININFOURL / OUTINFOURL, INOVERLIBGRAPH / OUTOVERLIBGRAPH, INNOTES
            / OUTNOTES, INOVERLIBCAPTION
            / OUTOVERLIBCAPTION allow you to have different urls for the in and out
            side of links (based on idea from llow)
        </li>

        <li>OVERLIBGRAPH (and IN/OUT versions) can take multiple URLs separated
            by spaces (again from idea by llow)
        </li>

        <li>debug/warning log output contains the map name, and the debug output
            is marked DEBUG
        </li>

        <li>debug log output contains the calling function, file/line number,
            too. Making debugging-by-mail easier.
        </li>

        <li>fping: TARGET to do live pings of devices. See targets.html</li>

        <li>a very basic sample 'skeleton' DS plugin</li>

        <li>an additional check-gdbug.php to spot bad GD installs</li>

        <li>MRTG DS plugin can do a few new tricks. See TARGET and
            targets.html
        </li>
    </ul>

    <h2 id="changes0941">Changes For This Version (0.94 to 0.941)</h2>

    <h3>Fixes</h3>

    <ul>
        <li>Added extra code to help discourage browser caching.</li>

        <li>Issue with '-' DS names again.</li>

        <li>Removed some extra chatty debugging code from poller_output</li>
    </ul>

    <h2 id="changes094">Changes For This Version (0.93 to 0.94)</h2>

    <p>0.94 was released early, to fix the one small issue caused by files
        moving around in the recent Cacti 0.8.7 release. However, there are some
        nice new features even so
        - INBWFORMAT has been requested for quite a while, and so has poller_output
        support.</p>

    <h3>Changes</h3>

    <ul>
        <li>Finally a better tab image, and a red 'active' one too, for the
            Cacti plugin.
        </li>
    </ul>

    <h3>New Features</h3>

    <ul>
        <li>INBWFORMAT and OUTBWFORMAT allow you to format the text for BWLABEL,
            same as for COMMENTs
        </li>

        <li>New cactithold/cactimonitor data source plugin reads data from
            Cacti's Threshold plugin. (Original development for this plugin was paid
            for by Stellar Consulting - Thanks!)
        </li>

        <li>New LINKSTYLE command allows you to have one-way (one arrow)
            links.
        </li>

        <li>RRD DS can use Cacti's poller_output to get data without running
            RRDtool at all. (this also means it can work with the Boost plugin for
            large installations) See
            <a href="targets.html">targets.html</a> for more info on this one.
        </li>

        <li>Editor
            - Align horizontal and Align-vertical for links. Calculates link offsets
            to make link vertical/horizontal.
        </li>
    </ul>

    <h3>Fixes</h3>

    <ul>
        <li>"Full Screen Maps" mode in Cacti Plugin was broken by me adding the
            "View Only First" mode.
        </li>

        <li>Imagemaps for horiz/vert format legend were wrong in editor (thanks
            to Alex Moura for pointing this out)
        </li>

        <li>Changes for compatibility with Cacti 0.8.7's moved config file.</li>
    </ul>

    <h2 id="changes093">Changes For This Version (0.92 to 0.93)</h2>

    <h3>Changes</h3>

    <ul>
        <li>The auth realm names for Cacti have been changed to match ReportIt
            and Aggregate
            - easier to tell who does what (Plugin -&gt; Weathermap: View Maps)
        </li>

        <li>Editor warns about older editor-config.php format now</li>

        <li>Formatted numbers (Mega, Kilo etc) now can include milli, micro and
            nano (m,u,n).
        </li>
    </ul>

    <h3>New Features</h3>

    <ul>
        <li>weathermap-cacti-plugin.php?action=viewmap&amp;id=mapfilename works
            as well as a map number - useful for crosslinks
        </li>

        <li>Warning for duplicate node or link names</li>

        <li>unique code for each warning message, and a page to explain it on
            the website. Ugh.
        </li>

        <li>warning in editor file-selector so you can tell if the file is
            read-only
        </li>

        <li>click config filename to edit in editor from Cacti (thanks to
            streaker69)
        </li>

        <li>cactihost: DS plugin fetches a bunch of other stats from Cacti's DB
            now, too (like availability and response times)
        </li>

        <li>Picking Cacti sources from editor has a javascript "live filter"
            feature now (needs a little work)
        </li>

        <li>node coordinates are directly editable in the editor now</li>

        <li>File picker allows you to use an existing map as a template</li>

        <li>Editor now allows you to clone a node with all it's styling
            intact.
        </li>

        <li>When picking coordinates (new node, move node, move timestamp etc),
            you can see the coordinates
        </li>

        <li>Editor toolbar fixed to window, to make it easier to scroll around
            large maps
        </li>

        <li>RRD Datasource has improved warnings for non-existent DS names</li>

        <li>Editor allows you to edit raw text of nodes and links</li>

        <li>Editor link in management page (warnesj)</li>

        <li>Docs link in management page too (streaker69)</li>

        <li>Editor has a better warning for unwriteable files and directory
            now.
        </li>

        <li>When you come TO the editor from Cacti, the Change File goes BACK to
            Cacti
        </li>

        <li>"Show Only First" mode in Cacti UI
            - useful for heirarchies of maps with a parent.
        </li>

        <li>scale: prefix for RRD datasource
            - multiply/divide by any value as you read an rrd datasource
        </li>
    </ul>

    <h3>Fixes</h3>

    <ul>
        <li>RRD doesn't consider DSes other than the ones you named when finding
            a valid line.
        </li>

        <li>editor-generated node names are a bit shorter (and easier to read)
            now.
        </li>

        <li>keyboard focus switches nicely to the popup dialogs now.</li>

        <li>Non-unique IDs in imagemaps, in overlib mode.</li>

        <li>COMMENTPOS 0 doesn't kill everything anymore</li>

        <li>OVERLIB would behave incorrectly with PHP4 and relatively positioned
            nodes (Bernd Ziller)
        </li>

        <li>Better-validating HTML produced</li>

        <li>angled bwlabels have the correct imagemap</li>

        <li>divide-by-zero error for some (?) PHP versions in poller</li>

        <li>the key_hidezero secret setting hides the zero in a gradient in a
            classic scale too.
        </li>
    </ul>

    <h2 id="changes092">Changes For This Version (0.91 to 0.92)</h2>

    <h3>New Features</h3>

    <ul>
        <li>BWSTYLE allows you to align the BWLABEL boxes with their links.
            Looks nice!
        </li>

        <li>COMMENTPOS allows you to move comments, much like BWLABELPOS for
            BWLABELs
        </li>

        <li>The editor works the way you'd expect for defaults now.</li>

        <li>The editor does a bunch of clever maths when moving a node that is
            part of a curved link to make a sensible result (I like this one).
        </li>

        <li>The editor will let you pick an INFOURL and OVERLIBGRAPH for a NODE
            from your Cacti graphs (but not a TARGET yet).
        </li>

        <li>The editor doesn't <em>require</em> an
            <tt>editor-config.php</tt> anymore, but if you <em>do</em> have one,
            <strong>make sure it's based on the editor-config.php-dist in this
                version</strong>. This file has changed.
        </li>

        <li>Secret SET codes: key_hidepercent_<em>scalename</em> and
            key_hidezero_<em>scalename</em>

            <br/>

            You can set these at the top of the config file, for each scale (change
            <em>scalename</em> to the scale name).
            <tt>SET key_hidezero_DEFAULT 1</tt> hides the 0-&gt;0 line if there is
            one, in a 'classic' style legend for that scale.
            <tt>SET key_hidepercent_scalename 1</tt> hides the percentage signs in
            the 'classic' style legend for that scale.
        </li>
    </ul>

    <h3>Fixes</h3>

    <ul>
        <li>SET in a DEFAULT node or link wasn't inherited properly.</li>

        <li>weathermap.conf is a simple map again. I accidentally packaged my
            test map for 0.9 and 0.91
        </li>

        <li>Any unreadable files in the configs/ directory would kill the
            editor
        </li>

        <li>One PHP short_tag remained, which upsets some PHP installs (seems to
            be mainly Windows)
        </li>
    </ul>

    <h2 id="changes091">Changes For This Version (0.9 to 0.91)</h2>

    <p>After all the big changes (below for 0.9), there are a whole bunch of
        bugs that no-one spotted in the testing, so this release is mostly a
        clean-up of those. There are a couple of small changes for new features, and
        a bit more diagnostic stuff for installation problems.</p>

    <h3>New Features</h3>

    <ul>
        <li>'Quiet Mode' Logging
            - in the Cacti plugin, there is a new setting to make Weathermap log
            <em>only</em> errors in the standard 'LOW' logging setting.
        </li>

        <li>Subtractive Aggregation
            - if one of the clauses in a TARGET line begins with a '-', then that
            value is subtracted from the result instead of added. This can be useful
            to calculate a value that couldn't be measured directly. For example,
            Netscreen firewalls don't give per-policy bandwidth stats, so to find
            out how much traffic is going to the internet, and how much to a VPN,
            you can start with the total interface traffic, and take out the VPN
            traffic (which you can measure), to leave the Internet traffic.
        </li>

        <li>There is a new
            <em>check.php</em> script, that verifies some basic requirements of your
            PHP installation. It's mainly useful during installation, so it's
            described in the installation instructions.
        </li>

        <li>A couple of additional variables are available in the 'special
            string tokens'
            - 'inscalekey' and 'outscalekey' store the internal names of the SCALE
            line that was triggered for the 'in' and 'out' values of a map object.
            You can use this to do some things like changing icons based on a value,
            without needing to write a plugin yourself. It's pretty obscure, but
            could be handy.
        </li>
    </ul>

    <h3>Fixes</h3>

    <ul>
        <li>RRD bug with '-' DS names. This was fixed in 0.9pre3, but somehow
            slipped through.
        </li>

        <li>ReadConfig doesn't complain about KEYPOS DEFAULT -1 -1 (as written
            by WriteConfig) anymore
        </li>

        <li>The MRTG plugin made some assumptions that broke handling of MRTG
            html files on remote systems. It doesn't now.
        </li>

        <li>NOTES was not fully tested, and broke cactihost: targets, at
            least.
        </li>

        <li>KILO was broken - fixed now.</li>

        <li>BWLABELPOS was handled badly by the editor
            - it would swap them over
        </li>
    </ul>

    <h2 id="changes09">Changes For This Version (0.82 to 0.9)</h2>

    <p>I've divided the changes up into chunks. Most of the serious work was
        done in the
        <a href="#structural">Structural Changes</a> section, but there are plenty
        of things for everyone. These are brief descriptions
        - see the Config Reference for the full detail. I've mentioned the
        appropriate configuration keywords if there are any.</p>

    <h3 id="graphical">Graphical Changes</h3>

    <p>These tend to be smaller things, but together they make a lot of useful
        additions.</p>

    <h4>Link comments</h4>

    <p>You can add a comment string which runs down the side of link arrows.
        This is intended for use for circuit references, or interface names. There
        are 4 new directives to make this happen: INCOMMENT, OUTCOMMENT, COMMENTFONT
        and COMMENTFONTCOLOR. You
        <em>must</em> use a TrueType COMMENTFONT to use this feature, as none of the
        other font types allow for rotation of text.</p>

    <h4>Bandwidth Label Positioning</h4>

    <p>You can now move the bandwidth labels (those little boxes) up and down
        the link arrow. You set a percentage position with BWLABELPOS.</p>

    <h4>Pixel-Offsets</h4>

    <p>You can make links end at arbitrary positions relative to a node now.
        Previously you could use compass-points, now you can also use pixels for the
        offsets in a NODES line</p>

    <h4>JPEG &amp; GIF Support</h4>

    <p>If your GD library supports it, then Weathermap now understands JPEG and
        GIF files for BACKGROUND, ICON and IMAGEOUTPUTFILE.</p>

    <h4>NOTES &amp; OVERLIBCAPTION</h4>

    <p>You can specify a caption string for the 'popup' overlib window now,
        per-node/link. You can
        <em>also</em> have HTML-formatted text in that window, with or without the
        graph. See NOTES and OVERLIBCAPTION.</p>

    <p>Use it with String Tokens to make captions for your nodes, with
        additional information.</p>

    <h4>New Legend Styles</h4>

    <p>The new KEYSTYLE directive allows you to choose between 'classic' and two
        new styles of Legend. The new styles are neater for showing gradient scales,
        or when you have a <em>lot</em> of different bands.</p>

    <h4>Relative Positioning</h4>

    <p>You can position a node relative to another node. This is handy for
        maintaining big rows of nodes, where only one needs to be fixed in position
        now.</p>


    <h3 id="structural">Structural Changes</h3>

    <p>The internal structure of Weathermap has changed quite a lot in this
        version. It started as a few small changes, but each one showed that another
        was required.</p>

    <h4>Datasource Plugins</h4>

    <p>All the data-reading parts of Weathermap are now in plugins. This is to
        allow users to add their own external data sources more easily, without
        having to change the Weathermap code. All the previous TARGET strings will
        work as before, and I've added a few new ones for 0.9:</p>

    <ul>
        <li><strong>static</strong>
            - if you need to fix a data value, you can do it without an external
            text file.
        </li>

        <li><strong>cactihost</strong>
            - read the up/down status of a host in Cacti
        </li>

        <li><strong>gauge</strong>
            - Read RRD values without any special treatment.
        </li>
    </ul>

    <p>The RRD plugin also has some improvements in dealing with error
        conditions in this release.
    </p>

    <p>There are planned plugins for Cacti (reading directly from the Cact
        database), Nagios Host Status, SNMP directly and others.</p>

    <h4>NODE Targets</h4>

    <p>NODEs can have data now. This means you can have them change colour
        according to a SCALE, just like a LINK does. Since a NODE only has one
        value, you can choose which one to use (of the two that a TARGET provides),
        with USEVALUE.</p>

    <h4>Multiple SCALEs</h4>

    <p>Because you probably don't want your nodes to change colour with the same
        values as your links, you can now have multiple SCALE sets too. The default
        one is DEFAULT, but you can define others and use them with USESCALE in a
        NODE or LINK section.</p>

    <h4>String Tokens</h4>

    <p>You can embed information from Weathermap, and from your own data, in
        almost any string in Weathermap. This allows you to do things like:</p>

    <ul>
        <li>Show percentage CPU usage in a node LABEL</li>

        <li>Change which ICON is used depending on a datasource value</li>

        <li>Add extra "sub-NODEs" around a NODE, with addional information</li>

        <li>Use generic TARGET, INFOURL, OVERLIBGRAPH lines in the DEFAULT link
            and node
        </li>
    </ul>

    <h4>Arbitrary Parameters</h4>

    <p>The new SET directive lets you define new 'variables' per-NODE, per-LINK
        or for the whole map. These can be used to give fine-tuning parameters to
        the data source plugins, and also as additional data in String Tokens.</p>

    <h3 id="cacti">Cacti Plugin</h3>

    <p>Most of the Cacti Plugin changes are bugfixes or for more advanced
        users.</p>

    <h4>Non-Standard Poller Cycle Times</h4>

    <p>If you are using the "&lt;5 minute poller" patch from TheWitness, then
        you may not want the time-consuming Weathermap process to run every poller
        cycle. There is now an option to choose how often Weathermap redraws it's
        maps, including 'Never'.</p>

    <h4>Async Map Generation</h4>

    <p>If you choose 'Never' above, then you need some way to redraw. The new
        <tt>weathermap-cacti-rebuild.php</tt> script allows you to run a separate
        cron job for Weathermap, but in the environment it would have inside the
        poller process.</p>

    <h4>Recalculate Now</h4>

    <p>If you have your permissions set up correctly, then you can force the
        redraw of all maps from within the Cacti UI, instead of waiting for a poller
        cycle.</p>

    <h3 id="editor">Editor</h3>

    <p>There are no new features in the Editor for 0.9, although a few annoying
        bugs have been squashed. I have big plans for the editor, but they are a
        whole project by themselves, so I've put it to one side. Otherwise 0.9 would
        never have appeared :-) Now this version is released, the Editor is one the
        main focus areas for the next release. </p>

    <h3 id="bugs">Bugfixes</h3>

    <p>There were a lot of bugs fixed along the way. Most were new ones, but a
        few are things that existed in 0.82 too:</p>

    <ul>
        <li>Wierd bug in HTML_ImageMap if you have a lot of similarly-named
            NODEs or LINKs.
        </li>

        <li>Floating-point limits in SCALEs work correctly now. Also, you can
            specify a 0-0 SCALE line and it will always take precedence.
        </li>

        <li>Cacti plugin doesn't assume 'admin' user always exists, and that
            users are never deleted.
        </li>

        <li>Cacti plugin's Cycle mode works in IE now.</li>

        <li>KILO was ignored in some places.</li>

        <li>Editor will let you rename nodes now!</li>

        <li>Editor deals properly with ' in node labels</li>

        <li>Editor deals properly with pathnames containing '\' on Windows.</li>

        <li>Editor lets you pick JPG and GIF images too, since we now support
            them
        </li>

        <li>Close-together nodes don't crash the curve-drawing anymore</li>
    </ul>
<?php
include "common-page-foot.php";
