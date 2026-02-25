from apps.leaves.models import LeaveStatus

LEAVE_TRANSITIONS = {
    LeaveStatus.DRAFT: {
        "submit": LeaveStatus.PENDING_MANAGER,
        "cancel": LeaveStatus.CANCELLED,
    },
    LeaveStatus.PENDING_MANAGER: {
        "approve": LeaveStatus.PENDING_HR,
        "reject":  LeaveStatus.REJECTED,
        "return":  LeaveStatus.DRAFT,
    },
    LeaveStatus.PENDING_HR: {
        "approve": LeaveStatus.PENDING_DEAN,
        "reject":  LeaveStatus.REJECTED,
        "return":  LeaveStatus.DRAFT,
    },
    LeaveStatus.PENDING_DEAN: {
        "approve": LeaveStatus.APPROVED,
        "reject":  LeaveStatus.REJECTED,
        "return":  LeaveStatus.DRAFT,
    },
}

# Managers skip themselves in their own leave
MANAGER_LEAVE_TRANSITIONS = {
    LeaveStatus.DRAFT: {
        "submit": LeaveStatus.PENDING_HR,
        "cancel": LeaveStatus.CANCELLED,
    },
    LeaveStatus.PENDING_HR: {
        "approve": LeaveStatus.PENDING_DEAN,
        "reject":  LeaveStatus.REJECTED,
        "return":  LeaveStatus.DRAFT,
    },
    LeaveStatus.PENDING_DEAN: {
        "approve": LeaveStatus.APPROVED,
        "reject":  LeaveStatus.REJECTED,
        "return":  LeaveStatus.DRAFT,
    },
}

STATUS_TO_REQUIRED_ROLE = {
    LeaveStatus.PENDING_MANAGER: "manager",
    LeaveStatus.PENDING_HR:      "hr_manager",
    LeaveStatus.PENDING_DEAN:    "dean",
}


def get_transitions(leave_request):
    employee = leave_request.employee
    if employee.role == "manager":
        return MANAGER_LEAVE_TRANSITIONS
    return LEAVE_TRANSITIONS


def can_approve(user, leave_request) -> bool:
    required_role = STATUS_TO_REQUIRED_ROLE.get(leave_request.status)
    if not required_role:
        return False
    if user.role != required_role:
        return False
    if required_role == "manager":
        return str(leave_request.employee.direct_manager_id) == str(user.id)
    return True


def get_allowed_actions(user, leave_request) -> list:
    transitions = get_transitions(leave_request)
    current = leave_request.status
    allowed = transitions.get(current, {})

    if user == leave_request.employee and current == LeaveStatus.DRAFT:
        return list(allowed.keys())
    if user == leave_request.employee and current in (
        LeaveStatus.DRAFT, LeaveStatus.PENDING_MANAGER
    ) and "cancel" in allowed:
        return ["cancel"]
    if can_approve(user, leave_request):
        return [a for a in allowed.keys() if a != "cancel"]
    return []
