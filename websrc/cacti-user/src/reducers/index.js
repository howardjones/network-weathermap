import { combineReducers } from 'redux';

import SettingsReducer from './SettingsReducer';
import GroupsReducer from './GroupsReducer';
import MapsReducer from './MapsReducer';
import DisplayReducer from './DisplayReducer';

const rootReducer = combineReducers({
    settings: SettingsReducer,
    groups: GroupsReducer,
    maps: MapsReducer,
    display: DisplayReducer
});

export default rootReducer;
