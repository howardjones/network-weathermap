#!/bin/sh

# simple regression test for weathermap
# generate a map, dump a config, use the dumped config to generate a new map 
# if the ReadConfig and WriteConfig are working, then the two maps should be very similiar
# we use ImageMagick's compare program to check that.
#
# can extend this in the future to have 'reference' images for the suite-n.cfg files

if [ ! -d tests ]; then
	mkdir tests
fi

echo ========================================================================================
echo "095-test.conf"
echo
time php weathermap --config configs/095-test.conf --dumpconfig tests/test1.cfg --output tests/first1.png --htmloutput tests/first1.html
time php weathermap --config tests/test1.cfg --output tests/test1.png --htmloutput tests/test1.html
compare tests/first1.png tests/test1.png tests/compare1.png

sed 's/first1/XXXX/g' < tests/first1.html > tests/first1a.html
sed 's/test1/XXXX/g' < tests/test1.html > tests/test1a.html
diff tests/first1a.html tests/test1a.html

echo ========================================================================================
echo "suite-1.conf"
echo

time php weathermap --config configs/suite-1.conf --dumpconfig tests/test2.cfg --output tests/first2.png --htmloutput tests/first2.html
time php weathermap --config tests/test2.cfg --output tests/test2.png --htmloutput tests/test2.html
compare tests/first2.png tests/test2.png tests/compare2.png
sed 's/first2/XXXX/g' < tests/first2.html > tests/first2a.html
sed 's/test2/XXXX/g' < tests/test2.html > tests/test2a.html
diff tests/first2a.html tests/test2a.html

echo ========================================================================================
echo "suite-2.conf"
echo
time php weathermap --config configs/suite-2.conf --dumpconfig tests/test3.cfg --output tests/first3.png --htmloutput tests/first3.html
time php weathermap --config tests/test3.cfg --output tests/test3.png --htmloutput tests/test3.html
compare tests/first3.png tests/test3.png tests/compare3.png
sed 's/first3/XXXX/g' < tests/first3.html > tests/first3a.html
sed 's/test3/XXXX/g' < tests/test3.html > tests/test3a.html
diff tests/first1a.html tests/test1a.html

echo ========================================================================================
echo "Torture test"
echo
./mk-torture.pl > tests/torture.conf
time php weathermap --config tests/torture.conf --dumpconfig tests/test4.cfg --output tests/first4.png
