#!/bin/sh

# Run tests, update test reports and optionally do code-quality reporting

fflag=0

while [ $# -gt 0 ]
do
    case "$1" in
        -f)  fflag=1;;
    esac
    shift
done

if [ $fflag -eq 1 ]; then
	make testcoverage
else
	make test
fi

  grep  Output test-suite/diffs/*.txt | grep -v '|0|' | awk -F: '{ print $1;}' | sed -e 's/.png.txt//' -e 's/test-suite\/diffs\///' > test-suite/failing-images.txt
        test-suite/make-failing-summary.pl test-suite/failing-images.txt test-suite/summary.html > test-suite/summary-failing.html

echo
echo "NOTE: 3 tests have a timestamp in them, and will always fail"

if [ $fflag -eq 1 ]; then

	echo "Running code quality and test coverage reports."
	phpmd lib/ html unusedcode > test-suite/md-unused.html
	phpmd lib/ html codesize,design,naming,cleancode > test-suite/md-rest.html

    # phpcs -p -v --tab-width=4 -s --extensions=php --standard=PEAR --report-full=test-suite/phpcs-report-PEAR.txt  .
    # phpcs -p -v --tab-width=4 -s --extensions=php --sniffs=Generic.PHP.DisallowShortOpenTag,Squiz.PHP.CommentedOutCode --report-full=test-suite/phpcs-report-smelly.txt  .
    # phpcs -p -v --tab-width=4 -s --extensions=php --standard=PSR2 --report-full=test-suite/phpcs-report-PSR-1-2.txt  .
    phpcs -p -v --tab-width=4 -s --extensions=php --standard=PSR2ish --report-full=test-suite/phpcs-report-PSR-1-2.txt lib 

    phpcpd lib/  > test-suite/cut-paste.txt

fi
