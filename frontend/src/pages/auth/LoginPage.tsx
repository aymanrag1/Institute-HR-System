import { useState } from "react";
import { Form, Input, Button, Card, Alert, Typography } from "antd";
import { UserOutlined, LockOutlined } from "@ant-design/icons";
import { useNavigate } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { authApi } from "../../api/auth";
import { useAuthStore } from "../../store/authStore";
import LanguageToggle from "../../components/common/LanguageToggle";

const { Title, Text } = Typography;

export default function LoginPage() {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const { setAuth } = useAuthStore();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const onFinish = async ({ email, password }: { email: string; password: string }) => {
    setLoading(true);
    setError("");
    try {
      const { data } = await authApi.login(email, password);
      setAuth(data.user, data.access, data.refresh);
      navigate("/dashboard");
    } catch (err: any) {
      setError(err.response?.data?.detail || "Login failed. Please check your credentials.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={{
      minHeight: "100vh", background: "linear-gradient(135deg, #001529 0%, #003366 100%)",
      display: "flex", alignItems: "center", justifyContent: "center",
      fontFamily: "Cairo, sans-serif",
    }}>
      <Card
        style={{ width: 420, borderRadius: 12, boxShadow: "0 20px 60px rgba(0,0,0,0.4)" }}
        bodyStyle={{ padding: "40px 36px" }}
      >
        <div style={{ textAlign: "center", marginBottom: 32 }}>
          <Title level={3} style={{ color: "#003366", margin: 0 }}>
            {t("app.institute")}
          </Title>
          <Text type="secondary" style={{ fontSize: 13 }}>
            {t("app.name")} — {t("app.version")}
          </Text>
        </div>

        {error && (
          <Alert
            message={error}
            type="error"
            showIcon
            style={{ marginBottom: 20 }}
            closable
            onClose={() => setError("")}
          />
        )}

        <Form layout="vertical" onFinish={onFinish} size="large">
          <Form.Item
            name="email"
            label={t("auth.email")}
            rules={[
              { required: true, message: "Email is required" },
              { type: "email", message: "Invalid email" },
            ]}
          >
            <Input prefix={<UserOutlined />} placeholder="user@rsyi.edu.eg" />
          </Form.Item>

          <Form.Item
            name="password"
            label={t("auth.password")}
            rules={[{ required: true, message: "Password is required" }]}
          >
            <Input.Password prefix={<LockOutlined />} placeholder="••••••••" />
          </Form.Item>

          <Form.Item style={{ marginBottom: 8 }}>
            <Button
              type="primary"
              htmlType="submit"
              loading={loading}
              block
              style={{ background: "#003366", borderColor: "#003366", height: 44, fontSize: 15 }}
            >
              {t("auth.login_btn")}
            </Button>
          </Form.Item>
        </Form>

        <div style={{ textAlign: "center", marginTop: 16 }}>
          <LanguageToggle />
        </div>

        <div style={{ textAlign: "center", marginTop: 20, fontSize: 11, color: "#bbb" }}>
          Developed by AYMAN RAGAB | +201159230034
        </div>
      </Card>
    </div>
  );
}
