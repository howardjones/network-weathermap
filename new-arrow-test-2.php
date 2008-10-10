<?php

include_once("WeatherMap.class.php");

// the fields within a spine triple
define("X",0);
define("Y",1);
define("DISTANCE",2);

$weathermap_debugging=TRUE;

$type = 4;
$width = 7;
$cn = 1;

$epsilon = 0.00000000001;

$map = NULL;

$linkname = "";


$unidirectional = false;
$widths = array();

$widths[IN] = $width;
$widths[OUT] = $width;

$dirs = array(OUT,IN);

$draw_skeleton = false;
$draw_capping_skeleton = false;
$draw_arrow_skeleton = false;

$im = imagecreatetruecolor(980, 980);

$white = imagecolorallocate($im,255,255,255);
$black = imagecolorallocate($im,0,0,0);
$red = imagecolorallocate($im,255,0,0);
$green = imagecolorallocate($im,0,255,0);
// $grey = imagecolorallocate($im,192,192,192);
$grey = imagecolorallocatealpha($im,192,192,192,64);
$blue = imagecolorallocate($im,0,0,255);

$fillcolours[IN] = $grey;
$fillcolours[OUT] = $grey;
$outlinecolour = $black;

imagefilledrectangle($im,0,0,1024,1024,$white);


if($type==4)
{
    $cn = rand(1,10);
}

for($cc=0; $cc<$cn; $cc++)
{

if($type==0)
{
    $points_x = array(100,300);
    $points_y = array(100,350);
}

if($type == 10)
{
    // strange flat spot
    $points_x = array(492,615,690,778,820,796);
    $points_y = array(539,519,519,365,400,520);
}

if($type==1)
{
    // zig zag
    $points_x = array(900,860,800,900,200,100,700,600,300,300);
    $points_y = array(100,400,700,900,700,300,400,100,200,50);
}

if($type==2)
{
    // U shaped
    $points_x = array(200,100,700,600);
    $points_y = array(700,300,400,700);
}

if($type==3)
{
    // sharp angle
    $points_x = array(300,100,300,400);
    $points_y = array(900,300,700,100);
}

if($type==4)
{
    $npoints = rand(3,6);
        
    $points_x = array();
    $points_y = array();
    
    $points_x []= intval(rand(300,600));
    $points_y []= intval(rand(300,600));
     
    $last_angle = 0; 
        
    for($i=0; $i < $npoints; $i++)
    {
        $dangle = rand(-120,120);
        $angle = $angle + $dangle;
        $distance = rand(50,200);
        
        $dx = $distance * cos(deg2rad($angle));
        $dy = -$distance * sin(deg2rad($angle));
        
        $newx = $points_x[$i] + $dx;
        $newy = $points_y[$i] + $dy;
        
        if($newx < 0) $newx = $points_x[$i] - $dx;
        if($newy < 0) $newy = $points_y[$i] - $dy;
        
        if($newx > 1000) $newx = $points_x[$i] - $dx;
        if($newy > 1000) $newy = $points_y[$i] - $dy;
        
        $points_x []= intval($newx);
        $points_y []= intval($newy);
    }
    
    print "\n\n\n\$points_x = array(".join(",",$points_x).");\n";
    print "\$points_y = array(".join(",",$points_y).");\n\n\n";
}

if($draw_skeleton) draw_skeleton($im,$points_x,$points_y,$blue,$green,$red, $width);

// fill this with (x,y,distance) triples for use with find_distance()
$curvepoints = calc_straight($points_x, $points_y);
$totaldistance = $curvepoints[count($curvepoints)-1][DISTANCE];

print "Distance is $totaldistance\n";


if($unidirectional)
{
        $halfway = $totaldistance;
        $dirs = array(OUT);
        $q2_percent = 100;
        $halfway = $totaldistance * ($q2_percent/100);
        list($halfway_x,$halfway_y,$halfwayindex) = find_distance_coords($curvepoints,$halfway);
        
        $spine[OUT] = $curvepoints;
}
else
{
    // we'll split the spine in half here.
    $q2_percent = 50;
    $halfway = $totaldistance * ($q2_percent/100);
    
    $dirs = array(OUT,IN);
    
    list($halfway_x,$halfway_y,$halfwayindex) = find_distance_coords($curvepoints,$halfway);
    print "Midpoint is: $halfway_x,$halfway_y\n";
    if($draw_skeleton) imagearc($im,$halfway_x, $halfway_y,30,10,0,360,$blue);    

    $spine[OUT] = array();
    $spine[IN] = array();
    $npoints = count($curvepoints)-1;

    for($i=0; $i<=$halfwayindex; $i++)
    {
        $spine[OUT] []= $curvepoints[$i];
    }
    // finally, add the actual midpoint
    $spine[OUT] []= array($halfway_x,$halfway_y, $halfway);
    
    // and then from the end to the middle for the other arrow
    for($i=$npoints; $i>$halfwayindex; $i--)
    {
        // copy the original spine, but reversing the distance calculation
        $spine[IN] []= array($curvepoints[$i][X], $curvepoints[$i][Y], $totaldistance - $curvepoints[$i][DISTANCE]);
    }
    // finally, add the actual midpoint
    $spine[IN] []= array($halfway_x,$halfway_y, $halfway);
}

// now we have two seperate spines, with distances, so that the arrowhead is the end of each.
// (or one, if it's unidir)

// so we can loop along the spine for each one as a seperate entity

if($draw_skeleton && isset($spine[IN]) ) draw_spine($im, $spine[IN],$red);
if($draw_skeleton) draw_spine($im, $spine[OUT],$green);


// we calculate the arrow size up here, so that we can decide on the
// minimum length for a link. The arrowheads are the limiting factor.
list($arrowsize[IN],$arrowwidth[IN]) = calc_arrowsize($widths[IN], $map, $linkname);
list($arrowsize[OUT],$arrowwidth[OUT]) = calc_arrowsize($widths[OUT], $map, $linkname);

// the 1.2 here is empirical. It ought to be 1 in theory.
// in practice, a link this short is useless anyway, especially with bwlabels.
$minimumlength = 1.2*($arrowsize[IN]+$arrowsize[OUT]);

foreach ($dirs as $dir)
{
    $n = count($spine[$dir]) - 1;
    $l = $spine[$dir][$n][DISTANCE];

    // loop increment, start point, width, labelpos, fillcolour, outlinecolour, commentpos    
    $arrowsettings = array(+1, 0, $widths[$dir], 0, $fillcolours[$dir], $outlinecolour, 5);
    
    print "Line is $n points to a distance of $l\n";
    if($l < $minimumlength)
    {
        print "Skipping too-short line.\n";
    }
    else
    {
        $arrow_d = $l- $arrowsize[$dir];
        list($pre_mid_x,$pre_mid_y,$pre_midindex) = find_distance_coords($spine[$dir], $arrow_d);
        
        $out = array_slice($spine[$dir], 0, $pre_midindex);
        $out []= array($pre_mid_x, $pre_mid_y, $arrow_d);
        
        $spine[$dir] = $out;
        
        $adx=($halfway_x - $pre_mid_x);
        $ady=($halfway_y - $pre_mid_y);
        $ll=sqrt(($adx * $adx) + ($ady * $ady));

        $anx=$ady / $ll;
        $any=-$adx / $ll;
        
        $ax1 = $pre_mid_x + $widths[$dir] * $anx;
        $ay1 = $pre_mid_y + $widths[$dir] * $any;

        $ax2 = $pre_mid_x + $arrowwidth[$dir] * $anx;
        $ay2 = $pre_mid_y + $arrowwidth[$dir] * $any;
        
        $ax3 = $halfway_x;
        $ay3 = $halfway_y;
        
        $ax5 = $pre_mid_x - $widths[$dir] * $anx;
        $ay5 = $pre_mid_y - $widths[$dir] * $any;

        $ax4 = $pre_mid_x - $arrowwidth[$dir] * $anx;
        $ay4 = $pre_mid_y - $arrowwidth[$dir] * $any;
        
        if($draw_arrow_skeleton)
        {
            imagearc($im,$spine[$dir][$pre_midindex][X],$spine[$dir][$pre_midindex][Y],20,20,0,360,$blue);
            
            imagearc($im, $ax1,$ay1 ,5,5,0,360,$blue);
            imagearc($im, $ax2,$ay2 ,5,5,0,360,$blue);
            imagearc($im, $ax3,$ay3 ,5,5,0,360,$blue);
            imagearc($im, $ax4,$ay4 ,5,5,0,360,$blue);
            imagearc($im, $ax5,$ay5 ,5,5,0,360,$blue);
            
            imagelinethick($im, $ax1,$ay1, $ax2,$ay2, $blue,2);
            imagelinethick($im, $ax2,$ay2, $ax3,$ay3, $blue,2);
            imagelinethick($im, $ax3,$ay3, $ax4,$ay4, $blue,2);
            imagelinethick($im, $ax4,$ay4, $ax5,$ay5, $blue,2);
            
        }
               
        
        $simple = simplify_spine($spine[$dir]);
        $newn = count($simple);
        
        print "Simplified to $newn points\n";
        if($draw_skeleton) draw_spine_chain($im,$simple,$blue, 12);
        // draw_spine_chain($im,$spine[$dir],$blue, 10);

        // now do the actual drawing....
                
        $numpoints=0;
        $numrpoints=0;
        
        $finalpoints = array();
        $reversepoints = array();
        
        $finalpoints[] = $simple[0][X];
        $finalpoints[] = $simple[0][Y];
        $numpoints++;
        
        $reversepoints[] = $simple[0][X];
        $reversepoints[] = $simple[0][Y];
        $numrpoints++;
        
        // before the main loop, add in the jump out to the corners
        // if this is the first step, then we need to go from the middle to the outside edge first
        // ( the loop may not run, but these corners are required)
        $i = 0;
        $v1 = new Vector($simple[$i+1][X] - $simple[$i][X], $simple[$i+1][Y] - $simple[$i][Y]);
        $n1 = $v1->get_normal();
                
        $finalpoints[] = $simple[$i][X] + $n1->dx*$width;
        $finalpoints[] = $simple[$i][Y] + $n1->dy*$width;
        $numpoints++;
        
        $reversepoints[] = $simple[$i][X] - $n1->dx*$width;
        $reversepoints[] = $simple[$i][Y] - $n1->dy*$width;
        $numrpoints++;
        
        $max_start = count($simple)-2;
        print "max_start is $max_start\n";
        for ($i=0; $i <$max_start; $i++)
        {
            if($draw_skeleton) imagearc($im,$simple[$i+1][X],$simple[$i+1][Y],120,120,0,360,$blue);
        
            $v1 = new Vector($simple[$i+1][X] - $simple[$i][X], $simple[$i+1][Y] - $simple[$i][Y]);
            $v2 = new Vector($simple[$i+2][X] - $simple[$i+1][X], $simple[$i+2][Y] - $simple[$i+1][Y]);
            $n1 = $v1->get_normal();
            $n2 = $v2->get_normal();
            
            $capping = FALSE;
            // figure out the angle between the lines - for very sharp turns, we should do something special
            // (actually, their normals, but the angle is the same and we need the normals later)
            $angle = rad2deg(atan2($n2->dy,$n2->dx) - atan2($n1->dy,$n1->dx));
            if($angle > 180) $angle -= 360;
            if($angle < -180) $angle += 360;
    
            if(abs($angle)>169)  { $capping = TRUE; print "Would cap. ($angle)\n"; }
            
            // $capping = FALSE; // override that for now           
                        
            // now figure out the geometry for where the next corners are
            
            list($xi1,$yi1) = line_crossing( $simple[$i][X] + $n1->dx * $width, $simple[$i][Y] + $n1->dy * $width,
                                            $simple[$i+1][X] + $n1->dx * $width, $simple[$i+1][Y] + $n1->dy * $width,
                                            $simple[$i+1][X] + $n2->dx * $width, $simple[$i+1][Y] + $n2->dy * $width,
                                            $simple[$i+2][X] + $n2->dx * $width, $simple[$i+2][Y] + $n2->dy * $width                                
                                    );
        
            list($xi2,$yi2) = line_crossing( $simple[$i][X] - $n1->dx * $width, $simple[$i][Y] - $n1->dy * $width,
                                        $simple[$i+1][X] - $n1->dx * $width, $simple[$i+1][Y] - $n1->dy * $width,
                                        $simple[$i+1][X] - $n2->dx * $width, $simple[$i+1][Y] - $n2->dy * $width,
                                        $simple[$i+2][X] - $n2->dx * $width, $simple[$i+2][Y] - $n2->dy * $width                                
                                        );
            
            if($draw_skeleton)  imagearc($im,$xi1,$yi1,8,8,0,360,$red);        
            if($draw_skeleton)  imagearc($im,$xi2,$yi2,20,20,0,360,$red);
            
            if(!$capping)
            {
                $finalpoints[] = $xi1;
                $finalpoints[] = $yi1;
                $numpoints++;
                        
                $reversepoints[] = $xi2;
                $reversepoints[] = $yi2;
                $numrpoints++;
                
                if($draw_capping_skeleton)  imagearc($im,$xi2,$yi2,20,20,0,360,$red);
                if($draw_capping_skeleton)  imagearc($im,$xi1,$yi1,20,20,0,360,$blue);
                
            }
            else
            {
                // in here, we need to decide which is the 'outside' of the corner,
                // because that's what we flatten. The inside of the corner is left alone.
                // - depending on the relative angle between the two segments, it could
                //   be either one of these points.
                
                list($xi3,$yi3) = line_crossing( $simple[$i][X] + $n1->dx*$width, $simple[$i][Y] + $n1->dy*$width,
                                    $simple[$i+1][X] + $n1->dx*$width, $simple[$i+1][Y] + $n1->dy*$width,
                                    $simple[$i+1][X] - $n2->dx*$width, $simple[$i+1][Y] - $n2->dy*$width,
                                    $simple[$i+2][X] - $n2->dx*$width, $simple[$i+2][Y] - $n2->dy*$width                                
                                    );
    
                list($xi4,$yi4) = line_crossing( $simple[$i][X] - $n1->dx*$width, $simple[$i][Y] - $n1->dy*$width,
                                        $simple[$i+1][X] - $n1->dx*$width, $simple[$i+1][Y] - $n1->dy*$width,
                                        $simple[$i+1][X] + $n2->dx*$width, $simple[$i+1][Y] + $n2->dy*$width,
                                        $simple[$i+2][X] + $n2->dx*$width, $simple[$i+2][Y] + $n2->dy*$width                                
                                        );
                
                if($draw_capping_skeleton)  imagearc($im,$xi3,$yi3,12,12,0,360,$red);
                if($draw_capping_skeleton)  imagearc($im,$xi4,$yi4,12,12,0,360,$red);
                
                if($angle < 0)
                {
                    print "Angle <0\n";
                    
                    // in here, we need to decide which is the 'outside' of the corner,
                    // because that's what we flatten. The inside of the corner is left alone.
                    // - depending on the relative angle between the two segments, it could
                    //   be either one of these points.
                    
                    $finalpoints[] = $xi3;
                    $finalpoints[] = $yi3;
                    $numpoints++;
                    
                    $finalpoints[] = $xi4;
                    $finalpoints[] = $yi4;
                    $numpoints++;
                    
                    $reversepoints[] = $xi2;
                    $reversepoints[] = $yi2;
                    $numrpoints++;
                    
                    if($draw_capping_skeleton)  imagearc($im,$xi2,$yi2,12,12,0,360,$green);
                }
                else
                {
                    print "Angle >0\n";
                    
                    $reversepoints[] = $xi4;
                    $reversepoints[] = $yi4;
                    $numrpoints++;
                    
                    $reversepoints[] = $xi3;
                    $reversepoints[] = $yi3;
                    $numrpoints++;
                    
                    $finalpoints[] = $xi1;
                    $finalpoints[] = $yi1;
                    $numpoints++;
                    
                    if($draw_capping_skeleton)  imagearc($im,$xi1,$yi1,12,12,0,360,$green);                    
                }
                
            }
        }
  
        // at this end, we add the arrowhead
        
        $finalpoints[] = $ax1;
        $finalpoints[] = $ay1;
        $finalpoints[] = $ax2;
        $finalpoints[] = $ay2;
        $finalpoints[] = $ax3;
        $finalpoints[] = $ay3;
        $finalpoints[] = $ax4;
        $finalpoints[] = $ay4;
        $finalpoints[] = $ax5;
        $finalpoints[] = $ay5;
        
        $numpoints += 5;
        
        if(1==0)
        {
            // now close up the polygon, having been through all the points
            $finalpoints[] = $simple[$i+1][X] + $n2->dx * $width;
            $finalpoints[] = $simple[$i+1][Y] + $n2->dy * $width;
            $numpoints++;
            
            $reversepoints[] = $simple[$i+1][X] - $n2->dx * $width;
            $reversepoints[] = $simple[$i+1][Y] - $n2->dy * $width;
            $numrpoints++;
        }
        
        // combine the forwards and backwards paths, to make a complete loop
        for($i=($numrpoints-1)*2; $i>=0; $i-=2)
        {
            $x = $reversepoints[$i];
            $y = $reversepoints[$i+1];
            
            $finalpoints[] = $x;
            $finalpoints[] = $y;
            $numpoints++;
        }
        // $finalpoints[] contains a complete outline of the line at this stage
        
        // and draw it
        
        print "Drawing polygon and polyline for $numpoints points\n"; 
        print "Polygon: ".imagefilledpolygon($im,$finalpoints,count($finalpoints)/2,$grey)."\n";    
        print "Polyline: ".imagepolyline($im,$finalpoints,count($finalpoints)/2,$black)."\n";
        
    }    
}

print "Done\n";

if(1==0)
{

    $finalpoints = array();
    $reversepoints = array();
    
    $finalpoints[] = $points_x[0];
    $finalpoints[] = $points_y[0];
    $numpoints++;
    $reversepoints[] = $points_x[0];
    $reversepoints[] = $points_y[0];
    $numrpoints++;
    
    for ($i=0; $i <$max_start; $i++)
    //for ($i=0; $i<1; $i++)
    {   
        if($draw_skeleton) imagearc($im,$points_x[$i+1],$points_y[$i+1],120,120,0,360,$blue);
        
        $dx1 = $points_x[$i+1] - $points_x[$i];
        $dy1 = $points_y[$i+1] - $points_y[$i];
        
        $dx2 = $points_x[$i+2] - $points_x[$i+1];
        $dy2 = $points_y[$i+2] - $points_y[$i+1];
            
        $len1 = sqrt($dx1*$dx1 + $dy1*$dy1);
        $len2 = sqrt($dx2*$dx2 + $dy2*$dy2);
                
        $nx1 = $dy1 / $len1;
        $ny1 = -$dx1 / $len1;
        
        $nx2 = $dy2 / $len2;
        $ny2 = -$dx2 / $len2;
        
        $capping = FALSE;
        // figure out the angle between the lines - for very sharp turns, we should do something special
        // (actually, their normals, but the angle is the same and we need the normals later)
        $angle = rad2deg(atan2($ny2,$nx2) - atan2($ny1,$nx1));
        if(abs($angle)>169)  { $capping = TRUE; }
        $capping = FALSE; // override that for now
        
        if($i==0)
        {
            $finalpoints[] = $points_x[$i] + $nx1*$width;
            $finalpoints[] = $points_y[$i] + $ny1*$width;
            $numpoints++;
            
            $reversepoints[] = $points_x[$i] - $nx1*$width;
            $reversepoints[] = $points_y[$i] - $ny1*$width;
            $numrpoints++;
        } 
      
        list($xi1,$yi1) = line_crossing( $points_x[$i] + $nx1*$width, $points_y[$i] + $ny1*$width,
                                    $points_x[$i+1] + $nx1*$width, $points_y[$i+1] + $ny1*$width,
                                    $points_x[$i+1] + $nx2*$width, $points_y[$i+1] + $ny2*$width,
                                    $points_x[$i+2] + $nx2*$width, $points_y[$i+2] + $ny2*$width                                
                                    );
        
        list($xi2,$yi2) = line_crossing( $points_x[$i] - $nx1*$width, $points_y[$i] - $ny1*$width,
                                    $points_x[$i+1] - $nx1*$width, $points_y[$i+1] - $ny1*$width,
                                    $points_x[$i+1] - $nx2*$width, $points_y[$i+1] - $ny2*$width,
                                    $points_x[$i+2] - $nx2*$width, $points_y[$i+2] - $ny2*$width                                
                                    );
        
        if($draw_skeleton)  imagearc($im,$xi1,$yi1,8,8,0,360,$red);        
        if($draw_skeleton)  imagearc($im,$xi2,$yi2,20,20,0,360,$red);
      
        // calculate the extra two points for capping  
        if($capping)
        {
            // the next two are only needed for blunting very acute turns
            
            list($xi3,$yi3) = line_crossing( $points_x[$i] + $nx1*$width, $points_y[$i] + $ny1*$width,
                                    $points_x[$i+1] + $nx1*$width, $points_y[$i+1] + $ny1*$width,
                                    $points_x[$i+1] - $nx2*$width, $points_y[$i+1] - $ny2*$width,
                                    $points_x[$i+2] - $nx2*$width, $points_y[$i+2] - $ny2*$width                                
                                    );
    
            list($xi4,$yi4) = line_crossing( $points_x[$i] - $nx1*$width, $points_y[$i] - $ny1*$width,
                                    $points_x[$i+1] - $nx1*$width, $points_y[$i+1] - $ny1*$width,
                                    $points_x[$i+1] + $nx2*$width, $points_y[$i+1] + $ny2*$width,
                                    $points_x[$i+2] + $nx2*$width, $points_y[$i+2] + $ny2*$width                                
                                    );
            
            if($draw_skeleton)  imagearc($im,$xi3,$yi3,12,12,0,360,$red);
            if($draw_skeleton)  imagearc($im,$xi4,$yi4,12,12,0,360,$red);
            
            
        }
        
        if($capping && $angle > 0)
        {        
            // in here, we need to decide which is the 'outside' of the corner,
            // because that's what we flatten. The inside of the corner is left alone.
            // - depending on the relative angle between the two segments, it could
            //   be either one of these points.
            
            $finalpoints[] = $xi3;
            $finalpoints[] = $yi3;
            $numpoints++;
            
            $finalpoints[] = $xi4;
            $finalpoints[] = $yi4;
            $numpoints++;
            
            $reversepoints[] = $xi2;
            $reversepoints[] = $yi2;
            $numrpoints++;
        }
        
        if($capping && $angle < 0)
        {
            $reversepoints[] = $xi3;
            $reversepoints[] = $yi3;
            $numrpoints++;
            
            $reversepoints[] = $xi4;
            $reversepoints[] = $yi4;
            $numrpoints++;
            
            $finalpoints[] = $xi2;
            $finalpoints[] = $yi2;
            $numpoints++;
        }
        
        if(!$capping)
        {
            $finalpoints[] = $xi1;
            $finalpoints[] = $yi1;
            $numpoints++;
                    
            $reversepoints[] = $xi2;
            $reversepoints[] = $yi2;
            $numrpoints++;
        }   
    }
    
    $finalpoints[] = $points_x[$i+1] + $nx2*$width;
    $finalpoints[] = $points_y[$i+1] + $ny2*$width;
    $numpoints++;
    
    $reversepoints[] = $points_x[$i+1] - $nx2*$width;
    $reversepoints[] = $points_y[$i+1] - $ny2*$width;
    $numrpoints++;
    
    for($i=($numrpoints-1)*2; $i>=0; $i-=2)
    {
        $x = $reversepoints[$i];
        $y = $reversepoints[$i+1];
        
        $finalpoints[] = $x;
        $finalpoints[] = $y;
        $numpoints++;
    }
    
    // $finalpoints[] contains a complete outline of the line at this stage
    
    // ***********************************************************************************
    
    print "Drawing polygon and polyline for $numpoints points\n"; 
    print "Polygon: ".imagefilledpolygon($im,$finalpoints,count($finalpoints)/2,$grey)."\n";    
    print "Polyline: ".imagepolyline($im,$finalpoints,count($finalpoints)/2,$black)."\n";
    
    
    // ***********************************************************************************
    
    list($x, $y, $i, $a) = find_distance_coords_angle($curvepoints,$totaldistance * 0.5);
    
    print "50% mark at $x,$y ($a degrees)\n";
    
    imagearc($im,$x,$y,15,15,0,360,$red);
    imagearc($im,$x,$y,17,17,0,360,$red);
    draw_tangent($x,$y,$a, 50, $red, $im);
    
    
    list($x, $y, $i, $a) = find_distance_coords_angle($curvepoints,$totaldistance * 0.25);
    
    print "25% mark at $x,$y ($a degrees)\n";
    imagearc($im,$x,$y,5,5,0,360,$red);
    imagearc($im,$x,$y,7,7,0,360,$red);
    draw_tangent($x,$y,$a, 50, $red, $im);
    
    list($x, $y, $i, $a) = find_distance_coords_angle($curvepoints,$totaldistance * 0.75);
    
    print "75% mark at $x,$y ($a degrees)\n";
    imagearc($im,$x,$y,5,5,0,360,$red);
    imagearc($im,$x,$y,7,7,0,360,$red);
    draw_tangent($x,$y,$a, 50, $red, $im);
    
    
    draw_tangent(100,100, 90, 100, $red, $im);
    draw_tangent(100,100, 120, 100, $red, $im);
}   // end of 1==0

}

// ***********************************************************************************

imagepng($im,"output2.png");


function draw_tangent($x, $y, $angle, $length, $colour, $im)
{
       
    $dy = ($length/2) * sin(deg2rad($angle));
    $dx = - ($length/2) * cos(deg2rad($angle));
    
    $x1 = $x - $dx;
    $y1 = $y - $dy;
    
    $x2 = $x + $dx;
    $y2 = $y + $dy;
    
    imageline($im, $x1,$y1, $x2, $y2, $colour);
}

// Take a spine, and strip out all the points that are co-linear with the points either side of them
function simplify_spine(&$input)
{
    global $epsilon;
    
    $output = array();
    
    $output []= $input[0];
    $n=1;
    $c = count($input)-2;
    $skip=0;
    
    for($n=1; $n<$c; $n++)
    {
        // only copy the point if n-1, n and n+1 don't form a line
        $dx1 = $input[$n][X] - $input[$n-1][X];
        $dx2 = $input[$n+1][X] - $input[$n][X];
        
        $dy1 = $input[$n][Y] - $input[$n-1][Y];
        $dy2 = $input[$n+1][Y] - $input[$n][Y];
        
        $r1 = 0;
        $r2 = 0;
      
        // for non-vertical, get the slope
        if($dx1 != 0) $r1 = ($dy1/$dx1);
        if($dx2 != 0) $r2 = ($dy2/$dx2);
        
        if ( abs($r2-$r1) > $epsilon )
        {
            $output []= $input[$n];
        }
        else
        {
            // ignore n
            $skip++;
            
        }
    }
    
    debug("Skipped $skip points of $c\n");
    
    $output []= $input[$c+1];
    return $output;
}

function find_distance_coords_rough(&$roughspine, $distance)
{
    print "Looking for $distance\n";
    for($i=1; $i < count($roughspine);$i++)
    {
        $d1 = $roughspine[$i-1][DISTANCE];
        $d = $roughspine[$i][DISTANCE];
        $dd = $d - $d1;
        
        print "$i ($d)\n";
        if($d > $distance )
        {
            print "this line --> between ".($i-1)." and $i ($d1 -> $d)\n";
            $ratio = ($distance - $d1) / $dd;
            
            $miss = $distance - $d1;
            print "A ratio of $ratio makes the distance ".($d1 + ($dd*$ratio)). " (DD is $dd, and the miss is $miss)\n";
            
            $dx = $roughspine[$i][X] - $roughspine[$i-1][X];
            $dy = $roughspine[$i][Y] - $roughspine[$i-1][Y];
            
            print "diff is $dx, $dy\n";
            
            $dx = $dx * $ratio;
            $dy = $dy * $ratio;
            
            print "ratiodiff is $dx, $dy\n";
                        
            $rx = $roughspine[$i-1][X];
            $ry = $roughspine[$i-1][Y];
            
            print "root is $rx, $ry\n";
            
            $halfway_x = $rx + $dx;
            $halfway_y = $ry + $dy;
            
            print "Rough Midpoint is: $halfway_x, $halfway_y ($ratio)\n";
            
            return( array($halfway_x, $halfway_y, $i-1) );
        }
    }
}

function draw_spine_chain($im,$spine,$col, $size=10)
{
    $newn = count($spine);
        
    print "Simplified to $newn points\n";
    for ($i=0; $i < $newn; $i++)
    {   
        imagearc($im,$spine[$i][X],$spine[$i][Y],$size,$size,0,360,$col);
    }
}

function draw_spine($im, $spine,$col)
{
    $max_i = count($spine)-1;
    
    for ($i=0; $i <$max_i; $i++)
    {
        imagelinethick($im,
                    $spine[$i][X],$spine[$i][Y],
                    $spine[$i+1][X],$spine[$i+1][Y],
                    $col,
                    3
                    );
    }
}

function draw_skeleton($im, $points_x, $points_y, $linecolour, $normalcolour, $markercolour, $width)
{
    
    
    $max_i = count($points_x)-1;
    
        for ($i=0; $i <= $max_i; $i++)
        {
            imagearc($im,$points_x[$i],$points_y[$i],15,15,0,360,$markercolour);
            
            if($i<$max_i)
            {
                imageline($im,$points_x[$i],$points_y[$i],$points_x[$i+1],$points_y[$i+1],$linecolour);
        
                $dx = $points_x[$i+1] - $points_x[$i];
                $dy = $points_y[$i+1] - $points_y[$i];
                $len = sqrt($dx*$dx + $dy*$dy);
                
                $dx1 = $dx/$len;
                $dy1 = $dy/$len;
                
                $nx=$dy / $len;
                $ny=-$dx / $len;
                
                
                    // normals at this point
                    imageline($im,
                              $points_x[$i],$points_y[$i],
                              $points_x[$i]+$width*$nx, $points_y[$i]+$width*$ny,
                              $normalcolour);
                    imageline($im,
                              $points_x[$i],$points_y[$i],
                              $points_x[$i]-$width*$nx, $points_y[$i]-$width*$ny,
                              $normalcolour);
                    
                    // normals at the next point
                    imageline($im,
                              $points_x[$i+1],$points_y[$i+1],
                              $points_x[$i+1]+$width*$nx, $points_y[$i+1]+$width*$ny,
                              $normalcolour);
                    imageline($im,
                              $points_x[$i+1],$points_y[$i+1],
                              $points_x[$i+1]-$width*$nx, $points_y[$i+1]-$width*$ny,
                              $normalcolour);
                            
                    // Lines up to the next point...
                    
                    imageline($im,
                              $points_x[$i]+$width*$nx, $points_y[$i]+$width*$ny,
                              $points_x[$i+1]+$width*$nx, $points_y[$i+1]+$width*$ny,
                              $linecolour);
                    
                    imageline($im,
                              $points_x[$i]-$width*$nx, $points_y[$i]-$width*$ny,
                              $points_x[$i+1]-$width*$nx, $points_y[$i+1]-$width*$ny,
                              $linecolour);
                
            }
            
        }
    

}


function imagelinethick($image, $x1, $y1, $x2, $y2, $color, $thick = 1)
{
    /* this way it works well only for orthogonal lines
    imagesetthickness($image, $thick);
    return imageline($image, $x1, $y1, $x2, $y2, $color);
    */
    if ($thick == 1) {
        return imageline($image, $x1, $y1, $x2, $y2, $color);
    }
    $t = $thick / 2 - 0.5;
    if ($x1 == $x2 || $y1 == $y2) {
        return imagefilledrectangle($image, round(min($x1, $x2) - $t), round(min($y1, $y2) - $t), round(max($x1, $x2) + $t), round(max($y1, $y2) + $t), $color);
    }
    $k = ($y2 - $y1) / ($x2 - $x1); //y = kx + q
    $a = $t / sqrt(1 + pow($k, 2));
    $points = array(
        round($x1 - (1+$k)*$a), round($y1 + (1-$k)*$a),
        round($x1 - (1-$k)*$a), round($y1 - (1+$k)*$a),
        round($x2 + (1+$k)*$a), round($y2 - (1-$k)*$a),
        round($x2 + (1-$k)*$a), round($y2 + (1+$k)*$a),
    );
    imagefilledpolygon($image, $points, 4, $color);
    return imagepolygon($image, $points, 4, $color);
}

?>