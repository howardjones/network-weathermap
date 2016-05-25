<?php include 'vars.php'; $PAGE_TITLE='Configuration Reference'; include 'common-page-head.php'; ?><h2 id="configref">Configuration Reference</h2><p>This page is automatically compiled, and documents all the
                    configuration directives that are available in PHP Weathermap
                    <?php echo $WEATHERMAP_VERSION; ?>.  </p>
    <h2 class="configsection">Introduction</h2>
        <div class="preamble">
  <div xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude" id="contents">
      <h4 xmlns="" class="configsection">Node-specific Configuration Directives</h4>
      <p xmlns="" id="context_NODE">
        <a class="tocentry" href="#NODE_COLORS">*COLOR</a>
        <a class="tocentry" href="#NODE_ICON">ICON</a>
        <a class="tocentry" href="#NODE_INFOURL">INFOURL</a>
        <a class="tocentry" href="#NODE_LABEL">LABEL</a>
        <a class="tocentry" href="#NODE_LABELANGLE">LABELANGLE</a>
        <a class="tocentry" href="#NODE_LABELFONT">LABELFONT</a>
        <a class="tocentry" href="#NODE_LABELOFFSET">LABELOFFSET</a>
        <a class="tocentry" href="#NODE_MAXVALUE">MAXVALUE</a>
        <a class="tocentry" href="#NODE_NODE">NODE</a>
        <a class="tocentry" href="#NODE_NOTES">NOTES</a>
        <a class="tocentry" href="#NODE_OVERLIBCAPTION">OVERLIBCAPTION</a>
        <a class="tocentry" href="#NODE_OVERLIBGRAPH">OVERLIBGRAPH</a>
        <a class="tocentry" href="#NODE_OVERLIBHEIGHT">OVERLIBHEIGHT</a>
        <a class="tocentry" href="#NODE_OVERLIBWIDTH">OVERLIBWIDTH</a>
        <a class="tocentry" href="#NODE_POSITION">POSITION</a>
        <a class="tocentry" href="#NODE_SET">SET</a>
        <a class="tocentry" href="#NODE_TARGET">TARGET</a>
        <a class="tocentry" href="#NODE_TEMPLATE">TEMPLATE</a>
        <a class="tocentry" href="#NODE_USEICONSCALE">USEICONSCALE</a>
        <a class="tocentry" href="#NODE_USESCALE">USESCALE</a>
        <a class="tocentry" href="#NODE_ZORDER">ZORDER</a>
       </p>
      <h4 xmlns="" class="configsection">Link-specific Configuration Directives</h4>
      <p xmlns="" id="context_LINK">
        <a class="tocentry" href="#LINK_ARROWSTYLE">ARROWSTYLE</a>
        <a class="tocentry" href="#LINK_BANDWIDTH">BANDWIDTH</a>
        <a class="tocentry" href="#LINK_BWFONT">BWFONT</a>
        <a class="tocentry" href="#LINK_BWLABEL">BWLABEL</a>
        <a class="tocentry" href="#LINK_BWLABELPOS">BWLABELPOS</a>
        <a class="tocentry" href="#LINK_BWSTYLE">BWSTYLE</a>
        <a class="tocentry" href="#LINK_COLORS">*COLOR</a>
        <a class="tocentry" href="#LINK_COMMENTFONT">COMMENTFONT</a>
        <a class="tocentry" href="#LINK_COMMENTPOS">COMMENTPOS</a>
        <a class="tocentry" href="#LINK_COMMENTSTYLE">COMMENTSTYLE</a>
        <a class="tocentry" href="#LINK_DUPLEX">DUPLEX</a>
        <a class="tocentry" href="#LINK_INBWFORMAT">INBWFORMAT</a>
        <a class="tocentry" href="#LINK_INCOMMENT">INCOMMENT</a>
        <a class="tocentry" href="#LINK_INFOURL">INFOURL</a>
        <a class="tocentry" href="#LINK_ININFOURL">ININFOURL</a>
        <a class="tocentry" href="#LINK_INNOTES">INNOTES</a>
        <a class="tocentry" href="#LINK_INOVERLIBCAPTION">INOVERLIBCAPTION</a>
        <a class="tocentry" href="#LINK_INOVERLIBGRAPH">INOVERLIBGRAPH</a>
        <a class="tocentry" href="#LINK_LINK">LINK</a>
        <a class="tocentry" href="#LINK_LINKSTYLE">LINKSTYLE</a>
        <a class="tocentry" href="#LINK_NODES">NODES</a>
        <a class="tocentry" href="#LINK_NOTES">NOTES</a>
        <a class="tocentry" href="#LINK_OUTBWFORMAT">OUTBWFORMAT</a>
        <a class="tocentry" href="#LINK_OUTCOMMENT">OUTCOMMENT</a>
        <a class="tocentry" href="#LINK_OUTINFOURL">OUTINFOURL</a>
        <a class="tocentry" href="#LINK_OUTNOTES">OUTNOTES</a>
        <a class="tocentry" href="#LINK_OUTOVERLIBCAPTION">OUTOVERLIBCAPTION</a>
        <a class="tocentry" href="#LINK_OUTOVERLIBGRAPH">OUTOVERLIBGRAPH</a>
        <a class="tocentry" href="#LINK_OVERLIBCAPTION">OVERLIBCAPTION</a>
        <a class="tocentry" href="#LINK_OVERLIBGRAPH">OVERLIBGRAPH</a>
        <a class="tocentry" href="#LINK_OVERLIBHEIGHT">OVERLIBHEIGHT</a>
        <a class="tocentry" href="#LINK_OVERLIBWIDTH">OVERLIBWIDTH</a>
        <a class="tocentry" href="#LINK_SET">SET</a>
        <a class="tocentry" href="#LINK_SPLITPOS">SPLITPOS</a>
        <a class="tocentry" href="#LINK_TARGET">TARGET</a>
        <a class="tocentry" href="#LINK_TEMPLATE">TEMPLATE</a>
        <a class="tocentry" href="#LINK_USESCALE">USESCALE</a>
        <a class="tocentry" href="#LINK_VIA">VIA</a>
        <a class="tocentry" href="#LINK_VIASTYLE">VIASTYLE</a>
        <a class="tocentry" href="#LINK_WIDTH">WIDTH</a>
        <a class="tocentry" href="#LINK_ZORDER">ZORDER</a>
       </p>
      <h4 xmlns="" class="configsection">Global Configuration Directives</h4>
      <p xmlns="" id="context_GLOBAL">
        <a class="tocentry" href="#GLOBAL_BACKGROUND">BACKGROUND</a>
        <a class="tocentry" href="#GLOBAL_COLORS">*COLOR</a>
        <a class="tocentry" href="#GLOBAL_DATAOUTPUTFILE">DATAOUTPUTFILE</a>
        <a class="tocentry" href="#GLOBAL_FONT">*FONT</a>
        <a class="tocentry" href="#GLOBAL_FONTDEFINE">FONTDEFINE</a>
        <a class="tocentry" href="#GLOBAL_HEIGHT">HEIGHT</a>
        <a class="tocentry" href="#GLOBAL_HTMLOUTPUTFILE">HTMLOUTPUTFILE</a>
        <a class="tocentry" href="#GLOBAL_HTMLSTYLE">HTMLSTYLE</a>
        <a class="tocentry" href="#GLOBAL_HTMLSTYLESHEET">HTMLSTYLESHEET</a>
        <a class="tocentry" href="#GLOBAL_IMAGEOUTPUTFILE">IMAGEOUTPUTFILE</a>
        <a class="tocentry" href="#GLOBAL_IMAGEURI">IMAGEURI</a>
        <a class="tocentry" href="#GLOBAL_INCLUDE">INCLUDE</a>
        <a class="tocentry" href="#GLOBAL_KEYPOS">KEYPOS</a>
        <a class="tocentry" href="#GLOBAL_KEYSTYLE">KEYSTYLE</a>
        <a class="tocentry" href="#GLOBAL_KILO">KILO</a>
        <a class="tocentry" href="#GLOBAL_MAXTIMEPOS">MAXTIMEPOS</a>
        <a class="tocentry" href="#GLOBAL_MINTIMEPOS">MINTIMEPOS</a>
        <a class="tocentry" href="#GLOBAL_SCALE">SCALE</a>
        <a class="tocentry" href="#GLOBAL_SET">SET</a>
        <a class="tocentry" href="#GLOBAL_TIMEPOS">TIMEPOS</a>
        <a class="tocentry" href="#GLOBAL_TITLE">TITLE</a>
        <a class="tocentry" href="#GLOBAL_TITLEPOS">TITLEPOS</a>
        <a class="tocentry" href="#GLOBAL_WIDTH">WIDTH</a>
       </p>
    </div>
</div>
    

    <h2 id="s_scope_NODE" class="configsection">Node-specific Configuration Directives</h2>
        <div class="referenceentry">
  <h3 id="NODE_NODE">NODE</h3>
  <div class="definition">NODE

        <em class="meta">nodename</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The initial definition of a <a href="#NODE_NODE">NODE.</a> This must come before any other 
 configuration related to this node. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The 'nodename' is used in link definitions to specify which nodes the link 
 joins. The nodename is must be a single word, with no spaces. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">There is one special node name, 'DEFAULT', which allows for the setting of 
 defaults. All nodes that are defined after this one in the configuration file 
 will use the parameters of this node as a starting point. For this reason, it is 
 best to define the DEFAULT node at the top of the configuration file, if you 
 intend to use it. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.7</dt>
      <dd>Added DEFAULT node.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="NODE_TEMPLATE">TEMPLATE</h3>
  <div class="definition">TEMPLATE

        <em class="meta">nodename</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">By default, each new node starts with the same set of properties. You can 
 change the default properties by defining a node called DEFAULT. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">You can also use the <a href="#NODE_TEMPLATE">TEMPLATE</a> keyword to make a node inherit it's settings from 
 <em>any</em> other node. The node you use must be defined earlier in the config 
 file than where you use it. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">You can make template-only nodes that are not visible in the map, by not 
 including a <a href="#LINK_NODES">NODES</a> line in the node. Template nodes can also use templates, to 
 build up a hierarchy of 'types'. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"><strong>NOTE:</strong> The <a href="#NODE_TEMPLATE">TEMPLATE</a> line should be the first line in the <a href="#NODE_NODE">NODE</a> 
 definition, as it will copy the configuration over the top of anything else you 
 have already defined for that <a href="#NODE_NODE">NODE.</a> </p> 
  </div>
  <div class="examples">
    <h4>Examples</h4>
    <blockquote class="example">
      <small>
        <cite>NODE Templates in use - with template-only nodes</cite>
      </small>
      <pre>NODE server
            </pre>
    </blockquote>
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.96</dt>
      <dd>Added template support
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="NODE_POSITION">POSITION</h3>
  <div class="definition">POSITION

        <em class="meta">x-coord</em>

        <em class="meta">y-coord</em>
    </div>
  <div class="definition">POSITION

        <em class="meta">nodename</em>

        <em class="meta">x-coord</em>

        <em class="meta">y-coord</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies where to place the node on the map. Coordinates are in pixel units, 
 with the origin at the top-left of the map. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Optionally, you can also specify another node that the coordinates are 
 relative to. This allows you to have 'sub-nodes' that follow a master node 
 around as you alter the map. Relative nodes can be relative to other relative 
 nodes, as long as the node at the end of the chain is not relatively 
 positioned! </p> 
  </div>
  <div class="examples">
    <h4>Examples</h4>
    <blockquote class="example">
      <small>
        <cite>Example of a 'sub-node', that will be 20 pixels above the main
        node, wherever that gets moved to. It is used to show additional information
        about the main node.</cite>
      </small>
      <pre>
NODE main_node
    POSITION 200 320
    LABEL MAIN

NODE sub_node
    POSITION main_node 0 -20
    LABEL {nodes:main_node:invalue}
            </pre>
    </blockquote>
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.9</dt>
      <dd>Add relative position from other nodes.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="NODE_LABEL">LABEL</h3>
  <div class="definition">LABEL

        <em class="meta">labeltext</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies a label for the node. Everything to the end of the line is used. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">If the node has an <a href="#NODE_ICON">ICON</a> defined as well, then you can specify the position of 
 the label relative to the node's centre-point by using <a href="#NODE_LABELOFFSET">LABELOFFSET.</a> </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The text for the label can contain 
 <a href="advanced.html#tokens">special tokens</a> to show map data. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">This is drawn using the font specified by <a href="#NODE_LABELFONT">LABELFONT</a> in the colours specified 
 by <a href="#NODE_COLORS">LABELFONTCOLOR,</a> <a href="#NODE_COLORS">LABELFONTSHADOWCOLOR,</a> <a href="#NODE_COLORS">LABELBGCOLOR</a> and <a href="#NODE_COLORS">LABELOUTLINECOLOR.</a> </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.9</dt>
      <dd>Added 'special token' support.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="NODE_TARGET">TARGET</h3>
  <div class="definition">TARGET

        <em class="meta">targetspec</em>
    </div>
  <div class="definition">TARGET

        <em class="meta">targetspec</em>

        <em class="meta">targetspec</em>
    </div>
  <div class="definition">TARGET

        <em class="meta">targetspec</em>

        -

        <em class="meta">targetspec</em>
    </div>
  <div class="definition">TARGET

        <em class="meta">targetspec</em>

        n*

        <em class="meta">targetspec</em>
    </div>
  <div class="definition">TARGET "<em class="meta">targetspec</em>"
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies where to look for the current "throughput" information for this 
 <a href="#NODE_NODE">NODE.</a> You can also specify multiple targets, which will then be added together 
 to make the aggregate result which is then displayed. Specify the targets on one 
 <a href="#NODE_TARGET">TARGET</a> line, seperated with a space. If a targetspec starts with a '-', then 
 it's value will be <i>subtracted</i> from the final result instead. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Also, if a targetspec starts with a number, then a *, then it's used a 
 scaling factor on the result. You can do basic maths with this, especially if 
 you remember that multiplying by a number below 1 is the same as dividing by 1 
 divided by that number (0.5* is the same as divide by 2). </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">For both the '-' and '*' options, 
 <em>there must be no spaces</em> between any modifiers and the actual target 
 string after it. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">It's important to note, especially for <a href="#NODE_NODE">NODEs,</a> that the value that is used 
 does not have to be bandwidth. You can use data for temperature, session-counts, 
 CPU usage or anything else you can get data for. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The actual contents of the 
 <em>targetspec</em> depend on the data source plugins that are available. 
 <a href="targets.html">The standard plugins are documented here</a>. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">To help with filenames that can contain spaces, or for the external script 
 data source, you can enclose the entire targetspec in double-quotes (") to show 
 that it is a single targetspec. 
 <em>The quotes must be around the whole targetspec, including any 
 prefixes.</em> </p> 
  </div>
  <div class="examples">
    <h4>Examples</h4>
    <blockquote class="example">
      <small>
        <cite>Using multiple data sources for one link</cite>
      </small>
      <pre>TARGET link1a.rrd link1b.rrd
            </pre>
    </blockquote>
    <blockquote class="example">
      <small>
        <cite>Taking the input from one file, and output from
        another</cite>
      </small>
      <pre>TARGET poot.rrd:-:DS1 poot2.rrd:DS0:-
            </pre>
    </blockquote>
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.96</dt>
      <dd>Added quotes option for targets with spaces.
        </dd>
      <dt>0.96</dt>
      <dd>Added scale factors for all datasources.
        </dd>
      <dt>0.91</dt>
      <dd>Added 'negative' datasources.
        </dd>
      <dt>0.9</dt>
      <dd>Added plugin data sources, node targets, and added new
        plugins.
        </dd>
      <dt>0.8</dt>
      <dd>Added ability to specify multiple targets. Added
        tab-delimited data source. Added 'ignore' DS name.
        </dd>
      <dt>0.5</dt>
      <dd>Added ability to specify DS names.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="NODE_USESCALE">USESCALE</h3>
  <div class="definition">USESCALE

        <em class="meta">scalename</em>
    </div>
  <div class="definition">USESCALE

        <em class="meta">scalename</em>

        <em class="meta">{in,out}</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specify which <a href="#GLOBAL_SCALE">SCALE</a> to use to decide the colour of this node. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">After the percentage usage is calculated (using BANDWIDTH/MAXVALUE and data 
 from the <a href="#NODE_TARGET">TARGET</a> line), the colour is decided by looking up the percentage 
 against this <a href="#GLOBAL_SCALE">SCALE.</a> If there is no <a href="#NODE_USESCALE">USESCALE</a> line, then the default scale is 
 used. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">If you also specify 'absolute', then no percentage calculation is performed. 
 The raw values from the <a href="#NODE_TARGET">TARGET</a> line are just looked up in the named <a href="#GLOBAL_SCALE">SCALE.</a> </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">For nodes, you can also specify a scalename of 'none'. This stops the node's 
 colour from changing at all. This is useful if you want to use the <a href="#NODE_TARGET">TARGET</a> line 
 to fetch data that is used in the <a href="#NODE_LABEL">LABEL</a> or <a href="#NODE_ICON">ICON</a> of the node, for example, 
 without changing the colour of the node itself. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">You can also change the colour of the <a href="#NODE_ICON">ICON</a> associated with a <a href="#NODE_NODE">NODE</a> according 
 to a different <a href="#GLOBAL_SCALE">SCALE,</a> by using <a href="#NODE_USEICONSCALE">USEICONSCALE.</a> </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.97</dt>
      <dd>Added absolute scale support.
        </dd>
      <dt>0.95</dt>
      <dd>Added USEICONSCALE.
        </dd>
      <dt>0.9</dt>
      <dd>Added named scales and USESCALE.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="NODE_MAXVALUE">MAXVALUE</h3>
  <div class="definition">MAXVALUE

        <em class="meta">max-value</em>
    </div>
  <div class="definition">MAXVALUE

        <em class="meta">max-in-value</em>

        <em class="meta">max-out-value</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies the maximum value(s) for the node, in the same way that <a href="#LINK_BANDWIDTH">BANDWIDTH</a> 
 does for a <a href="#LINK_LINK">LINK.</a> These are used to calculate the percentage usage value, which 
 in turn is used to decide the <a href="#NODE_NODE">NODE's</a> colour, if it has a <a href="#NODE_TARGET">TARGET</a> defined. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The default value is 100, which has the effect of not changing the input 
 value ( (n/100)*100 = n ). </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.9</dt>
      <dd>Added TARGET and MAXVALUE for nodes.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="NODE_ICON">ICON</h3>
  <div class="definition">ICON

        <em class="meta">iconimagefile</em>
    </div>
  <div class="definition">ICON

        <em class="meta">maxwidth</em>

        <em class="meta">maxheight</em>

        <em class="meta">iconimagefile</em>
    </div>
  <div class="definition">ICON none
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies an icon to use for the node. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The filename can either be a full path to the image, or a relative one. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The icon file must be in PNG, JPEG or GIF format. Alpha-transparency within 
 the icon should be honoured by Weathermap for PNG icons, to create irregular 
 shapes. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">If you specify the word 'none' as the icon filename, then no icon is used. 
 This is useful if you have specified an <a href="#NODE_ICON">ICON</a> in the DEFAULT <a href="#NODE_NODE">NODE,</a> and want to 
 override that for a few special cases. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">There are some special icon filenames that can be used to generate an icon 
 image without using an external file. These all 
 <em>require</em> you to specify a <em>maxwidth</em> and 
 <em>maxheight</em> which are then used as the size of the icon. The 'magic 
 filenames' are: 

 <ul><li>'box' - to produce a square cornered box.</li><li>'rbox' 
 - to produce a round-cornered box</li><li>'round' 
 - to produce a circle or ellipse.</li> 

 <li>'inpie' &amp; 'outpie' 
 - to produce a pie-chart of either the in or out value relative to it's 
 maximum. The colouring options on this are likely to change in a future 
 version.</li> 

 <li>'nink' 
 - to produce a circular 'yin-yang'-style symbol, with each half showing the 
 in and out values. The colouring options on this are likely to change in a 
 future version.</li> 
 </ul>The colours for these "artificial icons" are specified using the 
 <a href="#NODE_COLORS">AICONFILLCOLOR</a> and <a href="#NODE_COLORS">AICONOUTLINECOLOR</a> keywords. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The second form allows you to specify a maximum width and height for the 
 icon. If the PNG file that you specify is bigger or smaller than this size, then 
 it is automatically scaled up (or down) in proportion, so that it fits into a 
 box of the size you specify. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The size of the icon image is used by <a href="#NODE_LABELOFFSET">LABELOFFSET</a> to decided how far to move 
 the label, if you use compass-point offsets. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The text for the filename can contain 
 <a href="advanced.html#tokens">special tokens</a> to select an icon based on 
 map data. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">You can adjust the colour of the icon according to a <a href="#GLOBAL_SCALE">SCALE,</a> by using 
 <a href="#NODE_USEICONSCALE">USEICONSCALE,</a> if you are using the PHP GD library (the function required is not 
 present in the main GD library). </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.96</dt>
      <dd>Added inpie, outpie and nink.
        </dd>
      <dt>0.95</dt>
      <dd>Added Artificial Icon support - round, rbox and box.
        </dd>
      <dt>0.95</dt>
      <dd>Added Icon colourising support.
        </dd>
      <dt>0.9</dt>
      <dd>Added 'special token' support.
        </dd>
      <dt>0.9</dt>
      <dd>Added JPEG and GIF support.
        </dd>
      <dt>0.9</dt>
      <dd>Added special icon 'none', and automatic scaling.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="NODE_USEICONSCALE">USEICONSCALE</h3>
  <div class="definition">USEICONSCALE

        <em class="meta">scalename</em>
    </div>
  <div class="definition">USEICONSCALE

        <em class="meta">scalename</em>

        <em class="meta">{in,out}</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specify which <a href="#GLOBAL_SCALE">SCALE</a> to use to decide the colour of the icon for this node. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">After the percentage usage is calculated (using BANDWIDTH/MAXVALUE and data 
 from the <a href="#NODE_TARGET">TARGET</a> line), the colour is decided by looking up the percentage 
 against this <a href="#GLOBAL_SCALE">SCALE.</a> If there is no <a href="#NODE_USEICONSCALE">USEICONSCALE</a> line, then no scale is used, and 
 the icon colour does not change. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Hint: This facility works best when you start with greyscale images. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"><strong>NOTE:</strong> Prior to 0.97, Icon colourising used the PHP 
 <tt>imagefilter()</tt> function. This function is 
 <em>only</em> available in the version of GD that is bundled with PHP, and not 
 with the official GD library. Several popular operating systems (e.g. 
 Debian/Ubuntu) use the official GD library rather than the bundled PHP library. 
 If you know that you 
 <em>do</em> have the imagefilter function, and you prefer the 'old-style' 
 coloring, then you can add 
 <tt><a href="#NODE_SET">SET</a> use_imagefilter 1</tt> in the top section of your map config file, to use 
 <tt>imagefilter</tt> instead. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.97</dt>
      <dd>Removed dependency on imagefilter
        </dd>
      <dt>0.95</dt>
      <dd>Added USEICONSCALE.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="NODE_LABELOFFSET">LABELOFFSET</h3>
  <div class="definition">LABELOFFSET

        <em class="meta">compass-point</em>
    </div>
  <div class="definition">LABELOFFSET

        <em class="meta">x-offset</em>

        <em class="meta">y-offset</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">If you specify an <a href="#NODE_ICON">ICON,</a> and also a <a href="#NODE_LABEL">LABEL,</a> then you will find that the label 
 is often hard to read. <a href="#NODE_LABELOFFSET">LABELOFFSET</a> allows you to move the position of the <a href="#NODE_LABEL">LABEL,</a> 
 so that it's not directly over the centre of the node anymore. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">You can specify a compass-point (e.g. <a href="#NODE_LABELOFFSET">LABELOFFSET</a> S). The compass-point 
 method takes the size of the <a href="#NODE_ICON">ICON,</a> and uses that as the offset distance in the 
 direction you specify. This way, you can change your icon for something of a 
 different size, and not need to change all your offsets. You can use the main 8 
 points of the compass: N, E, S, W, NE, SE, NW, SW. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"> 
 <img src="../images/compass-points.png"/> 

 <br/>The compass points, relative to the node's bounding-box.. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">For more control, you can specify an integer offset for the x and y positions 
 of the label (e.g. <a href="#NODE_LABELOFFSET">LABELOFFSET</a> -10 -20) instead. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.7</dt>
      <dd>Originally added LABELOFFSET
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="NODE_LABELANGLE">LABELANGLE</h3>
  <div class="definition">LABELANGLE

        <em class="meta">angle</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies a rotation angle of the label for a node. Allowed angles are 0, 90, 
 180 and 270 degrees. The rotation is around the centre of the label, after any 
 <a href="#NODE_LABELOFFSET">LABELOFFSET</a> has been applied. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The <a href="#NODE_LABELFONT">LABELFONT</a> 
 <em>must be a TrueType font</em> for angles other than 0 (the default) as these 
 are the only font type to support rotating text. </p> 
 <figure xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"> 
 <img src="../images/labelangle+labeloffset.png"/> 

 <figcaption>Rotated label, using <tt><a href="#NODE_LABELANGLE">LABELANGLE</a> 90</tt> and <tt><a href="#NODE_LABELOFFSET">LABELOFFSET</a> E</tt></figcaption> 
 </figure> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.95</dt>
      <dd>Added LABELANGLE.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="NODE_LABELFONT">LABELFONT</h3>
  <div class="definition">LABELFONT

        <em class="meta">fontnumber</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specify the font used for drawing the <a href="#NODE_LABEL">LABEL.</a> </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Fonts are specified by number. The GD library that Weathermap uses has 5 
 built-in fonts, 1-5. You can define new fonts based on TrueType or GD fonts by 
 using the <a href="#GLOBAL_FONTDEFINE">FONTDEFINE</a> directive. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"> 
 <img src="../images/font-sizes.png"/>The built-in GD fonts. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.7</dt>
      <dd>Global NODEFONT became per-node LABELFONT.
        </dd>
      <dt>0.6</dt>
      <dd>Originally added NODEFONT.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="NODE_COLORS">*COLOR</h3>
  <div class="definition">LABELFONTCOLOR

        <em class="meta">red</em>

        <em class="meta">green</em>

        <em class="meta">blue</em>
    </div>
  <div class="definition">LABELFONTSHADOWCOLOR

        <em class="meta">red</em>

        <em class="meta">green</em>

        <em class="meta">blue</em>
    </div>
  <div class="definition">LABELBGCOLOR

        <em class="meta">red</em>

        <em class="meta">green</em>

        <em class="meta">blue</em>
    </div>
  <div class="definition">LABELOUTLINECOLOR

        <em class="meta">red</em>

        <em class="meta">green</em>

        <em class="meta">blue</em>
    </div>
  <div class="definition">AICONOUTLINECOLOR

        <em class="meta">red</em>

        <em class="meta">green</em>

        <em class="meta">blue</em>
    </div>
  <div class="definition">AICONFILLCOLOR

        <em class="meta">red</em>

        <em class="meta">green</em>

        <em class="meta">blue</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specify the colours used for drawing the <a href="#NODE_LABEL">LABEL.</a> </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">red, green and blue are numbers from 0 to 255. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"><a href="#NODE_COLORS">LABELFONTSHADOWCOLOR,</a> <a href="#NODE_COLORS">LABELBGCOLOR</a> and <a href="#NODE_COLORS">LABELOUTLINECOLOR,</a> <a href="#NODE_COLORS">AICONFILLCOLOR</a> and <a href="#NODE_COLORS">AICONOUTLINECOLOR</a> 
 have an additional 
 option - 'none' 
 - which stops that element of the <a href="#NODE_LABEL">LABEL</a> being drawn. <a href="#NODE_COLORS">LABELFONTSHADOWCOLOR</a> 
 defaults to 'none'. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"><a href="#NODE_COLORS">LABELFONTCOLOR</a> has an additional option 'contrast', which will select either 
 black or white depending on the current <a href="#NODE_COLORS">LABELBGCOLOR.</a> This is especially useful 
 if you are using a <a href="#GLOBAL_SCALE">SCALE</a> to change the colour of your <a href="#NODE_NODE">NODE</a> <a href="#NODE_LABEL">LABELs.</a> </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"><a href="#NODE_COLORS">AICONOUTLINECOLOR</a> and <a href="#NODE_COLORS">AICONFILLCOLOR</a> are used to colour an 'artificial <a href="#NODE_ICON">ICON'</a> 
 if one is defined for this node. To allow the artifical icon to 
 <em>also</em> follow the colour of the <a href="#NODE_COLORS">LABELBGCOLOR</a> when you are using a <a href="#GLOBAL_SCALE">SCALE,</a> 
 you can also specify 'copy' as the colour for <a href="#NODE_COLORS">AICONFILLCOLOR.</a> See <a href="#NODE_ICON">ICON</a> for more 
 about artificial icons. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.98</dt>
      <dd>Added 'none' for AICONFILLCOLOR.
        </dd>
      <dt>0.95</dt>
      <dd>Added 'contrast' and 'copy' options.
        </dd>
      <dt>0.95</dt>
      <dd>Added AICONFILLCOLOR and AICONOUTLINECOLOR.
        </dd>
      <dt>0.8</dt>
      <dd>Added LABELFONTCOLOR, LABELFONTSHADOWCOLOR, LABELBGCOLOR
        and LABELOUTLINECOLOR.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="NODE_INFOURL">INFOURL</h3>
  <div class="definition">INFOURL

        <em class="meta">url</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Creates a hyperlink in the HTML output. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">If you are using the HTML output facility, then a link is added to the 
 &lt;map&gt; section of the HTML so that when you click on the node, you are 
 taken to the url specified here. </p> 
  </div>
</div>

        <div class="referenceentry">
  <h3 id="NODE_OVERLIBGRAPH">OVERLIBGRAPH</h3>
  <div class="definition">OVERLIBGRAPH

        <em class="meta">url</em>
    </div>
  <div class="definition">OVERLIBGRAPH

        <em class="meta">url</em>

        <em class="meta">url</em>...
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Creates a popup image in the HTML output. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">If you are using the HTML output facility, and <a href="#GLOBAL_HTMLSTYLE">HTMLSTYLE</a> is set to 'overlib', 
 then a link is added to the &lt;map&gt; section of the HTML so that when you 
 move the mouse pointer over the the node, a box will pop up containing the image 
 that you specify. Typically used to link to historical data in your network 
 monitoring system. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">You can also specify more than one url, in which case the images are 
 'stacked' one after another in the popup box. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">If a <a href="#NODE_NOTES">NOTES</a> line is also specified for a node, then the image(s) specified 
 here appears with the <a href="#NODE_NOTES">NOTES</a> text underneath it. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">You can influence how Weathermap positions the popup box, using the 
 <a href="#NODE_OVERLIBWIDTH">OVERLIBWIDTH</a> and <a href="#NODE_OVERLIBHEIGHT">OVERLIBHEIGHT</a> keywords. </p> 
  </div>
  <div class="examples">
    <h4>Examples</h4>
    <blockquote class="example">
      <small>
        <cite>Typical use of OVERLIBGRAPH</cite>
      </small>
      <pre>OVERLIBGRAPH http://www.yoursite.net/mrtg/router1-cpu-daily.png
            </pre>
    </blockquote>
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.95</dt>
      <dd>Added multiple URL support
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="NODE_OVERLIBWIDTH">OVERLIBWIDTH</h3>
  <div class="definition">OVERLIBWIDTH

        <em class="meta">imagewidth</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specify the width, in pixels of the graph image referred to by <a href="#NODE_OVERLIBGRAPH">OVERLIBGRAPH</a> 
 line. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">This is an optional extra that allows the OverLib library to make a better 
 job of positioning the 'popup' image so that it doesn't appear off the edge of 
 the screen. Typically, you would use this once, in the DEFAULT <a href="#NODE_NODE">NODE.</a> If you use 
 this, you must also use <a href="#NODE_OVERLIBHEIGHT">OVERLIBHEIGHT,</a> for either to have any effect. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.7</dt>
      <dd>Originally added OVERLIBWIDTH and OVERLIBHEIGHT based on
        code by Niels Baggesen.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="NODE_OVERLIBHEIGHT">OVERLIBHEIGHT</h3>
  <div class="definition">OVERLIBHEIGHT

        <em class="meta">imagewidth</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specify the width, in pixels of the graph image referred to by <a href="#NODE_OVERLIBGRAPH">OVERLIBGRAPH</a> 
 line. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">This is an optional extra that allows the OverLib library to make a better 
 job of positioning the 'popup' image so that it doesn't appear off the edge of 
 the screen. Typically, you would use this once, in the DEFAULT <a href="#NODE_NODE">NODE.</a> If you use 
 this, you must also use <a href="#NODE_OVERLIBWIDTH">OVERLIBWIDTH,</a> for either to have any effect. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.7</dt>
      <dd>Originally added OVERLIBWIDTH and OVERLIBHEIGHT based on
        code by Niels Baggesen.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="NODE_OVERLIBCAPTION">OVERLIBCAPTION</h3>
  <div class="definition">OVERLIBCAPTION

        <em class="meta">caption text</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specify the caption used for the popup HTML 'window' if you have also 
 specified an <a href="#NODE_OVERLIBGRAPH">OVERLIBGRAPH</a> line. By default, this is the name of the <a href="#NODE_NODE">NODE.</a> </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The text for the caption can contain 
 <a href="advanced.html#tokens">special tokens</a> to show map data. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.9</dt>
      <dd>Originally added OVERLIBCAPTION.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="NODE_NOTES">NOTES</h3>
  <div class="definition">NOTES

        <em class="meta">notes text</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies the text or HTML notes for a node. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The 
 <em>notes text</em> appears in a popup box when the user hovers their mouse over 
 the node. If an <a href="#NODE_OVERLIBGRAPH">OVERLIBGRAPH</a> is specified too, then the text appears below the 
 graph. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The map <a href="#GLOBAL_HTMLSTYLE">HTMLSTYLE</a> must be set to 'overlib' to enable any of the mouse-hover 
 functionality. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.9</dt>
      <dd>Originally added NOTES.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="NODE_SET">SET</h3>
  <div class="definition">SET

        <em class="meta">hintname</em>

        <em class="meta">hintvalue</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies a value for a <em>hint variable</em>. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Hint Variables allow the user to pass settings to the internals of Weathermap 
 that wouldn't normally need to be changed, or that aren't part of the core 
 Weathermap application. Examples are: small rendering changes, parameters for 
 datasources plugins and similar. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Hint Variables are either Global for the map, or assigned to a specific link 
 or node. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">There is more about Hint Variables in the 
 <a href="advanced.html">Advanced Topics</a> section. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.9</dt>
      <dd>Originally added SET.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="NODE_ZORDER">ZORDER</h3>
  <div class="definition">ZORDER

        <em class="meta">z-coord</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies the order in which to draw this item on the map. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">By default, all nodes are drawn above all links. There are some situations 
 where you might like to change this, for example if you use a <a href="#NODE_NODE">NODE</a> as a 
 background image, and you want links to show in front of that image. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">By default, all nodes have a Z coordinate of 600, and all links have 300. The 
 map legend is at 1000, which cannot be changed (you can move everything else 
 above it, if you like, of course). Items are drawn from lowest Z up to highest 
 Z, so if you want a particular node to appear underneath the default links, you 
 can use <a href="#NODE_ZORDER">'ZORDER</a> 250' to do that. 
 </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.96</dt>
      <dd>Added Z-ordering support.
        </dd>
    </dl>
  </div>
</div>
    

    <h2 id="s_scope_LINK" class="configsection">Link-specific Configuration Directives</h2>
        <div class="referenceentry">
  <h3 id="LINK_LINK">LINK</h3>
  <div class="definition">LINK

        <em class="meta">linkname</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The first line of a <a href="#LINK_LINK">LINK</a> definition. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The linkname must be unique within the map, and must not contain spaces. The 
 only place it currently appears is in the small title-bar of a popup graph if 
 you specify an <a href="#LINK_OVERLIBGRAPH">OVERLIBGRAPH</a> without an <a href="#LINK_OVERLIBCAPTION">OVERLIBCAPTION,</a> however. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">There is one special link name, 'DEFAULT', which allows for the setting of 
 defaults. All links that are defined after this one in the configuration file 
 will use the parameters of this link as a starting point. For this reason, it is 
 best to define the DEFAULT link at the top of the configuration file, if you 
 intend to use it. </p> 
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_TEMPLATE">TEMPLATE</h3>
  <div class="definition">TEMPLATE

        <em class="meta">linkname</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">By default, each new link starts with the same set of properties. You can 
 change the default properties by defining a link called DEFAULT. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">You can also use the <a href="#LINK_TEMPLATE">TEMPLATE</a> keyword to make a link inherit it's settings from 
 <em>any</em> other link. The link you use must be defined earlier in the config 
 file than where you use it. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">You can make template-only links that are not visible in the map, by not 
 including a <a href="#LINK_NODES">NODES</a> line in the link. Template links can also use templates, to 
 build up a hierarchy of 'types'. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"><strong>NOTE:</strong> The <a href="#LINK_TEMPLATE">TEMPLATE</a> line should be the first line in the <a href="#LINK_LINK">LINK</a> 
 definition, as it will copy the configuration over the top of anything else you 
 have already defined for that <a href="#LINK_LINK">LINK.</a> </p> 
  </div>
  <div class="examples">
    <h4>Examples</h4>
    <blockquote class="example">
      <small>
        <cite>LINK Templates in use - with template-only links</cite>
      </small>
      <pre>LINK bigpipe
    WIDTH 8
    ARROWSTYLE classic

LINK smallpipe
    WIDTH 3
    ARROWSTYLE compact

# this link uses the bigpipe template, so it doesn't need any formatting/styling commands
LINK a_real_link
    TEMPLATE bigpipe
    NODES rtr1 rtr2
            </pre>
    </blockquote>
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.96</dt>
      <dd>Added template support
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_NODES">NODES</h3>
  <div class="definition">NODES

        <em class="meta">nodename{:compassoffset}</em>

        <em class="meta">nodename{:compassoffset}</em>
    </div>
  <div class="definition">NODES

        <em class="meta">nodename{:compassoffset}{percentage}</em>

        <em class="meta">nodename{:compassoffset}{percentage}</em>
    </div>
  <div class="definition">NODES

        <em class="meta">nodename{:xoffset:yoffset}</em>

        <em class="meta">nodename{:xoffset:yoffset}</em>
    </div>
  <div class="definition">NODES

        <em class="meta">nodename{:angle}r{radius}</em>

        <em class="meta">nodename{:angle}r{radius}</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">These are the <a href="#NODE_NODE">NODEs</a> that this link joins. There can be only two. They are the 
 'nodename's from the <a href="#NODE_NODE">NODE</a> line for each node. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Optionally, you can add an offset after a nodename, to move the location of 
 that end of the link. This can help with crowded areas of the map, and also in 
 making parallel links. Valid offsets can be numeric values, to indicate the 
 relative position in pixels from the centre of the node, or are named after 
 compass-points: N,S,E,W,NE,NE,SE,SW. The compass points describe locations 
 around the edge of the box that contains the node. You can also specify a 
 percentage after the compass point, to be a certain proportion of the way from 
 the centre. The percentage must be two digits. Finally, you can also use polar 
 coordinates to specify offsets, with an angle in degrees and a radius in pixels 
 from the centre point. 0 degrees is straight up. 
 </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The order of the nodes is significant. When reading data sources, the flow 
 from the first node to the second is considered 'out' and from second-to-first 
 is 'in'. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"> 
 <img src="../images/compass-points.png"/> 

 <br/>The compass points, relative to the node's bounding-box.. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"> 
 <img src="../images/linkoffset-defaults.png"/> 

 <br/>The default - node centre to node centre. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"> 
 <img src="../images/linkoffset-eastwest.png"/> 

 <br/>Using compass points - <tt><a href="#LINK_NODES">NODES</a> node1:E node2:W</tt> </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"> 
 <img src="../images/linkoffset-east50west50.png"/> 

 <br/>Using compass points with percentages - 
 <tt><a href="#LINK_NODES">NODES</a> node1:E50 node2:W50</tt> </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"> 
 <img src="../images/linkoffset-radial.png"/> 

 <br/>Using polar offsets - <tt><a href="#LINK_NODES">NODES</a> node1:45r20 node2:225r20</tt> </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"> 
 <img src="../images/linkoffset-parallel.png"/> 

 <br/>Using offsets to make parallel links- 
 <tt><a href="#LINK_NODES">NODES</a> node1:NE50 node2:NW50</tt> for one link and 
 <tt><a href="#LINK_NODES">NODES</a> node1:SE50 node2:SW50</tt> for the other. </p> 
  </div>
  <div class="examples">
    <h4>Examples</h4>
    <blockquote class="example">
      <small>
        <cite>Defining a simple link</cite>
      </small>
      <pre>LINK mylink
    NODES node1 node2
            </pre>
    </blockquote>
    <blockquote class="example">
      <small>
        <cite>Two parallel links, using offsets</cite>
      </small>
      <pre>LINK firstlink
    NODES node1:E node2:E

LINK secondlink
    NODES node1:W node2:W
            </pre>
    </blockquote>
    <blockquote class="example">
      <small>
        <cite>Two parallel links, using percentage compass offsets to bring
        the links closer together</cite>
      </small>
      <pre>LINK firstlink NODES node1:E50 node2:E50

LINK secondlink
    NODES node1:W50 node2:W50
            </pre>
    </blockquote>
    <blockquote class="example">
      <small>
        <cite>An offset link using pixel offsets</cite>
      </small>
      <pre>LINK firstlink
    NODES node1:-10:10 node2:20:12
            </pre>
    </blockquote>
    <blockquote class="example">
      <small>
        <cite>An offset link using polar coordinates</cite>
      </small>
      <pre>LINK firstlink
    NODES node1:45r20 node2:225r20
            </pre>
    </blockquote>
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.96</dt>
      <dd>Added polar offsets.
        </dd>
      <dt>0.96</dt>
      <dd>Added fractional compass offsets.
        </dd>
      <dt>0.9</dt>
      <dd>Added numeric pixel offsets.
        </dd>
      <dt>0.8</dt>
      <dd>Added ability to specify node offset.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_TARGET">TARGET</h3>
  <div class="definition">TARGET

        <em class="meta">targetspec</em>
    </div>
  <div class="definition">TARGET

        <em class="meta">targetspec</em>

        <em class="meta">targetspec</em>
    </div>
  <div class="definition">TARGET

        <em class="meta">targetspec</em>

        -

        <em class="meta">targetspec</em>
    </div>
  <div class="definition">TARGET

        <em class="meta">targetspec</em>

        n*

        <em class="meta">targetspec</em>
    </div>
  <div class="definition">TARGET "<em class="meta">targetspec</em>"
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies where to look for the current throughput information for this <a href="#LINK_LINK">LINK.</a> 
 You can also specify multiple targets, which will then be added together to make 
 the aggregate bandwidth which is then displayed. Specify the targets on one 
 <a href="#LINK_TARGET">TARGET</a> line, seperated with a space. If a targetspec starts with a '-', then 
 it's value will be <i>subtracted</i> from the final result instead. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Also, if a targetspec starts with a number, then a *, then it's used a 
 scaling factor on the result. You can do basic maths with this, especially if 
 you remember that multiplying by a number below 1 is the same as dividing by 1 
 divided by that number (0.5* is the same as divide by 2). </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">For both the '-' and '*' options, 
 <em>there must be no spaces</em> between any modifiers and the actual target 
 string after it. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The actual contents of the 
 <em>targetspec</em> depend on the data source plugins that are available. 
 <a href="targets.html">The standard plugins are documented here</a>. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">To help with filenames that can contain spaces, or for the external script 
 data source, you can enclose the entire targetspec in double-quotes (") to show 
 that it is a single targetspec. 
 <em>The quotes must be around the whole targetspec, including any 
 prefixes.</em> </p> 
  </div>
  <div class="examples">
    <h4>Examples</h4>
    <blockquote class="example">
      <small>
        <cite>Using multiple data sources for one link</cite>
      </small>
      <pre>TARGET link1a.rrd link1b.rrd
            </pre>
    </blockquote>
    <blockquote class="example">
      <small>
        <cite>Taking the input from one file, and output from
        another</cite>
      </small>
      <pre>TARGET poot.rrd:-:DS1 poot2.rrd:DS0:-
            </pre>
    </blockquote>
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.96</dt>
      <dd>Added quotes option for targets with spaces.
        </dd>
      <dt>0.96</dt>
      <dd>Added scale factors for all datasources.
        </dd>
      <dt>0.91</dt>
      <dd>Added 'negative' datasources.
        </dd>
      <dt>0.9</dt>
      <dd>Added plugin data sources, node targets, and added new
        plugins.
        </dd>
      <dt>0.8</dt>
      <dd>Added ability to specify multiple targets. Added
        tab-delimited data source. Added 'ignore' DS name.
        </dd>
      <dt>0.5</dt>
      <dd>Added ability to specify DS names.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_USESCALE">USESCALE</h3>
  <div class="definition">USESCALE

        <em class="meta">scalename</em>
    </div>
  <div class="definition">USESCALE

        <em class="meta">scalename</em>

        percent
    </div>
  <div class="definition">USESCALE

        <em class="meta">scalename</em>

        absolute
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specify which <a href="#GLOBAL_SCALE">SCALE</a> to use to decide the colour of this link. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">After the percentage usage is calculated (using <a href="#LINK_BANDWIDTH">BANDWIDTH</a> and data from the 
 <a href="#LINK_TARGET">TARGET</a> line), the colour is decided by looking up the percentage against this 
 <a href="#GLOBAL_SCALE">SCALE.</a> If there is no <a href="#LINK_USESCALE">USESCALE</a> line, then the default scale is used. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">If you also specify 'absolute', then no percentage calculation is performed. 
 The raw values from the <a href="#LINK_TARGET">TARGET</a> line are just looked up in the named <a href="#GLOBAL_SCALE">SCALE.</a> </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.97</dt>
      <dd>Added absolute scale support.
        </dd>
      <dt>0.9</dt>
      <dd>Added named scales and USESCALE.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_WIDTH">WIDTH</h3>
  <div class="definition">WIDTH

        <em class="meta">width</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies the width of this link when drawn. The 
 <em>width</em> value can be any positive number (including non-integers). </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The actual width of the final link arrow will be 
 <em>roughly</em> 2*width+1 pixels, due to the way links are drawn, and rounding 
 errors as the internal floating-point values are finally forced onto an integer 
 pixel-grid. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"> 
 What it actually does is calculate a centre-line (or spine), divide that up into 
 so many segments, and then at each point along the spine, find the normal (90 
 degrees to the direction of the line). Then step <a href="#LINK_WIDTH">WIDTH</a> pixels along the normal 
 in each direction to get a point to draw. This apparently-complex scheme is 
 required to allow for <a href="#LINK_VIA">VIAs</a> 
 - both angled and curved links have special handling, and regular straight links 
 are treated as curved links with no <a href="#LINK_VIA">VIA</a> by default. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.97a</dt>
      <dd>Added non-integer widths. Retconned explanation.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_BANDWIDTH">BANDWIDTH</h3>
  <div class="definition">BANDWIDTH

        <em class="meta">max-bandwidth</em>
    </div>
  <div class="definition">BANDWIDTH

        <em class="meta">max-in-bandwidth</em>

        <em class="meta">max-out-bandwidth</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies the maximum throughput of this link, in bits per second. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">This is used to calculate the percentage utilisation, which in turn is used 
 to make the colour for the link arrow, and optionally the label on the link. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The second form allows you to have 'asymmetric' links, like an ADSL, where 
 the first number is the maximum bandwidth from node1 to node2 and the second is 
 the maximum from node2 to node1, as they are given in the <a href="#LINK_NODES">NODES</a> line. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Bandwidths can also use K,M,G and T suffixes to specify large values. Also 
 see the <a href="#GLOBAL_KILO">KILO</a> global option though. </p> 
  </div>
  <div class="examples">
    <h4>Examples</h4>
    <blockquote class="example">
      <small>
        <cite>A typical ADSL line (as seen from the CPE)</cite>
      </small>
      <pre>BANDWIDTH 2M 256K
            </pre>
    </blockquote>
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.9</dt>
      <dd>Added MAXVALUE as a synonym to match NODE MAXVALUE. No
        change in functionality.
        </dd>
      <dt>0.5</dt>
      <dd>Added support for decimals in BANDWIDTH specifications.
        </dd>
      <dt>0.4</dt>
      <dd>Added support for K,M,G,T suffixes on bandwidth specs.
        Changed bandwidth from bytes to bits.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_DUPLEX">DUPLEX</h3>
  <div class="definition">DUPLEX

        <em class="meta">full</em>
    </div>
  <div class="definition">DUPLEX

        <em class="meta">half</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">'half' indicates that the bandwidth specified by the <a href="#LINK_BANDWIDTH">BANDWIDTH</a> keyword is 
 half-duplex rather than the default full. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">This means that the percentages calculated are calculated as (in+out)/max 
 instead of (in/max) and (out/max) separately. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.95</dt>
      <dd>Added DUPLEX.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_BWLABEL">BWLABEL</h3>
  <div class="definition">BWLABEL

        <em class="meta">formatname</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies the type of 'bandwidth' label shown on each link. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The default is 'percent', but you can also have 'none', 'unformatted' or 
 'bits'. 
 'bits' shows the actual bandwidth, formatted using K,M,T,G suffixes where 
 appropriate. 'unformatted' takes the value from the <a href="#LINK_TARGET">TARGET</a> and displays it 
 without any formatting 
 - this can be useful for mapping things other than bandwidth. 'none' hides the 
 bandwidth label altogether. 
 </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.8</dt>
      <dd>Added unformatted format.
        </dd>
      <dt>0.7</dt>
      <dd>Changed from global BWLABELS to per-link BWLABEL.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_INBWFORMAT">INBWFORMAT</h3>
  <div class="definition">INBWFORMAT

        <em class="meta">string</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies a custom string to use for the inbound data <a href="#LINK_BWLABEL">BWLABEL</a> bandwidth 
 labels. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">This command is intended as an advanced alternative to the <a href="#LINK_BWLABEL">BWLABEL</a> command, 
 for situations where you want more control over the content of the labels. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Instead of using bits, percent, etc, you can use any string in the label. 
 Most importantly, the text for the label can contain 
 <a href="advanced.html#tokens">special tokens</a> to show map data. In most 
 normal situations you 
 <em>need</em> to use the tokens, or the label won't do much useful </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">There is also a matching <a href="#LINK_OUTBWFORMAT">OUTBWFORMAT</a> command to do the ame job for the 
 outbound bandwidth label. </p> 
  </div>
  <div class="examples">
    <h4>Examples</h4>
    <blockquote class="example">
      <small>
        <cite>Providing more information in the bwlabel</cite>
      </small>
      <pre>INBWFORMAT {link:this:inpercent}% of {link:this:max_bandwidth_in:%k}b/sec
            </pre>
    </blockquote>
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.94</dt>
      <dd>Added INBWFORMAT.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_OUTBWFORMAT">OUTBWFORMAT</h3>
  <div class="definition">OUTBWFORMAT

        <em class="meta">string</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies a custom string to use for the outbound data <a href="#LINK_BWLABEL">BWLABEL</a> bandwidth 
 labels. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">See the <a href="#LINK_INBWFORMAT">INBWFORMAT</a> entry for more information. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.94</dt>
      <dd>Added OUTBWFORMAT.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_BWSTYLE">BWSTYLE</h3>
  <div class="definition">BWSTYLE

        <em class="meta">formatname</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies the style used to draw the box around the 'bandwidth' label shown 
 on each link. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Two styles are currently available: 'classic' leaves the box horizontal, 
 regardless of the direction of the link. 'angled' rotates the box to follow the 
 directiong of the link arrow. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The <a href="#LINK_BWFONT">BWFONT</a> 
 <em>must be a TrueType font</em> as these are the only font type to support 
 rotating text. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">To change the contents of the label, use <a href="#LINK_BWLABEL">BWLABEL</a> or INBWFORMAT/OUTBWFORMAT. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"> 
 <img src="../images/bwstyle-classic.png"/> Classic label style </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"> 
 <img src="../images/bwstyle-angled.png"/> Angled label style </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.92</dt>
      <dd>Added BWSTYLE.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_BWLABELPOS">BWLABELPOS</h3>
  <div class="definition">BWLABELPOS

        <em class="meta">inposition</em>

        <em class="meta">outposition</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies the position of the 'bandwidth' labels shown on each link. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The two position values are percentages along the link arrow, from the first 
 to the second node. Therefore <tt><a href="#LINK_BWLABELPOS">BWLABELPOS</a> 75 25</tt> is the default. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Weathermap will produce a warning if the outpostion is greater than the 
 inposition. In most cases, you have probably made a mistake, but if you have a 
 good reason to do this, then you can disable that warning using <a href="#LINK_SET">'SET</a> 
 nowarn_bwlabelpos 1' in the top section of you map config file. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.96</dt>
      <dd>Added warning for proably-wrong positions
        </dd>
      <dt>0.9</dt>
      <dd>Added BWLABELPOS
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_BWFONT">BWFONT</h3>
  <div class="definition">BWFONT

        <em class="meta">fontnumber</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specify the font used for drawing the <a href="#LINK_BWLABEL">BWLABEL</a> boxes. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Fonts are specified by number. The GD library that Weathermap uses has 5 
 built-in fonts, 1-5. You can define new fonts based on TrueType or GD fonts by 
 using the <a href="#GLOBAL_FONTDEFINE">FONTDEFINE</a> directive. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"> 
 <img src="../images/font-sizes.png"/>The built-in GD fonts. </p> 
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_INCOMMENT">INCOMMENT</h3>
  <div class="definition">INCOMMENT

        <em class="meta">string</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies a comment for the input side of a <a href="#LINK_LINK">LINK.</a> </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The link comment appears as text running alongside the link arrow. The font 
 used is governed by <a href="#LINK_COMMENTFONT">COMMENTFONT</a> and the colour by <a href="#LINK_COLORS">COMMENTFONTCOLOR.</a> </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The text for the comment can contain 
 <a href="advanced.html#tokens">special tokens</a> to show map data. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The <a href="#LINK_COMMENTFONT">COMMENTFONT</a> 
 <em>must be a TrueType font</em> as these are the only font type to support 
 rotating text. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.9</dt>
      <dd>Added link comments
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_OUTCOMMENT">OUTCOMMENT</h3>
  <div class="definition">OUTCOMMENT

        <em class="meta">string</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies a comment for the output side of a <a href="#LINK_LINK">LINK.</a> </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The link comment appears as text running alongside the link arrow. The font 
 used is governed by <a href="#LINK_COMMENTFONT">COMMENTFONT</a> and the colour by <a href="#LINK_COLORS">COMMENTFONTCOLOR.</a> </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The text for the comment can contain 
 <a href="advanced.html#tokens">special tokens</a> to show map data. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The <a href="#LINK_COMMENTFONT">COMMENTFONT</a> 
 <em>must be a TrueType font</em> as these are the only font type to support 
 rotating text. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.9</dt>
      <dd>Added link comments
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_COMMENTFONT">COMMENTFONT</h3>
  <div class="definition">COMMENTFONT

        <em class="meta">fontnumber</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specify the font used for drawing the <a href="#LINK_INCOMMENT">INCOMMENT</a> and <a href="#LINK_OUTCOMMENT">OUTCOMMENT</a> text. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Fonts are specified by number. The GD library that Weathermap uses has 5 
 built-in fonts, 1-5. You can define new fonts based on TrueType or GD fonts by 
 using the <a href="#GLOBAL_FONTDEFINE">FONTDEFINE</a> directive. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">For Link Comments, you 
 <em>must</em> define a TrueType font. These are the only font that can rotate 
 text through any angle, as required by comments. You can change the colour used 
 to render the font with <a href="#LINK_COLORS">COMMENTFONTCOLOR</a> </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.9</dt>
      <dd>Added link comments
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_COMMENTPOS">COMMENTPOS</h3>
  <div class="definition">COMMENTPOS

        <em class="meta">inposition</em>

        <em class="meta">outposition</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specify the position along the link used for drawing the <a href="#LINK_INCOMMENT">INCOMMENT</a> and 
 <a href="#LINK_OUTCOMMENT">OUTCOMMENT</a> text. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The positions are specified as a percentage position along the link, like 
 <a href="#LINK_BWLABELPOS">BWLABELPOS.</a> The default positions are equivalent to 
 <strong><a href="#LINK_COMMENTPOS">COMMENTPOS</a> 95 5</strong>. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.92</dt>
      <dd>Added COMMENTPOS
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_COMMENTSTYLE">COMMENTSTYLE</h3>
  <div class="definition">COMMENTSTYLE edge
    </div>
  <div class="definition">COMMENTSTYLE center
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specify whether link comments run along the outside edge of the link, or down 
 the centre of the link arrow. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"> 
 <img src="../images/commentstyle-edge.png"/> 'edge' comment style </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"> 
 <img src="../images/commentstyle-center.png"/> 'center' comment style </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.96</dt>
      <dd>Added COMMENTSTYLE
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_SPLITPOS">SPLITPOS</h3>
  <div class="definition">SPLITPOS

        <em class="meta">position</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specify the position of the 'split' between the in and out arrows in a link. 
 <em>position</em> is a percentage, and defaults to 50. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.95</dt>
      <dd>Added SPLITPOS
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_COLORS">*COLOR</h3>
  <div class="definition">OUTLINECOLOR

        <em class="meta">red</em>

        <em class="meta">green</em>

        <em class="meta">blue</em>
    </div>
  <div class="definition">BWOUTLINECOLOR

        <em class="meta">red</em>

        <em class="meta">green</em>

        <em class="meta">blue</em>
    </div>
  <div class="definition">BWFONTCOLOR

        <em class="meta">red</em>

        <em class="meta">green</em>

        <em class="meta">blue</em>
    </div>
  <div class="definition">BWBOXCOLOR

        <em class="meta">red</em>

        <em class="meta">green</em>

        <em class="meta">blue</em>
    </div>
  <div class="definition">COMMENTFONTCOLOR

        <em class="meta">red</em>

        <em class="meta">green</em>

        <em class="meta">blue</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specify the colours used for drawing the link. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">red, green and blue are numbers from 0 to 255. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"><a href="#LINK_COLORS">OUTLINECOLOR,</a> <a href="#LINK_COLORS">BWOUTLINECOLOR</a> and <a href="#LINK_COLORS">BWBOXCOLOR</a> have an additional option - 'none' 
 - which stops that element of the link being drawn. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"><a href="#LINK_COLORS">COMMENTFONTCOLOR</a> also has an extra option - 'contrast' 
 - which will choose black or white, depending on the colour of the link. This is 
 most useful with <a href="#LINK_COMMENTSTYLE">COMMENTSTYLE</a> center. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The colours are used as follows: </p> 
 <ul xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"> 
 <li><a href="#LINK_COLORS">OUTLINECOLOR</a> is the colour of the line around the edge of the arrow.</li> 

 <li><a href="#LINK_COLORS">BWOUTLINECOLOR</a> is the colour of the line surrounding the 'bandwidth 
 label' box</li> 

 <li><a href="#LINK_COLORS">BWBOXCOLOR</a> is the background colour for the same box</li> 

 <li><a href="#LINK_COLORS">BWFONTCOLOR</a> is the colour used for text within that box</li> 

 <li><a href="#LINK_COLORS">COMMENTFONTCOLOR</a> is the colour used for the text produced by <a href="#LINK_INCOMMENT">INCOMMENT</a> 
 and <a href="#LINK_OUTCOMMENT">OUTCOMMENT,</a> along the side of a link arrow</li> 
 </ul> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"> 
 <img src="../images/contrast.png"/> <tt><a href="#LINK_COLORS">COMMENTFONTCOLOR</a> contrast</tt> with 
 <tt><a href="#LINK_COMMENTSTYLE">COMMENTSTYLE</a> center</tt> </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.96</dt>
      <dd>Added COMMENTFONTCOLOR contrast.
        </dd>
      <dt>0.93</dt>
      <dd>Added correction
        - COMMENTCOLOR was shown instead of COMMENTFONTCOLOR.
        </dd>
      <dt>0.9</dt>
      <dd>Added COMMENTCOLOR.
        </dd>
      <dt>0.8</dt>
      <dd>Added OUTLINECOLOR, BWOUTLINECOLOR, BWFONTCOLOR and
        BWBOXCOLOR.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_INFOURL">INFOURL</h3>
  <div class="definition">INFOURL

        <em class="meta">url</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Creates a hyperlink in the HTML output. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">If you are using the HTML output facility, then a link is added to the 
 &lt;map&gt; section of the HTML so that when you click on the (weathermap) link, 
 you are taken to the url specified here. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">It is also possible to specify the <a href="#LINK_INFOURL">INFOURL</a> for the 'in' and 'out' halves of a 
 link individually, using <a href="#LINK_ININFOURL">ININFOURLand</a> <a href="#LINK_OUTINFOURL">OUTINFOURL.</a> </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.95</dt>
      <dd>Added IN/OUT support
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_ININFOURL">ININFOURL</h3>
  <div class="definition">ININFOURL

        <em class="meta">url</em>
    </div>
  <div class="definition">ININFOURL

        <em class="meta">url</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Same as <a href="#LINK_INFOURL">INFOURL,</a> but specifies a hyperlink for only the 'in' side of a 
 link. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.95</dt>
      <dd>Added IN/OUT support
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_OUTINFOURL">OUTINFOURL</h3>
  <div class="definition">OUTINFOURL

        <em class="meta">url</em>
    </div>
  <div class="definition">OUTINFOURL

        <em class="meta">url</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Same as <a href="#LINK_INFOURL">INFOURL,</a> but specifies a hyperlink for only the 'out' side of a 
 link. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.95</dt>
      <dd>Added IN/OUT support
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_OVERLIBGRAPH">OVERLIBGRAPH</h3>
  <div class="definition">OVERLIBGRAPH

        <em class="meta">url</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Creates a popup image in the HTML output. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">If you are using the HTML output facility, and <a href="#GLOBAL_HTMLSTYLE">HTMLSTYLE</a> is set to 'overlib', 
 then a link is added to the &lt;map&gt; section of the HTML so that when you 
 move the mouse pointer over the the (weathermap) link, a box will pop up 
 containing the image that you specify. Typically used to link to historical data 
 in your network monitoring system. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">You can also specify more than one url, in which case the images are 
 'stacked' one after another in the popup box. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">If a <a href="#LINK_NOTES">NOTES</a> line is also specified for a link, then the image specified here 
 appears with the <a href="#LINK_NOTES">NOTES</a> text underneath it. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">It is also possible to specify the <a href="#LINK_OVERLIBGRAPH">OVERLIBGRAPH</a> for the 'in' and 'out' halves 
 of a link individually, using <a href="#LINK_INOVERLIBGRAPH">INOVERLIBGRAPH</a> and <a href="#LINK_OUTOVERLIBGRAPH">OUTOVERLIBGRAPH.</a> </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">You can influence how Weathermap positions the popup box, using the 
 <a href="#LINK_OVERLIBWIDTH">OVERLIBWIDTH</a> and <a href="#LINK_OVERLIBHEIGHT">OVERLIBHEIGHT</a> keywords. </p> 
  </div>
  <div class="examples">
    <h4>Examples</h4>
    <blockquote class="example">
      <small>
        <cite>Typical use of OVERLIBGRAPH</cite>
      </small>
      <pre>OVERLIBGRAPH http://www.yoursite.net/mrtg/router1-link2-daily.png
            </pre>
    </blockquote>
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.95</dt>
      <dd>Added multiple URL support
        </dd>
      <dt>0.95</dt>
      <dd>Added IN/OUT support
        </dd>
      <dt>0.0pre</dt>
      <dd>Odd fact: This command, and the accompanying code to
        generate overlib imagemaps, were the first modification I ever made to the GRNET
        perl weathermap, and was what got me interested in writing my own version.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_INOVERLIBGRAPH">INOVERLIBGRAPH</h3>
  <div class="definition">INOVERLIBGRAPH

        <em class="meta">url</em>
    </div>
  <div class="definition">INOVERLIBGRAPH

        <em class="meta">url</em>

        <em class="meta">url</em>...
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Same as <a href="#LINK_OVERLIBGRAPH">OVERLIBGRAPH,</a> but specifies a pop-up graph for only the 'in' side of 
 a link. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.95</dt>
      <dd>Added multiple URL support
        </dd>
      <dt>0.95</dt>
      <dd>Added IN/OUT support
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_OUTOVERLIBGRAPH">OUTOVERLIBGRAPH</h3>
  <div class="definition">OUTOVERLIBGRAPH

        <em class="meta">url</em>
    </div>
  <div class="definition">OUTOVERLIBGRAPH

        <em class="meta">url</em>

        <em class="meta">url</em>...
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Same as <a href="#LINK_OVERLIBGRAPH">OVERLIBGRAPH,</a> but specifies a pop-up graph for only the 'out' side of 
 a link. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.95</dt>
      <dd>Added multiple URL support
        </dd>
      <dt>0.95</dt>
      <dd>Added IN/OUT support
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_OVERLIBWIDTH">OVERLIBWIDTH</h3>
  <div class="definition">OVERLIBWIDTH

        <em class="meta">imagewidth</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specify the width, in pixels of the graph image referred to by <a href="#LINK_OVERLIBGRAPH">OVERLIBGRAPH</a> 
 line. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">This is an optional extra that allows the OverLib library to make a better 
 job of positioning the 'popup' image so that it doesn't appear off the edge of 
 the screen. Typically, you would use this once, in the DEFAULT link. If you use 
 this, you must also use <a href="#LINK_OVERLIBHEIGHT">OVERLIBHEIGHT,</a> for either to have any effect. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.7</dt>
      <dd>Originally added OVERLIBWIDTH and OVERLIBHEIGHT based on
        code by Niels Baggesen.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_OVERLIBHEIGHT">OVERLIBHEIGHT</h3>
  <div class="definition">OVERLIBHEIGHT

        <em class="meta">imagewidth</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specify the width, in pixels of the graph image referred to by <a href="#LINK_OVERLIBGRAPH">OVERLIBGRAPH</a> 
 line. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">This is an optional extra that allows the OverLib library to make a better 
 job of positioning the 'popup' image so that it doesn't appear off the edge of 
 the screen. Typically, you would use this once, in the DEFAULT link. If you use 
 this, you must also use <a href="#LINK_OVERLIBWIDTH">OVERLIBWIDTH,</a> for either to have any effect. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.7</dt>
      <dd>Originally added OVERLIBWIDTH and OVERLIBHEIGHT based on
        code by Niels Baggesen.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_OVERLIBCAPTION">OVERLIBCAPTION</h3>
  <div class="definition">OVERLIBCAPTION

        <em class="meta">caption text</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specify the caption used for the popup HTML 'window' if you have also 
 specified an <a href="#LINK_OVERLIBGRAPH">OVERLIBGRAPH</a> line. By default, this is the name of the <a href="#LINK_LINK">LINK.</a> </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The text for the caption can contain 
 <a href="advanced.html#tokens">special tokens</a> to show map data. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.9</dt>
      <dd>Originally added OVERLIBCAPTION.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_INOVERLIBCAPTION">INOVERLIBCAPTION</h3>
  <div class="definition">INOVERLIBCAPTION

        <em class="meta">caption text</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Same as <a href="#LINK_OVERLIBCAPTION">OVERLIBCAPTION,</a> but specifies a pop-up graph caption for only the 
 'in' side of a link. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.95</dt>
      <dd>Added multiple URL support
        </dd>
      <dt>0.9</dt>
      <dd>Originally added OVERLIBCAPTION.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_OUTOVERLIBCAPTION">OUTOVERLIBCAPTION</h3>
  <div class="definition">OUTOVERLIBCAPTION

        <em class="meta">caption text</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Same as <a href="#LINK_OVERLIBCAPTION">OVERLIBCAPTION,</a> but specifies a pop-up graph caption for only the 
 'out' side of a link. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.95</dt>
      <dd>Added multiple URL support
        </dd>
      <dt>0.9</dt>
      <dd>Originally added OVERLIBCAPTION.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_NOTES">NOTES</h3>
  <div class="definition">NOTES

        <em class="meta">notes text</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies the text or HTML notes for a link. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The 
 <em>notes text</em> appears in a popup box when the user hovers their mouse over 
 the link. If an <a href="#LINK_OVERLIBGRAPH">OVERLIBGRAPH</a> is specified too, then the text appears below the 
 graph. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The map <a href="#GLOBAL_HTMLSTYLE">HTMLSTYLE</a> must be set to 'overlib' to enable any of the mouse-hover 
 functionality. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">It is also possible to specify the <a href="#LINK_NOTES">NOTES</a> for the 'in' and 'out' halves of a 
 link individually, using <a href="#LINK_INNOTES">INNOTES</a> and <a href="#LINK_OUTNOTES">OUTNOTES.</a> </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.95</dt>
      <dd>Added IN/OUT support
        </dd>
      <dt>0.9</dt>
      <dd>Originally added NOTES.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_INNOTES">INNOTES</h3>
  <div class="definition">INNOTES

        <em class="meta">url</em>
    </div>
  <div class="definition">INNOTES

        <em class="meta">notes text</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Same as <a href="#LINK_NOTES">NOTES,</a> but specifies a text box for only the 'in' side of a link. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.95</dt>
      <dd>Added IN/OUT support
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_OUTNOTES">OUTNOTES</h3>
  <div class="definition">OUTNOTES

        <em class="meta">url</em>
    </div>
  <div class="definition">OUTNOTES

        <em class="meta">notes text</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Same as <a href="#LINK_NOTES">NOTES,</a> but specifies a pop-up text box for only the 'out' side of a 
 link. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.95</dt>
      <dd>Added IN/OUT support
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_VIA">VIA</h3>
  <div class="definition">VIA

        <em class="meta">x-coord</em>

        <em class="meta">y-coord</em>
    </div>
  <div class="definition">VIA

        <em class="meta">nodename</em>

        <em class="meta">x-offset</em>

        <em class="meta">y-offset</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specify an additional point that a link must pass through. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">A link normally goes in a straight line between the two nodes listed in the 
 <a href="#LINK_NODES">NODES</a> configuration line. If you need it to go around something else, or to 
 seperate two parallel links so that the bandwidth labels are all visible, you 
 can make the link curve. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">If you specify multiple <a href="#LINK_VIA">VIA</a> lines, then the link will pass through each in 
 turn, in the order they are specified. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">You can choose between curved or angled links with <a href="#LINK_VIASTYLE">VIASTYLE.</a> </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Any <a href="#LINK_VIA">VIA</a> can also be specified relative to a <a href="#NODE_NODE">NODE</a> on the map. This makes it 
 easier to have curves keep the intended shape as you re-organise a map. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.96</dt>
      <dd>Added angled VIAs.
        </dd>
      <dt>0.95</dt>
      <dd>Added relative-positioned VIA.
        </dd>
      <dt>0.8</dt>
      <dd>Originally added VIA.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_VIASTYLE">VIASTYLE</h3>
  <div class="definition">VIASTYLE curved
    </div>
  <div class="definition">VIASTYLE angled
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">When using <a href="#LINK_VIA">VIA</a> to define a non-straight <a href="#LINK_LINK">LINK,</a> you can choose to have a curved 
 link, where the curve passes through each <a href="#LINK_VIA">VIA</a> point, or an angled link where 
 each <a href="#LINK_VIA">VIA</a> point is a 'corner'. The default is for a curved link. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"> 
 <img src="../images/viastyle-curved.png"/>Curved <a href="#LINK_VIA">VIA</a> </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"> 
 <img src="../images/viastyle-angled.png"/>Angled <a href="#LINK_VIA">VIA</a> </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.96</dt>
      <dd>Added 'angled' style, and VIASTYLE.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_LINKSTYLE">LINKSTYLE</h3>
  <div class="definition">LINKSTYLE oneway
    </div>
  <div class="definition">LINKSTYLE twoway
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies the whether the link should be drawn with one or two arrows. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">A 'standard' (twoway) link has two arrows 
 - one for inbound data and one for outbound data. In some situations (e.g. 
 round-trip latency), you might only want an arrow in one direction. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">One-way arrows only show the outbound <a href="#LINK_BWLABEL">BWLABEL,</a> but they show it in the 
 standard position 
 - 25% of the way along. Change the position of the label with <a href="#LINK_BWLABELPOS">BWLABELPOS</a> as 
 usual (you still need to specify 
 <em>two</em> positions in the <a href="#LINK_BWLABELPOS">BWLABELPOS</a> line, however). </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.94</dt>
      <dd>Added LINKSTYLE.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_ARROWSTYLE">ARROWSTYLE</h3>
  <div class="definition">ARROWSTYLE

        <em class="meta">stylename</em>
    </div>
  <div class="definition">ARROWSTYLE

        <em class="meta">width</em>

        <em class="meta">length</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies the style of arrowhead used for drawing links. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The default is 'classic' which has a wide arrowhead. You can also choose 
 'compact' which gives narrower heads. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Finally, you can get finer control by adjusting the size yourself. The width 
 and length of the head are in units of link-width. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Classic is equivalent to '4 2' and Compact is equivalent to '1 1'. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"> 
 <img src="../images/arrowstyle-classic.png"/> Classic arrow style </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"> 
 <img src="../images/arrowstyle-compact.png"/> Compact arrow style </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.8</dt>
      <dd>Added custom numeric form.
        </dd>
      <dt>0.7</dt>
      <dd>First added.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_SET">SET</h3>
  <div class="definition">SET

        <em class="meta">hintname</em>

        <em class="meta">hintvalue</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies a value for a <em>hint variable</em>. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Hint Variables allow the user to pass settings to the internals of Weathermap 
 that wouldn't normally need to be changed, or that aren't part of the core 
 Weathermap application. Examples are: small rendering changes, parameters for 
 datasources plugins and similar. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Hint Variables are either Global for the map, or assigned to a specific link 
 or node. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">There is more about Hint Variables in the 
 <a href="advanced.html">Advanced Topics</a> section. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.9</dt>
      <dd>Originally added SET.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="LINK_ZORDER">ZORDER</h3>
  <div class="definition">ZORDER

        <em class="meta">z-coord</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies the order in which to draw this item on the map. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">By default, all nodes are drawn above all links. There are some situations 
 where you might like to change this, for example if you use a <a href="#NODE_NODE">NODE</a> as a 
 background image, and you want links to show in front of that image. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">By default, all nodes have a Z coordinate of 600, and all links have 300. The 
 map legend is at 1000, which cannot be changed (you can move everything else 
 above it, if you like, of course). Items are drawn from lowest Z up to highest 
 Z, so if you want a particular node to appear underneath the default links, you 
 can use <a href="#LINK_ZORDER">'ZORDER</a> 250' to do that. 
 </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.96</dt>
      <dd>Added Z-ordering support.
        </dd>
    </dl>
  </div>
</div>
    

    <h2 id="s_scope_GLOBAL" class="configsection">Global Configuration Directives</h2>
        <div class="referenceentry">
  <h3 id="GLOBAL_BACKGROUND">BACKGROUND</h3>
  <div class="definition">BACKGROUND <em class="meta">imagefile</em></div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specify an PNG, JPEG or GIF image file to be used as a background image. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Any <a href="#GLOBAL_WIDTH">WIDTH</a> and <a href="#GLOBAL_HEIGHT">HEIGHT</a> specifications will be ignored 
 - the map will take the size of the background. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.9</dt>
      <dd>Added JPEG and GIF support for backgrounds.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="GLOBAL_WIDTH">WIDTH</h3>
  <div class="definition">WIDTH

        <em class="meta">map-width</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies the width of the map image in pixels. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">If a <a href="#GLOBAL_BACKGROUND">BACKGROUND</a> is specified, and the imagefile is successfully loaded, then 
 any <a href="#GLOBAL_WIDTH">WIDTH</a> specified is ignored. If neither a <a href="#GLOBAL_BACKGROUND">BACKGROUND</a> or <a href="#GLOBAL_WIDTH">WIDTH</a> is specified, 
 then the default <a href="#GLOBAL_WIDTH">WIDTH</a> is 800 pixels. </p> 
  </div>
</div>

        <div class="referenceentry">
  <h3 id="GLOBAL_HEIGHT">HEIGHT</h3>
  <div class="definition">HEIGHT

        <em class="meta">map-height</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies the height of the map image in pixels. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">If a <a href="#GLOBAL_BACKGROUND">BACKGROUND</a> is specified, and the imagefile is successfully loaded, then 
 any <a href="#GLOBAL_HEIGHT">HEIGHT</a> specified is ignored. If neither a <a href="#GLOBAL_BACKGROUND">BACKGROUND</a> or <a href="#GLOBAL_HEIGHT">HEIGHT</a> is specified, 
 then the default <a href="#GLOBAL_HEIGHT">HEIGHT</a> is 600 pixels. </p> 
  </div>
</div>

        <div class="referenceentry">
  <h3 id="GLOBAL_HTMLOUTPUTFILE">HTMLOUTPUTFILE</h3>
  <div class="definition">HTMLOUTPUTFILE

        <em class="meta">htmlfile</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">This specifies the name of the HTML file that will be generated. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The equivalent command-line option takes precedence over this configuration 
 line, if both are used. 
 </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.98</dt>
      <dd>This now creates a second copy of the file in the location specified, when using the
            Cacti plugin.
        </dd>
      <dt>0.7</dt>
      <dd>Added HTMLOUTPUTFILE.
        </dd>
    </dl>
  </div>
</div>
        <div class="referenceentry">
  <h3 id="GLOBAL_DATAOUTPUTFILE">DATAOUTPUTFILE</h3>
  <div class="definition">DATAOUTPUTFILE

        <em class="meta">datafile</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies a file to write a dump of all the data collected 
 during the rendering of the map. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The file is in a tab-separated file format suitable for reading using the 
 <a href="targets.html#wmdata">wmdata datasource plugin</a>, which allows one map to refer to data in another map. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">If you are using the Cacti plugin, this data is always saved in the output/ directory, in addition to anywhere you specify with this command. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.98</dt>
      <dd>Added DATAOUTPUTFILE
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="GLOBAL_HTMLSTYLESHEET">HTMLSTYLESHEET</h3>
  <div class="definition">HTMLSTYLESHEET

        <em class="meta">URL</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies a CSS stylesheet to reference, when generating HTML. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">When HTML output is enabled, this allows you to include your own CSS 
 stylesheet in the result, allowing for customisation of the output without 
 needing to use awk/perl/etc to modify the HTML. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">If you are generating maps using the Cacti plugin, then this directive is 
 ignored. </p> 
 <changes xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"> 
 <change version="0.96">Added <a href="#GLOBAL_HTMLSTYLESHEET">HTMLSTYLESHEET.</a> 
 </change> 
 </changes> 
  </div>
</div>

        <div class="referenceentry">
  <h3 id="GLOBAL_IMAGEOUTPUTFILE">IMAGEOUTPUTFILE</h3>
  <div class="definition">IMAGEOUTPUTFILE

        <em class="meta">imagefile</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">This specifies the name of the PNG, JPEG or GIF file that will be generated. 
 The format chosen is based on the file-extension. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The equivalent command-line option takes precedence over this configuration 
 line, if both are used. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.98</dt>
      <dd>This now creates a second copy of the file in the location specified, when using the Cacti plugin.</dd>
      <dt>0.9</dt>
      <dd>Added JPEG and GIF support.
        </dd>
      <dt>0.7</dt>
      <dd>Added IMAGEOUTPUTFILE.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="GLOBAL_IMAGEURI">IMAGEURI</h3>
  <div class="definition">IMAGEURI 
        <em class="meta">image-uri</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">If you are generating output files using <a href="#GLOBAL_HTMLOUTPUTFILE">HTMLOUTPUTFILE</a> and <a href="#GLOBAL_IMAGEOUTPUTFILE">IMAGEOUTPUTFILE</a> 
 that are in a different directory to the weathermap installation, then the HTML 
 will probably contain an incorrect IMG tag. This keyword allows you to replace 
 the IMG SRC attribute in the HTML output with a corrected one. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The equivalent command-line option (--image-uri) takes precedence over this 
 configuration line, if both are used. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">If you are generating maps using the Cacti plugin, then this directive is 
 ignored, as the filename and uri are calculated instead. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.96</dt>
      <dd>Added IMAGEURI.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="GLOBAL_FONTDEFINE">FONTDEFINE</h3>
  <div class="definition">FONTDEFINE

        <em class="meta">fontnumber</em>

        <em class="meta">gdfontfile</em>
    </div>
  <div class="definition">FONTDEFINE

        <em class="meta">fontnumber</em>

        <em class="meta">ttffontfile</em>

        <em class="meta">fontsize</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Defines a custom font to be used for text within the map. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">By default, the GD library used by Weathermap has 5 fonts, numbered 1-5. 
 <a href="#GLOBAL_FONTDEFINE">FONTDEFINE</a> allows you to define new font numbers, and link them to fonts in two 
 other formats. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The first format is 'GD fonts', which are a bitmapped format used by GD 
 alone. They are not scalable, and are also platform-specific (they use a 
 different byte-order depending on the host). You should specify the full 
 filename including any extensions. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The second format is TrueType fonts, which are scalable, standard and 
 generally a lot nicer! This time, you need to specify the size that the font 
 should be rendered at. The size is in pixels. You can load the same font into 
 multiple fontnumbers with different sizes to use in different parts of a map. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The freetype library used in PHP makes a somewhat complex set of rules for 
 where it will search for truetype fonts. The two easiest options are: 

 <ul><li>Use the full absolute path to your .ttf file</li> 
 <li>Keep your .ttf files in the Weathermap directory, and use the first part 
 of the filename only, with no '.ttf' on the end.</li> 
 </ul> 

 <p><em>If you have a font in the Weathermap directory as above and it's not loading, also 
 try: ./FontName.ttf</em> </p> 

 The full set of rules is 
 <a href="http://www.boutell.com/gd/manual2.0.33.html#gdImageStringFT">available 
 here</a> </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Regardless of the format, the newly defined font can be used anywhere that 
 you'd normally use a font number (for example, <a href="#LINK_BWFONT">BWFONT</a> or <a href="#GLOBAL_FONT">KEYFONT).</a> 
 </p> 
  </div>
  <div class="examples">
    <h4>Examples</h4>
    <blockquote class="example">
      <small>
        <cite>Defining a new Truetype font, with the font file in the
        weathermap directory</cite>
      </small>
      <pre>FONTDEFINE 10 VeraBd 16
            </pre>
    </blockquote>
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.8</dt>
      <dd>First added FONTDEFINE
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="GLOBAL_FONT">*FONT</h3>
  <div class="definition">TITLEFONT

        <em class="meta">fontnumber</em>
    </div>
  <div class="definition">KEYFONT

        <em class="meta">fontnumber</em>
    </div>
  <div class="definition">TIMEFONT

        <em class="meta">fontnumber</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specify the fonts used for various text. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Fonts are specified by number. The GD library that Weathermap uses has 5 
 built-in fonts, 1-5. You can define new fonts based on TrueType or GD fonts by 
 using the <a href="#GLOBAL_FONTDEFINE">FONTDEFINE</a> directive. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"> 
 <img src="../images/font-sizes.png"/>The built-in GD fonts. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.8</dt>
      <dd>Originally added TITLEFONT.
        </dd>
      <dt>0.7</dt>
      <dd>Originally added TIMEFONT.
        </dd>
      <dt>0.6</dt>
      <dd>Originally added KEYFONT.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="GLOBAL_COLORS">*COLOR</h3>
  <div class="definition">BGCOLOR        <em class="meta">red</em>         <em class="meta">green</em>        <em class="meta">blue</em>     </div>
  <div class="definition">TIMECOLOR

        <em class="meta">red</em>

        <em class="meta">green</em>

        <em class="meta">blue</em>
    </div>
  <div class="definition">TITLECOLOR

        <em class="meta">red</em>

        <em class="meta">green</em>

        <em class="meta">blue</em>
    </div>
  <div class="definition">KEYTEXTCOLOR

        <em class="meta">red</em>

        <em class="meta">green</em>

        <em class="meta">blue</em>
    </div>
  <div class="definition">KEYOUTLINECOLOR <em class="meta">red</em> <em class="meta">green</em> <em class="meta">blue</em> </div>
  <div class="definition">KEYOUTLINECOLOR none
    </div>
  <div class="definition">KEYBGCOLOR

        <em class="meta">red</em>

        <em class="meta">green</em>

        <em class="meta">blue</em>
    </div>
  <div class="definition">KEYBGCOLOR none
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specify the colours used for drawing the global elements of the map. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">red, green and blue are numbers from 0 to 255. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">You can also choose not to draw either the outline or background rectangle for 
 keys by specifying 'none' for those. 
 </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.98</dt>
      <dd>Added 'none' option for KEYBGCOLOR and KEYOUTLINECOLOR</dd>
      <dt>0.8</dt>
      <dd>Added TIMECOLOR, TITLECOLOR, KEYTEXTCOLOR,
            KEYOUTLINECOLOR and KEYBGCOLOR.
        </dd>
      <dt>0.7</dt>
      <dd>Added BGCOLOR.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="GLOBAL_TIMEPOS">TIMEPOS</h3>
  <div class="definition">TIMEPOS

        <em class="meta">x-pos</em>

        <em class="meta">y-pos</em>
    </div>
  <div class="definition">TIMEPOS

        <em class="meta">x-pos</em>

        <em class="meta">y-pos</em>

        <em class="meta">formatstring</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies where to draw the timestamp on the map. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">If no <a href="#GLOBAL_TIMEPOS">TIMEPOS</a> line is given, then the timestamp is drawn in the top-right 
 corner. To hide it completely, set y to be <nobr>-200</nobr> or so. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">You can optionally specify an additional parameter to change the text of the 
 timestamp. This text can contain special tokens which are substituted with parts 
 of the current time. The default timestamp text is 
 <nobr><tt>Created: %b %d %Y %H:%M:%S</tt></nobr>. The tokens used are those 
 accepted by the PHP strftime function. For a full list see the 
 <a href="http://www.php.net/manual/en/function.strftime.php">PHP manual 
 page</a>. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">You can control the font used to draw the timestamp with <a href="#GLOBAL_FONT">TIMEFONT,</a> and the 
 colour that it is drawn in, using <a href="#GLOBAL_COLORS">TIMECOLOR.</a> </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.6</dt>
      <dd>Added ability to change text.
        </dd>
      <dt>0.5</dt>
      <dd>Originally added TIMEPOS
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="GLOBAL_MINTIMEPOS">MINTIMEPOS</h3>
  <div class="definition">MINTIMEPOS

        <em class="meta">x-pos</em>

        <em class="meta">y-pos</em>
    </div>
  <div class="definition">MINTIMEPOS

        <em class="meta">x-pos</em>

        <em class="meta">y-pos</em>

        <em class="meta">formatstring</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies where to draw the 'oldest data' timestamp on the map. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The data collection part of weathermap collects a timestamp for each piece of 
 data, alongside the actual data. This is intended to signify the time that the 
 data was actually valid 
 - it would be the file modification date if it was a text file, or the current 
 time if it was a live SNMP query. The minimum and maximum of these times are 
 collated, and can be shown on the map with <a href="#GLOBAL_MINTIMEPOS">MINTIMEPOS</a> and <a href="#GLOBAL_MAXTIMEPOS">MAXTIMEPOS.</a> </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">If no <a href="#GLOBAL_MINTIMEPOS">MINTIMEPOS</a> line is given, then the timestamp is not drawn. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">You can optionally specify an additional parameter to change the text of the 
 timestamp. This text can contain special tokens which are substituted with parts 
 of the current time. The default timestamp text is 
 <nobr><tt>Oldest Data: %b %d %Y %H:%M:%S</tt></nobr>. The tokens used are those 
 accepted by the PHP strftime function. For a full list see the 
 <a href="http://www.php.net/manual/en/function.strftime.php">PHP manual 
 page</a>. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">You can control the font used to draw the timestamp with <a href="#GLOBAL_FONT">TIMEFONT,</a> and the 
 colour that it is drawn in, using <a href="#GLOBAL_COLORS">TIMECOLOR.</a> </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.96</dt>
      <dd>Originally added MINTIMEPOS
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="GLOBAL_MAXTIMEPOS">MAXTIMEPOS</h3>
  <div class="definition">MAXTIMEPOS

        <em class="meta">x-pos</em>

        <em class="meta">y-pos</em>
    </div>
  <div class="definition">MAXTIMEPOS

        <em class="meta">x-pos</em>

        <em class="meta">y-pos</em>

        <em class="meta">formatstring</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies where to draw the 'newest data' timestamp on the map. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The data collection part of weathermap collects a timestamp for each piece of 
 data, alongside the actual data. This is intended to signify the time that the 
 data was actually valid 
 - it would be the file modification date if it was a text file, or the current 
 time if it was a live SNMP query. The minimum and maximum of these times are 
 collated, and can be shown on the map with <a href="#GLOBAL_MINTIMEPOS">MINTIMEPOS</a> and <a href="#GLOBAL_MAXTIMEPOS">MAXTIMEPOS.</a> </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">If no <a href="#GLOBAL_MAXTIMEPOS">MAXTIMEPOS</a> line is given, then the timestamp is not drawn. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">You can optionally specify an additional parameter to change the text of the 
 timestamp. This text can contain special tokens which are substituted with parts 
 of the current time. The default timestamp text is 
 <nobr><tt>Newest Data: %b %d %Y %H:%M:%S</tt></nobr>. The tokens used are those 
 accepted by the PHP strftime function. For a full list see the 
 <a href="http://www.php.net/manual/en/function.strftime.php">PHP manual 
 page</a>. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">You can control the font used to draw the timestamp with <a href="#GLOBAL_FONT">TIMEFONT,</a> and the 
 colour that it is drawn in, using <a href="#GLOBAL_COLORS">TIMECOLOR.</a> </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.96</dt>
      <dd>Originally added MAXTIMEPOS
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="GLOBAL_TITLE">TITLE</h3>
  <div class="definition">TITLE

        <em class="meta">titlestring</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies the title text. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">The <a href="#GLOBAL_TITLE">TITLE</a> is shown in file-selectors for both the editor and the Cacti 
 plugin. If you'd like the title to be shown on the map too, then add <a href="#GLOBAL_TITLEPOS">TITLEPOS</a> 
 line also. </p> 
  </div>
</div>

        <div class="referenceentry">
  <h3 id="GLOBAL_TITLEPOS">TITLEPOS</h3>
  <div class="definition">TITLEPOS

        <em class="meta">x-pos</em>

        <em class="meta">y-pos</em>
    </div>
  <div class="definition">TITLEPOS

        <em class="meta">x-pos</em>

        <em class="meta">y-pos</em>

        <em class="meta">headingstring</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies the position of the title text. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">If no <a href="#GLOBAL_TITLEPOS">TITLEPOS</a> line is given, then no title is drawn. You can optionally 
 specify an additional parameter, to change the title. Any text after the second 
 coordinate is taken as a new <a href="#GLOBAL_TITLE">TITLE.</a> </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">You can control the font used to draw the title with <a href="#GLOBAL_FONT">TITLEFONT,</a> and the 
 colour that it is drawn in, using <a href="#GLOBAL_COLORS">TITLECOLOR.</a> </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.8</dt>
      <dd>Originally added TITLEPOS.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="GLOBAL_KILO">KILO</h3>
  <div class="definition">KILO

        <em class="meta">number</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies base value for kilo, mega and giga abbreviations. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Both <a href="#LINK_BANDWIDTH">BANDWIDTH</a> and <a href="#LINK_BWLABEL">BWLABEL</a> can use K,M,G,T as abbreviations for thousands, 
 millions and so on. You can define what the multiple used is. The default is 
 1000. </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.4</dt>
      <dd>Originally added KILO.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="GLOBAL_HTMLSTYLE">HTMLSTYLE</h3>
  <div class="definition">HTMLSTYLE

        <em class="meta">formatname</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies the HTML output style. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">When HTML output is enabled, there are two variations, which you can choose 
 between. 'static' is a basic HTML page with client-side imagemap, but no 'pop 
 up' graphs. 'overlib' adds the use of the OverLib library to the page, so that 
 pop up graphs can work, too. This requires Javascript, which is why 'static' is 
 the default. </p> 
  </div>
</div>

        <div class="referenceentry">
  <h3 id="GLOBAL_SCALE">SCALE</h3>
  <div class="definition">SCALE

        <em class="meta">min</em>

        <em class="meta">max</em>

        <em class="meta">red</em>

        <em class="meta">green</em>

        <em class="meta">blue</em>
    </div>
  <div class="definition">SCALE

        <em class="meta">scalename</em>

        <em class="meta">min</em>

        <em class="meta">max</em>

        <em class="meta">red</em>

        <em class="meta">green</em>

        <em class="meta">blue</em>
    </div>
  <div class="definition">SCALE

        <em class="meta">min</em>

        <em class="meta">max</em>

        <em class="meta">red</em>

        <em class="meta">green</em>

        <em class="meta">blue</em>

        <em class="meta">red2</em>

        <em class="meta">green2</em>

        <em class="meta">blue2</em>
    </div>
  <div class="definition">SCALE

        <em class="meta">scalename</em>

        <em class="meta">min</em>

        <em class="meta">max</em>

        <em class="meta">red</em>

        <em class="meta">green</em>

        <em class="meta">blue</em>

        <em class="meta">red2</em>

        <em class="meta">green2</em>

        <em class="meta">blue2</em>
    </div>
  <div class="definition">SCALE

        <em class="meta">min</em>

        <em class="meta">max</em>

        <em class="meta">red</em>

        <em class="meta">green</em>

        <em class="meta">blue</em>

        <em class="meta">tagtext</em>
    </div>
  <div class="definition">SCALE

        <em class="meta">scalename</em>

        <em class="meta">min</em>

        <em class="meta">max</em>

        <em class="meta">red</em>

        <em class="meta">green</em>

        <em class="meta">blue</em>

        <em class="meta">tagtext</em>
    </div>
  <div class="definition">SCALE

        <em class="meta">min</em>

        <em class="meta">max</em>

        <em class="meta">red</em>

        <em class="meta">green</em>

        <em class="meta">blue</em>

        <em class="meta">red2</em>

        <em class="meta">green2</em>

        <em class="meta">blue2</em>

        <em class="meta">tagtext</em>
    </div>
  <div class="definition">SCALE

        <em class="meta">scalename</em>

        <em class="meta">min</em>

        <em class="meta">max</em>

        <em class="meta">red</em>

        <em class="meta">green</em>

        <em class="meta">blue</em>

        <em class="meta">red2</em>

        <em class="meta">green2</em>

        <em class="meta">blue2</em>

        <em class="meta">tagtext</em>
    </div>
  <div class="definition">SCALE

        <em class="meta">min</em>

        <em class="meta">max</em>

        none
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Defines one 'span' within the link colour-coding table. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"><a href="#GLOBAL_SCALE">SCALE</a> is used to specify how <a href="#NODE_NODE">NODEs</a> and <a href="#LINK_LINK">LINKs</a> are colour-coded according to 
 their percent usage. If the percentage usage falls between min and max then the 
 colour specified by red, green and blue is used to colour the link. Colour 
 values are between 0 and 255. Percentages are between 0 and 100, obviously. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">In combination with the 'absolute' option of <a href="#LINK_USESCALE">USESCALE,</a> you can also use raw 
 data from <a href="#LINK_TARGET">TARGET</a> lines. Just use the absolute values for min and max in <a href="#GLOBAL_SCALE">SCALE</a> 
 lines. In this format, min and max can use the same abbreviations for mega, giga 
 etc as <a href="#LINK_BANDWIDTH">BANDWIDTH</a> and <a href="#NODE_MAXVALUE">MAXVALUE</a> can. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">If you specify 
 <i>two</i> colours on the line (the third and fourth forms above), then the 
 colour chosen for the link will be calculated as a proportion between the two 
 colours. You can avoid specifying many <a href="#GLOBAL_SCALE">SCALE</a> lines this way. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">If you specify the word 'none' instead of a colour, then a transparent colour 
 is used for that range of values. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Without a <i>scalename</i>, 
 the <a href="#GLOBAL_SCALE">SCALE</a> line will add a definition to the scale named 'DEFAULT'. If you define 
 any other named scales, you can then use the <a href="#LINK_USESCALE">USESCALE</a> directive to specify that 
 a particular <a href="#NODE_NODE">NODE</a> or <a href="#LINK_LINK">LINK</a> use your new scale. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">With any of the formats, you can specify a 'tag' on the end of the line. When 
 the colour is decided for the 'in' and 'out' parts of a <a href="#NODE_NODE">NODE</a> or <a href="#LINK_LINK">LINK,</a> then a 
 special <a href="#GLOBAL_SET">SET</a> variable is defined called {node:this:inscaletag} (or outscaletag, 
 or link:this...) which contains the tagtext from the <a href="#GLOBAL_SCALE">SCALE</a> line that matched. 
 You can use this to do things like choose an icon, or change the label of a node 
 or link based on a percentage. There are examples of this (and most other 
 node-related formatting things) in the suite-1.conf map found in the 
 random-bits/ folder of the weathermap distribution. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">If you don't add 
 <i>any</i> <a href="#GLOBAL_SCALE">SCALE</a> lines to a configuration file, then a default set is added for 
 you, but as soon as you add one, you'll need to make enough to cover the whole 
 0-100 range to get nice colours. Any percentage not matched by <a href="#GLOBAL_SCALE">SCALE</a> rules is 
 rendered in grey. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">You can specify a colour for an 
 <em>exact</em> percentage (e.g. zero) by using that value for both the min and 
 max values. The scale lines are sorted by min then max, and scanned from top to 
 bottom. The first match wins. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">To show a legend in the map for a given <a href="#GLOBAL_SCALE">SCALE,</a> you should use <a href="#GLOBAL_KEYPOS">KEYPOS</a> and 
 <a href="#GLOBAL_KEYSTYLE">KEYSTYLE.</a> </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">You can also apply <a href="#GLOBAL_SCALE">SCALEs</a> to colorise <a href="#NODE_ICON">ICON</a> images. You do this using 
 <a href="#NODE_USEICONSCALE">USEICONSCALE.</a> </p> 
  </div>
  <div class="examples">
    <h4>Examples</h4>
    <blockquote class="example">
      <small>
        <cite>Setting up a (very simple) colour scale. Colours run smoothly
        from green to red.</cite>
      </small>
      <pre>SCALE 0 100 0 255 0 255 0 0
            </pre>
    </blockquote>
    <blockquote class="example">
      <small>
        <cite>The default scale set</cite>
      </small>
      <pre>
SCALE 0 0 192 192 192
SCALE 0 1 255 255 255
SCALE 1 10 140 0 255
SCALE 10 25 32 32 255
SCALE 25 40 0 192 255
SCALE 40 55 0 240 0
SCALE 55 70 240 240 0
SCALE 70 85 255 192 0
SCALE 85 100 255 0 0
            </pre>
    </blockquote>
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.97</dt>
      <dd>Added absolute scale support.
        </dd>
      <dt>0.96</dt>
      <dd>Added 'none' colour option.
        </dd>
      <dt>0.95</dt>
      <dd>Added USEICONSCALE.
        </dd>
      <dt>0.95</dt>
      <dd>Added scale tags.
        </dd>
      <dt>0.9</dt>
      <dd>Added named scales.
        </dd>
      <dt>0.9</dt>
      <dd>Added considtently sorted scales.
        </dd>
      <dt>0.8</dt>
      <dd>Added interpolated scale colours.
        </dd>
      <dt>0.5</dt>
      <dd>Changed to allow min and max to be fractional.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="GLOBAL_KEYPOS">KEYPOS</h3>
  <div class="definition">KEYPOS

        <em class="meta">x-pos</em>

        <em class="meta">y-pos</em>
    </div>
  <div class="definition">KEYPOS

        <em class="meta">x-pos</em>

        <em class="meta">y-pos</em>

        <em class="meta">headingstring</em>
    </div>
  <div class="definition">KEYPOS

        <em class="meta">scalename</em>

        <em class="meta">x-pos</em>

        <em class="meta">y-pos</em>
    </div>
  <div class="definition">KEYPOS

        <em class="meta">scalename</em>

        <em class="meta">x-pos</em>

        <em class="meta">y-pos</em>

        <em class="meta">headingstring</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies the position of the key, or legend, that shows what each 
 colour-range in a scale means. If a scalename is not given, then "DEFAULT" is 
 assumed. If no <a href="#GLOBAL_KEYPOS">KEYPOS</a> line is given for a scale, then no legend is drawn 
 - handy if you have many many colour ranges. You can also hide any legend by 
 giving it a position with negative coordinates. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">You can optionally specify an additional parameter, to change the heading 
 above the colours in the key. This can be used to change the language of the 
 map, for example. If a scalename is given, then you 
 <em>must also specify a title</em> 
 - there is no useful default title for non-DEFAULT scales. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">You can control the font used to draw the key with <a href="#GLOBAL_FONT">KEYFONT,</a> and the colours 
 that it is drawn in, using <a href="#GLOBAL_COLORS">KEYTEXTCOLOR,</a> <a href="#GLOBAL_COLORS">KEYBGCOLOR</a> and <a href="#GLOBAL_COLORS">KEYOUTLINECOLOR.</a> </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.9</dt>
      <dd>Added support for multiple SCALEs.
        </dd>
      <dt>0.6</dt>
      <dd>Added ability to change text.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="GLOBAL_KEYSTYLE">KEYSTYLE</h3>
  <div class="definition">KEYSTYLE

        <em class="meta">stylename</em>
    </div>
  <div class="definition">KEYSTYLE

        <em class="meta">stylename</em>

        <em class="meta">size</em>
    </div>
  <div class="definition">KEYSTYLE

        <em class="meta">scalename</em>

        <em class="meta">stylename</em>
    </div>
  <div class="definition">KEYSTYLE

        <em class="meta">scalename</em>

        <em class="meta">stylename</em>

        <em class="meta">size</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies the style of the key, or legend, that shows what each colour-range 
 means. If a scalename is not given, then "DEFAULT" is assumed. Valid stylenames 
 are: 'classic', 'horizontal', 'vertical', 'inverted' and 'tags'. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Classic has a line for each <a href="#GLOBAL_SCALE">SCALE</a> range defined. 'vertical' and 'horizontal' 
 are fixed-size, showing a continuous block from 0-100% usage, which is much more 
 useful when gradient <a href="#GLOBAL_SCALE">SCALEs</a> are used, or when you have a large number of <a href="#GLOBAL_SCALE">SCALE</a> 
 lines in one scale. 'inverted' is the same as 'vertical', but with the zero 
 point at the bottom, thermometer-style. Finally, 'tags' is the same style as 
 'classic', but instead of percentages, it shows the tag string from the end of 
 the <a href="#GLOBAL_SCALE">SCALE</a> lines, if there are any. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"> 
 For 'horizontal' and 'vertical' styles, you can optionally add an additional 
 parameter, which specifies the longer dimension of the legend in pixels. That 
 is, for a horizontal legend, it specifies the width. The other dimension is 
 calculated from the size of the font used (see <a href="#GLOBAL_FONT">KEYFONT).</a> 
 </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"> 
 <img src="../images/keystyle-classic.png"/>Classic Style </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"> 
 <img src="../images/keystyle-horizontal.png"/>Horizontal Style </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"> 
 <img src="../images/keystyle-vertical.png"/>Vertical Style </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"> 
 <img src="../images/keystyle-inverted.png"/>Inverted Style </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude"> 
 <img src="../images/keystyle-tags.png"/>Tags Style </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">You can hide the percentage signs in the key, by setting 
 key_hidepercent_<em>scalename</em> to 1 </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">If you have a 0-&gt;0 line in your <a href="#GLOBAL_SCALE">SCALE,</a> then you can hide that in the key, by 
 setting key_hidezero_<em>scalename</em> to 1 </p> 
  </div>
  <div class="examples">
    <h4>Examples</h4>
    <blockquote class="example">
      <small>
        <cite>Hiding percentage signs, and the 'absolute zero' SCALE entry
        in a key.</cite>
      </small>
      <pre>SET key_hidezero_DEFAULT 1
SET key_hidepercent_DEFAULT 1
            </pre>
    </blockquote>
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.96</dt>
      <dd>Added tags and inverted styles.
        </dd>
      <dt>0.92</dt>
      <dd>Added hidepercent and hidezero SET variables.
        </dd>
      <dt>0.9</dt>
      <dd>Added support for multiple key styles (classic,
        horizontal, vertical).
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="GLOBAL_SET">SET</h3>
  <div class="definition">SET

        <em class="meta">hintname</em>

        <em class="meta">hintvalue</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Specifies a value for a <em>hint variable</em>. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Hint Variables allow the user to pass settings to the internals of Weathermap 
 that wouldn't normally need to be changed, or that aren't part of the core 
 Weathermap application. Examples are: small rendering changes, parameters for 
 datasources plugins and similar. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Hint Variables are either Global for the map, or assigned to a specific link 
 or node. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">There is more about Hint Variables in the 
 <a href="advanced.html">Advanced Topics</a> section. </p> 
  </div>
  <div class="examples">
    <h4>Examples</h4>
    <blockquote class="example">
      <small>
        <cite>Enabling 'bulging link mode' in the link-rendering
        code.</cite>
      </small>
      <pre>SET link_bulge 1
            </pre>
    </blockquote>
    <blockquote class="example">
      <small>
        <cite>Enabling 'screenshot mode' to anonymise a map in 0.95 or
        newer.</cite>
      </small>
      <pre>SET screenshot_mode 1
            </pre>
    </blockquote>
    <blockquote class="example">
      <small>
        <cite>Disabling 'WMWARN50' messages from appearing</cite>
      </small>
      <pre>SET nowarn_WMWARN50 1
            </pre>
    </blockquote>
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.9</dt>
      <dd>Originally added SET.
        </dd>
    </dl>
  </div>
</div>

        <div class="referenceentry">
  <h3 id="GLOBAL_INCLUDE">INCLUDE</h3>
  <div class="definition">INCLUDE

        <em class="meta">filename</em>
    </div>
  <div class="description">
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Include the contents of an additional file within the current map 
 configuration file. </p> 
 <p xmlns="http://www.w3.org/1999/xhtml" xmlns:xi="http://www.w3.org/2001/XInclude">Allows you to use a common set of definition across several map files. There 
 are several restrictions: 

 <ul> 
 <li><a href="#GLOBAL_INCLUDE">INCLUDE</a> can 
 <em>only</em> be used in the 'global' section of the the map configuration 
 file - that is, before the first <a href="#NODE_NODE">NODE</a> or LINK.</li> 

 <li>INCULDEd files must contain complete <a href="#NODE_NODE">NODE</a> or <a href="#LINK_LINK">LINK</a> definitions.</li> 

 <li>If you intend to use the web-based editor, then you can't currently use 
 any commands other than <a href="#NODE_NODE">NODE</a> or <a href="#LINK_LINK">LINK</a> definitions 
 - that is, no <a href="#GLOBAL_SCALE">SCALEs</a> or <a href="#GLOBAL_FONTDEFINE">FONTDEFINEs</a> etc.</li> 
 </ul> 

 The <a href="#GLOBAL_INCLUDE">INCLUDE</a> file can still be useful to define a set of standard templates that 
 can then be used across maps. Some of the other restrictions will hopefully be 
 lifted in a future version. The reason for the restrictions is that the way 
 config files are read and written doesn't keep track of where a particular 
 setting came from, apart from at the NODE/LINK level. This is also the reason 
 why comments are 'lost' by the editor. 
 </p> 
  </div>
  <div class="changes">
    <h4>Change History</h4>
    <dl class="small">
      <dt>0.96b</dt>
      <dd>Originally added INCLUDE.
        </dd>
    </dl>
  </div>
</div>
    
<?php include 'common-page-foot.php'; ?>
