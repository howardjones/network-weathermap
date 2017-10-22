import React from 'react';
import ReactDOM from 'react-dom';
import './index.css';
// import App from './App';
import registerServiceWorker from './registerServiceWorker';

import {createStore, applyMiddleware} from 'redux';
import {Provider} from 'react-redux';

import reducers from './reducers';

import {
    HashRouter as Router,
    Route,
    Redirect,
    Link
} from 'react-router-dom'


import WMUserApp from './WMUserApp';
import SingleMap from './components/SingleMap';
import MapGroup from './components/MapGroup';

import './index.css';

const createStoreWithMiddleware = applyMiddleware()(createStore);
const store = createStoreWithMiddleware(reducers, window.__REDUX_DEVTOOLS_EXTENSION__ && window.__REDUX_DEVTOOLS_EXTENSION__());

const AppRoutes = () => (
    <Router>
        <div>
            <Redirect from="/" to="/group/1"/>
            <Route path="/group/:group_id" component={MapGroup}/>
            <Route path="/map/:map_id" component={SingleMap}/>
            <Route path="/" component={WMUserApp}/>
        </div>
    </Router>
);


ReactDOM.render(<Provider store={store}><AppRoutes/></Provider>, document.getElementById('root'));

registerServiceWorker();
