import React, {Component} from 'react';
import './App.css';
import {connect} from 'react-redux';

import {loadSettings} from './actions';

class App extends Component {

    componentDidMount() {
        this.props.loadSettings(this.props.url);
    }


    render() {

        const ll = this.props.maps.map((item) => {
            return (<tr>
                <td>{item.configfile}</td>
                <td>{item.titlecache}</td>
                <td>group{item.group_id}</td>
                <td>{item.runtime} ({item.warncount})</td>
                <td>{item.active}</td>
            </tr>)
        });

        return (
            <div>
                <p>Weathermap Management App goes here.
                It will get data from {this.props.url} and maps from {this.props.settings.maps_url}</p>
                <table border={1}>
                    <thead>
                    <tr>
                        <th>Config File</th>
                        <th>Title</th>
                        <th>Group</th>
                        <th>Last Run</th>
                        <th>Active</th>
                    </tr>
                    </thead>
                    <tbody>
                    {ll}
                    </tbody>
                </table>

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
