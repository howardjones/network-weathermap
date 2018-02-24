import React, {Component} from 'react';

class AddGroup extends Component {

    render() {
        return <div className='wm-add-group-container wm-popup'>
            <h3>Add a new group</h3>
            <p>Group name: <input /> </p>
            <p><button>Add group</button><button>Cancel</button></p>

        </div>
    }
}

export default AddGroup;