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

        const config = {headers: {'Content-Type': 'multipart/form-data', withCredentials: true}};


        let data = new FormData();
        data.append('name', group_name);
        data.append(window.csrfMagicName, window.csrfMagicToken);

        return axios.post(source_url, data, config)
    }


    removeGroup(groupId) {
        const source_url = this.base_url + "group_delete";
        console.log(`API Removing group with id ${groupId} via ${source_url}`)

        const config = {headers: {'Content-Type': 'multipart/form-data', withCredentials: true}};


        let data = new FormData();
        data.append('id', groupId);
        data.append(window.csrfMagicName, window.csrfMagicToken);

        return axios.post(source_url, data, config)
    }

    addMaps(maps, groupId) {
        const source_url = this.base_url + "maps_add";
        const stringMaps = JSON.stringify(maps);
        console.log(`API Adding ${stringMaps} via ${source_url}`);

        const config = {headers: {'Content-Type': 'multipart/form-data', withCredentials: true}};


        let data = new FormData();
        data.append('maps', maps);
        data.append('group_id', groupId);
        data.append(window.csrfMagicName, window.csrfMagicToken);

        return axios.post(source_url, data, config)
    }

    removeMap(mapId) {
        const source_url = this.base_url + "delete_map";
        console.log(`API Removing map with id ${mapId} via ${source_url}`);

        const config = {headers: {'Content-Type': 'multipart/form-data', withCredentials: true}};


        let data = new FormData();
        data.append('id', mapId);
        data.append(window.csrfMagicName, window.csrfMagicToken);

        return axios.post(source_url, data, config)
    }

    enableMap(mapId) {
        const source_url = this.base_url + "enable_map";
        console.log(`API enabling map with id ${mapId} via ${source_url}`);

        const config = {headers: {'Content-Type': 'multipart/form-data', withCredentials: true}};


        let data = new FormData();
        data.append('id', mapId);
        data.append(window.csrfMagicName, window.csrfMagicToken);

        return axios.post(source_url, data, config)
    }

    disableMap(mapId) {
        const source_url = this.base_url + "disable_map";
        console.log(`API disabling map with id ${mapId} via ${source_url}`);

        const config = {headers: {'Content-Type': 'multipart/form-data', withCredentials: true}};


        let data = new FormData();
        data.append('id', mapId);
        data.append(window.csrfMagicName, window.csrfMagicToken);

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
