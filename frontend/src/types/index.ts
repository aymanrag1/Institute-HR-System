export type UserRole = "employee" | "manager" | "hr_manager" | "dean" | "admin";

export interface User {
  id: string;
  email: string;
  role: UserRole;
  first_name_ar: string;
  first_name_en: string;
  last_name_ar: string;
  last_name_en: string;
  full_name_ar: string;
  full_name_en: string;
  national_id: string | null;
  phone: string;
  department: string | null;
  department_name: { ar: string; en: string } | null;
  direct_manager: string | null;
  signature_file: string;
  signature_url: string | null;
  is_active: boolean;
  date_joined: string;
}

export interface Department {
  id: string;
  name_ar: string;
  name_en: string;
  code: string;
  head: string | null;
  is_active: boolean;
  member_count: number;
}

export type LeaveType = "annual" | "sick" | "emergency" | "unpaid" | "other";
export type LeaveStatus =
  | "draft"
  | "pending_manager"
  | "pending_hr"
  | "pending_dean"
  | "approved"
  | "rejected"
  | "cancelled";

export interface LeaveApproval {
  id: string;
  approver: string;
  approver_name: string;
  approver_name_en: string;
  approver_role: string;
  action: "approved" | "rejected" | "returned";
  notes: string;
  signature_file: string;
  signature_url: string | null;
  sequence_order: number;
  approved_at: string;
}

export interface LeaveRequest {
  id: string;
  employee: string;
  employee_name: string;
  employee_name_en: string;
  department_name: { ar: string; en: string } | null;
  leave_type: LeaveType;
  leave_type_display: string;
  start_date: string;
  end_date: string;
  total_days: number;
  reason: string;
  status: LeaveStatus;
  status_display: string;
  rejection_note: string;
  approvals: LeaveApproval[];
  created_at: string;
  updated_at: string;
}

export type OvertimeStatus = "pending_manager" | "pending_hr" | "approved" | "rejected";

export interface OvertimeRequest {
  id: string;
  employee: string;
  employee_name: string;
  date: string;
  start_time: string;
  end_time: string;
  total_hours: string;
  reason: string;
  status: OvertimeStatus;
  status_display: string;
  approvals: OvertimeApproval[];
  created_at: string;
  updated_at: string;
}

export interface OvertimeApproval {
  id: string;
  approver: string;
  approver_name: string;
  approver_role: string;
  action: string;
  notes: string;
  signature_url: string | null;
  approved_at: string;
}

export type AttendanceStatus = "present" | "absent" | "late" | "half_day" | "on_leave";

export interface AttendanceRecord {
  id: string;
  employee: string;
  employee_name: string;
  date: string;
  check_in: string | null;
  check_out: string | null;
  status: AttendanceStatus;
  status_display: string;
  source: string;
  notes: string;
  created_at: string;
}

export interface AttendanceImport {
  id: string;
  total_rows: number;
  success_rows: number;
  failed_rows: number;
  errors: { row: number; error: string }[];
  status: "pending" | "processing" | "completed" | "failed";
  created_at: string;
}

export type ViolationSeverity = "minor" | "moderate" | "major" | "critical";
export type ViolationStatus = "pending_dean" | "approved" | "rejected";

export interface Violation {
  id: string;
  employee: string;
  employee_name: string;
  created_by: string;
  created_by_name: string;
  type: string;
  type_display: string;
  severity: ViolationSeverity;
  severity_display: string;
  description: string;
  incident_date: string;
  penalty: string;
  status: ViolationStatus;
  status_display: string;
  dean_notes: string;
  dean_signature_url: string | null;
  approved_at: string | null;
  created_at: string;
}

export interface Notification {
  id: string;
  title_ar: string;
  title_en: string;
  body_ar: string;
  body_en: string;
  entity_type: string;
  entity_id: string;
  is_read: boolean;
  created_at: string;
}

export interface PaginatedResponse<T> {
  count: number;
  total_pages: number;
  next: string | null;
  previous: string | null;
  results: T[];
}
