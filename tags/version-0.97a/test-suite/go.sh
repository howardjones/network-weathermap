#!/bin/sh
#
#
# A couple of these test files use fonts that are not in svn for copyright reasons

# Rebuild References (should be VERY RARE)
 ./run-tests.sh /usr/local/bin/php references

# Run tests with default PHP (PHP5 for me)
 rm results-php5/*
 ./run-tests.sh /usr/local/bin/php results-php5


# Run test with PHP4
 rm results-php4/* 
./run-tests.sh /usr/local/php4/bin/php results-php4


# Run comparison against references
echo "PHP5 Comparisons"
 ./compare-results.sh php5
# Run comparison against references
echo "PHP4 Comparisons"
 ./compare-results.sh php4

