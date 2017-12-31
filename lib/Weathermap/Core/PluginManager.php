<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 31/12/17
 * Time: 12:47
 */

namespace Weathermap\Core;

use Weathermap\Core\MapUtility;
use Weathermap\Core\Target;
use Weathermap\Core\MapDataItem;

class PluginManager
{
    private $plugins = array();
    private $map;

    public function __construct($owner)
    {
        $this->map = $owner;
    }

    public function loadAllPlugins()
    {
        $pluginRootDirectory = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Plugins';

        $this->loadPlugins('data', $pluginRootDirectory . DIRECTORY_SEPARATOR . 'Datasources');
        $this->loadPlugins('pre', $pluginRootDirectory . DIRECTORY_SEPARATOR . 'Pre');
        $this->loadPlugins('post', $pluginRootDirectory . DIRECTORY_SEPARATOR . 'Post');
    }

    /**
     * Search a directory for plugin class files, and load them. Each one is then
     * instantiated once, and saved into the map object.
     *
     * @param string $pluginType - Which kind of plugin are we loading?
     * @param string $searchDirectory - Where to load from?
     */
    private function loadPlugins($pluginType = 'data', $searchDirectory = 'lib/datasources')
    {
        $typeToNamespace = array(
            'data' => '\\Weathermap\\Plugins\\Datasources',
            'pre' => '\\Weathermap\\Plugins\\Pre',
            'post' => '\\Weathermap\\Plugins\\Post'
        );

        MapUtility::debug("Beginning to load $pluginType plugins from $searchDirectory\n");

        $pluginList = $this->getPluginFileList($pluginType, $searchDirectory);
        $loaded = 0;
        foreach ($pluginList as $fullFilePath => $file) {
            MapUtility::debug("Loading $pluginType Plugin class from $file\n");

            $class = preg_replace('/\\.php$/', '', $file);
            // include_once $fullFilePath;

            MapUtility::debug("Loaded $pluginType Plugin class $class from $file\n");

            $classFullPath = $typeToNamespace[$pluginType] . '\\' . $class;

            MapUtility::debug("full class path is $classFullPath\n");

            $this->plugins[$pluginType][$class]['object'] = new $classFullPath;
            $this->plugins[$pluginType][$class]['active'] = true;
            $loaded++;

            if (!isset($this->plugins[$pluginType][$class])) {
                MapUtility::debug("** Failed to create an object for plugin $pluginType/$class\n");
                $this->plugins[$pluginType][$class]['active'] = false;
                $loaded--;
            }
            $this->plugins[$pluginType][$class]['name'] = $class;
        }
        MapUtility::debug("Finished loading $loaded $pluginType plugins.\n");
    }

    /**
     * @param $pluginType
     * @param $searchDirectory
     * @return array
     */
    private function getPluginFileList($pluginType, $searchDirectory)
    {
        $directoryHandle = $this->resolveDirectoryAndOpen($searchDirectory);

        $pluginList = array();
        if (!$directoryHandle) {
            MapUtility::warn("Couldn't open $pluginType Plugin directory ($searchDirectory). Things will probably go wrong. [WMWARN06]\n");
        }

        while ($file = readdir($directoryHandle)) {
            $fullFilePath = $searchDirectory . DIRECTORY_SEPARATOR . $file;

            if (!is_file($fullFilePath)
                || !preg_match('/\.php$/', $fullFilePath)
                || preg_match('/^Base\.php/', $file)
                || preg_match('/^Utility\.php/', $file)
            ) {
                continue;
            }

            $pluginList[$fullFilePath] = $file;
        }
        return $pluginList;
    }

    private function resolveDirectoryAndOpen($dir)
    {
        if (!file_exists($dir)) {
            $dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . $dir;
            MapUtility::debug("Relative path didn't exist. Trying $dir\n");
        }
        $directoryHandle = @opendir($dir);

        // XXX - is this ever necessary?
        if (!$directoryHandle) { // try to find it with the script, if the relative path fails
            $srcdir = substr($_SERVER['argv'][0], 0, strrpos($_SERVER['argv'][0], DIRECTORY_SEPARATOR));
            $directoryHandle = opendir($srcdir . DIRECTORY_SEPARATOR . $dir);
            if ($directoryHandle) {
                $dir = $srcdir . DIRECTORY_SEPARATOR . $dir;
            }
        }

        return $directoryHandle;
    }


    /**
     * Loop through the datasource plugins, allowing them to initialise any internals.
     * The plugins can also refuse to run, if resources they need aren't available.
     */
    public function initialiseAllPlugins()
    {
        MapUtility::debug("Running Init() for all Plugins...\n");

        foreach (array('data', 'pre', 'post') as $type) {
            MapUtility::debug("Initialising $type Plugins...\n");

            foreach ($this->plugins[$type] as $name => $pluginEntry) {
                MapUtility::debug("Running $name" . "->Init()\n");

                $ret = $pluginEntry['object']->init($this->map);

                if (!$ret) {
                    MapUtility::debug("Marking $name plugin as inactive, since Init() failed\n");
                    $this->plugins[$type][$name]['active'] = false;
                    MapUtility::debug(
                        "State is now %s\n",
                        ($this->plugins['data'][$name]['active'] ? 'active' : 'inactive')
                    );
                }
            }
        }
        MapUtility::debug("Finished Initialising Plugins...\n");
    }

    public function runProcessorPlugins($stage = 'pre')
    {
        MapUtility::debug("Running $stage-processing plugins...\n");

        $this->pluginMethod($stage, 'run');

        MapUtility::debug("Finished $stage-processing plugins...\n");
    }


    public function prefetchPlugins()
    {
        // give all the plugins a chance to prefetch their results
        MapUtility::debug("======================================\n");
        MapUtility::debug("Starting DS plugin prefetch\n");
        $this->pluginMethod('data', 'preFetch');
    }

    private function pluginMethod($type, $method)
    {
        MapUtility::debug("======================================\n");
        MapUtility::debug("Running $type plugin $method method\n");

        foreach ($this->plugins[$type] as $name => $pluginEntry) {
            if ($pluginEntry['active']) {
                MapUtility::debug("Running $name->$method()\n");
                $pluginEntry['object']->$method($this->map);
            }
        }
    }

    public function cleanupPlugins($type)
    {
        MapUtility::debug("======================================\n");
        MapUtility::debug("Starting DS plugin cleanup\n");
        $this->pluginMethod($type, 'cleanUp');
    }

    /**
     * @param string $targetString
     * @param MapDataItem $mapItem
     * @return bool|int|string
     */
    public function findHandlingPlugin($targetString, $mapItem)
    {
        $pluginList = $this->plugins['data'];

        MapUtility::debug("Finding handler for %s '%s'\n", $mapItem, $targetString);
        foreach ($pluginList as $name => $pluginEntry) {
            $isRecognised = $pluginEntry['object']->recognise($targetString);

            if ($isRecognised) {
                MapUtility::debug("plugin %s says it can handle it (state=%s)\n", $name, $pluginEntry['active']);
                return $pluginEntry;
            }
        }
        MapUtility::debug("Failed to find a plugin\n");
        return false;
    }

}