import uuid
from django.db import models


class ViolationType(models.TextChoices):
    ATTENDANCE  = "attendance",  "Attendance / حضور وانصراف"
    BEHAVIOR    = "behavior",    "Behavior / سلوك"
    POLICY      = "policy",      "Policy Violation / مخالفة لوائح"
    PERFORMANCE = "performance", "Performance / أداء"
    OTHER       = "other",       "Other / أخرى"


class ViolationSeverity(models.TextChoices):
    MINOR    = "minor",    "Minor / بسيط"
    MODERATE = "moderate", "Moderate / متوسط"
    MAJOR    = "major",    "Major / جسيم"
    CRITICAL = "critical", "Critical / بالغ"


class ViolationStatus(models.TextChoices):
    PENDING_DEAN = "pending_dean", "Pending Dean Approval"
    APPROVED     = "approved",     "Approved"
    REJECTED     = "rejected",     "Rejected"


class Violation(models.Model):
    id             = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    employee       = models.ForeignKey(
        "authentication.User", on_delete=models.CASCADE, related_name="violations"
    )
    created_by     = models.ForeignKey(
        "authentication.User", on_delete=models.CASCADE, related_name="created_violations"
    )
    type           = models.CharField(max_length=20, choices=ViolationType.choices)
    severity       = models.CharField(max_length=20, choices=ViolationSeverity.choices)
    description    = models.TextField()
    incident_date  = models.DateField()
    penalty        = models.TextField(blank=True)
    status         = models.CharField(
        max_length=20, choices=ViolationStatus.choices, default=ViolationStatus.PENDING_DEAN
    )
    dean_notes     = models.TextField(blank=True)
    dean_signature = models.CharField(max_length=500, blank=True)
    approved_at    = models.DateTimeField(null=True, blank=True)
    created_at     = models.DateTimeField(auto_now_add=True)
    updated_at     = models.DateTimeField(auto_now=True)

    class Meta:
        db_table = "violations"
        ordering = ["-created_at"]

    def __str__(self):
        return f"Violation {self.id} — {self.employee} [{self.severity}]"
