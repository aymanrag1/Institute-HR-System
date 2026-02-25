from rest_framework import serializers
from .models import Violation
from apps.core.storage import storage_client


class ViolationSerializer(serializers.ModelSerializer):
    employee_name  = serializers.SerializerMethodField()
    created_by_name = serializers.SerializerMethodField()
    type_display   = serializers.CharField(source="get_type_display",     read_only=True)
    severity_display = serializers.CharField(source="get_severity_display", read_only=True)
    status_display = serializers.CharField(source="get_status_display",   read_only=True)
    dean_signature_url = serializers.SerializerMethodField()

    class Meta:
        model = Violation
        fields = [
            "id", "employee", "employee_name",
            "created_by", "created_by_name",
            "type", "type_display", "severity", "severity_display",
            "description", "incident_date", "penalty",
            "status", "status_display",
            "dean_notes", "dean_signature", "dean_signature_url",
            "approved_at", "created_at", "updated_at",
        ]
        read_only_fields = [
            "id", "created_by", "status", "dean_notes",
            "dean_signature", "approved_at", "created_at", "updated_at",
        ]

    def get_employee_name(self, obj):   return obj.employee.get_full_name("ar")
    def get_created_by_name(self, obj): return obj.created_by.get_full_name("ar")
    def get_dean_signature_url(self, obj):
        return storage_client.generate_presigned_url(obj.dean_signature) if obj.dean_signature else None
