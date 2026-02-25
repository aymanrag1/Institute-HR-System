import uuid
from django.contrib.auth.models import AbstractBaseUser, PermissionsMixin, BaseUserManager
from django.db import models


class UserRole(models.TextChoices):
    EMPLOYEE   = "employee",   "Employee / موظف"
    MANAGER    = "manager",    "Direct Manager / المدير المباشر"
    HR_MANAGER = "hr_manager", "HR Manager / مدير الموارد البشرية"
    DEAN       = "dean",       "Dean / العميد"
    ADMIN      = "admin",      "System Admin / مدير النظام"


class CustomUserManager(BaseUserManager):
    def create_user(self, email, password=None, **extra):
        if not email:
            raise ValueError("Email is required")
        email = self.normalize_email(email)
        user = self.model(email=email, **extra)
        user.set_password(password)
        user.save(using=self._db)
        return user

    def create_superuser(self, email, password, **extra):
        extra.setdefault("role", UserRole.ADMIN)
        extra.setdefault("is_staff", True)
        extra.setdefault("is_superuser", True)
        return self.create_user(email, password, **extra)


class User(AbstractBaseUser, PermissionsMixin):
    id             = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    email          = models.EmailField(unique=True)
    first_name_ar  = models.CharField(max_length=100, blank=True)
    first_name_en  = models.CharField(max_length=100, blank=True)
    last_name_ar   = models.CharField(max_length=100, blank=True)
    last_name_en   = models.CharField(max_length=100, blank=True)
    national_id    = models.CharField(max_length=20, unique=True, null=True, blank=True)
    phone          = models.CharField(max_length=20, blank=True)
    role           = models.CharField(max_length=20, choices=UserRole.choices, default=UserRole.EMPLOYEE)
    department     = models.ForeignKey(
        "employees.Department", null=True, blank=True,
        on_delete=models.SET_NULL, related_name="members"
    )
    direct_manager = models.ForeignKey(
        "self", null=True, blank=True,
        on_delete=models.SET_NULL, related_name="subordinates"
    )
    signature_file = models.CharField(max_length=500, blank=True)
    is_active      = models.BooleanField(default=True)
    is_staff       = models.BooleanField(default=False)
    date_joined    = models.DateTimeField(auto_now_add=True)
    updated_at     = models.DateTimeField(auto_now=True)

    USERNAME_FIELD  = "email"
    REQUIRED_FIELDS = []
    objects = CustomUserManager()

    class Meta:
        db_table = "users"
        verbose_name = "User"
        verbose_name_plural = "Users"

    def get_full_name(self, lang="ar"):
        if lang == "ar":
            return f"{self.first_name_ar} {self.last_name_ar}".strip() or self.email
        return f"{self.first_name_en} {self.last_name_en}".strip() or self.email

    def __str__(self):
        return f"{self.get_full_name()} ({self.email})"
