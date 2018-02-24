import React, {Component} from 'react';
import {connect} from "react-redux";

class GroupSelector extends Component {

    render() {
        const group_options = Object.keys(this.props.groups).map((key, index) => {
            const item = this.props.groups[key];
            return <option key={item.id} value={item.id}>{item.name}</option>
        });
        return <select id={this.props.id} >
            {group_options}
        </select>
    }
}

const mapStateToProps = (state) => {
    return state;
};


export default connect(mapStateToProps)(GroupSelector);
