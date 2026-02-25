import client from "./client";
import type { User } from "../types";

export const authApi = {
  login: (email: string, password: string) =>
    client.post<{ access: string; refresh: string; user: User }>("/auth/login/", { email, password }),

  logout: (refresh: string) =>
    client.post("/auth/logout/", { refresh }),

  me: () => client.get<User>("/auth/me/"),

  changePassword: (old_password: string, new_password: string) =>
    client.put("/auth/change-password/", { old_password, new_password }),

  uploadSignature: (file: File) => {
    const form = new FormData();
    form.append("signature", file);
    return client.post<{ signature_url: string }>("/auth/signature/", form, {
      headers: { "Content-Type": "multipart/form-data" },
    });
  },
};
