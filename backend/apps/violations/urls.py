from django.urls import path, include
from rest_framework.routers import DefaultRouter
from .views import ViolationViewSet

router = DefaultRouter()
router.register("", ViolationViewSet, basename="violations")

urlpatterns = [path("", include(router.urls))]
