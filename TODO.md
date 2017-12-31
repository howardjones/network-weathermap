## TODO

MapScale should really be divided into MapScale and MapLegend.

Also, a MapScaleEntry class with the testing for ColourFromValue

PSR-0/PSR-1/PSR-2 Stuff:
- ~~namespaces (now we're in 5.3-land)~~
- ~~one class per file~~
- ~~use autoloader (now we're in 5.3-land)~~
- ~~use phpcs/phpcbf for conformance checking~~

make a (no map yet) thumbnail for UI (that is, an image the same size as the map thumbnails so layout doesn't go strange)

per-map debugging - needs UI to enable!

map scheduling - needs UI

consolidated map-properties page in UI? (variable settings, plus schedule, plus debug, plus runtime info, etc)

Rewrite Cacti integration and editor manual pages

KEYBG/KEYOUTLINE/KEYTEXTCOLOR per-scale

Winding order problem (I think) with sharp-angled vias - see conf_via3.conf

entries in weathermap_data are never expired

standalone version of the cacti map browser

thumb2 file - 48x48px thumb to show in map management screen.

Include note about timezone stuff in docs

improve usage_stats to cover things other than keywords

Some kind of shorthand for 'last non-relative node'? Then the sub chunks WOULD be generic

{node:this_link_a:name}

TAG + {node:tagged:name}

Cacti editor picker - if it's not traffic_in/traffic_out, let you pick the DSes

Editor picker - plugin-supplied picker listings - allow for Zabbix,Zenoss,Cricket,Groundwork,MRTG,Orion,Smokeping,WUG etc to supply useful picker info

Browse subdirs for images in editor: http://forums.cacti.net/about34831.html

Text Alignment in labels: http://forums.cacti.net/about35264.html

Some kind of special warning for if ALL the maps fail in a cycle. 

Also some sort of highlight to show that the map wasn't updated successfully last time it ran (permissions problems).

ProcessString for timepos string (+ some formatted date strings for use elsewhere?)


WISHES
-------

Editor off by two? Nodes centered instead of aligning at UR
http://forums.cacti.net/viewtopic.php?t=29616&start=0&postdays=0&postorder=asc&highlight=

Hide Maps in Cacti Tab?
http://forums.cacti.net/viewtopic.php?p=117591

Processing Token - 95th percentile
http://forums.cacti.net/viewtopic.php?p=135555

weathermap / smokeping (editor picker)
http://forums.cacti.net/viewtopic.php?t=27516

I just want to add a newline to the TIMEPOS output
http://forums.cacti.net/viewtopic.php?t=26885

Multi-line text, plus Mactrack DS
http://forums.cacti.net/viewtopic.php?t=26790

Non-percentage scales
http://forums.cacti.net/viewtopic.php?t=16372

Node Position as percentage along link? (fake labels etc)

Cron-style control per-map (to allow for a monthly average map, run once a month)
http://www.freebsd.org/cgi/cvsweb.cgi/src/usr.sbin/cron/lib/entry.c?rev=1.20

Image archiving - for animation, previous months monthly reports, etc.

New Link styles - dotted, dashed, split


Before 0.96
-----------

Refresh before viewing graphs? (glenp)
http://forums.cacti.net/viewtopic.php?p=145866#145866

Editor placeholder for dynamic icon? (glenp)
http://forums.cacti.net/viewtopic.php?p=145749#145749

rrdtool cdefs

spaces in filenames/targets where appropriate - quotes, and escaped quotes "([^\"\\]*(?:\\.[^\"\\]*)*)" | (\S+)\s | \s

(multiline (bw)labels? - interpret \n)

Coverage/Usage stats
WUG DS
Nagios DS
Solarwinds Orion DS
Nabbix DS
RRD xport DS (use rrdtool graph and PRINT in fact)

Extra DS for WUG, Nagios
USESCALE can use any variable - USESCALE scalename {node:this:cactihost_latency}

Background image links: http://art.gnome.org/backgrounds/abstract/
Background image generator: http://splintax.blogspot.com/2006/01/starfish-automatically-generated.html

RRDserv-compatible target?  rrdfile.rrd@hostname:portnumber   rrdremote:host:port:file.rrd (need to learn PHP sockets)

php-rrdtool support in rrd DS plugin



