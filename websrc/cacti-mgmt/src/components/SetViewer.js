import React, {Component} from 'react';

class SetViewer extends Component {

    render() {
        return <div className="box">
            Read-only SET view for higher level ({this.props.scope})
            <table>
                <tr>
                    <th>name3</th><td>e</td>
                </tr>
                <tr>
                    <th>name5</th><td>value22</td>
                </tr>
            </table>
        </div>
    }
}

export default SetViewer;