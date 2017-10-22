import axios from 'axios';

export const VIEW_ALL_FULL = 'VIEW_ALL_FULL';
export const VIEW_FIRST_FULL = 'VIEW_FIRST_FULL';
export const VIEW_THUMBS = 'VIEW_THUMBS';

export function viewAllFull() {
    return {type: VIEW_ALL_FULL}
}


export function viewFirstFull() {
    return {type: VIEW_FIRST_FULL}
}

export function viewThumbs() {
    return {type: VIEW_THUMBS}
}
