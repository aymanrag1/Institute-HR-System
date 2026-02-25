from django.utils import timezone
from rest_framework import viewsets, status
from rest_framework.decorators import action
from rest_framework.permissions import IsAuthenticated
from rest_framework.response import Response
from django_filters.rest_framework import DjangoFilterBackend

from .models import Violation, ViolationStatus
from .serializers import ViolationSerializer
from apps.core.permissions import IsHRManagerOrAdmin, IsDeanOrAdmin, IsAdminUser
from apps.core.audit import log_action


class ViolationViewSet(viewsets.ModelViewSet):
    serializer_class = ViolationSerializer
    filter_backends = [DjangoFilterBackend]
    filterset_fields = ["status", "severity", "type", "employee"]

    def get_permissions(self):
        if self.action == "create":
            return [IsHRManagerOrAdmin()]
        if self.action in ["update", "partial_update"]:
            return [IsHRManagerOrAdmin()]
        if self.action == "destroy":
            return [IsAdminUser()]
        if self.action in ["approve", "reject_violation"]:
            return [IsDeanOrAdmin()]
        return [IsAuthenticated()]

    def get_queryset(self):
        user = self.request.user
        qs = Violation.objects.select_related("employee", "created_by")
        if user.role == "employee":
            return qs.filter(employee=user)
        return qs.all()

    def perform_create(self, serializer):
        violation = serializer.save(
            created_by=self.request.user,
            status=ViolationStatus.PENDING_DEAN,
        )
        log_action(self.request.user, "violation.created", "Violation", violation.id,
                   new_data={"employee": str(violation.employee_id)}, request=self.request)

    def update(self, request, *args, **kwargs):
        instance = self.get_object()
        if instance.status != ViolationStatus.PENDING_DEAN:
            return Response({"detail": "Cannot edit after dean decision."}, status=400)
        return super().update(request, *args, **kwargs)

    @action(detail=True, methods=["post"])
    def approve(self, request, pk=None):
        violation = self.get_object()
        if violation.status != ViolationStatus.PENDING_DEAN:
            return Response({"detail": "Already processed."}, status=400)

        old_status = violation.status
        violation.status       = ViolationStatus.APPROVED
        violation.dean_notes   = request.data.get("notes", "")
        violation.dean_signature = request.user.signature_file
        violation.approved_at  = timezone.now()
        violation.save(update_fields=["status", "dean_notes", "dean_signature", "approved_at", "updated_at"])

        log_action(request.user, "violation.approved", "Violation", violation.id,
                   old_data={"status": old_status}, new_data={"status": violation.status}, request=request)

        # Notify employee
        from apps.notifications.tasks import create_notification
        create_notification(
            recipient_id=str(violation.employee_id),
            title_ar="تم اعتماد المخالفة",
            title_en="Violation has been approved",
            entity_type="violation",
            entity_id=str(violation.id),
        )
        return Response({"status": violation.status})

    @action(detail=True, methods=["post"], url_path="reject")
    def reject_violation(self, request, pk=None):
        violation = self.get_object()
        notes = request.data.get("notes", "")
        if not notes:
            return Response({"detail": "Notes required for rejection."}, status=400)
        if violation.status != ViolationStatus.PENDING_DEAN:
            return Response({"detail": "Already processed."}, status=400)

        old_status = violation.status
        violation.status     = ViolationStatus.REJECTED
        violation.dean_notes = notes
        violation.save(update_fields=["status", "dean_notes", "updated_at"])

        log_action(request.user, "violation.rejected", "Violation", violation.id,
                   old_data={"status": old_status}, new_data={"status": violation.status}, request=request)
        return Response({"status": violation.status})
