;
(function($)
{ // secure $ jQuery alias
/*******************************************************************************************/
    // jquery.event.drag.js - rev 10
    // Copyright (c) 2008, Three Dub Media (http://threedubmedia.com)
    // Liscensed under the MIT License (MIT-LICENSE.txt)
    // http://www.opensource.org/licenses/mit-license.php
    // Created: 2008-06-04 | Updated: 2008-08-05
/*******************************************************************************************/
    // Events: drag, dragstart, dragend
/*******************************************************************************************/

    // jquery method
    $.fn.drag = function( fn1, fn2, fn3 )
    {
        if (fn2) this.bind('dragstart', fn1); // 2+ args

        if (fn3) this.bind('dragend', fn3);   // 3 args
        return !fn1 ? this.trigger('mousedown', {
            which: 1
        })                                    // 0 args
        : this.bind('drag', fn2 ? fn2 : fn1); // 1+ args
    };

    // special event configuration
    var drag = $.event.special.drag = {
        distance: 0, // default distance dragged before dragstart
        setup: function( data )
        {
            data = $.extend({
                distance: drag.distance
            }, data || { });

            $.event.add(this, "mousedown", drag.handler, data);
        },
        teardown: function()
        {
            $.event.remove(this, "mousedown", drag.handler);

            if (this
                == drag.dragging) drag.dragging = drag.proxy = null; // deactivate element
            selectable(this, true); // enable text selection
        },
        handler: function( event )
        {
            var returnValue;

            // mousedown has initialized
            if (event.data.elem) {
                // update event properties...
                event.dragTarget = event.data.elem;                   // source element
                event.dragProxy = drag.proxy
                    || event.dragTarget; // proxy element or source
                event.cursorOffsetX = event.data.x - event.data.left; // mousedown offset
                event.cursorOffsetY = event.data.y - event.data.top;  // mousedown offset
                event.offsetX = event.pageX - event.cursorOffsetX;    // element offset
                event.offsetY = event.pageY - event.cursorOffsetY;    // element offset
            }

            // handle various events
            switch (event.type) {
                // mousedown, left click
                case !drag.dragging && event.which == 1 && 'mousedown': // initialize drag
                    $.extend(event.data, $(this).offset(), {
                        x: event.pageX,
                        y: event.pageY,
                        elem: this,
                        dist2: Math.pow(event.data.distance, 2) //  x² + y² = distance²
                    });                                         // store some initial attributes

                    $.event.add(document.body, "mousemove mouseup", drag.handler,
                        event.data);
                    selectable(this, false);                    // disable text selection
                    return false; // prevents text selection in safari
                // mousemove, check distance, start dragging
                case !drag.dragging && 'mousemove':                  // DRAGSTART >>
                    if (Math.pow(event.pageX - event.data.x, 2)
                        + Math.pow(event.pageY - event.data.y, 2) //  x² + y² = distance²
                    < event.data.dist2) break; // distance tolerance not reached
                    drag.dragging = event.dragTarget;                // activate element
                    event.type = "dragstart";                        // hijack event
                    returnValue = $.event.handle.call(drag.dragging,
                        event); // trigger "dragstart", return proxy element
                    drag.proxy = $(returnValue)[0] || drag.dragging; // set proxy

                    if (returnValue !== false) break; // "dragstart" accepted, stop
                    selectable(drag.dragging, true); // enable text selection
                    drag.dragging = drag.proxy = null;               // deactivate element
                // mousemove, dragging
                case 'mousemove': // DRAG >>
                    if (drag.dragging) {
                        event.type = "drag"; // hijack event
                        returnValue = $.event.handle.call(drag.dragging,
                            event); // trigger "drag"

                        if ($.event.special.drop) { // manage drop events
                            $.event.special.drop.allowed = (
                                returnValue !== false); // prevent drop
                            $.event.special.drop.handler(event); // "dropstart", "dropend"
                        }

                        if (returnValue !== false) break; // "drag" not rejected, stop
                        event.type = "mouseup"; // hijack event
                    }

                // mouseup, stop dragging
                case 'mouseup':                                    // DRAGEND >>
                    $.event.remove(document.body, "mousemove mouseup",
                        drag.handler); // remove page events

                    if (drag.dragging) {
                        if ($.event.special.drop)                  // manage drop events
                        $.event.special.drop.handler(event);       // "drop"
                        event.type = "dragend";                    // hijack event
                        $.event.handle.call(drag.dragging, event); // trigger "dragend"
                        selectable(drag.dragging, true); // enable text selection
                        drag.dragging = drag.proxy = null;         // deactivate element
                        event.data = { };
                    }
                    break;
            }
        }
    };

    // toggles text selection attributes
    function selectable(elem, bool)
    {
        if (!elem) return; // maybe element was removed ?

        elem.unselectable = bool ? "off" : "on";                       // IE
        elem.onselectstart = function() { return bool; };              // IE

        if (elem.style) elem.style.MozUserSelect = bool ? "" : "none"; // FF
    };

/*******************************************************************************************/
})(jQuery); // confine scope