#!/usr/bin/perl

# 	test-suites/make-failing-summary.pl test-suite/failing-images.txt test-suite/summary.html > test-suite/failing-summary.html
use Image::Size;
use List::Util qw(sum max);

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

@percents = ();

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

            ($x, $y) = imgsize("test-suite/references/${conf}.png");
            $totalpixels = $x * $y;

            $percent = sprintf("%.2f%%", $differences/$totalpixels*100);
            push(@percents, $percent);


            printf("<p><b>To run:</b><code>./weathermap --config test-suite/tests/%s --debug --no-data</code></p>\n", $conf);
			print "<p>$percent - $differences differences.</p>\n";

			print "<a href='approve.php?cf=".$conf."'>Approve left image as new reference</a>\n";
			print "<hr>";
		}
	} else {
		print $_;
	}
}

$summary = "";
if ($#percents > 0) {
    $summary = sprintf("\n\nAverage %.2f%% Worst %.2f%%\n\n", sum(@percents)/$#percents, max(@percents) );
    print $summary;
}

print STDERR $summary;
print STDERR "$count failing\n\n";

close(SUMMARY);
