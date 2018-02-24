import React, {Component} from 'react';
import GroupSelector from "./GroupSelector";

class CreateMap extends Component {

    render() {
        return <div className='wm-create-map-container wm-popup'>
            <h3>Create a new map</h3>
            <p>Map name:<input type="text"/></p>
            <p>source map XXXXX</p>
            <p>Group name: <GroupSelector/></p>
            <p>
                <button>Create</button>
                <button>Cancel</button>
            </p>

        </div>
    }
}

export default CreateMap;