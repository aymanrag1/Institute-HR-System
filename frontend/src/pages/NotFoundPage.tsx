import { Button, Result } from "antd";
import { useNavigate } from "react-router-dom";

export default function NotFoundPage() {
  const navigate = useNavigate();
  return (
    <div style={{
      minHeight: "100vh", display: "flex", alignItems: "center", justifyContent: "center",
    }}>
      <Result
        status="404"
        title="404"
        subTitle="الصفحة غير موجودة"
        extra={
          <Button type="primary" onClick={() => navigate("/dashboard")}>
            العودة للرئيسية
          </Button>
        }
      />
    </div>
  );
}
