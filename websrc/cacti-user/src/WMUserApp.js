import React from 'react';
import {connect} from 'react-redux';

import {loadSettings} from './actions';

import './App.css';

import {HashRouter, Redirect, Route} from 'react-router-dom'


import SingleMap from './components/SingleMap';
import MapGroup from './components/MapGroup';


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
                <small>(The Base URL is {this.props.url}, passed from outside)</small>
                <HashRouter>
                    <div>
                        <Route path="/" exact>
                            <Redirect to="/group/1"/>
                        </Route>
                        <Route path="/group/:group_id" component={MapGroup}/>
                        <Route path="/map/:map_id" component={SingleMap}/>
                    </div>
                </HashRouter>
            </div>
        );
    }
}

const mapStateToProps = (state) => (state);

const mapDispatchToProps = {
    loadSettings,
};

export default connect(mapStateToProps, mapDispatchToProps)(WMUserApp);
