from rest_framework.permissions import BasePermission


class IsAdminUser(BasePermission):
    def has_permission(self, request, view):
        return request.user.is_authenticated and request.user.role == "admin"


class IsHRManagerOrAdmin(BasePermission):
    def has_permission(self, request, view):
        return request.user.is_authenticated and request.user.role in ["hr_manager", "admin"]


class IsManagerOrAbove(BasePermission):
    def has_permission(self, request, view):
        return request.user.is_authenticated and request.user.role in [
            "manager", "hr_manager", "dean", "admin"
        ]


class IsDeanOrAdmin(BasePermission):
    def has_permission(self, request, view):
        return request.user.is_authenticated and request.user.role in ["dean", "admin"]


class IsOwnerOrHROrAdmin(BasePermission):
    def has_object_permission(self, request, view, obj):
        user = request.user
        if user.role in ["hr_manager", "dean", "admin"]:
            return True
        if hasattr(obj, "employee_id"):
            return obj.employee_id == user.id
        return getattr(obj, "id", None) == user.id


class CanApproveLeave(BasePermission):
    def has_object_permission(self, request, view, obj):
        from apps.leaves.state_machine import can_approve
        return can_approve(request.user, obj)


class CanApproveOvertime(BasePermission):
    def has_object_permission(self, request, view, obj):
        from apps.overtime.state_machine import can_approve
        return can_approve(request.user, obj)
