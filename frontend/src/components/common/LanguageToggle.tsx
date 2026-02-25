import { Button } from "antd";
import { useTranslation } from "react-i18next";
import { toggleLanguage } from "../../i18n";

export default function LanguageToggle() {
  const { i18n } = useTranslation();
  return (
    <Button size="small" onClick={toggleLanguage} style={{ fontFamily: "Cairo, sans-serif" }}>
      {i18n.language === "ar" ? "EN" : "ع"}
    </Button>
  );
}
