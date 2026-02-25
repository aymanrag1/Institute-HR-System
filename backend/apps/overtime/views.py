from rest_framework import viewsets, status
from rest_framework.decorators import action
from rest_framework.permissions import IsAuthenticated
from rest_framework.response import Response
from django.db import transaction

from .models import OvertimeRequest, OvertimeApproval
from .serializers import OvertimeRequestSerializer
from .state_machine import OVERTIME_TRANSITIONS, can_approve
from apps.core.audit import log_action
from apps.notifications.tasks import create_notification


class OvertimeRequestViewSet(viewsets.ModelViewSet):
    serializer_class = OvertimeRequestSerializer
    permission_classes = [IsAuthenticated]

    def get_queryset(self):
        user = self.request.user
        qs = OvertimeRequest.objects.select_related(
            "employee", "employee__department"
        ).prefetch_related("approvals__approver")

        if user.role == "employee":
            return qs.filter(employee=user)
        if user.role == "manager":
            return qs.filter(employee__direct_manager=user) | qs.filter(employee=user)
        return qs.all()

    def perform_create(self, serializer):
        serializer.save(employee=self.request.user)

    def update(self, request, *args, **kwargs):
        instance = self.get_object()
        if instance.employee != request.user:
            return Response({"detail": "Not your request."}, status=403)
        if instance.status != "pending_manager":
            return Response({"detail": "Cannot edit after submission."}, status=400)
        return super().update(request, *args, **kwargs)

    @action(detail=True, methods=["post"])
    def approve(self, request, pk=None):
        return self._process_action(request, "approve")

    @action(detail=True, methods=["post"])
    def reject(self, request, pk=None):
        notes = request.data.get("notes", "")
        if not notes:
            return Response({"detail": "Rejection note required."}, status=400)
        return self._process_action(request, "reject", notes)

    @transaction.atomic
    def _process_action(self, request, action_name: str, notes: str = ""):
        overtime = self.get_object()
        if not can_approve(request.user, overtime):
            return Response({"detail": "Not authorized."}, status=403)

        allowed = OVERTIME_TRANSITIONS.get(overtime.status, {})
        if action_name not in allowed:
            return Response({"detail": f"Action '{action_name}' not allowed."}, status=400)

        old_status = overtime.status
        overtime.status = allowed[action_name]
        overtime.save(update_fields=["status", "updated_at"])

        OvertimeApproval.objects.create(
            overtime=overtime,
            approver=request.user,
            approver_role=request.user.role,
            action=action_name,
            notes=notes,
            signature_file=request.user.signature_file,
        )

        log_action(
            user=request.user,
            action=f"overtime.{action_name}",
            entity_type="OvertimeRequest",
            entity_id=overtime.id,
            old_data={"status": old_status},
            new_data={"status": overtime.status},
            request=request,
        )

        create_notification(
            recipient_id=str(overtime.employee_id),
            title_ar="تحديث طلب الأوفر تايم",
            title_en="Overtime Request Update",
            entity_type="overtime",
            entity_id=str(overtime.id),
        )

        return Response({"status": overtime.status})
