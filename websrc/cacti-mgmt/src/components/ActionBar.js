import React, {Component} from 'react';

import {Link} from 'react-router-dom';

class ActionBar extends Component {

    render() {
        return <div className="wm-actionbar">
            These don't work yet.
            <Link to="/">
                <button>Front Page (temporary)</button>
            </Link>
            <Link to="/add-map-picker">
                <button>Add Map</button>
            </Link>
            <Link to="/add-group-form">
                <button>Add Group</button>
            </Link>
            (add a new group - form to get name)
            <Link to="/create-map">
                <button>Create Map</button>
            </Link>
            (add a new blank map - form to get filename and template file (if any))
            <Link to="/settings">
                <button>Settings</button>
            </Link>
            (all the weathermap-related settings - mainly for non-Cacti)
        </div>
    }
}

export default ActionBar;