import React, {Component} from 'react';
import {FormattedMessage, FormattedNumber, FormattedPlural} from 'react-intl';
import {Link} from "react-router-dom";


class MapListEntry extends Component {

    static on_off_once(t) {
        if (t === 'on') {
            return <FormattedMessage id='on' default='on'/>
        }

        if (t === 'off') {
            return <FormattedMessage id='off' default='off'/>
        }

        if (t === 'once') {
            return <FormattedMessage id='once' default='once'/>
        }

        return <div>SOMETHING WENT WRONG HERE</div>
    }


    render() {
        const item = this.props.item;

        const props_url = `/map/${item.id}/properties`;

        return (<tr key={item.id}>
            <td><Link to={props_url}>
                <button>Edit</button>
            </Link></td>
            <td>
                <small>{item.sortorder}</small>
            </td>
            <td><a href={this.props.editor_url + item.configfile}>{item.configfile}</a></td>
            <td>{item.titlecache}</td>
            <td><FormattedNumber maximumFractionDigits={2} value={item.runtime}/>s {item.warncount > 0 ?
                <span className="wm_map_warnings">(<FormattedNumber value={item.warncount}/>&nbsp;<FormattedPlural
                    value={item.warncount} zero="" one="warning" other="warnings"/>)</span> : ""}</td>
            <td>{MapListEntry.on_off_once(item.active)}</td>
            <td>{MapListEntry.on_off_once(item.debug)}</td>
            <td>{MapListEntry.on_off_once(item.archiving)}</td>
            <td>{item.schedule === "*" ?
                <FormattedMessage id="always" defaultMessage="always"/> : item.schedule}</td>
            <td>-</td>
        </tr>)
    }
}

export default MapListEntry;
