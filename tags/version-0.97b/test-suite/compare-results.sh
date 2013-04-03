#!/bin/sh

COMPARE="compare"

SUFFIX="php5"

if [ "X$1" != "X" ]; then
	SUFFIX=$1
fi

INDEX="index-comparisons-$SUFFIX.html"
INDEX2="index-$SUFFIX.html"
INDEX2TMP="indext.html"

echo "<body bgcolor='#cccccc'>" > $INDEX

echo "" > $INDEX2TMP

BADCOUNT=0

for source in tests/*.conf; do
  base=`basename $source`

  destination="comparisons-$SUFFIX/${base}.png"
  destination2="comparisons-$SUFFIX/${base}.txt"
  reference="references/${base}.png"
  result="results-$SUFFIX/${base}.png"

  if [ -f $result ]; then

#  echo "$source: $reference vs $result -> $destination"
  $COMPARE -metric AE $reference $result $destination  > $destination2 2>&1

 DIFFCOUNT=`cat $destination2`

	if [ $DIFFCOUNT != "0" ]; then
		echo "<h3>$source ($DIFFCOUNT)</h3>" >> $INDEX2TMP
  		echo "<img src='${destination}'><br />" >> $INDEX2TMP
		BADCOUNT=`expr $BADCOUNT + 1`
	fi

  echo "<hr>$source<br />" >> $INDEX
  echo "<img src='${destination}'><br />" >> $INDEX
  fi
done

echo "</body>" >> $INDEX

echo "<body bgcolor='#cccccc'>" > $INDEX2
echo "<a href='index-references.html'>Reference Images</a>" >> $INDEX2
echo "<a href='index-results-$SUFFIX.html'>Result Images</a>" >> $INDEX2
echo "<a href='index-comparisons-$SUFFIX.html'>Comparison Images</a>" >> $INDEX2
echo "<hr><h1>Exceptions ($BADCOUNT)</h1>" >> $INDEX2
echo "<h2>" >> $INDEX2
date >> $INDEX2
echo "</h2>" >> $INDEX2

cat $INDEX2TMP >> $INDEX2
rm $INDEX2TMP

echo "</body>" >> $INDEX2

echo
echo "There were $BADCOUNT different tests"
echo "See $INDEX2 for details."
echo
