import React, {Component} from 'react';

class AppSettings extends Component {

    render() {
        return <div className='wm-app-settings-container wm-popup'>
            <h4>Settings from the host App (cacti, etc)</h4>
            <p>Page Style</p>
            <p>Thumbnail Size</p>
            <p>Refresh Time</p>
            <p>Map Rendering Interval</p>
            <p>Output Format</p>
            <p>Show 'All' Tab</p>
            <p>Show Map Selector</p>
            <p>Quiet Logging</p>
        </div>
    }
}

export default AppSettings;