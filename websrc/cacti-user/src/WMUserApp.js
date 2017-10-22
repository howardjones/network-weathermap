import React, { Component } from 'react';

import LinksFooter from './components/LinksFooter';

import './App.css';

class WMUserApp extends React.Component {

//  <GroupTabs group={group_id}/>
//  <MapCollection group={group_id} />
//  <SingleMap map={map_id} />

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

export default WMUserApp;
