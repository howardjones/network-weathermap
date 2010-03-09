<?php
// PHP Weathermap 0.97a
// Copyright Howard Jones, 2005-2010 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License

require_once "HTML_ImageMap.class.php";

require_once "WeatherMap.functions.php";
require_once "WeatherMapNode.class.php";
require_once "WeatherMapLink.class.php";

$WEATHERMAP_VERSION="0.97a";
$weathermap_debugging=FALSE;
$weathermap_map="";
$weathermap_warncount=0;
$weathermap_debug_suppress = array("processstring","mysprintf");
$weathemap_lazycounter=0;

// Turn on ALL error reporting for now.
// error_reporting (E_ALL|E_STRICT);

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

// ***********************************************

// template class for data sources. All data sources extend this class.
// I really wish PHP4 would just die overnight
class WeatherMapDataSource
{
	// Initialize - called after config has been read (so SETs are processed)
	// but just before ReadData. Used to allow plugins to verify their dependencies
	// (if any) and bow out gracefully. Return FALSE to signal that the plugin is not
	// in a fit state to run at the moment.
	function Init(&$map) { return TRUE; }

	// called with the TARGET string. Returns TRUE or FALSE, depending on whether it wants to handle this TARGET
	// called by map->ReadData()
	function Recognise( $targetstring ) { return FALSE; }

	// the actual ReadData
	//   returns an array of two values (in,out). -1,-1 if it couldn't get valid data
	//   configline is passed in, to allow for better error messages
	//   itemtype and itemname may be used as part of the target (e.g. for TSV source line)
	// function ReadData($targetstring, $configline, $itemtype, $itemname, $map) { return (array(-1,-1)); }
	function ReadData($targetstring, &$map, &$item)
	{
		return(array(-1,-1));
	}
	
	// pre-register a target + context, to allow a plugin to batch up queries to a slow database, or snmp for example
	function Register($targetstring, &$map, &$item)
	{
	
	}
	
	// called before ReadData, to allow plugins to DO the prefetch of targets known from Register
	function Prefetch()
	{
		
	}
}

// template classes for the pre- and post-processor plugins
class WeatherMapPreProcessor
{
	function run($map) { return FALSE; }
}

class WeatherMapPostProcessor
{
	function run($map) { return FALSE; }
}

// ***********************************************

// Links, Nodes and the Map object inherit from this class ultimately.
// Just to make some common code common.
class WeatherMapBase
{
	var $notes = array();
	var $hints = array();
	var $inherit_fieldlist;

	function add_note($name,$value)
	{
		debug("Adding note $name='$value' to ".$this->name."\n");
		$this->notes[$name] = $value;
	}

	function get_note($name)
	{
		if(isset($this->notes[$name]))
		{
		//	debug("Found note $name in ".$this->name." with value of ".$this->notes[$name].".\n");
			return($this->notes[$name]);
		}
		else
		{
		//	debug("Looked for note $name in ".$this->name." which doesn't exist.\n");
			return(NULL);
		}
	}

	function add_hint($name,$value)
	{
		debug("Adding hint $name='$value' to ".$this->name."\n");
		$this->hints[$name] = $value;
		# warn("Adding hint $name to ".$this->my_type()."/".$this->name."\n");
	}


	function get_hint($name)
	{
		if(isset($this->hints[$name]))
		{
		//	debug("Found hint $name in ".$this->name." with value of ".$this->hints[$name].".\n");
			return($this->hints[$name]);
		}
		else
		{
		//	debug("Looked for hint $name in ".$this->name." which doesn't exist.\n");
			return(NULL);
		}
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
	var $owner;

	var $configline;
	var $infourl;
	var $overliburl;
	var $overlibwidth, $overlibheight;
	var $overlibcaption;
	var $my_default;
	var $defined_in;
	var $config_override;	# used by the editor to allow text-editing

	function my_type() {  return "ITEM"; }
}

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

	var $plugins = array();
	var $included_files = array();
	var $usage_stats = array();

	function WeatherMap()
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
	
	function Reset()
	{
		$this->next_id = 100;
		foreach (array_keys($this->inherit_fieldlist)as $fld) { $this->$fld=$this->inherit_fieldlist[$fld]; }
		
		$this->min_ds_time = NULL;
		$this->max_ds_time = NULL;

		$this->need_size_precalc=FALSE;

		$this->nodes=array(); // an array of WeatherMapNodes
		$this->links=array(); // an array of WeatherMapLinks
		
		// these are the default defaults
                // by putting them into a normal object, we can use the
                // same code for writing out LINK DEFAULT as any other link.
                debug("Creating ':: DEFAULT ::' DEFAULT LINK\n");
                // these two are used for default settings
                $deflink = new WeatherMapLink;
                $deflink->name=":: DEFAULT ::";
                $deflink->template=":: DEFAULT ::";
                $deflink->Reset($this);
               
                $this->links[':: DEFAULT ::'] = &$deflink;

                debug("Creating ':: DEFAULT ::' DEFAULT NODE\n");
                $defnode = new WeatherMapNode;
                $defnode->name=":: DEFAULT ::";
                $defnode->template=":: DEFAULT ::";
                $defnode->Reset($this);
               
                $this->nodes[':: DEFAULT ::'] = &$defnode;
                
       	$this->node_template_tree = array();
       	$this->link_template_tree = array();
       	
		$this->node_template_tree['DEFAULT'] = array();
		$this->link_template_tree['DEFAULT'] = array();


// ************************************
		// now create the DEFAULT link and node, based on those.
		// these can be modified by the user, but their template (and therefore comparison in WriteConfig) is ':: DEFAULT ::'
		debug("Creating actual DEFAULT NODE from :: DEFAULT ::\n");
                $defnode2 = new WeatherMapNode;
                $defnode2->name = "DEFAULT";
                $defnode2->template = ":: DEFAULT ::";
                $defnode2->Reset($this);
		
                $this->nodes['DEFAULT'] = &$defnode2;

		debug("Creating actual DEFAULT LINK from :: DEFAULT ::\n");
                $deflink2 = new WeatherMapLink;
                $deflink2->name = "DEFAULT";
                $deflink2->template = ":: DEFAULT ::";
                $deflink2->Reset($this);
		
                $this->links['DEFAULT'] = &$deflink2;

// for now, make the old defaultlink and defaultnode work too.
//                $this->defaultlink = $this->links['DEFAULT'];
//                $this->defaultnode = $this->nodes['DEFAULT'];

                assert('is_object($this->nodes[":: DEFAULT ::"])');
                assert('is_object($this->links[":: DEFAULT ::"])');
				assert('is_object($this->nodes["DEFAULT"])');
                assert('is_object($this->links["DEFAULT"])');

// ************************************


		$this->imap=new HTML_ImageMap('weathermap');
		$this->colours=array
			(
				);

		debug ("Adding default map colour set.\n");
		$defaults=array
			(
				'KEYTEXT' => array('bottom' => -2, 'top' => -1, 'red1' => 0, 'green1' => 0, 'blue1' => 0, 'special' => 1),
				'KEYOUTLINE' => array('bottom' => -2, 'top' => -1, 'red1' => 0, 'green1' => 0, 'blue1' => 0, 'special' => 1),
				'KEYBG' => array('bottom' => -2, 'top' => -1, 'red1' => 255, 'green1' => 255, 'blue1' => 255, 'special' => 1),
				'BG' => array('bottom' => -2, 'top' => -1, 'red1' => 255, 'green1' => 255, 'blue1' => 255, 'special' => 1),
				'TITLE' => array('bottom' => -2, 'top' => -1, 'red1' => 0, 'green1' => 0, 'blue1' => 0, 'special' => 1),
				'TIME' => array('bottom' => -2, 'top' => -1, 'red1' => 0, 'green1' => 0, 'blue1' => 0, 'special' => 1)
			);

		foreach ($defaults as $key => $def) { $this->colours['DEFAULT'][$key]=$def; }

		$this->configfile='';
		$this->imagefile='';
		$this->imageuri='';

		$this->fonts=array();

		// Adding these makes the editor's job a little easier, mainly
		for($i=1; $i<=5; $i++)
		{
			$this->fonts[$i]->type="GD builtin";
			$this->fonts[$i]->file='';
			$this->fonts[$i]->size=0;
		}

		$this->LoadPlugins('data', 'lib' . DIRECTORY_SEPARATOR . 'datasources');
		$this->LoadPlugins('pre', 'lib' . DIRECTORY_SEPARATOR . 'pre');
		$this->LoadPlugins('post', 'lib' . DIRECTORY_SEPARATOR . 'post');

		debug("WeatherMap class Reset() complete\n");
	}

	function myimagestring($image, $fontnumber, $x, $y, $string, $colour, $angle=0)
	{
		// if it's supposed to be a special font, and it hasn't been defined, then fall through
		if ($fontnumber > 5 && !isset($this->fonts[$fontnumber]))
		{
			warn ("Using a non-existent special font ($fontnumber) - falling back to internal GD fonts [WMWARN03]\n");
			if($angle != 0) warn("Angled text doesn't work with non-FreeType fonts [WMWARN02]\n");
			$fontnumber=5;
		}

		if (($fontnumber > 0) && ($fontnumber < 6))
		{
			imagestring($image, $fontnumber, $x, $y - imagefontheight($fontnumber), $string, $colour);
			if($angle != 0) warn("Angled text doesn't work with non-FreeType fonts [WMWARN02]\n");
		}
		else
		{
			// look up what font is defined for this slot number
			if ($this->fonts[$fontnumber]->type == 'truetype')
			{
				wimagettftext($image, $this->fonts[$fontnumber]->size, $angle, $x, $y,
					$colour, $this->fonts[$fontnumber]->file, $string);
			}

			if ($this->fonts[$fontnumber]->type == 'gd')
			{
				imagestring($image, $this->fonts[$fontnumber]->gdnumber,
					$x,      $y - imagefontheight($this->fonts[$fontnumber]->gdnumber),
					$string, $colour);
				if($angle != 0) warn("Angled text doesn't work with non-FreeType fonts [WMWARN04]\n");
			}
		}
	}

	function myimagestringsize($fontnumber, $string)
	{
		$linecount = 1;
		
		$lines = split("\n",$string);
		$linecount = sizeof($lines);
		$maxlinelength=0;
		foreach($lines as $line)
		{
			$l = strlen($line);
			if($l > $maxlinelength) $maxlinelength = $l;
		}
				
		if (($fontnumber > 0) && ($fontnumber < 6))
		{ return array(imagefontwidth($fontnumber) * $maxlinelength, $linecount * imagefontheight($fontnumber)); }
		else
		{
			// look up what font is defined for this slot number
			if (!isset($this->fonts[$fontnumber]))
			{
				warn ("Using a non-existent special font ($fontnumber) - falling back to internal GD fonts [WMWARN36]\n");
				$fontnumber=5;
				return array(imagefontwidth($fontnumber) * $maxlinelength, $linecount * imagefontheight($fontnumber));
			}
			else
			{
				if ($this->fonts[$fontnumber]->type == 'truetype')
				{
					$ysize = 0;
					$xsize = 0;
					foreach($lines as $line)
					{
						$bounds=imagettfbbox($this->fonts[$fontnumber]->size, 0, $this->fonts[$fontnumber]->file, $line);
						$cx = $bounds[4] - $bounds[0];
						$cy = $bounds[1] - $bounds[5];
						if($cx > $xsize) $xsize = $cx;
						$ysize += ($cy*1.2);
						# warn("Adding $cy (x was $cx)\n");
					}
					#$bounds=imagettfbbox($this->fonts[$fontnumber]->size, 0, $this->fonts[$fontnumber]->file,
					#	$string);
					# return (array($bounds[4] - $bounds[0], $bounds[1] - $bounds[5]));
					# warn("Size of $string is $xsize x $ysize over $linecount lines\n");
					
					return(array($xsize,$ysize));
				}

				if ($this->fonts[$fontnumber]->type == 'gd')
				{ return array(imagefontwidth($this->fonts[$fontnumber]->gdnumber) * $maxlinelength,
					$linecount * imagefontheight($this->fonts[$fontnumber]->gdnumber)); }
			}
		}
	}

	function ProcessString($input,&$context, $include_notes=TRUE,$multiline=FALSE)
	{
		# debug("ProcessString: input is $input\n");

		assert('is_scalar($input)');

		$context_description = strtolower( $context->my_type() );
		if($context_description != "map") $context_description .= ":" . $context->name; 

		debug("Trace: ProcessString($input, $context_description)\n");

		if($multiline==TRUE)
		{
			$i = $input;
			$input = str_replace("\\n","\n",$i);
			# if($i != $input)  warn("$i into $input\n");
		}

		$output = $input;
		
		# while( preg_match("/(\{[^}]+\})/",$input,$matches) )
		while( preg_match("/(\{(?:node|map|link)[^}]+\})/",$input,$matches) )
		{
			$value = "[UNKNOWN]";
			$format = "";
			$key = $matches[1];
			debug("ProcessString: working on ".$key."\n");

			if ( preg_match("/\{(node|map|link):([^}]+)\}/",$key,$matches) )
			{
				$type = $matches[1];
				$args = $matches[2];
				# debug("ProcessString: type is ".$type.", arguments are ".$args."\n");

				if($type == 'map')
				{
					$the_item = $this;
					if(preg_match("/map:([^:]+):*([^:]*)/",$args,$matches))
					{
						$args = $matches[1];
						$format = $matches[2];
					}
				}

				if(($type == 'link') || ($type == 'node'))
				{
					if(preg_match("/([^:]+):([^:]+):*([^:]*)/",$args,$matches))
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
					warn("ProcessString: $key refers to unknown item (context is $context_description) [WMWARN05]\n");
				}
				else
				{
				#	warn($the_item->name.": ".var_dump($the_item->hints)."\n");
					debug("ProcessString: Found appropriate item: ".get_class($the_item)." ".$the_item->name."\n");

					# warn($the_item->name."/hints: ".var_dump($the_item->hints)."\n");
					# warn($the_item->name."/notes: ".var_dump($the_item->notes)."\n");

					// SET and notes have precedent over internal properties
					// this is my laziness - it saves me having a list of reserved words
					// which are currently used for internal props. You can just 'overwrite' any of them.
					if(isset($the_item->hints[$args]))
					{
						$value = $the_item->hints[$args];
						debug("ProcessString: used hint\n");
					}
					// for some things, we don't want to allow notes to be considered.
					// mainly - TARGET (which can define command-lines), shouldn't be
					// able to get data from uncontrolled sources (i.e. data sources rather than SET in config files).
					elseif($include_notes && isset($the_item->notes[$args]))
					{
						$value = $the_item->notes[$args];
						debug("ProcessString: used note\n");

					}
					elseif(isset($the_item->$args))
					{
						$value = $the_item->$args;
						debug("ProcessString: used internal property\n");
					}
				}
			}

			// format, and sanitise the value string here, before returning it

			if($value===NULL) $value='NULL';
			debug("ProcessString: replacing ".$key." with $value\n");

			# if($format != '') $value = sprintf($format,$value);
			if($format != '')
			{

		#		debug("Formatting with mysprintf($format,$value)\n");
				$value = mysprintf($format,$value);
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

function LoadPlugins( $type="data", $dir="lib/datasources" )
{
	debug("Beginning to load $type plugins from $dir\n");
        
    if ( ! file_exists($dir)) {
        $dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . $dir;
        debug("Relative path didn't exist. Trying $dir\n");
    }
	# $this->datasourceclasses = array();
	$dh=@opendir($dir);

	if(!$dh) {	// try to find it with the script, if the relative path fails
		$srcdir = substr($_SERVER['argv'][0], 0, strrpos($_SERVER['argv'][0], DIRECTORY_SEPARATOR));
		$dh = opendir($srcdir.DIRECTORY_SEPARATOR.$dir);
		if ($dh) $dir = $srcdir.DIRECTORY_SEPARATOR.$dir;
	}

	if ($dh)
	{
		while ($file=readdir($dh))
		{
			$realfile = $dir . DIRECTORY_SEPARATOR . $file;

			if( is_file($realfile) && preg_match( '/\.php$/', $realfile ) )
			{
				debug("Loading $type Plugin class from $file\n");

				include_once( $realfile );
				$class = preg_replace( "/\.php$/", "", $file );
				if($type == 'data')
				{
					$this->datasourceclasses [$class]= $class;
					$this->activedatasourceclasses[$class]=1;
				}
				if($type == 'pre') $this->preprocessclasses [$class]= $class;
				if($type == 'post') $this->postprocessclasses [$class]= $class;

				debug("Loaded $type Plugin class $class from $file\n");
				$this->plugins[$type][$class] = new $class;
				if(! isset($this->plugins[$type][$class]))
				{
					debug("** Failed to create an object for plugin $type/$class\n");
				}
				else
				{
					debug("Instantiated $class.\n");
				}
			}
			else
			{
				debug("Skipping $file\n");
			}
		}
	}
	else
	{
		warn("Couldn't open $type Plugin directory ($dir). Things will probably go wrong. [WMWARN06]\n");
	}
}

function DatasourceInit()
{
	debug("Running Init() for Data Source Plugins...\n");
	foreach ($this->datasourceclasses as $ds_class)
	{
		// make an instance of the class
		$dsplugins[$ds_class] = new $ds_class;
		debug("Running $ds_class"."->Init()\n");
		# $ret = call_user_func(array($ds_class, 'Init'), $this);
		assert('isset($this->plugins["data"][$ds_class])');

		$ret = $this->plugins['data'][$ds_class]->Init($this);

		if(! $ret)
		{
			debug("Removing $ds_class from Data Source list, since Init() failed\n");
			$this->activedatasourceclasses[$ds_class]=0;
			# unset($this->datasourceclasses[$ds_class]);
		}
	}
	debug("Finished Initialising Plugins...\n");	
}

function ProcessTargets()
{
	debug("Preprocessing targets\n");
	
	$allitems = array(&$this->links, &$this->nodes);
	reset($allitems);
	
	debug("Preprocessing targets\n");
	
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
						debug ("ProcessTargets: New Target: $target[4]\n");
						// processstring won't use notes (only hints) for this string
						
						$targetstring = $this->ProcessString($target[4], $myobj, FALSE, FALSE);
						if($target[4] != $targetstring) debug("Targetstring is now $targetstring\n");

						// if the targetstring starts with a -, then we're taking this value OFF the aggregate
						$multiply = 1;
						if(preg_match("/^-(.*)/",$targetstring,$matches))
						{
							$targetstring = $matches[1];
							$multiply = -1 * $multiply;
						}
						
						// if the remaining targetstring starts with a number and a *-, then this is a scale factor
						if(preg_match("/^(\d+\.?\d*)\*(.*)/",$targetstring,$matches))
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
										warn("ProcessTargets: $type $name, target: $targetstring on config line $target[3] of $target[2] was recognised as a valid TARGET by a plugin that is unable to run ($ds_class) [WMWARN07]\n");
									}
								}
							}
						}
						if(! $matched)
						{
							warn("ProcessTargets: $type $name, target: $target[4] on config line $target[3] of $target[2] was not recognised as a valid TARGET [WMWARN08]\n");
						}							
						
						$tindex++;
					}
				}
			}
		}
	}
}

function ReadData()
{
	$this->DatasourceInit();

	debug ("======================================\n");
	debug("ReadData: Updating link data for all links and nodes\n");

	// we skip readdata completely in sizedebug mode
	if ($this->sizedebug == 0)
	{
		$this->ProcessTargets();
				
		debug ("======================================\n");
		debug("Starting prefetch\n");
		foreach ($this->datasourceclasses as $ds_class)
		{
			$this->plugins['data'][$ds_class]->Prefetch();
		}
		
		debug ("======================================\n");
		debug("Starting main collection loop\n");
		
		$allitems = array(&$this->links, &$this->nodes);
		reset($allitems);
		
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

				$total_in=0;
				$total_out=0;
				$name=$myobj->name;
				debug ("\n");
				debug ("ReadData for $type $name: \n");

				if( ($type=='LINK' && isset($myobj->a)) || ($type=='NODE' && !is_null($myobj->x) ) )
				{
					if (count($myobj->targets)>0)
					{
						$tindex = 0;
						foreach ($myobj->targets as $target)
						{
							debug ("ReadData: New Target: $target[4]\n");
	#						debug ( var_dump($target));
	
							$targetstring = $target[0];
							$multiply = $target[1];
							
	#						exit();
							
							$in = 0;
							$out = 0;
							$datatime = 0;
							if ($target[4] != '')
							{
								// processstring won't use notes (only hints) for this string
								
								$targetstring = $this->ProcessString($target[0], $myobj, FALSE, FALSE);
								if($target[0] != $targetstring) debug("Targetstring is now $targetstring\n");							
								if($multiply != 1) debug("Will multiply result by $multiply\n");
																
								if($target[0] != "")
								{
									$matched_by = $target[5];
									list($in,$out,$datatime) =  $this->plugins['data'][ $target[5] ]->ReadData($targetstring, $this, $myobj);
								}
	
								if (($in === NULL) && ($out === NULL))
								{
									$in=0;
									$out=0;
									warn
										("ReadData: $type $name, target: $targetstring on config line $target[3] of $target[2] had no valid data, according to $matched_by\n");
								}
								else
								{
									if($in === NULL) $in = 0;
									if($out === NULL) $out = 0;
								}
	
								if($multiply != 1) {  
									debug("Pre-multiply: $in $out\n"); 
								
									$in = $multiply*$in;
									$out = $multiply*$out;
								
									debug("Post-multiply: $in $out\n"); 
								}
								
								$total_in=$total_in + $in;
								$total_out=$total_out + $out;
								debug("Aggregate so far: $total_in $total_out\n");
								# keep a track of the range of dates for data sources (mainly for MRTG/textfile based DS)
								if($datatime > 0)
								{
									if($this->max_data_time==NULL || $datatime > $this->max_data_time) $this->max_data_time = $datatime;
									if($this->min_data_time==NULL || $datatime < $this->min_data_time) $this->min_data_time = $datatime;
									
									debug("DataTime MINMAX: ".$this->min_data_time." -> ".$this->max_data_time."\n");
								}
								
							}
							$tindex++;
						}
	
						debug ("ReadData complete for $type $name: $total_in $total_out\n");
					}
					else
					{
						debug("ReadData: No targets for $type $name\n");
					}
				}
				else
				{
					debug("ReadData: Skipping $type $name that looks like a template\n.");
				}

				# $this->links[$name]->bandwidth_in=$total_in;
				# $this->links[$name]->bandwidth_out=$total_out;
				$myobj->bandwidth_in = $total_in;
				$myobj->bandwidth_out = $total_out;

				if($type == 'LINK' && $myobj->duplex=='half')
				{
					// in a half duplex link, in and out share a common bandwidth pool, so percentages need to include both
					debug("Calculating percentage using half-duplex\n");
					$myobj->outpercent = (($total_in + $total_out) / ($myobj->max_bandwidth_out)) * 100;
					$myobj->inpercent = (($total_out + $total_in) / ($myobj->max_bandwidth_in)) * 100;
					if($myobj->max_bandwidth_out != $myobj->max_bandwidth_in)
					{
						warn("ReadData: $type $name: You're using asymmetric bandwidth AND half-duplex in the same link. That makes no sense. [WMWARN44]\n");
					}
				}
				else
				{
					$myobj->outpercent = (($total_out) / ($myobj->max_bandwidth_out)) * 100;
					$myobj->inpercent = (($total_in) / ($myobj->max_bandwidth_in)) * 100;
				}

				# print $myobj->name."=>".$myobj->inpercent."%/".$myobj->outpercent."\n";

                                $warn_in = true;
                                $warn_out = true;
                                if($type=='NODE' && $myobj->scalevar =='in') $warn_out = false;
                                if($type=='NODE' && $myobj->scalevar =='out') $warn_in = false;

				if($myobj->scaletype == 'percent')
				{
					list($incol,$inscalekey,$inscaletag) = $this->NewColourFromPercent($myobj->inpercent,$myobj->usescale,$myobj->name, TRUE, $warn_in);
					list($outcol,$outscalekey, $outscaletag) = $this->NewColourFromPercent($myobj->outpercent,$myobj->usescale,$myobj->name, TRUE, $warn_out);
				}
				else
				{
					// use absolute values, if that's what is requested
					list($incol,$inscalekey,$inscaletag) = $this->NewColourFromPercent($myobj->bandwidth_in,$myobj->usescale,$myobj->name, FALSE, $warn_in);
					list($outcol,$outscalekey, $outscaletag) = $this->NewColourFromPercent($myobj->bandwidth_out,$myobj->usescale,$myobj->name, FALSE, $warn_out);
				}
				
				$myobj->add_note("inscalekey",$inscalekey);
				$myobj->add_note("outscalekey",$outscalekey);
				
				$myobj->add_note("inscaletag",$inscaletag);
				$myobj->add_note("outscaletag",$outscaletag);
				
				$myobj->add_note("inscalecolor",$incol->as_html());
				$myobj->add_note("outscalecolor",$outcol->as_html());
				
				$myobj->colours[IN] = $incol;
				$myobj->colours[OUT] = $outcol;

				### warn("TAGS (setting) |$inscaletag| |$outscaletag| \n");

				debug ("ReadData: Setting $total_in,$total_out\n");
				unset($myobj);
			}
		}
		debug ("ReadData Completed.\n");
		debug("------------------------------\n");
	}
}

// nodename is a vestigal parameter, from the days when nodes were just big labels
function DrawLabelRotated($im, $x, $y, $angle, $text, $font, $padding, $linkname, $textcolour, $bgcolour, $outlinecolour, &$map, $direction)
{
	list($strwidth, $strheight)=$this->myimagestringsize($font, $text);

	if(abs($angle)>90)  $angle -= 180;
	if($angle < -180) $angle +=360;

	$rangle = -deg2rad($angle);

	$extra=3;

	$x1= $x - ($strwidth / 2) - $padding - $extra;
	$x2= $x + ($strwidth / 2) + $padding + $extra;
	$y1= $y - ($strheight / 2) - $padding - $extra;
	$y2= $y + ($strheight / 2) + $padding + $extra;

	// a box. the last point is the start point for the text.
	$points = array($x1,$y1, $x1,$y2, $x2,$y2, $x2,$y1,   $x-$strwidth/2, $y+$strheight/2 + 1);
	$npoints = count($points)/2;

	RotateAboutPoint($points, $x,$y, $rangle);

	if ($bgcolour != array
		(
			-1,
			-1,
			-1
		))
	{
		$bgcol=myimagecolorallocate($im, $bgcolour[0], $bgcolour[1], $bgcolour[2]);
		# imagefilledrectangle($im, $x1, $y1, $x2, $y2, $bgcol);
		wimagefilledpolygon($im,$points,4,$bgcol);
	}

	if ($outlinecolour != array
		(
			-1,
			-1,
			-1
		))
	{
		$outlinecol=myimagecolorallocate($im, $outlinecolour[0], $outlinecolour[1], $outlinecolour[2]);
		# imagerectangle($im, $x1, $y1, $x2, $y2, $outlinecol);
		wimagepolygon($im,$points,4,$outlinecol);
	}

	$textcol=myimagecolorallocate($im, $textcolour[0], $textcolour[1], $textcolour[2]);
	$this->myimagestring($im, $font, $points[8], $points[9], $text, $textcol,$angle);

	$areaname = "LINK:L".$map->links[$linkname]->id.':'.($direction+2);
	
	// the rectangle is about half the size in the HTML, and easier to optimise/detect in the browser
	if($angle==0)
	{
		$map->imap->addArea("Rectangle", $areaname, '', array($x1, $y1, $x2, $y2));
		debug ("Adding Rectangle imagemap for $areaname\n");
	}
	else
	{
		$map->imap->addArea("Polygon", $areaname, '', $points);
		debug ("Adding Poly imagemap for $areaname\n");
	}

}

function ColourFromPercent($image, $percent,$scalename="DEFAULT",$name="")
{
	$col = NULL;
	$tag = '';
	
	$nowarn_clipping = intval($this->get_hint("nowarn_clipping"));
	$nowarn_scalemisses = intval($this->get_hint("nowarn_scalemisses"));

	$bt = debug_backtrace();
	$function = (isset($bt[1]['function']) ? $bt[1]['function'] : '');
	print "$function calls ColourFromPercent\n";

	exit();

	if(isset($this->colours[$scalename]))
	{
		$colours=$this->colours[$scalename];

		if ($percent > 100)
		{
			if($nowarn_clipping==0) warn ("ColourFromPercent: Clipped $name $percent% to 100% [WMWARN33]\n");
			$percent=100;
		}

		foreach ($colours as $key => $colour)
		{
			if (($percent >= $colour['bottom']) and ($percent <= $colour['top']))
			{
				if(isset($colour['tag'])) $tag = $colour['tag'];

				// we get called early now, so might not need to actually allocate a colour
				if(isset($image))
				{
					if (isset($colour['red2']))
					{
						if($colour["bottom"] == $colour["top"])
						{
							$ratio = 0;
						}
						else
						{
							$ratio=($percent - $colour["bottom"]) / ($colour["top"] - $colour["bottom"]);
						}

						$r=$colour["red1"] + ($colour["red2"] - $colour["red1"]) * $ratio;
						$g=$colour["green1"] + ($colour["green2"] - $colour["green1"]) * $ratio;
						$b=$colour["blue1"] + ($colour["blue2"] - $colour["blue1"]) * $ratio;

						$col = myimagecolorallocate($image, $r, $g, $b);
					}
					else {
						$r=$colour["red1"];
						$g=$colour["green1"];
						$b=$colour["blue1"];

						$col = myimagecolorallocate($image, $r, $g, $b);
						# $col = $colour['gdref1'];
					}
					debug("CFPC $name $tag $key $r $g $b\n");
				}

				### warn(">>TAGS CFPC $tag\n");

				return(array($col,$key,$tag));
			}
		}
	}
	else
	{
		if($scalename != 'none')
		{
			warn("ColourFromPercent: Attempted to use non-existent scale: $scalename for $name [WMWARN09]\n");
		}
		else
		{
			return array($this->white,'','');
		}
	}

	// you'll only get grey for a COMPLETELY quiet link if there's no 0 in the SCALE lines
	if ($percent == 0) { return array($this->grey,'',''); }

	// and you'll only get white for a link with no colour assigned
	if($nowarn_scalemisses==0) warn("ColourFromPercent: Scale $scalename doesn't cover $percent% for $name [WMWARN29]\n");
	return array($this->white,'','');
}

function NewColourFromPercent($value,$scalename="DEFAULT",$name="",$is_percent=TRUE, $scale_warning=TRUE)
{
	$col = new Colour(0,0,0);
	$tag = '';
	$matchsize = NULL;

	$nowarn_clipping = intval($this->get_hint("nowarn_clipping"));
	$nowarn_scalemisses = (!$scale_warning) || intval($this->get_hint("nowarn_scalemisses"));
	
	if(isset($this->colours[$scalename]))
	{
		$colours=$this->colours[$scalename];

		if ($is_percent && $value > 100)
		{
			if($nowarn_clipping==0) warn ("NewColourFromPercent: Clipped $value% to 100% for item $name [WMWARN33]\n");
			$value = 100;
		}
		
		if ($is_percent && $value < 0)
		{
			if($nowarn_clipping==0) warn ("NewColourFromPercent: Clipped $value% to 0% for item $name [WMWARN34]\n");
			$value = 0;
		}

		foreach ($colours as $key => $colour)
		{
			if ( (!isset($colour['special']) || $colour['special'] == 0) and ($value >= $colour['bottom']) and ($value <= $colour['top']))
			{
				$range = $colour['top'] - $colour['bottom'];
				if (isset($colour['red2']))
				{
					if($colour["bottom"] == $colour["top"])
					{
						$ratio = 0;
					}
					else
					{
						$ratio=($value - $colour["bottom"]) / ($colour["top"] - $colour["bottom"]);
					}

					$r=$colour["red1"] + ($colour["red2"] - $colour["red1"]) * $ratio;
					$g=$colour["green1"] + ($colour["green2"] - $colour["green1"]) * $ratio;
					$b=$colour["blue1"] + ($colour["blue2"] - $colour["blue1"]) * $ratio;
				}
				else {
					$r=$colour["red1"];
					$g=$colour["green1"];
					$b=$colour["blue1"];

					# $col = new Colour($r, $g, $b);
					# $col = $colour['gdref1'];
				}
				
				// change in behaviour - with multiple matching ranges for a value, the smallest range wins
				if( is_null($matchsize) || ($range < $matchsize) ) 
				{
					$col = new Colour($r, $g, $b);
					$matchsize = $range;
				}
				
				if(isset($colour['tag'])) $tag = $colour['tag'];
				#### warn(">>NCFPC TAGS $tag\n");
				debug("NCFPC $name $scalename $value '$tag' $key $r $g $b\n");

				return(array($col,$key,$tag));
			}
		}
	}
	else
	{
		if($scalename != 'none')
		{
			warn("ColourFromPercent: Attempted to use non-existent scale: $scalename for item $name [WMWARN09]\n");
		}
		else
		{
			return array(new Colour(255,255,255),'','');
		}
	}

	// shouldn't really get down to here if there's a complete SCALE

	// you'll only get grey for a COMPLETELY quiet link if there's no 0 in the SCALE lines
	if ($value == 0) { return array(new Colour(192,192,192),'',''); }

	if($nowarn_scalemisses==0) warn("NewColourFromPercent: Scale $scalename doesn't include a line for $value".($is_percent ? "%" : "")." while drawing item $name [WMWARN29]\n");

	// and you'll only get white for a link with no colour assigned
	return array(new Colour(255,255,255),'','');
}


function coloursort($a, $b)
{
	if ($a['bottom'] == $b['bottom'])
	{
		if($a['top'] < $b['top']) { return -1; };
		if($a['top'] > $b['top']) { return 1; };
		return 0;
	}

	if ($a['bottom'] < $b['bottom']) { return -1; }

	return 1;
}

function FindScaleExtent($scalename="DEFAULT")
{
	$max = -999999999999999999999;
	$min = - $max;
	
	if(isset($this->colours[$scalename]))
	{
		$colours=$this->colours[$scalename];

		foreach ($colours as $key => $colour)
		{
			if(! $colour['special'])
			{
				$min = min($colour['bottom'], $min);
				$max = max($colour['top'],  $max);
			}
		}
	}
	else
	{
		warn("FindScaleExtent: non-existent SCALE $scalename [WMWARN43]\n");
	}
	return array($min, $max);
}

function DrawLegend_Horizontal($im,$scalename="DEFAULT",$width=400)
{
	$title=$this->keytext[$scalename];

	$colours=$this->colours[$scalename];
	$nscales=$this->numscales[$scalename];

	debug("Drawing $nscales colours into SCALE\n");

	$font=$this->keyfont;

	# $x=$this->keyx[$scalename];
	# $y=$this->keyy[$scalename];
	$x = 0;
	$y = 0;

	# $width = 400;
	$scalefactor = $width/100;

	list($tilewidth, $tileheight)=$this->myimagestringsize($font, "100%");
	$box_left = $x;
	# $box_left = 0;
	$scale_left = $box_left + 4 + $scalefactor/2;
	$box_right = $scale_left + $width + $tilewidth + 4 + $scalefactor/2;
	$scale_right = $scale_left + $width;

	$box_top = $y;
	# $box_top = 0;
	$scale_top = $box_top + $tileheight + 6;
	$scale_bottom = $scale_top + $tileheight * 1.5;
	$box_bottom = $scale_bottom + $tileheight * 2 + 6;

	$scale_im = imagecreatetruecolor($box_right+1, $box_bottom+1);
	$scale_ref = 'gdref_legend_'.$scalename;
	$this->AllocateScaleColours($scale_im,$scale_ref);

	wimagefilledrectangle($scale_im, $box_left, $box_top, $box_right, $box_bottom,
		$this->colours['DEFAULT']['KEYBG'][$scale_ref]);
	wimagerectangle($scale_im, $box_left, $box_top, $box_right, $box_bottom,
		$this->colours['DEFAULT']['KEYOUTLINE'][$scale_ref]);

	$this->myimagestring($scale_im, $font, $scale_left, $scale_bottom + $tileheight * 2 + 2 , $title,
		$this->colours['DEFAULT']['KEYTEXT'][$scale_ref]);

	for($p=0;$p<=100;$p++)
	{
		$dx = $p*$scalefactor;

		if( ($p % 25) == 0)
		{
			imageline($scale_im, $scale_left + $dx, $scale_top - $tileheight,
				$scale_left + $dx, $scale_bottom + $tileheight,
				$this->colours['DEFAULT']['KEYTEXT'][$scale_ref]);
			$labelstring=sprintf("%d%%", $p);
			$this->myimagestring($scale_im, $font, $scale_left + $dx + 2, $scale_top - 2, $labelstring,
				$this->colours['DEFAULT']['KEYTEXT'][$scale_ref]);
		}

		list($col,$junk) = $this->NewColourFromPercent($p,$scalename);
		if($col->is_real())
		{
			$cc = $col->gdallocate($scale_im);
			wimagefilledrectangle($scale_im, $scale_left + $dx - $scalefactor/2, $scale_top,
				$scale_left + $dx + $scalefactor/2, $scale_bottom,
				$cc);
		}
	}

	imagecopy($im,$scale_im,$this->keyx[$scalename],$this->keyy[$scalename],0,0,imagesx($scale_im),imagesy($scale_im));
	$this->keyimage[$scalename] = $scale_im;

    $rx = $this->keyx[$scalename];
    $ry = $this->keyy[$scalename];

	$this->imap->addArea("Rectangle", "LEGEND:$scalename", '',
		array($rx+$box_left, $ry+$box_top, $rx+$box_right, $ry+$box_bottom));
}

function DrawLegend_Vertical($im,$scalename="DEFAULT",$height=400,$inverted=false)
{
	$title=$this->keytext[$scalename];

	$colours=$this->colours[$scalename];
	$nscales=$this->numscales[$scalename];

	debug("Drawing $nscales colours into SCALE\n");

	$font=$this->keyfont;

	$x=$this->keyx[$scalename];
	$y=$this->keyy[$scalename];

	# $height = 400;
	$scalefactor = $height/100;

	list($tilewidth, $tileheight)=$this->myimagestringsize($font, "100%");

	# $box_left = $x;
	# $box_top = $y;
	$box_left = 0;
	$box_top = 0;

	$scale_left = $box_left+$scalefactor*2 +4 ;
	$scale_right = $scale_left + $tileheight*2;
	$box_right = $scale_right + $tilewidth + $scalefactor*2 + 4;

	list($titlewidth,$titleheight) = $this->myimagestringsize($font,$title);
	if( ($box_left + $titlewidth + $scalefactor*3) > $box_right)
	{
		$box_right = $box_left + $scalefactor*4 + $titlewidth;
	}

	$scale_top = $box_top + 4 + $scalefactor + $tileheight*2;
	$scale_bottom = $scale_top + $height;
	$box_bottom = $scale_bottom + $scalefactor + $tileheight/2 + 4;

	$scale_im = imagecreatetruecolor($box_right+1, $box_bottom+1);
	$scale_ref = 'gdref_legend_'.$scalename;
	$this->AllocateScaleColours($scale_im,$scale_ref);

	wimagefilledrectangle($scale_im, $box_left, $box_top, $box_right, $box_bottom,
		$this->colours['DEFAULT']['KEYBG']['gdref1']);
	wimagerectangle($scale_im, $box_left, $box_top, $box_right, $box_bottom,
		$this->colours['DEFAULT']['KEYOUTLINE']['gdref1']);

	$this->myimagestring($scale_im, $font, $scale_left-$scalefactor, $scale_top - $tileheight , $title,
		$this->colours['DEFAULT']['KEYTEXT']['gdref1']);

	$updown = 1;
	if($inverted) $updown = -1;
		

	for($p=0;$p<=100;$p++)
	{
		if($inverted)
		{
			$dy = (100-$p) * $scalefactor;
		}
		else
		{
			$dy = $p*$scalefactor;
		}
	
		if( ($p % 25) == 0)
		{
			imageline($scale_im, $scale_left - $scalefactor, $scale_top + $dy,
				$scale_right + $scalefactor, $scale_top + $dy,
				$this->colours['DEFAULT']['KEYTEXT'][$scale_ref]);
			$labelstring=sprintf("%d%%", $p);
			$this->myimagestring($scale_im, $font, $scale_right + $scalefactor*2 , $scale_top + $dy + $tileheight/2,
				$labelstring,  $this->colours['DEFAULT']['KEYTEXT'][$scale_ref]);
		}

		list($col,$junk) = $this->NewColourFromPercent($p,$scalename);
		if( $col->is_real())
		{
			$cc = $col->gdallocate($scale_im);
			wimagefilledrectangle($scale_im, $scale_left, $scale_top + $dy - $scalefactor/2,
				$scale_right, $scale_top + $dy + $scalefactor/2,
				$cc);
		}
	}

	imagecopy($im,$scale_im,$this->keyx[$scalename],$this->keyy[$scalename],0,0,imagesx($scale_im),imagesy($scale_im));
	$this->keyimage[$scalename] = $scale_im;

	$rx = $this->keyx[$scalename];
	$ry = $this->keyy[$scalename];
	$this->imap->addArea("Rectangle", "LEGEND:$scalename", '',
		array($rx+$box_left, $ry+$box_top, $rx+$box_right, $ry+$box_bottom));
}

function DrawLegend_Classic($im,$scalename="DEFAULT",$use_tags=FALSE)
{
	$title=$this->keytext[$scalename];

	$colours=$this->colours[$scalename];
	usort($colours, array("Weathermap", "coloursort"));
	
	$nscales=$this->numscales[$scalename];

	debug("Drawing $nscales colours into SCALE\n");

	$hide_zero = intval($this->get_hint("key_hidezero_".$scalename));
	$hide_percent = intval($this->get_hint("key_hidepercent_".$scalename));

	// did we actually hide anything?
	$hid_zero = FALSE;
	if( ($hide_zero == 1) && isset($colours['0_0']) )
	{
		$nscales--;
		$hid_zero = TRUE;
	}

	$font=$this->keyfont;

	$x=$this->keyx[$scalename];
	$y=$this->keyy[$scalename];

	list($tilewidth, $tileheight)=$this->myimagestringsize($font, "MMMM");
	$tileheight=$tileheight * 1.1;
	$tilespacing=$tileheight + 2;

	if (($this->keyx[$scalename] >= 0) && ($this->keyy[$scalename] >= 0))
	{

		# $minwidth = imagefontwidth($font) * strlen('XX 100%-100%')+10;
		# $boxwidth = imagefontwidth($font) * strlen($title) + 10;
		list($minwidth, $junk)=$this->myimagestringsize($font, 'MMMM 100%-100%');
		list($minminwidth, $junk)=$this->myimagestringsize($font, 'MMMM ');
		list($boxwidth, $junk)=$this->myimagestringsize($font, $title);
		
		if($use_tags)
		{
			$max_tag = 0;
			foreach ($colours as $colour)
			{
				if ( isset($colour['tag']) )
				{
					list($w, $junk)=$this->myimagestringsize($font, $colour['tag']);
					# print $colour['tag']." $w \n";
					if($w > $max_tag) $max_tag = $w;
				}
			}
			
			// now we can tweak the widths, appropriately to allow for the tag strings
			# print "$max_tag > $minwidth?\n";
			if( ($max_tag + $minminwidth) > $minwidth) $minwidth = $minminwidth + $max_tag;
			# print "minwidth is now $minwidth\n";
		}

		$minwidth+=10;
		$boxwidth+=10;

		if ($boxwidth < $minwidth) { $boxwidth=$minwidth; }

		$boxheight=$tilespacing * ($nscales + 1) + 10;

		$boxx=$x; $boxy=$y;
		$boxx=0;
		$boxy=0;

		// allow for X11-style negative positioning
		if ($boxx < 0) { $boxx+=$this->width; }

		if ($boxy < 0) { $boxy+=$this->height; }

		$scale_im = imagecreatetruecolor($boxwidth+1, $boxheight+1);
		$scale_ref = 'gdref_legend_'.$scalename;
		$this->AllocateScaleColours($scale_im,$scale_ref);

		wimagefilledrectangle($scale_im, $boxx, $boxy, $boxx + $boxwidth, $boxy + $boxheight,
			$this->colours['DEFAULT']['KEYBG'][$scale_ref]);
		wimagerectangle($scale_im, $boxx, $boxy, $boxx + $boxwidth, $boxy + $boxheight,
			$this->colours['DEFAULT']['KEYOUTLINE'][$scale_ref]);
		$this->myimagestring($scale_im, $font, $boxx + 4, $boxy + 4 + $tileheight, $title,
			$this->colours['DEFAULT']['KEYTEXT'][$scale_ref]);

		$i=1;

		foreach ($colours as $colour)
		{
			if (!isset($colour['special']) || $colour['special'] == 0)
			// if ( 1==1 || $colour['bottom'] >= 0)
			{
				// pick a value in the middle...
				$value = ($colour['bottom'] + $colour['top']) / 2;
				debug(sprintf("%f-%f (%f)  %d %d %d\n", $colour['bottom'], $colour['top'], $value, $colour['red1'], $colour['green1'], $colour['blue1']));
				
				#  debug("$i: drawing\n");
				if( ($hide_zero == 0) || $colour['key'] != '0_0')
				{
					$y=$boxy + $tilespacing * $i + 8;
					$x=$boxx + 6;

					$fudgefactor = 0;
					if( $hid_zero && $colour['bottom']==0 )
					{
						// calculate a small offset that can be added, which will hide the zero-value in a
						// gradient, but not make the scale incorrect. A quarter of a pixel should do it.
						$fudgefactor = ($colour['top'] - $colour['bottom'])/($tilewidth*4);
						# warn("FUDGING $fudgefactor\n");
					}

					// if it's a gradient, red2 is defined, and we need to sweep the values
					if (isset($colour['red2']))
					{
						for ($n=0; $n <= $tilewidth; $n++)
						{
							$value
								=  $fudgefactor + $colour['bottom'] + ($n / $tilewidth) * ($colour['top'] - $colour['bottom']);
							list($ccol,$junk) = $this->NewColourFromPercent($value, $scalename, "", FALSE);
							$col = $ccol->gdallocate($scale_im);
							wimagefilledrectangle($scale_im, $x + $n, $y, $x + $n, $y + $tileheight,
								$col);
						}
					}
					else
					{
						// pick a value in the middle...
						//$value = ($colour['bottom'] + $colour['top']) / 2;
						list($ccol,$junk) = $this->NewColourFromPercent($value, $scalename, "", FALSE);
						$col = $ccol->gdallocate($scale_im);
						wimagefilledrectangle($scale_im, $x, $y, $x + $tilewidth, $y + $tileheight,
							$col);
					}

					if($use_tags)
					{
						$labelstring = "";
						if(isset($colour['tag'])) $labelstring = $colour['tag'];
					}
					else
					{
						$labelstring=sprintf("%s-%s", $colour['bottom'], $colour['top']);
						if($hide_percent==0) { $labelstring.="%"; }
					}
										
					$this->myimagestring($scale_im, $font, $x + 4 + $tilewidth, $y + $tileheight, $labelstring,
						$this->colours['DEFAULT']['KEYTEXT'][$scale_ref]);
					$i++;
				}
				imagecopy($im,$scale_im,$this->keyx[$scalename],$this->keyy[$scalename],0,0,imagesx($scale_im),imagesy($scale_im));
				$this->keyimage[$scalename] = $scale_im;

			}
		}

		$this->imap->addArea("Rectangle", "LEGEND:$scalename", '',
			array($this->keyx[$scalename], $this->keyy[$scalename], $this->keyx[$scalename] + $boxwidth, $this->keyy[$scalename] + $boxheight));
		# $this->imap->setProp("href","#","LEGEND");
		# $this->imap->setProp("extrahtml","onclick=\"position_legend();\"","LEGEND");

	}
}

function DrawTimestamp($im, $font, $colour, $which="")
{
	// add a timestamp to the corner, so we can tell if it's all being updated
	# $datestring = "Created: ".date("M d Y H:i:s",time());
	# $this->datestamp=strftime($this->stamptext, time());

	switch($which)
	{
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
		
	list($boxwidth, $boxheight)=$this->myimagestringsize($font, $stamp);

	$x=$this->width - $boxwidth;
	$y=$boxheight;

	if (($pos_x != 0) && ($pos_y != 0))
	{
		$x = $pos_x;
		$y = $pos_y;
	}
		
	$this->myimagestring($im, $font, $x, $y, $stamp, $colour);
	$this->imap->addArea("Rectangle", $which."TIMESTAMP", '', array($x, $y, $x + $boxwidth, $y - $boxheight));
}

function DrawTitle($im, $font, $colour)
{
	$string = $this->ProcessString($this->title,$this);
	
	if($this->get_hint('screenshot_mode')==1)  $string= screenshotify($string);

	list($boxwidth, $boxheight)=$this->myimagestringsize($font, $string);

	$x=10;
	$y=$this->titley - $boxheight;

	if (($this->titlex >= 0) && ($this->titley >= 0))
	{
		$x=$this->titlex;
		$y=$this->titley;
	}

	$this->myimagestring($im, $font, $x, $y, $string, $colour);

	$this->imap->addArea("Rectangle", "TITLE", '', array($x, $y, $x + $boxwidth, $y - $boxheight));
}

function ReadConfigNG($input, $is_include=FALSE, $initial_context="GLOBAL")
{
	$valid_commands = array(
			"GLOBAL.set", "LINK.set", "NODE.set",
			"GLOBAL.#","LINK.#","NODE.#",
			"GLOBAL.include", "NODE.include", "LINK.include",
			
			"GLOBAL.width", "GLOBAL.height", "GLOBAL.background",
			"GLOBAL.scale", "GLOBAL.title", "GLOBAL.titlepos",
			"GLOBAL.fontdefine",
			"GLOBAL.keystyle", "GLOBAL.titlecolor", "GLOBAL.timecolor",
			"GLOBAL.titlefont", "GLOBAL.timefont", "GLOBAL.htmloutputfile",
			"GLOBAL.htmlstyle", "GLOBAL.imageoutputfile", "GLOBAL.keyfont",
			"GLOBAL.keytextcolor", "GLOBAL.keyoutlinecolor", "GLOBAL.keybgcolor",
			"GLOBAL.bgcolor",
			
			"SCALE.keypos", "SCALE.keystyle", "SCALE.scale",
			
			"LINK.width", "LINK.link", "LINK.nodes",
			"LINK.target", "LINK.usescale", "LINK.infourl",
			"LINK.linkstyle", "LINK.overlibcaption", "LINK.inoverlibcaption", "LINK.outoverlibcaption", 
			"LINK.inoverlibgraph", "LINK.outoverlibgraph",
			"LINK.overlibgraph", "LINK.overlibwidth", "LINK.overlibheight",
			"LINK.bwlabel", "LINK.via", "LINK.zorder", "LINK.outlinecolor",
			"LINK.notes", "LINK.innotes", "LINK.outnotes","LINK.ininfourl", "LINK.outinfourl",
			"LINK.bwstyle", "LINK.template", "LINK.splitpos", "LINK.bwlabelpos", "LINK.incomment", "LINK.outcomment",
			"LINK.viastyle", "LINK.bandwidth", "LINK.inbwformat", "LINK.outbwformat",
			"LINK.commentstyle", "LINK.commentfont", "LINK.commentfontcolor", "LINK.bwfont",
		
			"NODE.icon", "NODE.target", "NODE.position", "NODE.infourl", "NODE.overlibgraph",
			"NODE.zorder", "NODE.label", "NODE.template", "NODE.labelbgcolor",
			"NODE.maxvalue",
			"NODE.labeloutlinecolor", "NODE.aiconoutlinecolor", "NODE.aiconfillcolor", "NODE.usescale",
			"NODE.labelfontcolor", "NODE.labelfont", "NODE.labelangle", "NODE.labelfontshadowcolor",
			"NODE.node", "NODE.overlibwidth", "NODE.overlibheight", "NODE.labeloffset"
		);
	
	
	if( (strchr($input,"\n")!=FALSE) || (strchr($input,"\r")!=FALSE ) )
	{
		 debug("ReadConfig Detected that this is a config fragment.\n");
			 // strip out any Windows line-endings that have gotten in here
			 $input=str_replace("\r", "", $input);
			 $lines = split("/n",$input);
			 $filename = "{text insert}";
	}
	else
	{
		debug("ReadConfig Detected that this is a config filename.\n");
		 $filename = $input;
		
		$fd=fopen($filename, "r");
		 
		if ($fd)
		{
				while (!feof($fd))
				{
					$buffer=fgets($fd, 4096);
					// strip out any Windows line-endings that have gotten in here
					$buffer=str_replace("\r", "", $buffer);
					$lines[] = $buffer;
				}
				fclose($fd);
		}
	}
		
	$linecount = 0;
	$context = $initial_context;

	foreach($lines as $buffer)
	{
		$linematched=0;
		$linecount++;
		$nextcontext = "";
		$key = "";
	
		$buffer = trim($buffer);
		// alternative for use later where quoted strings are more useful
		$args = ParseString($buffer);
		
		if(sizeof($args) > 0)
		{		
			$linematched++;		
			$cmd = strtolower(array_shift($args));
						
			if($cmd == 'include')
			{
				$this->ReadConfigNG($args[0],TRUE, $context);
			}
			elseif($cmd == 'node')
			{
				$context = "NODE.".$args[0];
			}
			elseif($cmd == 'link')
			{
				$context = "LINK.".$args[0];
				$vcount = 0;	# reset the via-number counter, it's a new link
			}
			elseif($cmd == 'scale' || $cmd == 'keystyle' || $cmd == 'keypos')
			{
				if( preg_match("/^[0-9\-]+/i",$args[0]) )
				{
					$scalename = "DEFAULT";
				}
				else
				{
					$scalename = array_shift($args);
				}
				if($cmd=="scale") $key = $args[0]."_".$args[1];
				$nextcontext = $context;
				$context = "SCALE.".$scalename;
			}
			
			array_unshift($args,$cmd);
			
			if($context == 'GLOBAL')
	 		{ 
				$ctype='GLOBAL'; 
			}
			else
			{
				list($ctype,$junk) = split("\\.", $context, 2);
			}
			
			$lookup = $ctype.".".$cmd;
			
			// Some things (scales, mainly) might define special keys
			// the key should be unique for that object
			// most (all?) things for a link or node are one-offs. 
			if($key == "") $key = $cmd; 
			if($cmd == 'set' || $cmd == 'fontdefine') $key .= "_".$args[1];
			if($cmd == 'via')
			{
				$key .= "_".$vcount;
				$vcount++;
			}
			
			# everything else
			if( substr($cmd, 0, 1) != '#')
			{
				if(! in_array($lookup, $valid_commands))
				{
					print "INVALID COMMAND: $lookup\n";
				}
				
				if(isset($config[$context][$key]))
				{
					print "REDEFINED $key in $context\n";
				}
				else
				{
					array_unshift($args,$linecount);
					array_unshift($args,$filename);
					$this->config[$context][$key] = $args;
				}
			}
			print "$context\\$key  $filename:$linecount ".join("|",$args)."\n";
			
			if($nextcontext != "") $context = $nextcontext;
		}
		
		if ($linematched == 0 && trim($buffer) != '') { warn ("Unrecognised config on line $linecount: $buffer\n"); }
		
	}
	
	if(! $is_include)
	{
		print_r($this->config);
	
		foreach ($this->config as $context=>$values)
		{
			print "> $context\n";
		}
	}
	
	
}

function ReadConfigNNG($input, $is_include=FALSE, $initial_context="GLOBAL")
{
	global $valid_commands;
		
	if( (strchr($input,"\n")!=FALSE) || (strchr($input,"\r")!=FALSE ) )
	{
		 debug("ReadConfig Detected that this is a config fragment.\n");
			 // strip out any Windows line-endings that have gotten in here
			 $input=str_replace("\r", "", $input);
			 $lines = split("/n",$input);
			 $filename = "{text insert}";
	}
	else
	{
		debug("ReadConfig Detected that this is a config filename.\n");
		 $filename = $input;
		
		$fd=fopen($filename, "r");
		 
		if ($fd)
		{
			while (!feof($fd))
			{
				$buffer=fgets($fd, 4096);
				// strip out any Windows line-endings that have gotten in here
				$buffer=str_replace("\r", "", $buffer);
				$lines[] = $buffer;
			}
			fclose($fd);
		}
	}
		
	$linecount = 0;
	$context = $initial_context;

	foreach($lines as $buffer)
	{
		$linematched=0;
		$linecount++;
		$nextcontext = "";
		$key = "";
	
		$buffer = trim($buffer);
		// alternative for use later where quoted strings are more useful
		$args = ParseString($buffer);
		
		if(sizeof($args) > 0)
		{		
			$linematched++;		
			$cmd = strtolower(array_shift($args));
						
			if($cmd == 'include')
			{
				$context = $this->ReadConfigNNG($args[0],TRUE, $context);
			}
			elseif($cmd == 'node')
			{
				$context = "NODE.".$args[0];
			}
			elseif($cmd == 'link')
			{
				$context = "LINK.".$args[0];
				$vcount = 0;	# reset the via-number counter, it's a new link
			}
			elseif($cmd == 'scale' || $cmd == 'keystyle' || $cmd == 'keypos')
			{
				if( preg_match("/^[0-9\-]+/i",$args[0]) )
				{
					$scalename = "DEFAULT";
				}
				else
				{
					$scalename = array_shift($args);
				}
				if($cmd=="scale") $key = $args[0]."_".$args[1];
				$nextcontext = $context;
				$context = "SCALE.".$scalename;
			}
			
			array_unshift($args,$cmd);
			
			if($context == 'GLOBAL')
	 		{ 
				$ctype='GLOBAL'; 
			}
			else
			{
				list($ctype,$junk) = split("\\.", $context, 2);
			}
			
			$lookup = $ctype.".".$cmd;
			
			// Some things (scales, mainly) might define special keys
			// the key should be unique for that object
			// most (all?) things for a link or node are one-offs. 
			if($key == "") $key = $cmd; 
			if($cmd == 'set' || $cmd == 'fontdefine') $key .= "_".$args[1];
			if($cmd == 'via')
			{
				$key .= "_".$vcount;
				$vcount++;
			}
			
			# everything else
			if( substr($cmd, 0, 1) != '#')
			{
				if(! array_key_exists($lookup, $valid_commands))
				{
					print "INVALID COMMAND: $lookup\n";
				}
				
				if(isset($config[$context][$key]))
				{
					print "REDEFINED $key in $context\n";
				}
				else
				{
					array_unshift($args,$linecount);
					array_unshift($args,$filename);
					$this->config[$context][$key] = $args;
				}
			}
			print "$context\\$key  $filename:$linecount ".join("|",$args)."\n";
			
			if($nextcontext != "") $context = $nextcontext;
		}
		
		if ($linematched == 0 && trim($buffer) != '') { warn ("Unrecognised config on line $linecount: $buffer\n"); }
		
	}
	
	if(! $is_include)
	{
		
		# print_r($this->config);
	
		foreach ($this->config as $context=>$values)
		{
		#	print "> $context\n";
		}
	}
	
	return($context);
}


function WriteConfigNG($filename)
{
	global $WEATHERMAP_VERSION;
	
	$fd = fopen($filename);
	
	
	
	fclose($fd);
}

function ReadConfig($input, $is_include=FALSE)
{
	$curnode=null;
	$curlink=null;
	$matches=0;
	$nodesseen=0;
	$linksseen=0;
	$scalesseen=0;
	$last_seen="GLOBAL";
	$filename = "";
	$objectlinecount=0;

	 // check if $input is more than one line. if it is, it's a text of a config file
	// if it isn't, it's the filename

	$lines = array();
	
	if( (strchr($input,"\n")!=FALSE) || (strchr($input,"\r")!=FALSE ) )
	{
		 debug("ReadConfig Detected that this is a config fragment.\n");
			 // strip out any Windows line-endings that have gotten in here
			 $input=str_replace("\r", "", $input);
			 $lines = split("/n",$input);
			 $filename = "{text insert}";
	}
	else
	{
		debug("ReadConfig Detected that this is a config filename.\n");
		 $filename = $input;
		 
		if($is_include){ 
			debug("ReadConfig Detected that this is an INCLUDED config filename.\n");
			if($is_include && in_array($filename, $this->included_files))
			{
				warn("Attempt to include '$filename' twice! Skipping it.\n");
				return(FALSE);
			}
			else
			{
				$this->included_files[] = $filename;
				$this->has_includes = TRUE;
			}
		}
		
		$fd=fopen($filename, "r");
		 
		if ($fd)
		{
				while (!feof($fd))
				{
					$buffer=fgets($fd, 4096);
					// strip out any Windows line-endings that have gotten in here
					$buffer=str_replace("\r", "", $buffer);
					$lines[] = $buffer;
				}
				fclose($fd);
		}
	}
		
	$linecount = 0;
	$objectlinecount = 0;

	foreach($lines as $buffer)
	{
		$linematched=0;
		$linecount++;
		
		if (preg_match("/^\s*#/", $buffer)) {
			// this is a comment line
		}
		else
		{
			$buffer = trim($buffer);	
			
			// for any other config elements that are shared between nodes and links, they can use this
			unset($curobj);
			$curobj = NULL;
			if($last_seen == "LINK") $curobj = &$curlink;
			if($last_seen == "NODE") $curobj = &$curnode;
			if($last_seen == "GLOBAL") $curobj = &$this;
			
			$objectlinecount++;

			#if (preg_match("/^\s*(LINK|NODE)\s+([A-Za-z][A-Za-z0-9_\.\-\:]*)\s*$/i", $buffer, $matches))
			if (preg_match("/^\s*(LINK|NODE)\s+(\S+)\s*$/i", $buffer, $matches))
			{
				$objectlinecount = 0;
				if(1==1)
				{
					$this->ReadConfig_Commit($curobj);
				}
				else
				{
					// first, save the previous item, before starting work on the new one
					if ($last_seen == "NODE")
					{
						$this->nodes[$curnode->name]=$curnode;
						if($curnode->template == 'DEFAULT') $this->node_template_tree[ "DEFAULT" ][]= $curnode->name;
					
						debug ("Saving Node: " . $curnode->name . "\n");
					}

					if ($last_seen == "LINK")
					{
						if (isset($curlink->a) && isset($curlink->b))
						{
							$this->links[$curlink->name]=$curlink;
							debug ("Saving Link: " . $curlink->name . "\n");
						}
						else
						{
							$this->links[$curlink->name]=$curlink;
							debug ("Saving Template-Only Link: " . $curlink->name . "\n");
						}
						if($curlink->template == 'DEFAULT') $this->link_template_tree[ "DEFAULT" ][]= $curlink->name;				
					}
				}

				if ($matches[1] == 'LINK')
				{
					if ($matches[2] == 'DEFAULT')
					{
						if ($linksseen > 0) { warn
							("LINK DEFAULT is not the first LINK. Defaults will not apply to earlier LINKs. [WMWARN26]\n");
						}
						unset($curlink);
						debug("Loaded LINK DEFAULT\n");
						$curlink = $this->links['DEFAULT'];
					}
					else
					{
						unset($curlink);

						if(isset($this->links[$matches[2]]))
						{
							warn("Duplicate link name ".$matches[2]." at line $linecount - only the last one defined is used. [WMWARN25]\n");
						}
						
						debug("New LINK ".$matches[2]."\n");
						$curlink=new WeatherMapLink;
						$curlink->name=$matches[2];
						$curlink->Reset($this);
										
						$linksseen++;
					}

					$last_seen="LINK";
					$curlink->configline = $linecount;
					$linematched++;
					$curobj = &$curlink;
				}

				if ($matches[1] == 'NODE')
				{
					if ($matches[2] == 'DEFAULT')
					{
						if ($nodesseen > 0) { warn
							("NODE DEFAULT is not the first NODE. Defaults will not apply to earlier NODEs. [WMWARN27]\n");
						}

						unset($curnode);
						debug("Loaded NODE DEFAULT\n");
						$curnode = $this->nodes['DEFAULT'];
					}
					else
					{
						unset($curnode);

						if(isset($this->nodes[$matches[2]]))
						{
							warn("Duplicate node name ".$matches[2]." at line $linecount - only the last one defined is used. [WMWARN24]\n");
						}

						$curnode=new WeatherMapNode;
						$curnode->name=$matches[2];
						$curnode->Reset($this);
						
						$nodesseen++;
					}

					$curnode->configline = $linecount;
					$last_seen="NODE";
					$linematched++;
					$curobj = &$curnode;
				}
				
				# record where we first heard about this object
				$curobj->defined_in = $filename;
			}

			// most of the config keywords just copy stuff into object properties.
			// these are all dealt with from this one array. The special-cases
			// follow on from that
			$config_keywords = array(
					array('LINK','/^\s*(MAXVALUE|BANDWIDTH)\s+(\d+\.?\d*[KMGT]?)\s+(\d+\.?\d*[KMGT]?)\s*$/i',array('max_bandwidth_in_cfg'=>2,'max_bandwidth_out_cfg'=>3)),
					array('LINK','/^\s*(MAXVALUE|BANDWIDTH)\s+(\d+\.?\d*[KMGT]?)\s*$/i',array('max_bandwidth_in_cfg'=>2,'max_bandwidth_out_cfg'=>2)),
					array('NODE','/^\s*(MAXVALUE)\s+(\d+\.?\d*[KMGT]?)\s+(\d+\.?\d*[KMGT]?)\s*$/i',array('max_bandwidth_in_cfg'=>2,'max_bandwidth_out_cfg'=>3)),
					array('NODE','/^\s*(MAXVALUE)\s+(\d+\.?\d*[KMGT]?)\s*$/i',array('max_bandwidth_in_cfg'=>2,'max_bandwidth_out_cfg'=>2)),
					array('GLOBAL','/^\s*BACKGROUND\s+(.*)\s*$/i',array('background'=>1)),
					array('GLOBAL','/^\s*HTMLOUTPUTFILE\s+(.*)\s*$/i',array('htmloutputfile'=>1)),
					array('GLOBAL','/^\s*HTMLSTYLESHEET\s+(.*)\s*$/i',array('htmlstylesheet'=>1)),
					array('GLOBAL','/^\s*IMAGEOUTPUTFILE\s+(.*)\s*$/i',array('imageoutputfile'=>1)),
					array('GLOBAL','/^\s*IMAGEURI\s+(.*)\s*$/i',array('imageuri'=>1)),
					array('GLOBAL','/^\s*TITLE\s+(.*)\s*$/i',array('title'=>1)),
					array('GLOBAL','/^\s*HTMLSTYLE\s+(static|overlib)\s*$/i',array('htmlstyle'=>1)),
					array('GLOBAL','/^\s*KEYFONT\s+(\d+)\s*$/i',array('keyfont'=>1)),
					array('GLOBAL','/^\s*TITLEFONT\s+(\d+)\s*$/i',array('titlefont'=>1)),
					array('GLOBAL','/^\s*TIMEFONT\s+(\d+)\s*$/i',array('timefont'=>1)),
					array('GLOBAL','/^\s*TITLEPOS\s+(-?\d+)\s+(-?\d+)\s*$/i',array('titlex'=>1, 'titley'=>2)),
					array('GLOBAL','/^\s*TITLEPOS\s+(-?\d+)\s+(-?\d+)\s+(.*)\s*$/i',array('titlex'=>1, 'titley'=>2, 'title'=>3)),
					array('GLOBAL','/^\s*TIMEPOS\s+(-?\d+)\s+(-?\d+)\s*$/i',array('timex'=>1, 'timey'=>2)),
					array('GLOBAL','/^\s*TIMEPOS\s+(-?\d+)\s+(-?\d+)\s+(.*)\s*$/i',array('timex'=>1, 'timey'=>2, 'stamptext'=>3)),
					array('GLOBAL','/^\s*MINTIMEPOS\s+(-?\d+)\s+(-?\d+)\s*$/i',array('mintimex'=>1, 'mintimey'=>2)),
					array('GLOBAL','/^\s*MINTIMEPOS\s+(-?\d+)\s+(-?\d+)\s+(.*)\s*$/i',array('mintimex'=>1, 'mintimey'=>2, 'minstamptext'=>3)),
					array('GLOBAL','/^\s*MAXTIMEPOS\s+(-?\d+)\s+(-?\d+)\s*$/i',array('maxtimex'=>1, 'maxtimey'=>2)),
					array('GLOBAL','/^\s*MAXTIMEPOS\s+(-?\d+)\s+(-?\d+)\s+(.*)\s*$/i',array('maxtimex'=>1, 'maxtimey'=>2, 'maxstamptext'=>3)),
					array('NODE', "/^\s*LABEL\s*$/i", array('label'=>'')),	# special case for blank labels
					array('NODE', "/^\s*LABEL\s+(.*)\s*$/i", array('label'=>1)),
					array('(LINK|GLOBAL)', "/^\s*WIDTH\s+(\d+)\s*$/i", array('width'=>1)),
					array('(LINK|GLOBAL)', "/^\s*HEIGHT\s+(\d+)\s*$/i", array('height'=>1)),
					array('LINK', "/^\s*WIDTH\s+(\d+\.\d+)\s*$/i", array('width'=>1)),
					array('LINK', '/^\s*ARROWSTYLE\s+(classic|compact)\s*$/i', array('arrowstyle'=>1)),
					array('LINK', '/^\s*VIASTYLE\s+(curved|angled)\s*$/i', array('viastyle'=>1)),
					array('LINK', '/^\s*INCOMMENT\s+(.*)\s*$/i', array('comments[IN]'=>1)),
					array('LINK', '/^\s*OUTCOMMENT\s+(.*)\s*$/i', array('comments[OUT]'=>1)),
					array('LINK', '/^\s*BWFONT\s+(\d+)\s*$/i', array('bwfont'=>1)),
					array('LINK', '/^\s*COMMENTFONT\s+(\d+)\s*$/i', array('commentfont'=>1)),
					array('LINK', '/^\s*COMMENTSTYLE\s+(edge|center)\s*$/i', array('commentstyle'=>1)),
					array('LINK', '/^\s*DUPLEX\s+(full|half)\s*$/i', array('duplex'=>1)),
					array('LINK', '/^\s*BWSTYLE\s+(classic|angled)\s*$/i', array('labelboxstyle'=>1)),
					array('LINK', '/^\s*LINKSTYLE\s+(twoway|oneway)\s*$/i', array('linkstyle'=>1)),
					array('LINK', '/^\s*BWLABELPOS\s+(\d+)\s(\d+)\s*$/i', array('labeloffset_in'=>1,'labeloffset_out'=>2)),
					array('LINK', '/^\s*COMMENTPOS\s+(\d+)\s(\d+)\s*$/i', array('commentoffset_in'=>1, 'commentoffset_out'=>2)),
					array('LINK', '/^\s*USESCALE\s+([A-Za-z][A-Za-z0-9_]*)\s*$/i', array('usescale'=>1)),
					array('LINK', '/^\s*USESCALE\s+([A-Za-z][A-Za-z0-9_]*)\s+(absolute|percent)\s*$/i', array('usescale'=>1,'scaletype'=>2)),

					array('LINK', '/^\s*SPLITPOS\s+(\d+)\s*$/i', array('splitpos'=>1)),
					
					array('NODE', '/^\s*LABELOFFSET\s+([-+]?\d+)\s+([-+]?\d+)\s*$/i', array('labeloffsetx'=>1,'labeloffsety'=>2)),
					array('NODE', '/^\s*LABELOFFSET\s+(C|NE|SE|NW|SW|N|S|E|W)\s*$/i', array('labeloffset'=>1)),
                                        array('NODE', '/^\s*LABELOFFSET\s+((C|NE|SE|NW|SW|N|S|E|W)\d+)\s*$/i', array('labeloffset'=>1)),
                                        array('NODE', '/^\s*LABELOFFSET\s+(-?\d+r\d+)\s*$/i', array('labeloffset'=>1)),
                            
					array('NODE', '/^\s*LABELFONT\s+(\d+)\s*$/i', array('labelfont'=>1)),
					array('NODE', '/^\s*LABELANGLE\s+(0|90|180|270)\s*$/i', array('labelangle'=>1)),
					# array('(NODE|LINK)', '/^\s*TEMPLATE\s+(\S+)\s*$/i', array('template'=>1)),						
					
					array('LINK', '/^\s*OUTBWFORMAT\s+(.*)\s*$/i', array('bwlabelformats[OUT]'=>1,'labelstyle'=>'--')),
					array('LINK', '/^\s*INBWFORMAT\s+(.*)\s*$/i', array('bwlabelformats[IN]'=>1,'labelstyle'=>'--')),
					# array('NODE','/^\s*ICON\s+none\s*$/i',array('iconfile'=>'')),
					array('NODE','/^\s*ICON\s+(\S+)\s*$/i',array('iconfile'=>1, 'iconscalew'=>'#0', 'iconscaleh'=>'#0')),
					array('NODE','/^\s*ICON\s+(\S+)\s*$/i',array('iconfile'=>1)),
					array('NODE','/^\s*ICON\s+(\d+)\s+(\d+)\s+(inpie|outpie|box|rbox|round|gauge|nink)\s*$/i',array('iconfile'=>3, 'iconscalew'=>1, 'iconscaleh'=>2)),
					array('NODE','/^\s*ICON\s+(\d+)\s+(\d+)\s+(\S+)\s*$/i',array('iconfile'=>3, 'iconscalew'=>1, 'iconscaleh'=>2)),
					
					array('NODE','/^\s*NOTES\s+(.*)\s*$/i',array('notestext[IN]'=>1,'notestext[OUT]'=>1)),
					array('LINK','/^\s*NOTES\s+(.*)\s*$/i',array('notestext[IN]'=>1,'notestext[OUT]'=>1)),
					array('LINK','/^\s*INNOTES\s+(.*)\s*$/i',array('notestext[IN]'=>1)),
					array('LINK','/^\s*OUTNOTES\s+(.*)\s*$/i',array('notestext[OUT]'=>1)),
					
					array('NODE','/^\s*INFOURL\s+(.*)\s*$/i',array('infourl[IN]'=>1,'infourl[OUT]'=>1)),
					array('LINK','/^\s*INFOURL\s+(.*)\s*$/i',array('infourl[IN]'=>1,'infourl[OUT]'=>1)),
					array('LINK','/^\s*ININFOURL\s+(.*)\s*$/i',array('infourl[IN]'=>1)),
					array('LINK','/^\s*OUTINFOURL\s+(.*)\s*$/i',array('infourl[OUT]'=>1)),
					
					array('NODE','/^\s*OVERLIBCAPTION\s+(.*)\s*$/i',array('overlibcaption[IN]'=>1,'overlibcaption[OUT]'=>1)),
					array('LINK','/^\s*OVERLIBCAPTION\s+(.*)\s*$/i',array('overlibcaption[IN]'=>1,'overlibcaption[OUT]'=>1)),
					array('LINK','/^\s*INOVERLIBCAPTION\s+(.*)\s*$/i',array('overlibcaption[IN]'=>1)),
					array('LINK','/^\s*OUTOVERLIBCAPTION\s+(.*)\s*$/i',array('overlibcaption[OUT]'=>1)),
											
					array('(NODE|LINK)', "/^\s*ZORDER\s+([-+]?\d+)\s*$/i", array('zorder'=>1)),
					array('(NODE|LINK)', "/^\s*OVERLIBWIDTH\s+(\d+)\s*$/i", array('overlibwidth'=>1)),
					array('(NODE|LINK)', "/^\s*OVERLIBHEIGHT\s+(\d+)\s*$/i", array('overlibheight'=>1)),
					array('NODE', "/^\s*POSITION\s+([-+]?\d+)\s+([-+]?\d+)\s*$/i", array('x'=>1,'y'=>2)),
					array('NODE', "/^\s*POSITION\s+(\S+)\s+([-+]?\d+)\s+([-+]?\d+)\s*$/i", array('x'=>2,'y'=>3,'original_x'=>2,'original_y'=>3,'relative_to'=>1,'relative_resolved'=>FALSE)),
					array('NODE', "/^\s*POSITION\s+(\S+)\s+([-+]?\d+)r(\d+)\s*$/i", array('x'=>2,'y'=>3,'original_x'=>2,'original_y'=>3,'relative_to'=>1,'polar'=>TRUE,'relative_resolved'=>FALSE))
					);

			// alternative for use later where quoted strings are more useful
			$args = ParseString($buffer);

			// this loop replaces a whole pile of duplicated ifs with something with consistent handling 
			foreach ($config_keywords as $keyword)
			{
				if(preg_match("/".$keyword[0]."/",$last_seen))
				{
					$statskey = $last_seen."-".$keyword[1];
					$statskey = str_replace( array('/^\s*','\s*$/i'),array('',''), $statskey);
					if(!isset($this->usage_stats[$statskey])) $this->usage_stats[$statskey] = 0;
					
					if(preg_match($keyword[1],$buffer,$matches))
					{
						# print "CONFIG MATCHED: ".$keyword[1]."\n";
						
						$this->usage_stats[$statskey]++;
						
						foreach ($keyword[2] as $key=>$val)
						{
							// so we can poke in numbers too, if the value starts with #
							// then take the # off, and treat the rest as a number literal
							if(preg_match("/^#(.*)/",$val,$m))
							{
								$val = $m[1];
							}	
							elseif(is_numeric($val)) 
							{
								// if it's a number, then it;s a match number,
								// otherwise it's a literal to be put into a variable
								$val = $matches[$val];
							}
							
							assert('is_object($curobj)');
							
							if(preg_match('/^(.*)\[([^\]]+)\]$/',$key,$m))
							{
								$index = constant($m[2]);
								$key = $m[1];
								$curobj->{$key}[$index] = $val;
							}
							else
							{
								$curobj->$key = $val;
							}
						}
						$linematched++;
						# print "\n\n";
						break;
					}
				}
			}

			if (preg_match("/^\s*NODES\s+(\S+)\s+(\S+)\s*$/i", $buffer, $matches))
			{
				if ($last_seen == 'LINK')
				{
					$valid_nodes=2;

					foreach (array(1, 2)as $i)
					{
						$endoffset[$i]='C';
						$nodenames[$i]=$matches[$i];

						// percentage of compass - must be first
						if (preg_match("/:(NE|SE|NW|SW|N|S|E|W|C)(\d+)$/i", $matches[$i], $submatches))
						{
							$endoffset[$i]=$submatches[1].$submatches[2];
							$nodenames[$i]=preg_replace("/:(NE|SE|NW|SW|N|S|E|W|C)\d+$/i", '', $matches[$i]);
							$this->need_size_precalc=TRUE;
						}
						
						if (preg_match("/:(NE|SE|NW|SW|N|S|E|W|C)$/i", $matches[$i], $submatches))
						{
							$endoffset[$i]=$submatches[1];
							$nodenames[$i]=preg_replace("/:(NE|SE|NW|SW|N|S|E|W|C)$/i", '', $matches[$i]);
							$this->need_size_precalc=TRUE;
						}

						if( preg_match("/:(-?\d+r\d+)$/i", $matches[$i], $submatches) )
						{
							$endoffset[$i]=$submatches[1];
							$nodenames[$i]=preg_replace("/:(-?\d+r\d+)$/i", '', $matches[$i]);
							$this->need_size_precalc=TRUE;
						}

						if (preg_match("/:([-+]?\d+):([-+]?\d+)$/i", $matches[$i], $submatches))
						{
							$xoff = $submatches[1];
							$yoff = $submatches[2];
							$endoffset[$i]=$xoff.":".$yoff;
							$nodenames[$i]=preg_replace("/:$xoff:$yoff$/i", '', $matches[$i]);
							$this->need_size_precalc=TRUE;
						}

						if (!array_key_exists($nodenames[$i], $this->nodes))
						{
							warn ("Unknown node '" . $nodenames[$i] . "' on line $linecount of config\n");
							$valid_nodes--;
						}
					}
					
					// TODO - really, this should kill the whole link, and reset for the next one
					if ($valid_nodes == 2)
					{
						$curlink->a=$this->nodes[$nodenames[1]];
						$curlink->b=$this->nodes[$nodenames[2]];
						$curlink->a_offset=$endoffset[1];
						$curlink->b_offset=$endoffset[2];
					}
					else {
						// this'll stop the current link being added
						$last_seen="broken"; }

						$linematched++;
				}
			}

			if ( $last_seen=='GLOBAL' && preg_match("/^\s*INCLUDE\s+(.*)\s*$/i", $buffer, $matches))
			{
				if(file_exists($matches[1])){
					debug("Including '{$matches[1]}'\n");
					$this->ReadConfig($matches[1], TRUE);
					$last_seen = "GLOBAL";				
				}else{
					warn("INCLUDE File '{$matches[1]}' not found!\n");
				}
				$linematched++;
			}
			
			if ( ( $last_seen=='NODE' || $last_seen=='LINK' ) && preg_match("/^\s*TARGET\s+(.*)\s*$/i", $buffer, $matches))
			{
				$linematched++;
				# $targets=preg_split('/\s+/', $matches[1], -1, PREG_SPLIT_NO_EMPTY);
				$rawtargetlist = $matches[1]." ";
							
				if($args[0]=='TARGET')
				{
					// wipe any existing targets, otherwise things in the DEFAULT accumulate with the new ones
					$curobj->targets = array();
					array_shift($args); // take off the actual TARGET keyword
					
					foreach($args as $arg)
					{
						// we store the original TARGET string, and line number, along with the breakdown, to make nicer error messages later
						// array of 7 things:
						// - only 0,1,2,3,4 are used at the moment (more used to be before DS plugins)
						// 0 => final target string (filled in by ReadData)
						// 1 => multiplier (filled in by ReadData)
						// 2 => config filename where this line appears
						// 3 => linenumber in that file
						// 4 => the original target string
						// 5 => the plugin to use to pull data 
						$newtarget=array('','',$filename,$linecount,$arg,"","");
						if ($curobj)
						{
							debug("  TARGET: $arg\n");
							$curobj->targets[]=$newtarget;
						}
					}
				}
			}
			
			if ($last_seen == 'LINK' && preg_match(
				"/^\s*BWLABEL\s+(bits|percent|unformatted|none)\s*$/i", $buffer,
				$matches))
			{
				$format_in = '';
				$format_out = '';
				$style = strtolower($matches[1]);
				if($style=='percent')
				{
					$format_in = FMT_PERC_IN;
					$format_out = FMT_PERC_OUT;
				}
				if($style=='bits')
				{
					$format_in = FMT_BITS_IN;
					$format_out = FMT_BITS_OUT;
				}
				if($style=='unformatted')
				{
					$format_in = FMT_UNFORM_IN;
					$format_out = FMT_UNFORM_OUT;
				}

				$curobj->labelstyle=$style;
				$curobj->bwlabelformats[IN] = $format_in;
				$curobj->bwlabelformats[OUT] = $format_out;
				$linematched++;
			}			
			
			if (preg_match("/^\s*SET\s+(\S+)\s+(.*)\s*$/i", $buffer, $matches))
			{
					$curobj->add_hint($matches[1],trim($matches[2]));
					$linematched++;
			}				

			// allow setting a variable to ""
			if (preg_match("/^\s*SET\s+(\S+)\s*$/i", $buffer, $matches))
			{
					$curobj->add_hint($matches[1],'');
					$linematched++;
			}				
			
			if (preg_match("/^\s*(IN|OUT)?OVERLIBGRAPH\s+(.+)$/i", $buffer, $matches))
			{
				$this->has_overlibs = TRUE;
				if($last_seen == 'NODE' && $matches[1] != '') {
						warn("IN/OUTOVERLIBGRAPH make no sense for a NODE! [WMWARN42]\n");
					} else if($last_seen == 'LINK' || $last_seen=='NODE' ) {
				
						$urls = preg_split('/\s+/', $matches[2], -1, PREG_SPLIT_NO_EMPTY);

						if($matches[1] == 'IN') $index = IN;
						if($matches[1] == 'OUT') $index = OUT;
						if($matches[1] == '') {
							$curobj->overliburl[IN]=$urls;
							$curobj->overliburl[OUT]=$urls;
						} else {
							$curobj->overliburl[$index]=$urls;
						}
						$linematched++;
					}
			}			
		
			// array('(NODE|LINK)', '/^\s*TEMPLATE\s+(\S+)\s*$/i', array('template'=>1)),
				
			if ( ( $last_seen=='NODE' || $last_seen=='LINK' ) && preg_match("/^\s*TEMPLATE\s+(\S+)\s*$/i", $buffer, $matches))
			{
				$tname = $matches[1];
				if( ($last_seen=='NODE' && isset($this->nodes[$tname])) || ($last_seen=='LINK' && isset($this->links[$tname])) )
				{
					$curobj->template = $matches[1];
					debug("Resetting to template $last_seen ".$curobj->template."\n");
					$curobj->Reset($this);
					if( $objectlinecount > 1 ) warn("line $linecount: TEMPLATE is not first line of object. Some data may be lost. [WMWARN39]\n");
					// build up a list of templates - this will be useful later for the tree view
					
					if($last_seen == 'NODE') $this->node_template_tree[ $tname ][]= $curobj->name;
					if($last_seen == 'LINK') $this->link_template_tree[ $tname ][]= $curobj->name;
				}
				else
				{
					warn("line $linecount: $last_seen TEMPLATE '$tname' doesn't exist! (if it does exist, check it's defined first) [WMWARN40]\n");
				}
				$linematched++;	
				
			}

			if ($last_seen == 'LINK' && preg_match("/^\s*VIA\s+([-+]?\d+)\s+([-+]?\d+)\s*$/i", $buffer, $matches))
			{
				$curlink->vialist[]=array
					(
						$matches[1],
						$matches[2]
					);

				$linematched++;
			}

			if ($last_seen == 'LINK' && preg_match("/^\s*VIA\s+(\S+)\s+([-+]?\d+)\s+([-+]?\d+)\s*$/i", $buffer, $matches))
			{
				$curlink->vialist[]=array
					(
						$matches[2],
						$matches[3],
						$matches[1]
					);

				$linematched++;
			}

			if( ($last_seen == 'NODE') && preg_match("/^\s*USE(ICON)?SCALE\s+([A-Za-z][A-Za-z0-9_]*)(\s+(in|out))?(\s+(absolute|percent))?\s*$/i",$buffer,$matches))
			{
				$svar = '';
				$stype = 'percent';
				if(isset($matches[3]))
				{
					$svar = trim($matches[3]);
				}
				if(isset($matches[6]))
				{
					$stype = strtolower(trim($matches[6]));
				}
				// opens the door for other scaley things...
				switch($matches[1])
				{
					case 'ICON':
						$varname = 'iconscalevar';
						$uvarname = 'useiconscale';
						$tvarname = 'iconscaletype';
						
						// if(!function_exists("imagefilter"))
						// {
						// 	warn("ICON SCALEs require imagefilter, which is not present in your PHP [WMWARN040]\n");
						// }
						break;
					default:
						$varname = 'scalevar';
						$uvarname = 'usescale';
						$tvarname = 'scaletype';
						break;
				}

				if($svar != '')
				{
					$curnode->$varname = $svar;
				}
				$curnode->$tvarname = $stype;
				$curnode->$uvarname = $matches[2];

				// warn("Set $varname and $uvarname\n");

				// print ">> $stype $svar ".$matches[2]." ".$curnode->name." \n";

				$linematched++;
			}

			// one REGEXP to rule them all:
//				if(preg_match("/^\s*SCALE\s+([A-Za-z][A-Za-z0-9_]*\s+)?(\d+\.?\d*)\s+(\d+\.?\d*)\s+(\d+)\s+(\d+)\s+(\d+)(?:\s+(\d+)\s+(\d+)\s+(\d+))?\s*$/i",
//	0.95b		if(preg_match("/^\s*SCALE\s+([A-Za-z][A-Za-z0-9_]*\s+)?(\d+\.?\d*)\s+(\d+\.?\d*)\s+(\d+)\s+(\d+)\s+(\d+)(?:\s+(\d+)\s+(\d+)\s+(\d+))?\s*(.*)$/i",
			if(preg_match("/^\s*SCALE\s+([A-Za-z][A-Za-z0-9_]*\s+)?(\-?\d+\.?\d*[munMGT]?)\s+(\-?\d+\.?\d*[munMGT]?)\s+(?:(\d+)\s+(\d+)\s+(\d+)(?:\s+(\d+)\s+(\d+)\s+(\d+))?|(none))\s*(.*)$/i",
				$buffer, $matches))
			{
				// The default scale name is DEFAULT
				if($matches[1]=='') $matches[1] = 'DEFAULT';
				else $matches[1] = trim($matches[1]);

				$key=$matches[2] . '_' . $matches[3];

				$this->colours[$matches[1]][$key]['key']=$key;

				$tag = $matches[11];				

				$this->colours[$matches[1]][$key]['tag']=$tag;

				$this->colours[$matches[1]][$key]['bottom'] = unformat_number($matches[2], $this->kilo);
				$this->colours[$matches[1]][$key]['top'] = unformat_number($matches[3], $this->kilo);
				$this->colours[$matches[1]][$key]['special'] = 0;

				if(isset($matches[10]) && $matches[10] == 'none')
				{
					$this->colours[$matches[1]][$key]['red1'] = -1;
					$this->colours[$matches[1]][$key]['green1'] = -1;
					$this->colours[$matches[1]][$key]['blue1'] = -1;
				}
				else
				{
					$this->colours[$matches[1]][$key]['red1'] = (int)($matches[4]);
					$this->colours[$matches[1]][$key]['green1'] = (int)($matches[5]);
					$this->colours[$matches[1]][$key]['blue1'] = (int)($matches[6]);
				}

				// this is the second colour, if there is one
				if(isset($matches[7]) && $matches[7] != '')
				{
					$this->colours[$matches[1]][$key]['red2'] = (int) ($matches[7]);
					$this->colours[$matches[1]][$key]['green2'] = (int) ($matches[8]);
					$this->colours[$matches[1]][$key]['blue2'] = (int) ($matches[9]);
				}


				if(! isset($this->numscales[$matches[1]]))
				{
					$this->numscales[$matches[1]]=1;
				}
				else
				{
					$this->numscales[$matches[1]]++;
				}
				// we count if we've seen any default scale, otherwise, we have to add
				// one at the end.
				if($matches[1]=='DEFAULT')
				{
					$scalesseen++;
				}

				$linematched++;
			}

			if (preg_match("/^\s*KEYPOS\s+([A-Za-z][A-Za-z0-9_]*\s+)?(-?\d+)\s+(-?\d+)(.*)/i", $buffer, $matches))
			{
				$whichkey = trim($matches[1]);
				if($whichkey == '') $whichkey = 'DEFAULT';

				$this->keyx[$whichkey]=$matches[2];
				$this->keyy[$whichkey]=$matches[3];
				$extra=trim($matches[4]);

				if ($extra != '')
					$this->keytext[$whichkey] = $extra;
				if(!isset($this->keytext[$whichkey]))
					$this->keytext[$whichkey] = "DEFAULT TITLE";
				if(!isset($this->keystyle[$whichkey]))
					$this->keystyle[$whichkey] = "classic";

				$linematched++;
			}

			
			// truetype font definition (actually, we don't really check if it's truetype) - filename + size
			if (preg_match("/^\s*FONTDEFINE\s+(\d+)\s+(\S+)\s+(\d+)\s*$/i", $buffer, $matches))
			{
				if (function_exists("imagettfbbox"))
				{
					// test if this font is valid, before adding it to the font table...
					$bounds=@imagettfbbox($matches[3], 0, $matches[2], "Ignore me");

					if (isset($bounds[0]))
					{
						$this->fonts[$matches[1]]->type="truetype";
						$this->fonts[$matches[1]]->file=$matches[2];
						$this->fonts[$matches[1]]->size=$matches[3];
					}
					else { warn
						("Failed to load ttf font " . $matches[2] . " - at config line $linecount\n [WMWARN30]"); }
				}
				else { warn
					("imagettfbbox() is not a defined function. You don't seem to have FreeType compiled into your gd module. [WMWARN31]\n");
				}

				$linematched++;
			}

			// GD font definition (no size here)
			if (preg_match("/^\s*FONTDEFINE\s+(\d+)\s+(\S+)\s*$/i", $buffer, $matches))
			{
				$newfont=imageloadfont($matches[2]);

				if ($newfont)
				{
					$this->fonts[$matches[1]]->type="gd";
					$this->fonts[$matches[1]]->file=$matches[2];
					$this->fonts[$matches[1]]->gdnumber=$newfont;
				}
				else { warn ("Failed to load GD font: " . $matches[2]
					. " ($newfont) at config line $linecount [WMWARN32]\n"); }

				$linematched++;
			}

			if(preg_match("/^\s*KEYSTYLE\s+([A-Za-z][A-Za-z0-9_]+\s+)?(classic|horizontal|vertical|inverted|tags)\s?(\d+)?\s*$/i",$buffer, $matches))
			{
				$whichkey = trim($matches[1]);
				if($whichkey == '') $whichkey = 'DEFAULT';
				$this->keystyle[$whichkey] = strtolower($matches[2]);

				if(isset($matches[3]) && $matches[3] != '')
				{
					$this->keysize[$whichkey] = $matches[3];
				}
				else
				{
					$this->keysize[$whichkey] = $this->keysize['DEFAULT'];
				}

				$linematched++;
			}

			
			if (preg_match("/^\s*KILO\s+(\d+)\s*$/i", $buffer, $matches))
			{
				$this->kilo=$matches[1];
				# $this->defaultlink->owner->kilo=$matches[1];
				# $this->links['DEFAULT']=$matches[1];
				$linematched++;
			}
			
			if (preg_match(
				"/^\s*(TIME|TITLE|KEYBG|KEYTEXT|KEYOUTLINE|BG)COLOR\s+(\d+)\s+(\d+)\s+(\d+)\s*$/i",
				$buffer,
				$matches))
			{
				$key=$matches[1];
				# "Found colour line for $key\n";
				$this->colours['DEFAULT'][$key]['red1']=$matches[2];
				$this->colours['DEFAULT'][$key]['green1']=$matches[3];
				$this->colours['DEFAULT'][$key]['blue1']=$matches[4];
				$this->colours['DEFAULT'][$key]['bottom']=-2;
				$this->colours['DEFAULT'][$key]['top']=-1;
				$this->colours['DEFAULT'][$key]['special']=1;

				$linematched++;
			}

			if (($last_seen == 'NODE') && (preg_match(
				"/^\s*(AICONOUTLINE|AICONFILL|LABELFONT|LABELFONTSHADOW|LABELBG|LABELOUTLINE)COLOR\s+((\d+)\s+(\d+)\s+(\d+)|none|contrast|copy)\s*$/i",
				$buffer,
				$matches)))
			{
				$key=$matches[1];
				$field=strtolower($matches[1]) . 'colour';
				$val = strtolower($matches[2]);

				if(isset($matches[3]))	// this is a regular colour setting thing
				{
					$curnode->$field=array(	$matches[3],$matches[4],$matches[5]);
					$linematched++;
				}

				if($val == 'none' && ($matches[1]=='LABELFONTSHADOW' || $matches[1]=='LABELBG' || $matches[1]=='LABELOUTLINE' || $matches[1]=='AICONOUTLINE'))
				{
					$curnode->$field=array(-1,-1,-1);
					$linematched++;
				}
				
				if($val == 'contrast' && $matches[1]=='LABELFONT')
				{
					$curnode->$field=array(-3,-3,-3);
					$linematched++;
				}

				if($matches[2] == 'copy' && $matches[1]=='AICONFILL')
				{
					$curnode->$field=array(-2,-2,-2);
					$linematched++;
				}
			}

			if (($last_seen == 'LINK') && (preg_match(
				"/^\s*(COMMENTFONT|BWBOX|BWFONT|BWOUTLINE|OUTLINE)COLOR\s+((\d+)\s+(\d+)\s+(\d+)|none|contrast|copy)\s*$/i",
				$buffer,
				$matches)))
			{
				$key=$matches[1];
				$field=strtolower($matches[1]) . 'colour';
				$val = strtolower($matches[2]);

				if(isset($matches[3]))	// this is a regular colour setting thing
				{
					$curlink->$field=array(	$matches[3],$matches[4],$matches[5]);
					$linematched++;
				}
				
				if($val == 'none' && ($key=='BWBOX' || $key=='BWOUTLINE' || $key=='OUTLINE'))
				{
					// print "***********************************\n";
					$curlink->$field=array(-1,-1,-1);
					$linematched++;
				}
				
				if($val == 'contrast' && $key=='COMMENTFONT')
				{
					// print "***********************************\n";
					$curlink->$field=array(-3,-3,-3);
					$linematched++;
				}
			}

			if ($last_seen == 'LINK' && preg_match(
				"/^\s*ARROWSTYLE\s+(\d+)\s+(\d+)\s*$/i", $buffer, $matches))
			{
				$curlink->arrowstyle=$matches[1] . ' ' . $matches[2];
				$linematched++;
			}
			

			if ($linematched == 0 && trim($buffer) != '') { warn
				("Unrecognised config on line $linecount: $buffer\n"); }

			if ($linematched > 1) { warn
			("Same line ($linecount) interpreted twice. This is a program error. Please report to Howie with your config!\nThe line was: $buffer");
			}
		} // if blankline
	}     // while

	if(1==1)
	{
		$this->ReadConfig_Commit($curobj);
	}
	else
	{
	if ($last_seen == "NODE")
	{
		$this->nodes[$curnode->name]=$curnode;
		debug ("Saving Node: " . $curnode->name . "\n");
		if($curnode->template == 'DEFAULT') $this->node_template_tree[ "DEFAULT" ][]= $curnode->name;	
	}

	if ($last_seen == "LINK")
	{
		if (isset($curlink->a) && isset($curlink->b))
		{
			$this->links[$curlink->name]=$curlink;
			debug ("Saving Link: " . $curlink->name . "\n");
			if($curlink->template == 'DEFAULT') $this->link_template_tree[ "DEFAULT" ][]= $curlink->name;				
		}
		else { warn ("Dropping LINK " . $curlink->name . " - it hasn't got 2 NODES!"); }
	}
	}
	
		
	debug("ReadConfig has finished reading the config ($linecount lines)\n");
	debug("------------------------------------------\n");

	// load some default colouring, otherwise it all goes wrong
	if ($scalesseen == 0)
	{
		debug ("Adding default SCALE colour set (no SCALE lines seen).\n");
		$defaults=array
			(
				'0_0' => array('bottom' => 0, 'top' => 0, 'red1' => 192, 'green1' => 192, 'blue1' => 192, 'special'=>0),
				'0_1' => array('bottom' => 0, 'top' => 1, 'red1' => 255, 'green1' => 255, 'blue1' => 255, 'special'=>0),
				'1_10' => array('bottom' => 1, 'top' => 10, 'red1' => 140, 'green1' => 0, 'blue1' => 255, 'special'=>0),
				'10_25' => array('bottom' => 10, 'top' => 25, 'red1' => 32, 'green1' => 32, 'blue1' => 255, 'special'=>0),
				'25_40' => array('bottom' => 25, 'top' => 40, 'red1' => 0, 'green1' => 192, 'blue1' => 255, 'special'=>0),
				'40_55' => array('bottom' => 40, 'top' => 55, 'red1' => 0, 'green1' => 240, 'blue1' => 0, 'special'=>0),
				'55_70' => array('bottom' => 55, 'top' => 70, 'red1' => 240, 'green1' => 240, 'blue1' => 0, 'special'=>0),
				'70_85' => array('bottom' => 70, 'top' => 85, 'red1' => 255, 'green1' => 192, 'blue1' => 0, 'special'=>0),
				'85_100' => array('bottom' => 85, 'top' => 100, 'red1' => 255, 'green1' => 0, 'blue1' => 0, 'special'=>0)
			);

		foreach ($defaults as $key => $def)
		{
			$this->colours['DEFAULT'][$key]=$def;
			$this->colours['DEFAULT'][$key]['key']=$key;
			$scalesseen++;
		}
		// we have a 0-0 line now, so we need to hide that.
		$this->add_hint("key_hidezero_DEFAULT",1);
	}
	else { debug ("Already have $scalesseen scales, no defaults added.\n"); }
	
	$this->numscales['DEFAULT']=$scalesseen;
	$this->configfile="$filename";

	if($this->has_overlibs && $this->htmlstyle == 'static')
	{
		warn("OVERLIBGRAPH is used, but HTMLSTYLE is static. This is probably wrong. [WMWARN41]\n");
	}
	
	debug("Building cache of z-layers and finalising bandwidth.\n");

// 	$allitems = array_merge($this->links, $this->nodes);

	$allitems = array();
	foreach ($this->nodes as $node)
	{
		$allitems[] = $node;
	}
	foreach ($this->links as $link)
	{
		$allitems[] = $link;
	}
	
	# foreach ($allitems as &$item)
	foreach ($allitems as $ky=>$vl)
	{
		$item =& $allitems[$ky];
		$z = $item->zorder;
		if(!isset($this->seen_zlayers[$z]) || !is_array($this->seen_zlayers[$z]))
		{
			$this->seen_zlayers[$z]=array();
		}
		array_push($this->seen_zlayers[$z], $item);
		
		// while we're looping through, let's set the real bandwidths
		if($item->my_type() == "LINK")
		{
			$this->links[$item->name]->max_bandwidth_in = unformat_number($item->max_bandwidth_in_cfg, $this->kilo);
			$this->links[$item->name]->max_bandwidth_out = unformat_number($item->max_bandwidth_out_cfg, $this->kilo);
		}
		elseif($item->my_type() == "NODE")
		{
			$this->nodes[$item->name]->max_bandwidth_in = unformat_number($item->max_bandwidth_in_cfg, $this->kilo);
			$this->nodes[$item->name]->max_bandwidth_out = unformat_number($item->max_bandwidth_out_cfg, $this->kilo);
		}
		else
		{
			warn("Internal bug - found an item of type: ".$item->my_type()."\n");
		}
		// $item->max_bandwidth_in=unformat_number($item->max_bandwidth_in_cfg, $this->kilo);
		// $item->max_bandwidth_out=unformat_number($item->max_bandwidth_out_cfg, $this->kilo);
		
		debug (sprintf("   Setting bandwidth on ".$item->my_type()." $item->name (%s -> %d bps, %s -> %d bps, KILO = %d)\n", $item->max_bandwidth_in_cfg, $item->max_bandwidth_in, $item->max_bandwidth_out_cfg, $item->max_bandwidth_out, $this->kilo));		
	}

	debug("Found ".sizeof($this->seen_zlayers)." z-layers including builtins (0,100).\n");

	// calculate any relative positions here - that way, nothing else
	// really needs to know about them

	debug("Resolving relative positions for NODEs...\n");
	// safety net for cyclic dependencies
	$i=100;
	do
	{
		$skipped = 0; $set=0;
		foreach ($this->nodes as $node)
		{
			if( ($node->relative_to != '') && (!$node->relative_resolved))
			{
				debug("Resolving relative position for NODE ".$node->name." to ".$node->relative_to."\n");
				if(array_key_exists($node->relative_to,$this->nodes))
				{
					
					// check if we are relative to another node which is in turn relative to something
					// we need to resolve that one before we can resolve this one!
					if(  ($this->nodes[$node->relative_to]->relative_to != '') && (!$this->nodes[$node->relative_to]->relative_resolved) )
					{
						debug("Skipping unresolved relative_to. Let's hope it's not a circular one\n");
						$skipped++;
					}
					else
					{
						$rx = $this->nodes[$node->relative_to]->x;
						$ry = $this->nodes[$node->relative_to]->y;
						
						if($node->polar)
						{
							// treat this one as a POLAR relative coordinate.
							// - draw rings around a node!
							$angle = $node->x;
							$distance = $node->y;
							$newpos_x = $rx + $distance * sin(deg2rad($angle));
							$newpos_y = $ry - $distance * cos(deg2rad($angle));
							debug("->$newpos_x,$newpos_y\n");
							$this->nodes[$node->name]->x = $newpos_x;
							$this->nodes[$node->name]->y = $newpos_y;
							$this->nodes[$node->name]->relative_resolved=TRUE;
							$set++;
						}
						else
						{
							
							// save the relative coords, so that WriteConfig can work
							// resolve the relative stuff
	
							$newpos_x = $rx + $this->nodes[$node->name]->x;
							$newpos_y = $ry + $this->nodes[$node->name]->y;
							debug("->$newpos_x,$newpos_y\n");
							$this->nodes[$node->name]->x = $newpos_x;
							$this->nodes[$node->name]->y = $newpos_y;
							$this->nodes[$node->name]->relative_resolved=TRUE;
							$set++;
						}
					}
				}
				else
				{
					warn("NODE ".$node->name." has a relative position to an unknown node! [WMWARN10]\n");
				}
			}
		}
		debug("Relative Positions Cycle $i - set $set and Skipped $skipped for unresolved dependencies\n");
		$i--;
	} while( ($set>0) && ($i!=0)  );

	if($skipped>0)
	{
		warn("There are Circular dependencies in relative POSITION lines for $skipped nodes. [WMWARN11]\n");
	}

	debug("-----------------------------------\n");


	debug("Running Pre-Processing Plugins...\n");
	foreach ($this->preprocessclasses as $pre_class)
	{
		debug("Running $pre_class"."->run()\n");
		$this->plugins['pre'][$pre_class]->run($this);
	}
	debug("Finished Pre-Processing Plugins...\n");

	return (TRUE);
}

function ReadConfig_Commit(&$curobj)
{
	if(is_null($curobj)) return;
	
	$last_seen = $curobj->my_type();

	// first, save the previous item, before starting work on the new one
	if ($last_seen == "NODE")
	{
		$this->nodes[$curobj->name]=$curobj;
		debug ("Saving Node: " . $curobj->name . "\n");
		if($curobj->template == 'DEFAULT') $this->node_template_tree[ "DEFAULT" ][]= $curobj->name;
	}

	if ($last_seen == "LINK")
	{
		if (isset($curobj->a) && isset($curobj->b))
		{
			$this->links[$curobj->name]=$curobj;
			debug ("Saving Link: " . $curobj->name . "\n");
		}
		else
		{
			$this->links[$curobj->name]=$curobj;
			debug ("Saving Template-Only Link: " . $curobj->name . "\n");
		}
		if($curobj->template == 'DEFAULT') $this->link_template_tree[ "DEFAULT" ][]= $curobj->name;				
	}
}

function WriteConfig($filename)
{
	global $WEATHERMAP_VERSION;

	$fd=fopen($filename, "w");
	$output="";

	if ($fd)
	{
		$output.="# Automatically generated by php-weathermap v$WEATHERMAP_VERSION\n\n";

		if (count($this->fonts) > 0)
		{
			foreach ($this->fonts as $fontnumber => $font)
			{
				if ($font->type == 'truetype')
					$output.=sprintf("FONTDEFINE %d %s %d\n", $fontnumber, $font->file, $font->size);

				if ($font->type == 'gd')
					$output.=sprintf("FONTDEFINE %d %s\n", $fontnumber, $font->file);
			}

			$output.="\n";
		}

		$basic_params = array(
				array('background','BACKGROUND',CONFIG_TYPE_LITERAL),
				array('width','WIDTH',CONFIG_TYPE_LITERAL),
				array('height','HEIGHT',CONFIG_TYPE_LITERAL),
				array('htmlstyle','HTMLSTYLE',CONFIG_TYPE_LITERAL),
				array('kilo','KILO',CONFIG_TYPE_LITERAL),
				array('keyfont','KEYFONT',CONFIG_TYPE_LITERAL),
				array('timefont','TIMEFONT',CONFIG_TYPE_LITERAL),
				array('titlefont','TITLEFONT',CONFIG_TYPE_LITERAL),
				array('title','TITLE',CONFIG_TYPE_LITERAL),
				array('htmloutputfile','HTMLOUTPUTFILE',CONFIG_TYPE_LITERAL),
				array('htmlstylesheet','HTMLSTYLESHEET',CONFIG_TYPE_LITERAL),
				array('imageuri','IMAGEURI',CONFIG_TYPE_LITERAL),
				array('imageoutputfile','IMAGEOUTPUTFILE',CONFIG_TYPE_LITERAL)
			);

		foreach ($basic_params as $param)
		{
			$field = $param[0];
			$keyword = $param[1];

			if ($this->$field != $this->inherit_fieldlist[$field])
			{
				if($param[2] == CONFIG_TYPE_COLOR) $output.="$keyword " . render_colour($this->$field) . "\n";
				if($param[2] == CONFIG_TYPE_LITERAL) $output.="$keyword " . $this->$field . "\n";
			}
		}

		if (($this->timex != $this->inherit_fieldlist['timex'])
			|| ($this->timey != $this->inherit_fieldlist['timey'])
			|| ($this->stamptext != $this->inherit_fieldlist['stamptext']))
				$output.="TIMEPOS " . $this->timex . " " . $this->timey . " " . $this->stamptext . "\n";

		if (($this->mintimex != $this->inherit_fieldlist['mintimex'])
			|| ($this->mintimey != $this->inherit_fieldlist['mintimey'])
			|| ($this->minstamptext != $this->inherit_fieldlist['minstamptext']))
				$output.="MINTIMEPOS " . $this->mintimex . " " . $this->mintimey . " " . $this->minstamptext . "\n";
		
		if (($this->maxtimex != $this->inherit_fieldlist['maxtimex'])
			|| ($this->maxtimey != $this->inherit_fieldlist['maxtimey'])
			|| ($this->maxstamptext != $this->inherit_fieldlist['maxstamptext']))
				$output.="MAXTIMEPOS " . $this->maxtimex . " " . $this->maxtimey . " " . $this->maxstamptext . "\n";
						
		if (($this->titlex != $this->inherit_fieldlist['titlex'])
			|| ($this->titley != $this->inherit_fieldlist['titley']))
				$output.="TITLEPOS " . $this->titlex . " " . $this->titley . "\n";

		$output.="\n";

		foreach ($this->colours as $scalename=>$colours)
		{
		  // not all keys will have keypos but if they do, then all three vars should be defined
		if ( (isset($this->keyx[$scalename])) && (isset($this->keyy[$scalename])) && (isset($this->keytext[$scalename]))
		    && (($this->keytext[$scalename] != $this->inherit_fieldlist['keytext'])
			|| ($this->keyx[$scalename] != $this->inherit_fieldlist['keyx'])
			|| ($this->keyy[$scalename] != $this->inherit_fieldlist['keyy'])))
			{
			     // sometimes a scale exists but without defaults. A proper scale object would sort this out...
			     if($this->keyx[$scalename] == '') { $this->keyx[$scalename] = -1; }
			     if($this->keyy[$scalename] == '') { $this->keyy[$scalename] = -1; }

				$output.="KEYPOS " . $scalename." ". $this->keyx[$scalename] . " " . $this->keyy[$scalename] . " " . $this->keytext[$scalename] . "\n";
            }

		if ( (isset($this->keystyle[$scalename])) &&  ($this->keystyle[$scalename] != $this->inherit_fieldlist['keystyle']['DEFAULT']) )
		{
			$extra='';
			if ( (isset($this->keysize[$scalename])) &&  ($this->keysize[$scalename] != $this->inherit_fieldlist['keysize']['DEFAULT']) )
			{
				$extra = " ".$this->keysize[$scalename];
			}
			$output.="KEYSTYLE  " . $scalename." ". $this->keystyle[$scalename] . $extra . "\n";
		}
		$locale = localeconv();
		$decimal_point = $locale['decimal_point'];

			foreach ($colours as $k => $colour)
			{
				if (!isset($colour['special']) || ! $colour['special'] )
				{
					$top = rtrim(rtrim(sprintf("%f",$colour['top']),"0"),$decimal_point);
					$bottom= rtrim(rtrim(sprintf("%f",$colour['bottom']),"0"),$decimal_point);

                                        if ($bottom > 1000) {
                                            $bottom = nice_bandwidth($colour['bottom'], $this->kilo);
                                        }

                                        if ($top > 1000) {
                                            $top = nice_bandwidth($colour['top'], $this->kilo);
                                        }

					$tag = (isset($colour['tag'])? $colour['tag']:'');

					if( ($colour['red1'] == -1) && ($colour['green1'] == -1) && ($colour['blue1'] == -1))
					{
						$output.=sprintf("SCALE %s %-4s %-4s   none   %s\n", $scalename,
							$bottom, $top, $tag);
					}
					elseif (!isset($colour['red2']))
					{
						$output.=sprintf("SCALE %s %-4s %-4s %3d %3d %3d  %s\n", $scalename,
							$bottom, $top,
							$colour['red1'],            $colour['green1'], $colour['blue1'],$tag);
					}
					else
					{
						$output.=sprintf("SCALE %s %-4s %-4s %3d %3d %3d   %3d %3d %3d    %s\n", $scalename,
							$bottom, $top,
							$colour['red1'],
							$colour['green1'],                     $colour['blue1'],
							$colour['red2'],                       $colour['green2'],
							$colour['blue2'], $tag);
					}
				}
				else { $output.=sprintf("%sCOLOR %d %d %d\n", $k, $colour['red1'], $colour['green1'],
					$colour['blue1']); }
			}
			$output .= "\n";
		}

		foreach ($this->hints as $hintname=>$hint)
		{
			$output .= "SET $hintname $hint\n";
		}
		
		// this doesn't really work right, but let's try anyway
		if($this->has_includes)
		{
			$output .= "\n# Included files\n";
			foreach ($this->included_files as $ifile)
			{
				$output .= "INCLUDE $ifile\n";
			}
		}

		$output.="\n# End of global section\n\n";

		fwrite($fd, $output);

		## fwrite($fd,$this->nodes['DEFAULT']->WriteConfig());
		## fwrite($fd,$this->links['DEFAULT']->WriteConfig());

		# fwrite($fd, "\n\n# Node definitions:\n");

		foreach (array("template","normal") as $which)
		{
			if($which == "template") fwrite($fd,"\n# TEMPLATE-only NODEs:\n");
			if($which == "normal") fwrite($fd,"\n# regular NODEs:\n");
			
			foreach ($this->nodes as $node)
			{
				if(!preg_match("/^::\s/",$node->name))
				{
					if($node->defined_in == $this->configfile)
					{

						if($which=="template" && $node->x === NULL)  { debug("TEMPLATE\n"); fwrite($fd,$node->WriteConfig()); }
						if($which=="normal" && $node->x !== NULL) { fwrite($fd,$node->WriteConfig()); }
					}
				}
			}
			
			if($which == "template") fwrite($fd,"\n# TEMPLATE-only LINKs:\n");
			if($which == "normal") fwrite($fd,"\n# regular LINKs:\n");
			
			foreach ($this->links as $link)
			{
				if(!preg_match("/^::\s/",$link->name))
				{
					if($link->defined_in == $this->configfile)
					{
						if($which=="template" && $link->a === NULL) fwrite($fd,$link->WriteConfig());
						if($which=="normal" && $link->a !== NULL) fwrite($fd,$link->WriteConfig());
					}
				}
			}
		}		

		fwrite($fd, "\n\n# That's All Folks!\n");

		fclose($fd);
	}
	else
	{
		warn ("Couldn't open config file $filename for writing");
		return (FALSE);
	}

	return (TRUE);
}

// pre-allocate colour slots for the colours used by the arrows
// this way, it's the pretty icons that suffer if there aren't enough colours, and
// not the actual useful data
// we skip any gradient scales
function AllocateScaleColours($im,$refname='gdref1')
{
	# $colours=$this->colours['DEFAULT'];
	foreach ($this->colours as $scalename=>$colours)
	{
		foreach ($colours as $key => $colour)
		{
			if ( (!isset($this->colours[$scalename][$key]['red2']) ) && (!isset( $this->colours[$scalename][$key][$refname] )) )
			{
				$r=$colour['red1'];
				$g=$colour['green1'];
				$b=$colour['blue1'];
				debug ("AllocateScaleColours: $scalename/$refname $key ($r,$g,$b)\n");
				$this->colours[$scalename][$key][$refname]=myimagecolorallocate($im, $r, $g, $b);
			}			
		}
	}
}

function DrawMap($filename = '', $thumbnailfile = '', $thumbnailmax = 250, $withnodes = TRUE, $use_via_overlay = FALSE, $use_rel_overlay=FALSE)
{
	debug("Trace: DrawMap()\n");
	metadump("# start",true);
	$bgimage=NULL;
	if($this->configfile != "")
	{
		$this->cachefile_version = crc32(file_get_contents($this->configfile));
	}
	else
	{
		$this->cachefile_version = crc32("........");
	}

	debug("Running Post-Processing Plugins...\n");
	foreach ($this->postprocessclasses as $post_class)
	{
		debug("Running $post_class"."->run()\n");
		//call_user_func_array(array($post_class, 'run'), array(&$this));
		$this->plugins['post'][$post_class]->run($this);

	}
	debug("Finished Post-Processing Plugins...\n");

	debug("=====================================\n");
	debug("Start of Map Drawing\n");

	$this->datestamp = strftime($this->stamptext, time());

	// do the basic prep work
	if ($this->background != '')
	{
		if (is_readable($this->background))
		{
			$bgimage=imagecreatefromfile($this->background);

			if (!$bgimage) { warn
				("Failed to open background image.  One possible reason: Is your BACKGROUND really a PNG?\n");
			}
			else
			{
				$this->width=imagesx($bgimage);
				$this->height=imagesy($bgimage);
			}
		}
		else { warn
			("Your background image file could not be read. Check the filename, and permissions, for "
			. $this->background . "\n"); }
	}

	$image=wimagecreatetruecolor($this->width, $this->height);

	# $image = imagecreate($this->width, $this->height);
	if (!$image) { warn
		("Couldn't create output image in memory (" . $this->width . "x" . $this->height . ")."); }
	else
	{
		ImageAlphaBlending($image, true);
		# imageantialias($image,true);

		// by here, we should have a valid image handle

		// save this away, now
		$this->image=$image;

		$this->white=myimagecolorallocate($image, 255, 255, 255);
		$this->black=myimagecolorallocate($image, 0, 0, 0);
		$this->grey=myimagecolorallocate($image, 192, 192, 192);
		$this->selected=myimagecolorallocate($image, 255, 0, 0); // for selections in the editor

		$this->AllocateScaleColours($image);

		// fill with background colour anyway, in case the background image failed to load
		wimagefilledrectangle($image, 0, 0, $this->width, $this->height, $this->colours['DEFAULT']['BG']['gdref1']);

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
			debug("Pre-rendering ".$node->name." to get bounding boxes.\n");
			if(!is_null($node->x)) $this->nodes[$node->name]->pre_render($image, $this);
		}
		
		$all_layers = array_keys($this->seen_zlayers);
		sort($all_layers);

		foreach ($all_layers as $z)
		{
			$z_items = $this->seen_zlayers[$z];
			debug("Drawing layer $z\n");
			// all the map 'furniture' is fixed at z=1000
			if($z==1000)
			{
				foreach ($this->colours as $scalename=>$colours)
				{
					debug("Drawing KEY for $scalename if necessary.\n");

					if( (isset($this->numscales[$scalename])) && (isset($this->keyx[$scalename])) && ($this->keyx[$scalename] >= 0) && ($this->keyy[$scalename] >= 0) )
					{
						if($this->keystyle[$scalename]=='classic') $this->DrawLegend_Classic($image,$scalename,FALSE);
						if($this->keystyle[$scalename]=='horizontal') $this->DrawLegend_Horizontal($image,$scalename,$this->keysize[$scalename]);
						if($this->keystyle[$scalename]=='vertical') $this->DrawLegend_Vertical($image,$scalename,$this->keysize[$scalename]);
						if($this->keystyle[$scalename]=='inverted') $this->DrawLegend_Vertical($image,$scalename,$this->keysize[$scalename],true);
						if($this->keystyle[$scalename]=='tags') $this->DrawLegend_Classic($image,$scalename,TRUE);
					}
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
					if(strtolower(get_class($it))=='weathermaplink')
					{
						// only draw LINKs if they have NODES defined (not templates)
						// (also, check if the link still exists - if this is in the editor, it may have been deleted by now)
						if ( isset($this->links[$it->name]) && isset($it->a) && isset($it->b))
						{
							debug("Drawing LINK ".$it->name."\n");
							$this->links[$it->name]->Draw($image, $this);
						}
					}
					if(strtolower(get_class($it))=='weathermapnode')
					{
						// if(!is_null($it->x)) $it->pre_render($image, $this);
						if($withnodes)
						{
							// don't try and draw template nodes
							if( isset($this->nodes[$it->name]) && !is_null($it->x))
							{
								# print "::".get_class($it)."\n";
								debug("Drawing NODE ".$it->name."\n");
								$this->nodes[$it->name]->NewDraw($image, $this);
								$ii=0;
								foreach($this->nodes[$it->name]->boundingboxes as $bbox)
								{
									# $areaname = "NODE:" . $it->name . ':'.$ii;
									$areaname = "NODE:N". $it->id . ":" . $ii;
									$this->imap->addArea("Rectangle", $areaname, '', $bbox);
									debug("Adding imagemap area");
									$ii++;
								}
								debug("Added $ii bounding boxes too\n");
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
			if ($filename == '') { imagepng ($image); }
			else {
				$result = FALSE;
				$functions = TRUE;
				if(function_exists('imagejpeg') && preg_match("/\.jpg/i",$filename))
				{
					debug("Writing JPEG file to $filename\n");
					$result = imagejpeg($image, $filename);
				}
				elseif(function_exists('imagegif') && preg_match("/\.gif/i",$filename))
				{
					debug("Writing GIF file to $filename\n");
					$result = imagegif($image, $filename);
				}
				elseif(function_exists('imagepng') && preg_match("/\.png/i",$filename))
				{
					debug("Writing PNG file to $filename\n");
					$result = imagepng($image, $filename);
				}
				else
				{
					warn("Failed to write map image. No function existed for the image format you requested. [WMWARN12]\n");
					$functions = FALSE;
				}

				if(($result==FALSE) && ($functions==TRUE))
				{
					if(file_exists($filename))
					{
						warn("Failed to overwrite existing image file $filename - permissions of existing file are wrong? [WMWARN13]");
					}
					else
					{
						warn("Failed to create image file $filename - permissions of output directory are wrong? [WMWARN14]");
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
						warn("Failed to overwrite existing image file $filename - permissions of existing file are wrong? [WMWARN15]");
					}
					else
					{
						warn("Failed to create image file $filename - permissions of output directory are wrong? [WMWARN16]");
					}
				}
			}
		}
		else
		{
			warn("Skipping thumbnail creation, since we don't have the necessary function. [WMWARN17]");
		}
		imagedestroy ($image);
	}
}

function CleanUp()
{
	// destroy all the images we created, to prevent memory leaks
	foreach ($this->nodes as $node) { if(isset($node->image)) imagedestroy($node->image); }
	#foreach ($this->nodes as $node) { unset($node); }
	#foreach ($this->links as $link) { unset($link); }

}

function PreloadMapHTML()
{
	debug("Trace: PreloadMapHTML()\n");
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
				if($type == 'LINK') $dirs = array(IN=>array(0,2), OUT=>array(1,3));
				if($type == 'NODE') $dirs = array(IN=>array(0,1,2,3));
				
				// check to see if any of the relevant things have a value
				$change = "";
				foreach ($dirs as $d=>$parts)
				{
					//print "$d - ".join(" ",$parts)."\n";
					$change .= join('',$myobj->overliburl[$d]);
					$change .= $myobj->notestext[$d];
				}
				
				if ($this->htmlstyle == "overlib")
				{
					//print "CHANGE: $change\n";

					// skip all this if it's a template node
					if($type=='LINK' && ! isset($myobj->a->name)) { $change = ''; }
					if($type=='NODE' && ! isset($myobj->x)) { $change = ''; }

					if($change != '')
					{
						//print "Something to be done.\n";
						if($type=='NODE')
						{
							$mid_x = $myobj->x;
							$mid_y = $myobj->y;
						}
						if($type=='LINK')
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
						# $areaname = $type.":" . $myobj->name . ":" . $part;
						$areaname = $type.":" . $prefix . $myobj->id. ":" . $part;
						//print "INFOURL for $areaname - ";
												
						if ( ($this->htmlstyle != 'editor') && ($myobj->infourl[$dir] != '') ) {
							$this->imap->setProp("href", $this->ProcessString($myobj->infourl[$dir],$myobj), $areaname);
							//print "Setting.\n";
						}
						else
						{
							//print "NOT Setting.\n";
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
	debug("Trace: MakeHTML()\n");
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
        $html='<map name="' . $imagemapname . '" id="' . $imagemapname . '">';

        # $html.=$this->imap->subHTML("NODE:",true);
        # $html.=$this->imap->subHTML("LINK:",true);

        $all_layers = array_keys($this->seen_zlayers);
        rsort($all_layers);

        debug("Starting to dump imagemap in reverse Z-order...\n");
        // this is not precisely efficient, but it'll get us going
        // XXX - get Imagemap to store Z order, or map items to store the imagemap
        foreach ($all_layers as $z)
        {
                debug("Writing HTML for layer $z\n");
                $z_items = $this->seen_zlayers[$z];
                if(is_array($z_items))
                {
                        debug("   Found things for layer $z\n");

                        // at z=1000, the legends and timestamps live
                        if($z == 1000) {
                            debug("     Builtins fit here.\n");
                            $html .= $this->imap->subHTML("LEGEND:",true,($this->context != 'editor'));
                            $html .= $this->imap->subHTML("TIMESTAMP",true,($this->context != 'editor'));
                        }

                        foreach($z_items as $it)
                        {
                                # print "     " . $it->name . "\n";
                                if($it->name != 'DEFAULT' && $it->name != ":: DEFAULT ::")
                                {
                                        $name = "";
                                        if(strtolower(get_class($it))=='weathermaplink') $name = "LINK:L";
                                        if(strtolower(get_class($it))=='weathermapnode') $name = "NODE:N";
                                        $name .= $it->id . ":";
                                        debug("      Writing $name from imagemap\n");
                                        // skip the linkless areas if we are in the editor - they're redundant
                                        $html .= $this->imap->subHTML($name,true,($this->context != 'editor'));
                                }
                        }
                }
        }

        $html.='</map>';

	return($html);
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

	debug("Comparing files in $cachefolder starting with $cacheprefix, with date of $configchanged\n");

	$dh=opendir($cachefolder);

	if ($dh)
	{
		while ($file=readdir($dh))
		{
			$realfile = $cachefolder . DIRECTORY_SEPARATOR . $file;

			if(is_file($realfile) && ( preg_match('/^'.$cacheprefix.'/',$file) ))
				//                                            if (is_file($realfile) )
			{
				debug("$realfile\n");
				if( (filemtime($realfile) < $configchanged) || ((time() - filemtime($realfile)) > $agelimit) )
				{
					debug("Cache: deleting $realfile\n");
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
	else { debug("Couldn't read cache folder.\n"); }
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

};
// vim:ts=4:sw=4:
?>
