<?php
// PHP Weathermap 0.97a
// Copyright Howard Jones, 2005-2010 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License

require_once "HTML_ImageMap.class.php";

class WeatherMapLink extends WeatherMapItem
{
	var $owner,                $name;
	var $id;
	var $maphtml;
	var $a,                    $b; // the ends - references to nodes
	var $width,                $arrowstyle, $linkstyle;
	var $bwfont,               $labelstyle, $labelboxstyle;
	var $zorder;
	var $overliburl = array();
	var $infourl = array();
	var $notes;
	var $overlibcaption = array();
	var $overlibwidth,         $overlibheight;
	var $bandwidth_in,         $bandwidth_out;
	var $max_bandwidth_in,     $max_bandwidth_out;
	var $max_bandwidth_in_cfg, $max_bandwidth_out_cfg;
	var $targets = array();
	var $a_offset,             $b_offset;
	var $in_ds,                $out_ds;
	var $colours = array();
	var $selected;
	var $inpercent,            $outpercent;
	var $inherit_fieldlist;
	var $vialist = array();
	var $viastyle;
	var $usescale, $duplex; 
	var $scaletype;
	var $outlinecolour;
	var $bwoutlinecolour;
	var $bwboxcolour;
	var $splitpos;
	var $commentfont;
	var $notestext = array();
	var $inscalekey,$outscalekey;
	var $inscaletag, $outscaletag;
	# var $incolour,$outcolour;
	var $commentfontcolour;
	var $commentstyle;
	var $bwfontcolour;
	# var $incomment, $outcomment;
	var $comments = array();
	var $bwlabelformats = array();
	var $curvepoints;
	var $labeloffset_in, $labeloffset_out;
	var $commentoffset_in, $commentoffset_out;
	var $template;

	function WeatherMapLink() { $this->inherit_fieldlist=array
		(
			'my_default' => NULL,
			'width' => 7,
			'commentfont' => 1,
			'bwfont' => 2,
			'template' => ':: DEFAULT ::',
			'splitpos'=>50,
			'labeloffset_out' => 25,
			'labeloffset_in' => 75,
			'commentoffset_out' => 5,
			'commentoffset_in' => 95,
			'commentstyle' => 'edge',
			'arrowstyle' => 'classic',
			'viastyle' => 'curved',
			'usescale' => 'DEFAULT',
			'scaletype' => 'percent',
			'targets' => array(),
			'duplex' => 'full',
			'infourl' => array('',''),
			'notes' => array(),
			'hints' => array(),
			'comments' => array('',''),
			'bwlabelformats' => array(FMT_PERC_IN,FMT_PERC_OUT),
			'overliburl' => array(array(),array()),
			'notestext' => array(IN=>'',OUT=>''),
			'labelstyle' => 'percent',
			'labelboxstyle' => 'classic',
			'linkstyle' => 'twoway',
			'overlibwidth' => 0,
			'overlibheight' => 0,
			'outlinecolour' => array(0, 0, 0),
			'bwoutlinecolour' => array(0, 0, 0),
			'bwfontcolour' => array(0, 0, 0),
			'bwboxcolour' => array(255, 255, 255),
			'commentfontcolour' => array(192,192,192),
			'inpercent'=>0, 'outpercent'=>0,
			'inscalekey'=>'', 'outscalekey'=>'',
			# 'incolour'=>-1,'outcolour'=>-1,
			'a_offset' => 'C',
			'b_offset' => 'C',
			#'incomment' => '',
			#'outcomment' => '',
			'zorder' => 300,
			'overlibcaption' => array('',''),
			'max_bandwidth_in' => 100000000,
			'max_bandwidth_out' => 100000000,
			'max_bandwidth_in_cfg' => '100M',
			'max_bandwidth_out_cfg' => '100M'
		);
	// $this->a_offset = 'C';
	// $this->b_offset = 'C';
	//  $this->targets = array();
	}

	function Reset(&$newowner)
	{
		$this->owner=$newowner;
		
		$template = $this->template;
                if($template == '') $template = "DEFAULT";

		debug("Resetting $this->name with $template\n");

		// the internal default-default gets it's values from inherit_fieldlist
		// everything else comes from a link object - the template.
		if($this->name==':: DEFAULT ::')
		{
			foreach (array_keys($this->inherit_fieldlist) as $fld) {
				$this->$fld=$this->inherit_fieldlist[$fld];
			}
		}
		else
		{
			$this->CopyFrom($this->owner->links[$template]); 
		}
		$this->template = $template;
		
		// to stop the editor tanking, now that colours are decided earlier in ReadData
		$this->colours[IN] = new Colour(192,192,192);
		$this->colours[OUT] = new Colour(192,192,192);
		$this->id = $newowner->next_id++;
	}

	function my_type() {  return "LINK"; }

	function CopyFrom(&$source)
	{
		debug("Initialising LINK $this->name from $source->name\n");
		assert('is_object($source)');
				
		foreach (array_keys($this->inherit_fieldlist) as $fld) {
			 if($fld != 'template') $this->$fld = $source->$fld;
		}
	}

// image = GD image references
// col = array of Colour objects
// widths = array of link widths
	function DrawComments($image,$col,$widths)
	{
		$curvepoints =& $this->curvepoints;
		$last = count($curvepoints)-1;
				
		$totaldistance = $curvepoints[$last][2];
				
		$start[OUT] = 0;
		$commentpos[OUT] = $this->commentoffset_out;
		$commentpos[IN] = $this->commentoffset_in;
		$start[IN] = $last;
		
		if($this->linkstyle=="oneway")
		{
			$dirs = array(OUT);
		}
		else
		{
			$dirs = array(OUT,IN);
		}
		
		foreach ($dirs as $dir)
		{
			
		
			// Time to deal with Link Comments, if any
			$comment = $this->owner->ProcessString($this->comments[$dir], $this);
			
			# print "COMMENT: $comment";
			
			if($this->owner->get_hint('screenshot_mode')==1)  $comment=screenshotify($comment);
	
			if($comment != '')
			{
				# print "\n\n----------------------------------------------------------------\nComment $dir for ".$this->name."\n";;
			
				list($textlength, $textheight) = $this->owner->myimagestringsize($this->commentfont, $comment);
				
				$extra_percent = $commentpos[$dir];
				
				// $font = $this->commentfont;
				// nudge pushes the comment out along the link arrow a little bit
				// (otherwise there are more problems with text disappearing underneath links)
				# $nudgealong = 0; $nudgeout=0;
				$nudgealong = intval($this->get_hint("comment_nudgealong"));
				$nudgeout = intval($this->get_hint("comment_nudgeout"));		
	
				$extra = ($totaldistance * ($extra_percent/100));
				# $comment_index = find_distance($curvepoints,$extra);
				
				list($x,$y,$comment_index,$angle) = find_distance_coords_angle($curvepoints,$extra);
							
				#  print "$extra_percent => $extra ($totaldistance)\n";
				#printf("  Point A is %f,%f\n",$curvepoints[$comment_index][0], $curvepoints[$comment_index][1]);
				#printf("  Point B is %f,%f\n",$curvepoints[$comment_index+1][0], $curvepoints[$comment_index+1][1]);
				#printf("  Point X is %f,%f\n",$x, $y);
							
				# if( ($comment_index != 0)) print "I ";
				# if (($x != $curvepoints[$comment_index][0]) ) print "X ";
				# if (($y != $curvepoints[$comment_index][1]) ) print "Y ";
				# print "\n";
							
				if( ($comment_index != 0) && (($x != $curvepoints[$comment_index][0]) || ($y != $curvepoints[$comment_index][1])) )
				{
				#	print "  -> Path 1\n";
					$dx = $x - $curvepoints[$comment_index][0];
					$dy = $y - $curvepoints[$comment_index][1];
				}
				else
				{			
				#	print "  -> Path 2\n";
					$dx = $curvepoints[$comment_index+1][0] - $x;
					$dy = $curvepoints[$comment_index+1][1] - $y;
				}
								
				$centre_distance = $widths[$dir] + 4 + $nudgeout;
				if($this->commentstyle == 'center')
				{
					$centre_distance = $nudgeout - ($textheight/2);
				}
								
				// find the normal to our link, so we can get outside the arrow
				
				$l=sqrt(($dx * $dx) + ($dy * $dy));
				
				# print "$extra => $comment_index/$last => $x,$y => $dx,$dy => $l\n";
				
				$dx = $dx/$l; 	$dy = $dy/$l;
				$nx = $dy;  $ny = -$dx;
				$flipped=FALSE;
				
				// if the text will be upside-down, rotate it, flip it, and right-justify it
				// not quite as catchy as Missy's version
				if(abs($angle)>90)
				{
					# $col = $map->selected;
					$angle -= 180;
					if($angle < -180) $angle +=360;
					$edge_x = $x + $nudgealong*$dx - $nx * $centre_distance;
					$edge_y = $y + $nudgealong*$dy - $ny * $centre_distance;
					# $comment .= "@";
					$flipped = TRUE;
				}
				else
				{
					$edge_x = $x + $nudgealong*$dx + $nx * $centre_distance;
					$edge_y = $y + $nudgealong*$dy + $ny * $centre_distance;
				}
				
				
				
				if( !$flipped && ($extra + $textlength) > $totaldistance)
				{					
					$edge_x -= $dx * $textlength;
					$edge_y -= $dy * $textlength;
					# $comment .= "#";
				}
				
				if( $flipped && ($extra - $textlength) < 0)
				{					
					$edge_x += $dx * $textlength;
					$edge_y += $dy * $textlength;
					# $comment .= "%";
				}
				
				// FINALLY, draw the text!
				# imagefttext($image, $fontsize, $angle, $edge_x, $edge_y, $col, $font,$comment);
				$this->owner->myimagestring($image, $this->commentfont, $edge_x, $edge_y, $comment, $col[$dir], $angle);
				#imagearc($image,$x,$y,10,10,0, 360,$this->owner->selected);
				#imagearc($image,$edge_x,$edge_y,10,10,0, 360,$this->owner->selected);
			}
		}
	}

	function Draw($im, &$map)
	{
		// Get the positions of the end-points
		$x1=$map->nodes[$this->a->name]->x;
		$y1=$map->nodes[$this->a->name]->y;

		$x2=$map->nodes[$this->b->name]->x;
		$y2=$map->nodes[$this->b->name]->y;
		
		if(is_null($x1)) { warn("LINK ".$this->name." uses a NODE with no POSITION! [WMWARN35]\n"); return; }
		if(is_null($y1)) { warn("LINK ".$this->name." uses a NODE with no POSITION! [WMWARN35]\n"); return; }
		if(is_null($x2)) { warn("LINK ".$this->name." uses a NODE with no POSITION! [WMWARN35]\n"); return; }
		if(is_null($y2)) { warn("LINK ".$this->name." uses a NODE with no POSITION! [WMWARN35]\n"); return; }
		
		
		if( ($this->linkstyle=='twoway') && ($this->labeloffset_in < $this->labeloffset_out) && (intval($map->get_hint("nowarn_bwlabelpos"))==0) )
		{
			warn("LINK ".$this->name." probably has it's BWLABELPOSs the wrong way around [WMWARN50]\n");
		}
		
		list($dx, $dy)=calc_offset($this->a_offset, $map->nodes[$this->a->name]->width, $map->nodes[$this->a->name]->height);
		$x1+=$dx;
		$y1+=$dy;

		list($dx, $dy)=calc_offset($this->b_offset, $map->nodes[$this->b->name]->width, $map->nodes[$this->b->name]->height);
		$x2+=$dx;
		$y2+=$dy;

		if( ($x1==$x2) && ($y1==$y2) && sizeof($this->vialist)==0)
		{
			warn("Zero-length link ".$this->name." skipped. [WMWARN45]");
			return;
		}

		$outlinecol = new Colour($this->outlinecolour);
		$commentcol = new Colour($this->commentfontcolour);
		
		$outline_colour = $outlinecol->gdallocate($im);
				
		$xpoints = array ( );
		$ypoints = array ( );

		$xpoints[]=$x1;
		$ypoints[]=$y1;

		# warn("There are VIAs.\n");
		foreach ($this->vialist as $via)
		{
			# imagearc($im, $via[0],$via[1],20,20,0,360,$map->selected);
			if(isset($via[2]))
			{
				$xpoints[]=$map->nodes[$via[2]]->x + $via[0];
				$ypoints[]=$map->nodes[$via[2]]->y + $via[1];
			}
			else
			{
				$xpoints[]=$via[0];
				$ypoints[]=$via[1];
			}
		}

		$xpoints[]=$x2;
		$ypoints[]=$y2;

		# list($link_in_colour,$link_in_scalekey, $link_in_scaletag) = $map->NewColourFromPercent($this->inpercent,$this->usescale,$this->name);
		# list($link_out_colour,$link_out_scalekey, $link_out_scaletag) = $map->NewColourFromPercent($this->outpercent,$this->usescale,$this->name);
		
		$link_in_colour = $this->colours[IN];
		$link_out_colour = $this->colours[OUT];
		
		$gd_in_colour = $link_in_colour->gdallocate($im);
		$gd_out_colour = $link_out_colour->gdallocate($im);
		
	//	$map->links[$this->name]->inscalekey = $link_in_scalekey;
	//	$map->links[$this->name]->outscalekey = $link_out_scalekey;
		
		$link_width=$this->width;
		// these will replace the one above, ultimately.
		$link_in_width=$this->width;
		$link_out_width=$this->width;
			
		// for bulging animations
		if ( ($map->widthmod) || ($map->get_hint('link_bulge') == 1))
		{
			// a few 0.1s and +1s to fix div-by-zero, and invisible links
			$link_width = (($link_width * $this->inpercent * 1.5 + 0.1) / 100) + 1;
			// these too
			$link_in_width = (($link_in_width * $this->inpercent * 1.5 + 0.1) / 100) + 1;
			$link_out_width = (($link_out_width * $this->outpercent * 1.5 + 0.1) / 100) + 1;
		}


		if($this->viastyle=='curved')
		{
			// Calculate the spine points - the actual curve	
			$this->curvepoints = calc_curve($xpoints, $ypoints);
										
			// then draw the curve itself
			draw_curve($im, $this->curvepoints,
				array($link_in_width,$link_out_width), $outline_colour, array($gd_in_colour, $gd_out_colour),
				$this->name, $map, $this->splitpos, ($this->linkstyle=='oneway'?TRUE:FALSE) );
		}
		
		if($this->viastyle=='angled')
		{
			// Calculate the spine points - the actual not a curve really, but we
			// need to create the array, and calculate the distance bits, otherwise
			// things like bwlabels won't know where to go.
			
			$this->curvepoints = calc_straight($xpoints, $ypoints);
							
			// then draw the "curve" itself
			draw_straight($im, $this->curvepoints,
				array($link_in_width,$link_out_width), $outline_colour, array($gd_in_colour, $gd_out_colour),
				$this->name, $map, $this->splitpos, ($this->linkstyle=='oneway'?TRUE:FALSE) );
		}

		if ( !$commentcol->is_none() )
		{			
			if($commentcol->is_contrast())
			{
				$commentcol_in = $link_in_colour->contrast();
				$commentcol_out = $link_out_colour->contrast();
			}
			else
			{
				$commentcol_in = $commentcol;
				$commentcol_out = $commentcol;
			}
		
			$comment_colour_in = $commentcol_in->gdallocate($im);
			$comment_colour_out = $commentcol_out->gdallocate($im);
			
			$this->DrawComments($im,array($comment_colour_in, $comment_colour_out),array($link_in_width*1.1,$link_out_width*1.1));
		}

		$curvelength = $this->curvepoints[count($this->curvepoints)-1][2];
		// figure out where the labels should be, and what the angle of the curve is at that point
		list($q1_x,$q1_y,$junk,$q1_angle) = find_distance_coords_angle($this->curvepoints,($this->labeloffset_out/100)*$curvelength);
		list($q3_x,$q3_y,$junk,$q3_angle) = find_distance_coords_angle($this->curvepoints,($this->labeloffset_in/100)*$curvelength);

		# imageline($im, $q1_x+20*cos(deg2rad($q1_angle)),$q1_y-20*sin(deg2rad($q1_angle)), $q1_x-20*cos(deg2rad($q1_angle)), $q1_y+20*sin(deg2rad($q1_angle)), $this->owner->selected );
		# imageline($im, $q3_x+20*cos(deg2rad($q3_angle)),$q3_y-20*sin(deg2rad($q3_angle)), $q3_x-20*cos(deg2rad($q3_angle)), $q3_y+20*sin(deg2rad($q3_angle)), $this->owner->selected );

		# warn("$q1_angle $q3_angle\n");

		if (!is_null($q1_x))
		{
			$outbound=array
				(
					$q1_x,
					$q1_y,
					0,
					0,
					$this->outpercent,
					$this->bandwidth_out,
					$q1_angle,
					OUT
				);

			$inbound=array
				(
					$q3_x,
					$q3_y,
					0,
					0,
					$this->inpercent,
					$this->bandwidth_in,
					$q3_angle,
					IN
				);

			if ($map->sizedebug)
			{
				$outbound[5]=$this->max_bandwidth_out;
				$inbound[5]=$this->max_bandwidth_in;
			}
			
			
			if($this->linkstyle=='oneway')
			{
				$tasks = array($outbound);
			}
			else
			{
				$tasks = array($inbound,$outbound);
			}

			foreach ($tasks as $task)
			{
				$thelabel="";
				
				$thelabel = $map->ProcessString($this->bwlabelformats[$task[7]],$this);
	
				if ($thelabel != '')
				{
					debug("Bandwidth for label is ".$task[5]."\n");
					
					$padding = intval($this->get_hint('bwlabel_padding'));		
					
					// if screenshot_mode is enabled, wipe any letters to X and wipe any IP address to 127.0.0.1
					// hopefully that will preserve enough information to show cool stuff without leaking info
					if($map->get_hint('screenshot_mode')==1)  $thelabel = screenshotify($thelabel);

					if($this->labelboxstyle == 'angled')
					{
						$angle = $task[6];
					}
					else
					{
						$angle = 0;
					}
					
					$map->DrawLabelRotated($im, $task[0],            $task[1],$angle,           $thelabel, $this->bwfont, $padding,
							$this->name,  $this->bwfontcolour, $this->bwboxcolour, $this->bwoutlinecolour,$map, $task[7]);
					
					// imagearc($im, $task[0], $task[1], 10,10,0,360,$map->selected);
				}
			}
		}
	}

	function WriteConfig()
	{
		$output='';
		# $output .= "# ID ".$this->id." - first seen in ".$this->defined_in."\n";

		if($this->config_override != '')
		{
			$output = $this->config_override."\n";
		}
		else
		{
			# $defdef = $this->owner->defaultlink;
			$dd = $this->owner->links[$this->template];
			
			debug("Writing config for LINK $this->name against $this->template\n");
						
			$basic_params = array(
					array('width','WIDTH',CONFIG_TYPE_LITERAL),
					array('zorder','ZORDER',CONFIG_TYPE_LITERAL),
					array('overlibwidth','OVERLIBWIDTH',CONFIG_TYPE_LITERAL),
					array('overlibheight','OVERLIBHEIGHT',CONFIG_TYPE_LITERAL),
					array('arrowstyle','ARROWSTYLE',CONFIG_TYPE_LITERAL),
					array('viastyle','VIASTYLE',CONFIG_TYPE_LITERAL),
					array('linkstyle','LINKSTYLE',CONFIG_TYPE_LITERAL),
					array('splitpos','SPLITPOS',CONFIG_TYPE_LITERAL),
					array('duplex','DUPLEX',CONFIG_TYPE_LITERAL),
					array('commentstyle','COMMENTSTYLE',CONFIG_TYPE_LITERAL),
					array('labelboxstyle','BWSTYLE',CONFIG_TYPE_LITERAL),
					array('usescale','USESCALE',CONFIG_TYPE_LITERAL),
					
					array('bwfont','BWFONT',CONFIG_TYPE_LITERAL),
					array('commentfont','COMMENTFONT',CONFIG_TYPE_LITERAL),
					
					array('bwoutlinecolour','BWOUTLINECOLOR',CONFIG_TYPE_COLOR),
					array('bwboxcolour','BWBOXCOLOR',CONFIG_TYPE_COLOR),
					array('outlinecolour','OUTLINECOLOR',CONFIG_TYPE_COLOR),
					array('commentfontcolour','COMMENTFONTCOLOR',CONFIG_TYPE_COLOR),
					array('bwfontcolour','BWFONTCOLOR',CONFIG_TYPE_COLOR)
				);
			
			# TEMPLATE must come first. DEFAULT
			if($this->template != 'DEFAULT' && $this->template != ':: DEFAULT ::')
			{
				$output.="\tTEMPLATE " . $this->template . "\n";
			}
			
			foreach ($basic_params as $param)
			{
				$field = $param[0];
				$keyword = $param[1];
						
				# $output .= "# For $keyword: ".$this->$field." vs ".$dd->$field."\n";
				if ($this->$field != $dd->$field) 
				#if (1==1)
				{ 
					if($param[2] == CONFIG_TYPE_COLOR) $output.="\t$keyword " . render_colour($this->$field) . "\n"; 
					if($param[2] == CONFIG_TYPE_LITERAL) $output.="\t$keyword " . $this->$field . "\n"; 
				}
			}		
		
			if ($this->infourl[IN] == $this->infourl[OUT]) {
				$dirs = array(IN=>""); // only use the IN value, since they're both the same, but don't prefix the output keyword
			} else {
				$dirs = array( IN=>"IN", OUT=>"OUT" );// the full monty two-keyword version
			}
						
			foreach ($dirs as $dir=>$tdir) {
				if ($this->infourl[$dir] != $dd->infourl[$dir]) { 
					$output .= "\t" . $tdir . "INFOURL " . $this->infourl[$dir] . "\n"; 
				}
			}
			
			if ($this->overlibcaption[IN] == $this->overlibcaption[OUT]) {
				$dirs = array(IN=>""); // only use the IN value, since they're both the same, but don't prefix the output keyword
			} else {
				$dirs = array( IN=>"IN", OUT=>"OUT" );// the full monty two-keyword version
			}
						
			foreach ($dirs as $dir=>$tdir) {
				if ($this->overlibcaption[$dir] != $dd->overlibcaption[$dir]) { 
					$output .= "\t".$tdir."OVERLIBCAPTION " . $this->overlibcaption[$dir] . "\n"; 
				}
			}
		
			if ($this->notestext[IN] == $this->notestext[OUT]) {
				$dirs = array(IN=>""); // only use the IN value, since they're both the same, but don't prefix the output keyword
			} else { 
				$dirs = array( IN=>"IN", OUT=>"OUT" );// the full monty two-keyword version
			}
	
			foreach ($dirs as $dir=>$tdir) {
				if ($this->notestext[$dir] != $dd->notestext[$dir]) {
					$output .= "\t" . $tdir . "NOTES " . $this->notestext[$dir] . "\n"; 
				}
			}
				
			if ($this->overliburl[IN]==$this->overliburl[OUT]) {
				$dirs = array(IN=>""); // only use the IN value, since they're both the same, but don't prefix the output keyword
			} else {
				$dirs = array( IN=>"IN", OUT=>"OUT" );// the full monty two-keyword version
			}
			
			foreach ($dirs as $dir=>$tdir) {
				if ($this->overliburl[$dir] != $dd->overliburl[$dir]) { 
					$output.="\t".$tdir."OVERLIBGRAPH " . join(" ",$this->overliburl[$dir]) . "\n"; 
				}
			}	
			
			// if formats have been set, but they're just the longform of the built-in styles, set them back to the built-in styles
			if($this->labelstyle=='--' && $this->bwlabelformats[IN] == FMT_PERC_IN && $this->bwlabelformats[OUT] == FMT_PERC_OUT)
			{
				$this->labelstyle = 'percent';
			}
			if($this->labelstyle=='--' && $this->bwlabelformats[IN] == FMT_BITS_IN && $this->bwlabelformats[OUT] == FMT_BITS_OUT)
			{
				$this->labelstyle = 'bits';
			}
			if($this->labelstyle=='--' && $this->bwlabelformats[IN] == FMT_UNFORM_IN && $this->bwlabelformats[OUT] == FMT_UNFORM_OUT)
			{
				$this->labelstyle = 'unformatted';
			}

			// if specific formats have been set, then the style will be '--'
			// if it isn't then use the named style
			if ( ($this->labelstyle != $dd->labelstyle) && ($this->labelstyle != '--') ) { 
				$output .= "\tBWLABEL " . $this->labelstyle . "\n"; 
			}
						
			// if either IN or OUT field changes, then both must be written because a regular BWLABEL can't do it
			// XXX this looks wrong
			$comparison = $dd->bwlabelformats[IN];
			$comparison2 = $dd->bwlabelformats[OUT];
						
			if ( ( $this->labelstyle == '--') && ( ($this->bwlabelformats[IN] != $comparison) || ($this->bwlabelformats[OUT]!= '--')) )
			{
				$output .= "\tINBWFORMAT " . $this->bwlabelformats[IN]. "\n";
				$output .= "\tOUTBWFORMAT " . $this->bwlabelformats[OUT]. "\n";
			}
	
			$comparison = $dd->labeloffset_in;
			$comparison2 = $dd->labeloffset_out;
	
			if ( ($this->labeloffset_in != $comparison) || ($this->labeloffset_out != $comparison2) )
			{ $output.="\tBWLABELPOS " . $this->labeloffset_in . " " . $this->labeloffset_out . "\n"; }
	
			$comparison=$dd->commentoffset_in.":".$dd->commentoffset_out;
			$mine = $this->commentoffset_in.":".$this->commentoffset_out;
			if ($mine != $comparison) { $output.="\tCOMMENTPOS " . $this->commentoffset_in." ".$this->commentoffset_out. "\n"; }
	
	
			$comparison=$dd->targets;
	
			if ($this->targets != $comparison) {
				$output.="\tTARGET";
	
				foreach ($this->targets as $target) { 
					if(strpos($target[4]," ") == FALSE) {
						$output .= " " . $target[4]; 
					} else {
						$output .= ' "' . $target[4] . '"'; 
					}
				}	
				$output .= "\n";
			}
				
			foreach (array(IN,OUT) as $dir) {
				if ($dir==IN) { 
					$tdir="IN";
				}
				if ($dir==OUT) { 
					$tdir="OUT";
				}
				
				$comparison=$dd->comments[$dir];
				if ($this->comments[$dir] != $comparison) { 
					$output .= "\t" . $tdir . "COMMENT " . $this->comments[$dir] . "\n"; 
				}
			}
					
			if (isset($this->a) && isset($this->b))	{
				$output .= "\tNODES " . $this->a->name;
	
				if ($this->a_offset != 'C') {
					$output .= ":" . $this->a_offset;
				}
	
				$output .= " " . $this->b->name;
	
				if ($this->b_offset != 'C') {
					$output .= ":" . $this->b_offset;
				}
	
				$output .= "\n";
			}
	
			if (count($this->vialist) > 0) {
				foreach ($this->vialist as $via) {
					if( isset($via[2])) {
						$output .= sprintf("\tVIA %s %d %d\n", $via[2],$via[0], $via[1]);
					} else {
						$output .= sprintf("\tVIA %d %d\n", $via[0], $via[1]);
					}
				}
			}
	
			if (($this->max_bandwidth_in != $dd->max_bandwidth_in)
				|| ($this->max_bandwidth_out != $dd->max_bandwidth_out)
					|| ($this->name == 'DEFAULT'))
			{
				if ($this->max_bandwidth_in == $this->max_bandwidth_out)
				{ $output.="\tBANDWIDTH " . $this->max_bandwidth_in_cfg . "\n"; }
				else { $output
				.="\tBANDWIDTH " . $this->max_bandwidth_in_cfg . " " . $this->max_bandwidth_out_cfg . "\n"; }
			}
		
			foreach ($this->hints as $hintname=>$hint)
			{
			  // all hints for DEFAULT node are for writing
			  // only changed ones, or unique ones, otherwise
			      if( 
			    ($this->name == 'DEFAULT')
			  ||
				    (isset($dd->hints[$hintname]) 
				    &&
				    $dd->hints[$hintname] != $hint)
				  ||
				    (!isset($dd->hints[$hintname]))
				)
			      {		      
			    $output .= "\tSET $hintname $hint\n";
			      }
			}
	
			if ($output != '') {
				$output = "LINK " . $this->name . "\n".$output."\n";
			}
		}
		return($output);
	}

	function asJS()
	{
		$js='';
		$js.="Links[" . js_escape($this->name) . "] = {";
		$js .= "\"id\":" . $this->id. ", ";

		if (isset($this->a))
		{
			$js.="a:'" . $this->a->name . "', ";
			$js.="b:'" . $this->b->name . "', ";
		}

		$js.="width:'" . $this->width . "', ";
		$js.="target:";

		$tgt='';

		foreach ($this->targets as $target) { 
			if(strpos($target[4]," ") == FALSE) {
				$tgt .= $target[4] . ' ';
			} else {
				$tgt .= '"'.$target[4] . '" ';
			}
		}

		$js.=js_escape(trim($tgt));
		$js.=",";

		$js.="bw_in:" . js_escape($this->max_bandwidth_in_cfg) . ", ";
		$js.="bw_out:" . js_escape($this->max_bandwidth_out_cfg) . ", ";

		$js.="name:" . js_escape($this->name) . ", ";
		$js.="overlibwidth:'" . $this->overlibheight . "', ";
		$js.="overlibheight:'" . $this->overlibwidth . "', ";
		$js.="overlibcaption:" . js_escape($this->overlibcaption[IN]) . ", ";
		
		$js.="commentin:" . js_escape($this->comments[IN]) . ", ";
		$js.="commentposin:" . intval($this->commentoffset_in) . ", ";
		
		$js.="commentout:" . js_escape($this->comments[OUT]) . ", ";
		$js.="commentposout:" . intval($this->commentoffset_out) . ", ";

		$js.="infourl:" . js_escape($this->infourl[IN]) . ", ";
		$js.="overliburl:" . js_escape(join(" ",$this->overliburl[IN]));
		
		$js.="};\n";
		$js .= "LinkIDs[\"L" . $this->id . "\"] = ". js_escape($this->name) . ";\n";
		return $js;
	}

	function asJSON($complete=TRUE)
	{
		$js = '';
		$js .= "" . js_escape($this->name) . ": {";
		$js .= "\"id\":" . $this->id. ", ";
		if (isset($this->a)) 
		{
			$js.="\"a\":\"" . $this->a->name . "\", ";
			$js.="\"b\":\"" . $this->b->name . "\", ";
		}

		if($complete)
		{
			$js.="\"infourl\":" . js_escape($this->infourl) . ", ";
			$js.="\"overliburl\":" . js_escape($this->overliburl). ", ";
			$js.="\"width\":\"" . $this->width . "\", ";
			$js.="\"target\":";
	
			$tgt="";
	
			foreach ($this->targets as $target) { $tgt.=$target[4] . " "; }
	
			$js.=js_escape(trim($tgt));
			$js.=",";
	
			$js.="\"bw_in\":" . js_escape($this->max_bandwidth_in_cfg) . ", ";
			$js.="\"bw_out\":" . js_escape($this->max_bandwidth_out_cfg) . ", ";
	
			$js.="\"name\":" . js_escape($this->name) . ", ";
			$js.="\"overlibwidth\":\"" . $this->overlibheight . "\", ";
			$js.="\"overlibheight\":\"" . $this->overlibwidth . "\", ";
			$js.="\"overlibcaption\":" . js_escape($this->overlibcaption) . ", ";
		}
		$vias = "\"via\": [";
		foreach ($this->vialist as $via)
				$vias .= sprintf("[%d,%d,'%s'],", $via[0], $via[1],$via[2]);
		$vias .= "],";
		$vias = str_replace("],],", "]]", $vias);
		$vias = str_replace("[],", "[]", $vias);
		$js .= $vias;

		$js.="},\n";
		return $js;
	}
};

// vim:ts=4:sw=4:
?>
