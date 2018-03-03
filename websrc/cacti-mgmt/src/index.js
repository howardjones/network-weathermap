import React from 'react';
import ReactDOM from 'react-dom';
import './index.css';
import 'react-router-modal/css/react-router-modal.css';

import App from './App';

import {applyMiddleware, createStore} from 'redux';
import {Provider} from 'react-redux';
import thunk from 'redux-thunk';
import logger from 'redux-logger';

import {addLocaleData, IntlProvider} from 'react-intl';
import fr from 'react-intl/locale-data/fr';
import en from 'react-intl/locale-data/en';
import es from 'react-intl/locale-data/es';
import pt from 'react-intl/locale-data/pt';
import ru from 'react-intl/locale-data/ru';

import translations from './translations';

import reducers from './reducers';

import {HashRouter as Router, Route} from 'react-router-dom'
import FileSelector from "./components/AddMap";
import MapProperties from "./components/MapProperties";
import AddGroup from "./components/AddGroup";
import CreateMap from "./components/CreateMap";
import GroupProperties from "./components/GroupProperties";
import {ModalContainer, ModalRoute} from "react-router-modal";

const createStoreWithMiddleware = applyMiddleware(thunk, logger)(createStore);
const store = createStoreWithMiddleware(reducers, window.__REDUX_DEVTOOLS_EXTENSION__ && window.__REDUX_DEVTOOLS_EXTENSION__());

let wm_root = document.getElementById('weathermap-mgmt-root');

addLocaleData([...en, ...fr, ...es, ...pt, ...ru]);

const AppRoutes = () => (
    <Router>
        <div>
            <Route path="/"
                   render={(routeProps) => (
                       <App {...routeProps} {...(wm_root.dataset)} />
                   )}
            />

            <ModalRoute exact path='/add-map-picker' parentPath='/'>
                <FileSelector store={store}/>
            </ModalRoute>

            <ModalRoute exact path="/map/:id/properties" component={MapProperties} parentPath='/'/>
            <ModalRoute exact path="/group/:id/properties" component={GroupProperties} parentPath='/'/>
            <ModalRoute exact path="/add-group-form" component={AddGroup} parentPath='/'/>
            <ModalRoute exact path="/create-map" component={CreateMap} parentPath='/'/>

            <ModalContainer bodyModalOpenClassName='wm-modal-open'
                            containerClassName='wm-modal-container'
                            backdropClassName='wm-modal-backdrop'
                            modalClassName='wm-modal'/>
        </div>
    </Router>
);

const locale = translations.hasOwnProperty(wm_root.dataset['locale']) ? wm_root.dataset['locale'] : 'en';

ReactDOM.render(<Provider store={store}>
    <IntlProvider messages={translations[locale]} locale={locale}>
        <AppRoutes/>
    </IntlProvider>
</Provider>, wm_root);


