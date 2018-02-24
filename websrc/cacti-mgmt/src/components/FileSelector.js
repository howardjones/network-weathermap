import React, {Component} from 'react';
import {FormattedMessage} from 'react-intl';
import GroupSelector from "./GroupSelector";

class FileSelector extends Component {

    constructor() {
        super();

        this.state = {files: [], loaded: false, show_used: false};
    }

    componentDidMount() {

        // fake an API call for now
        setTimeout(function () {
            this.setState((prevState, props) => {

                let data = [{"config":"093-test.conf","title":"scriptalert(document.cookie);/script","flags":["USED"]},{"config":"094-test.conf","title":"Testing THold DS","flags":[]},{"config":"095-test.conf","title":"0.95 Test Map","flags":[]},{"config":"096-test-2.conf","title":"0.96 Test Map","flags":[]},{"config":"096-test.conf","title":"0.96 Test Map {map:titleextra}","flags":[]},{"config":"097-simple.conf","title":"0.97 DS changes","flags":["USED"]},{"config":"097-test.conf","title":"0.96 Test Map {map:titleextra}","flags":[]},{"config":"icon-scale-tag.conf","title":"(no title)","flags":[]},{"config":"lacour-bug.conf","title":"(no title)","flags":[]},{"config":"simple.conf","title":"Simple Map","flags":["USED"]},{"config":"switch-status-2.conf","title":"same map as switch-status.conf, but with every port scaled","flags":[]},{"config":"switch-status.conf","title":"(no title)","flags":["USED"]},{"config":"template-debug.conf","title":"0.96 Test Map","flags":[]},{"config":"test-bg.png","title":"(no title)","flags":[]},{"config":"timezones.conf","title":"(no title)","flags":[]},{"config":"torture-with-imaps.conf","title":"(no title)","flags":[]},{"config":"torture.conf","title":"(no title)","flags":[]},{"config":"weathermap.conf","title":"(no title)","flags":[]}];
                data = data.map((item, index) => {
                    return {...item, index: index, selected: false}
                });

                return {
                    ...prevState,
                    loaded: true,
                    files: data
                };
            });
        }.bind(this), 2000);

        this.toggleHideUsed = this.toggleHideUsed.bind(this);
        this.handleChangeChk = this.handleChangeChk.bind(this);

    }

    handleChangeChk(e) {
        console.log("CHANGE to " + e.target.id);
        const chk_index = parseInt(e.target.dataset['index'], 10);
        console.log(chk_index);

        this.setState((prevState, props) => {

            let f = prevState.files;

            f[chk_index].selected = !f[chk_index].selected;
            console.log(f[chk_index].config);

            return {
                files: f
            }
        });
    }

    toggleHideUsed() {
        this.setState((prevState, props) => {
            return {
                ...prevState,
                show_used: !prevState.show_used
            }
        });
    }

    render() {

        let contents = null;

        if (this.state.loaded) {

            const visible_maps = this.state.files.filter((file) => {

                return this.state.show_used || !file.flags.includes('USED');
            });

            const maplist = visible_maps.map((item, index) => {
                    return (<tr key={item.index} className={item.selected ? "wm-row-selected" : ""}>
                        <th><input data-index={item.index} id={"CHK_" + item.index} type="checkbox" checked={item.selected}
                                   onChange={this.handleChangeChk}/>
                            {item.config}</th>
                        <td>{item.title}</td>
                        <td>{item.flags}</td>
                    </tr>)
                }
            );

            contents = <div>
                <p>This is the popup file picker (data is hardcoded JSON - API does exist though)</p>
                <p>
                    <button
                        onClick={this.toggleHideUsed}>{this.state.show_used ? "HIDE USED" : "INCLUDE USED"}</button>
                </p>

                <table className="wm-picker">
                    <thead>
                    <tr>
                        <th><FormattedMessage id="config_file" defaultMessage="Config File"/></th>
                        <th><FormattedMessage id="title" defaultMessage="Title"/></th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    {maplist}
                    </tbody>
                </table>
                <button><FormattedMessage id="add_selected" defaultMessage="Add Selected Maps"/></button>

                To group: <GroupSelector id="groupselector" groups={this.props.groups}/>
                or:
                <button><FormattedMessage id="cancel" defaultMessage="Cancel"/></button>

            </div>
        } else {
            contents = <div>Loading</div>
        }

        return <div className='wm-picker-container  wm-popup'>
            {contents}
        </div>


    }
}

export default FileSelector;