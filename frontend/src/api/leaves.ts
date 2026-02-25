import client from "./client";
import type { LeaveRequest, PaginatedResponse } from "../types";

export const leavesApi = {
  list: (params?: Record<string, string>) =>
    client.get<PaginatedResponse<LeaveRequest>>("/leaves/", { params }),

  get: (id: string) => client.get<LeaveRequest>(`/leaves/${id}/`),

  create: (data: Partial<LeaveRequest>) =>
    client.post<LeaveRequest>("/leaves/", data),

  update: (id: string, data: Partial<LeaveRequest>) =>
    client.patch<LeaveRequest>(`/leaves/${id}/`, data),

  submit: (id: string) => client.post(`/leaves/${id}/submit/`),

  approve: (id: string, notes?: string) =>
    client.post(`/leaves/${id}/approve/`, { notes }),

  reject: (id: string, notes: string) =>
    client.post(`/leaves/${id}/reject/`, { notes }),

  returnRequest: (id: string, notes?: string) =>
    client.post(`/leaves/${id}/return_request/`, { notes }),

  timeline: (id: string) => client.get(`/leaves/${id}/timeline/`),

  pdfUrl: (id: string) => `/api/v1/leaves/${id}/pdf/`,
};
