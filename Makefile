VERSION=1.0.0dev
RELBASE=./releases
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
	rm -rf $(RELBASE)

release: 
	#sql
	# remove the dev-only dependencies from vendor
	composer --no-dev update
	bower install
	# build the react apps (yarn copies the compiled files over)
	cd websrc/cacti-user; yarn run build
	cd websrc/cacti-mgmt; yarn run build
	echo Building release $(RELNAME)
	# mv $(RELDIR) $(RELDIR).$$
	mkdir -p $(RELDIR)
	tar cTf packing.list-core - | (cd $(RELDIR); tar xvf -)
	cd $(RELBASE); zip -r $(RELNAME).zip weathermap/*
	cd $(RELBASE); tar cvfz $(RELNAME).tgz weathermap
	cd $(RELBASE); mv weathermap $(RELNAME)
	echo $(RELNAME) built in $(RELBASE)

test:
	echo "Linting for minimum and maximum PHP versions"
	vendor/bin/parallel-lint -p php5.6 --exclude app --exclude vendor .
	vendor/bin/parallel-lint -p php7.1 --exclude app --exclude vendor .
	vendor/bin/parallel-lint -p php7.2 --exclude app --exclude vendor .
	php -d xdebug.profiler_enable=off vendor/bin/phpunit -c build/phpunit.xml
	grep  Output test-suite/diffs/*.txt | grep -v '|0|' | awk -F: '{ print $1;}' | sed -e 's/.png.txt//' -e 's/test-suite\/diffs\///' > test-suite/failing-images.txt
	php test-suite/make-failing-summary.php > test-suite/summary-failing.html

testcoverage:	
	php -d xdebug.profiler_enable=on vendor/bin/phpunit -c build/phpunit.xml --coverage-html test-suite/code-coverage/

sql:
	mysqldump -n --add-drop-table --no-data -uroot -p cacti weathermap_maps > weathermap.sql
	mysqldump -n --add-drop-table --no-data -uroot -p cacti weathermap_auth >> weathermap.sql
	mysqldump -n --add-drop-table --no-data -uroot -p cacti weathermap_groups >> weathermap.sql
	mysqldump -n --add-drop-table --no-data -uroot -p cacti weathermap_settings >> weathermap.sql
	mysqldump -n --add-drop-table --no-data -uroot -p cacti weathermap_data >> weathermap.sql


