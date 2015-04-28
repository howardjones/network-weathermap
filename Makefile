VERSION=0.98pre
RELBASE=dist
RELNAME=php-weathermap-$(VERSION)
RELDIR=$(RELBASE)/weathermap
CACTIDIR=/var/www/html/cactiauto/plugins
CACTIDB=cactiauto
CACTIUSER=cacti
WwWUSER=www-data

all: ready manual release

manual:	docs/index.html
	touch docs/src/index.xml
	$(MAKE) -C docs/src VERSION=$(VERSION)
	cd docs/example && ./bodge-example.sh
	
clean:
	rm docs/src/contents.xml test-suite/results1-php5/* test-suite/results2-php5/* test-suite/diffs/* ./test-suite/code-coverage/*

release: 
	echo Building release $(RELNAME)
	rm -rf $(RELDIR)
	mkdir -p $(RELDIR)
	mkdir -p $(RELDIR)/random-bits $(RELDIR)/lib/datasources $(RELDIR)/lib/port $(RELDIR)/lib/pre $(RELDIR)/vendor $(RELDIR)/images $(RELDIR)/editor-resources $(RELDIR)/output $(RELDIR)/configs $(RELDIR)/cacti-resources $(RELDIR)/plugin-images $(RELDIR)/docs
	grep -q ENABLED=true editor.php
	tar cTf packing.list - | (cd $(RELDIR); tar xvf -)
	cd $(RELBASE); zip -r $(RELNAME).zip weathermap/*
	cd $(RELBASE); tar cvfz $(RELNAME).tgz weathermap
	# Add in test code, and repackage
	tar cTf packing.list-tests - | (cd $(RELDIR); tar xvf -)
	mkdir -p $(RELDIR)/test-suite/diffs
	cd $(RELBASE); zip -r $(RELNAME)-tests.zip weathermap/*
	cd $(RELBASE); tar cvfz $(RELNAME)-tests.tgz weathermap
	# rm -rf $(RELDIR)
	echo $(RENAME) built in $(RELBASE)
	# copy the results into the Vagrant shared directory, ready for test installations
	cp -f $(RELBASE)/$(RELNAME)-tests.zip  $(RELBASE)/$(RELNAME).zip docs/dev/vagrant-testers
	ls -l $(RELBASE)

test:
	./test.sh

# build a release, then run tests in the release packaging directing (tests for packing.list issues)
releasetest: release
	cd $(RELDIR) && /usr/local/bin/composer install
	cd $(RELDIR) && $(RELDIR)/test.sh

deploycacti: release
	touch $(CACTIDIR)/weathermap
	rm -rf $(CACTIDIR)/weathermap
	unzip $(RELBASE)/$(RELNAME).zip -d $(CACTIDIR)
	chown -R $(CACTIUSER) $(CACTIDIR)/weathermap/output
	chown -R $(WWWUSER) $(CACTIDIR)/weathermap/configs

# You will want to (1) use a TEST cacti install for this and
# (2) create a .my.cnf with your credentials, so you don't go crazy
# (3) actually create test-suite/cacti-running.sql - this is a dump of the Cacti DB from
# just after I changed the password, so all the paths are correct but no plugins are installed etc.
cleancacti:
	mysqladmin drop -f $(CACTIDB)
	mysqladmin create $(CACTIDB)
	mysql $(CACTIDB) < test-suite/cacti-running.sql

sql:
	mysqldump -n --add-drop-table --no-data $(CACTIDB) weathermap_maps > weathermap.sql
	mysqldump -n --add-drop-table --no-data $(CACTIDB) weathermap_auth >> weathermap.sql
	mysqldump -n --add-drop-table --no-data $(CACTIDB) weathermap_groups >> weathermap.sql
	mysqldump -n --add-drop-table --no-data $(CACTIDB) weathermap_settings >> weathermap.sql
	mysqldump -n --add-drop-table --no-data $(CACTIDB) weathermap_data >> weathermap.sql

