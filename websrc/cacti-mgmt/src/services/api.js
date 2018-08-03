import axios from 'axios';


export function getSettings(source_url) {
    return axios.get(source_url, {withCredentials: true})
}

export function getMaps(source_url) {
    return axios.get(source_url, {withCredentials: true})
}

export function getGroups(source_url) {
    return axios.get(source_url, {withCredentials: true})
}

class WeathermapAPI {

    constructor() {
        this.base_url = '';
        this.maps_url = '';
    }

    getMaps() {
        const source_url = this.maps_url;
        console.log(`API Getting ${source_url}`)
        return axios.get(source_url, {withCredentials: true})
    }

    addGroup(group_name) {
        const source_url = this.base_url + "group_add";
        console.log(`API Adding ${group_name} via ${source_url}`)

        const config = { headers: { 'Content-Type': 'multipart/form-data', withCredentials: true } };


        let data = new FormData()
        data.append('name', group_name)

        return axios.post(source_url, data, config)
    }

    setBaseURL(management_url) {
        this.base_url = management_url;
    }

    setMapsURL(maps_url) {
        this.maps_url = maps_url;
    }
}

export default WeathermapAPI;