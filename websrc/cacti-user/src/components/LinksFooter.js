import React, {Component} from 'react';
import {connect} from 'react-redux';


const LinksFooter = (props) => {
    return (
        <div className="LinksFooter layoutbox">
            Powered by <a href={`http://www.network-weathermap.com/?v=${props.settings.wm_version}`}>PHP Weathermap
            version {props.settings.wm_version}</a>
            --- <a href={props.settings.management_url}>Weathermap Management</a>
            | <a href={props.settings.docs_url}>Local Documentation</a>
            | <a href={props.settings.editor_url}>Editor</a>
        </div>
    )
}

function mapStateToProps(state) {
    return {settings: state.settings}
}

export default connect(mapStateToProps)(LinksFooter);
