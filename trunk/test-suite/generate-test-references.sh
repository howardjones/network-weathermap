#!/bin/sh

DIRECTORY="references"

INDEX="index-$DIRECTORY.html"

echo "<body bgcolor='#cccccc'>" > $INDEX

for source in tests/*.conf; do
  destination=`basename $source`
  destination="$DIRECTORY/${destination}.png"

  echo "$source -> $destination"
  echo "<hr>$source<br />" >> $INDEX
  echo "<img src='${destination}'><br />" >> $INDEX
  if [ ! -f $destination ]; then
    php ../weathermap --config $source --output $destination
  else
   echo "Skipping (exists)"
  fi
done

echo "</body>" >> $INDEX
