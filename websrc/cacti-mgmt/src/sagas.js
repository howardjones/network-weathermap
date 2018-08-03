import {all, call, put, apply, takeEvery, takeLatest} from 'redux-saga/effects'

import {
    addGroup,
    ADD_GROUP,
    ADD_GROUP_ERROR,
    ADD_GROUP_SUCCESS,
    GET_MAPS,
    GET_MAPS_ERROR,
    GET_MAPS_SUCCESS,
    GET_SETTINGS,
    GET_SETTINGS_ERROR,
    GET_SETTINGS_SUCCESS
} from './actions';

import {getMaps, getSettings} from './services/api';


function* getSettingsSaga(action) {
    console.log("SAGA will get settings")

    try {
        const response = yield call(getSettings, action.api_url)
        // yield delay(500)
        const data = response.data
        yield put({type: GET_SETTINGS_SUCCESS, data})
        yield put({type: GET_MAPS, api_url: data.maps_url})
    } catch (error) {
        yield put({type: GET_SETTINGS_ERROR, error})
    }
}

function* getMapsSaga(action) {
    console.log("SAGA will get maps")

    let api = window.wm_api;

    try {
        const response = yield apply(api, api.getMaps)
        // yield delay(500)
        const data = response.data
        yield put({type: GET_MAPS_SUCCESS, data})
    } catch (error) {
        yield put({type: GET_MAPS_ERROR, error})
    }
}


function* addGroupSaga(action) {
    console.log(`SAGA will add group ${action.name}`)

    let api = window.wm_api;

    try {
        const response = yield apply(api, api.addGroup, [action.name])
        // yield delay(500)
        const data = response.data
        yield put({type: ADD_GROUP_SUCCESS, data})
        yield put({type: GET_MAPS})
    } catch (error) {
        yield put({type: ADD_GROUP_ERROR, error})
    }
}

// we only export the rootSaga
// single entry point to start all Sagas at once
export default function* rootSaga() {
    yield all([
        takeLatest(GET_SETTINGS, getSettingsSaga),
        takeLatest(GET_MAPS, getMapsSaga),
        takeEvery(ADD_GROUP, addGroupSaga),
    ])
}
