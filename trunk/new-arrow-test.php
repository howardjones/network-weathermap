<?php

$type = 1;
$width = 15;


$im = imagecreatetruecolor(1024,1024);



$white = imagecolorallocate($im,255,255,255);
$black = imagecolorallocate($im,0,0,0);
$red = imagecolorallocate($im,255,0,0);
$green = imagecolorallocate($im,0,255,0);
// $grey = imagecolorallocate($im,192,192,192);
$grey = imagecolorallocatealpha($im,192,192,192,64);
$blue = imagecolorallocate($im,0,0,255);

imagefilledrectangle($im,0,0,1024,1024,$white);


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

$max_i = count($points_x)-1;
$max_start = $max_i -1;

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
    imagearc($im,$points_x[$i+1],$points_y[$i+1],120,120,0,360,$blue);
    
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
    
    imagearc($im,$xi1,$yi1,8,8,0,360,$red);        
    imagearc($im,$xi2,$yi2,20,20,0,360,$red);
  
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
        
        imagearc($im,$xi3,$yi3,12,12,0,360,$red);
        imagearc($im,$xi4,$yi4,12,12,0,360,$red);
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

imagepng($im,"output.png");



// take the same set of points that imagepolygon does, but don't close the shape
function imagepolyline($image, $points, $npoints, $color)
{
	for ($i=0; $i < ($npoints - 1);
	$i++) { imageline($image, $points[$i * 2], $points[$i * 2 + 1], $points[$i * 2 + 2], $points[$i * 2 + 3],
		$color); }
}


// find the point where a line from x1,y1 through x2,y2 cross another line through x3,y3 and x4,y4
// (the point might not be between those points, but beyond them)
function line_crossing($x1,$y1,$x2,$y2, $x3,$y3,$x4,$y4)
{
    
    // First, check that the slope isn't infinite.
    // if it is, tweak it to be merely huge
    if($x1 != $x2) { $slope1 = ($y2-$y1)/($x2-$x1); }
    else { $slope1 = 1e10; print "Slope1 is infinite.\n";}
    
    if($x3 != $x4) { $slope2 = ($y4-$y3)/($x4-$x3); }
    else { $slope2 = 1e10; print "Slope2 is infinite.\n";}
    
    $a1 = $slope1;
    $a2 = $slope2;
    $b1 = -1;
    $b2 = -1;   
    $c1 = ($y1 - $slope1 * $x1 );
    $c2 = ($y3 - $slope2 * $x3 );
    
    $det_inv = 1/($a1*$b2 - $a2*$b1);
    
    $xi = (($b1*$c2 - $b2*$c1)*$det_inv);
    $yi = (($a2*$c1 - $a1*$c2)*$det_inv);
    
    return(array($xi,$yi));
}

?>