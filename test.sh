#!/bin/sh
# Run tests, then produce some summary reports

make test
php test-suite/make-failing-summary.php > test-suite/summary-failing.html

