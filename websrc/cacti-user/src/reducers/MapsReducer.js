import {SET_MAP_DATA} from "../actions";

const INITIAL_STATE = [];

export default function (state = INITIAL_STATE, action) {

    switch (action.type) {
        case SET_MAP_DATA:
            if (action.maps !== undefined) {
                state = action.maps;
            } else {
                console.log("Got undefined maps in SET_MAP_DATA");
            }
            break;
        default:
            break;
    }

    return state;
};