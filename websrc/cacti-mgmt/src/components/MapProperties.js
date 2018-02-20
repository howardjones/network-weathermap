import React, {Component} from 'react';

class MapProperties extends Component {

    render() {
        return <div className='wm-map-properties-container wm-popup'>
            <h3>This is the popup map properties box - all settings for one map in one form.</h3>

            <p>Group</p>
            <p>Active on/off</p>
            <p>Archive on/off</p>
            <p>Debugging on/once/off</p>
            <p>Schedule</p>
            <p>Access Control</p>
            <p>Per-Map SET Settings</p>
        </div>
    }
}

export default MapProperties;