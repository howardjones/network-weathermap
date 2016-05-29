<?php
        include "vars.php";
        $PAGE_TITLE="Installation - Command-Line Tool and Editor";
        include "common-page-head.php";
?>
            <h2>Installation</h2>

            <h3>Command-Line Tool and Editor</h3>

            <h4>Requirements</h4>

            <p>You will need the 'pcre' and 'gd' PHP modules in
            <em>both your command-line and server-side (mod_php/ISAPI) PHP</em>. The
            command-line tool runs using the command-line PHP, and the editor uses the
            server-side one. In some situations it is possible to have two completely
            different PHP installations serving these two
            - if you install from a package, then re-install from source, but to a
            different directory, for example. The editor and the CLI tool should both
            warn you if the part they need is not present.</p>>

            <p>The command-line tool uses the Console_Getopt
            <a href = "http://pear.php.net/">PEAR</a> module. This comes as standard
            with PEAR, so you should be able to just install PEAR to get it. This may be
            a seperate package/port/RPM on your system, or you may need to install it
            from pear.php.net</p>

            <p>Before you start using it, you might want to change one PHP setting.
            Weathermap uses a fair bit of memory by PHP standards, as it builds the
            image for the map in memory before saving it. As a result, your PHP process
            <i>may</i> run out of memory. PHP has a 'safety valve' built-in, to stop
            runaway scripts from killing your server, which defaults to 8MB in most
            versions (this has changed in 5.2.x). This is controlled by the
            'memory_limit =' line in php.ini. You may need to increase this to 32MB or
            even more if you have problems. These problems will typically show up as the
            process just dying with no warning or error message, as PHP kills the
            script.</p>

            <h4>Installation</h4>

            <p>Unpack the zip file into a directory somewhere. If you are intending to
            use the browser-based editor, then the directory that you unpack the zip
            file into should be within the 'web space' on the web server that runs your
            data-collection application (that is, Cacti, MRTG, or similar)
            - /var/www/html, /usr/local/www/data or whatever it is for you.</p>

            <p>You can then use the pre-install checker to see if your PHP environment
            has everything it needs. To do this, you need to run a special
            <tt>check.php</tt> script, twice...</p>

            <p>First, go to http://yourcactiserver/plugins/weathermap/check.php to see
            if your webserver PHP (mod_php, ISAPI etc) is OK. Then, from a
            command-prompt run
            <tt>php check.php</tt> to see if your command-line PHP is OK. If any modules
            or functions are missing, you will get a warning, and an explanation of what
            will be affected (not all of the things that are checked are deadly
            problems).</p>

            <p>
            You'll need to edit two lines in the <tt>weathermap</tt> file:

            <ul>
                <li>If you are on a Unix-based platform (BSD, OS X, Linux etc), the path
                in the very top line should be the full path to your command-line php
                executable (usr/bin/php, or /usr/local/bin/php usually).</li>

                <li>Around line 30 or so, you may need to change the path to your
                rrdtool executable, if you are intending to use RRD-based datasources
                for your maps.</li>
            </ul>
            </p>

            <h4>Testing</h4>

            That should be it! You should be able to run

            </p>

            <div class = "shell">
                <code>./weathermap</code>

                <br />

                or

                <br />

                <code>php weathermap</code> (on Windows you will need this one)
            </div><p>from a shell or command prompt, and get a (rather boring)
            <code>weathermap.png</code> file in return. If you don't, you
            <i>should</i> get some kind of error to help you figure out why.</p>

            <h4>Editor</h4>

            <p>Once you have weathermap itself working, continue onto the editor:</p>

            <p>
            If you use Cacti, and want to be able to pick
            data sources from your Cacti installation by name, you should use the
                integrated access to the editor described on the '<a href="install-cacti-editor.html">Install Cacti + Editor</a>' page.
            </p>

            <p>
            Make sure that your webserver can write to the configs directory. To do
            this, you need to know which user your webserver runs as (maybe 'nobody',
            'www' or 'httpd' on most *nixes) and then run:

            <div class = "shell">
                <pre>chown www configs
                                chmod u+w configs</pre>
            </div>

            In a pinch, you can just <code>chmod 777 configs</code>, but this
            <em>really isn't</em> a recommended solution for a production system.</p>

            <p>On Windows, the same applies
            - the user that runs the webserver runs as should have permissions to write
            new files, and change existing files in the configs folder.</p>

            </p>

            <p>Since version 0.97, you now also need to enable the editor. The reason is
            so that you can't have the editor enabled without knowing about it. The
            editor allows access to your config files without authentication, so you
            should consider using features in your webserver to limit who can access
            <tt>editor.php</tt>. For example, on an Apache server, something like:

            <pre>
                            &lt;Directory /var/www/html/weathermap&gt;
                                &lt;Files editor.php&gt;
                                    Order Deny,Allow
                                    Deny from all
                                    Allow from 127.0.0.1
                                &lt;/FilesMatch&gt;
                            &lt;/Directory&gt;
    </pre>
            When you are happy that the world can't edit your maps, then enable the
            editor. This is done by editing the top of editor.php and changing
            <code>$ENABLED=false;</code> to <code>$ENABLED=true;</code></p>

            <p>
            You should now be able to go to
            http://yourserver/wherever-you-unpacked-weathermap/editor.php in a browser,
            and get a welcome page that offers to load or create a config file. That's
            it. All done. Please see the
            <a href = "editor.html">editor manual page</a> for more about
            <i>using</i> the editor!
            </p>

            <p>
                <strong>Important Security Note:</strong> The editor allows
                <i>anyone</i> who can access editor.php to change the configuration files
                for your network weathermaps. There is no authentication built-in for
                editing, except with the Cacti Plugin. This is why direct access to the
                editor is disabled by default
                - the editor won't work until you choose to make it work, or give permissions in Cacti. It's recommended
                that you either:

            <ul><li>change the ownership of configuration files so that the editor can't
            write to them once they are complete, or </li><li>use your webserver's
            authentication and access control facilities to limit who can access the
            editor.php URL. On apache, this can be done using the FilesMatch directive
            and mod_access.</li>
            </ul>
            </p>

<?php
        include "common-page-foot.php";
