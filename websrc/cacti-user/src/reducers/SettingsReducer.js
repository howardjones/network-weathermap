import immutable from 'object-path-immutable';

import {VIEW_ALL_FULL, VIEW_FIRST_FULL, VIEW_THUMBS} from '../actions';

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

   console.log(action);
    
    switch(action.type) {
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
