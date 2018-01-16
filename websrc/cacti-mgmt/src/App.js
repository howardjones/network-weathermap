import React, {Component} from 'react';
import './App.css';

class App extends Component {
    render() {
        return (
            <div>
                Weathermap Management App goes here.
                It will get data from {this.props.url}
            </div>
        );
    }
}

export default App;
