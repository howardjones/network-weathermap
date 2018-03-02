import React, {Component} from 'react';
import {FormattedMessage} from 'react-intl';

class MapListHeader extends Component {

    render() {
        return <thead>
        <tr className="tableHeader">
            <th className="wm-maptable-id" ><FormattedMessage id="id" defaultMessage="ID"/></th>
            <th className="wm-maptable-sort">sort</th>
            <th className="wm-maptable-filename" ><FormattedMessage id="config_file" defaultMessage="Config File"/></th>
            <th className="wm-maptable-title"><FormattedMessage id="title" defaultMessage="Title"/></th>
            <th className="wm-maptable-lastrun"><FormattedMessage id="last_ran" defaultMessage="Last Ran"/></th>
            <th className="wm-maptable-enabled"><FormattedMessage id="enabled" defaultMessage="Enabled"/></th>
            <th className="wm-maptable-debugging"><FormattedMessage id="debugging" defaultMessage="Debugging"/></th>
            <th className="wm-maptable-archiving"><FormattedMessage id="archiving" defaultMessage="Archiving"/></th>
            <th className="wm-maptable-schedule"><FormattedMessage id="schedule" defaultMessage="Schedule"/></th>
            <th className="wm-maptable-access"><FormattedMessage id="access" defaultMessage="Access"/></th>
        </tr>
        </thead>
    }
}

export default MapListHeader;