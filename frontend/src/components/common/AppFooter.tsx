import { Layout } from "antd";
import { useTranslation } from "react-i18next";

const { Footer } = Layout;

export default function AppFooter() {
  const { t } = useTranslation();
  return (
    <Footer
      style={{
        textAlign: "center",
        background: "#001529",
        color: "#888",
        fontSize: 12,
        padding: "12px 24px",
      }}
    >
      {t("footer.version")} &nbsp;|&nbsp; {t("footer.developed_by")} &nbsp;|&nbsp;
      {t("footer.phone")}
    </Footer>
  );
}
