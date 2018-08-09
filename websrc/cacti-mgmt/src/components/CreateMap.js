import React, {Component} from 'react';
import {Link} from "react-router-dom";
import {FormattedMessage} from "react-intl";
import Selector from "./Selector";
import connect from "react-redux/es/connect/connect";

class CreateMap extends Component {

  constructor() {
    super();
    this.state = {
      selectedGroup: '',
      selectedSource: ''
    }
  }

  updateSelectedGroup(group) {
    this.setState({
      selectedGroup: group
    })
  }

  updateSelectedSource(source) {
    this.setState({
      selectedSource: source
    })
  }

  render() {
    return <div className='wm-create-map-container wm-popup'>
      <h3>Create a new map</h3>
      <p>Map name: <input type="text"/></p>
      <p>source map: <Selector id="sourceselector"
                               options={[
                                 {name: '-- BLANK --', id: '0'},
                                 {name: 'file1', id: '1'},
                                 {name: 'file2', id: '3'}
                               ]}
                               value={this.state.selectedSource}
                               defaultOption="select source"
                               callbackFn={this.updateSelectedSource}/>
      </p>
      <p>Group name: <Selector id="groupselector"
                               options={Object.values(this.props.groups)}
                               value={this.state.selectedGroup}
                               defaultOption="select Group"
                               callbackFn={this.updateSelectedGroup}/>
      </p>
      <p>Immediately add to schedule? <input type="checkbox"/></p>
      <p>
        <button><FormattedMessage id="create" defaultMessage="Create"/></button>
        <Link to="/">
          <button><FormattedMessage id="cancel" defaultMessage="Cancel"/></button>
        </Link>
      </p>
    </div>
  }
}


function mapStateToProps(state) {
  return {
    groups: state.groups
  };
}

export default connect(mapStateToProps)(CreateMap);
