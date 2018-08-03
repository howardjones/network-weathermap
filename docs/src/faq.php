<?php
	include "vars.php";
	$PAGE_TITLE="FAQ &amp; Useful Tips";
	$PATH_EXTRA="../";
	include "common-page-head.php";
?>
            <h2 id = "faq">FAQ &amp; Useful Tips</h2>

            <h3>FAQs</h3>

            <p>Here are a few things that have come up more than once from previous
            versions. Also, it's worth checking the <a href =
                "http://www.network-weathermap.com/">online copy</a> of this document,
            which will be updated over time.</p>

            <p class = "important">If something isn't described here,
            <em>check the cacti.log for errors</em>. Weathermap usually produces a
            useful error message if there is a problem. Next, try setting Cacti's Log
            Level to DEBUG for 10 minutes, and then have a look in cacti.log. Don't
            forget to turn the Log Level back down again!</p>

            <dl class = "faq">
                <dt id = "module_not_found">I get some error about 'module not
                found', or similar...can you help?</dt>

                <dd><p>The answer is probably no, or at least not directly. In most
                cases, this is the result of a missing dependency, either PHP with no
                GD, or with an older version, or without PHP support, or without
                TrueColour support. I do try to extend weathermap to make these error
                messages more explanatory wherever possible, but ultimately, you need to
                get PHP working first. </p><p>Beware that on some systems you can have a
                working GD in your 'web' PHP (mod_php) and still have a non-working
                command-line PHP
                - Debian and derivatives (Ubuntu) can suffer from this. </p><p>Also be
                sure that the PHP you get from the command-line is the same installation
                as you expect
                - 'which php' and 'whereis php' will provide some *nix users with an
                idea that they are running the right one, as does 'php -m' and 'php -v'
                (is it the version you expect?). Also, some packaged PHP systems (MAMP,
                XAMPP, WAMP etc) have the module installed, but disabled by default.
                Check your php.ini to see if there is a commented-out line like
                'extension=php_gd.dll'.</p>

                <p>In 0.91 and newer, you may find the check.php to be useful when
                figuring out which php.ini and php version you should be looking at
                - http://yourserver/plugins/weathermap/check.php for the webserver
                (editor/rebuild now) version, and
                <tt>php check.php</tt> from the command-line for the CLI (Cacti
                Poller/command-line tool) version.</p>

                </dd>

                <dt id = "see_nothing">I don't see ANYTHING in the logs when
                using the Cacti plugin.</dt>

                <dd><p>Check if this is still the case when Weathermap is the only
                plugin. If a plugin dies completely, it takes the poller and any
                remaining plugins with it. This appears to be a problem with some older
                versions of 'Reports' in particular, where anything listed after Reports
                in the plugins[] list will not be run.</p>

                </dd>

                <dt id = "nofunction">I get an error message about
                function_exists()</dt>

                <dd><p>This is PHP telling you that a function that Weathermap requires
                is not available in your PHP installation. Usually, it's to do with
                either GD, or FreeType. It can be that you have those libraries, but not
                current enough versions, or versions compiled without particular
                options. For example, it's possible to have FreeType installed, GD
                installed, the php-gd module installed, but that the GD library wasn't
                compiled with FreeType
                <em>support</em> so they don't know how to talk to each other. This
                tends to be less of a problem on packaged systems than where you build
                your own libraries. </p>

                <p>In 0.91 and newer, you may find the check.php to be useful when
                figuring out which php.ini and php version you should be looking at, and
                which functions might be missing
                - http://yourserver/plugins/weathermap/check.php for the webserver
                (editor/rebuild now) version, and
                <tt>php check.php</tt> from the command-line for the CLI (Cacti
                Poller/command-line tool) version.</p>

                </dd>

                <dt id = "nothumb">My maps don't get created/updated (or "This
                map hasn't been created yet")</dt>

                <dd><p>This is almost always a permissions problem. Look in your
                cacti.log for lines starting WEATHERMAP to see what is going
                wrong. </p></dd>

                <dt id = "no_overlib">I've defined some OVERLIBGRAPH lines, but
                nothing appears in the map HTML when I use the command-line tool.</dt>

                <dd><p>For historical reasons, the default format for HTMLSTYLE is
                'static' which avoids javascript. To get the overlib graphs to appear
                you need to have 'HTMLSTYLE overlib' near the top of your map
                configuration file.</p></dd>

                <dt id= "overliboffscreen">When I use OVERLIBGRAPH and the
                pointer is near the right side of the screen, the floating graph
                disappears off the side of the screen...</dt>

                <dd><p>If you set OVERLIBWIDTH and OVERLIBHEIGHT correctly, then
                Weathermap can make a better guess about whether to show the map to the
                left or right. Be careful to set them to the correct value, or you may
                see strange 'flashing' graphs. The easiest way is to right-click on the
                graph in your browser and read the sizes from the Properties panel.</p>

                <p>Typically one size is right for all your graphs, so this is a good
                example of something you can add into NODE DEFAULT (or LINK DEFAULT).</p>

                </dd>

                <dt id = "gauges">The value from my RRD data is much too
                big!</dt>

                <dd><p>Is it about 8 times too big, by any chance? Historically,
                Weathermap was mainly used for SNMP Interface statistics, which use
                bytes-per-second counters ("octet counters" in SNMP-speak). Because of
                this, the standard RRD datasource plugin multiplies everything by 8, to
                get back to bits-per-second. New in 0.9, you can get the 'raw' value
                from an RRD file by using
                <a href = "targets.html#rrd">'gauge:' as a prefix</a>.</p><p>If the
                value is wrong by some other constant factor, you can use the 'scale:'
                prefix to multiply or divide by that factor
                - e.g divide by 1000 to turn a milliseconds value into a 'real' seconds
                value, or multiply by 1024 to get a kilobytes values from a megabytes
                one.</p></dd>

                <dt id= "timezone">The timestamp on my map is all wrong!</dt>

                <dd><p>I have not seen this one myself, but a Chinese user has, and it
                seems that PHP doesn't always use your system timezone correctly. The
                fix is to edit php.ini to change date.timezone </p></dd>

                <dt id = "gdbug">Weathermap just dies without warning on my
                Debian/Ubuntu system

                <br />OR... I get a blank screen in the editor when I add a node

                <br />OR... I get a segmentation fault when Weathermap runs</dt>

                <dd><p>
                It's a GD bug. It's documented
                <a href = "http://bugs.libgd.org/4">here</a>,
                and the reason it mainly affects Debian is that Debian links to the
                system GD (version 2.0.33) not the built-in PHP one (2.0.28ish). For
                whatever reason, the problems aren't present in the PHP GD library,
                which has a different alpha-blending implementation as far as I can see
                from the docs. </p>

                <p>The bugs are apparently fixed in GD 2.0.34, which has been released
                and is in Debian unstable. Users report that upgrading to the version of
                libgd2-xpm from unstable will fix this problem if you can't wait, or
                recompile PHP to use it's own GD library.</p>

                <p>Since 0.95, there is a small program supplied with Weathermap to test
                for this problem. Run
                <code>php check-gdbug.pgp</code> in the weathermap directory to see if
                it really is this problem that affects you.</p>

                <p>There is more information about fixing this issue for Debian Etch
                (and perhaps also Ubuntu Edgy) in
                <a href = "http://forums.cacti.net/viewtopic.php?t=21517">this Cacti
                Forum thread</a>.</p>

                </dd>

                <dt id = "icons">Where can I find some icons to use with
                Weathermap? Why don't you have any supplied with it?</dt>

                <dd><p>
                There are plenty of sources online. A good google search would be 'visio
                network icons'. Some to get you started:

                <ul>
                    <li><a href =
                        "http://www.cisco.com/web/about/ac50/ac47/2.html">Cisco's
                    distinctive icons</a> in a number of formats, and I have converted a
                    set of these into
                    <a href = "http://wotsit.thingy.com/haj/cacti/">PNG with
                    transparency</a>, too.</li>

                    <li><a href = "http://www.nagiosexchange.org/">Nagios Exchange</a>
                    has icons for network maps</li>

                    <li>For your own use only, you could use the ones that come with MS
                    Visio. Obviously these can't be redistributed!</li>
                </ul>As for the second part, I can't draw, and I don't know of any
                freely distributable icons. Feel free to draw some for me, and make them
                open source.</p>

                </dd>

                <dt id = "logo">How can I embed an image (like my company's
                logo) in my maps? I don't want to have to create a special
                background...</dt>

                <dd><p>You can create a NODE with no links and no label, but with an
                ICON. The ICON is your logo. You can also use this to embed images from
                somewhere else - even dynamically produced ones
                - how about MRTG graphs or RRD stripgraphs embedded in your map?</p></dd>

                <dt id = "curves">I need to have a link that isn't just a
                straight line...how can I do this?</dt>

                <dd><p>
                You can use the <a href =
                    "config-reference.html#LINK_VIA">VIA</a> keyword to make a link go
                around corners.:

                <div class = "shell">
                    <pre>LINK bendylink
	NODES node1 node2
	VIA 200 300
	VIA 360 240
</pre>
                </div></p>

                </dd>

                <dt id= "parallel">I need to have more than one link from node
                A to node B, but they just overlap...how can I do this?</dt>

                <dd><p>See
                <a href = "http://www.network-weathermap.com/articles/parallel">this
                article</a> for more about parallel links.</p>

                </dd>

                <dt id = "aggregate">I still have two links, but I use MLPPP,
                and I want to see a single link on the map for both physical lines.</dt>

                <dd> <p>See
                <a href = "http://www.network-weathermap.com/articles/parallel">this
                article</a> for more about parallel and aggregated links.</p>

                </dd>

                <dt id = "editor_requests">When will you make the editor work
                like <em>XYZ</em>?</dt>

                <dd><p>Adding the editor in 0.7 has made adding a new core weathermap
                into something that needs more consideration. Anything that dramatically
                changes how you make a map (like the LINK DEFAULT and NODE DEFAULT
                changes for 0.7) should mean a similarly big change in the editor. All
                the options should really be editable from the editor too. In reality,
                just getting the editor to run smoothly on more than one browser is
                sometimes painful, let alone re-tooling it to add new features. With
                that all said, there very likely will be a new version of the editor in
                0.9, with drag &amp; drop editing, and (more) complete support for all
                the new features since 0.6 (which is the version that the editor really
                was written for
                - it was written before I added new 0.7 features. D'oh!).</p></dd>

                <dt id= "feature_requests">Will you extend weathermap to do
                <em>XYZ</em>?</dt>

                <dd><p>It depends. I have an internal idea of what I want weathermap to
                be like. I don't want it to feel (too much) like a small program with
                everyone's wishlist bolted on afterwards. If it seems like something
                that a number of people could use, and doesn't dramatically change the
                direction of the program (it won't get <a href =
                    "http://en.wikipedia.org/wiki/Zawinski's_Law_of_Software_Envelopment"
                    title = "jwz's law of software envelopment">mail-reading
                capability</a> anytime soon), then it stands a better chance. Things
                that take dozens of parameters to adjust something very subtle are
                less likely. Ultimately, it is a GPLed program though, so <i>you</i> can
                <i>always</i> add your own features! I try to keep a todo list on the
                website for current work-in-progress. Obviously, things that
                <i>I</i> want are <i>always</i> sensible and useful<tt>:-)</tt> .</p>

                <p>In the past, people have paid to have features added. I have a day
                job which pays for my food and my mortgage, so this isn't
                <em>always</em> a possibility, but it sometimes is. If it's a good idea,
                then I'll probably add it anyway, but if it's something special to you,
                or that you want in a particular timescale, then maybe we can talk!</p>

                </dd>

                <dt id= "repayme">How can I possibly repay you for making my
                life and work so much easier?</dt>

                <dd><p>Actually, this one isn't very frequent. If you do find yourself
                asking it, feel free to
                <a href = "http://www.network-weathermap.com/support/donate">make a
                donation, or send a gift,</a> though. However, I do like to
                <a href = "http://www.network-weathermap.com/contact">hear from
                users</a> anyway
                - it's nice to know that people do use this thing.</p></dd>
            </dl>
<?php
	include "common-page-foot.php";
