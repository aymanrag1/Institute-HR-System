import uuid
from django.db import models


class AuditLog(models.Model):
    id          = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    user        = models.ForeignKey(
        "authentication.User", on_delete=models.SET_NULL, null=True, related_name="audit_logs"
    )
    action      = models.CharField(max_length=100)
    entity_type = models.CharField(max_length=50)
    entity_id   = models.CharField(max_length=50)
    old_data    = models.JSONField(default=dict)
    new_data    = models.JSONField(default=dict)
    ip_address  = models.GenericIPAddressField(null=True, blank=True)
    user_agent  = models.TextField(blank=True)
    created_at  = models.DateTimeField(auto_now_add=True)

    class Meta:
        db_table = "audit_logs"
        ordering = ["-created_at"]
        indexes = [
            models.Index(fields=["entity_type", "entity_id"]),
            models.Index(fields=["user", "created_at"]),
        ]

    def __str__(self):
        return f"{self.user} — {self.action} [{self.created_at}]"
