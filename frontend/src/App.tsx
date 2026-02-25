import { Suspense, lazy } from "react";
import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom";
import { ConfigProvider, Spin } from "antd";
import arEG from "antd/locale/ar_EG";
import enUS from "antd/locale/en_US";
import { useTranslation } from "react-i18next";
import { useAuthStore } from "./store/authStore";
import AppLayout from "./components/layout/AppLayout";
import "./i18n";

const LoginPage     = lazy(() => import("./pages/auth/LoginPage"));
const DashboardPage = lazy(() => import("./pages/dashboard/DashboardPage"));
const LeavesPage    = lazy(() => import("./pages/leaves/LeavesPage"));
const OvertimePage  = lazy(() => import("./pages/overtime/OvertimePage"));
const AttendancePage = lazy(() => import("./pages/attendance/AttendancePage"));
const ViolationsPage = lazy(() => import("./pages/violations/ViolationsPage"));
const EmployeesPage  = lazy(() => import("./pages/employees/EmployeesPage"));
const ProfilePage    = lazy(() => import("./pages/profile/ProfilePage"));
const NotFoundPage   = lazy(() => import("./pages/NotFoundPage"));

const Loader = () => (
  <div style={{ minHeight: "100vh", display: "flex", alignItems: "center", justifyContent: "center" }}>
    <Spin size="large" />
  </div>
);

function RequireAuth({ children }: { children: JSX.Element }) {
  const { isAuthenticated } = useAuthStore();
  return isAuthenticated ? children : <Navigate to="/login" replace />;
}

function RequireGuest({ children }: { children: JSX.Element }) {
  const { isAuthenticated } = useAuthStore();
  return isAuthenticated ? <Navigate to="/dashboard" replace /> : children;
}

export default function App() {
  const { i18n } = useTranslation();
  const isRTL    = i18n.language === "ar";

  return (
    <ConfigProvider
      locale={isRTL ? arEG : enUS}
      direction={isRTL ? "rtl" : "ltr"}
      theme={{
        token: {
          colorPrimary: "#003366",
          fontFamily: "'Cairo', 'Segoe UI', sans-serif",
          borderRadius: 6,
        },
      }}
    >
      <BrowserRouter>
        <Suspense fallback={<Loader />}>
          <Routes>
            {/* Guest routes */}
            <Route
              path="/login"
              element={<RequireGuest><LoginPage /></RequireGuest>}
            />

            {/* Protected routes */}
            <Route
              path="/"
              element={<RequireAuth><AppLayout /></RequireAuth>}
            >
              <Route index element={<Navigate to="/dashboard" replace />} />
              <Route path="dashboard"   element={<DashboardPage />} />
              <Route path="leaves"      element={<LeavesPage />} />
              <Route path="overtime"    element={<OvertimePage />} />
              <Route path="attendance"  element={<AttendancePage />} />
              <Route path="violations"  element={<ViolationsPage />} />
              <Route path="employees"   element={<EmployeesPage />} />
              <Route path="profile"     element={<ProfilePage />} />
            </Route>

            {/* Fallback */}
            <Route path="*" element={<NotFoundPage />} />
          </Routes>
        </Suspense>
      </BrowserRouter>
    </ConfigProvider>
  );
}
