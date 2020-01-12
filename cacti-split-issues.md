# Outstanding Issues

## Poller

~~Move to using new Poller/Runtime code~~

* ~~make the stats consistent (targets:0)~~

* ~~add some more useful stats (nlinks,nnodes, target plugins, nvias, ncurves, nangles, pix dimensions, pix format)~~

* memory leak in map generation (now we can see stats!)

## Editor

Map Style - setting label to bits doesn't change anything

~~Cacti picker is blank~~

~~Timestamp and Title are in Imagemap, but don't make it into the HTML(? - only Z=1000 items)~~

~~BWlabels have no imagemap box (:2 and :3 _do_ exist, but don't "work") - bwlabel boxes are 1 pixel wide~~

~~font samples is a black box~~

~~Map Style has a strange orange box at the bottom??~~

~~lagged imagemap?~~

~~Feature: "Lock to" list for nodes shouldn't really include itself~~

Feature: Relative overlay should include an arrowhead (?)

~~Config editing all appears on a single line.~~

---

## Mgmt plugin

~~Settings: Add links to mgmt10.php~~

~~Settings: Back to Map Admin  links to mgmt10.php~~

~~Group name on maplist links to empty page~~

~~Add on group editor links to mgmt10.php~~

~~Edit on group editor links to empty page~~

~~Add on group editor links to empty page~~

~~Settings editor for groups shows wrong layout~~

~~Sort buttons missing on group list~~

~~Delete button missing on group list~~

~~Add/Edit setting form is missing~~

---
## User plugin

~~handleViewCycleFiltered is missing in user plugin~~

~~handleViewCycle is missing in user plugin~~

~~map combo box doesn't actually work~~

~~management links next to full page map title are wrong ( need makeManagementURL() )~~

## Misc

~~bwlabels are showing up in MAP imagemap, not the link...~~

* the random-bits and CLI tools need some love

* cacti-rebuild needs to use Runtime

## Notes

Before paratest

    make test  238.31s user 9.02s system 111% cpu 3:42.46 total

