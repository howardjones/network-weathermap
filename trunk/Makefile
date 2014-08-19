VERSION=0.98pre
RELBASE=releases
RELNAME=php-weathermap-$(VERSION)
RELDIR=$(RELBASE)/weathermap

all: ready manual release

random-bits/suite-1.png:  random-bits/suite-1.conf
	php weathermap --config  random-bits/suite-1.conf --output random-bits/suite-1.png

random-bits/suite-2.png:  random-bits/suite-2.conf
	php weathermap --config  random-bits/suite-2.conf --output random-bits/suite-2.png

ready: random-bits/suite-1.png random-bits/suite-2.png

manual:	docs/index.html
	touch docs/src/index.xml
	$(MAKE) -C docs/src VERSION=$(VERSION)
	cd docs/example && ./bodge-example.sh
	
clean:
	rm docs/src/contents.xml test-suite/results1-php5/* test-suite/results2-php5/* test-suite/diffs/* ./test-suite/code-coverage/*

release: 
	echo Building release $(RELNAME)
	grep -q ENABLED=true editor.php
	# mv $(RELDIR) $(RELDIR).$$
	mkdir -p $(RELDIR)
	rm -rf $(RELDIR)
	mkdir -p $(RELDIR)
	tar cTf packing.list - | (cd $(RELDIR); tar xvf -)
	cd $(RELBASE); zip -r $(RELNAME).zip weathermap/*
	cd $(RELBASE); tar cvfz $(RELNAME).tgz weathermap
	rm -rf $(RELDIR)
	mkdir -p $(RELDIR)
	tar cTf packing.list-tests - | (cd $(RELDIR); tar xvf -)
	cd $(RELBASE); zip -r $(RELNAME)-tests.zip weathermap/*
	cd $(RELBASE); tar cvfz $(RELNAME)-tests.tgz weathermap
	rm -rf $(RELDIR)
	echo $(RENAME) built in $(RELBASE)
	# copy the results into the Vagrant shared directory, ready for test installations
	cp -f $(RELBASE)/$(RELNAME)-tests.zip  $(RELBASE)/$(RELNAME).zip docs/dev/vagrant-testers

test:	
	phpunit Tests/
	grep  Output test-suite/diffs/*.txt | grep -v '|0|' | awk -F: '{ print $1;}' | sed -e 's/.png.txt//' -e 's/test-suite\/diffs\///' > test-suite/failing-images.txt
	test-suite/make-failing-summary.pl test-suite/failing-images.txt test-suite/summary.html > test-suite/summary-failing.html

testcoverage:	
	phpunit --coverage-html test-suite/code-coverage/ Tests/

sql:
	mysqldump -n --add-drop-table --no-data -uroot -p cacti weathermap_maps > weathermap.sql
	mysqldump -n --add-drop-table --no-data -uroot -p cacti weathermap_auth >> weathermap.sql
	mysqldump -n --add-drop-table --no-data -uroot -p cacti weathermap_groups >> weathermap.sql
	mysqldump -n --add-drop-table --no-data -uroot -p cacti weathermap_settings >> weathermap.sql
	mysqldump -n --add-drop-table --no-data -uroot -p cacti weathermap_data >> weathermap.sql

tag:
	svn copy http://www.network-weathermap.com/svn/repos/trunk http://www.network-weathermap.com/svn/repos/tags/version-$(VERSION) -m "Tagging $(VERSION) for release"

