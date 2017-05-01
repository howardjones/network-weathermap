# Manual UI Testing #

Here is my very boring feature-test script for the Cacti plugin and editor. Just to make it slightly 
more interesting, I'm marking the checks that have found new bugs with a * at the end of the line for
each one, like fighter pilots.

## Basics - Install & Run Demo Map ##

* On DEV, make sure 'make test' passes all unit tests (allow for 3 fails on tests with timestamps in the image)
* On DEV, check the test-suites/index.html output for problems.
* Create clean TEST system (easiest is VM because you can snapshot it) - built from dev-environment.txt
* Build zip on DEV system ('make release') and copy zip (only) to TEST system
* Unzip in plugins/ directory as usual
* CHECK - Weathermap appears in Plugin Management
* CHECK - Weathermap version shows up (and is correct!)
* Click install
* CHECK - Weathermap tab and Manage Weathermaps console link appear
* Click User Management - CHECK that Weathermap View and Manage rights appear in Realm Permissions
* Remove View permission, and CHECK Weathermap tab disappears (console menu remains)
* Add View permission, remove Manage, and CHECK Weathermap tab reappears and console menu disappears
* CHECK mysqlshow shows 5 tables - weathermap_data weathermap_auth weathermap_maps weathermap_settings, weathermap_groups
* Go to Manage Weathermaps - wait 5 minutes and CHECK "last completed" is updating
* Click Add and CHECK that simple.conf ONLY is listed. *
* Click View to see the contents of simple.conf in a new window/tab
* Click Add and CHECK that simple.conf is listed in the main management page
* Click Add again, CHECK the list is empty
* Click 'show these files' and CHECK that only simple.conf is listed (no php or .htaccess files!) *
* Click Add one more time, and CHECK there are two simple.conf entries listed
* Wait 5 minutes and CHECK that a warning message appears on the Management page (we didn't set permissions on output/) *
* chown cacti weathermap/output and wait another 5 minutes
* CHECK thumbnail does appear for both simple.conf in the Weathermap tab
* CHECK that 0 warnings appear for both maps in the Management page *
* Go to http://yourserver/cacti/plugins/weathermap/output/ directly in a browser CHECK it dumps you back at the Cacti menu
* Go to http://yourserver/cacti/plugins/weathermap/configs/ directly in a browser CHECK it dumps you back at the Cacti menu
* Go to http://yourserver/cacti/plugins/weathermap/lib/ directly in a browser CHECK it dumps you back at the Cacti menu
* Go to http://yourserver/cacti/plugins/weathermap/editor-resources/ directly in a browser CHECK it dumps you back at the Cacti menu *
* Go to http://yourserver/cacti/plugins/weathermap/images/ directly in a browser CHECK it dumps you back at the Cacti menu
* Go to http://yourserver/cacti/plugins/weathermap/random-bits/ directly in a browser CHECK it dumps you back at the Cacti menu *

## Editor ##

(Doing this before the Cacti UI stuff means we'll have some extra maps to play with!)

* Go to the management page. Click the Editor link at the bottom. CHECK you get a warning about enabling the editor. *
* Edit editor.php and set ENABLED=true, reload the previous page, and CHECK you get a file selector
* CHECK that it shows a warning about the config directory (we didn't change any permissions)
* "chown -R www-data configs" (or whatever is needed) and reload - CHECK the warning disappears
* CHECK that the file selector lists only show simple.conf (no .htaccess, index.php or image files) *
* CHECK that the date and version at the top and bottom are correct *

## Basics - Navigate End-User Features ##

* Click Weathermap Tab, click first thumbnail - CHECK you get a full image back


## Groups & Permissions ##

## Custom SET Parameters ##

## Custom Schedules ##

## Cacti-specific Behaviours ##

