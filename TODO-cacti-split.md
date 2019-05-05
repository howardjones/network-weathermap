# TODO

* Re-introduce 0.98a Cacti plugin code
* Rewrite to use MapManager (and UIBase?)
* Update poller code to __really__ use the new Poller classes
* Fix remaining editor issues
* Ensure that all the Cacti <-> Weathermap links are clearly defined, and separated from core code.

--> Weathermap 1.0 for Cacti 0.8.8

* Create a new repo for weathermap-cacti, and delete all the core stuff, ready to hand over to Netniv.
* ??? magic

--> Weathermap 1.0 for Cacti 0.8.8 and 1.x 

----

# Design

* Should Weathermap core be a library, so that the NMS plugin can just use composer install/update to get it?

  * in that world, the CLI tool would be another consumer of the same library