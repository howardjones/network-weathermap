import React, {Component} from 'react';
import './App.css';
import './iconfont/styles.css';
import {connect} from 'react-redux';

// import {loadSettings} from './actions';
import MapList from "./components/MapList";
import Footer from "./components/Footer";
import ActionBar from "./components/ActionBar";

class App extends Component {

    componentDidMount() {
      //   this.props.loadSettings(this.props.url);
    }

    render() {
        return (
            <div>
                <p>Most styling is temporary!</p>
                <p>If there are any warnings, they should go here (e.g. file permissions or other global warnings)</p>

                <ActionBar/>
                <MapList maps={this.props.maps} groups={this.props.groups} settings={this.props.settings}/>
                <Footer settings={this.props.settings}/>
            </div>
        );
    }
}

const mapStateToProps = (state) => {
    return state;
};

const mapDispatchToProps = {
    // loadSettings,
};

export default connect(mapStateToProps, mapDispatchToProps)(App);
