import React, {Component} from 'react';

class AccessEditor extends Component {

    render() {
        return <div className="box">
            <h4>Edit access list</h4>

            <h5>Allowed Access</h5>
            <ul>
                <li>Group 5 (X)</li>
                <li>User 1 (X)</li>
                <li>
                    <select>
                        <option>All Logged-in Users</option>
                        <option>Group 1</option>
                        <option>Group 2</option>
                        <option>User 1</option>
                        <option>User 2</option>
                        <option>User 3</option>
                        <option>User 4</option>
                        <option>User 5</option>
                    </select>
                    <button>Add</button>
                </li>
            </ul>
            <button>Copy access from :</button> <select><option>List of maps and groups</option></select>

        </div>
    }
}

export default AccessEditor;