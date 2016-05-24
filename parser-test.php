<?php
$valid_commands = array ();

$f = fopen("config-schema.tsv", "r");

while (!feof($f)) {
    $line = fgets($f);
    $parts = explode("\t", $line);
    $context = array_shift($parts);
    $command = array_shift($parts);

    $valid_commands["$context.$command"] = $parts;
}
fclose($f);

$dom[':: DEFAULTNODE ::'] = array (
    'name' => '',
    'label' => '',
    'position' => array (
        0,
        0
    ),
    'template' => null
);

$dom[':: DEFAULTLINK ::'] = array (
    'template' => null,
    'name' => '',
    'width' => 7
);

$defnode = uniqid('N');
$deflink = uniqid('L');

$dom[$defnode] = array (
    'template' => ':: DEFAULTNODE ::',
    'target' => 'ploip'
);

$dom[$deflink] = array ('template' => ':: DEFAULTLINK ::');

ReadConfig('configs/097-test.conf');

function ReadConfig($input, $is_include = false, $initial_context = 'GLOBAL')
{
    if ((strchr($input, "\n") != false) || (strchr($input, "\r") != false)) {
        wm_debug("ReadConfig Detected that this is a config fragment.\n");
        // strip out any Windows line-endings that have gotten in here
        $input = str_replace("\r", "", $input);
        $lines = explode("/n", $input);
        $filename = "{text insert}";
    } else {
        wm_debug("ReadConfig Detected that this is a config filename.\n");
        $filename = $input;

        $fd = fopen($filename, "r");

        if ($fd) {
            while (!feof($fd)) {
                $buffer = fgets($fd, 4096);
                // strip out any Windows line-endings that have gotten in here
                $buffer = str_replace("\r", "", $buffer);
                $lines[] = $buffer;
            }
            fclose($fd);
        }
    }

    $linecount = 0;
    $context = $initial_context;

    foreach ($lines as $buffer) {
        $linematched = 0;
        $linecount++;
        $nextcontext = "";
        $key = "";

        $buffer = trim($buffer);
        // alternative for use later where quoted strings are more useful
        $args = wm_parse_string($buffer);

        if (sizeof($args) > 0) {
            $linematched++;
            $cmd = strtolower(array_shift($args));

            if ($cmd == 'include') {
                $this->ReadConfigNG($args[0], true, $context);
            } elseif ($cmd == 'node') {
                $context = "NODE." . $args[0];
            } elseif ($cmd == 'link') {
                $context = "LINK." . $args[0];
                $vcount = 0; # reset the via-number counter, it's a new link
            } elseif ($cmd == 'scale' || $cmd == 'keystyle' || $cmd == 'keypos') {
                if (preg_match("/^[0-9\-]+/i", $args[0])) {
                    $scalename = "DEFAULT";
                } else {
                    $scalename = array_shift($args);
                }

                if ($cmd == "scale")
                    $key = $args[0] . "_" . $args[1];
                $nextcontext = $context;
                $context = "SCALE." . $scalename;
            }

            array_unshift($args, $cmd);

            if ($context == 'GLOBAL') {
                $ctype = 'GLOBAL';
            } else {
                list($ctype, $junk) = explode("\\.", $context, 2);
            }

            $lookup = $ctype . "." . $cmd;

            // Some things (scales, mainly) might define special keys
            // the key should be unique for that object
            // most (all?) things for a link or node are one-offs.
            if ($key == "")
                $key = $cmd;

            if ($cmd == 'set' || $cmd == 'fontdefine')
                $key .= "_" . $args[1];

            if ($cmd == 'via') {
                $key .= "_" . $vcount;
                $vcount++;
            }

            # everything else
            if (substr($cmd, 0, 1) != '#') {
                if (!in_array($lookup, $valid_commands)) {
                    print "INVALID COMMAND: $lookup\n";
                }

                if (isset($config[$context][$key])) {
                    print "REDEFINED $key in $context\n";
                } else {
                    array_unshift($args, $linecount);
                    array_unshift($args, $filename);
                    $this->config[$context][$key] = $args;
                }
            }
            print "$context\\$key  $filename:$linecount " . join("|", $args) . "\n";

            if ($nextcontext != "")
                $context = $nextcontext;
        }

        if ($linematched == 0 && trim($buffer) != '') {
            wm_warn("Unrecognised config on line $linecount: $buffer\n");
        }
    }

    if (!$is_include) {
        print_r($this->config);

        foreach ($this->config as $context => $values) {
            print "> $context\n";
        }
    }
}

function testing()
{
    global $dom;
    global $defnode;

    $node1 = uniqid("N");
    $node2 = uniqid("N");
    $dom[$node1] = array (
        'template' => $defnode,
        'label' => 'Node 1',
        'target' => 'poop'
    );

    $dom[$node2] = array (
        'template' => $node1,
        'label' => 'Node 2'
    );

    # print_r($dom);

    $v = get_value($node2, "grub");
    print "\n\n";
    print "Value is $v\n";
    print "\n\n";
}

function get_value($itemid, $name)
{
    global $dom;

    if (isset($dom[$itemid])) {
        if (isset($dom[$itemid][$name])) {
            print "Found value\n";
            return ($dom[$itemid][$name]);
        } else {
            print "Punting to parent\n";
            return (get_value($dom[$itemid]['template'], $name));
        }
    } else {
        print "Invalid itemid\n";
        return "";
    }
}
