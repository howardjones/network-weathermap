GLOBALS = global_*.xml intro_*.xml
LINKS = link_*.xml
NODES = node_*.xml

VERSION=0.93

all: config-reference.html

contents.xml: contents.xsl index.xml $(GLOBALS) $(LINKS) $(NODES)
	echo '<pop></pop>' > contents.xml
	xsltproc --xinclude contents.xsl index.xml > contents-t.xml
	cp contents-t.xml contents.xml

config-reference.html: reference.xsl index.xml contents.xml $(GLOBALS) $(LINKS) $(NODES)
	xsltproc --xinclude reference.xsl index.xml | ./relink-reference.pl > config-reference.html
	./process.pl $(VERSION)  < config-reference.html > ../pages/config-reference.html
	./process.pl $(VERSION)   < main.html > ../pages/main.html
	./process.pl $(VERSION)   < index.html > ../index.html
	./process.pl $(VERSION)  < cli-reference.html > ../pages/cli-reference.html
	./process.pl $(VERSION)   < cacti-plugin.html > ../pages/cacti-plugin.html
	./process.pl $(VERSION)   < editor.html | ./relink-reference.pl ALL > ../pages/editor.html
	./process.pl $(VERSION)   < advanced.html | ./relink-reference.pl ALL > ../pages/advanced.html
	./process.pl $(VERSION)   < faq.html | ./relink-reference.pl ALL > ../pages/faq.html
	./process.pl $(VERSION)   < changes.html | ./relink-reference.pl ALL > ../pages/changes.html
	./process.pl $(VERSION)   < targets.html | ./relink-reference.pl ALL > ../pages/targets.html
	./process.pl $(VERSION)   < install-cacti.html > ../pages/install-cacti.html
	./process.pl $(VERSION)   < install-cacti-editor.html > ../pages/install-cacti-editor.html
	./process.pl $(VERSION)   < install-cli.html > ../pages/install-cli.html
	./process.pl $(VERSION)   < install-cli-editor.html > ../pages/install-cli-editor.html
	./process.pl $(VERSION)   < upgrading.html > ../pages/upgrading.html
	
