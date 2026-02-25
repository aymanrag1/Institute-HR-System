import uuid
from django.db import models


class AttendanceStatus(models.TextChoices):
    PRESENT  = "present",  "Present / حاضر"
    ABSENT   = "absent",   "Absent / غائب"
    LATE     = "late",     "Late / متأخر"
    HALF_DAY = "half_day", "Half Day / نصف يوم"
    ON_LEAVE = "on_leave", "On Leave / في إجازة"


class AttendanceRecord(models.Model):
    id         = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    employee   = models.ForeignKey(
        "authentication.User", on_delete=models.CASCADE, related_name="attendance_records"
    )
    date       = models.DateField()
    check_in   = models.TimeField(null=True, blank=True)
    check_out  = models.TimeField(null=True, blank=True)
    status     = models.CharField(max_length=20, choices=AttendanceStatus.choices, default=AttendanceStatus.PRESENT)
    source     = models.CharField(max_length=20, choices=[("manual", "Manual"), ("excel_import", "Excel Import")], default="manual")
    notes      = models.TextField(blank=True)
    created_by = models.ForeignKey(
        "authentication.User", on_delete=models.SET_NULL, null=True,
        related_name="created_attendance_records"
    )
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        db_table = "attendance_records"
        unique_together = [("employee", "date")]
        ordering = ["-date"]

    def __str__(self):
        return f"{self.employee} — {self.date} [{self.status}]"


class AttendanceImport(models.Model):
    id           = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    uploaded_by  = models.ForeignKey(
        "authentication.User", on_delete=models.CASCADE, related_name="attendance_imports"
    )
    file_path    = models.CharField(max_length=500)
    total_rows   = models.IntegerField(default=0)
    success_rows = models.IntegerField(default=0)
    failed_rows  = models.IntegerField(default=0)
    errors       = models.JSONField(default=list)
    status       = models.CharField(
        max_length=20,
        choices=[("pending","Pending"),("processing","Processing"),("completed","Completed"),("failed","Failed")],
        default="pending"
    )
    created_at   = models.DateTimeField(auto_now_add=True)

    class Meta:
        db_table = "attendance_imports"
        ordering = ["-created_at"]
