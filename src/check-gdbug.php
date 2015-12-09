<?php
	# This file is from Weathermap version 0.97d

	ob_start();
	
	if (isset($argv)) {
	      $env = "CLI";
	} else {
	      $env = "WEB";
	}
	
	echo wordwrap("Some version of the GD graphics library have a bug in their handling of Alpha channels. Unfortunately, Weathermap uses these to draw Nodes.");
	echo ($env=='CLI'?"\n\n":"\n<p>");
	
	echo wordwrap("This program will test if your PHP installation is using a buggy GD library.");
	echo ($env=='CLI'?"\n\n":"\n<p>");
	
	echo wordwrap("If you are, you should either use PHP's built-in (aka 'bundled') GD library, or update to GD Version 2.0.34 or newer. Weathermap REQUIRES working Alpha support.");
	echo ($env=='CLI'?"\n\n":"\n<p>");
	
	echo wordwrap("Let's see if you have the GD transparency bug...");
	echo ($env=='CLI'?"\n\n":"\n<p>");
	echo wordwrap("If you see no more output, or a segfault, then you do, and you'll need to upgrade.");
	echo ($env=='CLI'?"\n\n":"\n<p>");
	echo wordwrap("If you get other errors, like 'undefined function', then run check.php to\nmake sure that your PHP installation is otherwise OK.");
		     echo ($env=='CLI'?"\n\n":"\n<p>");
	echo "Here we go...";
	echo ($env=='CLI'?"\n\n":"\n<p>");
	
	// make sure even the affected folks can see the explanation
	ob_flush();
	flush();
	
	$temp_width = 10;
	$temp_height = 10;
	
	$node_im=imagecreatetruecolor($temp_width,$temp_height );
	imagesavealpha($node_im, TRUE);
	$nothing=imagecolorallocatealpha($node_im,128,0,0,127);
	imagefill($node_im, 0, 0, $nothing);
	imagedestroy($node_im);
	
	echo "...nope. We got past the risky part, so that's good.\nYour GD library looks healthy.\n";
	echo ($env=='CLI'?"\n":"\n<p>");