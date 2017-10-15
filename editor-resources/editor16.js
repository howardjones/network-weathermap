// Extra stuff to patch the old editor javascript to work with the newer midway editor backend
// We pulled all the PHP (but a few variable substitutions) out of the old editor page, so this
// replaces some of that with client-side equivalents.

// Ultimately, the editor will fetch only data from the server, and present it using client-side
// templates and other this-decade technology. For now, escaping from the code+html mess of the
// original editor is an OK start.

jQuery(document).ready(initJS16);

function initJS16() {

    jQuery.each(imlist, function (index, value) {
        // slot in the list of images in:
        //    node icon selectbox
        //    map background image
        var newitem = jQuery("<option />").attr("value", value).text(value);
        jQuery(".imlist").append(newitem);
    });

    jQuery.each(fontlist, function (index, value) {
        // slot in the list of fonts in each of the select boxes where they are expected
        var newitem = jQuery("<option />").attr("value", value).text(value);
        jQuery(".fontlist").append(newitem);
    });

    // TODO: set the selected settings for each font in mapstyle
    // mapstyle_linklabels
    // mapstyle_htmlstyle
    // mapstyle_arrowstyle
    // mapstyle_nodefont
    // mapstyle_linkfont
    // mapstyle_legendfont

}
