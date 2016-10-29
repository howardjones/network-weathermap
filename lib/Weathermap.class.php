<?php

// PHP Weathermap 0.98
// Copyright Howard Jones, 2005-2016 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License

require_once "all.php";


// ***********************************************


class WMImageLoader
{
	var $cache = array();

	function load_image($filename) {

	}

	// we don't want to be caching huge images (they are probably the background, and won't be re-used)
	function cacheable($width, $height) {
	    // for now, disable this. The imageduplicate() function doesn't work in all cases.
		return false;

		if ($width * $height > 65536) {
			return false;
		}
		return true;
	}

    function imagecreatescaledcolourizedfromfile($filename, $scalew, $scaleh, $colour, $colour_method)
    {

        wm_debug("Getting a (maybe cached) scaled coloured image for $filename at $scalew x $scaleh with $colour\n");

        $key = sprintf("%s:%d:%d:%s:%s", $filename, $scalew, $scaleh,
            $colour->asString(), $colour_method);
        wm_debug("$key\n");

        if (array_key_exists($key, $this->cache)) {
            wm_debug("Cache hit for $key\n");
            $icon_im = $this->cache[$key];
            wm_debug("From cache: $icon_im\n");
            $real_im = $this->imageduplicate($icon_im);
        } else {
            wm_debug("Cache miss - processing\n");
            $icon_im = $this->imagecreatefromfile($filename);
            // imagealphablending($icon_im, true);

            $icon_w = imagesx($icon_im);
            $icon_h = imagesy($icon_im);

            wm_debug("$colour_method\n");
            if ($colour_method == 'imagefilter') {
                wm_debug("Colorizing (imagefilter)...\n");
                list ($red, $green, $blue) = $colour->getComponents();
                imagefilter($icon_im, IMG_FILTER_COLORIZE, $red, $green, $blue);
            }

            if ($colour_method == 'imagecolorize') {
                wm_debug("Colorizing (imagecolorize)...\n");
                list ($red, $green, $blue) = $colour->getComponents();
                imagecolorize($icon_im, $red, $green, $blue);
            }

            if ($scalew > 0 && $scaleh > 0) {

                wm_debug("If this is the last thing in your logs, you probably have a buggy GD library. Get > 2.0.33 or use PHP builtin.\n");

                wm_debug("SCALING ICON here\n");
                if ($icon_w > $icon_h) {
                    $scalefactor = $icon_w / $scalew;
                } else {
                    $scalefactor = $icon_h / $scaleh;
                }
                if ($scalefactor != 1.0) {
                    $new_width = $icon_w / $scalefactor;
                    $new_height = $icon_h / $scalefactor;

                    $scaled = imagecreatetruecolor($new_width, $new_height);
                    imagealphablending($scaled, false);
                    imagecopyresampled($scaled, $icon_im, 0, 0, 0, 0, $new_width, $new_height, $icon_w,
                        $icon_h);
                    imagedestroy($icon_im);
                    $icon_im = $scaled;
                }
            }
            if ($this->cacheable($scalew, $scaleh)) {
                wm_debug("Caching $key $icon_im\n");
                $this->cache[$key] = $icon_im;
                $real_im = $this->imageduplicate($icon_im);
            } else {
                $real_im = $icon_im;
            }
        }

        wm_debug("Returning $real_im\n");
        return ($real_im);
    }

	function imagecreatescaledfromfile($filename, $scalew, $scaleh)
	{
		list($width, $height, $type, $attr) = getimagesize($filename);

		wm_debug("Getting a (maybe cached) image for $filename at $scalew x $scaleh\n");

		// do the non-scaling version if no scaling is required
		if ($scalew == 0 && $scaleh == 0) {
			wm_debug("No scaling, punt to regular\n");
			return $this->imagecreatefromfile($filename);
		}

		if ($width == $scalew && $height == $scaleh) {
			wm_debug("No scaling, punt to regular\n");
			return $this->imagecreatefromfile($filename);
		}
		$key = sprintf("%s:%d:%d", $filename, $scalew, $scaleh);

		if (array_key_exists($key, $this->cache)) {
			wm_debug("Cache hit for $key\n");
			$icon_im = $this->cache[$key];
			wm_debug("From cache: $icon_im\n");
			$real_im = $this->imageduplicate($icon_im);
		} else {
			wm_debug("Cache miss - processing\n");
			$icon_im = $this->imagecreatefromfile($filename);

			$icon_w = imagesx($icon_im);
			$icon_h = imagesy($icon_im);

			wm_debug("If this is the last thing in your logs, you probably have a buggy GD library. Get > 2.0.33 or use PHP builtin.\n");

			wm_debug("SCALING ICON here\n");
			if ($icon_w > $icon_h) {
				$scalefactor = $icon_w / $scalew;
			} else {
				$scalefactor = $icon_h / $scaleh;
			}
			if ($scalefactor != 1.0) {
				$new_width = $icon_w / $scalefactor;
				$new_height = $icon_h / $scalefactor;

				$scaled = imagecreatetruecolor($new_width, $new_height);
				imagesavealpha($scaled, true);
				imagealphablending($scaled, false);
				imagecopyresampled($scaled, $icon_im, 0, 0, 0, 0, $new_width, $new_height, $icon_w,
					$icon_h);
				imagedestroy($icon_im);
				$icon_im = $scaled;
			}
			if($this->cacheable($scalew, $scaleh)) {
				wm_debug("Caching $key $icon_im\n");
				$this->cache[$key] = $icon_im;
				$real_im = $this->imageduplicate($icon_im);
			} else {
				$real_im = $icon_im;
			}
		}

		wm_debug("Returning $real_im\n");
		return ($real_im);
	}

	function imageduplicate($source_im)
	{
		$source_width = imagesx($source_im);
		$source_height = imagesy($source_im);

		if (imageistruecolor($source_im)) {
			wm_debug("Duplicating $source_width x $source_height TC image\n");
			$new_im = imagecreatetruecolor($source_width, $source_height);
			imagealphablending($new_im, false);
			imagesavealpha($new_im, true);
		} else {
			wm_debug("Duplicating $source_width x $source_height palette image\n");
			$new_im = imagecreate($source_width, $source_height);
			$trans = imagecolortransparent($source_im);
			if ($trans >= 0) {
				wm_debug("Duplicating transparency in indexed image\n");
				$rgb = imagecolorsforindex($source_im, $trans);
				$trans_index = imagecolorallocatealpha($new_im, $rgb['red'], $rgb['green'], $rgb['blue'],
					$rgb['alpha']);
				imagefill($new_im, 0, 0, $trans_index);
			}
		}

		imagecopy($new_im, $source_im, 0, 0, 0, 0, $source_width, $source_height);

		return $new_im;
	}

	function imagecreatefromfile($filename)
	{
		$result_image=NULL;
		$new_image=NULL;
		if (is_readable($filename))
		{
			list($width, $height, $type, $attr) = getimagesize($filename);
			$key = $filename;

			if (array_key_exists($key, $this->cache)) {
				wm_debug("Cache hit! for $key\n");
				$cache_image = $this->cache[$key];
				wm_debug("From cache: $cache_image\n");
				$new_image = $this->imageduplicate($cache_image);
				wm_debug("$new_image\n");
			} else {
				wm_debug("Cache miss - processing\n");

				switch ($type) {
					case IMAGETYPE_GIF:
						if (imagetypes() & IMG_GIF) {
							wm_debug("Load gif\n");
							$new_image = imagecreatefromgif($filename);
						} else {
							wm_warn("Image file $filename is GIF, but GIF is not supported by your GD library. [WMIMG01]\n");
						}
						break;

					case IMAGETYPE_JPEG:
						if (imagetypes() & IMG_JPEG) {
							wm_debug("Load jpg\n");
							$new_image = imagecreatefromjpeg($filename);
						} else {
							wm_warn("Image file $filename is JPEG, but JPEG is not supported by your GD library. [WMIMG02]\n");
						}
						break;

					case IMAGETYPE_PNG:
						if (imagetypes() & IMG_PNG) {
							wm_debug("Load png\n");
							$new_image = imagecreatefrompng($filename);
						} else {
							wm_warn("Image file $filename is PNG, but PNG is not supported by your GD library. [WMIMG03]\n");
						}
						break;

					default:
						wm_warn("Image file $filename wasn't recognised (type=$type). Check format is supported by your GD library. [WMIMG04]\n");
						break;
				}
			}
			if(!is_null($new_image) && $this->cacheable($width, $height)) {
				wm_debug("Caching $key $new_image\n");
				$this->cache[$key] = $new_image;
				$result_image = $this->imageduplicate($new_image);
			} else {
				$result_image = $new_image;
			}
		}
		else
		{
			wm_warn("Image file $filename is unreadable. Check permissions. [WMIMG05]\n");
		}
		wm_debug("Returning $result_image\n");
		return $result_image;
	}

}

// ***********************************************

class WeatherMap extends WeatherMapBase
{
    /** @var WeatherMapNode[] $nodes */
	var $nodes = array();
    /** @var WeatherMapLink[] $links */
	var $links = array();

	var $texts = array(); // an array containing all the extraneous text bits
	var $used_images = array(); // an array of image filenames referred to (used by editor)
	var $seen_zlayers = array(0=>array(),1000=>array()); // 0 is the background, 1000 is the legends, title, etc

	var $config;
	var $next_id;
	var $min_ds_time;
	var $max_ds_time;
	var $background;
	var $htmlstyle;
	var $imap;
	var $colours;
	var $configfile;
	var $imagefile,
		$imageuri;
	var $rrdtool;
	var $title,
		$titlefont;
	var $kilo;
	var $sizedebug,
		$widthmod,
		$debugging;
	var $linkfont,
		$nodefont,
		$keyfont,
		$timefont;
	// var $bg_r, $bg_g, $bg_b;
	var $timex,
		$timey;
	var $width,
		$height;
	var $keyx,
		$keyy, $keyimage;
	var $titlex,
		$titley;
	var $keytext,
		$stamptext, $datestamp;
	var $min_data_time, $max_data_time;
	var $htmloutputfile,
		$imageoutputfile;
	var $dataoutputfile;
	var $htmlstylesheet;
	var $defaultlink,
		$defaultnode;
	var $need_size_precalc;
	var $keystyle,$keysize;
	var $rrdtool_check;
	var $inherit_fieldlist;
	var $mintimex, $maxtimex;
	var $mintimey, $maxtimey;
	var $minstamptext, $maxstamptext;
	var $context;

	var $name;
	var $imagecache;
	var $black,
		$white,
		$grey,
		$selected;

	var $datasourceclasses;
	var $preprocessclasses;
	var $postprocessclasses;
	var $activedatasourceclasses;
	var $thumb_width, $thumb_height;
	var $has_includes;
	var $has_overlibs;
	var $node_template_tree;
	var $link_template_tree;
    var $dsinfocache=array();

	var $plugins = array();
	var $included_files = array();

    var $colourtable = array();
    var $warncount = 0;

    var $scales;
    var $fonts;
    var $numscales;

    public function __construct()
    {
        parent::__construct();

        $this->inherit_fieldlist=array
        (
            'width' => 800,
            'height' => 600,
            'kilo' => 1000,
            'numscales' => array('DEFAULT' => 0),
            'datasourceclasses' => array(),
            'preprocessclasses' => array(),
            'postprocessclasses' => array(),
            'included_files' => array(),
            'context' => '',
            'dumpconfig' => FALSE,
            'rrdtool_check' => '',
            'background' => '',
            'imageoutputfile' => '',
            'imageuri' => '',
            'htmloutputfile' => '',
            'dataoutputfile' => '',
            'htmlstylesheet' => '',
            'labelstyle' => 'percent', // redundant?
            'htmlstyle' => 'static',
            'keystyle' => array('DEFAULT' => 'classic'),
            'title' => 'Network Weathermap',
            'keytext' => array('DEFAULT' => 'Traffic Load'),
            'keyx' => array('DEFAULT' => -1),
            'keyy' => array('DEFAULT' => -1),
            'keyimage' => array(),
            'keysize' => array('DEFAULT' => 400),
            'stamptext' => 'Created: %b %d %Y %H:%M:%S',
            'keyfont' => 4,
            'titlefont' => 2,
            'timefont' => 2,
            'timex' => 0,
            'timey' => 0,

            'mintimex' => -10000,
            'mintimey' => -10000,
            'maxtimex' => -10000,
            'maxtimey' => -10000,
            'minstamptext' => 'Oldest Data: %b %d %Y %H:%M:%S',
            'maxstamptext' => 'Newest Data: %b %d %Y %H:%M:%S',

            'thumb_width' => 0,
            'thumb_height' => 0,
            'titlex' => -1,
            'titley' => -1,
            'cachefolder' => 'cached',
            'mapcache' => '',
            'sizedebug' => FALSE,
            'debugging' => FALSE,
            'widthmod' => FALSE,
            'has_includes' => FALSE,
            'has_overlibs' => FALSE,
            'name' => 'MAP'
        );

        $this->min_ds_time = null;
        $this->max_ds_time = null;

        $this->scales = array();

        $this->colourtable = array();

        $this->configfile = '';
        $this->imagefile = '';
        $this->imageuri = '';

        $this->fonts = new WMFontTable();
        $this->fonts->init();

        $this->Reset();
    }

    function __WeatherMap()
	{
		$this->inherit_fieldlist=array
			(
				'width' => 800,
				'height' => 600,
				'kilo' => 1000,
				'numscales' => array('DEFAULT' => 0),
				'datasourceclasses' => array(),
				'preprocessclasses' => array(),
				'postprocessclasses' => array(),
				'included_files' => array(),
				'context' => '',
				'dumpconfig' => FALSE,
				'rrdtool_check' => '',
				'background' => '',
				'imageoutputfile' => '',
				'imageuri' => '',
				'htmloutputfile' => '',
				'dataoutputfile' => '',
				'htmlstylesheet' => '',
				'labelstyle' => 'percent', // redundant?
				'htmlstyle' => 'static',
				'keystyle' => array('DEFAULT' => 'classic'),
				'title' => 'Network Weathermap',
				'keytext' => array('DEFAULT' => 'Traffic Load'),
				'keyx' => array('DEFAULT' => -1),
				'keyy' => array('DEFAULT' => -1),
				'keyimage' => array(),
				'keysize' => array('DEFAULT' => 400),
				'stamptext' => 'Created: %b %d %Y %H:%M:%S',
				'keyfont' => 4,
				'titlefont' => 2,
				'timefont' => 2,
				'timex' => 0,
				'timey' => 0,

				'mintimex' => -10000,
				'mintimey' => -10000,
				'maxtimex' => -10000,
				'maxtimey' => -10000,
				'minstamptext' => 'Oldest Data: %b %d %Y %H:%M:%S',
				'maxstamptext' => 'Newest Data: %b %d %Y %H:%M:%S',

				'thumb_width' => 0,
				'thumb_height' => 0,
				'titlex' => -1,
				'titley' => -1,
				'cachefolder' => 'cached',
				'mapcache' => '',
				'sizedebug' => FALSE,
				'debugging' => FALSE,
				'widthmod' => FALSE,
				'has_includes' => FALSE,
				'has_overlibs' => FALSE,
				'name' => 'MAP'
			);

		$this->Reset();
	}

	function my_type() {  return "MAP"; }

    public function __toString()
    {
        return "MAP";
    }

	function Reset()
	{
		$this->imagecache = new WMImageLoader();
		$this->next_id = 100;
		foreach (array_keys($this->inherit_fieldlist)as $fld) { $this->$fld=$this->inherit_fieldlist[$fld]; }

		$this->min_ds_time = NULL;
		$this->max_ds_time = NULL;

		$this->need_size_precalc=FALSE;

        $this->nodes=array(); // an array of WeatherMapNodes
		$this->links=array(); // an array of WeatherMapLinks

        $this->createDefaultLinks();
        $this->createDefaultNodes();

       	$this->node_template_tree = array();
       	$this->link_template_tree = array();

		$this->node_template_tree['DEFAULT'] = array();
		$this->link_template_tree['DEFAULT'] = array();

        assert('is_object($this->nodes[":: DEFAULT ::"])');
        assert('is_object($this->links[":: DEFAULT ::"])');
        assert('is_object($this->nodes["DEFAULT"])');
        assert('is_object($this->links["DEFAULT"])');



		$this->imap=new HTML_ImageMap('weathermap');
        $this->colours = array();

		$this->configfile='';
		$this->imagefile='';
		$this->imageuri='';
//
//		$this->fonts=array();
//
//		// Adding these makes the editor's job a little easier, mainly
//		for($i=1; $i<=5; $i++)
//		{
//			$this->fonts[$i] = new WMFont();
//			$this->fonts[$i]->type="GD builtin";
//			$this->fonts[$i]->file='';
//			$this->fonts[$i]->size=0;
//		}

		$this->loadAllPlugins();

        $this->scales['DEFAULT'] = new WeatherMapScale("DEFAULT", $this);
        $this->populateDefaultColours();

		wm_debug("WeatherMap class Reset() complete\n");
	}

    // Simple accessors to stop the editor from reaching inside objects quite so much

    function getNode($name)
    {
        if (isset($this->nodes[$name])) {
            return $this->nodes[$name];
        }
        throw new WeathermapInternalFail("NoSuchNode");
    }

    function addNode($newObject)
    {
        if ($this->nodeExists($newObject->name)) {
            throw new WeathermapInternalFail("NodeAlreadyExists");
        }
        $this->nodes[$newObject->name] = $newObject;
        $this->addItemToZLayer($newObject, $newObject->getZIndex());
    }

    function getLink($name)
    {
        if (isset($this->links[$name])) {
            return $this->links[$name];
        }
        throw new WeathermapInternalFail("NoSuchLink");
    }

    function addLink($newObject)
    {
        if ($this->linkExists($newObject->name)) {
            throw new WeathermapInternalFail("LinkAlreadyExists");
        }
        $this->links[$newObject->name] = $newObject;
        $this->addItemToZLayer($newObject, $newObject->getZIndex());
    }

    function getScale($name)
    {
        if (isset($this->scales[$name])) {
            return $this->scales[$name];
        }
        throw new WeathermapInternalFail("NoSuchScale");
    }



    private function loadAllPlugins()
    {
        $this->loadPlugins('data', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'datasources');
        $this->loadPlugins('pre', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'pre');
        $this->loadPlugins('post', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'post');
    }

    private function populateDefaultColours()
    {
        wm_debug("Adding default map colour set.\n");
        $defaults = array(
            'KEYTEXT' => array('bottom' => -2, 'top' => -1, 'red' => 0, 'green' => 0, 'blue' => 0),
            'KEYOUTLINE' => array('bottom' => -2, 'top' => -1, 'red' => 0, 'green' => 0, 'blue' => 0),
            'KEYBG' => array('bottom' => -2, 'top' => -1, 'red' => 255, 'green' => 255, 'blue' => 255),
            'BG' => array('bottom' => -2, 'top' => -1, 'red' => 255, 'green' => 255, 'blue' => 255),
            'TITLE' => array('bottom' => -2, 'top' => -1, 'red' => 0, 'green' => 0, 'blue' => 0),
            'TIME' => array('bottom' => -2, 'top' => -1, 'red' => 0, 'green' => 0, 'blue' => 0)
        );

        foreach ($defaults as $key => $def) {
            $this->colourtable[$key] = new WMColour($def['red'], $def['green'], $def['blue']);
        }

        // legacy style
        foreach ($defaults as $key => $def) {
            $this->colours['DEFAULT'][$key] = $def;
            $this->colours['DEFAULT'][$key]['c1'] = $this->colourtable[$key];
            $this->colours['DEFAULT'][$key]['special'] = true;
        }
    }


    function ProcessString($input, &$context, $include_notes = true, $multiline = false)
    {
        # debug("ProcessString: input is $input\n");

        if ($input === '') {
            return '';
        }

        // don't bother with all this regexp rubbish if there's nothing to match
        if (false === strpos($input, "{")) {
            return $input;
        }

        $the_item = NULL;

        assert('is_scalar($input)');

        $context_description = strtolower($context->my_type());
        if ($context_description != "map") $context_description .= ":" . $context->name;

        wm_debug("Trace: ProcessString($input, $context_description)\n");

        if ($multiline == TRUE) {
            $i = $input;
            $input = str_replace("\\n", "\n", $i);
            # if($i != $input)  warn("$i into $input\n");
        }

        if ($context_description === 'node') {
            $input = str_replace("{node:this:graph_id}", $context->get_hint("graph_id"), $input);
            $input = str_replace("{node:this:name}", $context->name, $input);
        }

        if ($context_description === 'link') {
            $input = str_replace("{link:this:graph_id}", $context->get_hint("graph_id"), $input);
        }

        // check if we can now quit early before the regexp stuff
        if (false === strpos($input, "{")) {
            return $input;
        }

        $output = $input;

        while (preg_match('/(\{(?:node|map|link)[^}]+\})/', $input, $matches)) {
            $value = "[UNKNOWN]";
            $format = "";
            $key = $matches[1];
            wm_debug("ProcessString: working on " . $key . "\n");

            if (preg_match('/\{(node|map|link):([^}]+)\}/', $key, $matches)) {
                $type = $matches[1];
                $args = $matches[2];
                # debug("ProcessString: type is ".$type.", arguments are ".$args."\n");

                if ($type == 'map') {
                    $the_item = $this;
                    if (preg_match('/map:([^:]+):*([^:]*)/', $args, $matches)) {
                        $args = $matches[1];
                        $format = $matches[2];
                    }
                }

                if (($type == 'link') || ($type == 'node')) {
                    if (preg_match('/([^:]+):([^:]+):*([^:]*)/', $args, $matches)) {
                        $itemname = $matches[1];
                        $args = $matches[2];
                        $format = $matches[3];

                        #				debug("ProcessString: item is $itemname, and args are now $args\n");

                        $the_item = NULL;
                        if (($itemname == "this") && ($type == strtolower($context->my_type()))) {
                            $the_item = $context;
                        } elseif (strtolower($context->my_type()) == "link" && $type == 'node' && ($itemname == '_linkstart_' || $itemname == '_linkend_')) {
                            // this refers to the two nodes at either end of this link
                            if ($itemname == '_linkstart_') {
                                $the_item = $context->a;
                            }

                            if ($itemname == '_linkend_') {
                                $the_item = $context->b;
                            }
                        } elseif (($itemname == "parent") && ($type == strtolower($context->my_type())) && ($type == 'node') && ($context->relative_to != '')) {
                            $the_item = $this->nodes[$context->relative_to];
                        } else {
                            if (($type == 'link') && isset($this->links[$itemname])) {
                                $the_item = $this->links[$itemname];
                            }
                            if (($type == 'node') && isset($this->nodes[$itemname])) {
                                $the_item = $this->nodes[$itemname];
                            }
                        }
                    }
                }

                if (is_null($the_item)) {
                    wm_warn("ProcessString: $key refers to unknown item (context is $context_description) [WMWARN05]\n");
                } else {
                    #	warn($the_item->name.": ".var_dump($the_item->hints)."\n");
                    wm_debug("ProcessString: Found appropriate item: " . get_class($the_item) . " " . $the_item->name . "\n");

                    # warn($the_item->name."/hints: ".var_dump($the_item->hints)."\n");
                    # warn($the_item->name."/notes: ".var_dump($the_item->notes)."\n");

                    // SET and notes have precedent over internal properties
                    // this is my laziness - it saves me having a list of reserved words
                    // which are currently used for internal props. You can just 'overwrite' any of them.
                    if (isset($the_item->hints[$args])) {
                        $value = $the_item->hints[$args];
                        wm_debug("ProcessString: used hint\n");
                    }
                    // for some things, we don't want to allow notes to be considered.
                    // mainly - TARGET (which can define command-lines), shouldn't be
                    // able to get data from uncontrolled sources (i.e. data sources rather than SET in config files).
                    elseif ($include_notes && isset($the_item->notes[$args])) {
                        $value = $the_item->notes[$args];
                        wm_debug("ProcessString: used note\n");

                    } elseif (isset($the_item->$args)) {
                        $value = $the_item->$args;
                        wm_debug("ProcessString: used internal property\n");
                    }
                }
            }

            // format, and sanitise the value string here, before returning it

            if ($value === NULL) $value = 'NULL';
            wm_debug("ProcessString: replacing " . $key . " with $value\n");

            # if($format != '') $value = sprintf($format,$value);
            if ($format != '') {
                $value = WMUtility::sprintf($format, $value, $this->kilo);
            }

            $input = str_replace($key, '', $input);
            $output = str_replace($key, $value, $output);
        }
        return ($output);
    }

function RandomData()
{
	foreach ($this->links as $link)
	{
		$this->links[$link->name]->bandwidth_in=rand(0, $link->max_bandwidth_in);
		$this->links[$link->name]->bandwidth_out=rand(0, $link->max_bandwidth_out);
	}
}

    /**
     * Search a directory for plugin class files, and load them. Each one is then
     * instantiated once, and saved into the map object.
     *
     * @param string $pluginType - Which kind of plugin are we loading?
     * @param string $searchDirectory - Where to load from?
     */
    private function loadPlugins($pluginType = "data", $searchDirectory = "lib/datasources")
    {
        wm_debug("Beginning to load $pluginType plugins from $searchDirectory\n");


        $pluginList = $this->getPluginFileList($pluginType, $searchDirectory);

        foreach ($pluginList as $fullFilePath => $file) {
            wm_debug("Loading $pluginType Plugin class from $file\n");

            $class = preg_replace("/\\.php$/", "", $file);
            include_once($fullFilePath);

            wm_debug("Loaded $pluginType Plugin class $class from $file\n");

            $this->plugins[$pluginType][$class]['object'] = new $class;
            $this->plugins[$pluginType][$class]['active'] = true;

            if (!isset($this->plugins[$pluginType][$class])) {
                wm_debug("** Failed to create an object for plugin $pluginType/$class\n");
                $this->plugins[$pluginType][$class]['active'] = false;
            }

        }
        wm_debug("Finished loading plugins.\n");
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
            wm_warn("Couldn't open $pluginType Plugin directory ($searchDirectory). Things will probably go wrong. [WMWARN06]\n");
        }

        while ($file = readdir($directoryHandle)) {
            $fullFilePath = $searchDirectory . DIRECTORY_SEPARATOR . $file;

            if (!is_file($fullFilePath) || !preg_match('/\.php$/', $fullFilePath)) {
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
            wm_debug("Relative path didn't exist. Trying $dir\n");
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
    private function initialiseAllPlugins()
    {
        wm_debug("Running Init() for all Plugins...\n");

        foreach (array('data', 'pre', 'post') as $type) {
            wm_debug("Initialising $type Plugins...\n");

            foreach ($this->plugins[$type] as $name => $pluginEntry) {
                wm_debug("Running $name" . "->Init()\n");

                $ret = $pluginEntry['object']->Init($this);

                if (!$ret) {
                    wm_debug("Marking $name plugin as inactive, since Init() failed\n");
                    $this->plugins[$type][$name]['active'] = false;
                    wm_debug("State is now %s\n", $this->plugins['data'][$name]['active']);
                }
            }
        }
        wm_debug("Finished Initialising Plugins...\n");
    }

    public function runProcessorPlugins($stage = "pre")
    {
        wm_debug("Running $stage-processing plugins...\n");

        $this->pluginMethod($stage, "run");
//        foreach ($this->plugins[$stage] as $name => $pluginEntry) {
//            wm_debug("Running %s->run()\n", $name);
//            if ($pluginEntry['active']) {
//                $pluginEntry['object']->run($this);
//            }
//        }
        wm_debug("Finished $stage-processing plugins...\n");
    }




// nodename is a vestigal parameter, from the days when nodes were just big labels
// TODO - this is only used by Link - it doesn't need to be here
    function DrawLabelRotated($im, $x, $y, $angle, $text, $font, $padding, $linkname, $textcolour, $bgcolour, $outlinecolour, &$map, $direction)
    {
        $fontObject = $this->fonts->getFont($font);
        list($strwidth, $strheight) = $fontObject->calculateImageStringSize($text);

        if (abs($angle) > 90) $angle -= 180;
        if ($angle < -180) $angle += 360;

        $rangle = -deg2rad($angle);

        $extra = 3;

        $x1 = $x - ($strwidth / 2) - $padding - $extra;
        $x2 = $x + ($strwidth / 2) + $padding + $extra;
        $y1 = $y - ($strheight / 2) - $padding - $extra;
        $y2 = $y + ($strheight / 2) + $padding + $extra;

        // a box. the last point is the start point for the text.
        $points = array($x1, $y1, $x1, $y2, $x2, $y2, $x2, $y1, $x - $strwidth / 2, $y + $strheight / 2 + 1);

        rotateAboutPoint($points, $x, $y, $rangle);

        if ($bgcolour->isRealColour()) {
            $bgcol = $bgcolour->gdAllocate($im);
            imagefilledpolygon($im, $points, 4, $bgcol);
        }

        if ($outlinecolour->isRealColour()) {
            $outlinecol = $outlinecolour->gdAllocate($im);
            imagepolygon($im, $points, 4, $outlinecol);
        }

        $textcol = $textcolour->gdAllocate($im);
        $fontObject->drawImageString($im, $points[8], $points[9], $text, $textcol, $angle);

        $areaname = "LINK:L" . $map->links[$linkname]->id . ':' . ($direction + 2);

        // the rectangle is about half the size in the HTML, and easier to optimise/detect in the browser
        if ($angle == 0) {
            $map->imap->addArea("Rectangle", $areaname, '', array($x1, $y1, $x2, $y2));
            wm_debug("Adding Rectangle imagemap for $areaname\n");
        } else {
            $map->imap->addArea("Polygon", $areaname, '', $points);
            wm_debug("Adding Poly imagemap for $areaname\n");
        }
        $this->links[$linkname]->imap_areas[] = $areaname;

    }

    function DrawTimestamp($im, $font, $colour, $which = "")
    {
        // add a timestamp to the corner, so we can tell if it's all being updated

        $fontObject = $this->fonts->getFont($font);

        switch ($which) {
            case "MIN":
                $stamp = strftime($this->minstamptext, $this->min_data_time);
                $pos_x = $this->mintimex;
                $pos_y = $this->mintimey;
                break;
            case "MAX":
                $stamp = strftime($this->maxstamptext, $this->max_data_time);
                $pos_x = $this->maxtimex;
                $pos_y = $this->maxtimey;
                break;
            default:
                $stamp = $this->datestamp;
                $pos_x = $this->timex;
                $pos_y = $this->timey;
                break;
        }

        list($boxwidth, $boxheight) = $fontObject->calculateImageStringSize($stamp);

        $x = $this->width - $boxwidth;
        $y = $boxheight;

        if (($pos_x != 0) && ($pos_y != 0)) {
            $x = $pos_x;
            $y = $pos_y;
        }

        $fontObject->drawImageString($im, $x, $y, $stamp, $colour);
        $areaname = $which . "TIMESTAMP";
        $this->imap->addArea("Rectangle", $areaname, '', array($x, $y, $x + $boxwidth, $y - $boxheight));
        $this->imap_areas[] = $areaname;
    }

    function DrawTitle($im, $font, $colour)
    {
        $fontObject = $this->fonts->getFont($font);
        $string = $this->ProcessString($this->title, $this);

        if ($this->get_hint('screenshot_mode') == 1) {
            $string = WMUtility::stringAnonymise($string);
        }

        list($boxwidth, $boxheight) = $fontObject->calculateImageStringSize($string);

        $x = 10;
        $y = $this->titley - $boxheight;

        if (($this->titlex >= 0) && ($this->titley >= 0)) {
            $x = $this->titlex;
            $y = $this->titley;
        }

        $fontObject->drawImageString($im, $x, $y, $string, $colour);

        $this->imap->addArea("Rectangle", "TITLE", '', array($x, $y, $x + $boxwidth, $y - $boxheight));
        $this->imap_areas[] = 'TITLE';
    }



    /**
     * ReadConfig reads in either a file or part of a config and modifies the current map.
     *
     * @param $input string Either a filename or a fragment of config in a string
     * @return bool indicates success or failure     *
     *
     */
    function ReadConfig($input)
    {
        $reader = new WeatherMapConfigReader($this);

        // check if $input is more than one line. if it is, it's a text of a config file
        // if it isn't, it's the filename

        if ((strchr($input, "\n") != false) || (strchr($input, "\r") != false)) {
            wm_debug("ReadConfig Detected that this is a config fragment.\n");
            // strip out any Windows line-endings that have gotten in here
            $input = str_replace("\r", "", $input);
            $lines = explode("\n", $input);
            $filename = "{text insert}";

            $reader->readConfigLines($lines);

        } else {
            wm_debug("ReadConfig Detected that this is a config filename.\n");
            $reader->readConfigFile($input);
            $this->configfile = $input;
        }

        $this->postReadConfigTasks();

        return (true);
    }

    function postReadConfigTasks()
    {
        if ($this->has_overlibs && $this->htmlstyle == 'static') {
            wm_warn("OVERLIBGRAPH is used, but HTMLSTYLE is static. This is probably wrong. [WMWARN41]\n");
        }

        $this->populateDefaultScales();
        $this->replicateScaleSettings();
        $this->buildZLayers();
        $this->resolveRelativePositions();
        $this->updateMaxValues();


        $this->initialiseAllPlugins();
        $this->runProcessorPlugins("pre");
    }



    private function populateDefaultScales()
    {
        // load some default colouring, otherwise it all goes wrong

        $did_populate = $this->scales['DEFAULT']->populateDefaultsIfNecessary();

        if ($did_populate) {
            // we have a 0-0 line now, so we need to hide that.
            // (but respect the user's wishes if they defined a scale)
            $this->add_hint("key_hidezero_DEFAULT", 1);
        }

        $this->scales['none'] = new WeatherMapScale("none", $this);

    }

    /**
     * Temporary function to bridge between the old and new
     * scale-worlds. Just until the ConfigReader updates these
     * directly.
     */
    private function replicateScaleSettings()
    {
        foreach ($this->scales as $scaleName => $scaleObject) {
            $scaleObject->keyoutlinecolour = $this->colourtable['KEYOUTLINE'];
            $scaleObject->keytextcolour = $this->colourtable['KEYTEXT'];
            $scaleObject->keybgcolour = $this->colourtable['KEYBG'];
            $scaleObject->keyfont = $this->fonts->getFont($this->keyfont);

            if ((isset($this->numscales[$scaleName])) && isset($this->keyx[$scaleName])) {
                $scaleObject->keypos = new WMPoint($this->keyx[$scaleName], $this->keyy[$scaleName]);
                $scaleObject->keystyle = $this->keystyle[$scaleName];
                $scaleObject->keytitle = $this->keytext[$scaleName];
                if (isset($this->keysize[$scaleName])) {
                    $scaleObject->keysize = $this->keysize[$scaleName];
                }
            }
        }
    }


    private function buildZLayers()
    {
        wm_debug("Building cache of z-layers.\n");

        $allItems = $this->buildAllItemsList();

        foreach ($allItems as $item) {
            $zIndex = $item->getZIndex();
            $this->addItemToZLayer($item, $zIndex);
        }
        wm_debug("Found " . sizeof($this->seen_zlayers) . " z-layers including builtins (0,100).\n");
    }

    private function addItemToZLayer($item, $zIndex)
    {
        if (!isset($this->seen_zlayers[$zIndex]) || !is_array($this->seen_zlayers[$zIndex])) {
            $this->seen_zlayers[$zIndex] = array();
        }
        array_push($this->seen_zlayers[$zIndex], $item);
    }

    private function updateMaxValues()
    {
        wm_debug("Finalising bandwidth.\n");

        $allItems = $this->buildAllItemsList();

        foreach ($allItems as $item) {
            $item->updateMaxValues($this->kilo);
        }
    }

    private function resolveRelativePositions()
    {
        // calculate any relative positions here - that way, nothing else
        // really needs to know about them

        wm_debug("Resolving relative positions for NODEs...\n");
        // safety net for cyclic dependencies
        $maxIterations = 100;
        $iterations = $maxIterations;
        do {
            $nSkipped = 0;
            $nChanged = 0;

            foreach ($this->nodes as $node) {
                // if it's not relative, or already dealt with, skip to the next one
                if (!$node->isRelativePositioned() || $node->isRelativePositionResolved()) {
                    continue;
                }

                $anchorName = $node->getRelativeAnchor();

                wm_debug("Resolving relative position for $node to $anchorName\n");

                if (!$this->nodeExists($anchorName)) {
                    wm_warn("NODE " . $node->name . " has a relative position to an unknown node ($anchorName)! [WMWARN10]\n");
                    continue;
                }

                $anchorNode = $this->getNode($anchorName);
                wm_debug("Found anchor node: $anchorNode\n");

                // check if we are relative to another node which is in turn relative to something
                // we need to resolve that one before we can resolve this one!
                if (($anchorNode->isRelativePositioned()) && (!$anchorNode->isRelativePositionResolved())) {
                    wm_debug("Skipping unresolved relative_to. Let's hope it's not a circular one\n");
                    $nSkipped++;
                    continue;
                }

                if ($node->resolveRelativePosition($anchorNode)) {
                    $nChanged++;
                }
            }
            wm_debug("Relative Positions Cycle $iterations/$maxIterations - set $nChanged and Skipped $nSkipped for unresolved dependencies\n");
            $iterations--;
        } while (($nChanged > 0) && ($iterations > 0));

        if ($nSkipped > 0) {
            wm_warn("There are probably Circular dependencies in relative POSITION lines for $nSkipped nodes (or $maxIterations levels of relative positioning). [WMWARN11]\n");
        }
    }



    function WriteDataFile($filename)
    {
        if ($filename != "") {
            $fd = fopen($filename, 'w');
            if ($fd) {
                foreach ($this->nodes as $node) {
                    if (!preg_match('/^::\s/', $node->name) && sizeof($node->targets) > 0) {
                        fputs($fd, sprintf("N_%s\t%f\t%f\r\n", $node->name, $node->bandwidth_in, $node->bandwidth_out));
                    }
                }
                foreach ($this->links as $link) {
                    if (!preg_match('/^::\s/', $link->name) && sizeof($link->targets) > 0) {
                        fputs($fd, sprintf("L_%s\t%f\t%f\r\n", $link->name, $link->bandwidth_in, $link->bandwidth_out));
                    }
                }
                fclose($fd);
            }
        }
    }

function WriteConfig($filename)
{
	global $WEATHERMAP_VERSION;

	$fd = fopen($filename, "w");
	$output = "";

	if ($fd) {
		$output .= "# Automatically generated by php-weathermap v$WEATHERMAP_VERSION\n\n";

        $output .= $this->fonts->getConfig();
        $output .= "\n";

        $basic_params = array(
            array('title', 'TITLE', CONFIG_TYPE_LITERAL),
            array('width', 'WIDTH', CONFIG_TYPE_LITERAL),
            array('height', 'HEIGHT', CONFIG_TYPE_LITERAL),
            array('background', 'BACKGROUND', CONFIG_TYPE_LITERAL),
            array('htmlstyle', 'HTMLSTYLE', CONFIG_TYPE_LITERAL),
            array('kilo', 'KILO', CONFIG_TYPE_LITERAL),
            array('keyfont', 'KEYFONT', CONFIG_TYPE_LITERAL),
            array('timefont', 'TIMEFONT', CONFIG_TYPE_LITERAL),
            array('titlefont', 'TITLEFONT', CONFIG_TYPE_LITERAL),
			array('htmloutputfile', 'HTMLOUTPUTFILE', CONFIG_TYPE_LITERAL),
			array('dataoutputfile', 'DATAOUTPUTFILE', CONFIG_TYPE_LITERAL),
			array('htmlstylesheet', 'HTMLSTYLESHEET', CONFIG_TYPE_LITERAL),
			array('imageuri', 'IMAGEURI', CONFIG_TYPE_LITERAL),
			array('imageoutputfile', 'IMAGEOUTPUTFILE', CONFIG_TYPE_LITERAL)
		);

		foreach ($basic_params as $param) {
			$field = $param[0];
			$keyword = $param[1];

			if ($this->$field != $this->inherit_fieldlist[$field]) {
				if ($param[2] == CONFIG_TYPE_COLOR) {
					$output .= "$keyword " . render_colour($this->$field) . "\n";
				}
				if ($param[2] == CONFIG_TYPE_LITERAL) {
					$output .= "$keyword " . $this->$field . "\n";
				}
			}
		}

		if (($this->timex != $this->inherit_fieldlist['timex'])
			|| ($this->timey != $this->inherit_fieldlist['timey'])
			|| ($this->stamptext != $this->inherit_fieldlist['stamptext'])
		) {
			$output .= "TIMEPOS " . $this->timex . " " . $this->timey . " " . $this->stamptext . "\n";
		}

		if (($this->mintimex != $this->inherit_fieldlist['mintimex'])
			|| ($this->mintimey != $this->inherit_fieldlist['mintimey'])
			|| ($this->minstamptext != $this->inherit_fieldlist['minstamptext'])
		) {
			$output .= "MINTIMEPOS " . $this->mintimex . " " . $this->mintimey . " " . $this->minstamptext . "\n";
		}

		if (($this->maxtimex != $this->inherit_fieldlist['maxtimex'])
			|| ($this->maxtimey != $this->inherit_fieldlist['maxtimey'])
			|| ($this->maxstamptext != $this->inherit_fieldlist['maxstamptext'])
		) {
			$output .= "MAXTIMEPOS " . $this->maxtimex . " " . $this->maxtimey . " " . $this->maxstamptext . "\n";
		}

		if (($this->titlex != $this->inherit_fieldlist['titlex'])
			|| ($this->titley != $this->inherit_fieldlist['titley'])
		) {
			$output .= "TITLEPOS " . $this->titlex . " " . $this->titley . "\n";
		}

		$output .= "\n";

        foreach ($this->colourtable as $k => $colour) {
            $output .= sprintf("%sCOLOR %s\n", $k, $colour->asConfig());
        }
        $output .= "\n";

        foreach ($this->scales as $scale_name=>$scale) {
            $output .= $scale->getConfig();
        }
        $output .= "\n";

		foreach ($this->hints as $hintname => $hint) {
			$output .= "SET $hintname $hint\n";
		}

		// this doesn't really work right, but let's try anyway
		if ($this->has_includes) {
			$output .= "\n# Included files\n";
			foreach ($this->included_files as $ifile) {
				$output .= "INCLUDE $ifile\n";
			}
		}

		$output .= "\n# End of global section\n\n";

		fwrite($fd, $output);

		foreach (array("template", "normal") as $which) {
			if ($which == "template") {
				fwrite($fd, "\n# TEMPLATE-only NODEs:\n");
			}
			if ($which == "normal") {
				fwrite($fd, "\n# regular NODEs:\n");
			}

			foreach ($this->nodes as $node) {
				if (!preg_match('/^::\s/', $node->name)) {
					if ($node->defined_in == $this->configfile) {

						if ($which == "template" && $node->x === null) {
							wm_debug("TEMPLATE\n");
							fwrite($fd, $node->WriteConfig());
						}
						if ($which == "normal" && $node->x !== null) {
							fwrite($fd, $node->WriteConfig());
						}
					}
				}
			}

			if ($which == "template") {
				fwrite($fd, "\n# TEMPLATE-only LINKs:\n");
			}
			if ($which == "normal") {
				fwrite($fd, "\n# regular LINKs:\n");
			}

			foreach ($this->links as $link) {
				if (!preg_match('/^::\s/', $link->name)) {
					if ($link->defined_in == $this->configfile) {
						if ($which == "template" && $link->a === null) {
							fwrite($fd, $link->WriteConfig());
						}
						if ($which == "normal" && $link->a !== null) {
							fwrite($fd, $link->WriteConfig());
						}
					}
				}
			}
		}

		fwrite($fd, "\n\n# That's All Folks!\n");

		fclose($fd);
	} else {
		wm_warn("Couldn't open config file $filename for writing");
		return (false);
	}

	return (true);
}

// pre-allocate colour slots for the colours used by the arrows
// this way, it's the pretty icons that suffer if there aren't enough colours, and
// not the actual useful data
// we skip any gradient scales
    private function preAllocateScaleColours($im, $refname = 'gdref1')
    {
        foreach ($this->colours as $scalename => $colours) {

            foreach ($colours as $key => $colour) {
                // only do this for non-gradients (c2 is null)
                if ((!isset($this->colours[$scalename][$key]['c2'])) && (!isset($this->colours[$scalename][$key][$refname]))) {

                    wm_debug("AllocateScaleColours: %s/%s %s\n", $scalename, $refname, $key);

                    $this->colours[$scalename][$key][$refname] = $this->colours[$scalename][$key]['c1']->gdAllocate($im);
                    wm_debug("AllocateScaleColours: %s/%s %s %s\n", $scalename, $refname, $key,
                        $this->colours[$scalename][$key]['c1']);
                }
            }
        }
    }

    public function preCalculate()
    {
        wm_debug("preCalculating everything\n");

        $allMapItems = $this->buildAllItemsList();

        foreach ($allMapItems as $item) {
            $item->preCalculate($this);
        }
    }

function DrawMap($filename = '', $thumbnailfile = '', $thumbnailmax = 250, $withnodes = TRUE, $use_via_overlay = FALSE, $use_rel_overlay=FALSE)
{
    wm_debug("Trace: DrawMap()\n");
	$bgimage=NULL;
	if($this->configfile != "")
	{
		$this->cachefile_version = crc32(file_get_contents($this->configfile));
	}
	else
	{
		$this->cachefile_version = crc32("........");
	}

	wm_debug("Running Post-Processing Plugins...\n");
	foreach ($this->postprocessclasses as $post_class)
	{
		wm_debug("Running $post_class"."->run()\n");
		//call_user_func_array(array($post_class, 'run'), array(&$this));
		$this->plugins['post'][$post_class]->run($this);

	}
	wm_debug("Finished Post-Processing Plugins...\n");

	wm_debug("=====================================\n");
	wm_debug("Start of Map Drawing\n");

	// if we're running tests, we force the time to a particular value,
        // so the output can be compared to a reference image more easily
        $testmode = intval($this->get_hint("testmode"));

        if ($testmode == 1) {
            $maptime = 1270813792;
            date_default_timezone_set('UTC');
        } else {
            $maptime = time();
        }
        $this->datestamp = strftime($this->stamptext, $maptime);

	// do the basic prep work
	if ($this->background != '')
	{
		if (is_readable($this->background))
		{
			$bgimage=imagecreatefromfile($this->background);

			if (!$bgimage) { wm_warn
				("Failed to open background image.  One possible reason: Is your BACKGROUND really a PNG?\n");
			}
			else
			{
				$this->width=imagesx($bgimage);
				$this->height=imagesy($bgimage);
			}
		}
		else { wm_warn
			("Your background image file could not be read. Check the filename, and permissions, for "
			. $this->background . "\n"); }
	}

	$image=imagecreatetruecolor($this->width, $this->height);

	# $image = imagecreate($this->width, $this->height);
	if (!$image) { wm_warn
		("Couldn't create output image in memory (" . $this->width . "x" . $this->height . ")."); }
	else
	{
		imagealphablending($image, true);
		if($this->get_hint("antialias") == 1) {
			// Turn on anti-aliasing if it exists and it was requested
			if(function_exists("imageantialias")) {
				imageantialias($image,true);
			}
		}

		// by here, we should have a valid image handle

		// save this away, now
		$this->image=$image;

		$this->white=myimagecolorallocate($image, 255, 255, 255);
		$this->black=myimagecolorallocate($image, 0, 0, 0);
		$this->grey=myimagecolorallocate($image, 192, 192, 192);
		$this->selected=myimagecolorallocate($image, 255, 0, 0); // for selections in the editor

		$this->preAllocateScaleColours($image);

		// fill with background colour anyway, in case the background image failed to load
		imagefilledrectangle($image, 0, 0, $this->width, $this->height, $this->colours['DEFAULT']['BG']['gdref1']);

		if ($bgimage)
		{
			imagecopy($image, $bgimage, 0, 0, 0, 0, $this->width, $this->height);
			imagedestroy ($bgimage);
		}

		// Now it's time to draw a map

		// do the node rendering stuff first, regardless of where they are actually drawn.
		// this is so we can get the size of the nodes, which links will need if they use offsets
    // TODO - the geometry part should be in preCalculate()
		foreach ($this->nodes as $node)
		{
			// don't try and draw template nodes
			wm_debug("Pre-rendering ".$node->name." to get bounding boxes.\n");
			if (!is_null($node->x)) {
        $this->nodes[$node->name]->preCalculate($this);
        $this->nodes[$node->name]->pre_render($image, $this);
      }
		}

    $this->preCalculate();


		$all_layers = array_keys($this->seen_zlayers);
		sort($all_layers);

		foreach ($all_layers as $z)
		{
			$z_items = $this->seen_zlayers[$z];
			wm_debug("Drawing layer $z\n");
			// all the map 'furniture' is fixed at z=1000
			if($z==1000)
			{
                foreach ($this->scales as $scaleName => $scaleObject) {
                    wm_debug("Drawing KEY for $scaleName if necessary.\n");

                    // the new scale object draws its own legend
                    $this->scales[$scaleName]->drawLegend($image);
                }

				$this->DrawTimestamp($image, $this->timefont, $this->colours['DEFAULT']['TIME']['gdref1']);
				if(! is_null($this->min_data_time))
				{
					$this->DrawTimestamp($image, $this->timefont, $this->colours['DEFAULT']['TIME']['gdref1'],"MIN");
					$this->DrawTimestamp($image, $this->timefont, $this->colours['DEFAULT']['TIME']['gdref1'],"MAX");
				}
				$this->DrawTitle($image, $this->titlefont, $this->colours['DEFAULT']['TITLE']['gdref1']);
			}

			if(is_array($z_items))
			{
				foreach($z_items as $it)
				{
                    wm_debug("Drawing " . $it->my_type() . " " . $it->name . "\n");
				    $it->Draw($image, $this);

                    if(1==0) {
                        $class_lower = strtolower(get_class($it));

                        if ($class_lower == 'weathermaplink') {
                            // only draw LINKs if they have NODES defined (not templates)
                            // (also, check if the link still exists - if this is in the editor, it may have been deleted by now)
                            if (isset($this->links[$it->name]) && isset($it->a) && isset($it->b)) {
                                wm_debug("Drawing LINK " . $it->name . "\n");
                                $this->links[$it->name]->Draw($image, $this);
                            }
                        }

                        if ($class_lower == 'weathermapnode') {
                            // if(!is_null($it->x)) $it->pre_render($image, $this);
                            if ($withnodes) {
                                // don't try and draw template nodes
                                if (isset($this->nodes[$it->name]) && !is_null($it->x)) {
                                    # print "::".get_class($it)."\n";
                                    wm_debug("Drawing NODE " . $it->name . "\n");
                                    $this->nodes[$it->name]->Draw($image, $this);
                                    $ii = 0;
                                    foreach ($this->nodes[$it->name]->boundingboxes as $bbox) {
                                        # $areaname = "NODE:" . $it->name . ':'.$ii;
                                        $areaname = "NODE:N" . $it->id . ":" . $ii;
                                        $this->imap->addArea("Rectangle", $areaname, '', $bbox);
                                        $this->nodes[$it->name]->imap_areas[] = $areaname;
                                        wm_debug("Adding imagemap area");
                                        $ii++;
                                    }
                                    wm_debug("Added $ii bounding boxes too\n");
                                }
                            }
                        }
                    }

				}
			}
		}

		$overlay = myimagecolorallocate($image, 200, 0, 0);

		// for the editor, we can optionally overlay some other stuff
        if ($this->context == 'editor') {
            if ($use_rel_overlay) {
                #		$overlay = myimagecolorallocate($image, 200, 0, 0);

                // first, we can show relatively positioned NODEs
                foreach ($this->nodes as $node) {
                    if ($node->relative_to != '') {
                        $rel_x = $this->nodes[$node->relative_to]->x;
                        $rel_y = $this->nodes[$node->relative_to]->y;
                        imagearc($image, $node->x, $node->y,
                            15, 15, 0, 360, $overlay);
                        imagearc($image, $node->x, $node->y,
                            16, 16, 0, 360, $overlay);

                        imageline($image, $node->x, $node->y,
                            $rel_x, $rel_y, $overlay);
                    }
                }
            }

            if ($use_via_overlay) {
                // then overlay VIAs, so they can be seen
                foreach ($this->links as $link) {
                    foreach ($link->vialist as $via) {
                        if (isset($via[2])) {
                            $x = $this->nodes[$via[2]]->x + $via[0];
                            $y = $this->nodes[$via[2]]->y + $via[1];
                        } else {
                            $x = $via[0];
                            $y = $via[1];
                        }
                        imagearc($image, $x, $y, 10, 10, 0, 360, $overlay);
                        imagearc($image, $x, $y, 12, 12, 0, 360, $overlay);
                    }
                }
            }
        }

		// Ready to output the results...

		if($filename == 'null')
		{
			// do nothing at all - we just wanted the HTML AREAs for the editor or HTML output
		}
		else
		{
			if ($filename == '') {
				imagepng($image);
			} else {
				$result = false;
				$functions = true;
				if (function_exists('imagejpeg') && preg_match('/\.jpg/i', $filename)) {
					wm_debug("Writing JPEG file to $filename\n");
					$result = imagejpeg($image, $filename);
				} elseif (function_exists('imagegif') && preg_match('/\.gif/i', $filename)) {
					wm_debug("Writing GIF file to $filename\n");
					$result = imagegif($image, $filename);
				} elseif (function_exists('imagepng') && preg_match('/\.png/i', $filename)) {
					wm_debug("Writing PNG file to $filename\n");
					$result = imagepng($image, $filename);
				} else {
					wm_warn("Failed to write map image. No function existed for the image format you requested. [WMWARN12]\n");
					$functions = false;
				}

				if (($result == false) && ($functions == true)) {
					if (file_exists($filename)) {
						wm_warn("Failed to overwrite existing image file $filename - permissions of existing file are wrong? [WMWARN13]");
					} else {
						wm_warn("Failed to create image file $filename - permissions of output directory are wrong? [WMWARN14]");
					}
				}
			}
		}

		if($this->context == 'editor2')
		{
			$cachefile = $this->cachefolder.DIRECTORY_SEPARATOR.dechex(crc32($this->configfile))."_bg.".$this->cachefile_version.".png";
			imagepng($image, $cachefile);
			$cacheuri = $this->cachefolder.'/'.dechex(crc32($this->configfile))."_bg.".$this->cachefile_version.".png";
			$this->mapcache = $cacheuri;
		}

		if (function_exists('imagecopyresampled'))
		{
			// if one is specified, and we can, write a thumbnail too
			if ($thumbnailfile != '')
			{
				$result = FALSE;
				if ($this->width > $this->height) { $factor=($thumbnailmax / $this->width); }
				else { $factor=($thumbnailmax / $this->height); }

				$this->thumb_width = $this->width * $factor;
				$this->thumb_height = $this->height * $factor;

				$imagethumb=imagecreatetruecolor($this->thumb_width, $this->thumb_height);
				imagecopyresampled($imagethumb, $image, 0, 0, 0, 0, $this->thumb_width, $this->thumb_height,
					$this->width, $this->height);
				$result = imagepng($imagethumb, $thumbnailfile);
				imagedestroy($imagethumb);



				if(($result==FALSE))
				{
					if(file_exists($filename))
					{
						wm_warn("Failed to overwrite existing image file $filename - permissions of existing file are wrong? [WMWARN15]");
					}
					else
					{
						wm_warn("Failed to create image file $filename - permissions of output directory are wrong? [WMWARN16]");
					}
				}
			}
		}
		else
		{
			wm_warn("Skipping thumbnail creation, since we don't have the necessary function. [WMWARN17]");
		}
		imagedestroy ($image);
	}
}

    function CleanUp()
    {
        parent::cleanUp();

        $all_layers = array_keys($this->seen_zlayers);

        foreach ($all_layers as $z) {
            $this->seen_zlayers[$z] = null;
        }

        foreach ($this->links as $link) {
            $link->cleanUp();
            unset($link);
        }

        foreach ($this->nodes as $node) {
            $node->cleanUp();
            unset($node);
        }

        // Clear up the other random hashes of information
        $this->dsinfocache = null;
        $this->colourtable = null;
        $this->usage_stats = null;
        $this->scales = null;
    }

function PreloadMapHTML()
{
	wm_debug("Trace: PreloadMapHTML()\n");
		//   onmouseover="return overlib('<img src=graph.png>',DELAY,250,CAPTION,'$caption');"  onmouseout="return nd();"

		// find the middle of the map
		$center_x=$this->width / 2;
		$center_y=$this->height / 2;

		// loop through everything. Figure out along the way if it's a node or a link
		$allitems = array(&$this->nodes, &$this->links);
		reset($allitems);

		while( list($kk,) = each($allitems))
		{
			unset($objects);
			# $objects = &$this->links;
			$objects = &$allitems[$kk];

			reset($objects);
			while (list($k,) = each($objects))
			{
				unset($myobj);
				$myobj = &$objects[$k];

				$type = $myobj->my_type();
				$prefix = substr($type,0,1);

				$dirs = array();
				//print "\n\nConsidering a $type - ".$myobj->name.".\n";
				if ($type == 'LINK') $dirs = array(IN=>array(0,2), OUT=>array(1,3));
				if ($type == 'NODE') $dirs = array(IN=>array(0,1,2,3));

				if ($this->htmlstyle == "overlib")
				{
					// check to see if any of the relevant things have a value
					$change = "";
					foreach ($dirs as $d => $parts) {
						//print "$d - ".join(" ",$parts)."\n";
						$change .= join('', $myobj->overliburl[$d]);
						$change .= $myobj->notestext[$d];
					}

					//print "CHANGE: $change\n";

					// skip all this if it's a template node
					if ($type=='LINK' && ! isset($myobj->a->name)) { $change = ''; }
					if ($type=='NODE' && ! isset($myobj->x)) { $change = ''; }

					if ($change != '')
					{
						//print "Something to be done.\n";
						if ($type=='NODE')
						{
							$mid_x = $myobj->x;
							$mid_y = $myobj->y;
						}
						if ($type=='LINK')
						{
							$a_x = $this->nodes[$myobj->a->name]->x;
							$a_y = $this->nodes[$myobj->a->name]->y;

							$b_x = $this->nodes[$myobj->b->name]->x;
							$b_y = $this->nodes[$myobj->b->name]->y;

							$mid_x=($a_x + $b_x) / 2;
							$mid_y=($a_y + $b_y) / 2;
						}
						$left=""; $above="";
						$img_extra = "";

						if ($myobj->overlibwidth != 0)
						{
							$left="WIDTH," . $myobj->overlibwidth . ",";
							$img_extra .= " WIDTH=$myobj->overlibwidth";

							if ($mid_x > $center_x) $left.="LEFT,";
						}

						if ($myobj->overlibheight != 0)
						{
							$above="HEIGHT," . $myobj->overlibheight . ",";
							$img_extra .= " HEIGHT=$myobj->overlibheight";

							if ($mid_y > $center_y) $above.="ABOVE,";
						}

						foreach ($dirs as $dir=>$parts)
						{
							$caption = ($myobj->overlibcaption[$dir] != '' ? $myobj->overlibcaption[$dir] : $myobj->name);
							$caption = $this->ProcessString($caption,$myobj);

							$overlibhtml = "onmouseover=\"return overlib('";

							$n = 0;
							if(sizeof($myobj->overliburl[$dir]) > 0)
							{
								// print "ARRAY:".is_array($link->overliburl[$dir])."\n";
								foreach ($myobj->overliburl[$dir] as $url)
								{
									if($n>0) { $overlibhtml .= '&lt;br /&gt;'; }
									$overlibhtml .= "&lt;img $img_extra src=" . $this->ProcessString($url,$myobj) . "&gt;";
									$n++;
								}
							}
							# print "Added $n for $dir\n";
							if(trim($myobj->notestext[$dir]) != '')
							{
								# put in a linebreak if there was an image AND notes
								if($n>0) $overlibhtml .= '&lt;br /&gt;';
								$note = $this->ProcessString($myobj->notestext[$dir],$myobj);
								$note = htmlspecialchars($note, ENT_NOQUOTES);
								$note=str_replace("'", "\\&apos;", $note);
								$note=str_replace('"', "&quot;", $note);
								$overlibhtml .= $note;
							}
							$overlibhtml .= "',DELAY,250,${left}${above}CAPTION,'" . $caption
							. "');\"  onmouseout=\"return nd();\"";

							foreach ($parts as $part)
							{
								$areaname = $type.":" . $prefix . $myobj->id. ":" . $part;
								//print "INFOURL for $areaname - ";

								$this->imap->setProp("extrahtml", $overlibhtml, $areaname);
							}
						}
					} // if change
				} // overlib?

				// now look at inforurls
				foreach ($dirs as $dir=>$parts)
				{
					foreach ($parts as $part)
					{
						$areaname = $type.":" . $prefix . $myobj->id. ":" . $part;

						if (($this->htmlstyle != 'editor') && ($myobj->infourl[$dir] != '')) {
							$this->imap->setProp("href", $this->ProcessString($myobj->infourl[$dir], $myobj),
								$areaname);
						}
					}
				}

			}
		}

}

    function asJS()
    {
        $js = '';

        $js .= "var Links = new Array();\n";
        $js .= "var LinkIDs = new Array();\n";

        foreach ($this->links as $link) {
            $js .= $link->asJS();
        }

        $js .= "var Nodes = new Array();\n";
        $js .= "var NodeIDs = new Array();\n";

        foreach ($this->nodes as $node) {
            $js .= $node->asJS();
        }

        return $js;
    }


// This method MUST run *after* DrawMap. It relies on DrawMap to call the map-drawing bits
// which will populate the ImageMap with regions.
//
// imagemapname is a parameter, so we can stack up several maps in the Cacti plugin with their own imagemaps
function MakeHTML($imagemapname = "weathermap_imap")
{
	wm_debug("Trace: MakeHTML()\n");
	// PreloadMapHTML fills in the ImageMap info, ready for the HTML to be created.
	$this->PreloadMapHTML();

	$html='';

	$html .= '<div class="weathermapimage" style="margin-left: auto; margin-right: auto; width: '.$this->width.'px;" >';
	if ( $this->imageuri != '') {
		$html.=sprintf(
			'<img id="wmapimage" src="%s" width="%d" height="%d" border="0" usemap="#%s"',
			$this->imageuri,
			$this->width,
			$this->height,
			$imagemapname
		);
		//$html .=  'alt="network weathermap" ';
		$html .= '/>';
		}
	else {
		$html.=sprintf(
			'<img id="wmapimage" src="%s" width="%d" height="%d" border="0" usemap="#%s"',
			$this->imagefile,
			$this->width,
			$this->height,
			$imagemapname
		);
		//$html .=  'alt="network weathermap" ';
		$html .= '/>';
	}
	$html .= '</div>';

	$html .= $this->SortedImagemap($imagemapname);

	return ($html);
}

	function SortedImagemap($imagemapname)
	{
		$html = "\n<map name=\"" . $imagemapname . '" id="' . $imagemapname . "\">\n";

		$all_layers = array_keys($this->seen_zlayers);
		rsort($all_layers);

		wm_debug("Starting to dump imagemap in reverse Z-order...\n");
		foreach ($all_layers as $z) {
			wm_debug("Writing HTML for layer $z\n");
			$z_items = $this->seen_zlayers[$z];
			if (is_array($z_items)) {
				wm_debug("   Found things for layer $z\n");

				// at z=1000, the legends and timestamps live
				if ($z == 1000) {
					wm_debug("     Builtins fit here.\n");

					foreach ($this->imap_areas as $areaname) {
						// skip the linkless areas if we are in the editor - they're redundant
						$html .= "\t".$this->imap->exactHTML($areaname, true, ($this->context
							!= 'editor'));
                        $html .= "\n";
					}

					foreach ($this->scales as $it) {
                        foreach ($it->getImageMapAreas() as $area) {
                            wm_debug("$area\n");
                            // skip the linkless areas if we are in the editor - they're redundant
                            $html .= "\t".$area->asHTML();
                            $html .= "\n";
                        }
                        $html .= "\n";
                    }
				}

                // we reverse the array for each zlayer so that the imagemap order
                // will match up with the draw order (last drawn should be first hit)
                /** @var WeatherMapDataItem $it */
                foreach (array_reverse($z_items) as $it) {
					if ($it->name != 'DEFAULT' && $it->name != ":: DEFAULT ::") {
						foreach ($it->getImageMapAreas() as $area) {
						    wm_debug("$area\n");
							// skip the linkless areas if we are in the editor - they're redundant
                            $html .= "\t".$area->asHTML();
                            $html .= "\n";
						}
                        $html .= "\n";
					}
				}
			}
		}

		$html .= '</map>';

		return ($html);
	}

    public function nodeExists($nodeName)
    {
        return array_key_exists($nodeName, $this->nodes);
    }

    public function linkExists($linkName)
    {
        return array_key_exists($linkName, $this->links);
    }

    /**
     * Create an array of all the nodes and links, mixed together.
     * readData() makes several passes through this list.
     *
     * @return array
     */
    private function buildAllItemsList()
    {
        // TODO - this should probably be a static, or otherwise cached
        $allItems = array();

        $listOfItemLists = array(&$this->nodes, &$this->links);
        reset($listOfItemLists);

        while (list($outerListCount,) = each($listOfItemLists)) {
            unset($itemList);
            $itemList = &$listOfItemLists[$outerListCount];

            reset($itemList);
            while (list($innerListCount,) = each($itemList)) {
                unset($oneMapItem);
                $oneMapItem = &$itemList[$innerListCount];
                $allItems [] = $oneMapItem;
            }
        }
        return $allItems;
    }


    /**
     * For each mapitem, loop through all its targets and find a plugin
     * that recognises them. Then register the target with the plugin
     * so that it can potentially pre-fetch or optimise in some way.
     *
     * @param $itemList
     */
    private function preProcessTargets($itemList)
    {
        wm_debug("Preprocessing targets\n");

        /** @var WeatherMapDataItem $mapItem */
        foreach ($itemList as $mapItem) {
            if ($mapItem->isTemplate()) {
                continue;
            }

            $mapItem->prepareForDataCollection();
        }
    }

    /**
     * Keep track of the current minimum and maximum timestamp for collected data
     *
     * @param $dataTime
     */
    public function registerDataTime($dataTime)
    {
        if ($dataTime == 0) {
            return;
        }

        if ($this->max_data_time == null || $dataTime > $this->max_data_time) {
            $this->max_data_time = $dataTime;
        }

        if ($this->min_data_time == null || $dataTime < $this->min_data_time) {
            $this->min_data_time = $dataTime;
        }
        wm_debug("Current DataTime MINMAX: " . $this->min_data_time . " -> " . $this->max_data_time . "\n");
    }

    private function readDataFromTargets($itemList)
    {
        wm_debug("======================================\n");
        wm_debug("Starting main collection loop\n");

        foreach ($itemList as $mapItem) {
            if ($mapItem->isTemplate()) {
                wm_debug("ReadData: Skipping $mapItem that looks like a template\n.");
                continue;
            }

            $mapItem->performDataCollection();

            // NOTE - this part still happens even if there were no targets
            $mapItem->aggregateDataResults();
            $mapItem->calculateScaleColours();

            unset($mapItem);
        }
    }

    private function prefetchPlugins()
    {
        // give all the plugins a chance to prefetch their results
        wm_debug("======================================\n");
        wm_debug("Starting DS plugin prefetch\n");
        $this->pluginMethod("data", "Prefetch");
//        foreach ($this->plugins['data'] as $name => $pluginEntry) {
//            $pluginEntry['object']->Prefetch($this);
//        }
    }

    private function pluginMethod($type, $method)
    {
        wm_debug("======================================\n");
        wm_debug("Running $type plugin $method method\n");

        foreach ($this->plugins[$type] as $name => $pluginEntry) {
            if ($pluginEntry['active']) {
                wm_debug("Running $name->$method()\n");
                $pluginEntry['object']->$method($this);
            }
        }
    }

    private function cleanupPlugins($type)
    {
        wm_debug("======================================\n");
        wm_debug("Starting DS plugin cleanup\n");
        $this->pluginMethod("data", "CleanUp");

//        foreach ($this->plugins[$type] as $name => $pluginEntry) {
//            if ($pluginEntry['active']) {
//                $pluginEntry['object']->CleanUp($this);
//            }
//        }
    }


    function readData()
    {
        // we skip readdata completely in sizedebug mode
        if ($this->sizedebug != 0) {
            wm_debug("Size Debugging is on. Skipping readData.\n");
            return;
        }

        wm_debug("======================================\n");
        wm_debug("ReadData: Updating link data for all links and nodes\n");

        $allMapItems = $this->buildAllItemsList();

        // $this->initialiseAllPlugins();

        // process all the targets and find a plugin for them
        $this->preProcessTargets($allMapItems);

        $this->prefetchPlugins();

        $this->readDataFromTargets($allMapItems);

        $this->cleanupPlugins('data');

        $this->runProcessorPlugins("post");

        wm_debug("ReadData Completed.\n");
        wm_debug("------------------------------\n");

    }



    public function createDefaultNodes()
    {
        wm_debug("Creating ':: DEFAULT ::' DEFAULT NODE\n");
        $defnode = new WeatherMapNode(":: DEFAULT ::", ":: DEFAULT ::", $this);

        $this->nodes[':: DEFAULT ::'] = &$defnode;

        wm_debug("Creating actual DEFAULT NODE from :: DEFAULT ::\n");
        $defnode2 = new WeatherMapNode("DEFAULT", ":: DEFAULT ::", $this);
        $this->nodes['DEFAULT'] = &$defnode2;
    }

    public function createDefaultLinks()
    {
        // these are the default defaults
        // by putting them into a normal object, we can use the
        // same code for writing out LINK DEFAULT as any other link.
        wm_debug("Creating ':: DEFAULT ::' DEFAULT LINK\n");
        // these two are used for default settings
        $deflink = new WeatherMapLink(":: DEFAULT ::", ":: DEFAULT ::", $this);

        $this->links[':: DEFAULT ::'] = &$deflink;

        wm_debug("Creating actual DEFAULT LINK from :: DEFAULT ::\n");
        $deflink2 = new WeatherMapLink("DEFAULT", ":: DEFAULT ::", $this);
        $this->links['DEFAULT'] = &$deflink2;
    }


};
// vim:ts=4:sw=4:
