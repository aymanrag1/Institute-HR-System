import { useEffect, useState } from "react";
import {
  Table, Button, Space, Modal, Form, Input, Select,
  message, Popconfirm, Typography, Switch, Tag,
} from "antd";
import { PlusOutlined, EditOutlined, DeleteOutlined } from "@ant-design/icons";
import { useTranslation } from "react-i18next";
import { employeesApi, departmentsApi } from "../../api/employees";
import { usePermission } from "../../hooks/usePermission";
import type { User, Department } from "../../types";

const { Title } = Typography;
const { Option } = Select;

const ROLES = ["employee", "manager", "hr_manager", "dean", "admin"];

export default function EmployeesPage() {
  const { t } = useTranslation();
  const { can } = usePermission();

  const [users, setUsers]         = useState<User[]>([]);
  const [departments, setDepts]   = useState<Department[]>([]);
  const [managers, setManagers]   = useState<User[]>([]);
  const [loading, setLoading]     = useState(false);
  const [total, setTotal]         = useState(0);
  const [page, setPage]           = useState(1);
  const [editTarget, setEdit]     = useState<User | null>(null);
  const [showModal, setModal]     = useState(false);
  const [submitting, setSub]      = useState(false);

  const [form] = Form.useForm();

  const fetchAll = async (p = page) => {
    setLoading(true);
    try {
      const [uRes, dRes] = await Promise.all([
        employeesApi.list({ page: String(p) }),
        departmentsApi.list({ page_size: "200" }),
      ]);
      setUsers(uRes.data.results);
      setTotal(uRes.data.count);
      setDepts(dRes.data.results);
      setManagers(uRes.data.results.filter((u) => ["manager", "hr_manager", "dean"].includes(u.role)));
    } catch { message.error("Failed to load"); }
    finally { setLoading(false); }
  };

  useEffect(() => { fetchAll(1); }, []);

  const openCreate = () => { setEdit(null); form.resetFields(); setModal(true); };
  const openEdit   = (u: User) => {
    setEdit(u);
    form.setFieldsValue({ ...u, department: u.department, direct_manager: u.direct_manager });
    setModal(true);
  };

  const handleSave = async (values: any) => {
    setSub(true);
    try {
      if (editTarget)
        await employeesApi.update(editTarget.id, values);
      else
        await employeesApi.create(values);
      message.success(editTarget ? "Updated" : "Created");
      setModal(false);
      fetchAll(1);
    } catch (e: any) {
      const err = e.response?.data;
      message.error(err?.email?.[0] || err?.detail || "Error");
    } finally { setSub(false); }
  };

  const handleDelete = async (id: string) => {
    try {
      await employeesApi.delete(id);
      message.success("Deleted");
      fetchAll();
    } catch { message.error("Error"); }
  };

  const columns = [
    { title: "الاسم (ع)",    dataIndex: "full_name_ar",  key: "ar" },
    { title: "الاسم (En)",   dataIndex: "full_name_en",  key: "en" },
    { title: "البريد",       dataIndex: "email",         key: "email" },
    {
      title: "الدور", key: "role",
      render: (_: any, u: User) => (
        <Tag color={{ admin: "red", dean: "purple", hr_manager: "blue", manager: "orange", employee: "default" }[u.role] || "default"}>
          {u.role}
        </Tag>
      ),
    },
    { title: "القسم", key: "dept",
      render: (_: any, u: User) => u.department_name?.ar || "—",
    },
    {
      title: "نشط", key: "active",
      render: (_: any, u: User) => <Switch checked={u.is_active} disabled size="small" />,
    },
    ...(can("users", "edit") ? [{
      title: t("common.actions"), key: "actions",
      render: (_: any, u: User) => (
        <Space size="small">
          <Button size="small" icon={<EditOutlined />} onClick={() => openEdit(u)} />
          {can("users", "delete") && (
            <Popconfirm title="حذف هذا المستخدم؟" onConfirm={() => handleDelete(u.id)}>
              <Button size="small" danger icon={<DeleteOutlined />} />
            </Popconfirm>
          )}
        </Space>
      ),
    }] : []),
  ];

  return (
    <div>
      <div style={{ display: "flex", justifyContent: "space-between", marginBottom: 20 }}>
        <Title level={4} style={{ margin: 0, color: "#003366" }}>إدارة الموظفين</Title>
        {can("users", "create") && (
          <Button type="primary" icon={<PlusOutlined />} onClick={openCreate}>موظف جديد</Button>
        )}
      </div>

      <Table
        dataSource={users} columns={columns} rowKey="id" loading={loading}
        pagination={{ total, pageSize: 20, current: page, onChange: (p) => { setPage(p); fetchAll(p); } }}
        scroll={{ x: true }}
      />

      <Modal
        title={editTarget ? "تعديل موظف" : "موظف جديد"}
        open={showModal}
        onCancel={() => setModal(false)}
        onOk={() => form.submit()}
        confirmLoading={submitting}
        okText={t("common.save")}
        cancelText={t("common.cancel")}
        width={640}
      >
        <Form form={form} layout="vertical" onFinish={handleSave}>
          <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: "0 16px" }}>
            <Form.Item name="first_name_ar" label="الاسم الأول (ع)" rules={[{ required: true }]}>
              <Input />
            </Form.Item>
            <Form.Item name="first_name_en" label="First Name (En)" rules={[{ required: true }]}>
              <Input />
            </Form.Item>
            <Form.Item name="last_name_ar" label="الاسم الأخير (ع)" rules={[{ required: true }]}>
              <Input />
            </Form.Item>
            <Form.Item name="last_name_en" label="Last Name (En)" rules={[{ required: true }]}>
              <Input />
            </Form.Item>
          </div>

          <Form.Item name="email" label="البريد الإلكتروني" rules={[{ required: true }, { type: "email" }]}>
            <Input />
          </Form.Item>
          <Form.Item name="phone" label="الهاتف">
            <Input />
          </Form.Item>
          <Form.Item name="national_id" label="الرقم الوطني">
            <Input />
          </Form.Item>

          <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: "0 16px" }}>
            <Form.Item name="role" label="الدور" rules={[{ required: true }]}>
              <Select>
                {ROLES.map((r) => <Option key={r} value={r}>{r}</Option>)}
              </Select>
            </Form.Item>
            <Form.Item name="department" label="القسم">
              <Select allowClear>
                {departments.map((d) => <Option key={d.id} value={d.id}>{d.name_ar}</Option>)}
              </Select>
            </Form.Item>
          </div>

          <Form.Item name="direct_manager" label="المدير المباشر">
            <Select allowClear>
              {managers.map((m) => <Option key={m.id} value={m.id}>{m.full_name_ar}</Option>)}
            </Select>
          </Form.Item>

          {!editTarget && (
            <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: "0 16px" }}>
              <Form.Item name="password" label="كلمة المرور" rules={[{ required: true, min: 8 }]}>
                <Input.Password />
              </Form.Item>
              <Form.Item
                name="password2" label="تأكيد كلمة المرور"
                rules={[
                  { required: true },
                  ({ getFieldValue }) => ({
                    validator(_, v) {
                      return v === getFieldValue("password")
                        ? Promise.resolve()
                        : Promise.reject("Passwords don't match");
                    },
                  }),
                ]}
              >
                <Input.Password />
              </Form.Item>
            </div>
          )}

          <Form.Item name="is_active" label="نشط" valuePropName="checked">
            <Switch />
          </Form.Item>
        </Form>
      </Modal>
    </div>
  );
}
