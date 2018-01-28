import axios from 'axios';

export const SET_MAP_DATA = 'SET_MAP_DATA';
export const SET_GROUP_DATA = 'SET_GROUP_DATA';
export const SET_SETTINGS = 'SET_SETTINGS';

export function loadMaps(source_url) {
    // TODO -- this base URL should come from the settings
    // let url = "http://localhost:8016/cacti/plugins/weathermap/weathermap-cacti10-plugin.php?action=maplist";

    console.log("Getting maps from " + source_url);

    return (dispatch) => {
        axios.get(source_url, {withCredentials: true}).then((response) => {
            console.log("Got map data");
            console.log(response.data);
            if (response.data.maps) {
                dispatch(setGroups(response.data.groups));
                dispatch(setMaps(response.data.maps));
            } else {
                console.log("Didn't get actual map data");
            }
        })
    }
}


export function loadSettings(settings_url) {
    // TODO - this needs to come from somewhere outside!
    // let url = "http://localhost:8016/cacti/plugins/weathermap/weathermap-cacti10-plugin.php?action=settings";

    console.log("Getting settings from " + settings_url);

    return (dispatch) => {
        axios.get(settings_url).then((response) => {
            console.log("Got settings data");
            console.log(response.data);
            if (response.data.maps_url) {
                dispatch(setSettings(response.data));
                dispatch(loadMaps(response.data.maps_url));
            } else {
                console.log("Didn't get actual settings data");
            }
        })
    }
}


export function setMaps(map_data) {
    console.log(map_data);
    return {
        type: SET_MAP_DATA,
        maps: map_data
    }
}

export function setGroups(group_data) {
    console.log(group_data);
    return {
        type: SET_GROUP_DATA,
        groups: group_data
    }
}

export function setSettings(settings_data) {
    console.log(settings_data);
    return {
        type: SET_SETTINGS,
        settings: settings_data
    }
}
