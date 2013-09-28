#!/usr/bin/perl

# 	test-suites/make-failing-summary.pl test-suite/failing-images.txt test-suite/summary.html > test-suite/failing-summary.html

$failing_list = $ARGV[0];
$summary = $ARGV[1];

open(FAIL,$failing_list) || die($!);
%failing = map { $_, 1 } map { split } <FAIL>;
close(FAIL);

$count = 0;
foreach (keys %failing) {
	$count++;
}

print "$count failing.<p>";
print localtime()."<p>";

open(SUMMARY,$summary) || die($!);

while(<SUMMARY>) {
	if (m/<h4>(\S+)/ ) {
		$conf = $1;
		if( $failing{$conf} ) {
			print $_;
			$differences = 0;
			open(DIFF, "test-suite/diffs/${conf}.png.txt") || die($!);
			while(<DIFF>) {
				if( m/^Output: \|(\d+)/ ) {
					$differences = $1;	
				}				
			}
			close(DIFF);

			print "$differences differences.<br>";
			print "<a href='approve.php?cf=".$conf."'>Approve left image as new reference</a>";
			print "<hr>";
		}
	} else {
		print $_;
	}
	
}
close(SUMMARY);
