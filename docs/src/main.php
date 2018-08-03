<?php
        include "vars.php";
        $PAGE_TITLE="Introduction";
        $PATH_EXTRA="../";
        include "common-page-head.php";
?>
            <div class = "license"><p>PHP Weathermap is free software; you can
            redistribute it and/or modify it under the terms of the GNU General Public
            License as published by the Free Software Foundation; either version 2 of
            the License, or (at your option) any later version.</p>

                <p>PHP Weathermap is distributed in the hope that it will be useful, but
                WITHOUT ANY WARRANTY; without even the implied warranty of
                MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General
                Public License for more details.</p>

                <p>You should have received a copy of the GNU General Public License
                along with PHP Weathermap; if not, write to the Free Software
                Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301
                USA</p>

                <p>Parts of this software distribution are by other authors. Please see
                the README file for attribution and license details.</p>
            </div>

            <div id = "enclose">
                <div class = "card-right" style = "width: 290px;">
                    <h2 class = "card-title">PHP Weathermap</h2>

                    <p class = "card-photo">
                    <img src = "../images/weathermap-mini.png" /></p>

                    <p class = "card-desc">Sample output from php-weathermap, using data
                    collected by Cacti and MRTG.

                    <br /><a href = "../images/weathermap-example.png">Larger
                    version</a></p>
                </div>

                <h2 id = "introduction">Introduction</h2><p>PHP Weathermap is one of
                <a href = "#alternatives">many</a> implementations of the same basic idea
                - take data from your network devices and use it to provide a
                single-page overview of the current state of network, like the one to
                the right. It complements a tool like
                <a href = "http://www.mrtg.org">MRTG</a>,
                <a href = "http://cricket.sourceforge.net/">Cricket</a> or
                <a href = "http://www.cacti.net/">Cacti</a>,
                that provide in-depth graphing, and historical information, and can use
                data from those systems to produce it's maps. In fact, it
                <i>requires</i> some other data-collection source, as it does no
                device-polling on it's own.</p>

                <p>This particular version is written in PHP, and it can read statistics
                data from MRTG-produced HTML files, plain tab-seperated text files and
                from RRD files, such as those produced by newer MRTG setups, Cacti (my
                favourite) or another tool. It can also generate HTML 'holder' files for
                the map images, which can include popup overlays of historical data
                and links into your other monitoring/statistics system. It also has a
                interactive map editor, so you can largely avoid the text configuration
                files, if you prefer.</p>

                <p>To get a better idea of what is possible, see the
                <a href = "#example">example map</a> that comes with this manual.</p>

                <h3>Requirements</h3>

                <p>Based on lessons learned with the perl version, this one has a very
                restricted set of dependencies
				<ul>
                <li>you'll need a recentish PHP (&gt;4.3.0 I think) including the CLI
                version and the 'gd' extension with PNG, TrueColour and FreeType
                support</li><li>You will need command-line (<i>aka shell/ssh/telnet</i>) access
                to the server which will host the maps.</li>
				<li>You will need the PEAR PHP extension system - this comes with PHP when it's built from source, but it is sometimes a separate package
				if you install PHP from modules (e.g. php-pear on Debian/Ubuntu Linux).</li>
                <li>To read RRD files, you'll need the rrdtool command-line program. </li>
				</ul>

                Apart from the gd module, these requirements are the same as for
                Cacti, which is the most-tested partner stats system.

                <p>To use the Cacti plugin, you will need a recent version of Cacti, and
                possibly the matching Cacti Plugin Architecture, from
                <a href = "http://cactiusers.org/">Jimmy Conner's Cactiusers.org</a>. From Cacti 0.8.8 onwards,
				the Plugin Architecture is built-in.</p>

                <h2 id = "support">Support</h2>

                <p>There are two mailing lists for php-weathermap:

                <dl>
                    <dt>php-weathermap@thingy.com</dt>

                    <dd>General discussion of weathermap-related issues, bug reports and
                    development. Fairly low traffic currently. Typically contains
                    discussion of test or beta versions too, when they are
                    circulating.</dd>

                    <dt>php-weathermap-announce@thingy.com</dt>

                    <dd>Very low volume list for new version announcements and other
                    similarly rare events!</dd>
                </dl>

                More information about how to subscribe to the mailing lists is
                <a href = "http://www.network-weathermap.com/support/mailinglists">at
                the website</a>.</p>

                <p>Also, if you have an RSS reader, you can subscribe to the
                <a href = "http://www.network-weathermap.com/appcast.rss">'appcast' feed
                for php-weathermap</a> which contains roughly the same content as the
                -announce mailing list.</p>

                <h2 id = "installation" >Installation
                Guide</h2>

                <p>How to install Weathermap depends on how you intend to use it:

                <ul>
                    <li><a href = "install-cacti-editor.html">As a Cacti Plugin, with
                    the web-based editor</a></li>

                    <li><a href = "install-cacti.html">As a Cacti Plugin</a></li>

                    <li><a href = "install-cli-editor.html">As a standalone command-line
                    tool, with the web-based editor</a></li>

                    <li><a href = "install-cli.html">As a standalone command-line
                    tool</a></li>
                </ul>
		
		<h3 id="#security">Security</h3>
		<p>Weathermap has several features that can potentially give users more access than you intended to. This section describes
		the risks, and possible ways to mitigate them.</p>
		<p>First of all, there are quite a few folders in the weathermap distribution that don't need to be web-accessible. If you aren't' using the
		Cacti plugin, then practically all of it can be blocked or moved elsewhere. If you are using Weathermap with Cacti, then you can still
		deny ALL access from web users for the configs, output, lib and random-bits directories. The distribution comes with .htaccess files that
		will do this for you if you have Apache, and you have enabled "AllowOverride All" for the Weathermap directory (it may be on by default -
		you can test by trying to access http://yourserver/cacti/plugins/weathermap/lib/ - you should get a 403 Forbidden error.</p>
		<p>Secondly, you should use additional access control (by IP or authentication) to limit who can access the editor.php and cacti-pick.php pages. The second of those
		will allow someone accessing the page to see a list of all the datasources in your Cacti installation. The first one allows
		anyone who can access it to edit your maps (logically enough). <em>However</em>, Weathermap includes a datasource plugin that
		allow someone who can change the TARGET lines in a map to potentially run any command as the Cacti poller user. As of version 0.97b, this plugin
		(<a href="targets.html#script">external</a>) is disabled by default.</p>
		
                <p>Any user with access to the editor can also see all your maps. In a shared or service provider environment, this may be
		a Bad Thing.</p>

                <h2 id = "running">Running Weathermap</h2>

                <p>There are two ways to run Weathermap.</p><p>If you are using the
                Cacti plugin, then it will be run for you as part of the Cacti poller
                cycle. You don't need to do anything special apart from add your map
                configuration files, as explained in the
                <a href = "cacti-plugin.html">Cacti plugin</a> notes.</p>

                <p>If you are using weathermap as a standalone tool, you do it using the
                command line version. See the
                <a href = "cli-reference.html">CLI Reference</a> for all possible
                options, but a good starting point is something like:</p>

                <div class = "shell">
                    <pre>php ./weathermap --config myconfigfile.conf --output mymap.png --htmloutput mymap.html</pre>
                </div>

                <p>You can skip the htmloutput and output parts if you have
                HTMLOUTPUTFILE and IMAGEOUTPUTFILE lines in your configuration file.</p>

                <p>Usually, people want to run weathermap regularly (it's not a
                requirement though!). To do that, you need to create a 'cron job' or
                'Task Scheduler Task' to run a command-line like the one above on a
                regular basis. You probably already have a similar task setup to collect
                the data that weathermap is reading. This is the same kind of thing.</p>

                <h2 id = "basics">Basics</h2>

                <p>The weathermap is defined by a plain-text file which by default is
                called weathermap.conf (you can have many configurations, and choose
                between them with command-line switches). By default, weathermap will
                read that file, and produce a single PNG file called weathermap.png.</p>

                <p>The configuration file has three sections: Node definitions, Link
                definitions and Global settings. There is an
                <a href = "#example">example of a complete file</a> at the bottom of
                this page.</p>

                <h3>Nodes</h3>

                <blockquote class = "example">
				<cite>A simple NODE</cite>
                    <pre>NODE nycore1
	LABEL NYC
	POSITION 30 30
</pre>
                </blockquote><p>Nodes are the points on your network that are joined together.
                Depending on the detail in the map, they might be cities or individual
                routers. In a basic map, a node has 3 pieces of information
                - an internal name which must be unique to this node, it's position from
                the top-left corner of the map, in pixels, and optionally a label, which
                will appear within the box marking the position of the node. Nodes
                without a label don't appear on the map at all, but can still be used as
                an endpoint for a link.</p>

                <h3>Links</h3>

                <blockquote class = "example">
				<cite>A simple LINK</cite>
                    <pre>LINK backbone1
	NODES nycore1 paix1
	BANDWIDTH 3M
	TARGET ../my-mrtg-data/backbone1.html
</pre></blockquote>
                <p>Links are the network routes between the Nodes. Typically they
                are actual network links, but they can be anything that you can get
                numbers for that make sense on map.</p>

                <p>An absolute minimal link has 3 pieces of information too. They are
                the unique internal name for this link, and unique node names for the
                two endpoints. To show current usage on the map, you'll need to give two
                more pieces: the maximum bandwidth on the link, and a way to get the
                current throughput. The BANDWIDTH is measured in bits/sec, and can
                include the usual K,M,G and T suffixes for large values. The data-source
                is given in the TARGET line, and can be one of

                <ul>
                    <li>MRTG-generated HTML file (which contains a special HTML comment
                    at the bottom with the current values)</li>

                    <li>Cacti-generated RRD file</li>

                    <li>Some other RRD file, provided you know how it is structured
                    internally.</li>
                </ul>

                One important note: the order of the nodes in the NODES line is
                significant. The first node is considered to be the 'local' one when
                thinking about the data source in the TARGET. 'out' will be 'out'
                relative to the first node. If you find the map shows all your data
                flowing in the wrong direction, try swapping the order of the nodes
                here.</p>

                <h3>Global Settings</h3><p>These settings usually live at the top of the
                text file, and specify basic information about the map. The minimum
                settings are:</p>

                <blockquote class = "example">
                    <pre>WIDTH 800
HEIGHT 600</pre></blockquote><p>This is specifies the size of the map in pixels. If you want
                something a bit fancier than a plain white background, you can make up a
                background image to use in PNG format. In that case, the map will be the
                size of the background image:</p>

                <blockquote class = "example">
                    <pre>BACKGROUND western-europe.png</pre>
                </blockquote><p>There are also settings to set which fonts and colours are used
                for various elements of the map, where to position the colour-legend (if
                at all), what files to output, and more advanced layout techniques. The
                full list is in the
                <a href = "config-reference.html">Config Reference</a>.</p>

                <h2 id = "example">A Sample Config</h2>Here's a
                sample configuration, and <a href =
                    "../example/example.html">here's what it produces (modified to work
                a little better)</a>. The data isn't live in the output, but it gives
                you an idea of what can be done. The initial background image is <a href =
                    "../example/background.png">here</a>. The configuration file is also
                in the docs directory of the distribution, should you want it.

                
                    <blockquote class="example">
<cite>A Sample Configuration File</cite>				
<pre>
# some initial comments...
#
# This sample configuration file demonstrates most of the basic features of
# PHP Weathermap, along with some of the cosmetic and layout changes possible
#
#
BACKGROUND background.png
HTMLOUTPUTFILE example.html
IMAGEOUTPUTFILE example.png
TITLE Network Overview
HTMLSTYLE overlib
KEYPOS 10 400

# define some new TrueType fonts - built-in ones go from 1 to 5, so start high
FONTDEFINE 100 VeraIt 8
FONTDEFINE 101 Vera 12
FONTDEFINE 102 Vera 9

KEYFONT 102

LINK DEFAULT
	BANDWIDTH 100M
	BWLABEL bits
	BWFONT 100
	OVERLIBWIDTH 395
	OVERLIBHEIGHT 153
	WIDTH 4

NODE DEFAULT
	LABELFONT 101

NODE transit
	POSITION 400 180
	LABEL TRANSIT

# a little splash of background colour for these nodes
NODE isp1
	POSITION 250 100
	LABEL ISP1
		INFOURL http://www.isp1.com/support/lookingglass.html
	LABELBGCOLOR 255 224 224

NODE isp2
	POSITION 550 100
	LABEL ISP2
	INFOURL http://www.isp2.net/portal/
	LABELBGCOLOR 224 255 224

NODE core
	POSITION 400 300
	LABEL core
	INFOURL https://core.mynet.net/admin/

NODE customer1
	LABEL xy.com
	POSITION 150 370

NODE customer2
	LABEL ww.co.uk
	POSITION 250 450

NODE infra
	LABEL INFRASTRUCTURE
	POSITION 450 450

# this node has an icon, and so we push the label to the South edge of it, so it
# can still be read
NODE sync
	LABEL Sync
	ICON my_router.png
	LABELOFFSET S
	LABELFONT 2
	POSITION 550 370
# the icon is taken from a Nagios icon pack:
#   http://www.nagiosexchange.org/Image_Packs.75.0.html?&amp;tx_netnagext_pi1[p_view]=110&amp;tx_netnagext_pi1[page]=10%3A10

NODE site1
	LABEL site1
	POSITION 700 220

NODE site2
	LABEL site2
	POSITION 750 420

LINK sync-core
	NODES sync core
	TARGET data/sync_traffic_in_259.rrd
	OVERLIBGRAPH http://support.mynet.net/cacti/graph_image.php?local_graph_id=256&amp;rra_id=0&amp;graph_nolegend=true&amp;graph_height=100&amp;graph_width=300
	INFOURL http://support.mynet.net/cacti/graph.php?rra_id=all&amp;local_graph_id=256
#
# Site1 has two E1s, so we use NODE-offsets to allow them to run parallel
#

LINK sync-site1a
	NODES sync:N site1:W
	WIDTH 3
	TARGET data/sync_traffic_in_257.rrd
	BANDWIDTH 2M
	OVERLIBGRAPH http://support.mynet.net/cacti/graph_image.php?local_graph_id=254&amp;rra_id=0&amp;graph_nolegend=true&amp;graph_height=100&amp;graph_width=
	INFOURL http://support.mynet.net/cacti/graph.php?rra_id=all&amp;local_graph_id=126

LINK sync-site1b
	NODES sync:E site1:SE
	WIDTH 3
	OVERLIBGRAPH http://support.mynet.net/cacti/graph_image.php?local_graph_id=255&amp;rra_id=0&amp;graph_nolegend=true&amp;graph_height=100&amp;graph_width=
	TARGET data/sync_traffic_in_258.rrd
	BANDWIDTH 2M
	INFOURL http://support.mynet.net/cacti/graph.php?rra_id=all&amp;local_graph_id=56

#
# site2 also has two links, but this time we use the VIA to curve the links
#
LINK sync-site2a
	NODES sync site2
	WIDTH 3
	VIA 650 380
	TARGET data/sync_traffic_in_251.rrd
	OVERLIBGRAPH http://support.mynet.net/cacti/graph_image.php?local_graph_id=248&amp;rra_id=0&amp;graph_nolegend=true&amp;graph_height=100&amp;graph_width=300
	BANDWIDTH 1M
	INFOURL http://support.mynet.net/cacti/graph.php?rra_id=all&amp;local_graph_id=252

LINK sync-site2b
	NODES sync site2
	WIDTH 3
	VIA 650 420
	TARGET data/sync_traffic_in_252.rrd
	OVERLIBGRAPH http://support.mynet.net/cacti/graph_image.php?local_graph_id=228&amp;rra_id=0&amp;graph_nolegend=true&amp;graph_height=100&amp;graph_width=300
	BANDWIDTH 1M
	INFOURL http://support.mynet.net/cacti/graph.php?rra_id=all&amp;local_graph_id=561

#
# ISP 1 has a several links, again, but they prefer to see one arrow, and the aggregate bandwidth
#   so we use multiple TARGETs on one line, here, to sum the data

LINK transit-isp1
	NODES transit isp1
	TARGET data/trans1_traffic_in_352.rrd data/trans1_traffic_in_378.rrd data/trans1_traffic_in_420.rrd
	BANDWIDTH 10M
	OVERLIBGRAPH http://support.mynet.net/cacti/graph_image.php?local_graph_id=355&amp;rra_id=0&amp;graph_nolegend=true&amp;graph_height=100&amp;graph_width=
	INFOURL http://support.mynet.net/cacti/graph.php?rra_id=all&amp;local_graph_id=633

LINK transit-isp2
	NODES transit isp2
	TARGET data/trans1_traffic_in_438.rrd
	BANDWIDTH 34M
	OVERLIBGRAPH http://support.mynet.net/cacti/graph_image.php?local_graph_id=433&amp;rra_id=0&amp;graph_nolegend=true&amp;graph_height=100&amp;graph_width=300
	INFOURL http://support.mynet.net/cacti/graph.php?rra_id=all&amp;local_graph_id=265

LINK core-transit
	NODES transit core
	TARGET data/trans1_traffic_in_350.rrd
	ARROWSTYLE compact
	WIDTH 4
	OVERLIBGRAPH http://support.mynet.net/cacti/graph_image.php?local_graph_id=347&amp;rra_id=0&amp;graph_nolegend=true&amp;graph_height=100&amp;graph_width=300
	INFOURL http://support.mynet.net/cacti/graph.php?rra_id=all&amp;local_graph_id=122

LINK cust1-core
	NODES customer1 core
	TARGET data/extreme_traffic_in_299.rrd
	OVERLIBGRAPH http://support.mynet.net/cacti/graph_image.php?local_graph_id=296&amp;rra_id=0&amp;graph_nolegend=true&amp;graph_height=100&amp;graph_width=300
	INFOURL http://support.mynet.net/cacti/graph.php?rra_id=all&amp;local_graph_id=237

LINK cust2-core
	NODES customer2 core
	TARGET data/extreme_traffic_in_286.rrd
	OVERLIBGRAPH http://support.mynet.net/cacti/graph_image.php?local_graph_id=283&amp;rra_id=0&amp;graph_nolegend=true&amp;graph_height=100&amp;graph_width=300
	INFOURL http://support.mynet.net/cacti/graph.php?rra_id=all&amp;local_graph_id=222

LINK infra-core
	NODES infra core
	TARGET data/extreme_traffic_in_294.rrd
	OVERLIBGRAPH http://support.mynet.net/cacti/graph_image.php?local_graph_id=291&amp;rra_id=0&amp;graph_nolegend=true&amp;graph_height=100&amp;graph_width=300
	INFOURL http://support.mynet.net/cacti/graph.php?rra_id=all&amp;local_graph_id=228
</pre>
</blockquote>
                

                <h3 id = "alternatives">Other
                Weathermaps</h3><p>Obviously, you can't please everyone, so here's a
                list of other weathermap or network visualisation implementations that I
                know of. Some are open source, some aren't. All of them have some subtle
                or interesting wrinkle that the others don't.
                <a href = "mailto:howie@thingy.com">Let me know if you know of any
                others</a>.

                <dl>
                    <dt><a href = "http://netmon.grnet.gr/weathermap/" class = "ext">GRNET
                    perl version</a> by Panagiotis Christias.</dt>

                    <dd>Support only for MRTG, or anything else that can produce similar
                    HTML files.</dd>

                    <dt><a href =
                        "http://wotsit.thingy.com/haj/cacti-weathermap.html">My own perl
                    weathermap</a></dt>

                    <dd>A forked/modified version of the GRNET one above, adds
                    imagemaps, DHTML, RRD-reading and a number of smaller tweaks. No
                    longer updated.</dd>

                    <dt><a class = "ext" href =
                        "http://weathermap4rrd.tropicalex.net/">Weathermap4RRD</a></dt>

                    <dd>Another fork of the GRNET perl map. Also with (only?) RRD
                    support, and various graphical enhancements.
                    <i>Also</i> now with a PHP version!</dd>

                    <dt><a href =
                        "http://loadrunner.uits.iu.edu/weathermaps/abilene/" class =
                        "ext">Indiana University Abilene Weathermap</a></dt>

                    <dd>Another perl (i think) script, but with a rather different map
                    design. Can show error rates on links, too.</dd>

                    <dt><a href =
                        "http://noc.asti.dost.gov.ph/docus/tools/how-to/weathermap.php"
                        class = "ext">PREGINET Network Weathermap</a></dt>

                    <dd>Another perl open source map. Works by reading MRTG logs, as far
                    as I can tell.</dd>

                    <dt><a href = "http://www.it.teithe.gr/~v13/">V13 netmap</a> (click
                    'netmap' in the left hand panel - I love frames)</dt>

                    <dd>Similar output to this program, but does all it's own SNMP data
                    collection.</dd>
                </dl>
                </p>
            </div>

<?php
        include "common-page-foot.php";
