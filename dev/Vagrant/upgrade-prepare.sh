#!/usr/bin/env bash

# This is all the parts of vagrant-cacti-develop.sh that relate to Cacti 1.x
# (so you can test upgrading from 0.8.8 to 1.x on a 0.8.8 vagrant box)

WEBROOT="/var/www/html"

# Cacti 1.x insists on these
echo "Adding mysql timezones"
mysql_tzinfo_to_sql /usr/share/zoneinfo | sudo mysql -u root mysql

# Cacti 1.x also makes a few optional modules required.
sudo apt-get install -y php5.6-ldap php7.0-ldap php5.6-gmp php7.0-gmp

sudo mysql -uroot <<EOF
grant select on mysql.time_zone_name to cactiuser@localhost identified by 'cactiuser';
flush privileges;
ALTER DATABASE cacti CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
EOF

# it also suggests a lot of database tweaks. mostly they are for performance, but still
sudo bash -c "cat > /etc/mysql/mysql.conf.d/cacti.cnf" <<'EOF'
[mysqld]
max_heap_table_size=64M
join_buffer_size=64M
tmp_table_size=64M
innodb_buffer_pool_size=250M
innodb_doublewrite=off
innodb_flush_log_at_timeout=3
innodb_read_io_threads=32
innodb_write_io_threads=16
EOF

sudo service mysql restart
sudo service apache2 restart

sudo chown -R www-data.cacti $WEBROOT/cacti/resource/ $WEBROOT/cacti/scripts $WEBROOT/cacti/log $WEBROOT/cacti/cache
# this isn't in the recommendations, but otherwise you get no logs!
sudo chmod g+wrx $WEBROOT/cacti/log