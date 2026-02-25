import client from "./client";
import type { User, Department, PaginatedResponse } from "../types";

export const employeesApi = {
  list: (params?: Record<string, string>) =>
    client.get<PaginatedResponse<User>>("/users/", { params }),

  get: (id: string) => client.get<User>(`/users/${id}/`),

  create: (data: Partial<User> & { password: string; password2: string }) =>
    client.post<User>("/users/", data),

  update: (id: string, data: Partial<User>) =>
    client.patch<User>(`/users/${id}/`, data),

  delete: (id: string) => client.delete(`/users/${id}/`),
};

export const departmentsApi = {
  list: (params?: Record<string, string>) =>
    client.get<PaginatedResponse<Department>>("/departments/", { params }),

  create: (data: Partial<Department>) =>
    client.post<Department>("/departments/", data),

  update: (id: string, data: Partial<Department>) =>
    client.patch<Department>(`/departments/${id}/`, data),

  delete: (id: string) => client.delete(`/departments/${id}/`),
};

export const notificationsApi = {
  list: () => client.get("/notifications/"),
  unreadCount: () => client.get<{ count: number }>("/notifications/unread-count/"),
  markRead: (id: string) => client.post(`/notifications/${id}/read/`),
  markAllRead: () => client.post("/notifications/read-all/"),
};
