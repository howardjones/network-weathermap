<?php
        include "vars.php";
        $PAGE_TITLE="Cacti Plugin";
        include "common-page-head.php";
?>
            <h2 id = "cactiplugin">Cacti Plugin</h2>

            <p>
            <img class = "figure figright" src = "../images/cacti_step2.png" />Since
            v0.8 there is support for tighter
            <a href = "http://www.cacti.net/">Cacti</a> integration, using the Cacti
            Plugin Architecture that Jimmy Conner (aka cigamit) has developed for Cacti
            0.8.x.</p>

            <p class = "important">NOTE: To start using the plugin, you should follow the
            <a href = "main.html#installation">installation notes</a> through first.</p>

            <p>With that done, you should now have a Weathermap tab showing in your
            Cacti web interface, and a Weathermaps entry under Management in the Console
            menu. If you already have your map configuration files, you should now copy
            those into the 'configs' directory, inside the weathermap directory, so that
            the plugin can find them. If you don't, there's a complete example map in
            the weathermap/docs/example directory, that you can use to get started. Copy
            the example.conf from there into your configs/ directory instead.</p>

            <p>
            <img class = "figure figright" src = "../images/cacti_maps_manage.png" />Once
            your maps are in the configs directory, choose the 'Weathermaps' link from
            the left side menu of the Console page in Cacti. A new screen appears,
            showing the configuration files that Weathermap is currently handling.
            Choose 'Add' and pick one of your configuration files from the list. </p>

            <p>It should appear in the 'Weathermaps' list now, and you can see whether
            it will be updated on the next poller run ('active'), and who will be able
            to see it.</p>

            <p>At this stage, you should probably wait five minutes (a poller cycle),
            reload the Weathermap tab, and make sure your map was created. If it doesn't
            show up, turn on the regular Cacti DEBUG logging, and wait for the next
            poller cycle to finish. You should get some useful error message from
            Weathermap in the Cacti logs to help you figure out what went wrong.</p>

            <p>Don't forget to turn DEBUG off again, once you are done, as it can
            quickly take a lot of disk space!</p><p>Most serious errors will also show
            up in the log, even with DEBUG turned off.</p>

            <p>If your map was created OK, then welcome to Weathermap!</p>

            <p>The rest of this page is a reference to all the available options in the
            plugin.</p>

            <h3>Managing Maps - Access Control</h3>

            <p>Access-control with the

            <img src = "../images/cacti_user_manage.png" class = "figure figright" />
            Weathermap plugin is in two layers. First, you can control who will see the
            Weathermap tab (and the 'Weathermaps' management link) in the usual Cacti
            way: in User Management, give your users the 'View Weathermaps' right.
            Second, you can control
            <i>which</i> weathermaps that they will see, from the Manage Maps page.
            Click on the link for your new map in the column marked 'Accessible By', and
            you will get a page where you can add and remove users from the list who can
            see this particular map. There is one extra user 'Anyone', that matches
            <i>any</i> authenticated user. This is to save you adding new users to a
            list when you want to have a 'global' map available to all users.</p>

            <p>The final feature of the Management page is that you can change the order
            in which the maps are shown, by clicking the Sort Order arrows to move them
            up or down.</p>

            <h3>Viewing Maps</h3>

            <p>
            <img src = "../images/cacti_mainscreen.png" class = "figure figright" />Since
            this is what it's really all about - presenting your users with nice maps!</p>

            <p>Your users can access the maps that they have been allowed access to (see
            above) by clicking on the Weathermap tab. You will need to give them the
            right to View Weathermaps in the User Management page first. The user can
            choose to cycle between the maps that they can see (if there is more than
            one). Also note: if you use OVERLIB popup graphs in your maps, your users
            must have access to view those graphs in Cacti, or they will see a broken
            image icon instead!</p>

            <h3 style = "clear:both">Managing Maps - Display Options</h3>

            <p>
            <img src = "../images/cacti_wmap_settings.png" class = "figure figright" />All
            of the Weathermap plugin's settings are in the Misc tab of Cacti's
            Settings page (Console..Settings on the left side menu).</p>

            <p><em>Page style</em> gives you two choices of layout
            - a big stack of fullsize maps on one page, or a grid of thumbnails, each of
            which leads to a full size map view. From either view, you can also choose
            the Cycle mode. Cycle mode gives you an automatically refreshing page
            cycling through all the maps available to you.</p>

            <p>If you only have one map (or a user is only allowed to see one), then the
            user will get a full-size map regardless of the setting. Also in the
            settings page, you can choose the maximum size of the thumbnails. The Page
            style setting takes effect immediately, but the thumbnail size is used next
            time the maps are generated by the poller.</p>

            <p>The last display-related Weathermap setting in the Settings page is the
            Refresh Time for Cycle mode. You can choose how long each map stays
            on-screen for. The default is 'Automatic', which takes the 5 minutes that
            the data is valid for (a poller cycle), and divides it evenly between the
            available maps
            - if you had 5 maps, they would each get 1 minute onscreen before the page
            reloaded with new updated maps.
            <em>This is nothing to do with changing how the Cacti poller works!</em></p>

            <h3>Managing Maps - Other Options</h3>

            <p><em>Output Format</em> allows you to change the image file format used by
            the plugin. Since v0.9, Weathermap can create PNG, GIF and JPEG files, as
            long as the GD library on your system was compiled with the correct
            libraries. JPEG images can be quite a bit smaller than PNG, without much
            degradation in quality. PNG is the default.</p>

            <p><em>Map Rendering Interval</em> is intended for advanced users only. If
            you use the
            <em>1-minute polling</em> patch for Cacti, you might not want to have
            Weathermap redraw your maps every minute. This option allows you to change
            this, so that Weathermap only redraws every <em>n</em> polling cycles. </p>

            <p>During that one cycle when it does redraw, your polling cycle will still
            be longer than usual, so you can also turn off the poller part of
            Weathermap, so that it doesn't redraw at all. This allows you to use the
            user-access parts of the plugin, but manage the redrawing of maps yourself.
            To redraw all the maps outside of the standard Cacti poller process, there
            is a special PHP script
            <tt>weathermap-cacti-rebuild.php</tt> that does the same job as the Cacti
            poller. To use this, you need to edit it, and change the path in the top of
            the file to point to your Cacti root directory. Then set up a second
            /etc/crontab entry, to redraw your Weathermaps without slowing down your
            Cacti polling:</p>

            <div class = "shell">
                <pre>
                                */5 * * * *   cactiuser  /usr/bin/php   /your/cacti/path/plugins/weathermap/weathermap-cacti-rebuild.php
</pre>
            </div>

            <p>You will need to change the paths to php and cacti, and the user that
            cacti runs as. If you use 'crontab -e', instead of editing /etc/crontab
            directly, then you should remove the 'cactiuser'.
            <strong>In normal use, you don't need to add a cron job
            - the Cacti poller does this work for you.</strong>
            </p>

            <h3>Boost support</h3>
            <p>With TheWitness' Boost plugin installed in Cacti, the rrd files
            are no longer updated every poller cycle. This is a problem if you are
            using regular rrdtool TARGETs. There is support in the Weathermap plugin
            to directly access data from the poller instead of rrd files. You can
            find out more in the <a href="targets.html#rrd">Targets Reference</a>.
            </p>

            <h3>DSStats support</h3>

            <p>Another alternative, which requires changing your TARGET lines, is
                the DSStats plugin, also by TheWitness. This also collects data
                into the Cacti database, and has it's own benefits if you want
                to produce periodic summary maps. You can
            find out more in the <a href="targets.html#dsstats">Targets Reference</a>.</p>

            <h3>Accessing extra Cacti data</h3>
            <p>Both of the above datasource plugins allow you to access some
            additional information from the Cacti database about the data being polled (see the Targets Reference for more).            
            </p>
            <p>Additionally, there are some other datasource plugins that can
                access more information from Cacti:
                <a href="targets.html#cactithold">cactithold</a> works with cigamit's THold plugin, and
                <a href="targets.html#cactihost">cactihost</a> accesses Cacti's host status information.
            </p>

            <h3>Final Notes and Troubleshooting</h3>

            <p class = "important">Weathermap has quite a lot of logging. If you have a
            problem, then
            <em>check your cacti.log</em> for lines starting WEATHERMAP. Most normal
            errors will appear in here with Cacti's logging level set to LOW. If you set
            Cacti's logging level to DEBUG, then Weathermap will produce a
            <em>lot</em> of log information as it runs. Also see the
            <a href = "faq.html">FAQ section</a> of this manual, and the
            <a href = "http://www.network-weathermap.com/">network-weathermap.com</a>
            website for more.</p>

            
            <h3>Recalculate Now</h3>
            
            <p>On the Weathermap management page, there used to be a 'Recalculate NOW' button.
                It worked by running the normal map update process immediately, but did so 
                as the user that runs the webserver. This causes some permissions problems 
                that you need to understand in order to use it. People would just click it 
                and complain it didn't work, or killed their maps. I added a warning explaining
                the problem. People still complained. I removed the button.</p>
            <p>
                The code is all there still, so if you really promise not to ask me why your maps
                all stopped working after you press the button, you can re-enable it. Simply open
                weathermap-cacti-plugin-mgmt.php in and editor and change the line that says
                <pre>
                    $i_understand_file_permissions_and_how_to_fix_them = false;
                </pre>
                to
                <pre>
                    $i_understand_file_permissions_and_how_to_fix_them = true;
                </pre>
                Now you have the button, the following explains the issues in detail.
            </p>

            <p>
            This will try to recalculate all your maps on demand. This is more
            complicated than it sounds, due to file permissions! Normally, the Cacti
            poller would create the images and HTML files in the output directory, which
            means they are owned by the 'cactiuser', whatever that user is called on
            your system. When you click 'Recalculate NOW', the redraw process is run
            from within your webserver, and runs as whatever user runs your webserver
            (nobody, www, apache...). To allow for both these situations, the output
            directory and it's contents must have appropriate permissions to allow both
            users to write to the files. The lazy insecure way to do this is just 'chmod
            777 output/*', but that allows
            <em>everyone</em> to write to the files! A better way is to create a new
            group, make 'cacti' and 'www' members of that group (as well as their other
            groups), then 'chgrp -R newgroup output' and 'chmod 770 output/*' so that
            they can both write, but nothing else can.
            <strong>This is why the button is labelled 'experimental'.</strong></p>

<?php
        include "common-page-foot.php";
