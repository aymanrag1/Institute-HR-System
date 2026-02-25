from django.db import transaction
from django.utils import timezone

from .models import LeaveRequest, LeaveApproval, LeaveStatus
from .state_machine import get_transitions, can_approve
from apps.core.audit import log_action


class LeaveWorkflowService:

    @staticmethod
    @transaction.atomic
    def process_action(leave_request: LeaveRequest, actor, action: str,
                       notes: str = "", request=None) -> LeaveRequest:
        transitions = get_transitions(leave_request)
        allowed = transitions.get(leave_request.status, {})

        if action not in allowed:
            raise ValueError(f"Action '{action}' is not allowed from status '{leave_request.status}'.")

        if action not in ("submit", "cancel"):
            if not can_approve(actor, leave_request):
                raise PermissionError("You are not authorized to perform this action.")

        old_status = leave_request.status
        new_status = allowed[action]
        leave_request.status = new_status

        if action == "reject" and notes:
            leave_request.rejection_note = notes

        leave_request.save(update_fields=["status", "rejection_note", "updated_at"])

        if action not in ("cancel",):
            order = LeaveApproval.objects.filter(leave_request=leave_request).count() + 1
            LeaveApproval.objects.create(
                leave_request=leave_request,
                approver=actor,
                approver_role=actor.role,
                action=action,
                notes=notes,
                signature_file=actor.signature_file,
                sequence_order=order,
            )

        log_action(
            user=actor,
            action=f"leave.{action}",
            entity_type="LeaveRequest",
            entity_id=leave_request.id,
            old_data={"status": old_status},
            new_data={"status": new_status},
            request=request,
        )

        # Notify employee
        LeaveWorkflowService._notify(leave_request, action, actor)
        return leave_request

    @staticmethod
    def _notify(leave_request: LeaveRequest, action: str, actor):
        from apps.notifications.tasks import create_notification
        messages = {
            "submit":  ("تم تقديم طلب الإجازة", "Leave request submitted"),
            "approve": ("تمت الموافقة على طلب إجازتك", "Your leave request was approved"),
            "reject":  ("تم رفض طلب إجازتك", "Your leave request was rejected"),
            "return":  ("تم إرجاع طلب إجازتك للتعديل", "Your leave request was returned for correction"),
        }
        title_ar, title_en = messages.get(action, ("تحديث طلب الإجازة", "Leave request update"))
        create_notification(
            recipient_id=str(leave_request.employee_id),
            title_ar=title_ar,
            title_en=title_en,
            entity_type="leave",
            entity_id=str(leave_request.id),
        )
