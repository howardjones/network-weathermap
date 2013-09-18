var KEYCODE_ESCAPE = 27;
var KEYCODE_LEFT = 37;
var KEYCODE_RIGHT = 39
var KEYCODE_SPACE = 32;
        
jQuery.fn.center = function () {
		this.css("position","fixed");
	this.css("top", Math.max(0, (($(window).height() - $(this).outerHeight()) / 2) + 
						    $(window).scrollTop()) + "px");
	this.css("left", Math.max(0, (($(window).width() - $(this).outerWidth()) / 2) + 
						    $(window).scrollLeft()) + "px");
	return this;
}
    
   // wm_fullscreen = <?php echo ($fullscreen ? "1" : "0"); ?>;
        wm_current = 0;
        wm_countdown = 0;
        wm_period = 0;
    wm_nmaps = 0;
       // wm_poller_cycle = <?php echo $poller_cycle; ?> * 1000;
    wm_paused = false;
                
        wm_timer_counter = null;
        wm_timer_reloader = null;

    function wm_update_progess() {
	// update the countdown bar - 450 is the max width in pixels
	var progress = wm_countdown / (wm_period/200) * 450;
	$("#wm_progress").css("width",progress);		
    }
    
    // update the countdown, unless paused. Then just flash the progress bar.
        function wm_counter() 
        {
	if (wm_paused) {
		$("#wm_progress").toggleClass("paused");
	} else {			
		wm_update_progess();
                wm_countdown--;
		
		if (wm_countdown < 0) {
			wm_switchmap(1);
		}
	}				
        }

    // change to the next (or previous) map, reset the countdown, update the bar
        function wm_switchmap(direction)
        {
	var wm_new = wm_current + direction;
	
	if (wm_new < 0) wm_new += wm_nmaps;
	wm_new = wm_new % wm_nmaps;
	
	var now = $(".weathermapholder").eq(wm_current);
	var next = $(".weathermapholder").eq(wm_new);
	
	if (wm_fullscreen) {
		// in fullscreen, we centre everything, layer it with z-index and cross-fade
		next.center();	
		now.css("z-index", 2);
		next.css("z-index", 3);
		
		now.fadeOut(1200, function () {
			// now that we're done with it, force a reload on the image just passed
			var d = new Date();
			var newurl = $(this).find('img').attr("src");
			newurl = newurl.replace(/time=\d+/, "time=" + d.getTime());				

			$(this).find('img').attr( "src", newurl);
		} );
		next.fadeIn(1200);
	} else {
		// in non-fullscreen mode, the fades just make things look strange. Snap-changes
		now.hide(1, function () {
			// now that we're done with it, force a reload on the image just passed
			var d = new Date();
			var newurl = $(this).find('img').attr("src");
			newurl = newurl.replace(/time=\d+/, "time=" + d.getTime());				

			$(this).find('img').attr( "src", newurl);
		} );
		next.show(1);
	}
	
	wm_countdown = wm_period/200;
	wm_current = wm_new;
	
	$("#wm_current_map").text(wm_current + 1);		
	wm_update_progess();
        }

        function wm_reload() {
            // window.location.reload();
        }
    
    function wm_pause() {
	wm_paused = ! wm_paused;
	// remove the paused class on the progress bar, if we're mid-flash and no longer paused
	if (! wm_paused) {
		$("#wm_progress").removeClass("paused");
	}
    }

    function wm_next() {
	wm_switchmap(1);
    }
    
    function wm_prev() {
	wm_switchmap(-1);
    }
    
    function wm_initJS()
    {        	
	wm_nmaps = $(".weathermapholder").length;

	$("#wm_total_map").text(wm_nmaps);
	
	$("#cycle_pause").click(wm_pause);
	$("#cycle_next").click(wm_next);
	$("#cycle_prev").click(wm_prev);
	
	$(document).keyup( function (event) {
        if (event.keyCode == KEYCODE_ESCAPE) {
			window.location.href = $('#cycle_stop').attr('href');
            event.preventDefault();
		}
        
		if (event.keyCode == KEYCODE_SPACE) {
			wm_pause();
			event.preventDefault();
		}
        // left
		if (event.keyCode == KEYCODE_LEFT) {
			wm_prev();
			event.preventDefault();
		}
        // right
		if (event.keyCode == KEYCODE_RIGHT) {
			wm_next();
			event.preventDefault();
		}
	});
	
            // stop here if there were no maps
            if (wm_nmaps > 0) {
                wm_current = 0;
                
	    wm_switchmap(0);
	    		    		    
                // figure out how long the refresh is, so that we get
                // through all the maps in exactly 5 minutes

               // wm_period = <?php echo $refreshtime ?> * 1000;

                if (wm_period == 0) {
                    wm_period = wm_poller_cycle / wm_nmaps;
                }
                wm_countdown = wm_period/200;

                // a countdown timer in the top corner
                wm_timer_counter = setInterval(wm_counter, 200);
	    
                // when to reload the whole page (with new map data)
                wm_timer_reloader = setTimeout(wm_reload, wm_poller_cycle);
            }
	
	// aim to get a video-player style OSD for fullscreen mode:
	// if the pointer is off the controls for more than 5 seconds, fade the controls away
	// if the pointer moves after that, bring the controls back
	// if the pointer is over the controls, don't fade
	if (wm_fullscreen) {
		// $("#wmcyclecontrolbox").delay(5000).fadeOut(500);
		// $("body").mousemove( function () {$("#wmcyclecontrolbox").fadeIn(100); }  );
		// $("#wmcyclecontrolbox").mouseleave( function () { $("#wmcyclecontrolbox").delay(2000).fadeOut(500); });
	}
        }
        
    $(document).ready(wm_initJS);