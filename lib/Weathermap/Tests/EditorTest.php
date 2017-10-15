<?php
//
//require_once dirname(__FILE__) . '/../lib/all.php';
//require_once dirname(__FILE__) . '/../lib/Editor.php';
//include_once dirname(__FILE__) . "/WMTestSupport.php";

namespace Weathermap\Tests;

require dirname(__FILE__) . '/../../all.php';

use Weathermap\Editor\Editor;

class EditorTest extends \PHPUnit_Framework_TestCase
{

    protected static $testdir;
    protected static $result1dir;
    protected static $referencedir;
    protected static $phptag;

    protected static $previouswd;

    protected $projectRoot;

    public function testNodeRename()
    {
        $editor = new Editor();
        $editor->newConfig();

        $editor->addNode(100, 100, "named_node");
        $editor->addNode(100, 200, "other_named_node");
        $editor->addNode(200, 200, "third_named_node", "named_node");

        $n1 = $editor->map->getNode("third_named_node");
        $n1->positionRelativeTo = "named_node";

        $editor->addLink("other_named_node", "named_node", "linky");
        $editor->addLink("named_node", "third_named_node", "named_link");

        $l2 = $editor->map->getLink("linky");
        $l2->viaList = array(array(40, 40, "named_node"));


        $newName = $editor->renameNode("named_node", "steve");


        $this->assertEquals("steve", $newName, "Rename returns new name");

        $l1 = $editor->map->getLink("named_link");
        $l2 = $editor->map->getLink("linky");

        $this->assertEquals("steve", $l1->endpoints[0]->node->name, "Link endpoint should be renamed");
        $this->assertEquals("steve", $l2->endpoints[1]->node->name, "Link endpoint should be renamed");

        $n1 = $editor->map->getNode("third_named_node");
        $this->assertEquals("steve", $n1->positionRelativeTo);

        $this->assertEquals("steve", $l2->viaList[0][2], "Relative-positioned VIA should be renamed");

        $newName = $editor->renameNode("steve", "other_named_node");
        $this->assertEquals("steve", $newName, "Rename fails due to existing node");

        $newName = $editor->renameNode("traflorkians", "node3");
        $this->assertEquals("traflorkians", $newName, "Rename fails due to non-existing node");
    }

    public function testNodeAdd()
    {
        $editor = new Editor();
        $editor->newConfig();

        $editor->addNode(100, 100, "named_node");
        $editor->addNode(100, 200, "other_named_node");
        $editor->addNode(200, 200, "third_named_node", "named_node");

        $c = $editor->getConfig();

        $fh = fopen(self::$result1dir . DIRECTORY_SEPARATOR . "editortest-addnode.conf", "w");
        fputs($fh, $c);
        fclose($fh);
    }

    public function testLinkAdd()
    {
        $editor = new Editor();
        $editor->newConfig();

        $editor->addNode(100, 100, "node1");
        $editor->addNode(100, 200, "node2");
        $editor->addNode(200, 200, "node3");

        $editor->addLink("node1", "node2");
        $editor->addLink("node1", "node3", "named_link");
        $editor->addLink("node2", "node3", "other_named_link", "named_link");

        $c = $editor->getConfig();

        $fh = fopen(self::$result1dir . DIRECTORY_SEPARATOR . "editortest-addlink.conf", "w");
        fputs($fh, $c);
        fclose($fh);
    }

    public function testNodeClone()
    {
        $editor = new Editor();
        $editor->newConfig();

        $editor->addNode(100, 100, "named_node");
        $editor->addNode(100, 200, "other_named_node");
        $editor->addNode(200, 200, "third_named_node", "named_node");

        $editor->cloneNode("named_node");
        $editor->cloneNode("third_named_node", "named_clone_of_third_named_node");

        $c = $editor->getConfig();

        $fh = fopen(self::$result1dir . DIRECTORY_SEPARATOR . "editortest-clone.conf", "w");
        fputs($fh, $c);
        fclose($fh);
    }

    public function testDependencies()
    {
        $editor = new Editor();
        $editor->newConfig();

        $editor->addNode(100, 100, "node1");
        $editor->addNode(100, 200, "node2");
        $editor->addNode(200, 200, "node3");

        $n1 = $editor->map->getNode("node1");
        $n3 = $editor->map->getNode("node3");
        $n2 = $editor->map->getNode("node2");

        $this->assertEquals(array(), $n1->getDependencies());

        $editor->addLink("node1", "node2");

        $nDeps = $n1->getDependencies();
        $nDepsString = join(" ", array_map(array("Weathermap\\Tests\\EditorTest", "makeString"), $nDeps));
        $this->assertEquals("[LINK node1-node2]", $nDepsString, "Dependency created for new link");

        $editor->addLink("node1", "node3");

        $nDeps = $n1->getDependencies();
        $nDepsString = join(" ", array_map(array("Weathermap\\Tests\\EditorTest", "makeString"), $nDeps));
        $this->assertEquals("[LINK node1-node2] [LINK node1-node3]", $nDepsString, "Two dependencies with two links");

        $link = $editor->map->getLink("node1-node2");
        $link->setEndNodes($n2, $n3);

        $nDeps = $n1->getDependencies();
        $nDepsString = join(" ", array_map(array("Weathermap\\Tests\\EditorTest", "makeString"), $nDeps));
        $this->assertEquals("[LINK node1-node3]", $nDepsString, "Dependency removed when link moves");

        $nDeps = $n2->getDependencies();
        $nDepsString = join(" ", array_map(array("Weathermap\\Tests\\EditorTest", "makeString"), $nDeps));
        $this->assertEquals("[LINK node1-node2]", $nDepsString, "Dependency added when link moves");
    }

    public function testTidy()
    {
        $editor = new Editor();
        $editor->newConfig();

        $editor->addNode(100, 100, "node1");
        $editor->addNode(103, 200, "node2");
        $editor->addNode(97, 300, "node3");
        $editor->addNode(200, 298, "node4");
        $editor->addNode(200, 402, "node5");
        $editor->addNode(200, 100, "node6");


        $editor->addLink("node1", "node2", "l1");
        $editor->tidyLink("l1");
        $ll1 = $editor->map->getLink("l1");
        $this->assertEquals("1:8", $ll1->endpoints[0]->offset);
        $this->assertEquals("-1:-8", $ll1->endpoints[1]->offset);

        $editor->addLink("node2", "node3", "l2");
        $editor->tidyLink("l2");
        $ll2 = $editor->map->getLink("l2");
        $this->assertEquals($ll2->endpoints[0]->offset, "-3:8");
        $this->assertEquals("3:-8", $ll2->endpoints[1]->offset);

        $editor->addLink("node4", "node5", "l3");
        $editor->tidyLink("l3");
        $ll3 = $editor->map->getLink("l3");
        $this->assertEquals("S95", $ll3->endpoints[0]->offset);
        $this->assertEquals("N95", $ll3->endpoints[1]->offset);

        $editor->addLink("node1", "node6", "l4");
        $editor->tidyLink("l4");
        $ll4 = $editor->map->getLink("l4");
        $this->assertEquals("E95", $ll4->endpoints[0]->offset);
        $this->assertEquals("W95", $ll4->endpoints[1]->offset);

        $editor->addLink("node1", "node5", "l5");
        $editor->tidyLink("l5");
        $ll5 = $editor->map->getLink("l5");
        $this->assertEquals("", $ll5->endpoints[0]->offset);
        $this->assertEquals("", $ll5->endpoints[1]->offset);
    }

    public function testTidyAll()
    {
        $editor = new Editor();
        $editor->newConfig();

        $editor->addNode(100, 100, "node1");
        $editor->addNode(103, 200, "node2");
        $editor->addNode(97, 300, "node3");
        $editor->addNode(200, 298, "node4");
        $editor->addNode(200, 402, "node5");
        $editor->addNode(200, 100, "node6");

        $editor->addLink("node1", "node2", "l1");
        $editor->addLink("node2", "node3", "l2");
        $editor->addLink("node4", "node5", "l3");
        $editor->addLink("node1", "node6", "l4");
        $editor->addLink("node1", "node5", "l5");

        $editor->tidyAllLinks();

        // tidy bug that removed all the endpoints
        foreach ($editor->map->links as $link) {
            if ($link->name != ":: DEFAULT ::" && $link->name != "DEFAULT") {
                $this->assertFalse($link->isTemplate());
            }
        }

        $editor->retidyAllLinks();

        // tidy bug that removed all the endpoints
        foreach ($editor->map->links as $link) {
            if ($link->name != ":: DEFAULT ::" && $link->name != "DEFAULT") {
                $this->assertFalse($link->isTemplate());
            }
        }

        $ll4 = $editor->map->getLink("l4");
        $this->assertEquals("E95", $ll4->endpoints[0]->offset);
        $this->assertEquals("W95", $ll4->endpoints[1]->offset);

        $ll1 = $editor->map->getLink("l1");
        $this->assertEquals("1:8", $ll1->endpoints[0]->offset);
        $this->assertEquals("-1:-8", $ll1->endpoints[1]->offset);
    }

    public function testVIA()
    {
        $editor = new Editor();
        $editor->newConfig();

        $editor->addNode(100, 100, "node1");
        $editor->addNode(103, 200, "node2");

        $editor->addLink("node1", "node2", "l1");

        $link = $editor->map->getLink("l1");

        $editor->setLinkVia("l1", 150, 150);

        $this->assertEquals(1, count($link->viaList));

        $editor->clearLinkVias("l1");

        $this->assertEquals(0, count($link->viaList));
    }

    public function setUp()
    {
        $this->projectRoot = realpath(dirname(__FILE__) . "/../../../");

        self::$previouswd = getcwd();
        chdir($this->projectRoot);

        $version = explode('.', PHP_VERSION);
        $phptag = "php" . $version[0];

        self::$phptag = "php" . $version[0];
        self::$result1dir = "test-suite" . DIRECTORY_SEPARATOR . "results1-$phptag";

        if (!file_exists(self::$result1dir)) {
            mkdir(self::$result1dir);
        }
    }

    public function tearDown()
    {
        chdir(self::$previouswd);
    }

    public function makeString(
        $object
    ) {
        return (string)$object;
    }

    public function testInternals()
    {
        $this->assertTrue(Editor::rangeOverlaps(array(1, 5), array(4, 7)));
        $this->assertTrue(Editor::rangeOverlaps(array(4, 7), array(1, 5)));

        $this->assertFalse(Editor::rangeOverlaps(array(1, 5), array(6, 7)));

        $this->assertEquals(array(5, 10), Editor::findCommonRange(array(1, 10), array(5, 20)));

        $this->assertEquals(array(4, 5), Editor::findCommonRange(array(1, 5), array(4, 7)));

        $this->assertEquals(array(4, 5), Editor::findCommonRange(array(4, 7), array(1, 5)));

        $this->assertEquals("", Editor::simplifyOffset(0, 0));
        $this->assertEquals("1:2", Editor::simplifyOffset(1, 2));

        $this->assertEquals("E95", Editor::simplifyOffset(1, 0));
        $this->assertEquals("W95", Editor::simplifyOffset(-3, 0));

        $this->assertEquals("N95", Editor::simplifyOffset(0, -5));
        $this->assertEquals("S95", Editor::simplifyOffset(0, 9));
    }

    public function testNodeProperties()
    {
        $editor = new Editor();
        $editor->newConfig();

        $editor->addNode(100, 100, "named_node");
        $editor->addNode(100, 200, "other_named_node");
        $editor->addNode(200, 200, "third_named_node", "named_node");

        $editor->addLink("other_named_node", "named_node", "linky");
        $editor->addLink("named_node", "third_named_node", "named_link");

        $update = array(
            "name" => "named_node",
            "x" => 110,
            "y" => 120,
            "lock_to" => '',
            "new_name" => 'named_node',
            "label" => 'Tumblorkian',
            "infourl" => '',
            "hover" => '',
            "iconfilename" => ''
        );
        $editor->updateNode("named_node", $update);
        $n = $editor->map->getNode("named_node");
        $this->assertEquals(110, $n->x);
        $this->assertEquals(120, $n->y);
        $this->assertEquals(0, $n->originalX);
        $this->assertEquals(0, $n->originalY);
        $this->assertEquals('', $n->positionRelativeTo);
        $this->assertEquals('Tumblorkian', $n->label);

        $update['lock_to'] = 'other_named_node';
        $editor->updateNode("named_node", $update);

        $this->assertEquals('other_named_node', $n->positionRelativeTo);
        $this->assertEquals(10, $n->originalX);
        $this->assertEquals(-80, $n->originalY);

        $update['lock_to'] = '';
        $editor->updateNode("named_node", $update);

        $this->assertEquals('', $n->positionRelativeTo);
        $this->assertEquals(110, $n->x);
        $this->assertEquals(120, $n->y);
    }

    public function testNodeMove()
    {
        $editor = new Editor();
        $editor->newConfig();

        $editor->addNode(100, 100, "named_node");
        $editor->addNode(100, 200, "other_named_node");
        $editor->addNode(200, 200, "third_named_node", "named_node");

        $editor->addLink("other_named_node", "named_node", "linky");
        $editor->addLink("named_node", "third_named_node", "named_link");

        $l = $editor->map->getLink("linky");
        $n = $editor->map->getNode("named_node");
        $l->viaList = array(array(150, 150));

        // verify that vias rotate with the node movement
        $editor->moveNode("named_node", 100, 300);
        $this->assertEquals(100, $n->x);
        $this->assertEquals(300, $n->y);
        $this->assertEquals(50, $l->viaList[0][0]);
        $this->assertEquals(250, $l->viaList[0][1]);

        // and again, since we didn't change X before
        $editor->moveNode("named_node", 120, 320);
        $this->assertEquals(120, $n->x);
        $this->assertEquals(320, $n->y);
    }

    public function testLinkRename()
    {
        $editor = new Editor();
        $editor->newConfig();

        $editor->addNode(100, 100, "named_node");
        $editor->addNode(100, 200, "other_named_node");
        $editor->addNode(200, 200, "third_named_node", "named_node");

        $editor->addLink("other_named_node", "named_node", "linky");
        $editor->addLink("named_node", "third_named_node", "named_link");

        $l = $editor->map->getLink("linky");

        $editor->renameLink("linky", "gazorpazorpfield");
        $this->assertEquals("gazorpazorpfield", $l->name);

        $this->assertTrue($editor->map->linkExists("gazorpazorpfield"));
        $this->assertFalse($editor->map->linkExists("linky"));
    }

    public function testLinkProperties()
    {
        $editor = new Editor();
        $editor->newConfig();

        $editor->addNode(100, 100, "named_node");
        $editor->addNode(100, 200, "other_named_node");
        $editor->addNode(200, 200, "third_named_node", "named_node");

        $editor->addLink("other_named_node", "named_node", "linky");
        $editor->addLink("named_node", "third_named_node", "named_link");

        $l = $editor->map->getLink("linky");
        $ldd = $editor->map->getLink(":: DEFAULT ::");

        $this->assertEquals($ldd->width, $l->width);

        $update = array(
            "name" => "linky",
            "width" => 9,
            "target" => "static:10M:20M static:20M:10M",
            "bandwidth_in" => "10M",
            "bandwidth_out" => "15M",
            "commentpos_in" => 19,
            "commentpos_out" => 77,
            "comment_in" => "frik",
            "comment_out" => "frak",
            "infourl" => "dog",
            "hover" => "cat",
        );
        $editor->updateLink("linky", $update);


        $this->assertEquals(2, count($l->targets));
        $this->assertEquals("static:10M:20M", $l->targets[0]->asConfig());

        $this->assertEquals(1, count($l->overliburl[IN]));
        $this->assertEquals(1, count($l->overliburl[OUT]));
        $this->assertEquals(9, $l->width);

        $this->assertEquals("10M", $l->maxValuesConfigured[IN]);
        $this->assertEquals("15M", $l->maxValuesConfigured[OUT]);

        $this->assertEquals(10000000, $l->maxValues[IN]);
        $this->assertEquals(15000000, $l->maxValues[OUT]);

        $this->assertEquals(19, $l->commentOffsets[IN]);
        $this->assertEquals(77, $l->commentOffsets[OUT]);

        $this->assertEquals("frik", $l->comments[IN]);
        $this->assertEquals("frak", $l->comments[OUT]);

        $this->assertEquals("cat", $l->overliburl[OUT][0]);
        $this->assertEquals("cat", $l->overliburl[IN][0]);

        $this->assertEquals("dog", $l->infourl[OUT]);
        $this->assertEquals("dog", $l->infourl[IN]);

        $update = array(
            "name" => "linky",
            "width" => 2,
            "target" => "static:10M:20M static:20M:10M",
            "bandwidth_in" => "10M",
            "bandwidth_out" => "15M",
            "commentpos_in" => 19,
            "commentpos_out" => 77,
            "comment_in" => "frik",
            "comment_out" => "frak",
            "infourl" => "dog",
            "hover" => "cat dog",
        );
        $editor->updateLink("linky", $update);

        $this->assertEquals(2, $l->width);
        $this->assertEquals(2, count($l->overliburl[IN]));
        $this->assertEquals(2, count($l->overliburl[OUT]));
    }
}
