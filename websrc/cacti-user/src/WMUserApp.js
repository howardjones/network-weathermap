import React from 'react';
import {connect} from 'react-redux';

import {loadSettings} from './actions';

import './App.css';

class WMUserApp extends React.Component {

//  <GroupTabs group={group_id}/>
//  <MapCollection group={group_id} />
//  <SingleMap map={map_id} />

    componentDidMount() {
        this.props.loadSettings(this.props.url);
    }

    render() {
        // const group_id="1";
        // const map_id="e75f5cb8fe470b3ec78e";

        return (
            <div className="WMUserApp layoutbox">
                (The base URL is {this.props.url}, passed from outside)
                {this.props.children}
            </div>
        );
    }
}

const mapStateToProps = (state) => (state);

const mapDispatchToProps = {
    loadSettings,
};

export default connect(mapStateToProps, mapDispatchToProps)(WMUserApp);
