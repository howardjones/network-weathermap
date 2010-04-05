    function ReadConfigNG($input, $is_include = false, $initial_context = 'GLOBAL')
    {
        $valid_commands = array (
            'GLOBAL.set',
            'LINK.set',
            'NODE.set',
            'GLOBAL.#',
            'LINK.#',
            'NODE.#',
            'GLOBAL.include',
            'NODE.include',
            'LINK.include',
            'GLOBAL.width',
            'GLOBAL.height',
            'GLOBAL.background',
            'GLOBAL.scale',
            'GLOBAL.title',
            'GLOBAL.titlepos',
            'GLOBAL.fontdefine',
            'GLOBAL.keystyle',
            'GLOBAL.titlecolor',
            'GLOBAL.timecolor',
            'GLOBAL.titlefont',
            'GLOBAL.timefont',
            'GLOBAL.htmloutputfile',
            'GLOBAL.htmlstyle',
            'GLOBAL.imageoutputfile',
            'GLOBAL.keyfont',
            'GLOBAL.keytextcolor',
            'GLOBAL.keyoutlinecolor',
            'GLOBAL.keybgcolor',
            'GLOBAL.bgcolor',
            'SCALE.keypos',
            'SCALE.keystyle',
            'SCALE.scale',
            'LINK.width',
            'LINK.link',
            'LINK.nodes',
            'LINK.target',
            'LINK.usescale',
            'LINK.infourl',
            'LINK.linkstyle',
            'LINK.overlibcaption',
            'LINK.inoverlibcaption',
            'LINK.outoverlibcaption',
            'LINK.inoverlibgraph',
            'LINK.outoverlibgraph',
            'LINK.overlibgraph',
            'LINK.overlibwidth',
            'LINK.overlibheight',
            'LINK.bwlabel',
            'LINK.via',
            'LINK.zorder',
            'LINK.outlinecolor',
            'LINK.notes',
            'LINK.innotes',
            'LINK.outnotes',
            'LINK.ininfourl',
            'LINK.outinfourl',
            'LINK.bwstyle',
            'LINK.template',
            'LINK.splitpos',
            'LINK.bwlabelpos',
            'LINK.incomment',
            'LINK.outcomment',
            'LINK.viastyle',
            'LINK.bandwidth',
            'LINK.inbwformat',
            'LINK.outbwformat',
            'LINK.commentstyle',
            'LINK.commentfont',
            'LINK.commentfontcolor',
            'LINK.bwfont',
            'NODE.icon',
            'NODE.target',
            'NODE.position',
            'NODE.infourl',
            'NODE.overlibgraph',
            'NODE.zorder',
            'NODE.label',
            'NODE.template',
            'NODE.labelbgcolor',
            'NODE.maxvalue',
            'NODE.labeloutlinecolor',
            'NODE.aiconoutlinecolor',
            'NODE.aiconfillcolor',
            'NODE.usescale',
            'NODE.labelfontcolor',
            'NODE.labelfont',
            'NODE.labelangle',
            'NODE.labelfontshadowcolor',
            'NODE.node',
            'NODE.overlibwidth',
            'NODE.overlibheight',
            'NODE.labeloffset'
        );

        if ((strchr($input, "\n") != false) || (strchr($input, "\r") != false)) {
            debug("ReadConfig Detected that this is a config fragment.\n");
            // strip out any Windows line-endings that have gotten in here
            $input = str_replace("\r", "", $input);
            $lines = explode("/n", $input);
            $filename = '{text insert}';
        } else {
            debug("ReadConfig Detected that this is a config filename.\n");
            $filename = $input;

            $fd = fopen($filename, "r");

            if ($fd) {
                while (!feof($fd)) {
                    $buffer = fgets($fd, 4096);
                    // strip out any Windows line-endings that have gotten in here
                    $buffer = str_replace("\r", '', $buffer);
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
            $args = ParseString($buffer);

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
                warn("Unrecognised config on line $linecount: $buffer\n");
            }
        }

        if (!$is_include) {
            print_r($this->config);

            foreach ($this->config as $context => $values) {
                print "> $context\n";
            }
        }
    }

    function ReadConfigNNG($input, $is_include = false, $initial_context = 'GLOBAL')
    {
        global $valid_commands;

        if ((strchr($input, "\n") != false) || (strchr($input, "\r") != false)) {
            debug("ReadConfig Detected that this is a config fragment.\n");
            // strip out any Windows line-endings that have gotten in here
            $input = str_replace("\r", "", $input);
            $lines = explode("/n", $input);
            $filename = "{text insert}";
        } else {
            debug("ReadConfig Detected that this is a config filename.\n");
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
            $args = ParseString($buffer);

            if (sizeof($args) > 0) {
                $linematched++;
                $cmd = strtolower(array_shift($args));

                if ($cmd == 'include') {
                    $context = $this->ReadConfigNNG($args[0], true, $context);
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
                    if (!array_key_exists($lookup, $valid_commands)) {
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
                warn("Unrecognised config on line $linecount: $buffer\n");
            }
        }

        if (!$is_include) {

            # print_r($this->config);

            foreach ($this->config as $context => $values) {
            #	print "> $context\n";
            }
        }

        return ($context);
    }

    function WriteConfigNG($filename)
    {
        global $WEATHERMAP_VERSION;

        $fd = fopen($filename);

        fclose($fd);
    }


