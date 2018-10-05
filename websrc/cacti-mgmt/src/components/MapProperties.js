import React, {Component} from 'react';
import {connect} from "react-redux";
import ScheduleEditor from "./ScheduleEditor";
import AccessEditor from "./AccessEditor";
import SetEditor from "./SetEditor";
import {FormattedMessage} from 'react-intl';
import {removeMap, enableMap, disableMap} from '../actions';
import Selector from "./Selector";

class MapProperties extends Component {

    constructor(props) {
        super(props);
        this.state = {
            ...this.state,
            mapId: this.props.match.params.id,
            map: this.props.maps.filter(map => map.id === this.props.match.params.id).map((map => map))[0]
        };
        this.removeMap = this.removeMap.bind(this);
    }

    removeMap(event) {
        event.preventDefault();

        this.props.removeMap(this.props.match.params.id);
        this.props.closeModal();
    }

    handleChange = (event) => {
        this.setState({
            map: {
                ...this.state.map,
                active: event.target.value
            }
        });
        if (event.target.value === 'on') {
            this.props.enableMap(this.props.match.params.id);
        }
        if (event.target.value === 'off') {
            this.props.disableMap(this.props.match.params.id);
        }
    };

    render() {
        if (!this.state.map) {
            return <div className='wm-map-properties-container wm-popup'>No such map? (or loading)</div>
        }

        return <div className='wm-map-properties-container wm-popup'>
            <h3>Map Properties: map #{this.state.mapId}</h3>
            <h4>Config file: {this.state.map.configfile}</h4>

            <p>Maybe need some tabs in here?</p>

            <p>Active <select name="active" value={this.state.map.active} onChange={this.handleChange}>
                <option value='on'>Yes</option>
                <option value='off'>No</option>
            </select></p>

            <p>Group <Selector id="groupselector"
                               options={Object.values(this.props.groups)}
                               value={this.state.map.group_id}
                               defaultOption="select Group"
                               callbackFn={this.updateSelectedGroup}/>
            </p>

            <p>Archive <select name="archiving" value={this.state.map.archiving}>
                <option value='on'>Yes</option>
                <option value='off'>No</option>
            </select></p>

            <p>Debugging <select name="debug" value={this.state.map.debug}>
                <option value='on'>On</option>
                <option value='once'>Once</option>
                <option value='off'>Off</option>
            </select></p>

            <p>Schedule - {this.state.map.schedule} </p><ScheduleEditor/>

            <AccessEditor/>

            <p>Per-Map SET Settings -</p><SetEditor scope="map" id={this.state.map.id}/>

            <button onClick={this.removeMap}><FormattedMessage id="remove_map" defaultMessage="Remove Map from group"/>
            </button>

        </div>
    }
}


const mapStateToProps = (state) => {
    return state;
};


const mapDispatchToProps = dispatch => ({
    removeMap: (mapId) => {
        dispatch(removeMap(mapId));
    },
    enableMap: (mapId) => {
        dispatch(enableMap(mapId));
    },
    disableMap: (mapId) => {
        dispatch(disableMap(mapId));
    }
});


export default connect(mapStateToProps, mapDispatchToProps)(MapProperties);
