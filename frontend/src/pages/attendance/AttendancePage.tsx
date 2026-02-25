import { useEffect, useState } from "react";
import {
  Table, Button, Space, Upload, message, Typography,
  Alert, Progress, Modal, Descriptions,
} from "antd";
import {
  UploadOutlined, DownloadOutlined, PlusOutlined,
} from "@ant-design/icons";
import { useTranslation } from "react-i18next";
import { attendanceApi } from "../../api/attendance";
import { usePermission } from "../../hooks/usePermission";
import StatusBadge from "../../components/common/StatusBadge";
import type { AttendanceRecord, AttendanceImport } from "../../types";

const { Title, Text } = Typography;

export default function AttendancePage() {
  const { t } = useTranslation();
  const { can } = usePermission();

  const [records, setRecords]         = useState<AttendanceRecord[]>([]);
  const [loading, setLoading]         = useState(false);
  const [total, setTotal]             = useState(0);
  const [page, setPage]               = useState(1);
  const [importResult, setImportResult] = useState<AttendanceImport | null>(null);
  const [importModal, setImportModal] = useState(false);
  const [uploading, setUploading]     = useState(false);

  const fetch = async (p = page) => {
    setLoading(true);
    try {
      const { data } = await attendanceApi.list({ page: String(p) });
      setRecords(data.results);
      setTotal(data.count);
    } catch { message.error("Failed to load"); }
    finally { setLoading(false); }
  };

  useEffect(() => { fetch(1); }, []);

  const downloadTemplate = async () => {
    try {
      const { data } = await attendanceApi.downloadTemplate();
      const url = window.URL.createObjectURL(new Blob([data]));
      const a   = document.createElement("a");
      a.href    = url;
      a.download = "attendance_template.xlsx";
      a.click();
      window.URL.revokeObjectURL(url);
    } catch { message.error("Download failed"); }
  };

  const handleImport = async (file: File) => {
    setUploading(true);
    try {
      const { data } = await attendanceApi.importExcel(file);
      setImportResult(data);
      setImportModal(true);
      fetch(1);
    } catch (e: any) {
      message.error(e.response?.data?.detail || "Import failed");
    } finally { setUploading(false); }
    return false; // prevent default upload
  };

  const columns = [
    { title: "الموظف",              dataIndex: "employee_name",  key: "emp" },
    { title: t("attendance.date"),  dataIndex: "date",           key: "date" },
    { title: t("attendance.check_in"),  dataIndex: "check_in",  key: "ci", render: (v: string) => v || "—" },
    { title: t("attendance.check_out"), dataIndex: "check_out", key: "co", render: (v: string) => v || "—" },
    {
      title: t("attendance.status"), key: "status",
      render: (_: any, r: AttendanceRecord) => (
        <StatusBadge status={r.status} label={r.status_display} />
      ),
    },
    {
      title: "المصدر", dataIndex: "source", key: "source",
      render: (v: string) => v === "excel_import" ? "Excel" : "يدوي",
    },
  ];

  return (
    <div>
      <div style={{ display: "flex", justifyContent: "space-between", marginBottom: 20, flexWrap: "wrap", gap: 8 }}>
        <Title level={4} style={{ margin: 0, color: "#003366" }}>{t("attendance.title")}</Title>

        {can("attendance", "import") && (
          <Space wrap>
            <Button icon={<DownloadOutlined />} onClick={downloadTemplate}>
              {t("attendance.download_template")}
            </Button>
            <Upload
              accept=".xlsx,.xls"
              showUploadList={false}
              beforeUpload={handleImport}
            >
              <Button
                type="primary"
                icon={<UploadOutlined />}
                loading={uploading}
              >
                {t("attendance.import")}
              </Button>
            </Upload>
          </Space>
        )}
      </div>

      <Table
        dataSource={records} columns={columns} rowKey="id" loading={loading}
        pagination={{ total, pageSize: 20, current: page, onChange: (p) => { setPage(p); fetch(p); } }}
        scroll={{ x: true }}
      />

      {/* Import Result Modal */}
      <Modal
        title="نتيجة الاستيراد"
        open={importModal}
        onCancel={() => setImportModal(false)}
        footer={<Button onClick={() => setImportModal(false)}>إغلاق</Button>}
        width={560}
      >
        {importResult && (
          <div>
            <Progress
              percent={Math.round((importResult.success_rows / importResult.total_rows) * 100)}
              status={importResult.failed_rows > 0 ? "exception" : "success"}
              style={{ marginBottom: 16 }}
            />
            <Descriptions bordered size="small">
              <Descriptions.Item label="إجمالي الصفوف" span={3}>{importResult.total_rows}</Descriptions.Item>
              <Descriptions.Item label="تم استيرادها" span={3}>
                <Text type="success">{importResult.success_rows}</Text>
              </Descriptions.Item>
              <Descriptions.Item label="فشل" span={3}>
                <Text type="danger">{importResult.failed_rows}</Text>
              </Descriptions.Item>
            </Descriptions>

            {importResult.errors.length > 0 && (
              <div style={{ marginTop: 16 }}>
                <Text strong>الأخطاء:</Text>
                <div style={{ maxHeight: 200, overflowY: "auto", marginTop: 8 }}>
                  {importResult.errors.map((e, i) => (
                    <Alert
                      key={i}
                      type="error"
                      message={`صف ${e.row}: ${e.error}`}
                      style={{ marginBottom: 4 }}
                      showIcon
                    />
                  ))}
                </div>
              </div>
            )}
          </div>
        )}
      </Modal>
    </div>
  );
}
