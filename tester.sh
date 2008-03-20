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

cp overlib.js tests/
cp in.png in2.png in3.png tests/
cp out.png out2.png out3.png tests/

./mk-torture.pl > tests/torture.conf

# CONFIGS="configs/096-test.conf configs/095-test.conf random-bits/suite-1.conf random-bits/suite-2.conf tests/torture.conf"
CONFIGS="configs/096-test.conf configs/095-test.conf"

if [ "X$@" != "X" ]; then
	CONFIGS=$@
fi

echo > tests/results.html

for conf in ${CONFIGS}; do
	echo ========================================================================================
	echo $conf
	echo

	TESTNAME=`echo $conf | sed -e 's/\//_/g' -e 's/\.conf//g' `
	TEST2NAME="${TESTNAME}-2"

	time php weathermap --config $conf --image-uri ${TESTNAME}.png --dumpconfig tests/${TESTNAME}.cfg --output tests/${TESTNAME}.png --htmloutput tests/${TESTNAME}.html
	time php weathermap --config tests/${TESTNAME}.cfg --image-uri ${TEST2NAME}.png --dumpconfig tests/${TEST2NAME}.cfg --output tests/${TEST2NAME}.png --htmloutput tests/${TEST2NAME}.html
	compare tests/${TESTNAME}.png tests/${TEST2NAME}.png tests/${TESTNAME}-compare.png

	sed s/$TESTNAME/XXXX/g < tests/${TESTNAME}.html > tests/${TESTNAME}.clean.html
	sed s/$TEST2NAME/XXXX/g < tests/${TEST2NAME}.html > tests/${TEST2NAME}.clean.html
	diff tests/${TESTNAME}.clean.html tests/${TEST2NAME}.clean.html
	diff tests/${TESTNAME}.cfg tests/${TEST2NAME}.cfg

#	sort < $conf > tests/${TESTNAME}.sorted
#	sort < tests/${TESTNAME}.cfg > tests/${TESTNAME}.sorted2
#	diff tests/${TESTNAME}.sorted tests/${TESTNAME}.sorted2

	echo "<img src='${TESTNAME}-compare.png'><br />" >> tests/results.html
	
done
