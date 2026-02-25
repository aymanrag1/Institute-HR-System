from rest_framework import viewsets, filters, status
from rest_framework.decorators import action
from rest_framework.permissions import IsAuthenticated
from rest_framework.response import Response
from django.http import HttpResponse
from django_filters.rest_framework import DjangoFilterBackend

from .models import LeaveRequest, LeaveStatus
from .serializers import LeaveRequestSerializer
from .services import LeaveWorkflowService
from .pdf_generator import generate_leave_pdf
from apps.core.permissions import IsOwnerOrHROrAdmin


class LeaveRequestViewSet(viewsets.ModelViewSet):
    serializer_class = LeaveRequestSerializer
    permission_classes = [IsAuthenticated]
    filter_backends = [DjangoFilterBackend, filters.OrderingFilter]
    filterset_fields = ["status", "leave_type"]
    ordering_fields = ["created_at", "start_date"]

    def get_queryset(self):
        user = self.request.user
        qs = LeaveRequest.objects.select_related(
            "employee", "employee__department", "employee__direct_manager"
        ).prefetch_related("approvals__approver")

        if user.role == "employee":
            return qs.filter(employee=user)
        if user.role == "manager":
            return qs.filter(employee__direct_manager=user) | qs.filter(employee=user)
        return qs.all()

    def perform_create(self, serializer):
        serializer.save(employee=self.request.user, status=LeaveStatus.DRAFT)

    def update(self, request, *args, **kwargs):
        instance = self.get_object()
        if instance.employee != request.user:
            return Response({"detail": "Not your request."}, status=403)
        if instance.status != LeaveStatus.DRAFT:
            return Response({"detail": "Only draft requests can be edited."}, status=400)
        return super().update(request, *args, **kwargs)

    def destroy(self, request, *args, **kwargs):
        instance = self.get_object()
        if instance.employee != request.user:
            return Response({"detail": "Not your request."}, status=403)
        if instance.status not in (LeaveStatus.DRAFT, LeaveStatus.PENDING_MANAGER):
            return Response({"detail": "Cannot cancel at this stage."}, status=400)
        instance.status = LeaveStatus.CANCELLED
        instance.save(update_fields=["status", "updated_at"])
        return Response(status=204)

    @action(detail=True, methods=["post"])
    def submit(self, request, pk=None):
        leave = self.get_object()
        if leave.employee != request.user:
            return Response({"detail": "Not your request."}, status=403)
        try:
            LeaveWorkflowService.process_action(leave, request.user, "submit", request=request)
        except ValueError as e:
            return Response({"detail": str(e)}, status=400)
        return Response({"status": leave.status})

    @action(detail=True, methods=["post"])
    def approve(self, request, pk=None):
        leave = self.get_object()
        notes = request.data.get("notes", "")
        try:
            LeaveWorkflowService.process_action(leave, request.user, "approve", notes, request=request)
        except (ValueError, PermissionError) as e:
            return Response({"detail": str(e)}, status=400)
        return Response({"status": leave.status})

    @action(detail=True, methods=["post"])
    def reject(self, request, pk=None):
        leave = self.get_object()
        notes = request.data.get("notes", "")
        if not notes:
            return Response({"detail": "Rejection note is required."}, status=400)
        try:
            LeaveWorkflowService.process_action(leave, request.user, "reject", notes, request=request)
        except (ValueError, PermissionError) as e:
            return Response({"detail": str(e)}, status=400)
        return Response({"status": leave.status})

    @action(detail=True, methods=["post"])
    def return_request(self, request, pk=None):
        leave = self.get_object()
        notes = request.data.get("notes", "")
        try:
            LeaveWorkflowService.process_action(leave, request.user, "return", notes, request=request)
        except (ValueError, PermissionError) as e:
            return Response({"detail": str(e)}, status=400)
        return Response({"status": leave.status})

    @action(detail=True, methods=["get"])
    def pdf(self, request, pk=None):
        leave = self.get_object()
        if leave.status != LeaveStatus.APPROVED:
            return Response({"detail": "PDF is only available for approved requests."}, status=400)
        try:
            pdf_bytes = generate_leave_pdf(leave)
            response = HttpResponse(pdf_bytes, content_type="application/pdf")
            response["Content-Disposition"] = f'attachment; filename="leave_{leave.id}.pdf"'
            return response
        except Exception as e:
            return Response({"detail": f"PDF generation failed: {str(e)}"}, status=500)

    @action(detail=True, methods=["get"])
    def timeline(self, request, pk=None):
        leave = self.get_object()
        from .serializers import LeaveApprovalSerializer
        approvals = leave.approvals.order_by("sequence_order")
        return Response(LeaveApprovalSerializer(approvals, many=True).data)
