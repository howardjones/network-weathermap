<?php

	$test = ( isset($_REQUEST['cf']) ? $_REQUEST['cf'] : '');

	$reference = "references/$test.png"; 
	$last_result = "results1-php7/$test.png"; 

	if( file_exists($last_result) ) {
		copy($last_result,$reference);
		print "Copied $last_result to $reference <a href='summary-failing.html'>OK</a>";
	} else {
		print "Failed to handled $last_result";
	}

