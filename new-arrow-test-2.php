<?php

include_once("WeatherMap.functions.php");

$type = 1;
$width = 15;

$map = NULL;

$q2_percent = 50;
$unidirectional = false;
$widths = array();

$widths[IN] = $width;
$widths[OUT] = $width;

$dirs = array(OUT,IN);


$draw_skeleton = true;

$im = imagecreatetruecolor(980, 980);

$white = imagecolorallocate($im,255,255,255);
$black = imagecolorallocate($im,0,0,0);
$red = imagecolorallocate($im,255,0,0);
$green = imagecolorallocate($im,0,255,0);
// $grey = imagecolorallocate($im,192,192,192);
$grey = imagecolorallocatealpha($im,192,192,192,64);
$blue = imagecolorallocate($im,0,0,255);

imagefilledrectangle($im,0,0,1024,1024,$white);

if($type==0)
{
    $points_x = array(100,300);
    $points_y = array(100,350);
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
    $points_x = array(300,100,300,100);
    $points_y = array(900,300,700,100);
}

if($type==4)
{
    $npoints = rand(3,10);
    
    $points_x = array();
    $points_y = array();
    for($i=0; $i < $npoints; $i++)
    {
        $points_x []= rand(20,900);
        $points_y []= rand(20,900);
    }
    
}

$max_i = count($points_x)-1;
$max_start = $max_i -1;
$totaldistance = 0;

$roughspine = array();

$lx = $points_x[0];
$ly = $points_y[0];

$i=0;

# $roughspine []= array($points_x[$i],$points_y[$i],$totaldistance);

for ($i=0; $i <=$max_i; $i++)
{
    $roughspine []= array($points_x[$i],$points_y[$i],$totaldistance);
    print "|$i $totaldistance\n";
    
    $dx = $lx - $points_x[$i];
    $dy = $ly - $points_y[$i];
    
    $totaldistance += sqrt($dx * $dx + $dy*$dy);
        
    $lx = $points_x[$i];
    $ly = $points_y[$i];
    
}
$roughspine []= array($points_x[$max_i+1],$points_y[$max_i+1],$totaldistance);

print "Rough distance is $totaldistance\n";
$halfway = $totaldistance/2;

list($halfway_x,$halfway_y,$halfwayindex) = find_distance_coords_rough($roughspine,$halfway);

print "Rough Midpoint is: $halfway_x,$halfway_y\n";

imagearc($im,$halfway_x, $halfway_y,30,10,0,360,$blue);

// fill this with (x,y,distance) triples for use with find_distance()
$curvepoints = calc_straight($points_x, $points_y);
$totaldistance = $curvepoints[count($curvepoints)-1][2];

print "Distance is $totaldistance\n";

$halfway = $totaldistance * ($q2_percent/100);
if($unidirectional)
{
        $halfway = $totaldistance;
        $dirs = array(OUT);
}

// loop increment, start point, width, labelpos, fillcolour, outlinecolour, commentpos
$arrowsettings[OUT] = array(+1, 0, $widths[OUT], 0, $fillcolours[OUT], $outlinecolour, 5);
$arrowsettings[IN] = array(-1, count($curvepoints) - 1, $widths[IN], 0, $fillcolours[IN], $outlinecolour, 95);

// we calculate the arrow size up here, so that we can decide on the
// minimum length for a link. The arrowheads are the limiting factor.
list($arrowsize[IN],$arrowwidth[IN]) = calc_arrowsize($widths[IN], $map, $linkname);
list($arrowsize[OUT],$arrowwidth[OUT]) = calc_arrowsize($widths[OUT], $map, $linkname);
                
// the 1.2 here is empirical. It ought to be 1 in theory.
// in practice, a link this short is useless anyway, especially with bwlabels.
$minimumlength = 1.2*($arrowsize[IN]+$arrowsize[OUT]);

# warn("$linkname: Total: $totaldistance $arrowsize $arrowwidth $minimumlength\n");
if($totaldistance <= $minimumlength)
{
    die("Skipping drawing very short link ($linkname). Impossible to draw! Try changing WIDTH or ARROWSTYLE? [WMWARN01]\n");
}

list($halfway_x,$halfway_y,$halfwayindex) = find_distance_coords($curvepoints,$halfway);
print "Midpoint is: $halfway_x,$halfway_y\n";
imagearc($im,$halfway_x, $halfway_y,30,10,0,360,$blue);

print "Building line1\n";

$line1 = array();
$line2 = array();

for($i=0; $i < $halfwayindex; $i++)
{
    $line1 []= $curvepoints[$i];    
}
$line1 []= array($halfway_x, $halfway_y, $halfway);

print "Building line2\n";
for($i=count($curvepoints)-1; $i > $halfwayindex; $i--)
{
    $line2 []= $curvepoints[$i];    
}
$line2 []= array($halfway_x, $halfway_y, $halfway);

print "Done\n";

// line1 contains the forward line
// line2 contains the reverse line

// we can treat them as two single-direction lines now.

// first, simplify all those useless points out of the way

$spine_1 = simplify_spine($line1);
$spine_2 = simplify_spine($line2);



print "Done\n";

if($draw_skeleton)
{
    for ($i=0; $i <=$max_i; $i++)
    {
        imagearc($im,$points_x[$i],$points_y[$i],15,15,0,360,$red);
        
        if($i<$max_i)
        {
            imageline($im,$points_x[$i],$points_y[$i],$points_x[$i+1],$points_y[$i+1],$blue);
    
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
                          $green);
                imageline($im,
                          $points_x[$i],$points_y[$i],
                          $points_x[$i]-$width*$nx, $points_y[$i]-$width*$ny,
                          $green);
                
                // normals at the next point
                imageline($im,
                          $points_x[$i+1],$points_y[$i+1],
                          $points_x[$i+1]+$width*$nx, $points_y[$i+1]+$width*$ny,
                          $green);
                imageline($im,
                          $points_x[$i+1],$points_y[$i+1],
                          $points_x[$i+1]-$width*$nx, $points_y[$i+1]-$width*$ny,
                          $green);
                        
                // Lines up to the next point...
                
                imageline($im,
                          $points_x[$i]+$width*$nx, $points_y[$i]+$width*$ny,
                          $points_x[$i+1]+$width*$nx, $points_y[$i+1]+$width*$ny,
                          $green);
                
                imageline($im,
                          $points_x[$i]-$width*$nx, $points_y[$i]-$width*$ny,
                          $points_x[$i+1]-$width*$nx, $points_y[$i+1]-$width*$ny,
                          $green);
            
        }
        
    }
}




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


// ***********************************************************************************

imagepng($im,"output.png");


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


function simplify_spine(&$input)
{
    $output = array();
    
    $output []= $input[0];
    $n=1;
    $c = count($input)-2;
    $skip=0;
    
    for($n=1; $n<$c; $n++)
    {
        // only copy the point if n-1, n and n+1 don't form a line
        $dx1 = $input[$n][0] - $input[$n-1][0];
        $dx2 = $input[$n+1][0] - $input[$n][0];
        
        $dy1 = $input[$n][1] - $input[$n-1][1];
        $dy2 = $input[$n+1][1] - $input[$n][1];
        
        if ( $dx1 ==0 || $dx2==0 || ($dy1/$dx1) != ($dy2/$dx2) )
        {
            $output []= $input[$n];
        }
        else
        {
            // ignore n
            $skip++;
            
        }
    }
    
    print "Skipped $skip points of $c\n";
    
    $output []= $input[$c+1];
    return $output;
}

function find_distance_coords_rough(&$roughspine, $distance)
{
    print "Looking for $distance\n";
    for($i=1; $i < count($roughspine);$i++)
    {
        $d1 = $roughspine[$i-1][2];
        $d = $roughspine[$i][2];
        $dd = $d - $d1;
        
        print "$i ($d)\n";
        if($d > $distance )
        {
            print "this line --> between ".($i-1)." and $i ($d1 -> $d)\n";
            $ratio = ($distance - $d1) / $dd;
            
            $miss = $distance - $d1;
            print "A ratio of $ratio makes the distance ".($d1 + ($dd*$ratio)). " (DD is $dd, and the miss is $miss)\n";
            
            $dx = $roughspine[$i][0] - $roughspine[$i-1][0];
            $dy = $roughspine[$i][1] - $roughspine[$i-1][1];
            
            print "diff is $dx, $dy\n";
            
            $dx = $dx * $ratio;
            $dy = $dy * $ratio;
            
            print "ratiodiff is $dx, $dy\n";
                        
            $rx = $roughspine[$i-1][0];
            $ry = $roughspine[$i-1][1];
            
            print "root is $rx, $ry\n";
            
            $halfway_x = $rx + $dx;
            $halfway_y = $ry + $dy;
            
            print "Rough Midpoint is: $halfway_x, $halfway_y ($ratio)\n";
            
            return( array($halfway_x, $halfway_y, $i-1) );
        }
    }
}

?>