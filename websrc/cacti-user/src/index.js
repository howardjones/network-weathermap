import React from 'react';
import ReactDOM from 'react-dom';
import './index.css';
import {applyMiddleware, createStore} from 'redux';
import {Provider} from 'react-redux';
import thunk from 'redux-thunk';
import logger from 'redux-logger';

import reducers from './reducers';

import {HashRouter as Router, Redirect, Route} from 'react-router-dom'


import WMUserApp from './WMUserApp';
import SingleMap from './components/SingleMap';
import MapGroup from './components/MapGroup';

const createStoreWithMiddleware = applyMiddleware(thunk, logger)(createStore);
const store = createStoreWithMiddleware(reducers, window.__REDUX_DEVTOOLS_EXTENSION__ && window.__REDUX_DEVTOOLS_EXTENSION__());

let wm_root = document.getElementById('weathermap-user-root');

const AppRoutes = () => (
    <Router>
        <div>
            <Redirect from="/" to="/group/1"/>
            <Route path="/group/:group_id" component={MapGroup}/>
            <Route path="/map/:map_id" component={SingleMap}/>
            <Route path="/"
                render={(routeProps) => (
                    <WMUserApp {...routeProps} {...(wm_root.dataset)} />
                )}
            />
        </div>
    </Router>
);

ReactDOM.render(<Provider store={store}><AppRoutes/></Provider>, wm_root);


