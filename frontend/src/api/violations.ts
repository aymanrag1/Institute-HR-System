import client from "./client";
import type { Violation, PaginatedResponse } from "../types";

export const violationsApi = {
  list: (params?: Record<string, string>) =>
    client.get<PaginatedResponse<Violation>>("/violations/", { params }),

  get: (id: string) => client.get<Violation>(`/violations/${id}/`),

  create: (data: Partial<Violation>) =>
    client.post<Violation>("/violations/", data),

  update: (id: string, data: Partial<Violation>) =>
    client.patch<Violation>(`/violations/${id}/`, data),

  approve: (id: string, notes?: string) =>
    client.post(`/violations/${id}/approve/`, { notes }),

  reject: (id: string, notes: string) =>
    client.post(`/violations/${id}/reject/`, { notes }),
};
