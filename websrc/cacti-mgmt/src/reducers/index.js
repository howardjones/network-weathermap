import {combineReducers} from 'redux';
import CactiSettingsReducer from './CactiSettingsReducer';
import MapSettingsReducer from './MapSettingsReducer';
import GroupsReducer from './GroupsReducer';
import MapsReducer from './MapsReducer';

const rootReducer = combineReducers({
    settings: CactiSettingsReducer,
    groups: GroupsReducer,
    maps: MapsReducer,
    map_settings: MapSettingsReducer
});

export default rootReducer;
