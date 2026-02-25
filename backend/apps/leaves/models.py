import uuid
from django.db import models


class LeaveType(models.TextChoices):
    ANNUAL    = "annual",    "Annual Leave / إجازة سنوية"
    SICK      = "sick",      "Sick Leave / إجازة مرضية"
    EMERGENCY = "emergency", "Emergency Leave / إجازة طارئة"
    UNPAID    = "unpaid",    "Unpaid Leave / إجازة بدون راتب"
    OTHER     = "other",     "Other / أخرى"


class LeaveStatus(models.TextChoices):
    DRAFT           = "draft",           "Draft / مسودة"
    PENDING_MANAGER = "pending_manager", "Pending Manager / في انتظار المدير"
    PENDING_HR      = "pending_hr",      "Pending HR / في انتظار HR"
    PENDING_DEAN    = "pending_dean",    "Pending Dean / في انتظار العميد"
    APPROVED        = "approved",        "Approved / موافق عليه"
    REJECTED        = "rejected",        "Rejected / مرفوض"
    CANCELLED       = "cancelled",       "Cancelled / ملغي"


class ApprovalAction(models.TextChoices):
    APPROVED = "approved", "Approved"
    REJECTED = "rejected", "Rejected"
    RETURNED = "returned", "Returned for Correction"


class LeaveRequest(models.Model):
    id             = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    employee       = models.ForeignKey(
        "authentication.User", on_delete=models.CASCADE, related_name="leave_requests"
    )
    leave_type     = models.CharField(max_length=20, choices=LeaveType.choices)
    start_date     = models.DateField()
    end_date       = models.DateField()
    total_days     = models.PositiveIntegerField(default=0)
    reason         = models.TextField(blank=True)
    status         = models.CharField(max_length=30, choices=LeaveStatus.choices, default=LeaveStatus.DRAFT)
    rejection_note = models.TextField(blank=True)
    created_at     = models.DateTimeField(auto_now_add=True)
    updated_at     = models.DateTimeField(auto_now=True)

    class Meta:
        db_table = "leave_requests"
        ordering = ["-created_at"]

    def save(self, *args, **kwargs):
        if self.start_date and self.end_date:
            delta = (self.end_date - self.start_date).days + 1
            self.total_days = max(delta, 0)
        super().save(*args, **kwargs)

    def __str__(self):
        return f"Leave {self.id} — {self.employee} [{self.status}]"


class LeaveApproval(models.Model):
    id             = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    leave_request  = models.ForeignKey(LeaveRequest, on_delete=models.CASCADE, related_name="approvals")
    approver       = models.ForeignKey(
        "authentication.User", on_delete=models.CASCADE, related_name="leave_approvals"
    )
    approver_role  = models.CharField(max_length=20)
    action         = models.CharField(max_length=20, choices=ApprovalAction.choices)
    notes          = models.TextField(blank=True)
    signature_file = models.CharField(max_length=500, blank=True)
    sequence_order = models.PositiveIntegerField(default=1)
    approved_at    = models.DateTimeField(auto_now_add=True)

    class Meta:
        db_table = "leave_approvals"
        ordering = ["sequence_order"]

    def __str__(self):
        return f"{self.approver} {self.action} leave {self.leave_request_id}"
