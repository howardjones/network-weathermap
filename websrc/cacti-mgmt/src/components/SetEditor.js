import React, {Component} from 'react';
import SetViewer from "./SetViewer";

class SetEditor extends Component {

    render() {

        let higher = <div/>

        if (this.props.scope === 'map') {
            higher = <div>
                <SetViewer scope="global"/>
                <SetViewer scope="group" id={222}/>
            </div>
        }

        if (this.props.scope === 'group') {
            higher = <div>
                <SetViewer scope="global"/>
            </div>
        }

        const values = {
            "name1": "value1",
            "name2": "value2"
        };

        const editors = Object.keys(values).map( (key, index) => {
            return <tr key={index}>
                <th><input value={key} /></th>
                <td>=</td>
                <td><input value={values[key]} /></td>
                <td><button>Delete</button></td>
            </tr>

        });

        return <div className="box">
            {higher}
            <div className="box">
                Edit SET variables for this {this.props.scope}
                <table>
                    {editors}
                </table>
                <button>Add</button>
            </div>
        </div>
    }
}

export default SetEditor;