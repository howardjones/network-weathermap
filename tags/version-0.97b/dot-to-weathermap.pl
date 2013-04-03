#!/usr/bin/perl
$targetwidth = 1024;
$targetheight = 768;

while (<>) {
    chomp;
    chomp;

    if (m/^graph\s+(\S+)\s+(\S+)\s+(\S+)/) {
        $scale = $1;
        $width = $2;
        $height = $3;

        $scalefactor = $targetwidth / $width;

        if ($scalefactor > ($targetheight / $height))
            { $scalefactor = $targetheight / $height; }

        $new_width = $width * $scalefactor;
        $new_height = $height * $scalefactor;

        print "WIDTH $targetwidth\nHEIGHT $targetheight\n\n";
    }

    if (m/^node\s+(\S+)\s+(\S+)\s+(\S+)\s/) {
        $x = $2;
        $y = $3;
        $name = $1;
        $x = int($x * $scalefactor);
        $y = int($targetheight - $y * $scalefactor);

        print "NODE $name\n\tPOSITION $x $y\nLABEL $name\n\n";
    }

    if (m/^edge\s+(\S+)\s+(\S+)\s+(\d+)\s+(.*)/) {
        $n1 = $1;
        $n2 = $2;
        $vias = $3;
        $rest = $4;

        @bits = split(/\s+/, $rest);

        print "LINK $n1$n2$i\n\tWIDTH 3\n\tNODES $n1 $n2\n";

        for ($j = 0; $j < $vias; $j++) {
            $x = shift @bits;
            $y = shift @bits;
            $x = int($x * $scalefactor);
            $y = int($targetheight - $y * $scalefactor);
            print "\tVIA $x $y\n";
        }
        print "\n";
    }
    $i++;
}