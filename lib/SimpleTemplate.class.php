<?php

// Originally grabbed from here: http://www.massassi.com/php/articles/template_engines/

class SimpleTemplate
{
    private $vars; /// Holds all the template variables

    /**
     * Constructor
     *
     * @param $file string the file name you want to load
     */
    public function __construct($file = null)
    {
        $this->file = $file;
    }

    /**
     * Set a template variable.
     */
    public function set($name, $value)
    {
        $this->vars[$name] = is_object($value) ? $value->fetch() : $value;
    }

    /**
     * Open, parse, and return the template file.
     *
     * @param $file string the template file name
     * @return string
     */
    public function fetch($file = null)
    {
        if (!$file) {
            $file = $this->file;
        }

        extract($this->vars);          // Extract the vars to local namespace
        ob_start();                    // Start output buffering
        include($file);                // Include the file
        $contents = ob_get_contents(); // Get the contents of the buffer
        ob_end_clean();                // End buffering and discard
        return $contents;              // Return the contents
    }
}