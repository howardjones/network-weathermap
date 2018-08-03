import React from 'react';
import {connect} from 'react-redux';

import {Link} from 'react-router-dom';

const GroupTabs = (props) => {

    if (props.groups.length === 1) {
        return <span></span>
    }

    let my_groups = props.groups.slice();
    if (props.settings.show_all_tab) {
        my_groups.push({id: "0", name: "All Maps"})
    }

    return (
        <div className="GroupTabs layoutbox">
            Groups:
            {
                my_groups.map((group) => {
                    let cls = "tab";
                    if (group.id === props.group_id) {
                        cls = cls + " active";
                    }
                    return (<Link to={`../group/${group.id}`} key={group.id} className={cls}>{group.name}</Link>)

                })
            }
        </div>
    )
}

function mapStateToProps(state) {
    return {settings: state.settings, groups: state.groups}
}

export default connect(mapStateToProps)(GroupTabs);
