<?php
        include "vars.php";
        $PAGE_TITLE="Advanced Topics";
        include "common-page-head.php";
?>

            <h2 id = "advtopics">Advanced Topics</h2>

            <p>This page contains information for the more advanced corners of
            Weathermap usage. I assume that you are more familiar with the basics of map
            design here - there is less explanation of the simple stuff.</p>

			<ul>
			<li><a href="#tokens">Special String Processing Tokens</a></li>
			<li><a href="#formatting">Formatting in Special Tokens</a>			</li>
			<li><a href="#tweaks">SETting Hint Variables to tweak maps</a>			</li>
			<li><a href="#plugins">Writing Plugins</a></li>
			<li><a href="#dataplugins">Datasource Plugins</a></li>
			<li><a href="#prepostplugins">Pre- &amp; Post-Processing Plugins</a></li>
			
			</ul>
			
            <h3 id= "tokens">Special String Processing Tokens</h3>

            <p>Most places where you can specify a string in Weathermap config files,
            you can now include data from within the Weathermap data model. Useful
            examples of this would be:</p>

            <ul>
                <li>You can have the value of a data source printed in the map.</li>

                <li>You can have ICON filenames which change with the value of a
                datasource.</li>

                <li>You can make more effective use of the DEFAULTs, by including
                parameterised LABELs, ICONs and OVERLIBCAPTIONs.</li>
            </ul>

            <p>Finally, plugins can add internal 'notes' to objects in the map, which
            you can then use in the same way as the normal Weathermap data. For example,
            you could have a plugin that looks at the load-average on a server,
            categorises it as light, medium or heavy and saves that to a note, which can
            then be used to select an appropriate icon.
            </p>

            <p>The format for the tokens is simple:</p>

            <div class = "definition">{node:<em>nodename</em>:<em>variablename</em>}
            </div>

            <div class = "definition">{link:<em>linkname</em>:<em>variablename</em>}
            </div>

            <div class = "definition">{map:<em>variablename</em>}
            </div>

            Where nodename and linkname are the names of nodes or links defined in the
            map. There is a special link/node name, 'this', which always signifies the
            node or link that the string belongs to. For example:

            <div class = "example">
                <pre>
                                NODE test
                                   LABEL {node:this:name}
   </pre>
            </div>

            will set the label for the node to be 'test', since that is the node's name.
            Using 'this' in the DEFAULT node can save you some typing:

            <div class = "example">
                <pre>
                                NODE DEFAULT
                                   LABEL {node:this:name}
                                   OVERLIBCAPTION History for {node:this:name}

                                NODE test1
                                    POSITION 100 100

                                NODE test2
                                    POSITION 150 100
   </pre>
            </div>

            <p>Each node will take it's name as the label, and a useful caption for it's
            Overlib pop-up graph.
            </p>

            <p>For nodes specifically, there is one other special 'nodename'
            - 'parent'. If a node is positioned relative to another one, then this
            refers to that other node. The idea is to allow a node to be used as an
            additional data label for another one. </p>

            <p>
            Here is the list of valid <em>variablename</em>s and their meaning.</p>

            <table class = "biglist">
                <thead>
                    <tr><th>Variable Name</th><th>Meaning</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <th>bandwidth_in</th>

                        <td>The raw 'bandwidth' figure for the input side of the item.
                        This isn't always an actual bandwidth figure, especially for
                        NODEs. For bandwidth, you probably want to use the formatting
                        modifiers listed below.</td>
                    </tr>

                    <tr>
                        <th>bandwidth_out</th>

                        <td>The raw 'bandwidth' figure for the output side of the item.
                        As above, but for output.</td>
                    </tr>

                    <tr>
                        <th>max_bandwidth_in</th>

                        <td>The maximum input bandwidth, as defined by MAXVALUE or
                        BANDWIDTH.</td>
                    </tr>

                    <tr>
                        <th>max_bandwidth_out</th>

                        <td>The maximum output bandwidth, as defined by MAXVALUE or
                        BANDWIDTH.</td>
                    </tr>

                    <tr>
                        <th>inpercent</th>

                        <td>The percentage usage for the input side of the item,
                        calculated using the previous two figures.</td>
                    </tr>

                    <tr>
                        <th>outpercent</th>

                        <td>The percentage usage for the output side of the item,
                        calculated using the previous two figures.</td>
                    </tr>

                    <tr>
                        <th>name</th>

                        <td>The name of the NODE or LINK.</td>
                    </tr>

                    <tr>
                        <th>inscaletag</th>

                        <td>If the SCALE has
                        <a href = "config-reference.html#GLOBAL_SCALE">scale tag</a>,
                        then this is the scale tag for the SCALE line that the inpercent
                        value matched.</td>
                    </tr>

                    <tr>
                        <th>outscaletag</th>

                        <td>If the SCALE has
                        <a href = "config-reference.html#GLOBAL_SCALE">scale tag</a>,
                        then this is the scale tag for the SCALE line that the
                        outpercent value matched.</td>
                    </tr>

                    <tr>
                        <th>inscalecolor</th>

                        <td>The colour in HTML style ('#rrggbb') for the SCALE line that
                        the inpercent value matched. Intended for use with NOTES.</td>
                    </tr>

                    <tr>
                        <th>outscalecolor</th>

                        <td>The colour in HTML style ('#rrggbb') for the SCALE line that
                        the outpercent value matched. Intended for use with NOTES.</td>
                    </tr>
                </tbody>
            </table>

            <p>In addition to the variables defined by Weathermap itself, there are two
            additional sources for variables that greatly extend the usefulness of these
            string tokens: You, and Data Source Plugins.</p>

            <p>
            Firstly, you can define addition variables for any NODE, LINK or for the map
            in general, using the SET command. This serves two purposes. One is to allow
            you to give parameters to some datasource plugins (like a database password
            for some external database), but the other is just so you can use those
            values elsewhere in the map. Say you have a data-collection system where
            each router has an ID, but the URL is otherwise the same from one NODE to
            the next. You can define a new variable with the router ID, and then use a
            string token to insert that ID into the INFOURL or TARGET strings. This
            makes it easier to edit the configuration, and easier to make global changes
            if you move the server than runs the other system, for example.
            </p>

            <div class = "example">
                <pre>
                                NODE router_a
                                    LABEL Router A
                                    POSITION 100 200
                                    <b>SET router_id 33</b>
                                    INFOURL http://my.monitoring.system/view.php?id=<b>{node:this:router_id}</b>
                                    TARGET somemonitoringplugin:<b>{node:this:router_id}</b>
</pre>
            </div>

            <p>Since information from these variables can also appear in the NOTES popup
            for a NODE or LINK, then you can also use this as a more structured way to
            have that information in your config file, and still presented in the map.</p>

            <p>The second additional source is Data Source Plugins. Some plugins may
            define additional variables, above and beyond the normal 'in bandwidth' and
            'out bandwidth' that they normally would. For example, the Cacti Host data
            source plugin returns a "bandwidth" that is actually a number representing
            the state of the device, according to Cacti
            - up, down, disabled, recovering. Since you might want to show this as text
            somewhere, or use it to change the icon for a device, it
            <em>also</em> defines an additional variable called 'state' that contains a
            string - 'up','down','recovering', or 'disabled'.</p>

            <p>
            Here is one more example, showing the use of relative node positions, node
            targets and tokens working together:</p>

            <div class = "example">
                <pre>

                                NODE fw_a
                                    POSITION 100 100
                                    LABEL Firewall A
                                    TARGET fw_a_cpu.rrd:cpu:-  fw_a_sessions.rrd:-:sess

                                NODE fw_a_sessions
                                    POSITION fw_a -30 0
                                    LABEL {node:fw_a:outvalue} Sessions
   </pre>
            </div>

            <p>The first node uses two targets to populate the 'in' and 'out' slots from
            different RRD files. It uses the 'in' slot to change it's own color. The
            second node is positioned relative to the first, and uses tokens to show the
            number of sessions used (the 'out' slot) on the other node as it's label. In
            combination with the 'notes' available to plugins, you can display nearly
            anything in your weathermap.
            </p>

            <h3 id = "formatting">Formatting in Special Tokens</h3>

            <p>To allow you to control better how the values of special tokens are used,
            you can add a final part inside the token that specifies the format.</p>

            <p>This borrows the '%' format from the printf() function in C, PHP, perl
            and many other languages, but adds a couple of Weathermap-specific extras:</p>

            <table>
                <tr><th>{link:this:bandwidth_in:%d}</th><td>force to integer</td>
                </tr>

                <tr><th>{link:this:bandwidth_in:%04d}</th><td>force to integer, and pad
                to 4 digits with zeroes</td>
                </tr>

                <tr><th>{link:this:bandwidth_in:%0.2f}</th><td>floating-point with 2
                decimal places</td>
                </tr>

                <tr><th>{link:this:bandwidth_in:%k}</th><td>Add a prefix for mega, kilo,
                giga, milli etc, and show the rest as a floating-point number
                - e.g. 2.3M (Weathermap special)</td>
                </tr>

                <tr><th>{link:this:bandwidth_in:%0.2k}</th><td>as above, but limit the
                floating-point part to 2 decimal places (Weathermap special)</td>
                </tr>
                
                <tr><th>{link:this:bandwidth_in:%t}</th><td>Format a duration in seconds in human-readable form (Weathermap special)</td>
                <tr><th>{link:this:bandwidth_in:%T}</th><td>Format a duration in hundredths-of-seconds (aka SNMP TimeTicks) in human-readable form (Weathermap special)</td>
                <tr><th>{link:this:bandwidth_in:%2T} {link:this:bandwidth_in:%2t}</th><td>Format a duration in human-readable form show only the 2 most significant parts. e.g. 1y34d13m3s becomes 1y34d (Weathermap special)</td>
                
                </tr>
            </table>

            <h2 id = "tweaks">SETting Hint Variables to tweak maps</h2>

            <p>
            In addition to any variables that you define using SET, and the ones that
            Weathermap uses internally (like those listed above), there is a
            <em>third</em> place that SET is used
            - to make small adjustments to the way a map is calculated.</p>

            <p>Whenever there is a request for a 'tweak'
            - a setting that doesn't really deserve creating yet another map config
            keyword
            - then that tweak is done by having a special SET variable to control it.</p>

            <p>These are documented in the relevant config keyword section in the
            <a href = "config-reference.html">Config Reference</a>,
            but here's an example:</p>

            <p>When you are using KEYPOS and KEYSTYLE to draw the legend/key for a
            particular SCALE, you can choose to hide the percentage signs, by using</p>

            <div class = "example">
                <pre>SET key_hidepercent_DEFAULT 1</pre>
            </div>

            (for the DEFAULT scale in this example), in the top section of the map
            config file.

            <p>Another example of these 'hidden' tweaks is used in the
            <a href = "targets.html#rrd">RRDtool datasource plugin</a> to select what
            data is extracted.</p>

            <h2 id = "plugins">Writing Plugins</h2>

            <p>Version 0.9 of Weathermap introduces a plugin system for extending it's
            capabilities. This makes it possible for third parties to add new possible
            data sources and other functions to Weathermap without needing to alter the
            main code of the program.</p>

            <p>Currently, there are two basic types of plugin
            - datasources and pre/post-processing. Datasource plugins allow you to add
            new TARGET data sources. Pre-processing plugins are called once per map,
            before datasources are read, but after the map configuration is loaded.
            Post-processing plugins are called once per map, after all the data sources
            have been read. The processing plugins are intended to allow you to add
            additional information into the Map Notes data structure so that it can be
            accessed by the
            <a href = "#tokens">special string tokens</a> in NODE labels, for
            example. </p>

            <h3 id = "dataplugins">Datasource Plugins</h3>

            <p>
            Datasource plugins are the main plugin type in Weathermap. They allow the
            user to pull in data from almost any source, and incorporate it into the
            final Weathermap image. The best way to get started with writing a new DS
            plugin is probably to take an existing one apart.</p>

            <p>
            Each plugin is defined as a PHP class in the lib/datasources directory. The
            filename of the plugin must match the classname used inside the file. It's
            important to know that Weathermap <em>doesn't</em> instantiate the class
            - all the methods are just static methods in a convenient container.</p>

            <p>Each plugin has three methods: Init, Recognise and ReadData.</p>

            <p>At startup, Weathermap calls the Init method of each plugin to see if it
            can run at all. The Init method should check for the availability of
            appropriate config, or PHP functions or files. It should then return TRUE or
            FALSE, to indicate if it can run or not. For example, the SNMP DS plugin
            returns FALSE if it can't find the snmp_get PHP function, which is normally
            due to the SNMP module not being configured with PHP.
            </p>

            <p>For each map, after the configuration file has been read, the ReadData
            function inside Weathermap process TARGET lines to get data, before starting
            to draw the map. To do this, it loops though all the objects in the map,
            taking each TARGET of each object in turn (objects can have multiple
            aggregated targets), and for each of the TARGETs, it calls all of the DS
            plugins' Recognise method. The Recognise method should return TRUE or FALSE,
            depending on if it thinks that the supplied TARGET string is intended for
            it. This is normally decided by using preg_match to match a regular
            expression. It
            <em>should not</em> try and read any data source, or database, or file to
            validate the actual TARGET. It should
            <em>only</em> decide if the TARGET is the right 'shape' or not.</p>

            <p>Recognise is called even for plugins that have previously returned FALSE
            for Init. This is so that Weathermap can tell the user that their TARGET
            <em>would</em> have worked, if only the plugin was working OK, rather than
            mysteriously ignoring them.</p>

            <p>Finally, once it has established which of the plugins should deal with
            the TARGET string, it calls the ReadData method of that plugin, which should
            return a list() of three items - input value, output value and valid_time.</p>

            <p>The valid_time is a Unix time_t (as returned by time()), to indicate when
            the data was measured. This is mainly intended for things like RRDs where
            there may be a delay between the RRD being written and us reading the value,
            or for cached results. The input and output values are numeric, and are used
            as part of the final bandwidth values for the map object. If there is no
            valid result (e.g. the device id passed in the TARGET string doesn't exist)
            then the plugin should return -1,-1 for those two values.</p>

            <p>The ReadData function can also set addtional variables for the map
            object, which can then be used in strings in the map config file. It can do
            this by calling the $map-&gt;add_note() function.</p>

            <p>It can also
            <em>receive</em> addtional parameters that the user has defined with SET
            commands in the map config file. This is mainly intended for things like
            hostnames for the remote system that data is being read from, so that they
            don't have to be hardcoded, or appear in <em>every</em> TARGET string.</p>

            <h3 id= "prepostplugins">Pre- &amp; Post-Processing Plugins</h3>

            <p>Post-processing plugins can be used to calculate additional global
            statistics for a map, which can then be used in elements on the map. For
            example, you could calculate an average latency across all links in the
            network, or a total network uptime based on the uptimes of all nodes.
            Currently, there aren't any of these plugins, but the hooks are available.</p>
<?php
        include "common-page-foot.php";
