import { useEffect, useState } from "react";
import {
  Table, Button, Space, Modal, Form, Input, DatePicker, TimePicker,
  message, Popconfirm, Typography, Tooltip,
} from "antd";
import { PlusOutlined, CheckOutlined, CloseOutlined } from "@ant-design/icons";
import { useTranslation } from "react-i18next";
import { overtimeApi } from "../../api/overtime";
import { useAuthStore } from "../../store/authStore";
import { usePermission } from "../../hooks/usePermission";
import StatusBadge from "../../components/common/StatusBadge";
import type { OvertimeRequest } from "../../types";

const { Title } = Typography;
const { TextArea } = Input;

export default function OvertimePage() {
  const { t } = useTranslation();
  const { user } = useAuthStore();
  const { can } = usePermission();

  const [records, setRecords]   = useState<OvertimeRequest[]>([]);
  const [loading, setLoading]   = useState(false);
  const [total, setTotal]       = useState(0);
  const [page, setPage]         = useState(1);
  const [createModal, setCreate] = useState(false);
  const [actionModal, setAction] = useState<{ rec: OvertimeRequest; action: "approve" | "reject" } | null>(null);
  const [submitting, setSub]    = useState(false);

  const [createForm] = Form.useForm();
  const [actionForm] = Form.useForm();

  const fetch = async (p = page) => {
    setLoading(true);
    try {
      const { data } = await overtimeApi.list({ page: String(p) });
      setRecords(data.results);
      setTotal(data.count);
    } catch { message.error("Failed to load"); }
    finally { setLoading(false); }
  };

  useEffect(() => { fetch(1); }, []);

  const handleCreate = async (values: any) => {
    setSub(true);
    try {
      await overtimeApi.create({
        date:       values.date.format("YYYY-MM-DD"),
        start_time: values.start_time.format("HH:mm"),
        end_time:   values.end_time.format("HH:mm"),
        reason:     values.reason,
      });
      message.success("Overtime request created");
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
      if (actionModal.action === "approve")
        await overtimeApi.approve(actionModal.rec.id, values.notes);
      else
        await overtimeApi.reject(actionModal.rec.id, values.notes!);
      message.success("Done");
      setAction(null);
      actionForm.resetFields();
      fetch();
    } catch (e: any) {
      message.error(e.response?.data?.detail || "Error");
    } finally { setSub(false); }
  };

  const canApprove = (rec: OvertimeRequest) => {
    if (!user) return false;
    const map: Record<string, string> = { pending_manager: "manager", pending_hr: "hr_manager" };
    return map[rec.status] === user.role;
  };

  const columns = [
    { title: t("overtime.date"),        dataIndex: "date",           key: "date" },
    { title: t("overtime.start_time"),  dataIndex: "start_time",     key: "start" },
    { title: t("overtime.end_time"),    dataIndex: "end_time",       key: "end" },
    {
      title: t("overtime.total_hours"), dataIndex: "total_hours", key: "hours",
      render: (v: string) => `${v} ساعة`,
    },
    {
      title: t("leaves.status"), key: "status",
      render: (_: any, r: OvertimeRequest) => (
        <StatusBadge status={r.status} label={r.status_display} />
      ),
    },
    {
      title: t("common.actions"), key: "actions",
      render: (_: any, rec: OvertimeRequest) => (
        <Space size="small">
          {canApprove(rec) && (
            <>
              <Tooltip title={t("leaves.approve")}>
                <Button size="small" type="primary" icon={<CheckOutlined />}
                  onClick={() => setAction({ rec, action: "approve" })} />
              </Tooltip>
              <Tooltip title={t("leaves.reject")}>
                <Button size="small" danger icon={<CloseOutlined />}
                  onClick={() => setAction({ rec, action: "reject" })} />
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
        <Title level={4} style={{ margin: 0, color: "#003366" }}>{t("overtime.title")}</Title>
        {can("overtime", "create") && (
          <Button type="primary" icon={<PlusOutlined />} onClick={() => setCreate(true)}>
            {t("overtime.new")}
          </Button>
        )}
      </div>

      <Table
        dataSource={records} columns={columns} rowKey="id" loading={loading}
        pagination={{ total, pageSize: 20, current: page, onChange: (p) => { setPage(p); fetch(p); } }}
        scroll={{ x: true }}
      />

      <Modal
        title={t("overtime.new")} open={createModal}
        onCancel={() => { setCreate(false); createForm.resetFields(); }}
        onOk={() => createForm.submit()} confirmLoading={submitting}
        okText={t("common.save")} cancelText={t("common.cancel")}
      >
        <Form form={createForm} layout="vertical" onFinish={handleCreate}>
          <Form.Item name="date" label={t("overtime.date")} rules={[{ required: true }]}>
            <DatePicker style={{ width: "100%" }} />
          </Form.Item>
          <Form.Item name="start_time" label={t("overtime.start_time")} rules={[{ required: true }]}>
            <TimePicker format="HH:mm" style={{ width: "100%" }} />
          </Form.Item>
          <Form.Item name="end_time" label={t("overtime.end_time")} rules={[{ required: true }]}>
            <TimePicker format="HH:mm" style={{ width: "100%" }} />
          </Form.Item>
          <Form.Item name="reason" label={t("overtime.reason")} rules={[{ required: true }]}>
            <TextArea rows={3} />
          </Form.Item>
        </Form>
      </Modal>

      <Modal
        title={actionModal?.action === "approve" ? t("leaves.approve") : t("leaves.reject")}
        open={!!actionModal}
        onCancel={() => { setAction(null); actionForm.resetFields(); }}
        onOk={() => actionForm.submit()} confirmLoading={submitting}
        okText={t("common.save")} cancelText={t("common.cancel")}
        okButtonProps={{ danger: actionModal?.action === "reject" }}
      >
        <Form form={actionForm} layout="vertical" onFinish={handleAction}>
          <Form.Item
            name="notes" label={t("common.notes")}
            rules={actionModal?.action === "reject" ? [{ required: true }] : []}
          >
            <TextArea rows={3} />
          </Form.Item>
        </Form>
      </Modal>
    </div>
  );
}
