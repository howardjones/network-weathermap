import React, {Component} from 'react';

import {Link} from 'react-router-dom';

class ActionBar extends Component {

    render() {
        return <div className="wm-actionbar">
            <Link to="/create-map">
                <button>Create Map</button>
            </Link>
            <Link to="/add-map-picker">
                <button>Add Map</button>
            </Link>
            <Link to="/add-group-form">
                <button>Add Group</button>
            </Link>
            <Link to="/settings">
                <button>Settings</button>
            </Link>
        </div>
    }
}

export default ActionBar;