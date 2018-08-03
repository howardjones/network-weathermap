import React, {Component} from 'react';
import {FormattedMessage} from 'react-intl';

class Footer extends Component {

    render() {
        return <div className="wm-footer">
            <span className="wm-footer-element"><FormattedMessage id="this_is_network_weathermap"
                                                                  defaultMessage="This is Network Weathermap"/>&nbsp;{this.props.settings.wm_version}</span>
            <span className="wm-footer-element"><a href={this.props.settings.editor_url}><FormattedMessage id="editor"
                                                                                                           defaultMessage="Editor"/></a></span>
            <span className="wm-footer-element"><a href={this.props.settings.docs_url}><FormattedMessage id="manual"
                                                                                                         defaultMessage="Manual"/></a></span>
            <span className="wm-footer-element"><a href="http://network-weathermap.com/"><FormattedMessage id="website"
                                                                                                           defaultMessage="Website"/></a></span>
        </div>
    }
}

export default Footer;
