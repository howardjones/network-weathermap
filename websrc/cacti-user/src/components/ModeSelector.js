import React from 'react';
import {connect} from 'react-redux';

import {viewAllFull, viewFirstFull, viewThumbs} from '../actions';

class ModeSelector extends React.Component {
    constructor(props) {
        super(props);
        this.clickedFirstFull = this.clickedFirstFull.bind(this);
        this.clickedFull = this.clickedFull.bind(this);
        this.clickedThumbs = this.clickedThumbs.bind(this);
    }

    clickedThumbs() {
        this.props.dispatch(viewThumbs());
    }


    clickedFull() {
        this.props.dispatch(viewAllFull())
    }


    clickedFirstFull() {
        this.props.dispatch(viewFirstFull())
    }


    render() {

        return (
            <div className="ModeSelector layoutbox">
                Temporary mode-selector (until settings are hooked up):
                <a onClick={this.clickedThumbs}>Thumbs</a> |
                <a onClick={this.clickedFull}>Full</a> |
                <a onClick={this.clickedFirstFull}>Full (first only)</a>
            </div>
        )
    }
}

function mapStateToProps(state) {
    return {settings: state.settings}
}

export default connect(mapStateToProps)(ModeSelector);
