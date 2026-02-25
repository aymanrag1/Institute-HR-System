import client from "./client";
import type { AttendanceRecord, AttendanceImport, PaginatedResponse } from "../types";

export const attendanceApi = {
  list: (params?: Record<string, string>) =>
    client.get<PaginatedResponse<AttendanceRecord>>("/attendance/", { params }),

  create: (data: Partial<AttendanceRecord>) =>
    client.post<AttendanceRecord>("/attendance/", data),

  update: (id: string, data: Partial<AttendanceRecord>) =>
    client.patch<AttendanceRecord>(`/attendance/${id}/`, data),

  delete: (id: string) => client.delete(`/attendance/${id}/`),

  downloadTemplate: () =>
    client.get("/attendance/template/", { responseType: "blob" }),

  importExcel: (file: File) => {
    const form = new FormData();
    form.append("file", file);
    return client.post<AttendanceImport>("/attendance/import/", form, {
      headers: { "Content-Type": "multipart/form-data" },
    });
  },

  importStatus: (id: string) =>
    client.get<AttendanceImport>(`/attendance/${id}/status/`),

  report: (params?: Record<string, string>) =>
    client.get("/attendance/report/", { params }),
};
