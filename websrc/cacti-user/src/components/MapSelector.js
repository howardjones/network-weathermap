import React, {Component} from 'react';
import {connect} from 'react-redux';

const MapSelector = ({maps}) => {

    const options = maps.map((item, index) => {
        return (<option key={index} value={item.filehash}>{item.titlecache}</option>)
    });

    return <select>
        {options}
    </select>;
};


function mapStateToProps(state) {
    return {settings: state.settings, groups: state.groups, maps: state.maps}
}

export default connect(mapStateToProps)(MapSelector);
