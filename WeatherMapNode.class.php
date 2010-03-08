<?php
// PHP Weathermap 0.97a
// Copyright Howard Jones, 2005-2010 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License

require_once "HTML_ImageMap.class.php";

class WeatherMapNode extends WeatherMapItem
{
	var $owner;
	var $id;
	var $x,	$y;
	var $original_x, $original_y,$relative_resolved;
	var $width, $height;
	var $label, $proclabel, $labelfont;
	var $labelangle;
	var $name;
	var $infourl = array();
	var $notes;
	var $colours = array();
	var $overliburl;
	var $overlibwidth, $overlibheight;
	var $overlibcaption = array();
	var $maphtml;
	var $selected = 0;
	var $iconfile, $iconscalew, $iconscaleh;
	var $targets = array();
	var $bandwidth_in, $bandwidth_out;
	var $inpercent, $outpercent;
	var $max_bandwidth_in, $max_bandwidth_out;
	var $max_bandwidth_in_cfg, $max_bandwidth_out_cfg;
	var $labeloffset, $labeloffsetx, $labeloffsety;

	var $inherit_fieldlist;

	var $labelbgcolour;
	var $labeloutlinecolour;
	var $labelfontcolour;
	var $labelfontshadowcolour;
	var $cachefile;
	var $usescale;
	var $useiconscale;
	var $scaletype, $iconscaletype;
	var $inscalekey,$outscalekey;
	var $inscaletag, $outscaletag;
	# var $incolour,$outcolour;
	var $scalevar, $iconscalevar;
	var $notestext = array();
	var $image;
	var $centre_x, $centre_y;
	var $relative_to;
	var $zorder;
	var $template;
	var $polar;
	var $boundingboxes=array();

	function WeatherMapNode()
	{
		$this->inherit_fieldlist=array
			(
				'boundingboxes'=>array(),
				'my_default' => NULL,
				'label' => '',
				'proclabel' => '',
				'usescale' => 'DEFAULT',
				'scaletype' => 'percent',
				'iconscaletype' => 'percent',
				'useiconscale' => 'none',
				'scalevar' => 'in',
				'template' => ':: DEFAULT ::',
				'iconscalevar' => 'in',
				'labelfont' => 3,
				'relative_to' => '',
				'relative_resolved' => FALSE,
				'x' => NULL,
				'y' => NULL,
				'inscalekey'=>'', 'outscalekey'=>'',
				#'incolour'=>-1,'outcolour'=>-1,
				'original_x' => 0,
				'original_y' => 0,
				'inpercent'=>0,
				'outpercent'=>0,
				'labelangle'=>0,
				'iconfile' => '',
				'iconscalew' => 0,
				'iconscaleh' => 0,
				'targets' => array(),
				'infourl' => array(IN=>'',OUT=>''),
				'notestext' => array(IN=>'',OUT=>''),
				'notes' => array(),
				'hints' => array(),
				'overliburl' => array(IN=>array(),OUT=>array()),
				'overlibwidth' => 0,
				'overlibheight' => 0,
				'overlibcaption' => array(IN=>'',OUT=>''),
				'labeloutlinecolour' => array(0, 0, 0),
				'labelbgcolour' => array(255, 255, 255),
				'labelfontcolour' => array(0, 0, 0),
				'labelfontshadowcolour' => array(-1, -1, -1),
				'aiconoutlinecolour' => array(0,0,0),
				'aiconfillcolour' => array(-2,-2,-2), // copy from the node label
				'labeloffset' => '',
				'labeloffsetx' => 0,
				'labeloffsety' => 0,
				'zorder' => 600,
				'max_bandwidth_in' => 100,
				'max_bandwidth_out' => 100,
				'max_bandwidth_in_cfg' => '100',
				'max_bandwidth_out_cfg' => '100'
			);

		$this->width = 0;
		$this->height = 0;
		$this->centre_x = 0;
		$this->centre_y = 0;
		$this->polar = FALSE;
		$this->image = NULL;
	}

	function my_type() {  return "NODE"; }

	// make a mini-image, containing this node and nothing else
	// figure out where the real NODE centre is, relative to the top-left corner.
	function pre_render($im, &$map)
	{
		// don't bother drawing if there's no position - it's a template
		if( is_null($this->x) ) return;
		if( is_null($this->y) ) return;
		
		// apparently, some versions of the gd extension will crash
		// if we continue...
		if($this->label == '' && $this->iconfile=='') return;

		// start these off with sensible values, so that bbox
		// calculations are easier.
		$icon_x1 = $this->x; $icon_x2 = $this->x;
		$icon_y1 = $this->y; $icon_y2 = $this->y;
		$label_x1 = $this->x; $label_x2 = $this->x;
		$label_y1 = $this->y; $label_y2 = $this->y;
		$boxwidth = 0; $boxheight = 0;
		$icon_w = 0;
		$icon_h = 0;

		$col = new Colour(-1,-1,-1);
		# print $col->as_string();

		// if a target is specified, and you haven't forced no background, then the background will
		// come from the SCALE in USESCALE
		if( !empty($this->targets) && $this->usescale != 'none' )
		{
			$pc = 0;

			if($this->scalevar == 'in')
			{
				$pc = $this->inpercent;
				$col = $this->colours[IN];

			}
			
			if($this->scalevar == 'out')
			{
				$pc = $this->outpercent;
				$col = $this->colours[OUT];

			}
		}
		elseif($this->labelbgcolour != array(-1,-1,-1))
		{
			// $col=myimagecolorallocate($node_im, $this->labelbgcolour[0], $this->labelbgcolour[1], $this->labelbgcolour[2]);
			$col = new Colour($this->labelbgcolour);
		}

		$colicon = null;
		if ( !empty($this->targets) && $this->useiconscale != 'none' )
		{
			debug("Colorising the icon\n");
			$pc = 0;
			$val = 0;

			if($this->iconscalevar == 'in')
			{
				$pc = $this->inpercent;
				$col = $this->colours[IN];
				$val = $this->bandwidth_in;
			}
			if($this->iconscalevar == 'out')
			{
				$pc = $this->outpercent;
				$col = $this->colours[OUT];
				$val = $this->bandwidth_out;
			}

			if($this->iconscaletype=='percent')
			{
				list($colicon,$node_iconscalekey,$icontag) = 
					$map->NewColourFromPercent($pc, $this->useiconscale,$this->name );	
			}
			else
			{
				// use the absolute value if we aren't doing percentage scales.
				list($colicon,$node_iconscalekey,$icontag) = 
					$map->NewColourFromPercent($val, $this->useiconscale,$this->name, FALSE );
			}
		}

		// figure out a bounding rectangle for the label
		if ($this->label != '')
		{
			$padding = 4.0;
			$padfactor = 1.0;

			$this->proclabel = $map->ProcessString($this->label,$this,TRUE,TRUE);
			
			// if screenshot_mode is enabled, wipe any letters to X and wipe any IP address to 127.0.0.1
			// hopefully that will preserve enough information to show cool stuff without leaking info
			if($map->get_hint('screenshot_mode')==1)  $this->proclabel = screenshotify($this->proclabel);
			
			list($strwidth, $strheight) = $map->myimagestringsize($this->labelfont, $this->proclabel);

			if($this->labelangle==90 || $this->labelangle==270)
			{
				$boxwidth = ($strheight * $padfactor) + $padding;
				$boxheight = ($strwidth * $padfactor) + $padding;
				debug ("Node->pre_render: ".$this->name." Label Metrics are: $strwidth x $strheight -> $boxwidth x $boxheight\n");
				
				$label_x1 = $this->x - ($boxwidth / 2);
				$label_y1 = $this->y - ($boxheight / 2);

				$label_x2 = $this->x + ($boxwidth / 2);
				$label_y2 = $this->y + ($boxheight / 2);

				if($this->labelangle==90)
				{
					$txt_x = $this->x + ($strheight / 2);
					$txt_y = $this->y + ($strwidth / 2);
				}
				if($this->labelangle==270)
				{
					$txt_x = $this->x - ($strheight / 2);
					$txt_y = $this->y - ($strwidth / 2);
				}
			}
			
			if($this->labelangle==0 || $this->labelangle==180)
			{
				$boxwidth = ($strwidth * $padfactor) + $padding;
				$boxheight = ($strheight * $padfactor) + $padding;
				debug ("Node->pre_render: ".$this->name." Label Metrics are: $strwidth x $strheight -> $boxwidth x $boxheight\n");
							
				$label_x1 = $this->x - ($boxwidth / 2);
				$label_y1 = $this->y - ($boxheight / 2);

				$label_x2 = $this->x + ($boxwidth / 2);
				$label_y2 = $this->y + ($boxheight / 2);

				$txt_x = $this->x - ($strwidth / 2);
				$txt_y = $this->y + ($strheight / 2);
				
				if($this->labelangle==180)
				{
					$txt_x = $this->x + ($strwidth / 2);
					$txt_y = $this->y - ($strheight / 2);
				}

				# $this->width = $boxwidth;
				# $this->height = $boxheight;
			}
			$map->nodes[$this->name]->width = $boxwidth;
			$map->nodes[$this->name]->height = $boxheight;

			# print "TEXT at $txt_x , $txt_y\n";
		}

		// figure out a bounding rectangle for the icon
		if ($this->iconfile != '')
		{
			$icon_im = NULL;
			$icon_w = 0;
			$icon_h = 0;

			if($this->iconfile == 'rbox' || $this->iconfile == 'box' || $this->iconfile == 'round' || $this->iconfile == 'inpie' || $this->iconfile == 'outpie' || $this->iconfile == 'gauge' || $this->iconfile == 'nink')
			{
				debug("Artificial Icon type " .$this->iconfile. " for $this->name\n");
				// this is an artificial icon - we don't load a file for it

				$icon_im = imagecreatetruecolor($this->iconscalew,$this->iconscaleh);
				imageSaveAlpha($icon_im, TRUE);

				$nothing=imagecolorallocatealpha($icon_im,128,0,0,127);
				imagefill($icon_im, 0, 0, $nothing);

				$fill = NULL;
				$ink = NULL;

				$aifill = new Colour($this->aiconfillcolour);
				$aiink = new Colour($this->aiconoutlinecolour);

				if ( $aifill->is_copy() && !$col->is_none() )
				{
					$fill = $col;
				}
				else
				{
					if($aifill->is_real())
					{
						$fill = $aifill;
					}
				}

				if ($this->aiconoutlinecolour != array(-1,-1,-1))
				{
					$ink=$aiink;
				}

				if($this->iconfile=='box')
				{
					if($fill !== NULL && !$fill->is_none())
					{
						imagefilledrectangle($icon_im, 0, 0, $this->iconscalew-1, $this->iconscaleh-1, $fill->gdallocate($icon_im) );
					}

					if($ink !== NULL && !$ink->is_none())
					{
						imagerectangle($icon_im, 0, 0, $this->iconscalew-1, $this->iconscaleh-1, $ink->gdallocate($icon_im) );
					}
				}

				if($this->iconfile=='rbox')
				{
					if($fill !== NULL && !$fill->is_none())
					{
						imagefilledroundedrectangle($icon_im, 0, 0, $this->iconscalew-1, $this->iconscaleh-1, 4, $fill->gdallocate($icon_im) );
					}

					if($ink !== NULL && !$ink->is_none())
					{
						imageroundedrectangle($icon_im, 0, 0, $this->iconscalew-1, $this->iconscaleh-1, 4, $ink->gdallocate($icon_im) );
					}
				}

				if($this->iconfile=='round')
				{
					$rx = $this->iconscalew/2-1;
					$ry = $this->iconscaleh/2-1;

					if($fill !== NULL && !$fill->is_none() )
					{
						imagefilledellipse($icon_im,$rx,$ry,$rx*2,$ry*2,$fill->gdallocate($icon_im) );
					}

					if($ink !== NULL && !$ink->is_none())
					{
						imageellipse($icon_im,$rx,$ry,$rx*2,$ry*2,$ink->gdallocate($icon_im) );
					}
				}

				if($this->iconfile=='nink')
				{ 
					// print "NINK **************************************************************\n";
					$rx = $this->iconscalew/2-1;
					$ry = $this->iconscaleh/2-1;
					$size = $this->iconscalew;
					$quarter = $size/4;				
					
					$col1 = $this->colours[IN];
					$col2 = $this->colours[OUT];
					
					assert('!is_null($col1)');
					assert('!is_null($col2)');
					
					imagefilledarc($icon_im, $rx-1, $ry, $size, $size, 270,90, $col1->gdallocate($icon_im), IMG_ARC_PIE);
					imagefilledarc($icon_im, $rx+1, $ry, $size, $size, 90,270, $col2->gdallocate($icon_im), IMG_ARC_PIE);

					imagefilledarc($icon_im, $rx-1, $ry+$quarter, $quarter*2, $quarter*2, 0,360, $col1->gdallocate($icon_im), IMG_ARC_PIE);
					imagefilledarc($icon_im, $rx+1, $ry-$quarter, $quarter*2, $quarter*2, 0,360, $col2->gdallocate($icon_im), IMG_ARC_PIE);
					
					if($ink !== NULL && !$ink->is_none())
					{
						// XXX - need a font definition from somewhere for NINK text
						$font = 1;
						
						$instr = $map->ProcessString("{node:this:bandwidth_in:%.1k}",$this);
						$outstr = $map->ProcessString("{node:this:bandwidth_out:%.1k}",$this);
																		
						list($twid,$thgt) = $map->myimagestringsize($font,$instr);
						$map->myimagestring($icon_im, $font, $rx - $twid/2, $ry- $quarter + ($thgt/2),$instr,$ink->gdallocate($icon_im));
						
						list($twid,$thgt) = $map->myimagestringsize($font,$outstr);
						$map->myimagestring($icon_im, $font, $rx - $twid/2,  $ry + $quarter + ($thgt/2),$outstr,$ink->gdallocate($icon_im));
						
						imageellipse($icon_im,$rx,$ry,$rx*2,$ry*2,$ink->gdallocate($icon_im) );	
						// imagearc($icon_im, $rx,$ry,$quarter*4,$quarter*4, 0,360, $ink->gdallocate($icon_im));
					}
					// print "NINK ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^\n";
				}
				
				// XXX - needs proper colours
				if($this->iconfile=='inpie' || $this->iconfile=='outpie')
				{ 
					# list($colpie,$node_iconscalekey,$icontag) = $map->NewColourFromPercent($pc, $this->useiconscale,$this->name);
					
					if($this->iconfile=='inpie') $segment_angle = (($this->inpercent)/100) * 360;
					if($this->iconfile=='outpie') $segment_angle = (($this->outpercent)/100) * 360;
					
					$rx = $this->iconscalew/2-1;
					$ry = $this->iconscaleh/2-1;
										
					if($fill !== NULL && !$fill->is_none() )
					{
						imagefilledellipse($icon_im, $rx, $ry, $rx*2, $ry*2, $fill->gdallocate($icon_im) );
					}
					
					if($ink !== NULL && !$ink->is_none())
					{
						// imagefilledarc  ( resource $image  , int $cx  , int $cy  , int $width  , int $height  , int $start  , int $end  , int $color  , int $style  )
						imagefilledarc($icon_im, $rx, $ry, $rx*2,$ry*2, 0, $segment_angle, $ink->gdallocate($icon_im) , IMG_ARC_PIE);
					}
					
					if($fill !== NULL && !$fill->is_none() )
					{
						imageellipse($icon_im, $rx, $ry, $rx*2, $ry*2, $fill->gdallocate($icon_im) );
					}
					
					// warn('inpie AICON not implemented yet [WMWARN99]'); 
				}
				
				// if($this->iconfile=='outpie') { warn('outpie AICON not implemented yet [WMWARN99]'); }
				if($this->iconfile=='gauge') { warn('gauge AICON not implemented yet [WMWARN99]'); }

			}
			else
			{
				$this->iconfile = $map->ProcessString($this->iconfile ,$this);
				if (is_readable($this->iconfile))
				{
					imagealphablending($im, true);
					// draw the supplied icon, instead of the labelled box

					$icon_im = imagecreatefromfile($this->iconfile);
					# $icon_im = imagecreatefrompng($this->iconfile);
					if(function_exists("imagefilter") && isset($colicon) && $this->get_hint("use_imagefilter")==1)
					{						
						imagefilter($icon_im, IMG_FILTER_COLORIZE, $colicon->r, $colicon->g, $colicon->b);						
					}
					else
					{
                                            if(isset($colicon))
                                            {
                                                // debug("Skipping unavailable imagefilter() call.\n");
                                                imagecolorize($icon_im, $colicon->r, $colicon->g, $colicon->b);
                                            }
					}

					debug("If this is the last thing in your logs, you probably have a buggy GD library. Get > 2.0.33 or use PHP builtin.\n");
					if ($icon_im)
					{
						$icon_w = imagesx($icon_im);
						$icon_h = imagesy($icon_im);

						if(($this->iconscalew * $this->iconscaleh) > 0)
						{
							imagealphablending($icon_im, true);

							debug("SCALING ICON here\n");
							if($icon_w > $icon_h)
							{
								$scalefactor = $icon_w/$this->iconscalew;
							}
							else
							{
								$scalefactor = $icon_h/$this->iconscaleh;
							}
							$new_width = $icon_w / $scalefactor;
							$new_height = $icon_h / $scalefactor;
							$scaled = imagecreatetruecolor($new_width, $new_height);
							imagealphablending($scaled,false);
							imagecopyresampled($scaled, $icon_im, 0, 0, 0, 0, $new_width, $new_height, $icon_w, $icon_h);
							imagedestroy($icon_im);
							$icon_im = $scaled;

						}
					}
					else { warn ("Couldn't open ICON: '" . $this->iconfile . "' - is it a PNG, JPEG or GIF? [WMWARN37]\n"); }
				}
				else
				{
					if($this->iconfile != 'none')
					{
						warn ("ICON '" . $this->iconfile . "' does not exist, or is not readable. Check path and permissions. [WMARN38]\n");
					}
				}
			}

			if($icon_im)
			{
				$icon_w = imagesx($icon_im);
				$icon_h = imagesy($icon_im);

				$icon_x1 = $this->x - $icon_w / 2;
				$icon_y1 = $this->y - $icon_h / 2;
				$icon_x2 = $this->x + $icon_w / 2;
				$icon_y2 = $this->y + $icon_h / 2;

				$map->nodes[$this->name]->width = imagesx($icon_im);
				$map->nodes[$this->name]->height = imagesy($icon_im);

				// $map->imap->addArea("Rectangle", "NODE:" . $this->name . ':0', '', array($icon_x1, $icon_y1, $icon_x2, $icon_y2));
				$map->nodes[$this->name]->boundingboxes[] = array($icon_x1, $icon_y1, $icon_x2, $icon_y2);
			}

		}



		// do any offset calculations
		$dx=0;
		$dy=0;
		if ( ($this->labeloffset != '') && (($this->iconfile != '')) )
		{
			$this->labeloffsetx = 0;
			$this->labeloffsety = 0;

			list($dx, $dy) = calc_offset($this->labeloffset,
				($icon_w + $boxwidth -1),
				($icon_h + $boxheight)
			);

			#$this->labeloffsetx = $dx;
			#$this->labeloffsety = $dy;

		}

		$label_x1 += ($this->labeloffsetx + $dx);
		$label_x2 += ($this->labeloffsetx + $dx);
		$label_y1 += ($this->labeloffsety + $dy);
		$label_y2 += ($this->labeloffsety + $dy);

		if($this->label != '')
		{
			// $map->imap->addArea("Rectangle", "NODE:" . $this->name .':1', '', array($label_x1, $label_y1, $label_x2, $label_y2));
			$map->nodes[$this->name]->boundingboxes[] = array($label_x1, $label_y1, $label_x2, $label_y2);
		}

		// work out the bounding box of the whole thing

		$bbox_x1 = min($label_x1,$icon_x1);
		$bbox_x2 = max($label_x2,$icon_x2)+1;
		$bbox_y1 = min($label_y1,$icon_y1);
		$bbox_y2 = max($label_y2,$icon_y2)+1;

		#           imagerectangle($im,$bbox_x1,$bbox_y1,$bbox_x2,$bbox_y2,$map->selected);
		#         imagerectangle($im,$label_x1,$label_y1,$label_x2,$label_y2,$map->black);
		#       imagerectangle($im,$icon_x1,$icon_y1,$icon_x2,$icon_y2,$map->black);

		// create TWO imagemap entries - one for the label and one for the icon
		// (so we can have close-spaced icons better)


		$temp_width = $bbox_x2-$bbox_x1;
		$temp_height = $bbox_y2-$bbox_y1;
		// create an image of that size and draw into it
		$node_im=imagecreatetruecolor($temp_width,$temp_height );
		// ImageAlphaBlending($node_im, FALSE);
		imageSaveAlpha($node_im, TRUE);

		$nothing=imagecolorallocatealpha($node_im,128,0,0,127);
		imagefill($node_im, 0, 0, $nothing);

		#$col = $col->gdallocate($node_im);

		// imagefilledrectangle($node_im,0,0,$temp_width,$temp_height,  $nothing);

		$label_x1 -= $bbox_x1;
		$label_x2 -= $bbox_x1;
		$label_y1 -= $bbox_y1;
		$label_y2 -= $bbox_y1;

		$icon_x1 -= $bbox_x1;
		$icon_x2 -= $bbox_x1;
		$icon_y1 -= $bbox_y1;
		$icon_y2 -= $bbox_y1;


		// Draw the icon, if any
		if(isset($icon_im))
		{
			imagecopy($node_im, $icon_im, $icon_x1, $icon_y1, 0, 0, imagesx($icon_im), imagesy($icon_im));
			imagedestroy($icon_im);
		}

		// Draw the label, if any
		if ($this->label != '')
		{
			$txt_x -= $bbox_x1;
			$txt_x += ($this->labeloffsetx + $dx);
			$txt_y -= $bbox_y1;
			$txt_y += ($this->labeloffsety + $dy);

			#       print "FINAL TEXT at $txt_x , $txt_y\n";

			// if there's an icon, then you can choose to have no background

			if(! $col->is_none() )
			{
			    imagefilledrectangle($node_im, $label_x1, $label_y1, $label_x2, $label_y2, $col->gdallocate($node_im));
			}

			if ($this->selected)
			{
				imagerectangle($node_im, $label_x1, $label_y1, $label_x2, $label_y2, $map->selected);
				// would be nice if it was thicker, too...
				wimagerectangle($node_im, $label_x1 + 1, $label_y1 + 1, $label_x2 - 1, $label_y2 - 1, $map->selected);
			}
			else
			{
				$olcol = new Colour($this->labeloutlinecolour);
				if ($olcol->is_real())
				{
					imagerectangle($node_im, $label_x1, $label_y1, $label_x2, $label_y2, $olcol->gdallocate($node_im));
				}
			}
			#}

			$shcol = new Colour($this->labelfontshadowcolour);
			if ($shcol->is_real())
			{
				$map->myimagestring($node_im, $this->labelfont, $txt_x + 1, $txt_y + 1, $this->proclabel, $shcol->gdallocate($node_im),$this->labelangle);
			}

			$txcol = new Colour($this->labelfontcolour[0],$this->labelfontcolour[1],$this->labelfontcolour[2]);
			#$col=myimagecolorallocate($node_im, $this->labelfontcolour[0], $this->labelfontcolour[1],
			#	$this->labelfontcolour[2]);
			if($txcol->is_contrast())
			{
				if($col->is_real())
				{
					$txcol = $col->contrast();
				}
				else
				{
					warn("You can't make a contrast with 'none'. [WMWARN43]\n");
					$txcol = new Colour(0,0,0);
				}
			}
			$map->myimagestring($node_im, $this->labelfont, $txt_x, $txt_y, $this->proclabel, $txcol->gdallocate($node_im),$this->labelangle);
			//$map->myimagestring($node_im, $this->labelfont, $txt_x, $txt_y, $this->proclabel, $txcol->gdallocate($node_im),90);
		}

		# imagerectangle($node_im,$label_x1,$label_y1,$label_x2,$label_y2,$map->black);
		# imagerectangle($node_im,$icon_x1,$icon_y1,$icon_x2,$icon_y2,$map->black);

		$map->nodes[$this->name]->centre_x = $this->x - $bbox_x1;
		$map->nodes[$this->name]->centre_y = $this->y - $bbox_y1;

		if(1==0)
		{

			imageellipse($node_im, $this->centre_x, $this->centre_y, 8, 8, $map->selected);

			foreach (array("N","S","E","W","NE","NW","SE","SW") as $corner)
			{
				list($dx, $dy)=calc_offset($corner, $this->width, $this->height);
				imageellipse($node_im, $this->centre_x + $dx, $this->centre_y + $dy, 5, 5, $map->selected);
			}
		}

		# $this->image = $node_im;
		$map->nodes[$this->name]->image = $node_im;
	}

	function update_cache($cachedir,$mapname)
	{
		$cachename = $cachedir."/node_".md5($mapname."/".$this->name).".png";
		// save this image to a cache, for the editor
		imagepng($this->image,$cachename);
	}

	// draw the node, using the pre_render() output
	function NewDraw($im, &$map)
	{
		// take the offset we figured out earlier, and just blit
		// the image on. Who says "blit" anymore?

		// it's possible that there is no image, so better check.
		if(isset($this->image))
		{
			imagealphablending($im, true);
			imagecopy ( $im, $this->image, $this->x - $this->centre_x, $this->y - $this->centre_y, 0, 0, imagesx($this->image), imagesy($this->image) );
		}

	}

	// take the pre-rendered node and write it to a file so that
	// the editor can get at it.
	function WriteToCache()
	{
	}

	function Reset(&$newowner)
	{
		$this->owner=$newowner;
		$template = $this->template;
        
		if($template == '') $template = "DEFAULT";

		debug("Resetting $this->name with $template\n");
		
		// the internal default-default gets it's values from inherit_fieldlist
		// everything else comes from a node object - the template.
		if($this->name==':: DEFAULT ::')
		{
			foreach (array_keys($this->inherit_fieldlist)as
				$fld) { $this->$fld=$this->inherit_fieldlist[$fld]; }
		}
		else
		{
			$this->CopyFrom($this->owner->nodes[$template]); 
		}
		$this->template = $template;
		
		// to stop the editor tanking, now that colours are decided earlier in ReadData
		$this->colours[IN] = new Colour(192,192,192);
		$this->colours[OUT] = new Colour(192,192,192);
		
		$this->id = $newowner->next_id++;
	}

	function CopyFrom(&$source)
	{
		debug("Initialising NODE $this->name from $source->name\n");
		assert('is_object($source)');
		
		foreach (array_keys($this->inherit_fieldlist)as $fld) {
			if($fld != 'template') $this->$fld=$source->$fld;
		}
	}

	function WriteConfig()
	{
		$output='';
				
		# $output .= "# ID ".$this->id." - first seen in ".$this->defined_in."\n";

		// This allows the editor to wholesale-replace a single node's configuration
		// at write-time - it should include the leading NODE xyz line (to allow for renaming)
		if($this->config_override != '')
		{
			$output  = $this->config_override."\n";
		}
		else
		{
			# $defdef = $this->owner->defaultnode;
			$dd = $this->owner->nodes[$this->template];
			
			debug("Writing config for NODE $this->name against $this->template\n");
			
			# $field = 'zorder'; $keyword = 'ZORDER';
			$basic_params = array(
					# array('template','TEMPLATE',CONFIG_TYPE_LITERAL),
					array('label','LABEL',CONFIG_TYPE_LITERAL),
					array('zorder','ZORDER',CONFIG_TYPE_LITERAL),
					array('labeloffset','LABELOFFSET',CONFIG_TYPE_LITERAL),
					array('labelfont','LABELFONT',CONFIG_TYPE_LITERAL),
					array('labelangle','LABELANGLE',CONFIG_TYPE_LITERAL),
					array('overlibwidth','OVERLIBWIDTH',CONFIG_TYPE_LITERAL),
					array('overlibheight','OVERLIBHEIGHT',CONFIG_TYPE_LITERAL),

					array('aiconoutlinecolour','AICONOUTLINECOLOR',CONFIG_TYPE_COLOR),
					array('aiconfillcolour','AICONFILLCOLOR',CONFIG_TYPE_COLOR),
					array('labeloutlinecolour','LABELOUTLINECOLOR',CONFIG_TYPE_COLOR),
					array('labelfontshadowcolour','LABELFONTSHADOWCOLOR',CONFIG_TYPE_COLOR),
					array('labelbgcolour','LABELBGCOLOR',CONFIG_TYPE_COLOR),
					array('labelfontcolour','LABELFONTCOLOR',CONFIG_TYPE_COLOR)
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

			#	$comparison=($this->name == 'DEFAULT' ? $this->inherit_fieldlist[$field] : $defdef->$field);
				if ($this->$field != $dd->$field)
				{
					if($param[2] == CONFIG_TYPE_COLOR) $output.="\t$keyword " . render_colour($this->$field) . "\n";
					if($param[2] == CONFIG_TYPE_LITERAL) $output.="\t$keyword " . $this->$field . "\n";
				}
			}

			// IN/OUT are the same, so we can use the simpler form here
#			print_r($this->infourl);
			#$comparison=($this->name == 'DEFAULT'
			#? $this->inherit_fieldlist['infourl'][IN] : $defdef->infourl[IN]);
			if ($this->infourl[IN] != $dd->infourl[IN]) { $output.="\tINFOURL " . $this->infourl[IN] . "\n"; }
			
			#$comparison=($this->name == 'DEFAULT'
			#? $this->inherit_fieldlist['overlibcaption'][IN] : $defdef->overlibcaption[IN]);
			if ($this->overlibcaption[IN] != $dd->overlibcaption[IN]) { $output.="\tOVERLIBCAPTION " . $this->overlibcaption[IN] . "\n"; }

			// IN/OUT are the same, so we can use the simpler form here
			# $comparison=($this->name == 'DEFAULT'
			# ? $this->inherit_fieldlist['notestext'][IN] : $defdef->notestext[IN]);
			if ($this->notestext[IN] != $dd->notestext[IN]) { $output.="\tNOTES " . $this->notestext[IN] . "\n"; }

			# $comparison=($this->name == 'DEFAULT'
			# ? $this->inherit_fieldlist['overliburl'][IN] : $defdef->overliburl[IN]);
			if ($this->overliburl[IN] != $dd->overliburl[IN]) { $output.="\tOVERLIBGRAPH " . join(" ",$this->overliburl[IN]) . "\n"; }

			$val = $this->iconscalew. " " . $this->iconscaleh. " " .$this->iconfile;

			$comparison = $dd->iconscalew. " " . $dd->iconscaleh . " " . $dd->iconfile;

			if ($val != $comparison) {
				$output.="\tICON ";
				if($this->iconscalew > 0) {
					$output .= $this->iconscalew." ".$this->iconscaleh." ";
				}
				$output .= ($this->iconfile=='' ?  'none' : $this->iconfile) . "\n";
			}

			# $comparison=($this->name == 'DEFAULT'
			# ? $this->inherit_fieldlist['targets'] : $defdef->targets);

			if ($this->targets != $dd->targets)
			{
				$output.="\tTARGET";

				foreach ($this->targets as $target) { 
					if(strpos($target[4]," ") == FALSE) 
					{
						$output.=" " . $target[4]; 
					}
					else
					{
						$output.=' "' . $target[4].'"'; 
					}
				}
				

				$output.="\n";
			}

		#	$comparison = ($this->name == 'DEFAULT' ? $this->inherit_fieldlist['usescale'] : $defdef->usescale) . " " .
		#		($this->name == 'DEFAULT' ? $this->inherit_fieldlist['scalevar'] : $defdef->scalevar);
			$val = $this->usescale . " " . $this->scalevar;
			$comparison = $dd->usescale . " " . $dd->scalevar;

			if ( ($val != $comparison) ) { $output.="\tUSESCALE " . $val . "\n"; }

#			$comparison = ($this->name == 'DEFAULT'
#				? $this->inherit_fieldlist['useiconscale'] : $defdef->useiconscale) . " " .
#				($this->name == 'DEFAULT' ? $this->inherit_fieldlist['iconscalevar'] : $defdef->iconscalevar);
			$val = $this->useiconscale . " " . $this->iconscalevar;
			$comparison= $dd->useiconscale . " " . $dd->iconscalevar;
				
			if ( $val != $comparison) { $output.="\tUSEICONSCALE " .$val . "\n"; }

			#$comparison = ($this->name == 'DEFAULT'
			#? $this->inherit_fieldlist['labeloffsetx'] : $defdef->labeloffsetx) . " " . ($this->name == 'DEFAULT'
		#		? $this->inherit_fieldlist['labeloffsety'] : $defdef->labeloffsety);
			$val = $this->labeloffsetx . " " . $this->labeloffsety;
			$comparison = $dd->labeloffsetx . " " . $dd->labeloffsety;

			if ($comparison != $val ) { $output.="\tLABELOFFSET " . $val . "\n"; }

			#$comparison=($this->name == 'DEFAULT' ? $this->inherit_fieldlist['x'] : $defdef->x) . " " . 
			#			($this->name == 'DEFAULT' ? $this->inherit_fieldlist['y'] : $defdef->y);
			$val = $this->x . " " . $this->y;
			$comparison = $dd->x . " " . $dd->y;
			
			if ($val != $comparison)
			{
				if($this->relative_to == '')
				{ $output.="\tPOSITION " . $val . "\n"; }
				else
				{
					if($this->polar)
					{
						$output .= "\tPOSITION ".$this->relative_to . " " .  $this->original_x . "r" . $this->original_y . "\n";
					}
					else
					{
						$output.="\tPOSITION " . $this->relative_to . " " .  $this->original_x . " " . $this->original_y . "\n";
					}
				}
			}

			if (($this->max_bandwidth_in != $dd->max_bandwidth_in)
				|| ($this->max_bandwidth_out != $dd->max_bandwidth_out)
					|| ($this->name == 'DEFAULT'))
			{
				if ($this->max_bandwidth_in == $this->max_bandwidth_out)
				{ $output.="\tMAXVALUE " . $this->max_bandwidth_in_cfg . "\n"; }
				else { $output
				.="\tMAXVALUE " . $this->max_bandwidth_in_cfg . " " . $this->max_bandwidth_out_cfg . "\n"; }
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
			if ($output != '')
			{
				$output = "NODE " . $this->name . "\n$output\n";
			}
		}
		return ($output);
	}

	function asJS()
	{
		$js = '';
		$js .= "Nodes[" . js_escape($this->name) . "] = {";
		$js .= "x:" . (is_null($this->x)? "'null'" : $this->x) . ", ";
		$js .= "y:" . (is_null($this->y)? "'null'" : $this->y) . ", ";
		$js .= "\"id\":" . $this->id. ", ";
		// $js.="y:" . $this->y . ", ";
		$js.="ox:" . $this->original_x . ", ";
		$js.="oy:" . $this->original_y . ", ";
		$js.="relative_to:" . js_escape($this->relative_to) . ", ";
		$js.="label:" . js_escape($this->label) . ", ";
		$js.="name:" . js_escape($this->name) . ", ";
		$js.="infourl:" . js_escape($this->infourl[IN]) . ", ";
		$js.="overlibcaption:" . js_escape($this->overlibcaption[IN]) . ", ";
		$js.="overliburl:" . js_escape(join(" ",$this->overliburl[IN])) . ", ";
		$js.="overlibwidth:" . $this->overlibheight . ", ";
		$js.="overlibheight:" . $this->overlibwidth . ", ";
		if(preg_match("/^(none|nink|inpie|outpie|box|rbox|gauge|round)$/",$this->iconfile))
		{
			$js.="iconfile:" . js_escape("::".$this->iconfile);
		}
		else
		{
			$js.="iconfile:" . js_escape($this->iconfile);
		}
		
		$js .= "};\n";
		$js .= "NodeIDs[\"N" . $this->id . "\"] = ". js_escape($this->name) . ";\n";
		return $js;
	}

	function asJSON($complete=TRUE)
	{
		$js = '';
		$js .= "" . js_escape($this->name) . ": {";
		$js .= "\"id\":" . $this->id. ", ";
		$js .= "\"x\":" . ($this->x - $this->centre_x). ", ";
		$js .= "\"y\":" . ($this->y - $this->centre_y) . ", ";
		$js .= "\"cx\":" . $this->centre_x. ", ";
		$js .= "\"cy\":" . $this->centre_y . ", ";
		$js .= "\"ox\":" . $this->original_x . ", ";
		$js .= "\"oy\":" . $this->original_y . ", ";
		$js .= "\"relative_to\":" . js_escape($this->relative_to) . ", ";
		$js .= "\"name\":" . js_escape($this->name) . ", ";
		if($complete)
		{
			$js .= "\"label\":" . js_escape($this->label) . ", ";
			$js .= "\"infourl\":" . js_escape($this->infourl) . ", ";
			$js .= "\"overliburl\":" . js_escape($this->overliburl) . ", ";
			$js .= "\"overlibcaption\":" . js_escape($this->overlibcaption) . ", ";

			$js .= "\"overlibwidth\":" . $this->overlibheight . ", ";
			$js .= "\"overlibheight\":" . $this->overlibwidth . ", ";
			$js .= "\"iconfile\":" . js_escape($this->iconfile). ", ";
		}
		$js .= "\"iconcachefile\":" . js_escape($this->cachefile);
		$js .= "},\n";
		return $js;
	}
};

// vim:ts=4:sw=4:
?>
