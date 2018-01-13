import React from 'react';
import {connect} from 'react-redux';

import GroupTabs from './GroupTabs';
import MapCollection from './MapCollection';
import LinksFooter from './LinksFooter';
import ModeSelector from './ModeSelector';


const MapGroup = ({match}) => {

    return (
        <div className="MapGroup layoutbox">
            <ModeSelector/>
            <GroupTabs group_id={match.params.group_id}/>
            <MapCollection group_id={match.params.group_id}/>
            <LinksFooter/>
        </div>
    )
}

function mapStateToProps(state) {
    return {settings: state.settings, groups: state.groups, maps: state.maps}
}

export default connect(mapStateToProps)(MapGroup);
