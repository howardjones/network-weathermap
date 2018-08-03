import React, {Component} from 'react';
import {connect} from "react-redux";
import GroupSelector from "./GroupSelector";
import ScheduleEditor from "./ScheduleEditor";
import AccessEditor from "./AccessEditor";
import SetEditor from "./SetEditor";

class MapProperties extends Component {

    render() {
        const mapId = this.props.match.params.id;

        const mapList = this.props.maps.filter(map => map.id === mapId);

        console.log(mapList);
        console.log(mapId);

        const myMap = mapList[0];

        console.log("myMap is:");
        console.log(myMap);

        if (!myMap) {
            return <div className='wm-map-properties-container wm-popup'>No such map? (or loading)</div>
        }

        return <div className='wm-map-properties-container wm-popup'>
            <h3>Map Properties: map #{mapId}</h3>
            <h4>Config file: {myMap.configfile}</h4>

            <p>Maybe need some tabs in here?</p>

            <p>Active <select name="active" defaultValue={myMap.active}>
                <option value='on'>Yes</option>
                <option value='off'>No</option>
            </select></p>

            <p>Group <GroupSelector selected={myMap.group_id}/></p>

            <p>Archive <select name="archiving" defaultValue={myMap.archiving}>
                <option value='on'>Yes</option>
                <option value='off'>No</option>
            </select></p>

            <p>Debugging <select name="debug" defaultValue={myMap.debug}>
                <option value='on'>On</option>
                <option value='once'>Once</option>
                <option value='off'>Off</option>
            </select></p>

            <p>Schedule - {myMap.schedule} </p><ScheduleEditor/>

            <AccessEditor/>

            <p>Per-Map SET Settings -</p><SetEditor scope="map" id={myMap.id}/>


        </div>
    }
}


const mapStateToProps = (state) => {
    return state;
};


export default connect(mapStateToProps)(MapProperties);
