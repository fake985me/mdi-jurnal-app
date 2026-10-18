import i18n from '../i18n';
import { uiText } from './uiText';

// Translate display values while preserving the original values used by forms and APIs.
export function displayLabel(value) {
    if (value === null || value === undefined || value === '') return '-';
    const key = `labels.${String(value).trim().toLowerCase().replace(/[\s-]+/g, '_')}`;
    return i18n.global.te(key) ? i18n.global.t(key) : uiText(value);
}
