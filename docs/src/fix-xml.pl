#!/usr/bin/perl

use XML::Twig;
use Data::Dumper;

$file = $ARGV[0] || "link_overlibcaption.xml";
$outfile = $file;

print "Fixing $file\n";

  my $t= XML::Twig->new( discard_spaces_in => ['keyword','scope','anchor']);
  $t->parsefile( $file );

  my $root= $t->root;

foreach $k ('keyword','scope','anchor') {
	$target = $root->first_child( $k );
	$v = $target->text();
	$v =~ s/^\s+//;
	$v =~ s/\s+$//;
	$target->set_text($v);
 }

$t->print_to_file ($outfile);
