import React, {Component} from 'react';

class FileSelector extends Component {

    render() {

        const file_options = this.props.files.map((item, index) => {
            return <option key={index}>{item}</option>
        });
        return <select id={this.props.id}>
            {file_options}
        </select>
    }
}

export default FileSelector;