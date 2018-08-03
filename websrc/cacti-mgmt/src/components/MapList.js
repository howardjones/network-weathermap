import React, {Component} from 'react';
import {FormattedMessage} from 'react-intl';
import MapListEntry from "./MapListEntry";
import MapListHeader from "./MapListHeader";
import GroupHeader from "./GroupHeader";

class MapList extends Component {

    render() {

        const groups = Object.keys(this.props.groups).map((key, index) => {
            const group = this.props.groups[key];

            const mapsInGroup = this.props.maps.filter((map) => {
                return map.group_id === key;
            });

            if (mapsInGroup.length === 0) {
                return <div key={index}>
                    <GroupHeader group={group}/>
                    <em><FormattedMessage id="no_maps" defaultMessage="No maps in this group"/></em>
                </div>
            }

            const mapEntries = mapsInGroup.map((item, index) => {
                return <MapListEntry key={index} item={item} editor_url={this.props.settings.editor_url}/>
            });

            return <div key={index}>
                <GroupHeader group={group}/>

                <table border={1} className="cactiTable">
                    <MapListHeader/>
                    <tbody>
                    {mapEntries}
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