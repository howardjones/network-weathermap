<?php

# Read in the contents of the config keyword reference, and use it 
# to add links through the rest of the document to the relevant reference.
#
# There are a few bodges: one to aggregate all the *color entries
# and we might get it wrong if the same keyword appears in multiple contexts (e.g. WIDTH)
#

$f = fopen("contents.xml", "r") or die('Cannot open contents file');
while (($buffer = fgets($f, 4096)) !== false) {

    if (preg_match('/id="context_([^"]+)/', $buffer, $matches)) {
        $scope = $matches[1];
    }

    if (preg_match("/href=\"([^\"]+)\">([^<]+)</", $buffer, $matches)) {
        $keyword = $matches[2];
        $url = $matches[1];
        $map [ $scope . '|' . $keyword ] = $url;
        $map [ $keyword ] = $url;
        $words[$keyword] = 1;
    }
}
if (!feof($f)) {
    echo "Error: unexpected fgets() fail\n";
}
fclose($f);

// seed the additional stuff that won't otherwise get autolinked.

$seeds = array(
    "NODE_COLORS" => array("AICONFILLCOLOR","AICONOUTLINECOLOR","LABELFONTCOLOR","LABELBGCOLOR","LABELOUTLINECOLOR","LABELFONTSHADOWCOLOR"),
    "GLOBAL_COLORS"=>array("BGCOLOR","TIMECOLOR","TITLECOLOR","KEYTEXTCOLOR","KEYOUTLINECOLOR","KEYBGCOLOR"),
    "LINK_COLORS"=>array("OUTLINECOLOR","BWOUTLINECOLOR","BWFONTCOLOR","BWBOXCOLOR","COMMENTFONTCOLOR"),
    "GLOBAL_FONT"=>array("TITLEFONT","KEYFONT","TIMEFONT")
);

foreach (array_keys($seeds) as $scopecode) {
    foreach ($seeds[$scopecode] as $c) {
        list($scope, $junk) = explode("_", $scopecode);
        $url = "#".$scopecode;
        $map[ "$scope|$c" ] = $url;
        $map[ $c ] = $url;
        $words[ $c ] = 1;
    }
}
print_r($map);

$wholefile = 1;
$scope = "";
$handle = fopen('php://stdin', 'r');

while (!feof($handle)) {
    $buffer = fgets($handle);
    
    $linewords = preg_split("/\s+/", $buffer);
    foreach ($linewords as $word) {
        $bareword = $word;
        $prefix = "";
        $link = "";

        $bareword = preg_replace("/[^A-Z]/", '', $bareword);

        if ($bareword != "" && array_key_exists($bareword, $words)) {

            $link = ( array_key_exists("$scope|$bareword", $map) ? $map [ "$scope|$bareword" ] : null);

            $link = (isset($link) ? $link : $map [ $bareword ]);

            if ($wholefile) {
                $link = (isset($link) ? $link : $map [ "GLOBAL|".$bareword ]);
                $link = (isset($link) ? $link : $map [ "LINK|".$bareword ]);
                $link = (isset($link) ? $link : $map [ "NODE|".$bareword ]);
            }

            if (($link != '')) {
                if ($wholefile) {
                    $link = "config-reference.html" . $link;
                }

                # $word =~ s/^$prefix//;
                $word = sprintf("%s<a href=\"%s\">%s</a>", $prefix, $link, $word);
            }
            # print "{$link}";
        }

        print $word . " ";
    }
    print "\n";
}
fclose($handle);
