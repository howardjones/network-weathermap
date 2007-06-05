#!/usr/bin/perl

use CGI::SHTML;

my $cgi = new CGI::SHTML;

$version = $ARGV[0];

while(<STDIN>)
{
    # in the XML, HTML comments are filtered out, so we need to use the
    # old style in there.
	s/%NAV IN HERE%/<!--#include file="common-top-nav.html" -->/;	
	s/<!-- NAV IN HERE -->/<!--#include file="common-top-nav.html" -->/;	
	s/v?%VERSION%/<!--#echo var="WM_VERSION"-->/g;

	s/ xmlns:*\w*="[^"]*"//g;
	
	$output .= $_;
}

$ENV{'WM_VERSION'} = "v".$version;

# run this through twice, so that included files can also have '#echo's in them.
$first =  $cgi->parse_shtml($output);
$second = $cgi->parse_shtml($first);

print $second;
