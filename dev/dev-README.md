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
in a consistent manner. The script is here: <a href="install-notes.txt">install-notes.txt</a>.

## Basic Philosophy ##

Any time someone has to ask a question about how something in Weathermap works, I 
treat it as a failure - either the feature is too complicated, or the manual doesn't 
explain it well enough. Possibly, the user has totally misunderstood what should
happen, in which case I let myself off the hook, but that's not the default position.

## Code Standards ##

I try to stick to PSR-2 for coding formatting type stuff. The only exceptions are:

* Line-length (slavishly following this one makes for strange formatting, IMO)
* camelCase variable names where we're talking to Cacti. Cacti doesn't use this naming 
style, so some things *can't* follow it 

There's a `phpcs.xml` in the repo's root directory, that I use to check and fix the 
layout from time to time. You should be able to use `vendor/bin/phpcs` to do the same
checks (and `vendor/bin/phpcbf` to fix anything that can be fixed automatically)

## Testing ##

### Functional & Config-based Tests (automated) ###

The subversion repo includes tests for phpunit. You can run them all in one go
by running ./test.sh in the weathermap folder. This runs phpunit, and then compiles
a report of the failing tests. There are some traditional unit tests, but a lot of
the testing works as follows:

* load a small map config
* run it
* save config (as the editor would)
* load *that* config
* run it
* compare the two generated image files (to see if the config is "losing" bits - WriteConfig bugs)
* compare the generated image to a reference image (to see if the config works as expected)
  
Tests/ConfigTest.php does this for every .conf file that it finds in the test-suite/tests
directory. There are around 150 tests so far. Each one tests a particular feature or
combination. Every time I find a new problem, I add new tests. Every time I add a new
feature, I add new tests. This covers a lot of the core weathermap features by now.

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

### UI Functional Tests (manual) ###

This is the boring part.

There's a manual check-list in <a href="pre-release-tests.md">pre-release-tests.md</a> that exercises all the clickable things. It
has proven useful so far though, and caught a few issues that I wouldn't have otherwise noticed
until someone else told me about them.

I should probably look at something like Selenium for this stuff.

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

Just make sure that the version number is correct at the top of the main Makefile, then run 'make release'. The zip
(and a tgz file I don't currently use) will appear in a directory alongside the weathermap one called ../releases

Unless you already have files there. Then it all breaks a bit.

If you *do* end up making your own zips, I'd appreciate it if you either don't distribute them widely, or clearly label
where they came from, and that you will be supporting them, just to avoid confusion.