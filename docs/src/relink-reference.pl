#!/usr/bin/perl

# first we find all the possible links, and their scope, from the contents section

open(CONTENTS, "contents.xml");

while (<CONTENTS>) {
    if (m/id="context_([^"]+)/) { $scope = $1; }

    if (m/href="([^"]+)">([^<]+)</) {
        $map { $scope . '|' . $2 } = $1;
        $map { $2 } = $1;
        $words { $2 } = 1;
    }
}
close(CONTENTS);

# seed the additional stuff that won't otherwise get autolinked.
foreach $c (qw(AICONFILLCOLOR AICONOUTLINECOLOR LABELFONTCOLOR LABELBGCOLOR LABELOUTLINECOLOR LABELFONTSHADOWCOLOR))
    {
    $map { "NODE|$c" } = "#NODE_COLORS";
    $map { $c } = "#NODE_COLORS";
    $words { $c } = 1;
}

foreach $c (qw(OUTLINECOLOR BWOUTLINECOLOR BWFONTCOLOR BWBOXCOLOR COMMENTFONTCOLOR)) {
    $map { "LINK|$c" } = "#LINK_COLORS";
    $map { $c } = "#LINK_COLORS";
    $words { $c } = 1;
}

foreach $c (qw(BGCOLOR TIMECOLOR TITLECOLOR KEYTEXTCOLOR KEYOUTLINECOLOR KEYBGCOLOR)) {
    $map { "GLOBAL|$c" } = "#GLOBAL_COLORS";
    $map { $c } = "#GLOBAL_COLORS";
    $words { $c } = 1;
}

foreach $c (qw(TITLEFONT KEYFONT TIMEFONT)) {
    $map { "GLOBAL|$c" } = "#GLOBAL_FONT";
    $map { $c } = "#GLOBAL_FONT";
    $words { $c } = 1;
}

$wholefile = 0;

if ($ARGV[0] eq 'ALL') { $wholefile = 1; }

# then read in the whole file (lazy)
while (<STDIN>) {
    chomp;
    chomp;

    # we only want to autolink in the description sections

    s/<\/p>/ <\/p>/g;

    if (m/id="s_scope_([^"]+)/) { $scope = $1; }

    if (m/name="([^"]+)"/) { $lastseen = $1; }

    if ($indesc && m/<div/) { $indesc++; }

    if ($indesc && m/\/div>/) { $indesc--; }

    if ($indesc || $wholefile) {
        foreach $word(split(/\s+/, $_)) {
            $bareword = $word;
            $prefix = "";

            if ($bareword =~ m/(.*)>(.*)/) {
                $bareword = $2;
                $prefix = $1 . ">";
            }
            $bareword =~ tr/A-Z//cd;

            if ($bareword ne "") {
                # print STDERR "|$bareword|$word\n";

                if ($words { $bareword }) {
                    # print "!";
                    $link = $map { "$scope|$bareword" };
                    $link ||= $map { $bareword };

                    if ($wholefile) {
                        $link ||= $map { "GLOBAL|$bareword" };
                        $link ||= $map { "LINK|$bareword" };
                        $link ||= $map { "NODE|$bareword" };
                    }

                    # if ( ($link ne '') && ($lastseen ne "${scope}_${bareword}") ) {
                    if (($link ne '')) {
                        if ($wholefile) { $link = "config-reference.html" . $link; }

                        $word =~ s/^$prefix//;
                        $word = sprintf("%s<a href=\"%s\">%s</a>", $prefix, $link, $word);
                    }
                }
            }

            if ($word eq 'targets.html')
                { $word = '<a href="targets.html">targets.html</a>'; }
            print $word . " ";
        }
    } else { print $_; }

    if (m/div class="description"/) { $indesc = 1; }

    print "\n";
}