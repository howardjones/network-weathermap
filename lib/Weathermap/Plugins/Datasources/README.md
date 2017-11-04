# Datasource plugins

Some of these datasource plugins are rather sketchy or even completely non-working.

In particular:
  dbsample doesn't do anything.
  external has had no real testing yet

## Writing plugins

Start by copying one of the existing ones. It should inherit from Base. It should be
in the same namespace.

The minimum would be to implement the `__construct()` method, so it has a name, and the `ReadData()` method
so it can read data. If you have a simple pattern match, update the `regexpsHandled` array with those. If you 
need to do something more, implement the `Recognise` method fully.

The flow for a datasource plugin is:

1) Classes are loaded from this directory using require_once()

2) A single instance of the class is created per map, and its `Init()` method is called. If `Init()` returns *true* then the plugin is considered 'active'. Init() may return *false* for example if it requires certain PHP modules, or database configuration that is missing.

3) During the `readData()` process, each TARGET is broken up into the individual 'target clauses', and each of those
is passed to the `Recognise()` method of each active plugin in turn. If one returns *true* then it
is noted for later. If _none_ of them return *true*, then you get the 'TARGET xyz not recognized' warning.

4) If a plugin *was* found for the TARGET, then that plugin's `Register` method is called, passing the TARGET string. In the base class, this method
does nothing. It is intended to allow a plugin to build up a list of the data that will be required later, so it can potentially
prefetch it in the next step. This is why a single instance of each DS plugin class is used.

5) After all targets are recognised, or not, the `preFetch()` method is called once for each active plugin. At this stage,
the plugin can optionally fetch data from a remote system if it is more efficient to do this in a batch (for example, with 
`snmpbulkwalk`, or one optimised SQL query). Again, in the base class it does nothing.

6) Finally, all the targets in all the map items are looped through again, calling the
`ReadData()` method on the plugin identified by `Recognise()` earlier. At this point
actual data is returned to Weathermap. The data can be cached, or calculated, or fetched
live. This is also where you can use `addNote()` to define additional data to
be returned alongside your 'in' and 'out' values.

7) At the end of the map-drawing process, all the plugin objects are destroyed, but first the cleanUp() method is called, to
allow the plugin to clear up any temporary files or data it may have used.

The process is then repeated for each map - data is not preserved between different maps in a run.
