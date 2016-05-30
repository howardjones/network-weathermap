#!/bin/sh

for filename in test-suite/tests/*.conf configs/*.conf; do
	echo $filename
	time ./weathermap --config $filename --no-data
done
