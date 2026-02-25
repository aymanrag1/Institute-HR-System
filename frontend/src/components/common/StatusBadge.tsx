import { Tag } from "antd";

const STATUS_COLOR: Record<string, string> = {
  draft:           "default",
  pending_manager: "processing",
  pending_hr:      "processing",
  pending_dean:    "warning",
  approved:        "success",
  rejected:        "error",
  cancelled:       "default",
  pending:         "processing",
  completed:       "success",
  failed:          "error",
  present:         "success",
  absent:          "error",
  late:            "warning",
  half_day:        "orange",
  on_leave:        "blue",
  minor:           "default",
  moderate:        "warning",
  major:           "orange",
  critical:        "error",
};

interface Props {
  status: string;
  label: string;
}

export default function StatusBadge({ status, label }: Props) {
  return <Tag color={STATUS_COLOR[status] || "default"}>{label}</Tag>;
}
