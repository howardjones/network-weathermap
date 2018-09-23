# PHP Network Weathermap 1.0.0 (dev)

## Current Status (2018-03-02)

I decided it would be useful to add this section to the top of the README, while things are moving around a lot. Here
is what is working, and what is being worked on:

__NOTE__ First, please note that Weathermap will require PHP 5.6 or above, from 1.0.0 onwards (including the current dev version).

__NOTE2__ For dev version, READ THIS FILE FIRST, before installing. Especially if you are thinking of installing on your production
Cacti server.

### General goals

By the time 1.0.0 is done, near enough everything will have had some kind of rewrite work, even it's just
tidying up the naming. A lot of it will have significantly more change, including stuff
that was originally written before 0.98 a few years ago, and never used. The aim of all of that
is to break the code up between the UI, the map-drawing, and data-collecting parts, and make
it a generally more pleasant place for someone to work, in the hopes of getting some additional contributors.

To make it easier to deal with multiple contributors, __automated testing__ is pretty essential. You
should be able to quickly tell if your changes have broken anything. Lots of code has been pulled into
smaller testable classes to make that easier (and tests written!). Previously, most of the stuff that
appeared on a web page was not at all easy to test, and that's improving now. The editor has tests for
the first time, for example, and a lot of the database manipulation that used to be buried in the cacti
plugin does, too.

### Current Status/Usability

* __Core__ - should be working OK. passing all tests. All code modified to use namespaces and autoloader, one class per file, most PSR-2 standards.

* __CLI__ - should be working OK. Rewritten to avoid PEAR dependency. There's also a `weathermap-new` which needs testing, but is intended to replace the old `weathermap` (all the business is in a class, not the script)

* __Editor__ - should be working OK. Re-implemented class/template-based version of editor (same UI). 

* __Cacti 0.8 Plugin__ - broken UI, working poller. Code has all been moved, poller run, UI not tested.

* __Cacti 1.0 Plugin__ - broken UI, working poller. Code has all been moved, poller run, UI not tested.

### Using the dev version

This git repo deliberately DOES NOT contain third party libraries (and if it does now, it won't soon).

Dependencies are managed with bower. If you have never used it before, you will need to:

* Install nodejs (and npm - which should come with it)
* Install bower: `npm install -g bower`
* Install [composer](https://getcomposer.org/)
* Go to the weathermap checkout directory
* Make sure that directory is called `weathermap` and not `network-weathermap` (which git will default to) or Cacti will not recognise it properly.
* `bower install` should install all the necessary javascript dependencies to the vendor/ directory.
* `composer update` will grab the PHP dependencies for both the runtime and testing environments
The release process collects up these files and puts them in the zip file, via the packing.list file(s). You only need to do this if you are working with the current development code.

If you aren't intended to do any development, run the tests, or contribute patches (why not? It's fun!) then you can use `composer update --no-dev` above, and reduce the number of PHP packages installed significantly.

### Work currently in progress:

* ~~Move to namespaces - PHP Namespaces help keep our code out of your code. Especially important for something that sits inside other software~~

* ~~Remove dependency on PEAR - CLI uses a Composer module now for options~~

* Move to React for Cacti UI, with only JSON/API type stuff in the PHP code

   * Management
      * Add A Map (with multiples, directly into correct group)
      * Delete A Map
      * Enable/Disable Map
      * Add Group
      * Update Map (properties page)
         * Move to new group
         * Alter schedule
         * Alter debugging
         * (Alter archiving)
      * ~~Edit map~~
      * Create map (create a blank, ready to edit)
      * SET Settings editor for global, group and map
      * Access editor for (group) and map
      * App Settings editor (for Cacti/host-app settings)
         
   * User
      * ~~Show Thumbnails~~      
      * ~~Show full size maps~~
      * "Overlib" replacement for popup graphs      
      * Cycle mode
      * Cycle mode fullscreen

* ~~Update Editor to use same UI classes as Cacti (input validation, one method per 'command', testability)~~

* ~~Update Editor Data Picker to use same UI classes as Cacti (input validation, one method per 'command', testability)~~

### Known Issues

* Weathermap management only shows after the *second* click on the Weathermap menu option? Something to do with relative URLs and Cacti's partial loading

* Bower is deprecated. Need to move to using npm directly

* Judging from the memory logging, there's a memory leak (300-500KB per map).

### Longer-term WIP:

* Break down 'monster methods' into simpler ones. Identify groups within the larger classes for refactoring (e.g. plugin-related stuff in Map)

    * Poller - a single runMaps() function currently does almost everything. Replace with Poller class for general environment setup, which
    asks MapManager for the maps to run. ~~Use a MapRuntime class per-map to contain all the knowledge
    needed to run that map.~~
        
* Dependency Injection - there's some ugly stuff especially with logging and global variables. Switch to a real logger (planning on monolog), wrapped
in a simple class to manage things like muting certain messages, and switching between debug and normal logging. Then find
a better way (DI container?) to have that one logger object shared between the places that want to use it. This also has some
side benefits - monolog can log to lots of destinations (syslog etc), in lots of formats (json, text, pretty coloured text), and
can automatically do things like tag on memory usage and function call info for debug logs. 

* Move as much generic database-related stuff out of the Cacti plugin and into MapManager - MapManager is testable, whereas 
the Cacti plugin is not (easily). Also, MapManager is currently a literal collection of every query that was in the 0.8.8 plugin, 
which turns out to be quite a few. Breaking that down into global, map, and group objects would be a good thing.  

* ~~Make an abstraction layer for things like `read_config_option` in the UI, so it doesn't depend on Cacti being underneath. When someone wants to make a plugin/integration for a new
application, it'll be a lot 'thinner' this way, too. This is done as Weathermap\Integration\ApplicationInterface~~

* Map object: really, this is several different things:

    1. A container for the nodes, links, & scales, and some management functions used by the editor mainly to access them (addNode, getNode etc). Also
    processString, which lives here because it looks inside everything else (*does it still?*).
    
    2. For no particular reason, the home of title and timestamps (which should be their own objects with their draw() method)
    
    3. A manager for the map drawing and data collection process - there could definitely be a DataManager for
    readData(), preprocessTargets() etc.
    
    4. Global data for the map
    
    5. The imagemap and z-layer information, which are just another couple of managed lists used for draw.
    
    6. Some functions to draw overlays that are only used by the editor. There should be some mechanism to 
    provide a delegate to draw these, supplied by the editor.

* Create a MapTitle and MapTimestamp class (from #2 above)

* Create a MapDataManager class (#3 above)

* As much as possible make the draw function in Map generically call the draw()
functions in each map item.

* Other map items: These have a mix of concerns between the drawing off the item, and the data
related to it. Most of the data stuff is in MapDataItem these days though.

* In all cases, the way configs are built up for getConfig() (aka the editor) is
quite cumbersome, and adds a lot of complexity to the classes, as it effectively
reverse-engineers the config from scratch. Some kind of DOM-style structure produced
by readConfig(), and simply read back by getConfig() would make the world a lot simpler.

## Normal README

This is PHP Network Weathermap, version 1.0.0 by Howard Jones (howie@thingy.com)

See the docs sub-directory for full HTML documentation, FAQ and example config.

See CHANGES for the most recent updates, listed by version.

See LICENSE for the license under which php-weathermap is released.

There is much more information, tutorials and updates available at:
    http://www.network-weathermap.com/

## Project Admin 

For news and updates, see http://www.network-weathermap.com/
(also twitter @netweathermap and Facebook)

For issue tracking and bug reports use the Github issue tracker: https://github.com/howardjones/network-weathermap/issues  

I'm trying managing feature requests with FeatHub. You can add features here, and vote for them too:

[![Feature Requests](http://feathub.com/howardjones/network-weathermap?format=svg)](http://feathub.com/howardjones/network-weathermap)


## Credits

PHP Weathermap contains components from other software developers:

overlib.js is part of Overlib 4.21, copyright Erik Bosrup 1998-2004. All rights reserved.
See http://www.bosrup.com/web/overlib/?License

The Bitstream Vera Open Source fonts (Vera*.ttf) are copyright Bitstream, Inc.
See http://www.bitstream.com/font_rendering/products/dev_fonts/vera.html

The manual uses the Kube CSS Framework - http://imperavi.com/kube/
and ParaType's PT Sans font: http://www.fontsquirrel.com/fonts/PT-Sans

jquery-latest.min.js is the jQuery javascript library - written by John Resig and collaborators.
http://docs.jquery.com/Licensing

Some of the icons used in the editor, and also supplied in the images/ folder are
from the excellent Fam Fam Fam Silk icon collection by Mark James: 
   http://www.famfamfam.com/lab/icons/silk/
These are released under the Creative Commons Attribution 2.5 License
   http://creativecommons.org/licenses/by/2.5/

Other libraries in the vendor/ directory are provided by third-parties. `composer info` will
provide licensing information per component for php components. 
