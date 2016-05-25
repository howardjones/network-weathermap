GLOBALS = global_*.xml intro_*.xml
LINKS = link_*.xml
NODES = node_*.xml

VERSION ?= 0.98

all: config-reference.php

#
# We process index.xml twice - once to generate the ToC, and then again to generate
# the actual reference guide
#
contents.xml: contents.xsl index.xml $(GLOBALS) $(LINKS) $(NODES)
	echo '<pop></pop>' > contents.xml
	xsltproc --xinclude contents.xsl index.xml > contents-t.xml
	cp contents-t.xml contents.xml

config-reference.php: reference.xsl index.xml contents.xml $(GLOBALS) $(LINKS) $(NODES)
	xsltproc --xinclude reference.xsl index.xml | ./relink-reference.pl > config-reference.php
	# I will probably have to do some kind of pennance for this mess
	echo "<?php \$$WEATHERMAP_VERSION='v$(VERSION)';" > vars.php
	php config-reference.php > ../pages/config-reference.html
	php main.php > ../pages/main.html
	php errorcodes.php | ./relink-reference.pl ALL > ../pages/errorcodes.html
	php index.php > ../index.html
	php cli-reference.php > ../pages/cli-reference.html
	php cacti-plugin.php > ../pages/cacti-plugin.html
	php install-cacti.php > ../pages/install-cacti.html
	php install-cacti-editor.php > ../pages/install-cacti-editor.html
	php install-cli.php > ../pages/install-cli.html
	php install-cli-editor.php > ../pages/install-cli-editor.html
	php upgrading.php > ../pages/upgrading.html
	php editor.php | ./relink-reference.pl ALL > ../pages/editor.html
	php advanced.php | ./relink-reference.pl ALL > ../pages/advanced.html
	php faq.php | ./relink-reference.pl ALL > ../pages/faq.html
	php changes.php | ./relink-reference.pl ALL > ../pages/changes.html
	php targets.php | ./relink-reference.pl ALL > ../pages/targets.html

