import React, {Component} from 'react';
import {Link, withRouter} from "react-router-dom";
import {connect} from 'react-redux';
import {addGroup} from '../actions';

class AddGroupReal extends Component {

    constructor() {
        super();

        this.state = {group_name: 'new-group'};

        this.handleSubmit = this.handleSubmit.bind(this);
        this.handleGroupNameChange = this.handleGroupNameChange.bind(this);
    }

    handleSubmit(event) {
        const {history} = this.props;

        console.log(`Create a new group called ${this.state.group_name}`);
        event.preventDefault();

        this.props.addGroup(this.state.group_name);
        history.push("/")
    }

    handleGroupNameChange(event) {
        this.setState({group_name: event.target.value});
    }

    render() {
        return <div className='wm-add-group-container wm-popup'>
            <h3>Add a new group</h3>
            <form onSubmit={this.handleSubmit}>
                <p>Group name: <input id="group_name" onChange={this.handleGroupNameChange} defaultValue="new-group"
                                      value={this.state.group_name}/></p>
                <p>
                    <input type="submit" onClick={this.handleSubmit} value="Add"/>
                    <Link to="/">
                        <button>Cancel</button>
                    </Link>
                </p>
            </form>

        </div>
    }
}


const mapDispatchToProps = dispatch => ({
    addGroup: group_name => {
        dispatch(addGroup(group_name));
    },
});

const AddGroup = withRouter(AddGroupReal);

export default connect(null, mapDispatchToProps)(AddGroup);
