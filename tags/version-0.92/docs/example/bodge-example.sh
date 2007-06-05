#!/bin/sh

php ../../weathermap --config example-fake.conf --randomdata
cat example-fake.conf | sed -e 's/_OVER/OVER/' |  grep -v graph_page.html | grep -v graph_image.png > example.conf
