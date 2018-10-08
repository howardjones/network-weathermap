import React, {Component} from 'react';
import AccessEditor from "./AccessEditor";
import SetEditor from "./SetEditor";
import {removeGroup} from '../actions';
import {FormattedMessage} from "react-intl";
import {connect} from "react-redux";

class GroupProperties extends Component {

    constructor(props) {
        super(props);

        this.removeGroup = this.removeGroup.bind(this);
    }

    removeGroup(event) {
        event.preventDefault();
        console.log("removing group");
        console.log("group id: " + this.props.match.params.id);
        this.props.removeGroup(this.props.match.params.id);
        this.props.closeModal();
    }

    render() {

        const groupId = this.props.match.params.id;
        const group = this.props.groups[groupId];

        if (group) {

            return <div className='wm-group-properties-container wm-popup'>
                <h3>Group Properties for group #{groupId} "{group.name}"</h3>

                <p>Name: <input value={group.name}/></p>

                <p>Group-level map-SET values</p>
                <p>(Maybe group-level access?)</p>

                <SetEditor scope="group" id={groupId}/>
                <AccessEditor/>
                {groupId !== 1 &&
                <button onClick={this.removeGroup}><FormattedMessage id="remove_group" defaultMessage="Remove group"/>
                </button>}
            </div>
        }

        return <b>No group data...</b>;
    }
}

const mapStateToProps = (state) => {
    return state;
};

const mapDispatchToProps = dispatch => ({
    removeGroup: (groupId) => {
        dispatch(removeGroup(groupId));
    }
});

export default connect(mapStateToProps, mapDispatchToProps)(GroupProperties);
