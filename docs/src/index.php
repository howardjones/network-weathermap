<?php
        include "vars.php";
        $PAGE_TITLE="Start Here";
	$PATH_EXTRA="";
	$FRONT_PAGE = 1;
        include "common-page-head.php";
?>

            <p style = "text-align:center; font-weight: bold; font-size: 130%; margin-bottom: 3em; margin-top: 3em;"><a href = "pages/main.html">New
            Users should start here.</a></p>

			<h3>Documentation Guide</h3>
	<p>New in 0.97b, there is a <a href="pages/main.html#security">Security Notes</a> section that you should read.</p>
			
            <p>There are several sections, including
            <a href = "pages/main.html">Introduction</a>,
            <a href = "pages/main.html#installation">Installation</a>,
            <a href = "pages/main.html#basics">The Basics</a>,
            <a href = "pages/faq.html">FAQ</a>,
            <a href = "pages/config-reference.html">Reference</a>, and
            <a href = "pages/advanced.html">Advanced Topics</a>. There are seperate
            pages for the
            <a href = "pages/cacti-plugin.html">Cacti Plugin</a>, and for the
            <a href = "pages/editor.html">optional web-based editor</a>. There is also a
            reference page for
            <a href = "pages/errorcodes.html">all the error codes that Weathermap can
            produce</a>, with an explanation, and another
            <a href = "pages/targets.html">reference for the built-in datasource
            plugins</a>.</p>

            <p>For existing users, there's an overview to the
            <a href = "pages/changes.html">changes from the previous version</a>, and a
            <a href = "pages/upgrading.html">guide to upgrading</a>.</p>

            <p>There are also a growing number of articles at
            <a href = "http://www.network-weathermap.com/">network-weathermap.com</a>
            about specific tricks and techniques for making your own maps, including
            animation, non-network maps and more!</p>

            <p>What I'm trying to say is
            <a href = "http://en.wikipedia.org/wiki/RTFM">RTFM</a>,
            but in a more polite way!
            <tt>:-)</tt> I put a fair amount of my own time and effort into writing the
            program, and then a whole bunch
            <i>more</i> into writing the manual and other documentation - more than a thousand hours by now. I also spend my
            spare time supporting Weathermap users [I'm one of the all-time most active users on the Cacti forums], but a lot of questions asked are
            answered in the manual, or worse still, in the FAQ section.
            <i>Please</i> take a look. </p>

			<h3>Visual Guides</h3>
			
			<p>If you are more visually-minded, like me, then the following might also help:</p>
			
            <div style = "width: 610px; margin-left: auto; margin-right: auto;">
                <div style = "width: 300px; float:left; margin-right: 5px;">
                    <p class = "card-photo"><a href = "howto.png">

                    <img src = "images/howto-thumb.jpg" /></a></p>

                    <p class = "card-desc">Diagram explaining a lot of the basic map
                    formatting commands. Look in the
                    <a href = "pages/config-reference.html">Configuration Reference</a>
                    for more information on the commands mentioned.

                    <br /><a href = "howto.png">Larger version</a> |
                    <a href = "howto.pdf">PDF version</a></p>
                </div>

                <div style = "width: 300px;  margin-left: 5px;float:right;">
                    <p class = "card-photo"><a href = "images/weathermap-example.png">

                    <img src = "images/weathermap-mini.png" /></a></p>

                    <p class = "card-desc">Sample output from php-weathermap, using data
                    collected by Cacti and MRTG.

                    <br /><a href = "images/weathermap-example.png">Larger version</a></p>
					<p>This is from the <a href="pages/main.html#example">Example Map</a> section of the manual.</p>
                </div>
            </div>

            <br clear = "both" />

<?php
        include "common-page-foot.php";
