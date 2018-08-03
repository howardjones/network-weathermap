## TODO for next 0.98 release

*  if permissions in Cacti said Anyone *and* you, then no map images are shown
*  Imagemap sortorder problem in editor. If you can see it, you can click it.
*  Manual rewrite
*  config parser and other optimisations from working with ikorzha758

Commit old enough to have old-style code layout in it(!) for reference:
https://github.com/howardjones/network-weathermap/tree/3d8ae103dcddd67b1a1c9db109f5985e496ecd77

### Small features

* In-editor generator for switch port arrays http://forums.cacti.net/viewtopic.php?f=16&t=52618

---
## TODO for 0.98b

*  Pull new Cacti plugin layout from master (input validation etc)
*  Pull new editor layout?



# last checkin for previous ReadConfig rewrite
https://github.com/howardjones/network-weathermap/blob/f2f636190baf3767d376074e9766bb554bf82be5/Weathermap.class.php

###  Performance: 

*  ~~readconfig changes from before~~
*  ~~htmlimagemap direct-access~~
*  ~~processstring shortcuts~~

*  imagecreatefromfile isn't that fast - a cache for icon files might be useful
*  imagecopyresampled also
*  imagettftext

*  rip out all the wimage* stuff
*  add a 'get scaled icon' function and put the caching in there
   *  so multiple same-scaled copies of the same icon are not loaded or scaled
   *  do that only for images small than (say 128x128) which are obviously icons
      and not background images which will be huge and never reused.

* string drawing doesn't understand WMFont 



/Applications/XAMPP/bin/mysqldump --no-data -u root --password= cacti weathermap_maps weathermap_data weathermap_auth weathermap_groups weathermap_settings settings user_auth user_auth_perms user_auth_realm > test-suite/data/weathermap-empty.sql
/Applications/XAMPP/bin/mysqldump --xml  -u root --password= cacti weathermap_maps weathermap_data weathermap_auth weathermap_groups weathermap_settings settings user_auth > file.xml
