<?php
        include "vars.php";
        $PAGE_TITLE="Installation - Command-Line Tool Only";
        include "common-page-head.php";
?>
            <h2>Installation</h2>

            <h3>Command-Line Tool Only</h3>

            <h4>Requirements</h4>

            <p>You will need the 'pcre' and 'gd' PHP modules in
            <em>your command-line PHP</em>. The command-line tool runs using the
            command-line PHP which is not always the same as the server-side one. In
            some situations it is possible to have two completely different PHP
            installations serving these two
            - if you install from a package, then re-install from source, but to a
            different directory, for example. The CLI tool should warn you if the part
            it needs is not present.</p>

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

            <p>You can then use the pre-install checker to see if your PHP environment
            has everything it needs. To do this, you need to run a special
            <tt>check.php</tt> script. From a command-prompt run
            <tt>php check.php</tt> to see if your command-line PHP is OK. If any modules
            or functions are missing, you will get a warning, and an explanation of what
            will be affected (not all of the things that are checked are deadly
            problems).</p>

            <h4>Installation</h4>

            <p>Unpack the zip file into a directory somewhere. If you intend to just use
            the 'traditional' hand-written text configuration files, then it can be
            anywhere on the same server that runs your data-collection software (MRTG,
            Cricket, Cacti).</p>

            <p>
            You'll need to edit two lines in the <tt>weathermap</tt> file:

            <ul>
                <li>If you are on a Unix-based platform (BSD, OS X, Linux etc), the path
                in the very top line should be the full path to your command-line php
                executable (usr/bin/php, or /usr/local/bin/php usually).</li>

                <li>Around line 30 or so, you may need to change the path to your
                rrdtool executable, if you are intending to use RRD-based datasources
                for your maps.</li>
            </ul>That should be it! You should be able to run
            </p>

            <div class = "shell">
                <tt>./weathermap</tt>

                <br />

                or

                <br />

                <tt>php weathermap</tt> (on Windows you will need this one)
            </div><p>from a shell or command prompt, and get a (rather boring)
            <tt>weathermap.png</tt> file in return. If you don't, you
            <i>should</i> get some kind of error to help you figure out why.</p>

<?php
        include "common-page-foot.php";
