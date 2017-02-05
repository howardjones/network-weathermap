VERSION=0.98a
RELBASE=../releases
RELNAME=php-weathermap-$(VERSION)
RELDIR=$(RELBASE)/weathermap

all: ready manual release

random-bits/suite-1.png:  random-bits/suite-1.conf
	php weathermap --config  random-bits/suite-1.conf --output random-bits/suite-1.png

random-bits/suite-2.png:  random-bits/suite-2.conf
	php weathermap --config  random-bits/suite-2.conf --output random-bits/suite-2.png

ready: random-bits/suite-1.png random-bits/suite-2.png

manual:	docs/index.html
	php dump-keywords.php | pandoc --from=markdown --to=html5  -s -c keywords.css -o docs/keywords.html
	$(MAKE) -C docs/src VERSION=$(VERSION)
	cd docs/example && ./bodge-example.sh
	
clean:
	rm random-bits/suite-1.png random-bits/suite-2.png docs/src/contents.xml

release: sql
	echo Building release $(RELNAME)
	# mv $(RELDIR) $(RELDIR).$$
	mkdir -p $(RELDIR)
	tar cTf packing.list-core - | (cd $(RELDIR); tar xvf -)
	cd $(RELBASE); zip -r $(RELNAME).zip weathermap/*
	cd $(RELBASE); tar cvfz $(RELNAME).tgz weathermap
	cd $(RELBASE); mv $(RELDIR) $(RELNAME)
	echo $(RENAME) built in $(RELBASE)

test:	
	vendor/bin/phpunit -c build/phpunit.xml
	grep  Output test-suite/diffs/*.txt | grep -v '|0|' | awk -F: '{ print $1;}' | sed -e 's/.png.txt//' -e 's/test-suite\/diffs\///' > test-suite/failing-images.txt
	php test-suite/make-failing-summary.php > test-suite/summary-failing.html

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

