import {combineReducers} from 'redux';
import SettingsReducer from './SettingsReducer';
import GroupsReducer from './GroupsReducer';
import MapsReducer from './MapsReducer';

const rootReducer = combineReducers({
    settings: SettingsReducer,
    groups: GroupsReducer,
    maps: MapsReducer,
});

export default rootReducer;
