import { useEffect, useState } from "react";
import { Row, Col, Card, Statistic, List, Tag, Typography, Spin } from "antd";
import {
  CalendarOutlined, ClockCircleOutlined,
  WarningOutlined, CheckCircleOutlined,
} from "@ant-design/icons";
import { useTranslation } from "react-i18next";
import { useNavigate } from "react-router-dom";
import { useAuthStore } from "../../store/authStore";
import { leavesApi } from "../../api/leaves";
import { overtimeApi } from "../../api/overtime";
import { notificationsApi } from "../../api/employees";
import StatusBadge from "../../components/common/StatusBadge";
import type { LeaveRequest, OvertimeRequest, Notification } from "../../types";

const { Title, Text } = Typography;

export default function DashboardPage() {
  const { t } = useTranslation();
  const { user } = useAuthStore();
  const navigate = useNavigate();

  const [leaves, setLeaves] = useState<LeaveRequest[]>([]);
  const [overtime, setOvertime] = useState<OvertimeRequest[]>([]);
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetch = async () => {
      try {
        const [lRes, oRes, nRes] = await Promise.all([
          leavesApi.list({ page_size: "5" }),
          overtimeApi.list({ page_size: "5" }),
          notificationsApi.list(),
        ]);
        setLeaves(lRes.data.results);
        setOvertime(oRes.data.results);
        setNotifications(nRes.data.results?.slice(0, 5) || []);
      } catch { /* ignore */ }
      finally { setLoading(false); }
    };
    fetch();
  }, []);

  const stats = {
    leaves_pending: leaves.filter((l) =>
      ["pending_manager", "pending_hr", "pending_dean"].includes(l.status)
    ).length,
    leaves_approved: leaves.filter((l) => l.status === "approved").length,
    overtime_pending: overtime.filter((o) =>
      ["pending_manager", "pending_hr"].includes(o.status)
    ).length,
    overtime_approved: overtime.filter((o) => o.status === "approved").length,
  };

  if (loading) return <Spin size="large" style={{ display: "block", margin: "80px auto" }} />;

  return (
    <div>
      <Title level={4} style={{ color: "#003366", marginBottom: 24 }}>
        {t("nav.dashboard")} — {user?.full_name_ar}
      </Title>

      {/* Stats */}
      <Row gutter={[16, 16]} style={{ marginBottom: 32 }}>
        {[
          { title: t("leaves.title"), value: stats.leaves_pending, icon: <CalendarOutlined />, color: "#1890ff", sub: "Pending" },
          { title: t("overtime.title"), value: stats.overtime_pending, icon: <ClockCircleOutlined />, color: "#faad14", sub: "Pending" },
          { title: t("leaves.statuses.approved"), value: stats.leaves_approved, icon: <CheckCircleOutlined />, color: "#52c41a", sub: "Approved Leaves" },
          { title: t("overtime.title"), value: stats.overtime_approved, icon: <CheckCircleOutlined />, color: "#722ed1", sub: "Approved OT" },
        ].map((stat, i) => (
          <Col xs={24} sm={12} lg={6} key={i}>
            <Card
              hoverable
              style={{ borderRadius: 8, borderTop: `3px solid ${stat.color}` }}
            >
              <Statistic
                title={<span style={{ fontSize: 13 }}>{stat.title} <Text type="secondary">({stat.sub})</Text></span>}
                value={stat.value}
                prefix={<span style={{ color: stat.color }}>{stat.icon}</span>}
                valueStyle={{ color: stat.color, fontWeight: 700 }}
              />
            </Card>
          </Col>
        ))}
      </Row>

      {/* Recent leaves + notifications */}
      <Row gutter={[16, 16]}>
        <Col xs={24} lg={14}>
          <Card
            title={<span><CalendarOutlined /> {t("leaves.title")}</span>}
            extra={<a onClick={() => navigate("/leaves")}>{t("common.view")}</a>}
            style={{ borderRadius: 8 }}
          >
            <List
              dataSource={leaves}
              locale={{ emptyText: t("common.no_data") }}
              renderItem={(item) => (
                <List.Item
                  style={{ cursor: "pointer" }}
                  onClick={() => navigate(`/leaves/${item.id}`)}
                  extra={<StatusBadge status={item.status} label={item.status_display} />}
                >
                  <List.Item.Meta
                    title={<span style={{ fontSize: 13 }}>{item.leave_type_display}</span>}
                    description={
                      <span style={{ fontSize: 12, color: "#999" }}>
                        {item.start_date} → {item.end_date} ({item.total_days} {t("leaves.total_days")})
                      </span>
                    }
                  />
                </List.Item>
              )}
            />
          </Card>
        </Col>

        <Col xs={24} lg={10}>
          <Card
            title={<span><WarningOutlined /> الإشعارات الأخيرة</span>}
            style={{ borderRadius: 8 }}
          >
            <List
              dataSource={notifications}
              locale={{ emptyText: t("common.no_data") }}
              renderItem={(item) => (
                <List.Item>
                  <List.Item.Meta
                    title={
                      <span style={{ fontSize: 13, fontWeight: item.is_read ? 400 : 700 }}>
                        {item.title_ar}
                      </span>
                    }
                    description={
                      <span style={{ fontSize: 11, color: "#999" }}>
                        {new Date(item.created_at).toLocaleDateString("ar-EG")}
                      </span>
                    }
                  />
                  {!item.is_read && <Tag color="blue">جديد</Tag>}
                </List.Item>
              )}
            />
          </Card>
        </Col>
      </Row>
    </div>
  );
}
