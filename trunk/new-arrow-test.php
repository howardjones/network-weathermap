<?php

$type = 1;
$width = 20;


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
    $points_x = array(200,100,300,160);
    $points_y = array(700,300,700,100);
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
{
    
    $corner_x = $points_x[$i+1];
    $corner_y = $points_y[$i+1];
    
    imagearc($im,$corner_x,$corner_y,120,120,0,360,$blue);
    
    $dx1 = $points_x[$i+1] - $points_x[$i];
    $dy1 = $points_y[$i+1] - $points_y[$i];
    
    $dx2 = $points_x[$i+2] - $points_x[$i+1];
    $dy2 = $points_y[$i+2] - $points_y[$i+1];
    
    $len1 = sqrt($dx1*$dx1 + $dy1*$dy1);
    $len2 = sqrt($dx2*$dx2 + $dy2*$dy2);
    
    if($dx1 != 0) { $slope1 = $dy1/$dx1; }
    else { $slope1 = 1e10; print "Slope1 is infinite.\n";}
    
    if($dx2 != 0) { $slope2 = $dy2/$dx2; }
    else { $slope2 = 1e10; print "Slope2 is infinite.\n";}
        
    $nx1 = $dy1 / $len1;
    $ny1 = -$dx1 / $len1;
    
    $nx2 = $dy2 / $len2;
    $ny2 = -$dx2 / $len2;
    
    $line1_x = $points_x[$i] + $nx1*$width;
    $line1_y = $points_y[$i] + $ny1*$width;
    
    $line2_x = $points_x[$i] - $nx1*$width;
    $line2_y = $points_y[$i] - $ny1*$width;
    
    $line3_x = $points_x[$i+1] + $nx2*$width;
    $line3_y = $points_y[$i+1] + $ny2*$width;
    
    $line4_x = $points_x[$i+1] - $nx2*$width;
    $line4_y = $points_y[$i+1] - $ny2*$width;


    if($i==0)
    {
        $finalpoints[] = $line1_x;
        $finalpoints[] = $line1_y;
        $numpoints++;
        
        $reversepoints[] = $line2_x;
        $reversepoints[] = $line2_y;
        $numrpoints++;
    }
  
  
    $a1 = $slope1;
    $a2 = $slope2;
    $b1 = -1;
    $b2 = -1;
    
    // **********************
    
    $c1 = ($line1_y - $slope1 * $line1_x );
    $c2 = ($line3_y - $slope2 * $line3_x );
    
    $det_inv = 1/($a1*$b2 - $a2*$b1);
    
    $xi = (($b1*$c2 - $b2*$c1)*$det_inv);
    $yi = (($a2*$c1 - $a1*$c2)*$det_inv);
    
    $finalpoints[] = $xi;
    $finalpoints[] = $yi;
    $numpoints++;
    
    imagearc($im,$xi,$yi,5,5,0,360,$red);
    
    $c1 = ($line2_y - $slope1 * $line2_x );
    $c2 = ($line4_y - $slope2 * $line4_x );
    
    $det_inv = 1/($a1*$b2 - $a2*$b1);
    
    $xi = (($b1*$c2 - $b2*$c1)*$det_inv);
    $yi = (($a2*$c1 - $a1*$c2)*$det_inv);
    
    print "$xi $yi   $a1 $a2 $c1 $c2 $det_inv\n";
    
    $reversepoints[] = $xi;
    $reversepoints[] = $yi;
    $numrpoints++;
    
    imagearc($im,$xi,$yi,20,20,0,360,$red);
    
    if($miterlimit==0)
    {
        // the next two are only needed for blunting very acute turns
        
        $c1 = ($line1_y - $slope1 * $line1_x );
        $c2 = ($line4_y - $slope2 * $line4_x );
        
        $det_inv = 1/($a1*$b2 - $a2*$b1);
        
        $xi = (($b1*$c2 - $b2*$c1)*$det_inv);
        $yi = (($a2*$c1 - $a1*$c2)*$det_inv);
        
        imagearc($im,$xi,$yi,12,12,0,360,$red);
        
        $c1 = ($line2_y - $slope1 * $line2_x );
        $c2 = ($line3_y - $slope2 * $line3_x );
        
        $det_inv = 1/($a1*$b2 - $a2*$b1);
        
        $xi = (($b1*$c2 - $b2*$c1)*$det_inv);
        $yi = (($a2*$c1 - $a1*$c2)*$det_inv);
        
        imagearc($im,$xi,$yi,12,12,0,360,$red);
    }  
    
}

$line5_x = $points_x[$i+1] + $nx2*$width;
$line5_y = $points_y[$i+1] + $ny2*$width;
$line6_x = $points_x[$i+1] - $nx2*$width;
$line6_y = $points_y[$i+1] - $ny2*$width;

$finalpoints[] = $line5_x;
$finalpoints[] = $line5_y;
$numpoints++;
$reversepoints[] = $line6_x;
$reversepoints[] = $line6_y;
$numrpoints++;

for($i=($numrpoints-1)*2; $i>=0; $i-=2)
{
    $x = $reversepoints[$i];
    $y = $reversepoints[$i+1];
    
    $finalpoints[] = $x;
    $finalpoints[] = $y;
    $numpoints++;
}

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

?>

