from rest_framework import viewsets, filters
from rest_framework.permissions import IsAuthenticated
from django_filters.rest_framework import DjangoFilterBackend

from apps.authentication.models import User
from apps.authentication.serializers import UserCreateSerializer
from apps.core.permissions import IsAdminUser, IsHRManagerOrAdmin
from apps.core.audit import log_action
from .models import Department
from .serializers import DepartmentSerializer, UserListSerializer, UserUpdateSerializer


class UserViewSet(viewsets.ModelViewSet):
    queryset = User.objects.select_related("department", "direct_manager").filter(is_active=True)
    filter_backends = [DjangoFilterBackend, filters.SearchFilter, filters.OrderingFilter]
    filterset_fields = ["role", "department", "is_active"]
    search_fields = ["email", "first_name_ar", "first_name_en", "last_name_ar", "last_name_en", "national_id"]
    ordering_fields = ["date_joined", "first_name_ar"]

    def get_serializer_class(self):
        if self.action == "create":
            return UserCreateSerializer
        if self.action in ["update", "partial_update"]:
            return UserUpdateSerializer
        return UserListSerializer

    def get_permissions(self):
        if self.action == "create":
            return [IsAdminUser()]
        if self.action in ["update", "partial_update"]:
            return [IsHRManagerOrAdmin()]
        if self.action == "destroy":
            return [IsAdminUser()]
        return [IsAuthenticated()]

    def perform_destroy(self, instance):
        instance.is_active = False
        instance.save(update_fields=["is_active"])
        log_action(self.request.user, "user.deactivated", "User", instance.id, request=self.request)

    def perform_create(self, serializer):
        user = serializer.save()
        log_action(self.request.user, "user.created", "User", user.id, new_data={"email": user.email}, request=self.request)


class DepartmentViewSet(viewsets.ModelViewSet):
    queryset = Department.objects.select_related("head").all()
    serializer_class = DepartmentSerializer
    filter_backends = [filters.SearchFilter]
    search_fields = ["name_ar", "name_en", "code"]

    def get_permissions(self):
        if self.action in ["create", "update", "partial_update", "destroy"]:
            return [IsAdminUser()]
        return [IsAuthenticated()]
