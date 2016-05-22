<?php
// PHP Weathermap 0.98
// Copyright Howard Jones, 2005-2014 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License



// Links, Nodes and the Map object inherit from this class ultimately.
// Just to make some common code common.
    
class WeatherMapBase
{
    public $name;

    protected $notes = array();
    protected $hints = array();
    protected $imap_areas = array();
    protected $config = array();
    protected $descendents = array();
    protected $dependencies = array();

    protected $inherit_fieldlist;

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

    /**
     * Anything calling this should be doing it a better way!
     */
    public function getInternalMember($thing)
    {
        return $this->$thing;
    }

    /**
     * Anything calling this should be doing it a better way!
     */
    public function setInternalMember($thing, $value)
    {
        $this->$thing = $value;
    }

    public function add_note($name, $value)
    {
        wm_debug("Adding note $name='$value' to ".$this->name."\n");
        $this->notes[$name] = $value;
    }

    public function get_note($name, $defaultValue = null)
    {
        if (isset($this->notes[$name])) {
            return($this->notes[$name]);
        }

        return($defaultValue);
    }

    public function delete_note($name)
    {
        unset($this->notes[$name]);
    }

    public function add_hint($name, $value)
    {
        wm_debug("Adding hint $name='$value' to ".$this->name."\n");
        $this->hints[$name] = $value;
    }

    public function get_hint($name, $defaultValue = null)
    {
        if (isset($this->hints[$name])) {
            return($this->hints[$name]);
        }

        return($defaultValue);
    }

    public function delete_hint($name)
    {
        unset($this->hints[$name]);
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

    public function addConfigValue($keyname, $value, $recalculate = false)
    {
        wm_debug("Appending config %s = %s\n", $keyname, $value);
        if (is_null($this->config[$keyname])) {
            // create a new array, with this as the only item
            $this->config[$keyname] = array($value);
        } else {
            if (is_array($this->config[$keyname])) {
                // append the new item to the existing array
                $this->config[$keyname] []= $value;
            } else {
                // This is the second value, so make a new array of the old one, and this one
                $this->config[$keyname] = array( $this->config[$keyname], $value);
            }
        }

        if ($recalculate) {
            $affected = $this->recalculate();
            return $affected;
        }
        return array($this->name);
    }

    /**
     * Do any pre-drawing calculations needed, then let any items that use us as a template
     * do theirs, too. Recursively build up a list of the affected objects so we could
     * tell the editor to do selective updates
     */
    public function recalculate()
    {
        $notified = array();
        $notified []= $this->name;
        wm_debug("Recalculating %s\n", $this);
        $this->preCalculate();

        foreach ($this->descendents as $child) {
            wm_debug("  %s notifying %s\n", $this, $child);
            $new_notified = $child->preCalculate();
            foreach ($new_notified as $n) {
                $notified []= $n;
            }
        }
        return $notified;
    }

    public function setTemplate($template_name, $owner)
    {
        $this->template = $template_name;
        wm_debug("Resetting to template %s %s\n", $this->my_type(), $template_name);
        $this->reset($owner);
    }

    // by tracking which objects depend on each other, we can reduce the number of full-table searches for a single object
    // (mostly in the editor for things like moving nodes)

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

/**
 * Class WeatherMapItem - anything drawn on the map inherits from this.
 */
class WeatherMapItem extends WeatherMapBase
{
    // TODO - we should be able to make most of these protected
    public $owner;
    public $configline;

    public $parent;
    public $my_default;
    public $defined_in;
    public $config_override;   # used by the editor to allow text-editing

    public $imageMapAreas;
    public $zIndex;
    public $zorder;

    function __construct()
    {
        parent::__construct();

        $this->zIndex = 1000;
        $this->imageMapAreas = array();
        $this->descendents = array();
        $this->parent = null;
    }

    public function my_type()
    {
        return "ITEM";
    }

    public function setDefined($source)
    {
        $this->defined_in = $source;
    }

    public function getDefined()
    {
        return $this->defined_in;
    }

    /**
     * Accessor for the variables that should be visible to ProcessString {} tokens.
     * Side-effect - none of the others are available anymore, and ALL are decoupled from the
     * actual implementation names, so we can refactor/rename more easily.
     *
     * @param $name#
     */
    public function getValue($name)
    {

    }

    public function preChecks(&$owner)
    {

    }

    public function preRender(&$owner)
    {

    }

    public function preCalculate(&$owner)
    {

    }

    public function draw($im, &$map)
    {

    }

    public function isTemplate()
    {
        return false;
    }

    public function getImageMapAreas()
    {
        return $this->imageMapAreas;
    }

    public function getZIndex()
    {
        return $this->zorder;
    }
}
