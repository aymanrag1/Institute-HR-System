import uuid
from django.db import models


class Notification(models.Model):
    id          = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    recipient   = models.ForeignKey(
        "authentication.User", on_delete=models.CASCADE, related_name="notifications"
    )
    title_ar    = models.CharField(max_length=300)
    title_en    = models.CharField(max_length=300)
    body_ar     = models.TextField(blank=True)
    body_en     = models.TextField(blank=True)
    entity_type = models.CharField(max_length=50, blank=True)
    entity_id   = models.CharField(max_length=50, blank=True)
    is_read     = models.BooleanField(default=False)
    created_at  = models.DateTimeField(auto_now_add=True)

    class Meta:
        db_table = "notifications"
        ordering = ["-created_at"]

    def __str__(self):
        return f"Notification → {self.recipient} [{self.title_en}]"
