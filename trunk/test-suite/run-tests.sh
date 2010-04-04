#!/bin/sh

DIRECTORY="results"
PHP="/usr/local/bin/php"

# /usr/local/php4/bin/php

if [ "X$1" != "X" ]; then
	PHP=$1
fi

if [ "X$2" != "X" ]; then
	DIRECTORY=$2
fi

echo "Processing $DIRECTORY"

touch coverage.txt
rm coverage.txt

INDEX="index-$DIRECTORY.html"

echo "<body bgcolor='#cccccc'>" > $INDEX

for source in tests/*.conf; do
  destination=`basename $source`
  destination="$DIRECTORY/${destination}.png"

  echo -n "$source -> $destination"
  echo "<hr>$source<br />" >> $INDEX
  echo "<img src='${destination}'><br />" >> $INDEX
  if [ ! -f $destination ]; then
	echo
    $PHP ../weathermap --config $source --output $destination --trackcoverage
  else
   echo " - Skipped (exists)"
  fi
done

echo "</body>" >> $INDEX
