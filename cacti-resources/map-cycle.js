"use strict";
/*global $:false */

// http://stackoverflow.com/a/210733/2397542 - jQuery center function from Tony L.
jQuery.fn.center = function () {
    this.css("position", "fixed");

    this.css("top",  Math.max(0, (($(window).height() - this.outerHeight()) / 2) + $(window).scrollTop()) + "px");
    this.css("left", Math.max(0, (($(window).width() - this.outerWidth()) / 2) + $(window).scrollLeft()) + "px");

    return this;
};

var WMcycler = {

    KEYCODE_ESCAPE : 27,
    KEYCODE_LEFT : 37,
    KEYCODE_RIGHT : 39,
    KEYCODE_SPACE : 32,

    period : 0,
    fullscreen : 0,
    poller_cycle : 0,
    current : 0,
    countdown : 0,
    nmaps : 0,
    paused : false,
    timer_counter : null,
    timer_reloader : null,

    updateProgress : function () {
        // update the countdown bar - 450 is the max width in pixels
        var progress = this.countdown / (this.period / 200) * 450;
        $("#wm_progress").css("width", progress);
    },

    counterHandler : function () {
        if (this.paused) {
            $("#wm_progress").toggleClass("paused");
        } else {
            this.updateProgress();
            this.countdown--;

            if (this.countdown < 0) {
                this.switchMap(1);
            }
        }
    },
    forceReload: function (that) {
        var d = new Date(),
            newurl = $(that).find('img').attr("src");

        newurl = newurl.replace(/time=\d+/, "time=" + d.getTime());

        $(that).find('img').attr("src", newurl);
    },

    // change to the next (or previous) map, reset the countdown, update the bar
    switchMap : function (direction) {
        var wm_new = this.current + direction;

        if (wm_new < 0) {
            wm_new += this.nmaps;
        }
        wm_new = wm_new % this.nmaps;

        var now = $(".weathermapholder").eq(this.current),
            next = $(".weathermapholder").eq(wm_new);

        if (this.fullscreen) {
            // in fullscreen, we centre everything, layer it with z-index and
            // cross-fade
            next.center();
            now.css("z-index", 2);
            next.css("z-index", 3);

            now.fadeOut(1200, function () {
                // now that we're done with it, force a reload on the image just
                // passed
                WMcycler.forceReload(this);
            });
            next.fadeIn(1200);
        } else {
            // in non-fullscreen mode, the fades just make things look strange.
            // Snap-changes
            now.hide(1, function () {
                // now that we're done with it, force a reload on the image just
                // passed
                // WMcycler.forceReload();
            });
            next.show(1);
        }

        this.countdown = this.period / 200;
        this.current = wm_new;

        $("#wm_current_map").text(this.current + 1);
        this.updateProgress();
    },


    hideControls: function () {
        $("#wmcyclecontrolbox").fadeOut(500);
    },

    showControls: function () {
        $("#wmcyclecontrolbox").fadeIn(100);
    },

    start : function (initialData) {


	$('.weathermapholder').hide();

        this.period = initialData.period;
        this.poller_cycle = initialData.poller_cycle;
        this.fullscreen = initialData.fullscreen;

        this.nmaps = $(".weathermapholder").length;
        $("#wm_total_map").text(this.nmaps);

        // copy of this that we can pass into callbacks
        var that = this;

        this.initEvents(that);
        this.initKeys(that);

        // stop here if there were no maps
        if (this.nmaps > 0) {
            this.current = 0;

            this.switchMap(0);

            // figure out how long the refresh is, so that we get
            // through all the maps in exactly one poller cycle
            if (this.period === 0) {
                this.period = this.poller_cycle / this.nmaps;
            }
            this.countdown = this.period / 200;

            // a countdown timer in the top corner
            this.timer_counter = setInterval(function () {
                that.counterHandler();
            }, 200);

            // when to reload the whole page (with new map data)
            this.timer_reloader = setTimeout(function () {
                that.reload();
            }, this.poller_cycle);

            this.initIdle(that);
        }
    },

    initKeys: function (that) {
        $(document).keyup(function (event) {
            if (event.keyCode === that.KEYCODE_ESCAPE) {
                window.location.href = $('#cycle_stop').attr('href');
                event.preventDefault();
            }

            if (event.keyCode === that.KEYCODE_SPACE) {
                that.pauseAction();
                event.preventDefault();
            }
            // left
            if (event.keyCode === that.KEYCODE_LEFT) {
                that.previousAction();
                event.preventDefault();
            }
            // right
            if (event.keyCode === that.KEYCODE_RIGHT) {
                that.nextAction();
                event.preventDefault();
            }
        });
    },

    initEvents: function (that) {

        $("#cycle_pause").click(function () {
            that.pauseAction();
        });
        $("#cycle_next").click(function () {
            that.nextAction();
        });
        $("#cycle_prev").click(function () {
            that.previousAction();
        });
    },

    initIdle: function (that) {
        // aim to get a video-player style OSD for fullscreen mode:
        // if the pointer is off the controls for more than 5 seconds, fade the
        // controls away
        // if the pointer moves after that, bring the controls back
        // if the pointer is over the controls, don't fade
        if (this.fullscreen) {

            $(document).idleTimer({
                timeout: 5000
            });

            $(document).on("idle.idleTimer", function () {
                that.hideControls();
            });
            $(document).on("active.idleTimer", function () {
                that.showControls();
            });

        }
    },

    nextAction : function () {
        this.switchMap(1);
    },
    previousAction : function () {
        this.switchMap(-1);
    },
    pauseAction : function () {
        this.paused = !this.paused;
        // remove the paused class on the progress bar, if we're mid-flash and
        // no longer paused
        if (!this.paused) {
            $("#wm_progress").removeClass("paused");
        }
    }
};
