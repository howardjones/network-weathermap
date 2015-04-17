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
	vendor/bin/phpunit --coverage-html test-suite/code-coverage/ --coverage-clover build/logs/clover.xml Tests/
	vendor/bin/phpunit --coverage-html test-suite/code-coverage-codeonly/ Tests/Code
else
	vendor/bin/phpunit Tests/	
fi

grep  Output test-suite/diffs/*.txt | grep -v '|0|' | awk -F: '{ print $1;}' | sed -e 's/.png.txt//' -e 's/test-suite\/diffs\///' > test-suite/failing-images.txt
test-suite/make-failing-summary.pl test-suite/failing-images.txt test-suite/summary.html > test-suite/summary-failing.html

echo
# echo "NOTE: 3 tests have a timestamp in them, and will always fail"

if [ $fflag -eq 1 ]; then

	echo "Running code quality and test coverage reports."
	vendor/bin/phpmd lib/ html unusedcode > test-suite/md-unused.html
	vendor/bin/phpmd lib/ html codesize,design,naming,cleancode > test-suite/md-rest.html

    vendor/bin/phpcpd lib/  > test-suite/cut-paste.txt

    # phpcs -p -v --tab-width=4 -s --extensions=php --standard=PEAR --report-full=test-suite/phpcs-report-PEAR.txt  .
    # phpcs -p -v --tab-width=4 -s --extensions=php --sniffs=Generic.PHP.DisallowShortOpenTag,Squiz.PHP.CommentedOutCode --report-full=test-suite/phpcs-report-smelly.txt  .
    # phpcs -p -v --tab-width=4 -s --extensions=php --standard=PSR2 --report-full=test-suite/phpcs-report-PSR-1-2.txt  .
    vendor/bin/phpcs -p -v --tab-width=4 -s --extensions=php --standard=PSR2 --report-full=test-suite/phpcs-report-PSR-1-2.txt lib

fi
