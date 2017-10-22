import React, {Component} from 'react';
import {connect} from 'react-redux';


const MapFull = ({map, settings}) => {

    return (
        <div className="MapFull layoutbox">
            <h3>{map.title}</h3>
            <p>This needs to do some magic to get the imagemap.</p>
            <div>
                <img src={`${settings.image_url}${map.filehash}`} alt={map.titlecache}/>
            </div>
        </div>
    )
}


function mapStateToProps(state) {
    return {settings: state.settings}
}

export default connect(mapStateToProps)(MapFull);
