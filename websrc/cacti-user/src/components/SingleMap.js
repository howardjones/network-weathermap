import React from 'react';
import {connect} from 'react-redux';

import MapFull from './MapFull';
import LinksFooter from './LinksFooter';


const SingleMap = ({maps, match}) => {

    const maplist = maps.filter(map => map.filehash === match.params.map_id);
    const my_map = maplist[0];

    return (
        <div className="SingleMap layoutbox">
            single map view (no group tabs, just a map) (and maybe a combo selector)
            <MapFull map={my_map}/>
            <LinksFooter/>
        </div>
    )
}

function mapStateToProps(state) {
    return {settings: state.settings, groups: state.groups, maps: state.maps}
}

export default connect(mapStateToProps)(SingleMap);
