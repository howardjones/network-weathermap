GLOBALS = global_*.xml intro_*.xml
LINKS = link_*.xml
NODES = node_*.xml

VERSION ?= 0.97b

all: config-reference.html

#
# We process index.xml twice - once to generate the ToC, and then again to generate
# the actual reference guide
#
contents.xml: contents.xsl index.xml $(GLOBALS) $(LINKS) $(NODES)
	echo '<pop></pop>' > contents.xml
	xsltproc --xinclude contents.xsl index.xml > contents-t.xml
	cp contents-t.xml contents.xml

config-reference.html: reference.xsl index.xml contents.xml $(GLOBALS) $(LINKS) $(NODES)
	xsltproc --xinclude reference.xsl index.xml | ./relink-reference.pl > config-reference.html
	# I will probably have to do some kind of pennance for this mess
	echo "<?php \$$WEATHERMAP_VERSION='v$(VERSION)'; ?>" > vars.php
	php config-reference.html > ../pages/config-reference.html
	php main.html > ../pages/main.html
	php errorcodes.html | ./relink-reference.pl ALL > ../pages/errorcodes.html
	php index.html > ../index.html
	php cli-reference.html > ../pages/cli-reference.html
	php cacti-plugin.html > ../pages/cacti-plugin.html
	php install-cacti.html > ../pages/install-cacti.html
	php install-cacti-editor.html > ../pages/install-cacti-editor.html
	php install-cli.html > ../pages/install-cli.html
	php install-cli-editor.html > ../pages/install-cli-editor.html
	php upgrading.html > ../pages/upgrading.html
	php editor.html | ./relink-reference.pl ALL > ../pages/editor.html
	php advanced.html | ./relink-reference.pl ALL > ../pages/advanced.html
	php faq.html | ./relink-reference.pl ALL > ../pages/faq.html
	php changes.html | ./relink-reference.pl ALL > ../pages/changes.html
	php targets.html | ./relink-reference.pl ALL > ../pages/targets.html

