#!/bin/sh

TEST=$1

./weathermap --config test-suite/tests/$TEST --output test-suite/references/$TEST.png
