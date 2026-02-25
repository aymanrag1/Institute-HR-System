import { useEffect, useState } from "react";
import {
  Table, Button, Space, Modal, Form, Input, Select, DatePicker,
  message, Popconfirm, Typography, Tooltip,
} from "antd";
import {
  PlusOutlined, FilePdfOutlined, EyeOutlined,
  CheckOutlined, CloseOutlined,
} from "@ant-design/icons";
import { useTranslation } from "react-i18next";
import dayjs from "dayjs";
import { leavesApi } from "../../api/leaves";
import { useAuthStore } from "../../store/authStore";
import { usePermission } from "../../hooks/usePermission";
import StatusBadge from "../../components/common/StatusBadge";
import LeaveTimeline from "./LeaveTimeline";
import type { LeaveRequest } from "../../types";

const { Title } = Typography;
const { Option } = Select;
const { TextArea } = Input;

export default function LeavesPage() {
  const { t } = useTranslation();
  const { user } = useAuthStore();
  const { can } = usePermission();

  const [leaves, setLeaves] = useState<LeaveRequest[]>([]);
  const [loading, setLoading] = useState(false);
  const [total, setTotal] = useState(0);
  const [page, setPage] = useState(1);

  const [createModal, setCreateModal] = useState(false);
  const [timelineModal, setTimelineModal] = useState<LeaveRequest | null>(null);
  const [actionModal, setActionModal] = useState<{ leave: LeaveRequest; action: "approve" | "reject" | "return" } | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const [createForm] = Form.useForm();
  const [actionForm] = Form.useForm();

  const fetchLeaves = async (p = page) => {
    setLoading(true);
    try {
      const { data } = await leavesApi.list({ page: String(p) });
      setLeaves(data.results);
      setTotal(data.count);
    } catch { message.error("Failed to load leave requests"); }
    finally { setLoading(false); }
  };

  useEffect(() => { fetchLeaves(1); }, []);

  const handleCreate = async (values: any) => {
    setSubmitting(true);
    try {
      await leavesApi.create({
        leave_type:  values.leave_type,
        start_date:  values.start_date.format("YYYY-MM-DD"),
        end_date:    values.end_date.format("YYYY-MM-DD"),
        reason:      values.reason,
      });
      message.success("Leave request created");
      setCreateModal(false);
      createForm.resetFields();
      fetchLeaves(1);
    } catch (e: any) {
      message.error(e.response?.data?.detail || "Error creating request");
    } finally { setSubmitting(false); }
  };

  const handleSubmit = async (id: string) => {
    try {
      await leavesApi.submit(id);
      message.success("Request submitted");
      fetchLeaves();
    } catch (e: any) {
      message.error(e.response?.data?.detail || "Error");
    }
  };

  const handleAction = async (values: { notes?: string }) => {
    if (!actionModal) return;
    setSubmitting(true);
    try {
      const { leave, action } = actionModal;
      if (action === "approve") await leavesApi.approve(leave.id, values.notes);
      else if (action === "reject") await leavesApi.reject(leave.id, values.notes!);
      else await leavesApi.returnRequest(leave.id, values.notes);
      message.success("Action completed");
      setActionModal(null);
      actionForm.resetFields();
      fetchLeaves();
    } catch (e: any) {
      message.error(e.response?.data?.detail || "Error");
    } finally { setSubmitting(false); }
  };

  const downloadPDF = (id: string) => {
    const token = localStorage.getItem("access_token");
    const url = leavesApi.pdfUrl(id);
    const a = document.createElement("a");
    a.href = url;
    a.target = "_blank";
    a.click();
  };

  const canApprove = (leave: LeaveRequest) => {
    if (!user) return false;
    const roleMap: Record<string, string> = {
      pending_manager: "manager",
      pending_hr: "hr_manager",
      pending_dean: "dean",
    };
    return roleMap[leave.status] === user.role;
  };

  const columns = [
    {
      title: t("leaves.type"),
      dataIndex: "leave_type_display",
      key: "type",
    },
    {
      title: t("leaves.start_date"),
      dataIndex: "start_date",
      key: "start",
    },
    {
      title: t("leaves.end_date"),
      dataIndex: "end_date",
      key: "end",
    },
    {
      title: t("leaves.total_days"),
      dataIndex: "total_days",
      key: "days",
      render: (v: number) => `${v} يوم`,
    },
    {
      title: t("leaves.status"),
      dataIndex: "status",
      key: "status",
      render: (_: any, r: LeaveRequest) => (
        <StatusBadge status={r.status} label={r.status_display} />
      ),
    },
    {
      title: t("common.actions"),
      key: "actions",
      render: (_: any, record: LeaveRequest) => (
        <Space size="small" wrap>
          {/* Submit draft */}
          {record.status === "draft" && record.employee === user?.id && (
            <Popconfirm title="تقديم الطلب؟" onConfirm={() => handleSubmit(record.id)}>
              <Button size="small" type="primary">{t("leaves.submit")}</Button>
            </Popconfirm>
          )}

          {/* Approve / Reject / Return */}
          {canApprove(record) && (
            <>
              <Tooltip title={t("leaves.approve")}>
                <Button
                  size="small" type="primary" icon={<CheckOutlined />}
                  onClick={() => setActionModal({ leave: record, action: "approve" })}
                />
              </Tooltip>
              <Tooltip title={t("leaves.reject")}>
                <Button
                  size="small" danger icon={<CloseOutlined />}
                  onClick={() => setActionModal({ leave: record, action: "reject" })}
                />
              </Tooltip>
              <Tooltip title={t("leaves.return")}>
                <Button
                  size="small" icon={<CloseOutlined />}
                  onClick={() => setActionModal({ leave: record, action: "return" })}
                />
              </Tooltip>
            </>
          )}

          {/* Timeline */}
          <Tooltip title={t("leaves.timeline")}>
            <Button
              size="small" icon={<EyeOutlined />}
              onClick={() => setTimelineModal(record)}
            />
          </Tooltip>

          {/* PDF */}
          {record.status === "approved" && (
            <Tooltip title={t("leaves.download_pdf")}>
              <Button
                size="small" icon={<FilePdfOutlined />}
                onClick={() => downloadPDF(record.id)}
              />
            </Tooltip>
          )}
        </Space>
      ),
    },
  ];

  return (
    <div>
      <div style={{ display: "flex", justifyContent: "space-between", marginBottom: 20 }}>
        <Title level={4} style={{ margin: 0, color: "#003366" }}>{t("leaves.title")}</Title>
        {can("leaves", "create") && (
          <Button type="primary" icon={<PlusOutlined />} onClick={() => setCreateModal(true)}>
            {t("leaves.new")}
          </Button>
        )}
      </div>

      <Table
        dataSource={leaves}
        columns={columns}
        rowKey="id"
        loading={loading}
        pagination={{
          total, pageSize: 20, current: page,
          onChange: (p) => { setPage(p); fetchLeaves(p); },
          showTotal: (t) => `الإجمالي: ${t}`,
        }}
        scroll={{ x: true }}
      />

      {/* Create Modal */}
      <Modal
        title={t("leaves.new")}
        open={createModal}
        onCancel={() => { setCreateModal(false); createForm.resetFields(); }}
        onOk={() => createForm.submit()}
        confirmLoading={submitting}
        okText={t("common.save")}
        cancelText={t("common.cancel")}
      >
        <Form form={createForm} layout="vertical" onFinish={handleCreate}>
          <Form.Item name="leave_type" label={t("leaves.type")} rules={[{ required: true }]}>
            <Select placeholder="اختر نوع الإجازة">
              {["annual","sick","emergency","unpaid","other"].map((type) => (
                <Option key={type} value={type}>{t(`leaves.types.${type}`)}</Option>
              ))}
            </Select>
          </Form.Item>
          <Form.Item name="start_date" label={t("leaves.start_date")} rules={[{ required: true }]}>
            <DatePicker style={{ width: "100%" }} />
          </Form.Item>
          <Form.Item
            name="end_date"
            label={t("leaves.end_date")}
            rules={[
              { required: true },
              ({ getFieldValue }) => ({
                validator(_, value) {
                  if (!value || !getFieldValue("start_date") || value >= getFieldValue("start_date")) {
                    return Promise.resolve();
                  }
                  return Promise.reject("End date must be after start date");
                },
              }),
            ]}
          >
            <DatePicker style={{ width: "100%" }} />
          </Form.Item>
          <Form.Item name="reason" label={t("leaves.reason")}>
            <TextArea rows={3} />
          </Form.Item>
        </Form>
      </Modal>

      {/* Action Modal */}
      <Modal
        title={
          actionModal?.action === "approve" ? t("leaves.approve") :
          actionModal?.action === "reject"  ? t("leaves.reject") :
          t("leaves.return")
        }
        open={!!actionModal}
        onCancel={() => { setActionModal(null); actionForm.resetFields(); }}
        onOk={() => actionForm.submit()}
        confirmLoading={submitting}
        okText={t("common.save")}
        cancelText={t("common.cancel")}
        okButtonProps={{
          danger: actionModal?.action === "reject",
          type: actionModal?.action === "approve" ? "primary" : "default",
        }}
      >
        <Form form={actionForm} layout="vertical" onFinish={handleAction}>
          <Form.Item
            name="notes"
            label={t("common.notes")}
            rules={actionModal?.action === "reject" ? [{ required: true, message: "Notes required for rejection" }] : []}
          >
            <TextArea rows={3} />
          </Form.Item>
        </Form>
      </Modal>

      {/* Timeline Modal */}
      <Modal
        title={t("leaves.timeline")}
        open={!!timelineModal}
        onCancel={() => setTimelineModal(null)}
        footer={null}
        width={600}
      >
        {timelineModal && <LeaveTimeline leave={timelineModal} />}
      </Modal>
    </div>
  );
}
