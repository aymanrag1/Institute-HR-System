import { Timeline, Tag, Image, Typography, Divider } from "antd";
import { CheckCircleOutlined, CloseCircleOutlined, UndoOutlined } from "@ant-design/icons";
import { useTranslation } from "react-i18next";
import StatusBadge from "../../components/common/StatusBadge";
import type { LeaveRequest, LeaveApproval } from "../../types";

const { Text } = Typography;

const actionIcon = (action: string) => {
  if (action === "approved") return <CheckCircleOutlined style={{ color: "#52c41a" }} />;
  if (action === "rejected") return <CloseCircleOutlined style={{ color: "#ff4d4f" }} />;
  return <UndoOutlined style={{ color: "#faad14" }} />;
};

export default function LeaveTimeline({ leave }: { leave: LeaveRequest }) {
  const { t } = useTranslation();

  return (
    <div>
      <div style={{ marginBottom: 16 }}>
        <Text strong>{leave.employee_name}</Text>
        <br />
        <Text type="secondary">
          {leave.leave_type_display} — {leave.start_date} → {leave.end_date} ({leave.total_days} {t("leaves.total_days")})
        </Text>
        <br />
        <StatusBadge status={leave.status} label={leave.status_display} />
      </div>

      <Divider />

      {leave.approvals.length === 0 ? (
        <Text type="secondary">لا توجد موافقات بعد</Text>
      ) : (
        <Timeline
          items={leave.approvals.map((approval: LeaveApproval) => ({
            dot: actionIcon(approval.action),
            children: (
              <div style={{ paddingBottom: 8 }}>
                <div style={{ fontWeight: 600 }}>
                  {approval.approver_name}
                  <Tag
                    color={approval.action === "approved" ? "success" : approval.action === "rejected" ? "error" : "warning"}
                    style={{ marginRight: 8 }}
                  >
                    {approval.action === "approved" ? "موافقة" : approval.action === "rejected" ? "رفض" : "إرجاع"}
                  </Tag>
                </div>
                {approval.notes && (
                  <div style={{ color: "#666", fontSize: 12, margin: "4px 0" }}>
                    {approval.notes}
                  </div>
                )}
                <div style={{ fontSize: 11, color: "#999" }}>
                  {new Date(approval.approved_at).toLocaleString("ar-EG")}
                </div>
                {approval.signature_url && (
                  <Image
                    src={approval.signature_url}
                    height={50}
                    style={{ border: "1px solid #ddd", padding: 4, marginTop: 6 }}
                    preview={false}
                  />
                )}
              </div>
            ),
          }))}
        />
      )}
    </div>
  );
}
