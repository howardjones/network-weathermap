import React, {Component} from 'react';
import {FormattedMessage} from 'react-intl';
import MapListEntry from "./MapListEntry";
import MapListHeader from "./MapListHeader";

class MapList extends Component {

    render() {

        const groups = Object.keys(this.props.groups).map((key, index) => {
            const maps = this.props.maps.filter((map) => {
                return map.group_id === key;
            });

            const m = maps.map((item, index) => {
                return <MapListEntry key={index} item={item} groups={this.props.groups} settings={this.props.settings}/>
            });


            return <div key={index}>
                <h3>{this.props.groups[key].name}
                    <small>{this.props.groups[key].sortorder}</small>
                </h3>
                <table border={1} className="cactiTable">
                    <MapListHeader/>
                    <tbody>
                    {maps.length === 0 ? <tr>
                        <td><em><FormattedMessage id="no_maps" defaultMessage="No maps in this group"/></em></td>
                    </tr> : m}
                    </tbody>
                </table>
            </div>
        });

        return <div>
            <p>TODO: Align table columns between groups?</p>
            {groups}
        </div>
    }

}

export default MapList;