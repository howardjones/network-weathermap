import React, {Component} from 'react';
import GroupSelector from "./GroupSelector";
import {Link} from "react-router-dom";
import {FormattedMessage} from "react-intl";
import FileSelector from "./FileSelector";

class CreateMap extends Component {

    render() {
        return <div className='wm-create-map-container wm-popup'>
            <h3>Create a new map</h3>
            <p>Map name: <input type="text"/></p>
            <p>source map: <FileSelector files={['-- BLANK --', 'file1','file2']}/></p>
            <p>Group name: <GroupSelector/></p>
            <p>Immediately add to schedule? <input type="checkbox"/></p>
            <p>
                <button><FormattedMessage id="create" defaultMessage="Create"/></button>
                <Link to="/"><button><FormattedMessage id="cancel" defaultMessage="Cancel"/></button></Link>
            </p>
        </div>
    }
}

export default CreateMap;