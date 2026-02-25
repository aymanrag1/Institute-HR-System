from rest_framework import serializers
from .models import AttendanceRecord, AttendanceImport


class AttendanceRecordSerializer(serializers.ModelSerializer):
    employee_name   = serializers.SerializerMethodField()
    status_display  = serializers.CharField(source="get_status_display", read_only=True)

    class Meta:
        model = AttendanceRecord
        fields = [
            "id", "employee", "employee_name",
            "date", "check_in", "check_out",
            "status", "status_display", "source", "notes",
            "created_by", "created_at",
        ]
        read_only_fields = ["id", "source", "created_at"]

    def get_employee_name(self, obj): return obj.employee.get_full_name("ar")


class AttendanceImportSerializer(serializers.ModelSerializer):
    class Meta:
        model = AttendanceImport
        fields = ["id", "uploaded_by", "file_path", "total_rows",
                  "success_rows", "failed_rows", "errors", "status", "created_at"]
        read_only_fields = fields
