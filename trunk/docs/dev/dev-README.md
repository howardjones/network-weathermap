# Weathermap development and release management #

## Introduction ##

So you can play along at home, here are some notes on how Weathermap prepared
for release, how to create a dev environment, and how to complete the pre-release
testing. 

First, I use two VMs: one for actual coding, and a second as a clean system to do
final testing. A lot of the subversion repo doesn't end up in the final zip, so 
it's quite easy to accidentally miss a file out of the packing.list and have a 
broken version. Two totally separate Cacti installs in one box would also work.
Both VMs are built using a script to get the appropriate packages installed 
in a consistent manner.

## Testing ##

### Unit Tests (automated) ###

The subversion repo includes unit tests for phpunit. You can run them all in one go
by running ./test.sh in the weathermap folder. This runs phpunit, and then compiles
a report of the failing tests. There are some traditional unit tests, but a lot of
the testing works as follows:

* load a small map config
* run it
* save config (as the editor would)
* load *that* config
* save a second copy
* compare the two generated config files
* compare the generated image to a reference image
  
Tests/ConfigTest.php does this for every .conf file that it finds in the test-suite/tests
directory. There are around 150 tests so far. Each one tests a particular feature or
combination. Every time I find a new problem, I add new tests. Every time I add a new
feature, I add new tests.

Each test can have a global SET variable to indicate which version of Weathermap is needed
to run it. This is useful for excluding new features from testing. This looks like:

    SET REQUIRES_VERSION 0.98

The test runner looks for that, and will skip tests when runn with a lower version. The only
exception is if the current version includes 'dev' in the name - then it will always run all
tests.

After the tests have run, you can look at test-suite/index.html to get summary reports of all
image-comparison tests, or just the failing ones. ImageMagick is used to compare images, so 
each 'result' is 3 images - the output from the test, the reference image, and a comparison
image, which shows pink where there are differences. Sometimes it's just that different
platforms have different Freetype or GD versions, and so fonts are shifted around by a pixel
here and there. There's also an 'approve' link in that report, so you can update the reference
image to use the current output, if you think that the current output is actually correct (or
isn't a bug, just library differences).

### UI Tests (manual) ###

This is the boring part.

## Building Documentation ##

The manual is produced in a somewhat convoluted way, but it seemed like a good idea at the time,
and is intended to reduce the formatting errors in the final result.

The manual is 'compiled' from HTML/PHP source files for the text pages, and from XML files for the
configuration reference. Each config reference section has a specific structure that is turned into 
HTML by a couple of XSLT scripts and xsltproc - one for the table of contents, and one for the rest.
A perl script then extracts the table of contents, and uses it to turn any references to config keywords
in any pages into links to the correct reference section. Finally, each page is run through PHP, so we
can do variable substitution for things like the version number, and "top & tail" each page consistently
with the CSS headers etc.

The nice thing with this workflow is that if you break the XML, it all just stops. You don't end up with
a manual where everything is in 36-point Comic Sans or anything too crazy. You also don't need to worry 
about formatting beyond the occasional 'em' or 'strong'. Links to other sections are made for you. All
sectional formatting is made for you. The links between sections are (nearly) always correct. 

Also, the example map in the manual is fiddled with a little bit to add the dummy graphs. This is taken
care of in the project Makefile.

### Adding Documentation ###

If you add a keyword, and need to document it (why wouldn't you?). You can copy an existing .xml file in
the docs/src directory and modify it. 'scope' is the section of the config file it appears in (global, node or link).
You also need to add a new xi:include tag in the right section of index.xml so that your new keyword gets picked up.

If you just need to update documentation, modify the existing .xml file, and run 'make' in that directory to update the
final docs. You may need to 'touch index.xml' just to force a rebuild.

## Creating the final zip ##

