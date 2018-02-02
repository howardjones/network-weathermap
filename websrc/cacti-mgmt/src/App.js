import React, {Component} from 'react';
import './App.css';
import {connect} from 'react-redux';

import {loadSettings} from './actions';
import MapList from "./components/MapList";
import Footer from "./components/Footer";

class App extends Component {

    componentDidMount() {
        this.props.loadSettings(this.props.url);
    }


    render() {



        return (
            <div>
                <p>If there are any warnings, they should go here</p>
                <MapList maps={this.props.maps} settings={this.props.settings}/>
                <Footer/>
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
