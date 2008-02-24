#!/usr/bin/perl

# first we find all the possible links, and their scope, from the contents section

open( CONTENTS, "contents.xml" );
while (<CONTENTS>) {
    if (m/id="context_([^"]+)/) {
        $scope = $1;
    }
    if (m/href="([^"]+)">([^<]+)</) {
        $map{ $scope . '|' . $2 } = $1;
        $map{$2}                  = $1;
        $words{$2}                = 1;
    }
}
close(CONTENTS);

$wholefile = 0;
if($ARGV[0] eq 'ALL') { $wholefile=1; }

# then read in the whole file (lazy)
while (<STDIN>) {
    chomp;
    chomp;

    # we only want to autolink in the description sections

	s/<\/p>/ <\/p>/g;
    if (m/id="s_scope_([^"]+)/) { $scope    = $1; }
    if (m/name="([^"]+)"/)      { $lastseen = $1; }

    if ($indesc && m/<div/) { $indesc++; }
    if ( $indesc && m/\/div>/ ) { $indesc--; }

    if ($indesc || $wholefile) {
        foreach $word ( split( /\s+/, $_ ) ) {
		$bareword = $word;
		$bareword =~ tr/A-Z//cd;

            if ( $words{$bareword} ) {
		# print "!";
                $link = $map{"$scope|$bareword"};
                $link ||= $map{$bareword};
		if($wholefile)
		{
                $link ||= $map{"GLOBAL|$bareword"};
                $link ||= $map{"LINK|$bareword"};
                $link ||= $map{"NODE|$bareword"};
		}

                # if ( ($link ne '') && ($lastseen ne "${scope}_${bareword}") ) {
                if ( ($link ne '') ) {

			if($wholefile) { $link = "config-reference.html".$link; }
			
                    $word = sprintf( "<a href=\"%s\">%s</a>", $link, $word );
                }

            }
            print $word. " ";
        }

    }
    else {
        print $_;
    }

    if (m/div class="description"/) { $indesc = 1; }

    print "\n";
}
