from rest_framework import serializers
from apps.authentication.models import User
from apps.authentication.serializers import UserBasicSerializer, UserCreateSerializer
from .models import Department


class DepartmentSerializer(serializers.ModelSerializer):
    member_count = serializers.SerializerMethodField()

    class Meta:
        model = Department
        fields = ["id", "name_ar", "name_en", "code", "head", "is_active", "created_at", "member_count"]
        read_only_fields = ["id", "created_at"]

    def get_member_count(self, obj):
        return obj.members.filter(is_active=True).count()


class UserListSerializer(serializers.ModelSerializer):
    full_name_ar    = serializers.SerializerMethodField()
    full_name_en    = serializers.SerializerMethodField()
    department_name = serializers.SerializerMethodField()
    manager_name    = serializers.SerializerMethodField()

    class Meta:
        model = User
        fields = [
            "id", "email", "role", "national_id", "phone",
            "first_name_ar", "first_name_en", "last_name_ar", "last_name_en",
            "full_name_ar", "full_name_en",
            "department", "department_name",
            "direct_manager", "manager_name",
            "is_active", "date_joined",
        ]

    def get_full_name_ar(self, obj): return obj.get_full_name("ar")
    def get_full_name_en(self, obj): return obj.get_full_name("en")

    def get_department_name(self, obj):
        if obj.department:
            return {"ar": obj.department.name_ar, "en": obj.department.name_en}
        return None

    def get_manager_name(self, obj):
        if obj.direct_manager:
            return obj.direct_manager.get_full_name("ar")
        return None


class UserUpdateSerializer(serializers.ModelSerializer):
    class Meta:
        model = User
        fields = [
            "first_name_ar", "first_name_en", "last_name_ar", "last_name_en",
            "national_id", "phone", "department", "direct_manager", "role", "is_active",
        ]
