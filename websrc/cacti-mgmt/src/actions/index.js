export const GET_SETTINGS = 'GET_SETTINGS';
export const GET_SETTINGS_SUCCESS = 'GET_SETTINGS_SUCCESS';
export const GET_SETTINGS_ERROR = 'GET_SETTINGS_ERROR';

export const GET_MAPS = 'GET_MAPS';
export const GET_MAPS_SUCCESS = 'GET_MAPS_SUCCESS';
export const GET_MAPS_ERROR = 'GET_MAPS_ERROR';

export const GET_GROUPS = 'GET_GROUPS';
export const GET_GROUPS_SUCCESS = 'GET_GROUPS_SUCCESS';
export const GET_GROUPS_ERROR = 'GET_GROUPS_ERROR';

export const ADD_GROUP = 'ADD_GROUP';
export const ADD_GROUP_ERROR = 'ADD_GROUP_ERROR';
export const ADD_GROUP_SUCCESS = 'ADD_GROUP_SUCCESS';

export function getSettings(api_url) {
    return {type: GET_SETTINGS, api_url: api_url};
}

export function addGroup(group_name) {
    return {type: ADD_GROUP, name: group_name};
}

