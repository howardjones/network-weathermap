<?php

	$test = ( isset($_REQUEST['cf']) ? $_REQUEST['cf'] : '');

	$reference = "references/$test.png"; 
	$last_result = "results1-php5/$test.png"; 

	if(! is_writable($reference)) {
		print "Unable to write to $reference - check permissions.";
		exit();
	}

	if( file_exists($last_result) ) {
		copy($last_result,$reference);
		print "Copied $last_result to $reference <a href='summary-failing.html'>OK</a>";
	} else {
		print "Failed to handled $last_result";
	}

?>
