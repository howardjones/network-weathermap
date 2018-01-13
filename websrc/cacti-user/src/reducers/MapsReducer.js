import {SET_MAP_DATA} from "../actions";

const INITIAL_STATE = [];

export default function (state = INITIAL_STATE, action) {

    switch (action.type) {
        case SET_MAP_DATA:
            state = action.maps;
            break;
        default:
            break;
    }

    return state;
};