import i18n from '../i18n';

const messageKeys = new Map();

function indexMessages(messages, prefix = '') {
    for (const [name, value] of Object.entries(messages)) {
        const key = prefix ? `${prefix}.${name}` : name;
        if (typeof value === 'object') indexMessages(value, key);
        else if (typeof value === 'string') {
            const text = value.replace(/\{'([^']*)'\}/g, '$1');
            if (!messageKeys.has(text)) messageKeys.set(text, key);
        }
    }
}

indexMessages(i18n.global.getLocaleMessage('id'));
indexMessages(i18n.global.getLocaleMessage('en'));

// Also handles labels supplied by static option lists and existing notifications.
export function uiText(value, params) {
    if (typeof value !== 'string') return value;
    const key = messageKeys.get(value) || (i18n.global.te(value) ? value : null);
    return key ? i18n.global.t(key, params || {}) : value;
}

export function uiLocale() {
    return i18n.global.locale.value === 'id' ? 'id-ID' : 'en-US';
}

export function displayDate(value, options) {
    if (!value) return '-';
    return new Date(value).toLocaleDateString(uiLocale(), options);
}
