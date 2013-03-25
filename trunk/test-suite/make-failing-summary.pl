#!/usr/bin/perl

# 	test-suites/make-failing-summary.pl test-suite/failing-images.txt test-suite/summary.html > test-suite/failing-summary.html

$failing_list = $ARGV[0];
$summary = $ARGV[1];

open(FAIL,$failing_list) || die($!);
%failing = map { $_, 1 } map { split } <FAIL>;
close(FAIL);

open(SUMMARY,$summary) || die($!);

while(<SUMMARY>) {
	if (m/<h4>(\S+)/ ) {
		$conf = $1;
		if( $failing{$conf} ) {
			print $_;
			print "<a href='approve.php?cf=".$conf."'>Approve left image as new reference</a>";
		}
	} else {
		print $_;
	}
	
}
close(SUMMARY);
