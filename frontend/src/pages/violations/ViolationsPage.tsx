import { useEffect, useState } from "react";
import {
  Table, Button, Space, Modal, Form, Input, Select,
  DatePicker, message, Typography, Tooltip, Tag,
} from "antd";
import { PlusOutlined, CheckOutlined, CloseOutlined } from "@ant-design/icons";
import { useTranslation } from "react-i18next";
import { violationsApi } from "../../api/violations";
import { employeesApi } from "../../api/employees";
import { usePermission } from "../../hooks/usePermission";
import { useAuthStore } from "../../store/authStore";
import StatusBadge from "../../components/common/StatusBadge";
import type { Violation, User } from "../../types";

const { Title } = Typography;
const { Option } = Select;
const { TextArea } = Input;

const SEVERITY_COLOR: Record<string, string> = {
  minor: "default", moderate: "warning", major: "orange", critical: "error",
};

export default function ViolationsPage() {
  const { t } = useTranslation();
  const { user } = useAuthStore();
  const { can } = usePermission();

  const [records, setRecords]   = useState<Violation[]>([]);
  const [employees, setEmp]     = useState<User[]>([]);
  const [loading, setLoading]   = useState(false);
  const [total, setTotal]       = useState(0);
  const [page, setPage]         = useState(1);
  const [createModal, setCreate] = useState(false);
  const [actionModal, setAction] = useState<{ v: Violation; act: "approve" | "reject" } | null>(null);
  const [submitting, setSub]    = useState(false);

  const [createForm] = Form.useForm();
  const [actionForm] = Form.useForm();

  const fetch = async (p = page) => {
    setLoading(true);
    try {
      const { data } = await violationsApi.list({ page: String(p) });
      setRecords(data.results);
      setTotal(data.count);
    } catch { message.error("Failed to load"); }
    finally { setLoading(false); }
  };

  useEffect(() => {
    fetch(1);
    if (can("violations", "create")) {
      employeesApi.list({ page_size: "200" }).then(({ data }) => setEmp(data.results));
    }
  }, []);

  const handleCreate = async (values: any) => {
    setSub(true);
    try {
      await violationsApi.create({
        ...values,
        incident_date: values.incident_date.format("YYYY-MM-DD"),
      });
      message.success("Violation created");
      setCreate(false);
      createForm.resetFields();
      fetch(1);
    } catch (e: any) {
      message.error(e.response?.data?.detail || "Error");
    } finally { setSub(false); }
  };

  const handleAction = async (values: { notes?: string }) => {
    if (!actionModal) return;
    setSub(true);
    try {
      if (actionModal.act === "approve")
        await violationsApi.approve(actionModal.v.id, values.notes);
      else
        await violationsApi.reject(actionModal.v.id, values.notes!);
      message.success("Done");
      setAction(null);
      actionForm.resetFields();
      fetch();
    } catch (e: any) {
      message.error(e.response?.data?.detail || "Error");
    } finally { setSub(false); }
  };

  const columns = [
    { title: "الموظف",              dataIndex: "employee_name",    key: "emp" },
    { title: t("violations.type"),   dataIndex: "type_display",    key: "type" },
    {
      title: t("violations.severity"), key: "sev",
      render: (_: any, r: Violation) => (
        <Tag color={SEVERITY_COLOR[r.severity]}>{r.severity_display}</Tag>
      ),
    },
    { title: t("violations.incident_date"), dataIndex: "incident_date", key: "date" },
    {
      title: t("leaves.status"), key: "status",
      render: (_: any, r: Violation) => (
        <StatusBadge status={r.status} label={r.status_display} />
      ),
    },
    {
      title: t("common.actions"), key: "actions",
      render: (_: any, rec: Violation) => (
        <Space size="small">
          {can("violations", "approve") && rec.status === "pending_dean" && (
            <>
              <Tooltip title="اعتماد">
                <Button size="small" type="primary" icon={<CheckOutlined />}
                  onClick={() => setAction({ v: rec, act: "approve" })} />
              </Tooltip>
              <Tooltip title="رفض">
                <Button size="small" danger icon={<CloseOutlined />}
                  onClick={() => setAction({ v: rec, act: "reject" })} />
              </Tooltip>
            </>
          )}
        </Space>
      ),
    },
  ];

  return (
    <div>
      <div style={{ display: "flex", justifyContent: "space-between", marginBottom: 20 }}>
        <Title level={4} style={{ margin: 0, color: "#003366" }}>{t("violations.title")}</Title>
        {can("violations", "create") && (
          <Button type="primary" icon={<PlusOutlined />} onClick={() => setCreate(true)}>
            {t("violations.new")}
          </Button>
        )}
      </div>

      <Table
        dataSource={records} columns={columns} rowKey="id" loading={loading}
        pagination={{ total, pageSize: 20, current: page, onChange: (p) => { setPage(p); fetch(p); } }}
        scroll={{ x: true }}
      />

      <Modal
        title={t("violations.new")} open={createModal}
        onCancel={() => { setCreate(false); createForm.resetFields(); }}
        onOk={() => createForm.submit()} confirmLoading={submitting}
        okText={t("common.save")} cancelText={t("common.cancel")}
      >
        <Form form={createForm} layout="vertical" onFinish={handleCreate}>
          <Form.Item name="employee" label="الموظف" rules={[{ required: true }]}>
            <Select showSearch optionFilterProp="children" placeholder="اختر الموظف">
              {employees.map((e) => (
                <Option key={e.id} value={e.id}>{e.full_name_ar}</Option>
              ))}
            </Select>
          </Form.Item>
          <Form.Item name="type" label={t("violations.type")} rules={[{ required: true }]}>
            <Select>
              {["attendance","behavior","policy","performance","other"].map((v) => (
                <Option key={v} value={v}>{v}</Option>
              ))}
            </Select>
          </Form.Item>
          <Form.Item name="severity" label={t("violations.severity")} rules={[{ required: true }]}>
            <Select>
              {["minor","moderate","major","critical"].map((s) => (
                <Option key={s} value={s}>{t(`violations.severities.${s}`)}</Option>
              ))}
            </Select>
          </Form.Item>
          <Form.Item name="incident_date" label={t("violations.incident_date")} rules={[{ required: true }]}>
            <DatePicker style={{ width: "100%" }} />
          </Form.Item>
          <Form.Item name="description" label={t("violations.description")} rules={[{ required: true }]}>
            <TextArea rows={3} />
          </Form.Item>
          <Form.Item name="penalty" label={t("violations.penalty")}>
            <TextArea rows={2} />
          </Form.Item>
        </Form>
      </Modal>

      <Modal
        title={actionModal?.act === "approve" ? "اعتماد المخالفة" : "رفض المخالفة"}
        open={!!actionModal}
        onCancel={() => { setAction(null); actionForm.resetFields(); }}
        onOk={() => actionForm.submit()} confirmLoading={submitting}
        okButtonProps={{ danger: actionModal?.act === "reject" }}
        okText={t("common.save")} cancelText={t("common.cancel")}
      >
        <Form form={actionForm} layout="vertical" onFinish={handleAction}>
          <Form.Item
            name="notes" label={t("common.notes")}
            rules={actionModal?.act === "reject" ? [{ required: true }] : []}
          >
            <TextArea rows={3} />
          </Form.Item>
        </Form>
      </Modal>
    </div>
  );
}
