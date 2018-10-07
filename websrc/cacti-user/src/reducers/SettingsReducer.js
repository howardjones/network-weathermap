import {VIEW_ALL_FULL, VIEW_FIRST_FULL, VIEW_THUMBS} from '../actions';

import {SET_SETTINGS} from "../actions";

const INITIAL_STATE = {
    wm_version: '1.0',
    page_style: 'thumbs',  // 'full', 'full-first-only'
    cycle_time: 'auto',    // or a number of seconds
    show_all_tab: false,
    map_selector: true,
    thumb_url: '/cacti/plugins/weathermap/weathermap-cacti10-plugin.php?action=viewthumb&id=',
    image_url: '/cacti/plugins/weathermap/weathermap-cacti10-plugin.php?action=viewimage&id=',
    html_url: '/cacti/plugins/weathermap/weathermap-cacti10-plugin.php?action=viewhtml&id=',
    editor_url: "/cacti/plugins/weathermap/weathermap-cacti10-plugin-editor.php",
    docs_url: "/cacti/plugins/weathermap/docs/",
    management_url: "/cacti/plugins/weathermap/weathermap-cacti10-plugin-mgmt.php"
};

export default function (state = INITIAL_STATE, action) {

    console.log(action);

    switch (action.type) {
        case SET_SETTINGS:
            if (action.settings !== undefined) {
                return action.settings;
            } else {
                console.log("Got undefined settings in SET_SETTINGS");
                return state;
            }
        case VIEW_ALL_FULL:
            return {...state, page_style: "full"};

        case VIEW_FIRST_FULL:
            return {...state, page_style: "full-first-only"};

        case VIEW_THUMBS:
            return {...state, page_style: "thumbs"};

        default:
            return state;
    }
};
