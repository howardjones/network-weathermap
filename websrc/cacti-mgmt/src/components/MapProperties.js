import React, {Component} from 'react';
import {connect} from "react-redux";
import ScheduleEditor from "./ScheduleEditor";
import AccessEditor from "./AccessEditor";
import SetEditor from "./SetEditor";
import {FormattedMessage} from 'react-intl';
import {removeMap} from '../actions';
import Selector from "./Selector";

class MapProperties extends Component {

    constructor() {
        super();

        this.removeMap = this.removeMap.bind(this);
    }

    removeMap(event) {
        event.preventDefault();

        this.props.removeMap(this.props.match.params.id);
        this.props.closeModal();
    }

    render() {
        const mapId = this.props.match.params.id;

        const mapList = this.props.maps.filter(map => map.id === mapId);

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

            <p>Group <Selector id="groupselector"
                options={Object.values(this.props.groups)}
                value={myMap.group_id}
                defaultOption="select Group"
                callbackFn={this.updateSelectedGroup}/>
            </p>

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

            <button onClick={this.removeMap}><FormattedMessage id="remove_map" defaultMessage="Remove Map from group"/></button>

        </div>
    }
}


const mapStateToProps = (state) => {
    return state;
};


const mapDispatchToProps = dispatch => ({
    removeMap: (mapId) => {
        dispatch(removeMap(mapId));
    }
});


export default connect(mapStateToProps,mapDispatchToProps)(MapProperties);
