import React, {Component} from 'react';
import {Link} from "react-router-dom";

class GroupHeader extends Component {

    render() {
        const props_url = `/group/${this.props.group.id}/properties`;

        return <h3>{this.props.group.name}
            <Link to={props_url}>
                <button>Edit</button>
            </Link>
        </h3>

    }
}

export default GroupHeader;
