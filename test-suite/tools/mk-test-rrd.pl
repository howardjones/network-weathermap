#!/usr/bin/perl
foreach $filename ("tests/test_1.rrd", "tests/test_2.rrd", "tests/test_3.rrd",
    "tests/test 4.rrd") {
    ($dev, $ino, $mode, $nlink, $uid, $gid, $rdev, $size, $atime, $mtime, $ctime,
        $blksize, $blocks) = stat($filename);

    if ((time() - $mtime) > 600) {
        unlink($filename);

        $then = time() - 86400;

        $create =
            "create \"$filename\" --start $then --step 300  DS:traffic_in:COUNTER:600:0:100000000 DS:traffic_out:COUNTER:600:0:100000000 RRA:AVERAGE:0.5:1:600 RRA:AVERAGE:0.5:6:700 RRA:AVERAGE:0.5:24:775 RRA:AVERAGE:0.5:288:797 RRA:MAX:0.5:1:600 RRA:MAX:0.5:6:700  RRA:MAX:0.5:24:775  RRA:MAX:0.5:288:797";

        system("rrdtool $create");

        $i = $then +1;
        $now = time();

        while ($i < $now) {
            $command = "rrdtool update \"$filename\" $i:20:20";
            print "$command \n";
            system($command);

            $i += 90;
        }
    } else { print "$filename is new enough\n"; }
}