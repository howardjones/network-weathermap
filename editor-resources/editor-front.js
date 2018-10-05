jQuery(document).ready(initJS);

function initJS() {
    // check if DOM is available, if not, we'll stop here, leaving the warning showing
    if (!document.getElementById || !document.createTextNode || !document.getElementsByTagName) {
        // I'm pretty sure this is actually impossible now.
        return;
    }

    // check if there is a "No JavaScript" message
    jQuery("#nojs").hide();

    // so that's the warning hidden, now let's show the content

    // check if there is a "with JavaScript" div
    jQuery("#withjs").show();

}
