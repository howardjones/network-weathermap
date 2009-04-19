#!/usr/bin/perl

use DBI;

# This should be the URL for the base of your cacti install (no trailing slash)
$cacti_base = "http://www.mynet.net/cacti";

# How we should access your Cacti database....
$db_name     = "cacti";
$db_username = "cactiuser";
$db_password = "somepassword";
$db_host     = "localhost";

#
# You shouldn't need to change anything below here
#

$cacti_graph = "$cacti_base/graph_image.php?local_graph_id=%d&rra_id=0&graph_nolegend=true&graph_height=100&graph_width=300";
$cacti_graphpage = "$cacti_base/graph.php?rra_id=all&local_graph_id=%d";

$DSN = "DBI:mysql:database=$db_name:host=$db_host";

$dbh = DBI->connect( $DSN, $db_username, $db_password );

$inputfile  = $ARGV[0];
$outputfile = $inputfile . ".new";

open( INPUT, $inputfile ) || die($!);
open( OUTPUT, ">$outputfile" );

while (<INPUT>) {
    if (m/^\s*LINK\s+(\S+)/i) {
        if ( $overlibcount == 0 && $target ne "" ) {
            find_graph_urls($target);
        }

        $overlibcount = 0;
        $target       = "";
    }
    if (m/^\s*TARGET\s+(\S+\.rrd)/i) {
        $target = $1;
    }
    if (m/^\s*OVERLIBGRAPH\s+(\S+)/i) {
        $overlibcount++;
    }

    print OUTPUT $_;
}

# for the last LINK
if ( $overlibcount == 0 && $target ne "" ) {
    find_graph_urls($target);
}

close(OUTPUT);
close(INPUT);

print "\nNew config file is saved in $outputfile\n";

sub find_graph_urls {
    my ($target) = shift;

	# $dbh is global
    my ( @bits, $SQL, $sth, $data );
    my ( $data_template_id, $local_data_id, $count,     $output );
    my ( $local_graph_id,   $title,         $graph_url, $graphpage_url );

    # we've reached the next link entry, and there's work to be done
    @bits = split( /\//, $target );
    $target = $bits[-1];
    print "Find a graph for $target\n";

    $SQL = "select local_data_id from data_template_data where data_source_path like '%".$target."' LIMIT 1";
    $sth = $dbh->prepare($SQL);
    $sth->execute();
    $data          = $sth->fetchrow_hashref();
    $local_data_id = $$data{local_data_id};
    $sth->finish();

    $SQL =
"SELECT id FROM data_template_rrd WHERE local_data_id=$local_data_id LIMIT 1";
    $sth = $dbh->prepare($SQL);
    $sth->execute();
    $data                 = $sth->fetchrow_hashref();
    $data_template_rrd_id = $$data{id};
    $sth->finish();

    $SQL =
"SELECT DISTINCT graph_templates_item.local_graph_id,title_cache FROM graph_templates_item,graph_templates_graph WHERE task_item_id=$data_template_rrd_id and graph_templates_graph.local_graph_id = graph_templates_item.local_graph_id";
    $sth = $dbh->prepare($SQL);
    $sth->execute();
    $count  = 0;
    $output = "";
    while ( $data = $sth->fetchrow_hashref() ) {
        $local_graph_id = $$data{local_graph_id};
        $title          = $$data{title_cache};
        $graph_url      = sprintf( $cacti_graph, $local_graph_id );
        $graphpage_url  = sprintf( $cacti_graphpage, $local_graph_id );
        $output .= "\t# POSSIBLE OVERLIBGRAPH ($title) \n";
        $output .= "\t# OVERLIBGRAPH $graph_url\n";
        $output .= "\t# INFOURL $graphpage_url\n";
        $count++;
    }
    $sth->finish();
    if ( $count == 1 ) {
        print "  Single option. Adding it.\n";
        print OUTPUT
          "\t#Automatically made. Graph is ID $local_graph_id: $title\n";
        print OUTPUT "\tOVERLIBGRAPH $graph_url\n";
        print OUTPUT "\tINFOURL $graphpage_url\n";
    }
    else {
        print "  Multiple options. Adding them as comments.\n";
        print OUTPUT $output;
    }

    print OUTPUT "\n\n";

}
