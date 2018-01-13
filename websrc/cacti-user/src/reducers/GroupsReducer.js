import {SET_GROUP_DATA} from "../actions";

const INITIAL_STATE = [];

export default function (state = INITIAL_STATE, action) {

    switch (action.type) {
        case SET_GROUP_DATA:
            state = action.groups;
            break;
        default:
            break;
    }

    return state;
};