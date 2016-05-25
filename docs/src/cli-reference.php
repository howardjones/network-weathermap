<?php
        include "vars.php";
        $PAGE_TITLE="Command-line option Reference";
        include "common-page-head.php";
?>

            <div id = "enclose">
                <h2>Command-line options Reference</h2>The unix
                <tt>man</tt>-style specification for weathermap is:

                <div class = "shell">
                    <tt>./weathermap [--config <i>configfile</i>] [--output
                    <i>pngfile</i>] [--htmloutput <i>htmlfile</i>] [--image-uri
                    <i>URI</i>] [--debug] [--dumpafter] [--dumpconfig
                    <i>newconfigfile</i>] [--sizedebug] [--define var=value] [--no-warn={errorcodes}</tt>
                </div>As you can see,
                <i>all</i> the parameters are optional! By default the script will look
                for a configuration file called
                <tt>weathermap.conf</tt>, and produce a PNG image file called
                <tt>weathermap.png</tt>.
				
				<h2>Options</h2>
                <p><tt class="clioption">--output</tt> is used to specify the name of the PNG file that is
                created. This can also now be specified inside the configuration file,
                instead.</p>

                <p><tt class="clioption">--config</tt> is used to specify the name of the configuration
                file that is read.</p>

                <p><tt class="clioption">--debug</tt> enables a lot of chatty debug output that may be
                useful in the event of a problem. In case
                <tt class="clioption">--debug</tt> isn't verbose enough for you,
                <tt class="clioption">--dumpafter</tt> dumps the whole of the internal structure used by
                weathermap at the end of a run. Note that particularly with PHP 4.x,
                it's possible that this will never end, as the way that references to
                objects are handled has changed between PHP 4 and PHP 5.</p>

                <p><tt class="clioption">--dumpconfig</tt> writes out a new configuration file after
                reading in the specified one. This is useful when migrating older
                configuration files, as it will remove extra stuff made redundant by the
                newer <a href = "#REF_DEFAULTS">'default link and node'</a> style of
                configuration. Obviously, you should be careful not to overwrite your
                existing configuration files!</p>

                <p><tt class="clioption">--sizedebug</tt> simply tells weathermap to draw the links with
                the<i>maximum</i> bandwidth shown, not the current. It only works if
                BWLABEL is set to 'bits', but it is useful for checking you have the
                right sized links once you have finished your map.</p>

                <p><tt class="clioption">--define</tt> allows you to define additional internal variables
                for this run. It is equivalent to a
                <a href = "config-reference.html#GLOBAL_SET">SET</a> line in the global
                section of the map configuration file.</p>

                <p><tt class="clioption">--nowarn</tt> allows you to disable specific warnings by
                providing a list of the WMARNxxx codes for them. You shouldn't need to do this, but if you (for example)
                have an interface that can physically go above the BANDWIDTH you have set for it, you can use
                this to disable the 'clipped to 100%' warning that you would get.</p>

                <p><tt class="clioption">--htmloutput</tt> specifies the name for an HTML file to be
                generated to go with the PNG image file. This HTML can include imagemap
                and DHTML features to make your weathermap interactive to different
                degrees. This is governed by the
                <tt class="clioption"><a href = "config-reference.html#GLOBAL_HTMLSTYLE">HTMLSTYLE</a></tt>
                global setting, and <tt class="clioption">INFOURL</tt> and
                <tt class="clioption">OVERLIBGRAPH</tt> settings in NODE and LINK definitions. This can
                also now be specified inside the configuration file, instead.</p>

                <p><tt class="clioption">--image-uri</tt> specifies the URI used in an HTML file
                generated. If you are generating HTML in a different directory from the
                one the image is created, then weathermap will probably get the &lt;img
                src=""&gt; tag wrong. This option allows you to override the contents of
                the src attribute, if you know better.</p>
            </div>
<?php
        include "common-page-foot.php";
