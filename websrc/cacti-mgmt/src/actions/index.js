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

export const ADD_MAPS = 'ADD_MAPS';
export const ADD_MAPS_ERROR = 'ADD_MAPS_ERROR';
export const ADD_MAPS_SUCCESS = 'ADD_MAPS_SUCCESS';

export const REMOVE_MAP = 'REMOVE_MAP';
export const REMOVE_MAP_ERROR = 'REMOVE_MAP_ERROR';
export const REMOVE_MAP_SUCCESS = 'REMOVE_MAP_SUCCESS';

export const REMOVE_GROUP = 'REMOVE_GROUP';
export const REMOVE_GROUP_ERROR = 'REMOVE_GROUP_ERROR';
export const REMOVE_GROUP_SUCCESS = 'REMOVE_GROUP_SUCCESS';

export const ENABLE_MAP = 'ENABLE_MAP';
export const ENABLE_MAP_ERROR = 'ENABLE_MAP_ERROR';
export const ENABLE_MAP_SUCCESS = 'ENABLE_MAP_SUCCESS';

export const DISABLE_MAP = 'DISABLE_MAP';
export const DISABLE_MAP_ERROR = 'DISABLE_MAP_ERROR';
export const DISABLE_MAP_SUCCESS = 'DISABLE_MAP_SUCCESS';

export function getSettings(api_url) {
    return {type: GET_SETTINGS, api_url: api_url};
}

export function addGroup(group_name) {
  return {type: ADD_GROUP, name: group_name};
}

export function removeGroup(groupId) {
  return {type: REMOVE_GROUP, groupId: groupId};
}

export function addMaps(maps, groupId) {
  return {type: ADD_MAPS, maps: maps, groupId: groupId};
}

export function removeMap(mapId) {
    return {type: REMOVE_MAP, mapId: mapId};
}

export function enableMap(mapId) {
    return {type: ENABLE_MAP, mapId: mapId};
}

export function disableMap(mapId) {
    return {type: DISABLE_MAP, mapId: mapId};
}

