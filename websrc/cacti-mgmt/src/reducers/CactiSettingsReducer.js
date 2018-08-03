import {GET_SETTINGS_SUCCESS} from "../actions";

const INITIAL_STATE = {
    wm_version: '1.0',
    page_style: 'thumbs',  // 'full', 'full-first-only'
    cycle_time: 'auto',    // or a number of seconds
    show_all_tab: false,
    map_selector: true,
    thumb_url: '/cacti/plugins/weathermap/weathermap-cacti10-plugin.php?action=viewthumb&id=',
    image_url: '/cacti/plugins/weathermap/weathermap-cacti10-plugin.php?action=viewimage&id=',
    editor_url: "/cacti/plugins/weathermap/weathermap-cacti10-plugin-editor.php",
    docs_url: "/cacti/plugins/weathermap/docs/",
    management_url: "/cacti/plugins/weathermap/weathermap-cacti10-plugin-mgmt.php"
};

export default function (state = INITIAL_STATE, action) {

//    console.log(action);

    switch (action.type) {
        case GET_SETTINGS_SUCCESS:
            window.wm_api.setBaseURL(action.data.api_url);
            window.wm_api.setMapsURL(action.data.maps_url);
            return action.data;

        // case SET_SETTINGS:
        //     if (action.settings !== undefined) {
        //         return action.settings;
        //     } else {
        //         console.log("Got undefined settings in SET_SETTINGS");
        //         return state;
        //     }

        default:
            return state;
    }
};
