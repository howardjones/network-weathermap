import {GET_MAPS_SUCCESS} from "../actions";

const INITIAL_STATE = [];

export default function (state = INITIAL_STATE, action) {

    switch (action.type) {
        case GET_MAPS_SUCCESS:
            state = action.data.maps;
            break;

        // // case SET_MAP_DATA:
        // //     if (action.maps !== undefined) {
        // //         state = action.maps;
        // //     } else {
        // //         console.log("Got undefined maps in SET_MAP_DATA");
        // //     }
        //     break;
        default:
            break;
    }

    return state;
};