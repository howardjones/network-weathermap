VERSION=0.94
RELBASE=../releases
RELNAME=php-weathermap-$(VERSION)
RELDIR=$(RELBASE)/weathermap

release: 
	mkdir -p $(RELDIR)
	tar cTf packing.list - | (cd $(RELDIR); tar xvf -)
	cd $(RELBASE); zip -r $(RELNAME).zip weathermap/*
	cd $(RELBASE); tar cvfz $(RELNAME).tgz weathermap
	cd $(RELBASE); mv weathermap $(RELNAME)


testdata: testdata/test1.rrd
	echo "Creating test data"
	cd testdata; ./mk-rrd.pl
	
test:	testdata
	echo "Running test data set..."

sql:
	mysqldump -n --add-drop-table --no-data -uroot -p cacti weathermap_maps > weathermap.sql
	mysqldump -n --add-drop-table --no-data -uroot -p cacti weathermap_auth >> weathermap.sql


#
# svn copy http://www.network-weathermap.com/svn/repos/trunk http://www.network-weathermap.com/svn/repos/tags/version-0.94 -m "Tagging 0.94 for release"
#

