# Cacti integration for Weathermap

This directory contains nearly all the *Cacti-specific* parts of the Cacti plugin.

The remaining parts are in the weathermap root directory, because the 
plugin architecture in Cacti requires them to be there:

* `setup.php`
* `INFO`
* `setup10.php`
* `weathermap-cacti10-plugin.php`
* `weathermap-cacti10-plugin-mgmt.php`
* `weathermap-cacti10-plugin-editor.php`
* `setup88.php`
* `weathermap-cacti88-plugin.php`
* `weathermap-cacti88-plugin-mgmt.php`
* `weathermap-cacti88-plugin-editor.php`

There are effectively two complete plugins here, with as much shared code
as possible. `setup.php` looks at the version string for the host Cacti, and 
uses that to pull in either the '88' or '10' version of the various files. These
in turn pull in code from this directory to do most of their work (the 
files listed above are all very short).

This plugin in turn depends on the MapManager object to deal with most of its database interaction. This allows
us to re-use that in other integrations, including the forthcoming standalone Weathermap web app, as much
as possible. Similarly, there is a base class for the User plugin and the Management plugin, and as much as possible
of the code for 88 and 10 versions is shared there too. The `weathermap-cacti88-plugin-compat.php` file contains dummy or 
cut-down version of functions added in Cacti 1.x, so that more code can be shared there, too - for example the `__()` and `__n()`
internationalised-string functions.

The UI parts of the integration (vs poller stuff) use the same UIBase class as the EditorUI, so any
work on the input validation benefits all of those components. It also forces a consistent layout for
all of them, which again is good for testing.

Finally, the MapManager class actually has a link *back* to something in this directory: the CactiApplicationInterface class.
To avoid having calls to Cacti in MapManager, the parts where we actually need to use Cacti's own functions to do work are
in an interface class. This should be the only stuff that needs to be re-implemented to use a different host NMS, as long as
weathermap is able to use its own table structures. It's mainly to do with setting and getting user information and 
application settings.

The other advantage of all this separation is that more code can be tested as standalone units, without
needing Cacti (or worse, two versions of Cacti!) in the test environment. For example, before Weathermap 1.0.0
the entirety of MapManager, EditorUI, and Editor was in loose functions in
the single-file cacti plugins, and essentially untestable. 

## Data sources

There are also a few data source plugins that are only useful to Cacti users. You'll find
those in the Weathermap/Plugins/Datasources directory, along with the other DS plugins. They are
all prefixed 'Cacti'. 

## Plan

Since the pre- and post-1.x Cacti worlds render pages quite a bit differently, I am intending to
do all the page-rendering for Weathermap on the client-side, using two React-based applications, 
and effectively limiting the plugin's 'actions' to REST-style API calls. This sidesteps
a few issues with relative URLs and other unexpected or undocumented behaviour. Also, once again,
it should mean that the UI is reusable elsewhere, as long as the same API exists.

The code for these two React javascript apps is in the `websrc/` directory. It's being copied across from a
separate work-in-progress project, so it's not all there yet!
