import { useState } from "react";
import { Layout, Menu, Badge, Avatar, Dropdown, Space, Button } from "antd";
import {
  DashboardOutlined,
  CalendarOutlined,
  ClockCircleOutlined,
  CheckSquareOutlined,
  WarningOutlined,
  TeamOutlined,
  FileTextOutlined,
  UserOutlined,
  BellOutlined,
  LogoutOutlined,
  SettingOutlined,
} from "@ant-design/icons";
import { Outlet, useNavigate, useLocation } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { useAuthStore } from "../../store/authStore";
import { authApi } from "../../api/auth";
import { usePermission } from "../../hooks/usePermission";
import LanguageToggle from "../common/LanguageToggle";
import AppFooter from "../common/AppFooter";

const { Header, Sider, Content } = Layout;

export default function AppLayout() {
  const { t, i18n } = useTranslation();
  const navigate = useNavigate();
  const location = useLocation();
  const { user, logout, refreshToken } = useAuthStore();
  const { can, isRole } = usePermission();
  const [collapsed, setCollapsed] = useState(false);
  const isRTL = i18n.language === "ar";

  const handleLogout = async () => {
    if (refreshToken) {
      try { await authApi.logout(refreshToken); } catch { /* ignore */ }
    }
    logout();
    navigate("/login");
  };

  const menuItems = [
    { key: "/dashboard",   icon: <DashboardOutlined />, label: t("nav.dashboard") },
    { key: "/leaves",      icon: <CalendarOutlined />,  label: t("nav.leaves") },
    { key: "/overtime",    icon: <ClockCircleOutlined />, label: t("nav.overtime") },
    { key: "/attendance",  icon: <CheckSquareOutlined />, label: t("nav.attendance") },
    { key: "/violations",  icon: <WarningOutlined />,   label: t("nav.violations") },
    ...(isRole("hr_manager", "admin") ? [
      { key: "/employees",  icon: <TeamOutlined />,      label: t("nav.employees") },
      { key: "/audit",      icon: <FileTextOutlined />,  label: t("nav.audit") },
    ] : []),
  ];

  const userMenu = {
    items: [
      { key: "profile", icon: <UserOutlined />, label: t("nav.profile") },
      { key: "logout",  icon: <LogoutOutlined />, label: t("auth.logout"), danger: true },
    ],
    onClick: ({ key }: { key: string }) => {
      if (key === "logout") handleLogout();
      if (key === "profile") navigate("/profile");
    },
  };

  return (
    <Layout style={{ minHeight: "100vh" }} direction={isRTL ? "rtl" : "ltr"}>
      <Sider
        collapsible
        collapsed={collapsed}
        onCollapse={setCollapsed}
        style={{ background: "#001529" }}
        width={220}
      >
        <div style={{
          color: "white", textAlign: "center", padding: collapsed ? "16px 4px" : "16px",
          fontFamily: "Cairo, sans-serif", fontWeight: 700, fontSize: collapsed ? 12 : 14,
          borderBottom: "1px solid #1f3a5a", marginBottom: 8,
        }}>
          {collapsed ? "RSYI" : t("app.name")}
        </div>
        <Menu
          theme="dark"
          mode="inline"
          selectedKeys={[location.pathname]}
          items={menuItems}
          onClick={({ key }) => navigate(key)}
        />
      </Sider>

      <Layout>
        <Header style={{
          background: "#fff", padding: "0 24px",
          display: "flex", alignItems: "center", justifyContent: "space-between",
          boxShadow: "0 1px 4px rgba(0,21,41,.08)", gap: 16,
        }}>
          <div style={{ fontWeight: 700, fontSize: 16, color: "#003366" }}>
            {t("app.institute")}
          </div>

          <Space size={16}>
            <LanguageToggle />

            <Badge count={0} size="small">
              <Button
                type="text"
                icon={<BellOutlined style={{ fontSize: 18 }} />}
                onClick={() => navigate("/notifications")}
              />
            </Badge>

            <Dropdown menu={userMenu} trigger={["click"]}>
              <Space style={{ cursor: "pointer" }}>
                <Avatar icon={<UserOutlined />} style={{ background: "#003366" }} />
                {!collapsed && (
                  <span style={{ fontSize: 13, color: "#333" }}>
                    {user?.full_name_ar || user?.email}
                  </span>
                )}
              </Space>
            </Dropdown>
          </Space>
        </Header>

        <Content style={{ margin: "24px", background: "#f5f7fa" }}>
          <div style={{
            background: "#fff", borderRadius: 8,
            padding: "24px", minHeight: "calc(100vh - 160px)",
          }}>
            <Outlet />
          </div>
        </Content>

        <AppFooter />
      </Layout>
    </Layout>
  );
}
