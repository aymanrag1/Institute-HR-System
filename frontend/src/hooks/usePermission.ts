import { useAuthStore } from "../store/authStore";
import type { UserRole } from "../types";

const PERMISSIONS: Record<string, Record<string, UserRole[]>> = {
  leaves: {
    create:  ["employee", "manager", "hr_manager", "dean", "admin"],
    approve: ["manager", "hr_manager", "dean"],
    delete:  ["admin"],
    export:  ["hr_manager", "dean", "admin"],
  },
  overtime: {
    create:  ["employee", "manager", "hr_manager", "dean", "admin"],
    approve: ["manager", "hr_manager"],
    delete:  ["admin"],
  },
  attendance: {
    view_all: ["manager", "hr_manager", "dean", "admin"],
    import:   ["hr_manager", "admin"],
    edit:     ["hr_manager", "admin"],
    delete:   ["admin"],
  },
  violations: {
    create:  ["hr_manager", "admin"],
    approve: ["dean", "admin"],
    delete:  ["admin"],
  },
  users: {
    create: ["admin"],
    edit:   ["hr_manager", "admin"],
    delete: ["admin"],
  },
  audit: {
    view: ["hr_manager", "dean", "admin"],
  },
};

export const usePermission = () => {
  const { user } = useAuthStore();

  const can = (module: string, action: string): boolean => {
    if (!user) return false;
    return PERMISSIONS[module]?.[action]?.includes(user.role) ?? false;
  };

  const isRole = (...roles: UserRole[]): boolean => {
    if (!user) return false;
    return roles.includes(user.role);
  };

  return { can, isRole };
};
