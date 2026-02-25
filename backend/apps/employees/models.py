import uuid
from django.db import models


class Department(models.Model):
    id         = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    name_ar    = models.CharField(max_length=200)
    name_en    = models.CharField(max_length=200)
    code       = models.CharField(max_length=20, unique=True)
    head       = models.ForeignKey(
        "authentication.User", null=True, blank=True,
        on_delete=models.SET_NULL, related_name="headed_departments"
    )
    is_active  = models.BooleanField(default=True)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        db_table = "departments"
        verbose_name = "Department"

    def __str__(self):
        return self.name_ar
