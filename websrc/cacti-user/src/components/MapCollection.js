import React, {Component} from 'react';
import {connect} from 'react-redux';

import MapThumbnail from './MapThumbnail';
import MapFull from './MapFull';

const MapCollection = (props) => {

    const my_maps = props.maps.filter(map => map.group_id === props.group_id || props.group_id === "0");
    let maps = [];

    if (props.settings.page_style === 'thumbs') {
        maps = my_maps.map((item) => {
            return (<MapThumbnail map={item}/>)
        });
    }

    if (props.settings.page_style === 'full') {
        maps = my_maps.map((item) => {
            return (<MapFull map={item}/>)
        });
    }

    if (props.settings.page_style === 'full-first-only') {
        maps = my_maps.slice(0, 1).map((item) => {
            return (<MapFull map={item}/>)
        });
    }

    return (
        <div className="MapCollection layoutbox">
            {maps}
            <div className="MapCollection_clear"></div>
        </div>
    )
}

function mapStateToProps(state) {
    return {settings: state.settings, groups: state.groups, maps: state.maps}
}

export default connect(mapStateToProps)(MapCollection);
