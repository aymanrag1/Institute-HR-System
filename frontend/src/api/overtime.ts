import client from "./client";
import type { OvertimeRequest, PaginatedResponse } from "../types";

export const overtimeApi = {
  list: (params?: Record<string, string>) =>
    client.get<PaginatedResponse<OvertimeRequest>>("/overtime/", { params }),

  get: (id: string) => client.get<OvertimeRequest>(`/overtime/${id}/`),

  create: (data: Partial<OvertimeRequest>) =>
    client.post<OvertimeRequest>("/overtime/", data),

  update: (id: string, data: Partial<OvertimeRequest>) =>
    client.patch<OvertimeRequest>(`/overtime/${id}/`, data),

  approve: (id: string, notes?: string) =>
    client.post(`/overtime/${id}/approve/`, { notes }),

  reject: (id: string, notes: string) =>
    client.post(`/overtime/${id}/reject/`, { notes }),
};
