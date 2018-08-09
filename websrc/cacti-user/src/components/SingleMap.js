import React, {Component} from 'react';
import {connect} from 'react-redux';

import MapFull from './MapFull';
import LinksFooter from './LinksFooter';
import Selector from "./Selector";
import {Redirect} from "react-router";

class SingleMap extends Component {

  constructor() {
    super();
    this.state = {
      selectedMap: null,
      selectOptions: null,
      redirect: false,
      loading: true
    }
  }

  componentDidMount() {
    this.updateView();
  }

  componentDidUpdate() {
    this.updateView();
  };

  updateView() {
    if (this.state.redirect === true) {
      this.setState({
        redirect: false
      });
    }
    if (this.props.maps && this.props.settings) {
      if (!this.state.selectedMap) {
        this.props.maps.filter(map => map.filehash === this.props.match.params.map_id).map(map => {
          return this.setState({
            selectedMap: map
          },);
        });
      }
      if (this.state.selectedMap && this.props.settings.map_selector && !this.state.selectOptions) {
        this.setState({
          selectOptions: this.props.maps.map((map) => {
            return {
              name: map.titlecache || map.configfile,
              id: map.filehash
            }
          })
        });
      } else if (this.state.loading !== false) {
        this.setState({
          loading: false
        });
      }
    }
  }

  updateSelectedMap = (selectedMapHash) => {
    console.log('hash', selectedMapHash);
    return this.props.maps.filter((map) => {
      return map.filehash === selectedMapHash;
    }).map(map => {
      return this.setState({
        selectedMap: map,
        redirect: true
      });
    });
  };

  render() {
    return (
        <div className="SingleMap layoutbox">
          <p>single map view (no group tabs, just a map) (and maybe a combo selector)</p>
          {this.state.redirect && this.state.selectedMap && <Redirect to={`/map/${this.state.selectedMap.filehash}`}/>}
          {this.state.loading && <span>{'Loading'}</span>}
          {this.state.selectOptions && <Selector id="mapselector"
                                                 options={this.state.selectOptions}
                                                 value={this.state.selectedMap.filehash}
                                                 defaultOption="select a map"
                                                 callbackFn={this.updateSelectedMap}/>}
          {this.state.selectedMap && <MapFull map={this.state.selectedMap}/>}
          <LinksFooter/>
        </div>
    );
  }
}

function mapStateToProps(state) {
  console.log('hbfierbfk', state, state.maps);
  return {settings: state.settings, groups: state.groups, maps: state.maps};
}

export default connect(mapStateToProps)(SingleMap);
