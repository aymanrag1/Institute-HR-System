import uuid
from django.db import models
from decimal import Decimal


class OvertimeStatus(models.TextChoices):
    PENDING_MANAGER = "pending_manager", "Pending Manager"
    PENDING_HR      = "pending_hr",      "Pending HR"
    APPROVED        = "approved",        "Approved"
    REJECTED        = "rejected",        "Rejected"


class OvertimeRequest(models.Model):
    id          = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    employee    = models.ForeignKey(
        "authentication.User", on_delete=models.CASCADE, related_name="overtime_requests"
    )
    date        = models.DateField()
    start_time  = models.TimeField()
    end_time    = models.TimeField()
    total_hours = models.DecimalField(max_digits=5, decimal_places=2, default=Decimal("0.00"))
    reason      = models.TextField()
    status      = models.CharField(
        max_length=30, choices=OvertimeStatus.choices, default=OvertimeStatus.PENDING_MANAGER
    )
    created_at  = models.DateTimeField(auto_now_add=True)
    updated_at  = models.DateTimeField(auto_now=True)

    class Meta:
        db_table = "overtime_requests"
        ordering = ["-created_at"]

    def save(self, *args, **kwargs):
        if self.start_time and self.end_time:
            from datetime import datetime, date
            dt_start = datetime.combine(date.today(), self.start_time)
            dt_end   = datetime.combine(date.today(), self.end_time)
            diff = dt_end - dt_start
            if diff.total_seconds() > 0:
                self.total_hours = Decimal(str(round(diff.total_seconds() / 3600, 2)))
        super().save(*args, **kwargs)

    def __str__(self):
        return f"Overtime {self.id} — {self.employee} [{self.date}]"


class OvertimeApproval(models.Model):
    id             = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    overtime       = models.ForeignKey(OvertimeRequest, on_delete=models.CASCADE, related_name="approvals")
    approver       = models.ForeignKey(
        "authentication.User", on_delete=models.CASCADE, related_name="overtime_approvals"
    )
    approver_role  = models.CharField(max_length=20)
    action         = models.CharField(max_length=20)
    notes          = models.TextField(blank=True)
    signature_file = models.CharField(max_length=500, blank=True)
    approved_at    = models.DateTimeField(auto_now_add=True)

    class Meta:
        db_table = "overtime_approvals"
