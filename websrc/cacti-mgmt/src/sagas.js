import {all, call, put, apply, takeEvery, takeLatest} from 'redux-saga/effects'

import {
    ADD_GROUP,
    ADD_GROUP_ERROR,
    ADD_GROUP_SUCCESS,
    ADD_MAPS,
    ADD_MAPS_ERROR,
    ADD_MAPS_SUCCESS,
    GET_MAPS,
    GET_MAPS_ERROR,
    GET_MAPS_SUCCESS,
    GET_SETTINGS,
    GET_SETTINGS_ERROR,
    GET_SETTINGS_SUCCESS,
    REMOVE_MAP,
    REMOVE_MAP_ERROR,
    REMOVE_MAP_SUCCESS,
    REMOVE_GROUP,
    REMOVE_GROUP_ERROR,
    REMOVE_GROUP_SUCCESS,
    ENABLE_MAP,
    ENABLE_MAP_ERROR,
    ENABLE_MAP_SUCCESS,
    DISABLE_MAP,
    DISABLE_MAP_ERROR,
    DISABLE_MAP_SUCCESS
} from './actions';

import {getSettings} from './services/api';


function* getSettingsSaga(action) {
    console.log("SAGA will get settings");

    try {
        const response = yield call(getSettings, action.api_url);
        // yield delay(500)
        const data = response.data;
        yield put({type: GET_SETTINGS_SUCCESS, data});
        yield put({type: GET_MAPS, api_url: data.maps_url})
    } catch (error) {
        yield put({type: GET_SETTINGS_ERROR, error})
    }
}

function* getMapsSaga(action) {
    console.log("SAGA will get maps");

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
    console.log(`SAGA will add group ${action.name}`);

    let api = window.wm_api;

    try {
        const response = yield apply(api, api.addGroup, [action.name]);
        // yield delay(500)
        const data = response.data;
        yield put({type: ADD_GROUP_SUCCESS, data});
        yield put({type: GET_MAPS})
    } catch (error) {
        yield put({type: ADD_GROUP_ERROR, error})
    }
}

function* removeGroupSaga(action) {
    console.log(`SAGA will remove group with id ${action.groupId}`);

    let api = window.wm_api;

    try {
        const response = yield apply(api, api.removeGroup, [action.groupId]);
        // yield delay(500)
        const data = response.data;
        yield put({type: REMOVE_GROUP_SUCCESS, data});
        yield put({type: GET_MAPS})
    } catch (error) {
        yield put({type: REMOVE_GROUP_ERROR, error})
    }
}

function* addMapsSaga(action) {
    console.log(`SAGA will add maps ${action.maps} to group ${action.groupId}`);

    let api = window.wm_api;

    try {
        const response = yield apply(api, api.addMaps, [action.maps, action.groupId]);
        // yield delay(500)
        const data = response.data;
        yield put({type: ADD_MAPS_SUCCESS, data});
        yield put({type: GET_MAPS})
    } catch (error) {
        yield put({type: ADD_MAPS_ERROR, error})
    }
}

function* removeMapSaga(action) {
    console.log(`SAGA will remove map ${action.mapId} from its group`);

    let api = window.wm_api;

    try {
        const response = yield apply(api, api.removeMap, [action.mapId]);
        // yield delay(500)
        const data = response.data;
        yield put({type: REMOVE_MAP_SUCCESS, data});
        yield put({type: GET_MAPS})
    } catch (error) {
        yield put({type: REMOVE_MAP_ERROR, error})
    }
}

function* enableMapSaga(action) {
    console.log(`SAGA will enable map ${action.mapId}`);

    let api = window.wm_api;

    try {
        const response = yield apply(api, api.enableMap, [action.mapId]);
        // yield delay(500)
        const data = response.data;
        yield put({type: ENABLE_MAP_SUCCESS, data});
        yield put({type: GET_MAPS})
    } catch (error) {
        yield put({type: ENABLE_MAP_ERROR, error})
    }
}

function* disableMapSaga(action) {
    console.log(`SAGA will disable map ${action.mapId}`);

    let api = window.wm_api;

    try {
        const response = yield apply(api, api.disableMap, [action.mapId]);
        // yield delay(500)
        const data = response.data;
        yield put({type: DISABLE_MAP_SUCCESS, data});
        yield put({type: GET_MAPS})
    } catch (error) {
        yield put({type: DISABLE_MAP_ERROR, error})
    }
}

// we only export the rootSaga
// single entry point to start all Sagas at once
export default function* rootSaga() {
    yield all([
        takeLatest(GET_SETTINGS, getSettingsSaga),
        takeLatest(GET_MAPS, getMapsSaga),
        takeEvery(ADD_GROUP, addGroupSaga),
        takeEvery(REMOVE_GROUP, removeGroupSaga),
        takeEvery(ADD_MAPS, addMapsSaga),
        takeEvery(REMOVE_MAP, removeMapSaga),
        takeEvery(ENABLE_MAP, enableMapSaga),
        takeEvery(DISABLE_MAP, disableMapSaga)
    ])
}
