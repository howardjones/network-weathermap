import React, {Component} from 'react';
import {FormattedMessage, FormattedNumber, FormattedPlural} from 'react-intl';

class MapList extends Component {

    on_off_once(t) {
        if (t==='on') {
            return <FormattedMessage id='on' default='on'/>
        }

        if (t==='off') {
            return <FormattedMessage id='off' default='off'/>
        }

        if (t==='once') {
            return <FormattedMessage id='once' default='once'/>
        }

        return <div>SOMETHING WENT WRONG HERE</div>
    }

    render() {

        const map_entries = this.props.maps.map((item, index) => {
            return (<tr key={index}>
                <td>{item.id}</td>
                <td>{item.configfile}</td>
                <td>{item.titlecache}</td>
                <td>{item.group_id}</td>
                <td><FormattedNumber value={item.runtime} />s {item.warncount > 0 ? <span className="wm_map_warnings">(<FormattedNumber value={item.warncount}/>&nbsp;<FormattedPlural value={item.warncount} zero="" one="warning" other="warnings"/>)</span>: ""}</td>
                <td>{this.on_off_once(item.active)}</td>
                <td>{this.on_off_once(item.debug)}</td>
                <td>{this.on_off_once(item.archiving)}</td>
                <td>{item.schedule === "*" ? <FormattedMessage id="always" defaultMessage="always"/> : item.schedule}</td>
            </tr>)
        });

        return <table border={1} className="cactiTable">
            <thead>
            <tr className="tableHeader">
                <th><FormattedMessage id="id" defaultMessage="ID"/></th>
                <th><FormattedMessage id="config_file" defaultMessage="Config File"/></th>
                <th><FormattedMessage id="title" defaultMessage="Title"/></th>
                <th><FormattedMessage id="group" defaultMessage="Group"/></th>
                <th><FormattedMessage id="last_ran" defaultMessage="Last Ran"/></th>
                <th><FormattedMessage id="enabled" defaultMessage="Enabled"/></th>
                <th><FormattedMessage id="debugging" defaultMessage="Debugging"/></th>
                <th><FormattedMessage id="archiving" defaultMessage="Archiving"/></th>
                <th><FormattedMessage id="schedule" defaultMessage="Schedule"/></th>
            </tr>
            </thead>
            <tbody>
            {map_entries}
            </tbody>
        </table>

    }

}

export default MapList;