import React from 'react';
import {connect} from 'react-redux';

import {Link} from 'react-router-dom';

const MapThumbnail = ({map,settings}) => {

    return (
        <div className="wm_thumbcontainer">
            <div className="wm_thumbtitle">{map.titlecache}</div>
            <Link to={`/map/${map.filehash}`}>
                <img className="wm_thumb" src={`${settings.thumb_url}${map.filehash}`}  width={map.thumb_width} height={map.thumb_height} alt={map.titlecache}/>
            </Link>
        </div>
    )
}


function mapStateToProps(state) {
    return {settings: state.settings}
}

export default connect(mapStateToProps)(MapThumbnail);
