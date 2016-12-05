<?php

// Links, Nodes and the Map object inherit from this class ultimately.
// Just to make some common code common.
class WeatherMapBase
{
    public $name;

    var $notes = array();
    var $hints = array();
    var $inherit_fieldlist;
    var $imap_areas = array();
    var $parent;

    protected $config = array();
    protected $descendents = array();
    protected $dependencies = array();

    function __construct()
    {
        $this->config = array();
        $this->descendents = array();
        $this->dependencies = array();
    }

    function __toString()
    {
        return $this->my_type() . " " . (isset($this->name) ? $this->name : "[unnamed]");
    }

    public function my_type()
    {
        return "BASE";
    }

    function add_note($name, $value)
    {
        wm_debug("Adding note $name='$value' to " . $this->name . "\n");
        $this->notes[$name] = $value;
    }

    function get_note($name, $default = NULL)
    {
        if (isset($this->notes[$name])) {
            //	debug("Found note $name in ".$this->name." with value of ".$this->notes[$name].".\n");
            return $this->notes[$name];
        } else {
            //	debug("Looked for note $name in ".$this->name." which doesn't exist.\n");
            return $default;
        }
    }

    function add_hint($name, $value)
    {
        wm_debug("Adding hint $name='$value' to " . $this->name . "\n");
        $this->hints[$name] = $value;
        # warn("Adding hint $name to ".$this->my_type()."/".$this->name."\n");
    }

    function get_hint($name, $default = NULL)
    {
        if (isset($this->hints[$name])) {
            //	debug("Found hint $name in ".$this->name." with value of ".$this->hints[$name].".\n");
            return $this->hints[$name];
        } else {
            //	debug("Looked for hint $name in ".$this->name." which doesn't exist.\n");
            return $default;
        }
    }

    public function delete_hint($name)
    {
        unset($this->hints[$name]);
    }

    public function delete_note($name)
    {
        unset($this->notes[$name]);
    }

    /**
     * @param WeatherMapBase $dd
     * @return string
     */
    public function getHintConfig($dd)
    {
        $output = "";
        foreach ($this->hints as $hintName => $hint) {
            // all hints for DEFAULT node are for writing
            // only changed ones, or unique ones, otherwise
            if (
                ($this->name == 'DEFAULT')
                ||
                (isset($dd->hints[$hintName])
                    &&
                    $dd->hints[$hintName] != $hint)
                ||
                (!isset($dd->hints[$hintName]))
            ) {
                $output .= "\tSET $hintName $hint\n";
            }
        }
        return $output;
    }


    /**
     * Get a value for a config variable. Follow the template inheritance tree if necessary.
     * Return an array with the value followed by the status (whether it came from the source object or
     * a template, or just didn't exist). This will replace all that CopyFrom stuff.
     *
     * @param $keyname
     * @return array
     */
    public function getConfigValue($keyname)
    {
        if (isset($this->config[$keyname])) {
            return array($this->config[$keyname], CONF_FOUND_DIRECT);
        } else {
            if (!is_null($this->parent)) {
                list($value, $direct) = $this->parent->getConfig($keyname);
                if ($direct != CONF_NOT_FOUND) {
                    $direct = CONF_FOUND_INHERITED;
                }
            } else {
                $value = null;
                $direct = CONF_NOT_FOUND;
            }

            // if we got to the top of the tree without finding it, that's probably a typo in the original getConfig()
            if (is_null($value) && is_null($this->parent)) {
                wm_warn("Tried to get config keyword '$keyname' with no result. [WMWARN300]");
            }
            return array($value, $direct);
        }
    }

    public function getConfigValueWithoutInheritance($keyname)
    {
        if (isset($this->config[$keyname])) {
            return $this->config[$keyname];
        }
        return array(null);
    }

    /*
     * Set a new value for a config variable. If $recalculate is true (after the initial readConfig)
     * then also recursively tell all objects that have us as a template that their state has changed
     *
     * return an array of the objects that were notified
     */
    public function setConfigValue($keyname, $value, $recalculate = false)
    {
        wm_debug("Settings config %s = %s\n", $keyname, $value);
        if (is_null($value)) {
            unset($this->config[$keyname]);
        } else {
            $this->config[$keyname] = $value;
        }

        if ($recalculate) {
            $affected = $this->recalculate();
            return $affected;
        }
        return array($this->name);
    }

    public function recalculate()
    {
        /*
         * The idea here is to maintain a list of the items that inherit from this one, so if we change
         * something we can avoid necessarily redrawing the whole map just to get (e.g.) new imagemap coords
         *
         * In the future it would allow a client-side drawing editor to know which items are "dirty" after a change, too.
         *
         */
        throw new WeathermapUnimplementedException("Dynamic dependencies not implemented yet");

        return 0;
    }

    public function addConfigValue($keyname, $value, $recalculate = false)
    {
        wm_debug("Appending config %s = %s\n", $keyname, $value);
        if (is_null($this->config[$keyname])) {
            // create a new array, with this as the only item
            $this->config[$keyname] = array($value);
        } else {
            if (is_array($this->config[$keyname])) {
                // append the new item to the existing array
                $this->config[$keyname] [] = $value;
            } else {
                // This is the second value, so make a new array of the old one, and this one
                $this->config[$keyname] = array($this->config[$keyname], $value);
            }
        }

        if ($recalculate) {
            $affected = $this->recalculate();
            return $affected;
        }
        return array($this->name);
    }


    public function addDependency($object)
    {
        $this->dependencies[] = $object;
    }

    public function removeDependency($leavingObject)
    {
        foreach ($this->dependencies as $key => $object) {
            if ($leavingObject === $object) {
                // delete it
                unset($this->dependencies[$key]);
            }
        }
    }

    public function getDependencies()
    {
        return $this->dependencies;
    }

    public function cleanUp()
    {
        $this->dependencies = array();
        $this->descendents = array();
    }

}

class WeatherMapConfigItem
{
    var $defined_in;
    var $name;
    var $value;
    var $type;
}

// The 'things on the map' class. More common code (mainly variables, actually)
class WeatherMapItem extends WeatherMapBase
{
    /** @var  WeatherMap $owner */
    var $owner;

    var $configline;
    var $infourl;
    var $overliburl;
    var $overlibwidth, $overlibheight;
    var $overlibcaption;
    var $my_default;
    var $defined_in;
    var $config_override;    # used by the editor to allow text-editing
    public $imap_areas;
    public $zorder;
    protected $descendents = array();
    protected $dependencies = array();

    function __construct()
    {
        parent::__construct();

        $this->zorder = 1000;
        $this->imap_areas = array();
    }

    function my_type()
    {
        return "ITEM";
    }

    public function getZIndex()
    {
        return $this->zorder;
    }

    public function getImageMapAreas()
    {
        return $this->imap_areas;
    }

    public function setDefined($source)
    {
        $this->defined_in = $source;
    }

    public function getDefined()
    {
        return $this->defined_in;
    }

    public function replaceConfig($newConfig)
    {
        $this->config_override = $newConfig;
    }

}

