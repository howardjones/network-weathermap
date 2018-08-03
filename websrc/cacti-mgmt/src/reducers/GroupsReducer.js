import {GET_MAPS_SUCCESS} from "../actions";

const INITIAL_STATE = [];

export default function (state = INITIAL_STATE, action) {

    switch (action.type) {
        case GET_MAPS_SUCCESS:
            state = action.data.groups;
            break;

        // case SET_GROUP_DATA:
        //     if (action.groups !== undefined) {
        //         state = action.groups;
        //     } else {
        //         console.log("Got undefined maps in SET_GROUP_DATA");
        //     }
        //     break;

        default:
            break;
    }

    return state;
};