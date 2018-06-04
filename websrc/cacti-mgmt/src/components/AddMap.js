import React, {Component} from 'react';
import {FormattedMessage} from 'react-intl';
import GroupSelector from "./GroupSelector";
import {connect} from "react-redux";
import {Link} from "react-router-dom";
import axios from 'axios';

class FileSelector extends Component {

    constructor() {
        super();

        this.state = {files: [], loaded: false, show_used: false};

        this.toggleHideUsed = this.toggleHideUsed.bind(this);
        this.refreshList = this.refreshList.bind(this);
        this.handleChangeChk = this.handleChangeChk.bind(this);

    }

    componentDidMount() {
        this.refreshList();
    }

    refreshList() {

        const api_url = this.props.settings.api_url + 'listmapfiles';

        console.log("Loading from " + api_url);

        axios.get(api_url, {withCredentials: true}).then( (data) => {
            console.log(data);

            let newData = data.data.map((item, index) => {
                return {...item, index: index, selected: false}
            });

            console.log("Loaded");

            this.setState((prevState, props) => {
                return {
                    ...prevState,
                    loaded: true,
                    files: newData
                };
            });
        });
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
                <p>Pick map files to add to the schedule...</p>
                <p>
                    <button
                        onClick={this.toggleHideUsed}>{this.state.show_used ? "HIDE USED" : "INCLUDE USED"}</button>
                    <button
                        onClick={this.refreshList}>Refresh
                    </button>
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
                <Link to="/"><button><FormattedMessage id="cancel" defaultMessage="Cancel"/></button></Link>

            </div>
        } else {
            contents = <div>Loading</div>
        }

        return <div className='wm-file-selector'>
            {contents}
        </div>


    }
}

function mapStateToProps(state) {
    console.log(state); // state
    return { settings: state.settings};
}

// export default FileSelector;
export default connect(mapStateToProps)(FileSelector);
