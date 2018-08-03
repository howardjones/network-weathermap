import React, {Component} from 'react';

class ScheduleEditor extends Component {

    render() {
        return <div  className="box">
            Schedule Editor (cron-style string)
            <select name="schedule">
                <option>Every Poll</option>
                <option>Hourly</option>
                <option>Daily</option>
                <option>Weekly</option>
                <option>Monthly</option>
                <option>Annually</option>
            </select>
        </div>
    }
}

export default ScheduleEditor;