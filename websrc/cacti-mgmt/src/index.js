import React from 'react';
import ReactDOM from 'react-dom';
import './index.css';
import App from './App';

import {applyMiddleware, createStore} from 'redux';
import {Provider} from 'react-redux';
import thunk from 'redux-thunk';
import logger from 'redux-logger';

import reducers from './reducers';

import {HashRouter as Router, Route} from 'react-router-dom'

const createStoreWithMiddleware = applyMiddleware(thunk, logger)(createStore);
const store = createStoreWithMiddleware(reducers, window.__REDUX_DEVTOOLS_EXTENSION__ && window.__REDUX_DEVTOOLS_EXTENSION__());

let wm_root = document.getElementById('weathermap-mgmt-root');

const AppRoutes = () => (
    <Router>
        <div>
            <Route path="/"
                   render={(routeProps) => (
                       <App {...routeProps} {...(wm_root.dataset)} />
                   )}
            />
        </div>
    </Router>
);


ReactDOM.render(<Provider store={store}><AppRoutes/></Provider>, wm_root);


