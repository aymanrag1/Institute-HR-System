from rest_framework import serializers
from .models import OvertimeRequest, OvertimeApproval
from apps.core.storage import storage_client


class OvertimeApprovalSerializer(serializers.ModelSerializer):
    approver_name = serializers.SerializerMethodField()
    signature_url = serializers.SerializerMethodField()

    class Meta:
        model = OvertimeApproval
        fields = ["id", "approver", "approver_name", "approver_role", "action",
                  "notes", "signature_file", "signature_url", "approved_at"]

    def get_approver_name(self, obj): return obj.approver.get_full_name("ar")
    def get_signature_url(self, obj):
        return storage_client.generate_presigned_url(obj.signature_file) if obj.signature_file else None


class OvertimeRequestSerializer(serializers.ModelSerializer):
    approvals        = OvertimeApprovalSerializer(many=True, read_only=True)
    employee_name    = serializers.SerializerMethodField()
    status_display   = serializers.CharField(source="get_status_display", read_only=True)

    class Meta:
        model = OvertimeRequest
        fields = [
            "id", "employee", "employee_name",
            "date", "start_time", "end_time", "total_hours",
            "reason", "status", "status_display",
            "approvals", "created_at", "updated_at",
        ]
        read_only_fields = ["id", "employee", "total_hours", "status", "created_at", "updated_at"]

    def get_employee_name(self, obj): return obj.employee.get_full_name("ar")
