<?php

// PHP Weathermap 0.98
// Copyright Howard Jones, 2005-2016 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License

require_once "HTML_ImageMap.class.php";

require_once "WeatherMap.functions.php";
require_once "exceptions.php";
require_once "base-classes.php";
require_once "plugin-base-classes.php";
require_once "geometry.php";
require_once "WMPoint.class.php";
require_once "WMVector.class.php";
require_once "WMLine.class.php";
require_once "WMUtility.class.php";
require_once "WMTarget.class.php";
require_once "WMColour.class.php";
require_once "fonts.php";

require_once "WeatherMapConfigReader.class.php";
require_once "WeatherMapScale.class.php";

require_once "WeatherMapDataItem.class.php";
require_once "WeatherMapNode.class.php";
require_once "WeatherMapLink.class.php";


$WEATHERMAP_VERSION = "0.98a";
$weathermap_debugging = FALSE;
$weathermap_map = "";
$weathermap_warncount = 0;
$weathemap_lazycounter = 0;

// don't produce debug output for these functions
$weathermap_debug_suppress = array (
    'processstring',
    'mysprintf'
);

// don't output warnings/errors for these codes (WMxxx)
$weathermap_error_suppress = array();

// Turn on ALL error reporting for now (this stops PEAR Console_Getopt from working).
// error_reporting (E_ALL|E_STRICT);
error_reporting (E_ALL^E_STRICT);

// parameterise the in/out stuff a bit
define("IN",0);
define("OUT",1);
define("WMCHANNELS",2);

define('CONFIG_TYPE_LITERAL',0);
define('CONFIG_TYPE_COLOR',1);

// some strings that are used in more than one place
define('FMT_BITS_IN',"{link:this:bandwidth_in:%2k}");
define('FMT_BITS_OUT',"{link:this:bandwidth_out:%2k}");
define('FMT_UNFORM_IN',"{link:this:bandwidth_in}");
define('FMT_UNFORM_OUT',"{link:this:bandwidth_out}");
define('FMT_PERC_IN',"{link:this:inpercent:%.2f}%");
define('FMT_PERC_OUT',"{link:this:outpercent:%.2f}%");

// the fields within a spine triple
define("X",0);
define("Y",1);
define("DISTANCE",2);

require_once "WeatherMap.keywords.inc.php";

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
	var $nodes = array(); // an array of WeatherMapNodes
	var $links = array(); // an array of WeatherMapLinks
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
	var $cachefolder,$mapcache,$cachefile_version;
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
	var $usage_stats = array();
	var $coverage = array();
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

		if($input === '') {
			return '';
		}

		// don't bother with all this regexp rubbish if there's nothing to match
		if(false === strpos($input, "{")) {
			return $input;
		}

		assert('is_scalar($input)');

		$context_description = strtolower( $context->my_type() );
		if($context_description != "map") $context_description .= ":" . $context->name;

		wm_debug("Trace: ProcessString($input, $context_description)\n");

		if($multiline==TRUE)
		{
			$i = $input;
			$input = str_replace("\\n","\n",$i);
			# if($i != $input)  warn("$i into $input\n");
		}

		if($context_description === 'node') {
			$input = str_replace("{node:this:graph_id}", $context->get_hint("graph_id" ), $input);
			$input = str_replace("{node:this:name}", $context->name, $input);
		}

		if($context_description === 'link') {
			$input = str_replace("{link:this:graph_id}", $context->get_hint("graph_id" ), $input);
		}

		// check if we can now quit early before the regexp stuff
		if(false === strpos($input, "{")) {
			return $input;
 		}

		$output = $input;

		while( preg_match('/(\{(?:node|map|link)[^}]+\})/',$input,$matches) )
		{
			$value = "[UNKNOWN]";
			$format = "";
			$key = $matches[1];
			wm_debug("ProcessString: working on ".$key."\n");

			if ( preg_match('/\{(node|map|link):([^}]+)\}/',$key,$matches) )
			{
				$type = $matches[1];
				$args = $matches[2];
				# debug("ProcessString: type is ".$type.", arguments are ".$args."\n");

				if($type == 'map')
				{
					$the_item = $this;
					if(preg_match('/map:([^:]+):*([^:]*)/',$args,$matches))
					{
						$args = $matches[1];
						$format = $matches[2];
					}
				}

				if(($type == 'link') || ($type == 'node'))
				{
					if(preg_match('/([^:]+):([^:]+):*([^:]*)/',$args,$matches))
					{
						$itemname = $matches[1];
						$args = $matches[2];
						$format = $matches[3];

		#				debug("ProcessString: item is $itemname, and args are now $args\n");

						$the_item = NULL;
						if( ($itemname == "this") && ($type == strtolower($context->my_type())) )
						{
							$the_item = $context;
						}
						elseif( strtolower($context->my_type()) == "link" && $type == 'node' && ($itemname == '_linkstart_' || $itemname == '_linkend_') )
						{
							// this refers to the two nodes at either end of this link
							if($itemname == '_linkstart_')
							{
								$the_item = $context->a;
							}

							if($itemname == '_linkend_')
							{
								$the_item = $context->b;
							}
						}
						elseif( ($itemname == "parent") && ($type == strtolower($context->my_type())) && ($type=='node') && ($context->relative_to != '') )
						{
							$the_item = $this->nodes[$context->relative_to];
						}
						else
						{
							if( ($type == 'link') && isset($this->links[$itemname]) )
							{
								$the_item = $this->links[$itemname];
							}
							if( ($type == 'node') && isset($this->nodes[$itemname]) )
							{
								$the_item = $this->nodes[$itemname];
							}
						}
					}
				}

				if(is_null($the_item))
				{
					wm_warn("ProcessString: $key refers to unknown item (context is $context_description) [WMWARN05]\n");
				}
				else
				{
				#	warn($the_item->name.": ".var_dump($the_item->hints)."\n");
					wm_debug("ProcessString: Found appropriate item: ".get_class($the_item)." ".$the_item->name."\n");

					# warn($the_item->name."/hints: ".var_dump($the_item->hints)."\n");
					# warn($the_item->name."/notes: ".var_dump($the_item->notes)."\n");

					// SET and notes have precedent over internal properties
					// this is my laziness - it saves me having a list of reserved words
					// which are currently used for internal props. You can just 'overwrite' any of them.
					if(isset($the_item->hints[$args]))
					{
						$value = $the_item->hints[$args];
						wm_debug("ProcessString: used hint\n");
					}
					// for some things, we don't want to allow notes to be considered.
					// mainly - TARGET (which can define command-lines), shouldn't be
					// able to get data from uncontrolled sources (i.e. data sources rather than SET in config files).
					elseif($include_notes && isset($the_item->notes[$args]))
					{
						$value = $the_item->notes[$args];
						wm_debug("ProcessString: used note\n");

					}
					elseif(isset($the_item->$args))
					{
						$value = $the_item->$args;
						wm_debug("ProcessString: used internal property\n");
					}
				}
			}

			// format, and sanitise the value string here, before returning it

			if($value===NULL) $value='NULL';
			wm_debug("ProcessString: replacing ".$key." with $value\n");

			# if($format != '') $value = sprintf($format,$value);
			if($format != '')
			{

		#		debug("Formatting with mysprintf($format,$value)\n");
				$value = mysprintf($format,$value, $this->kilo);
			}

		#	debug("ProcessString: formatted to $value\n");
			$input = str_replace($key,'',$input);
			$output = str_replace($key,$value,$output);
		}
		#debug("ProcessString: output is $output\n");
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
                    // $pluginEntry['active'] = false;
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
        foreach ($this->plugins[$stage] as $name => $pluginEntry) {
            wm_debug("Running %s->run()\n", $name);
            $pluginEntry['object']->run($this);
        }
        wm_debug("Finished $stage-processing plugins...\n");
    }

    function DatasourceInit()
    {
        wm_debug("Running Init() for Data Source Plugins...\n");
        foreach ($this->datasourceclasses as $ds_class) {
            // make an instance of the class
            $dsplugins[$ds_class] = new $ds_class;
            wm_debug("Running $ds_class" . "->Init()\n");
            # $ret = call_user_func(array($ds_class, 'Init'), $this);
            assert('isset($this->plugins["data"][$ds_class])');

            $ret = $this->plugins['data'][$ds_class]->Init($this);

            if (!$ret) {
                wm_debug("Removing $ds_class from Data Source list, since Init() failed\n");
                $this->activedatasourceclasses[$ds_class] = 0;
                # unset($this->datasourceclasses[$ds_class]);
            }
        }
        wm_debug("Finished Initialising Plugins...\n");
    }

function ProcessTargets()
{
    throw new WeathermapDeprecatedException("old targets");
	wm_debug("Preprocessing targets\n");

	$allitems = array(&$this->links, &$this->nodes);
	reset($allitems);

	wm_debug("Preprocessing targets\n");

	while( list($kk,) = each($allitems))
	{
		unset($objects);
		$objects = &$allitems[$kk];

		reset($objects);
		while (list($k,) = each($objects))
		{
			unset($myobj);
			$myobj = &$objects[$k];

			$type = $myobj->my_type();
			$name=$myobj->name;


			if( ($type=='LINK' && isset($myobj->a)) || ($type=='NODE' && !is_null($myobj->x) ) )
			{
				if (count($myobj->targets)>0)
				{
					$tindex = 0;
					foreach ($myobj->targets as $target)
					{
						wm_debug ("ProcessTargets: New Target: $target[4]\n");
						// processstring won't use notes (only hints) for this string

						$targetstring = $this->ProcessString($target[4], $myobj, FALSE, FALSE);
						if($target[4] != $targetstring) wm_debug("Targetstring is now $targetstring\n");

						// if the targetstring starts with a -, then we're taking this value OFF the aggregate
						$multiply = 1;
						if(preg_match('/^-(.*)/',$targetstring,$matches))
						{
							$targetstring = $matches[1];
							$multiply = -1 * $multiply;
						}

						// if the remaining targetstring starts with a number and a *-, then this is a scale factor
						if(preg_match('/^(\d+\.?\d*)\*(.*)/',$targetstring,$matches))
						{
							$targetstring = $matches[2];
							$multiply = $multiply * floatval($matches[1]);
						}

						$matched = FALSE;
						$matched_by = '';
						foreach ($this->datasourceclasses as $ds_class)
						{
							if(!$matched)
							{
								// $recognised = call_user_func(array($ds_class, 'Recognise'), $targetstring);
								$recognised = $this->plugins['data'][$ds_class]->Recognise($targetstring);

								if( $recognised )
								{
									$matched = TRUE;
									$matched_by = $ds_class;

									if($this->activedatasourceclasses[$ds_class])
									{
										$this->plugins['data'][$ds_class]->Register($targetstring, $this, $myobj);
										if($type == 'NODE')
										{
											$this->nodes[$name]->targets[$tindex][1] = $multiply;
											$this->nodes[$name]->targets[$tindex][0] = $targetstring;
											$this->nodes[$name]->targets[$tindex][5] = $matched_by;
										}
										if($type == 'LINK')
										{
											$this->links[$name]->targets[$tindex][1] = $multiply;
											$this->links[$name]->targets[$tindex][0] = $targetstring;
											$this->links[$name]->targets[$tindex][5] = $matched_by;
										}
									}
									else
									{
										wm_warn("ProcessTargets: $type $name, target: $targetstring on config line $target[3] of $target[2] was recognised as a valid TARGET by a plugin that is unable to run ($ds_class) [WMWARN07]\n");
									}
								}
							}
						}
						if(! $matched)
						{
							wm_warn("ProcessTargets: $type $name, target: $target[4] on config line $target[3] of $target[2] was not recognised as a valid TARGET [WMWARN08]\n");
						}

						$tindex++;
					}
				}
			}
		}
	}
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
            $string = screenshotify($string);
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
     * Temporary new ReadConfig, using the new table of keywords
     * However, this also expects a bunch of other internal change (WMColour, WMScale etc)
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

	function ReadConfig_Commit(&$curobj)
	{
		if (is_null($curobj)) {
			return;
		}
		$last_seen = $curobj->my_type();
		// first, save the previous item, before starting work on the new one
		if ($last_seen == "NODE") {
			$this->nodes[$curobj->name] = $curobj;
			wm_debug("Saving Node: " . $curobj->name . "\n");
			if ($curobj->template == 'DEFAULT') {
				$this->node_template_tree["DEFAULT"][] = $curobj->name;
			}
		}
		if ($last_seen == "LINK") {
			if (isset($curobj->a) && isset($curobj->b)) {
				$this->links[$curobj->name] = $curobj;
				wm_debug("Saving Link: " . $curobj->name . "\n");
			} else {
				$this->links[$curobj->name] = $curobj;
				wm_debug("Saving Template-Only Link: " . $curobj->name . "\n");
			}
			if ($curobj->template == 'DEFAULT') {
				$this->link_template_tree["DEFAULT"][] = $curobj->name;
			}
		}
	}


function WriteDataFile($filename)
{
	if($filename != "") {
		$fd = fopen($filename, 'w');
		# $output = '';
		if($fd) {
			foreach ($this->nodes as $node) {
				if (!preg_match('/^::\s/', $node->name) && sizeof($node->targets)>0 )  {
					fputs($fd, sprintf("N_%s\t%f\t%f\r\n", $node->name, $node->bandwidth_in, $node->bandwidth_out));
				}
			}
			foreach ($this->links as $link) {
				if (!preg_match('/^::\s/', $link->name) && sizeof($link->targets)>0) {
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
		ImageAlphaBlending($image, true);
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
		foreach ($this->nodes as $node)
		{
			// don't try and draw template nodes
			wm_debug("Pre-rendering ".$node->name." to get bounding boxes.\n");
			if(!is_null($node->x)) $this->nodes[$node->name]->pre_render($image, $this);
		}

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

//
//				foreach ($this->colours as $scalename=>$colours)
//				{
//					wm_debug("Drawing KEY for $scalename if necessary.\n");
//
//					if( (isset($this->numscales[$scalename])) && (isset($this->keyx[$scalename])) && ($this->keyx[$scalename] >= 0) && ($this->keyy[$scalename] >= 0) )
//					{
//						if($this->keystyle[$scalename]=='classic') $this->DrawLegend_Classic($image,$scalename,FALSE);
//						if($this->keystyle[$scalename]=='horizontal') $this->DrawLegend_Horizontal($image,$scalename,$this->keysize[$scalename]);
//						if($this->keystyle[$scalename]=='vertical') $this->DrawLegend_Vertical($image,$scalename,$this->keysize[$scalename]);
//						if($this->keystyle[$scalename]=='inverted') $this->DrawLegend_Vertical($image,$scalename,$this->keysize[$scalename],true);
//						if($this->keystyle[$scalename]=='tags') $this->DrawLegend_Classic($image,$scalename,TRUE);
//					}
//				}

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
					$class_lower = strtolower(get_class($it));

					if ($class_lower =='weathermaplink')
					{
						// only draw LINKs if they have NODES defined (not templates)
						// (also, check if the link still exists - if this is in the editor, it may have been deleted by now)
						if ( isset($this->links[$it->name]) && isset($it->a) && isset($it->b))
						{
							wm_debug("Drawing LINK ".$it->name."\n");
							$this->links[$it->name]->Draw($image, $this);
						}
					}
					if ($class_lower =='weathermapnode')
					{
						// if(!is_null($it->x)) $it->pre_render($image, $this);
						if ($withnodes)
						{
							// don't try and draw template nodes
							if (isset($this->nodes[$it->name]) && !is_null($it->x))
							{
								# print "::".get_class($it)."\n";
								wm_debug("Drawing NODE ".$it->name."\n");
								$this->nodes[$it->name]->NewDraw($image, $this);
								$ii=0;
								foreach ($this->nodes[$it->name]->boundingboxes as $bbox)
								{
									# $areaname = "NODE:" . $it->name . ':'.$ii;
									$areaname = "NODE:N". $it->id . ":" . $ii;
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

		$overlay = myimagecolorallocate($image, 200, 0, 0);

		// for the editor, we can optionally overlay some other stuff
        if($this->context == 'editor')
        {
		if($use_rel_overlay)
		{
		#		$overlay = myimagecolorallocate($image, 200, 0, 0);

			// first, we can show relatively positioned NODEs
			foreach ($this->nodes as $node) {
					if($node->relative_to != '')
					{
							$rel_x = $this->nodes[$node->relative_to]->x;
							$rel_y = $this->nodes[$node->relative_to]->y;
							imagearc($image,$node->x, $node->y,
									15,15,0,360,$overlay);
							imagearc($image,$node->x, $node->y,
									16,16,0,360,$overlay);

							imageline($image,$node->x, $node->y,
									$rel_x, $rel_y, $overlay);
					}
			}
		}

		if($use_via_overlay)
		{
			// then overlay VIAs, so they can be seen
			foreach($this->links as $link)
			{
				foreach ($link->vialist as $via)
				{
					if(isset($via[2]))
					{
						$x = $this->nodes[$via[2]]->x + $via[0];
						$y = $this->nodes[$via[2]]->y + $via[1];
					}
					else
					{
						$x = $via[0];
						$y = $via[1];
					}
					imagearc($image, $x,$y, 10,10,0,360,$overlay);
					imagearc($image, $x,$y, 12,12,0,360,$overlay);
				}
			}
		}
        }

		#$this->myimagestring($image, 3, 200, 100, "Test 1\nLine 2", $overlay,0);

#	$this->myimagestring($image, 30, 100, 100, "Test 1\nLine 2", $overlay,0);
		#$this->myimagestring($image, 30, 200, 200, "Test 1\nLine 2", $overlay,45);

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
	$all_layers = array_keys($this->seen_zlayers);

    foreach ($all_layers as $z) {
        $this->seen_zlayers[$z] = null;
    }

    foreach ($this->links as $link) {
        $link->owner = null;
        $link->a = null;
        $link->b = null;

        unset($link);
    }

    foreach ($this->nodes as $node) {
        // destroy all the images we created, to prevent memory leaks

        if (isset($node->image)) {
            imagedestroy($node->image);
        }
        $node->owner = null;
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
	$js='';

	$js .= "var Links = new Array();\n";
	$js .= "var LinkIDs = new Array();\n";
	# $js.=$this->defaultlink->asJS();

	foreach ($this->links as $link) { $js.=$link->asJS(); }

	$js .= "var Nodes = new Array();\n";
	$js .= "var NodeIDs = new Array();\n";
	# $js.=$this->defaultnode->asJS();

	foreach ($this->nodes as $node) { $js.=$node->asJS(); }

	return $js;
}

function asJSON()
{
	$json = '';

	$json .= "{ \n";

	$json .= "\"map\": {  \n";
	foreach (array_keys($this->inherit_fieldlist)as $fld)
	{
		$json .= js_escape($fld).": ";
		$json .= js_escape($this->$fld);
		$json .= ",\n";
	}
	$json = rtrim($json,", \n");
	$json .= "\n},\n";

	$json .= "\"nodes\": {\n";
	$json .= $this->defaultnode->asJSON();
	foreach ($this->nodes as $node) { $json .= $node->asJSON(); }
	$json = rtrim($json,", \n");
	$json .= "\n},\n";



	$json .= "\"links\": {\n";
	$json .= $this->defaultlink->asJSON();
	foreach ($this->links as $link) { $json .= $link->asJSON(); }
	$json = rtrim($json,", \n");
	$json .= "\n},\n";

	$json .= "'imap': [\n";
	$json .= $this->imap->subJSON("NODE:");
	// should check if there WERE nodes...
	$json .= ",\n";
	$json .= $this->imap->subJSON("LINK:");
	$json .= "\n]\n";
	$json .= "\n";

	$json .= ", 'valid': 1}\n";

	return($json);
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
		$html = '<map name="' . $imagemapname . '" id="' . $imagemapname . '">';

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
						$html .= $this->imap->exactHTML($areaname, true, ($this->context
							!= 'editor'));
					}
				}

				foreach ($z_items as $it) {
					if ($it->name != 'DEFAULT' && $it->name != ":: DEFAULT ::") {
						foreach ($it->imap_areas as $areaname) {
							// skip the linkless areas if we are in the editor - they're redundant
							$html .= $this->imap->exactHTML(
								$areaname, true, ($this->context != 'editor'));
						}
					}
				}
			}
		}

		$html .= '</map>';

		return ($html);
	}

// update any editor cache files.
// if the config file is newer than the cache files, or $agelimit seconds have passed,
// then write new stuff, otherwise just return.
// ALWAYS deletes files in the cache folder older than $agelimit, also!
function CacheUpdate($agelimit=600)
{
	global $weathermap_lazycounter;

	$cachefolder = $this->cachefolder;
	$configchanged = filemtime($this->configfile );
	// make a unique, but safe, prefix for all cachefiles related to this map config
	// we use CRC32 because it makes for a shorter filename, and collisions aren't the end of the world.
	$cacheprefix = dechex(crc32($this->configfile));

	wm_debug("Comparing files in $cachefolder starting with $cacheprefix, with date of $configchanged\n");

	$dh=opendir($cachefolder);

	if ($dh)
	{
		while ($file=readdir($dh))
		{
			$realfile = $cachefolder . DIRECTORY_SEPARATOR . $file;

			if(is_file($realfile) && ( preg_match('/^'.$cacheprefix.'/',$file) ))
				//                                            if (is_file($realfile) )
			{
				wm_debug("$realfile\n");
				if( (filemtime($realfile) < $configchanged) || ((time() - filemtime($realfile)) > $agelimit) )
				{
					wm_debug("Cache: deleting $realfile\n");
					unlink($realfile);
				}
			}
		}
		closedir ($dh);

		foreach ($this->nodes as $node)
		{
			if(isset($node->image))
			{
				$nodefile = $cacheprefix."_".dechex(crc32($node->name)).".png";
				$this->nodes[$node->name]->cachefile = $nodefile;
				imagepng($node->image,$cachefolder.DIRECTORY_SEPARATOR.$nodefile);
			}
		}

		foreach ($this->keyimage as $key=>$image)
		{
				$scalefile = $cacheprefix."_scale_".dechex(crc32($key)).".png";
				$this->keycache[$key] = $scalefile;
				imagepng($image,$cachefolder.DIRECTORY_SEPARATOR.$scalefile);
		}


		$json = "";
		$fd = fopen($cachefolder.DIRECTORY_SEPARATOR.$cacheprefix."_map.json","w");
		foreach (array_keys($this->inherit_fieldlist)as $fld)
		{
			$json .= js_escape($fld).": ";
			$json .= js_escape($this->$fld);
			$json .= ",\n";
		}
		$json = rtrim($json,", \n");
		fputs($fd,$json);
		fclose($fd);

		$json = "";
		$fd = fopen($cachefolder.DIRECTORY_SEPARATOR.$cacheprefix."_tree.json","w");
		$id = 10;	// first ID for user-supplied thing

		$json .= "{ id: 1, text: 'SCALEs'\n, children: [\n";
		foreach ($this->colours as $scalename=>$colours)
		{
			$json .= "{ id: " . $id++ . ", text:" . js_escape($scalename) . ", leaf: true }, \n";
		}
		$json = rtrim($json,", \n");
		$json .= "]},\n";

		$json .= "{ id: 2, text: 'FONTs',\n children: [\n";
		foreach ($this->fonts as $fontnumber => $font)
		{
			if ($font->type == 'truetype')
				$json .= sprintf("{ id: %d, text: %s, leaf: true}, \n", $id++, js_escape("Font $fontnumber (TT)"));

			if ($font->type == 'gd')
				$json .= sprintf("{ id: %d, text: %s, leaf: true}, \n", $id++, js_escape("Font $fontnumber (GD)"));
		}
		$json = rtrim($json,", \n");
		$json .= "]},\n";

		$json .= "{ id: 3, text: 'NODEs',\n children: [\n";
		$json .= "{ id: ". $id++ . ", text: 'DEFAULT', children: [\n";

		$weathemap_lazycounter = $id;
		// pass the list of subordinate nodes to the recursive tree function
		$json .= $this->MakeTemplateTree( $this->node_template_tree );
		$id = $weathermap_lazycounter;

		$json = rtrim($json,", \n");
		$json .= "]} ]},\n";

		$json .= "{ id: 4, text: 'LINKs',\n children: [\n";
		$json .= "{ id: ". $id++ . ", text: 'DEFAULT', children: [\n";
		$weathemap_lazycounter = $id;
		$json .= $this->MakeTemplateTree( $this->link_template_tree );
		$id = $weathermap_lazycounter;
		$json = rtrim($json,", \n");
		$json .= "]} ]}\n";

		fputs($fd,"[". $json . "]");
		fclose($fd);

		$fd = fopen($cachefolder.DIRECTORY_SEPARATOR.$cacheprefix."_nodes.json","w");
		$json = "";
//		$json = $this->defaultnode->asJSON(TRUE);
		foreach ($this->nodes as $node) { $json .= $node->asJSON(TRUE); }
		$json = rtrim($json,", \n");
		fputs($fd,$json);
		fclose($fd);

		$fd = fopen($cachefolder.DIRECTORY_SEPARATOR.$cacheprefix."_nodes_lite.json","w");
		$json = "";
//		$json = $this->defaultnode->asJSON(FALSE);
		foreach ($this->nodes as $node) { $json .= $node->asJSON(FALSE); }
		$json = rtrim($json,", \n");
		fputs($fd,$json);
		fclose($fd);



		$fd = fopen($cachefolder.DIRECTORY_SEPARATOR.$cacheprefix."_links.json","w");
		$json = "";
//		$json = $this->defaultlink->asJSON(TRUE);
		foreach ($this->links as $link) { $json .= $link->asJSON(TRUE); }
		$json = rtrim($json,", \n");
		fputs($fd,$json);
		fclose($fd);

		$fd = fopen($cachefolder.DIRECTORY_SEPARATOR.$cacheprefix."_links_lite.json","w");
		$json = "";
//		$json = $this->defaultlink->asJSON(FALSE);
		foreach ($this->links as $link) { $json .= $link->asJSON(FALSE); }
		$json = rtrim($json,", \n");
		fputs($fd,$json);
		fclose($fd);

		$fd = fopen($cachefolder.DIRECTORY_SEPARATOR.$cacheprefix."_imaphtml.json","w");
		$json = $this->imap->subHTML("LINK:");
		fputs($fd,$json);
		fclose($fd);


		$fd = fopen($cachefolder.DIRECTORY_SEPARATOR.$cacheprefix."_imap.json","w");
		$json = '';
		$nodejson = trim($this->imap->subJSON("NODE:"));
		if($nodejson != '')
		{
			$json .= $nodejson;
			// should check if there WERE nodes...
			$json .= ",\n";
		}
		$json .= $this->imap->subJSON("LINK:");
		fputs($fd,$json);
		fclose($fd);

	}
	else { wm_debug("Couldn't read cache folder.\n"); }
}

function MakeTemplateTree( &$tree_list, $startpoint="DEFAULT")
{
	global $weathermap_lazycounter;

	$output = "";
	foreach ($tree_list[$startpoint] as $subnode)
	{
		$output .= "{ id: " . $weathermap_lazycounter++ . ", text: " . js_escape($subnode);
		if( isset($tree_list[$subnode]))
		{
			$output .= ", children: [ \n";
			$output .= $this->MakeTemplateTree($tree_list, $subnode);
			$output = rtrim($output,", \n");
			$output .= "] \n";
		}
		else
		{
			$output .= ", leaf: true ";
		}
		$output .= "}, \n";
	}

	return($output);
}

function DumpStats($filename="")
{
	$report = "Feature Statistics:\n\n";
	foreach ($this->usage_stats as $key=>$val)
	{
		$report .= sprintf("%70s => %d\n",$key,$val);
	}

	if($filename == "") print $report;
}

 function SeedCoverage()
        {
                global $WM_config_keywords2;

                foreach ( array_keys($WM_config_keywords2) as $context) {
                        foreach ( array_keys($WM_config_keywords2[$context]) as $keyword) {
                                foreach ( $WM_config_keywords2[$context][$keyword] as $patternarray) {
                                        $key = sprintf("%s:%s:%s",$context, $keyword ,$patternarray[1]);
                                        $this->coverage[$key] = 0;
                                }
                        }
                }
        }

        function LoadCoverage($file)
        {
                return 0;
                $i=0;
                $fd = fopen($file,"r");
                if(is_resource($fd)) {
                    while(! feof($fd)) {
                            $line = fgets($fd,1024);
                            $line = trim($line);
                            list($val,$key) = explode("\t",$line);
                            if($key != "") {
                                $this->coverage[$key] = $val;
                            }
                            if($val > 0) { $i++; }
                    }
                    fclose($fd);
                }
#               print "Loaded $i non-zero coverage stats.\n";
        }

        function SaveCoverage($file)
        {
                $i=0;
                $fd = fopen($file,"w+");
                foreach ($this->coverage as $key=>$val) {
                        fputs($fd, "$val\t$key\n");
                        if($val > 0) { $i++; }
                }
                fclose($fd);
#               print "Saved $i non-zero coverage stats.\n";
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
        foreach ($this->plugins['data'] as $name => $pluginEntry) {
            $pluginEntry['object']->Prefetch($this);
        }
    }

    private function cleanupPlugins($type)
    {
        wm_debug("======================================\n");
        wm_debug("Starting DS plugin cleanup\n");

        foreach ($this->plugins[$type] as $name => $pluginEntry) {
            $pluginEntry['object']->CleanUp($this);
        }
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
