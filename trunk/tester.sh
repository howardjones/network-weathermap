#!/bin/sh

# simple regression test for weathermap
# generate a map, dump a config, use the dumped config to generate a new map 
# if the ReadConfig and WriteConfig are working, then the two maps should be very similiar
# we use ImageMagick's compare program to check that.
#
# can extend this in the future to have 'reference' images for the suite-n.cfg files

time php weathermap --config configs/095-test.conf --dumpconfig test1.cfg --output first1.png
time php weathermap --config test1.cfg --output test1.png
compare first1.png test1.png compare1.png

time php weathermap --config configs/suite-1.conf --dumpconfig test2.cfg --output first2.png
time php weathermap --config test2.cfg --output test2.png
compare first2.png test2.png compare2.png

time php weathermap --config configs/suite-2.conf --dumpconfig test3.cfg --output first3.png
time php weathermap --config test3.cfg --output test3.png
compare first3.png test3.png compare3.png

./mk-torture.pl > torture.conf
time php weathermap --config torture.conf --dumpconfig test4.cfg --output first4.png
