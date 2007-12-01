// JSON for jQuery by Michael Geary
// See http://mg.to/2006/01/25/json-for-jquery
// Free beer and free speech. Enjoy!

$.json = { callbacks: {} };

$.fn.json = function( url, callback ) {
    var _$_ = this;
    load( url.replace( /{callback}/, name(callback) ) );
    return this;

    function name( callback ) {
        var id = (new Date).getTime();
        var name = 'json_' + id;

        var cb = $.json.callbacks[id] = function( json ) {
            delete $.json.callbacks[id];
            eval( 'delete ' + name );
            _$_.each( function() { callback(json); } );
        };

        eval( name + ' = cb' );
        return name;
    }

    function load( url ) {
        var script = document.createElement( 'script' );
        script.type = 'text/javascript';
        script.src = url;
        $('head',document).append( script );
    }
};