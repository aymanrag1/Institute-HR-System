from django.contrib import admin
from django.urls import path, include
from django.conf import settings
from django.conf.urls.static import static

urlpatterns = [
    path("admin/", admin.site.urls),
    path("api/v1/auth/",        include("apps.authentication.urls")),
    path("api/v1/users/",       include("apps.employees.urls")),
    path("api/v1/departments/", include("apps.employees.department_urls")),
    path("api/v1/leaves/",      include("apps.leaves.urls")),
    path("api/v1/overtime/",    include("apps.overtime.urls")),
    path("api/v1/attendance/",  include("apps.attendance.urls")),
    path("api/v1/violations/",  include("apps.violations.urls")),
    path("api/v1/notifications/", include("apps.notifications.urls")),
    path("api/v1/audit-logs/",  include("apps.audit.urls")),
]

if settings.DEBUG:
    import debug_toolbar
    urlpatterns = [path("__debug__/", include(debug_toolbar.urls))] + urlpatterns

urlpatterns += static(settings.MEDIA_URL, document_root=settings.MEDIA_ROOT)
