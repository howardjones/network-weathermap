
## Alpha in GD

* A truecolor image has alphablending ON by default
* imagetruecolortopalette() seems to destroy the alpha channel, regardless of the alphablending setting. This is a problem, because imagecolorize needs a paletted image to work.
* 
