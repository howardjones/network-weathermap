import React, {Component} from 'react';
import AccessEditor from "./AccessEditor";
import SetEditor from "./SetEditor";

class GroupProperties extends Component {

    render() {

        const groupId = this.props.match.params.id;

        // const groupList = this.props.groups.filter(map => map.id === groupId);
        //
        // console.log(groupList);
        // console.log(groupId);
        //
        // const myGroup = groupList[0];
        //
        // console.log("myGroup is:");
        // console.log(myGroup);

        return <div className='wm-group-properties-container wm-popup'>
            <h3>Group Properties for group #{groupId}</h3>

            <p>Name: <input value='name here'/></p>

            <p>Group-level map-SET values</p>
            <p>(Maybe group-level access?)</p>

            <SetEditor scope="group" id={groupId}/>
            <AccessEditor/>

        </div>
    }
}

export default GroupProperties;