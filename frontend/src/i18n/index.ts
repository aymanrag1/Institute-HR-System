import i18n from "i18next";
import { initReactI18next } from "react-i18next";
import ar from "./ar.json";
import en from "./en.json";

const savedLang = localStorage.getItem("lang") || "ar";

i18n.use(initReactI18next).init({
  resources: {
    ar: { translation: ar },
    en: { translation: en },
  },
  lng: savedLang,
  fallbackLng: "en",
  interpolation: { escapeValue: false },
});

// Set initial document direction
document.documentElement.dir  = savedLang === "ar" ? "rtl" : "ltr";
document.documentElement.lang = savedLang;

export const toggleLanguage = () => {
  const newLang = i18n.language === "ar" ? "en" : "ar";
  i18n.changeLanguage(newLang);
  localStorage.setItem("lang", newLang);
  document.documentElement.dir  = newLang === "ar" ? "rtl" : "ltr";
  document.documentElement.lang = newLang;
};

export default i18n;
