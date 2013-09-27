group { "cacti":
     ensure => "present",
}
 
user { "cacti":
    ensure => "present",
	gid => 'cacti',
    home => "/var/www/cacti"
}

Exec { path => ['/usr/bin', '/bin', '/usr/sbin', '/sbin', '/usr/local/bin', '/usr/local/sbin', '/opt/local/bin'] }
exec { 'apt-get update':
  command => '/usr/bin/apt-get update --fix-missing'
 # require => Exec['add php54 apt-repo']
}

package { ['rrdtool','mysql-server','snmp','php5-cli','apache2','libapache2-mod-php5','php5-mysql','php5-snmp','php5-gd','unzip','subversion','zip','xsltproc','phpunit','make','curl','imagemagick']:
	ensure => "latest",
	require => Exec['apt-get update']
}

package { 'fpm':
	provider => 'gem',
	ensure => 'latest',
	require => Package['make'] 
}