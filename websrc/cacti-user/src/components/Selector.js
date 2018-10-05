import React, {Component} from 'react';

class Selector extends Component {
  /** props
   id
   value
   options: {name: string, id: any}
   defaultOption
   callbackFn
   **/
  onChange = e => {
    this.props.callbackFn(e.target.value);
  };

  render() {
    const options = this.props.options.map((item) => {
      return <option key={item.id} value={item.id}>{item.name}</option>
    });
    return <select value={this.props.value}
                   onChange={this.onChange}
                   id={this.props.id}>
      <option value="" disabled>
        {this.props.defaultOption}
      </option>
      {options}
    </select>
  }
}

export default Selector;
