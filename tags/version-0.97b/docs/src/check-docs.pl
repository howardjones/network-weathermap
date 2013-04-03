#!/usr/bin/perl
$some_dir = ".";


#
# Check that all the little config XML files actually appear somewhere in the index.xml
#

opendir(DIR, $some_dir) || die "can't opendir $some_dir: $!";
@bits = grep {
    /^(node|global|link)_/ && /\.xml$/ && -f "$some_dir/$_"
} readdir(DIR);
closedir DIR;

open(INDEX, "index.xml") || die($!);

while (<INDEX>) {
    chomp;
    chomp;

    #             <xi:include href="node_label.xml" />
	#  <xi:include href = "node_overlibgraph.xml" />


    if (m/xi:include\s+href\s*=\s*"([^"]+)"/) {
        $target = $1;
        $seen { $target } = 1;
    }
}

foreach $file (@bits) {
    if ($seen { $file } == 0) { print "$file is not referenced.\n"; }
}
