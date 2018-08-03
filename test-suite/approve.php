<?php



 $version = explode('.', PHP_VERSION);
        $phptag = "php" . $version[0];

	$test = ( isset($_REQUEST['cf']) ? $_REQUEST['cf'] : '');

	$reference = "references/$test.png"; 
	$last_result = "results1-$phptag/$test.png"; 

	if( file_exists($last_result) ) {
                try {
		copy($last_result,$reference);
		print "Copied $last_result to $reference <a href='summary-failing.html'>OK</a>";
                } 
                catch (Exception $e) {
		print "Copy failed for $last_result. Permissions?";
                }
	} else {
		print "Failed to handled $last_result";
	}

