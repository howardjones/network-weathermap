VERSION=0.95
RELBASE=../releases
RELNAME=php-weathermap-$(VERSION)
RELDIR=$(RELBASE)/weathermap

all: ready manual

random-bits/suite-1.png:  random-bits/suite-1.conf
	php weathermap --config  random-bits/suite-1.conf --output random-bits/suite-1.png

random-bits/suite-2.png:  random-bits/suite-2.conf
	php weathermap --config  random-bits/suite-2.conf --output random-bits/suite-2.png

ready: random-bits/suite-1.png random-bits/suite-2.png

manual:	docs/index.html
	$(MAKE) -C docs/src VERSION=$(VERSION)
	cd docs/example && ./bodge-example.sh
	
clean:
	rm random-bits/suite-1.png random-bits/suite-2.png docs/src/contents.xml

release: 
	mkdir -p $(RELDIR)
	tar cTf packing.list - | (cd $(RELDIR); tar xvf -)
	cd $(RELBASE); zip -r $(RELNAME).zip weathermap/*
	cd $(RELBASE); tar cvfz $(RELNAME).tgz weathermap
	cd $(RELBASE); mv weathermap $(RELNAME)

test:	
	echo "Creating test data"
	./mk-test-rrd.pl
	echo "Running test data set..."
	./tester.sh

sql:
	mysqldump -n --add-drop-table --no-data -uroot -p cacti weathermap_maps > weathermap.sql
	mysqldump -n --add-drop-table --no-data -uroot -p cacti weathermap_auth >> weathermap.sql

tag:
	svn copy http://www.network-weathermap.com/svn/repos/trunk http://www.network-weathermap.com/svn/repos/tags/version-$(VERSION) -m "Tagging $(VERSION) for release"

