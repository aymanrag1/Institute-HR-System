import os
import tempfile
from io import BytesIO

from rest_framework import viewsets, status
from rest_framework.decorators import action
from rest_framework.parsers import MultiPartParser
from rest_framework.permissions import IsAuthenticated
from rest_framework.response import Response
from django.http import HttpResponse
from django_filters.rest_framework import DjangoFilterBackend
from django.db.models import Count, Q

from .models import AttendanceRecord, AttendanceImport
from .serializers import AttendanceRecordSerializer, AttendanceImportSerializer
from .excel_importer import process_attendance_import, generate_template_workbook
from apps.core.permissions import IsHRManagerOrAdmin
from apps.core.audit import log_action


class AttendanceViewSet(viewsets.ModelViewSet):
    serializer_class = AttendanceRecordSerializer
    filter_backends = [DjangoFilterBackend]
    filterset_fields = ["employee", "date", "status"]

    def get_permissions(self):
        if self.action in ["create", "update", "partial_update", "destroy",
                           "import_excel", "download_template"]:
            return [IsHRManagerOrAdmin()]
        return [IsAuthenticated()]

    def get_queryset(self):
        user = self.request.user
        qs = AttendanceRecord.objects.select_related("employee")

        date_from = self.request.query_params.get("date_from")
        date_to   = self.request.query_params.get("date_to")
        if date_from:
            qs = qs.filter(date__gte=date_from)
        if date_to:
            qs = qs.filter(date__lte=date_to)

        if user.role == "employee":
            return qs.filter(employee=user)
        if user.role == "manager":
            return qs.filter(Q(employee__direct_manager=user) | Q(employee=user))
        return qs.all()

    def perform_create(self, serializer):
        record = serializer.save(created_by=self.request.user, source="manual")
        log_action(self.request.user, "attendance.created", "AttendanceRecord", record.id, request=self.request)

    def perform_destroy(self, instance):
        log_action(self.request.user, "attendance.deleted", "AttendanceRecord", instance.id, request=self.request)
        instance.delete()

    @action(detail=False, methods=["get"], url_path="template")
    def download_template(self, request):
        wb = generate_template_workbook()
        buffer = BytesIO()
        wb.save(buffer)
        buffer.seek(0)
        response = HttpResponse(
            buffer.read(),
            content_type="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
        )
        response["Content-Disposition"] = 'attachment; filename="attendance_template.xlsx"'
        return response

    @action(detail=False, methods=["post"], url_path="import", parser_classes=[MultiPartParser])
    def import_excel(self, request):
        file = request.FILES.get("file")
        if not file:
            return Response({"detail": "No file provided."}, status=400)
        if not file.name.endswith((".xlsx", ".xls")):
            return Response({"detail": "Only Excel files (.xlsx, .xls) are accepted."}, status=400)

        with tempfile.NamedTemporaryFile(delete=False, suffix=".xlsx") as tmp:
            for chunk in file.chunks():
                tmp.write(chunk)
            tmp_path = tmp.name

        file_key = f"attendance-imports/{request.user.id}/{file.name}"
        import_job = AttendanceImport.objects.create(
            uploaded_by=request.user,
            file_path=file_key,
        )

        try:
            process_attendance_import(import_job, tmp_path)
        finally:
            os.unlink(tmp_path)

        log_action(self.request.user, "attendance.import", "AttendanceImport", import_job.id, request=request)
        return Response(AttendanceImportSerializer(import_job).data, status=201)

    @action(detail=True, methods=["get"], url_path="status")
    def import_status(self, request, pk=None):
        try:
            job = AttendanceImport.objects.get(pk=pk, uploaded_by=request.user)
        except AttendanceImport.DoesNotExist:
            return Response({"detail": "Not found."}, status=404)
        return Response(AttendanceImportSerializer(job).data)

    @action(detail=False, methods=["get"], url_path="report")
    def report(self, request):
        qs = self.get_queryset()
        summary = qs.values("status").annotate(count=Count("id")).order_by("status")
        total = qs.count()
        return Response({
            "total": total,
            "by_status": list(summary),
        })
