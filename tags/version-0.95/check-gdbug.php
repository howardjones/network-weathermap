<?php
	print "Some version of the GD graphics library have a bug in their\nhandling of Alpha channels. Unfortunately, Weathermap uses these to draw Nodes.\n\n";
	print "This program will test if your PHP installation is using a buggy GD library.\n";

	print "If you are, you should either use PHP's built-in (aka 'bundled') GD library,\n";
	print " or update to GD Version 2.0.34 or newer.\n\nWeathermap REQUIRES working Alpha support.\n\n";

	print "Let's see if you have the GD transparency bug...\n";
	print "If you see no more output, or a segfault, then you do,\nand you'll need to upgrade.\n\n";
	print "If you get other errors, like 'undefined function', then run check.php to\nmake sure that your PHP installation is otherwise OK.\n\nHere we go...\n";

	$temp_width = 10;
	$temp_height = 10;
	
	$node_im=imagecreatetruecolor($temp_width,$temp_height );
	imageSaveAlpha($node_im, TRUE);
	$nothing=imagecolorallocatealpha($node_im,128,0,0,127);
	imagefill($node_im, 0, 0, $nothing);
	imagedestroy($node_im);
	
	print "Nope. We got past the risky part, so that's good.\nYour GD library looks healthy.\n";
?>
