from rest_framework import serializers
from .models import LeaveRequest, LeaveApproval
from apps.core.storage import storage_client


class LeaveApprovalSerializer(serializers.ModelSerializer):
    approver_name    = serializers.SerializerMethodField()
    approver_name_en = serializers.SerializerMethodField()
    signature_url    = serializers.SerializerMethodField()

    class Meta:
        model = LeaveApproval
        fields = [
            "id", "approver", "approver_name", "approver_name_en",
            "approver_role", "action", "notes",
            "signature_file", "signature_url",
            "sequence_order", "approved_at",
        ]

    def get_approver_name(self, obj):
        return obj.approver.get_full_name("ar")

    def get_approver_name_en(self, obj):
        return obj.approver.get_full_name("en")

    def get_signature_url(self, obj):
        return storage_client.generate_presigned_url(obj.signature_file) if obj.signature_file else None


class LeaveRequestSerializer(serializers.ModelSerializer):
    approvals        = LeaveApprovalSerializer(many=True, read_only=True)
    employee_name    = serializers.SerializerMethodField()
    employee_name_en = serializers.SerializerMethodField()
    department_name  = serializers.SerializerMethodField()
    leave_type_display = serializers.CharField(source="get_leave_type_display", read_only=True)
    status_display     = serializers.CharField(source="get_status_display",     read_only=True)

    class Meta:
        model = LeaveRequest
        fields = [
            "id", "employee", "employee_name", "employee_name_en",
            "department_name", "leave_type", "leave_type_display",
            "start_date", "end_date", "total_days", "reason",
            "status", "status_display", "rejection_note",
            "approvals", "created_at", "updated_at",
        ]
        read_only_fields = ["id", "employee", "total_days", "status", "rejection_note", "created_at", "updated_at"]

    def get_employee_name(self, obj):
        return obj.employee.get_full_name("ar")

    def get_employee_name_en(self, obj):
        return obj.employee.get_full_name("en")

    def get_department_name(self, obj):
        if obj.employee.department:
            return {"ar": obj.employee.department.name_ar, "en": obj.employee.department.name_en}
        return None
