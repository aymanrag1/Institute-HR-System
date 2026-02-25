import { useState } from "react";
import {
  Card, Form, Input, Button, Upload, Image,
  message, Divider, Typography, Space,
} from "antd";
import { UploadOutlined, SaveOutlined } from "@ant-design/icons";
import { useTranslation } from "react-i18next";
import { authApi } from "../../api/auth";
import { useAuthStore } from "../../store/authStore";

const { Title, Text } = Typography;

export default function ProfilePage() {
  const { t }           = useTranslation();
  const { user, setUser } = useAuthStore();
  const [pwLoading, setPw] = useState(false);
  const [sigLoading, setSig] = useState(false);

  const [pwForm] = Form.useForm();

  const handlePasswordChange = async (values: any) => {
    setPw(true);
    try {
      await authApi.changePassword(values.old_password, values.new_password);
      message.success("Password changed successfully");
      pwForm.resetFields();
    } catch (e: any) {
      message.error(e.response?.data?.detail || "Error changing password");
    } finally { setPw(false); }
  };

  const handleSignatureUpload = async (file: File) => {
    setSig(true);
    try {
      const { data } = await authApi.uploadSignature(file);
      // Refresh user info
      const { data: updatedUser } = await authApi.me();
      setUser(updatedUser);
      message.success("Signature uploaded successfully");
    } catch (e: any) {
      message.error(e.response?.data?.detail || "Upload failed");
    } finally { setSig(false); }
    return false;
  };

  return (
    <div style={{ maxWidth: 700, margin: "0 auto" }}>
      <Title level={4} style={{ color: "#003366", marginBottom: 24 }}>الملف الشخصي</Title>

      {/* User Info */}
      <Card style={{ borderRadius: 8, marginBottom: 24 }}>
        <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 16 }}>
          <div>
            <Text type="secondary" style={{ fontSize: 12 }}>الاسم (عربي)</Text>
            <div style={{ fontWeight: 600 }}>{user?.full_name_ar}</div>
          </div>
          <div>
            <Text type="secondary" style={{ fontSize: 12 }}>Name (English)</Text>
            <div style={{ fontWeight: 600 }}>{user?.full_name_en}</div>
          </div>
          <div>
            <Text type="secondary" style={{ fontSize: 12 }}>البريد الإلكتروني</Text>
            <div>{user?.email}</div>
          </div>
          <div>
            <Text type="secondary" style={{ fontSize: 12 }}>الدور</Text>
            <div>{user?.role}</div>
          </div>
          <div>
            <Text type="secondary" style={{ fontSize: 12 }}>القسم</Text>
            <div>{user?.department_name?.ar || "—"}</div>
          </div>
          <div>
            <Text type="secondary" style={{ fontSize: 12 }}>الهاتف</Text>
            <div>{user?.phone || "—"}</div>
          </div>
        </div>
      </Card>

      {/* Signature */}
      <Card title="التوقيع الرقمي" style={{ borderRadius: 8, marginBottom: 24 }}>
        <Space direction="vertical" style={{ width: "100%" }}>
          {user?.signature_url && (
            <div>
              <Text type="secondary" style={{ fontSize: 12 }}>التوقيع الحالي:</Text>
              <br />
              <Image
                src={user.signature_url}
                height={80}
                style={{ border: "1px solid #eee", padding: 8, marginTop: 8 }}
              />
            </div>
          )}
          <Upload
            accept="image/*"
            showUploadList={false}
            beforeUpload={handleSignatureUpload}
          >
            <Button icon={<UploadOutlined />} loading={sigLoading}>
              {user?.signature_url ? "تحديث التوقيع" : "رفع التوقيع"}
            </Button>
          </Upload>
          <Text type="secondary" style={{ fontSize: 11 }}>
            يُستخدم التوقيع في الموافقة على الطلبات وطباعة المستندات الرسمية
          </Text>
        </Space>
      </Card>

      {/* Change Password */}
      <Card title="تغيير كلمة المرور" style={{ borderRadius: 8 }}>
        <Form
          form={pwForm}
          layout="vertical"
          onFinish={handlePasswordChange}
          style={{ maxWidth: 400 }}
        >
          <Form.Item
            name="old_password"
            label="كلمة المرور الحالية"
            rules={[{ required: true }]}
          >
            <Input.Password />
          </Form.Item>
          <Form.Item
            name="new_password"
            label="كلمة المرور الجديدة"
            rules={[{ required: true }, { min: 8, message: "8 أحرف على الأقل" }]}
          >
            <Input.Password />
          </Form.Item>
          <Form.Item
            name="confirm_password"
            label="تأكيد كلمة المرور"
            rules={[
              { required: true },
              ({ getFieldValue }) => ({
                validator(_, value) {
                  return value === getFieldValue("new_password")
                    ? Promise.resolve()
                    : Promise.reject("كلمتا المرور غير متطابقتين");
                },
              }),
            ]}
          >
            <Input.Password />
          </Form.Item>
          <Form.Item>
            <Button
              type="primary"
              htmlType="submit"
              loading={pwLoading}
              icon={<SaveOutlined />}
            >
              حفظ كلمة المرور
            </Button>
          </Form.Item>
        </Form>
      </Card>
    </div>
  );
}
