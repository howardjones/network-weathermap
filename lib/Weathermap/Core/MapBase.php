<?php

namespace Weathermap\Core;

/** Links, Nodes and the Map object inherit from this class ultimately.
 * Just to make some common code common.*/

class MapBase
{
    // the source information for config fetching
    const CONF_NOT_FOUND = 0;
    const CONF_FOUND_DIRECT = 1;
    const CONF_FOUND_INHERITED = 2;

    const CONFIG_TYPE_LITERAL = 0;
    const CONFIG_TYPE_COLOR = 1;

    public $notes = array();
    public $hints = array();
    public $inheritedFieldList;
    public $imagemapAreas = array();
    public $parent;

    public $name;

    protected $config = array();
    protected $descendents = array();
    protected $dependencies = array();

    public function __construct()
    {
        $this->config = array();
        $this->descendents = array();
        $this->dependencies = array();
        $this->name = null;
    }

    public function __toString()
    {
        return $this->myType() . ' ' . (isset($this->name) ? $this->name : '[unnamed]');
    }

    public function myType()
    {
        return 'BASE';
    }

    public function addNote($name, $value)
    {
        MapUtility::debug("Adding note $name='$value' to " . $this . "\n");
        $this->notes[$name] = $value;
    }

    public function getNote($name, $default = null)
    {
        if (isset($this->notes[$name])) {
            return $this->notes[$name];
        }

        return $default;
    }

    public function addHint($name, $value)
    {
        MapUtility::debug("Adding hint $name='$value' to " . $this . "\n");
        $this->hints[$name] = $value;
    }

    public function getHint($name, $default = null)
    {
        if (isset($this->hints[$name])) {
            return $this->hints[$name];
        }

        return $default;
    }

    public function deleteHint($name)
    {
        unset($this->hints[$name]);
    }

    public function deleteNote($name)
    {
        unset($this->notes[$name]);
    }

    /**
     * @param MapBase $comparison
     * @return string
     */
    public function getHintConfig($comparison)
    {
        $output = '';
        foreach ($this->hints as $hintName => $hint) {
            // all hints for DEFAULT node are for writing
            // only changed ones, or unique ones, otherwise
            if (($this->name == 'DEFAULT')
                ||
                (isset($comparison->hints[$hintName])
                    &&
                    $comparison->hints[$hintName] != $hint)
                ||
                (!isset($comparison->hints[$hintName]))
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
            return array($this->config[$keyname], self::CONF_FOUND_DIRECT);
        }

        if (!is_null($this->parent)) {
            list($value, $direct) = $this->parent->getConfig($keyname);
            if ($direct != self::CONF_NOT_FOUND) {
                $direct = self::CONF_FOUND_INHERITED;
            }
        } else {
            $value = null;
            $direct = self::CONF_NOT_FOUND;
        }

        // if we got to the top of the tree without finding it, that's probably a typo in the original getConfig()
        if (is_null($value) && is_null($this->parent)) {
            MapUtility::warn("Tried to get config keyword '$keyname' with no result. [WMWARN300]");
        }

        return array($value, $direct);
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
        MapUtility::debug("Settings config %s = %s\n", $keyname, $value);
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
        throw new WeathermapUnimplementedException('Dynamic dependencies not implemented yet');

        return 0;
    }

    public function addConfigValue($keyname, $value, $recalculate = false)
    {
        MapUtility::debug("Appending config %s = %s\n", $keyname, $value);
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

    /**
     * @param array $simpleParameters
     * @param MapBase $reference
     * @return string
     */
    protected function getSimpleConfig($simpleParameters, $reference)
    {
        $output = '';
        foreach ($simpleParameters as $param) {
            $field = $param[0];
            $keyword = $param[1];

            if ($this->$field != $reference->$field) {
                if ($param[2] == MapBase::CONFIG_TYPE_COLOR) {
                    $output .= "\t$keyword " . $this->$field->asConfig() . "\n";
                }
                if ($param[2] == MapBase::CONFIG_TYPE_LITERAL) {
                    $output .= "\t$keyword " . $this->$field . "\n";
                }
            }
        }
        return $output;
    }
}
