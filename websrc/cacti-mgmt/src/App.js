import React, {Component} from 'react';
import './App.css';
import {connect} from 'react-redux';

import {loadSettings} from './actions';
import MapList from "./components/MapList";

class App extends Component {

    componentDidMount() {
        this.props.loadSettings(this.props.url);
    }


    render() {



        return (
            <div>
                <p>Weathermap Management App goes here.
                It will get data from {this.props.url} and maps from {this.props.settings.maps_url}</p>
                <p>If there are any warnings, they should go here</p>
                <MapList maps={this.props.maps}/>

            </div>
        );
    }
}

const mapStateToProps = (state) => {
    return state;
};

const mapDispatchToProps = {
    loadSettings,
};

export default connect(mapStateToProps, mapDispatchToProps)(App);
