#!/bin/sh

echo "<body bgcolor='#cccccc'>" > index.html

for source in *.conf; do
  destination=`basename $source`
  destination="${destination}.png"
  echo "$source -> $destination"
  echo "<hr>$source<br />" >> index.html
  echo "<img src='${destination}'><br />" >> index.html
  if [ ! -f $destination ]; then
    ../weathermap --config $source --output $destination
  else
   echo "Skipping (exists)"
  fi
done

echo "</body>" >> index.html
