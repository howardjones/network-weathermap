#!/bin/sh

COMPARE="compare"

INDEX="index-comparisons.html"
INDEX2="index.html"

echo "<body bgcolor='#cccccc'>" > $INDEX
echo "<body bgcolor='#cccccc'>" > $INDEX2


echo "<a href='index-references.html'>Reference Images</a>" >> $INDEX2
echo "<a href='index-results.html'>Result Images</a>" >> $INDEX2
echo "<a href='index-comparisons.html'>Comparison Images</a>" >> $INDEX2
echo "<hr><h1>Exceptions</h1>" >> $INDEX2
echo "<h2>" >> $INDEX2
date >> $INDEX2
echo "</h2>" >> $INDEX2

for source in tests/*.conf; do
  base=`basename $source`

  destination="comparisons/${base}.png"
  destination2="comparisons/${base}.txt"
  reference="references/${base}.png"
  result="results/${base}.png"

  echo "$source: $reference vs $result -> $destination"
  $COMPARE -metric AE $reference $result $destination  > $destination2 2>&1

 DIFFCOUNT=`cat $destination2`

	if [ $DIFFCOUNT != "0" ]; then
		echo "<h3>$source ($DIFFCOUNT)</h3>" >> $INDEX2
  		echo "<img src='${destination}'><br />" >> $INDEX2
	fi

  echo "<hr>$source<br />" >> $INDEX
  echo "<img src='${destination}'><br />" >> $INDEX
done

echo "</body>" >> $INDEX
echo "</body>" >> $INDEX2

echo
