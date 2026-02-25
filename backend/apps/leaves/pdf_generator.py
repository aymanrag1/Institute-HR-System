import base64
from django.template.loader import render_to_string
from django.conf import settings

try:
    from weasyprint import HTML, CSS
    from weasyprint.text.fonts import FontConfiguration
    WEASYPRINT_AVAILABLE = True
except ImportError:
    WEASYPRINT_AVAILABLE = False


def _load_signature_b64(signature_key: str) -> str | None:
    if not signature_key:
        return None
    try:
        from apps.core.storage import storage_client
        data = storage_client.get_object(signature_key)
        b64 = base64.b64encode(data.read()).decode()
        return f"data:image/png;base64,{b64}"
    except Exception:
        return None


def generate_leave_pdf(leave_request) -> bytes:
    approvals = leave_request.approvals.order_by("sequence_order").select_related("approver")

    approvals_data = []
    for approval in approvals:
        approvals_data.append({
            "approver_name":    approval.approver.get_full_name("ar"),
            "approver_name_en": approval.approver.get_full_name("en"),
            "role_display":     approval.approver_role,
            "action":           approval.action,
            "notes":            approval.notes,
            "timestamp":        approval.approved_at,
            "signature_b64":    _load_signature_b64(approval.signature_file),
        })

    employee = leave_request.employee
    context = {
        "leave":             leave_request,
        "employee_name_ar":  employee.get_full_name("ar"),
        "employee_name_en":  employee.get_full_name("en"),
        "national_id":       employee.national_id or "—",
        "department":        employee.department.name_ar if employee.department else "—",
        "approvals":         approvals_data,
        "institute_name_ar": settings.INSTITUTE_NAME_AR,
        "institute_name_en": settings.INSTITUTE_NAME_EN,
        "app_version":       settings.APP_VERSION,
        "developer":         settings.APP_DEVELOPER,
        "developer_phone":   settings.APP_DEVELOPER_PHONE,
    }

    html_string = render_to_string("leaves/leave_pdf.html", context)

    if not WEASYPRINT_AVAILABLE:
        return html_string.encode("utf-8")

    font_config = FontConfiguration()
    css = CSS(string="""
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        body { font-family: 'Cairo', 'Arial', sans-serif; }
    """, font_config=font_config)

    return HTML(string=html_string, base_url=None).write_pdf(
        stylesheets=[css], font_config=font_config
    )
