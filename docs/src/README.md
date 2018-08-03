The documentation is produced in kind of a convoluted way, but I wanted to
be able to update it with the same Makefile that produces the release zip
files, and still be able to change the style in a relatively painless way.

So:
1) There's an XML file per config item
2) index.xml includes all of those
3) index.xml is processed by XSLT to make config-reference.html
4) Another script looks for the names of config keywords, and turns
   them into links to the relevant reference section - relink-reference.pl
5) That output, and all the other html files are processed by PHP
   to allow me to use includes and variables (the version number) in 
   pages, but still have a static manual.
6) The final output all goes to the pages/ directory, which is what
   gets distributed.

Sorry about that! :-)

HJ March 2013


--

check-docs.pl -> Verify that all the config reference sections are actually included
relink-reference.pl -> Look for mentions of the config keywords, and linkify them

relink-reference.php -> start of a rewrite of relink-reference.pl to avoid having a dependency on perl for building a PHP application.

