from django.urls import path
from rest_framework_simplejwt.views import TokenRefreshView
from . import views

urlpatterns = [
    path("login/",           views.LoginView.as_view(),       name="auth-login"),
    path("refresh/",         TokenRefreshView.as_view(),      name="auth-refresh"),
    path("logout/",          views.logout_view,                name="auth-logout"),
    path("me/",              views.me_view,                    name="auth-me"),
    path("change-password/", views.change_password_view,       name="auth-change-password"),
    path("signature/",       views.upload_signature_view,      name="auth-signature"),
]
