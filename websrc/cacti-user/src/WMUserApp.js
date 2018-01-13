import React from 'react';
import {connect} from 'react-redux';

import {loadMaps} from './actions';

import './App.css';

class WMUserApp extends React.Component {

//  <GroupTabs group={group_id}/>
//  <MapCollection group={group_id} />
//  <SingleMap map={map_id} />

    componentDidMount() {
        this.props.loadMaps();
    }

    render() {
        // const group_id="1";
        // const map_id="e75f5cb8fe470b3ec78e";

        return (
            <div className="WMUserApp layoutbox">
                {this.props.children}
            </div>
        );
    }
}

const mapStateToProps = (state) => (state);

const mapDispatchToProps = {
    loadMaps,
};

export default connect(mapStateToProps, mapDispatchToProps)(WMUserApp);
