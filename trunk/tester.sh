#!/bin/sh

# simple regression test for weathermap
# generate a map, dump a config, use the dumped config to generate a new map 
# if the ReadConfig and WriteConfig are working, then the two maps should be very similiar
# we use ImageMagick's compare program to check that.
#
# can extend this in the future to have 'reference' images for the suite-n.cfg files

echo ========================================================================================
echo "095-test.conf"
echo
time php weathermap --config configs/095-test.conf --dumpconfig test1.cfg --output first1.png --htmloutput first1.html
time php weathermap --config test1.cfg --output test1.png --htmloutput test1.html
compare first1.png test1.png compare1.png

sed 's/first1/XXXX/g' < first1.html > first1a.html
sed 's/test1/XXXX/g' < test1.html > test1a.html
diff first1a.html test1a.html

echo ========================================================================================
echo "suite-1.conf"
echo

time php weathermap --config configs/suite-1.conf --dumpconfig test2.cfg --output first2.png --htmloutput first2.html
time php weathermap --config test2.cfg --output test2.png --htmloutput test2.html
compare first2.png test2.png compare2.png
sed 's/first2/XXXX/g' < first2.html > first2a.html
sed 's/test2/XXXX/g' < test2.html > test2a.html
diff first2a.html test2a.html

echo ========================================================================================
echo "suite-2.conf"
echo
time php weathermap --config configs/suite-2.conf --dumpconfig test3.cfg --output first3.png --htmloutput first3.html
time php weathermap --config test3.cfg --output test3.png --htmloutput test3.html
compare first3.png test3.png compare3.png
sed 's/first3/XXXX/g' < first3.html > first3a.html
sed 's/test3/XXXX/g' < test3.html > test3a.html
diff first1a.html test1a.html

echo ========================================================================================
echo "Torture test"
echo
./mk-torture.pl > torture.conf
time php weathermap --config torture.conf --dumpconfig test4.cfg --output first4.png
