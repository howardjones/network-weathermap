<?php


	print "You should either use PHP's built-in (aka 'bundled') GD library,\n";
	print " or update to GD Version 2.0.34 or newer.\n\n";

	print "Let's see if you have the GD transparency bug...\n";
	print "If you see no more output, then you do.\n\n";

	$temp_width = 10;
	$temp_height = 10;
	
	$node_im=imagecreatetruecolor($temp_width,$temp_height );
	imageSaveAlpha($node_im, TRUE);
	$nothing=imagecolorallocatealpha($node_im,128,0,0,127);
	imagefill($node_im, 0, 0, $nothing);
	imagedestroy($node_im);
	
	print "Nope. We got past the risky part, so that's good.\nYour GD library look healthy.\n";
?>
