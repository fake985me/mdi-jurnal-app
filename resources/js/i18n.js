import { createI18n } from 'vue-i18n'
import en from './locales/en.json'
import id from './locales/id.json'

// Get stored language or detect from browser
function getDefaultLocale() {
    const stored = localStorage.getItem('locale')
    if (stored) return stored

    // Detect browser language
    const browserLang = navigator.language.split('-')[0]
    return browserLang === 'id' ? 'id' : 'en'
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
