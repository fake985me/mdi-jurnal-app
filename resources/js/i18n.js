import { createI18n } from 'vue-i18n'
import en from './locales/en.json'
import id from './locales/id.json'

// Use Indonesian by default, while retaining an explicitly selected language.
function getDefaultLocale() {
    const stored = localStorage.getItem('locale')
    if (stored === 'id' || stored === 'en') return stored
    return 'id'
}

const i18n = createI18n({
    legacy: false, // Use Composition API mode
    locale: getDefaultLocale(),
    fallbackLocale: 'en',
    messages: {
        en,
        id
    }
})

export default i18n
