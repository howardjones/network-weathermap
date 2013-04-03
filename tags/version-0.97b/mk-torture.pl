#!/usr/bin/perl
print "WIDTH 2048\nHEIGHT 768\n\n";
print "LINK DEFAULT\nWIDTH 2\n\n";
print "NODE centre\nPOSITION 1000 350\n\n";

for ($i = 0; $i < 100; $i++) {

    $x = $i * 50;
    $y = 50;
    $n = $i;
    print "NODE edge$n\nPOSITION $x $y\nLABEL $i\n\n";

    $y = 700;
    $n = $i +100;
    print "NODE edge$n\nPOSITION $x $y\nLABEL a$i\n\n";
}

for ($i = 0; $i < 200; $i++) {
    $j = $i +1;
    $j = $j %200;
    print "LINK lnk$i\nNODES centre edge$i\nBWLABEL bits\nTARGET tests/test_1.rrd\n\n";
    print "LINK link$i\nNODES edge$i edge$j\nVIA edge$i 10 30\nVIA edge$j -10 -20\nBWLABEL none\nTARGET tests/test_2.rrd\n\n";
}