import React, {Component} from 'react';
import {connect} from 'react-redux';

class MapFull extends Component {


  constructor() {
    super();
    this.state = {
      htmlContent: null
    };
  }

  componentDidMount() {
    this.getMapHtml();
  }

  componentDidUpdate(prevProps, prevState) {
    if (!this.props.map || this.props.map.filehash !== prevProps.map.filehash) {
      this.getMapHtml();
    }
  }

  getMapHtml() {
    const path = 'output/' + this.props.map.filehash + '.html';
    fetch(path).then(response => {
      return response.text();
    }).then(response => {
      this.setState({
        htmlContent: response
      });
    });
  }

  getMarkup() {
    return {__html: this.state.htmlContent}
  }

  render() {
    return (
        <div className="MapFull layoutbox">
          <h3>FULL: {this.props.map.title}</h3>
          <div id="overDiv" style={{position: 'fixed', visibility: 'hide', zIndex: 1}}></div>

          <div>
            {this.state.htmlContent ? <div>
              <div dangerouslySetInnerHTML={this.getMarkup()}></div>
            </div> : null}
          </div>
        </div>
    )
  }
}

function mapStateToProps(state) {
  return {settings: state.settings}
}

export default connect(mapStateToProps)(MapFull);
