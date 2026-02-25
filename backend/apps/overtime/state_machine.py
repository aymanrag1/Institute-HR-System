from apps.overtime.models import OvertimeStatus

OVERTIME_TRANSITIONS = {
    OvertimeStatus.PENDING_MANAGER: {
        "approve": OvertimeStatus.PENDING_HR,
        "reject":  OvertimeStatus.REJECTED,
    },
    OvertimeStatus.PENDING_HR: {
        "approve": OvertimeStatus.APPROVED,
        "reject":  OvertimeStatus.REJECTED,
    },
}

STATUS_TO_REQUIRED_ROLE = {
    OvertimeStatus.PENDING_MANAGER: "manager",
    OvertimeStatus.PENDING_HR:      "hr_manager",
}


def can_approve(user, overtime_request) -> bool:
    required_role = STATUS_TO_REQUIRED_ROLE.get(overtime_request.status)
    if not required_role:
        return False
    if user.role != required_role:
        return False
    if required_role == "manager":
        return str(overtime_request.employee.direct_manager_id) == str(user.id)
    return True
