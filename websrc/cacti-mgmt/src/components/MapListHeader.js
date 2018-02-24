import React, {Component} from 'react';
import {FormattedMessage} from 'react-intl';

class MapListHeader extends Component {

    render() {
        return <thead>
        <tr className="tableHeader">
            <th><FormattedMessage id="id" defaultMessage="ID"/></th>
            <th>sort</th>
            <th><FormattedMessage id="config_file" defaultMessage="Config File"/></th>
            <th><FormattedMessage id="title" defaultMessage="Title"/></th>
            <th><FormattedMessage id="group" defaultMessage="Group"/></th>
            <th><FormattedMessage id="last_ran" defaultMessage="Last Ran"/></th>
            <th><FormattedMessage id="enabled" defaultMessage="Enabled"/></th>
            <th><FormattedMessage id="debugging" defaultMessage="Debugging"/></th>
            <th><FormattedMessage id="archiving" defaultMessage="Archiving"/></th>
            <th><FormattedMessage id="schedule" defaultMessage="Schedule"/></th>
            <th><FormattedMessage id="access" defaultMessage="Access"/></th>
        </tr>
        </thead>
    }
}

export default MapListHeader;