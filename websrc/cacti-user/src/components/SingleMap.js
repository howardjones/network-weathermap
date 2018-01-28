import React from 'react';
import {connect} from 'react-redux';

import MapFull from './MapFull';
import LinksFooter from './LinksFooter';
import MapSelector from "./MapSelector";


const SingleMap = ({maps, match, settings}) => {

    const maplist = maps.filter(map => map.filehash === match.params.map_id);
    const my_map = maplist[0];

    let selector = <span></span>;
    let result = <span>No data for this map id</span>;

    if (my_map) {
        result = <MapFull map={my_map}/>;

        if (maplist && settings.map_selector) {
            selector = <MapSelector maps={maps}/>;
        }
    }

    return (
        <div className="SingleMap layoutbox">
            <p>single map view (no group tabs, just a map) (and maybe a combo selector)</p>
            {selector}
            {result}
            <LinksFooter/>
        </div>
    )
}

function mapStateToProps(state) {
    return {settings: state.settings, groups: state.groups, maps: state.maps}
}

export default connect(mapStateToProps)(SingleMap);
