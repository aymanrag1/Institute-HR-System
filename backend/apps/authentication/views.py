from rest_framework import status, generics
from rest_framework.decorators import api_view, permission_classes, parser_classes
from rest_framework.permissions import IsAuthenticated, AllowAny
from rest_framework.parsers import MultiPartParser
from rest_framework.response import Response
from rest_framework_simplejwt.views import TokenObtainPairView
from rest_framework_simplejwt.tokens import RefreshToken

from .serializers import (
    CustomTokenObtainPairSerializer,
    UserBasicSerializer,
    ChangePasswordSerializer,
)
from apps.core.storage import storage_client
from apps.core.audit import log_action


class LoginView(TokenObtainPairView):
    serializer_class = CustomTokenObtainPairSerializer
    throttle_scope   = "login"


@api_view(["POST"])
@permission_classes([IsAuthenticated])
def logout_view(request):
    try:
        refresh_token = request.data.get("refresh")
        if refresh_token:
            token = RefreshToken(refresh_token)
            token.blacklist()
    except Exception:
        pass
    return Response({"detail": "Logged out successfully."})


@api_view(["GET"])
@permission_classes([IsAuthenticated])
def me_view(request):
    return Response(UserBasicSerializer(request.user).data)


@api_view(["PUT"])
@permission_classes([IsAuthenticated])
def change_password_view(request):
    serializer = ChangePasswordSerializer(data=request.data, context={"request": request})
    serializer.is_valid(raise_exception=True)
    request.user.set_password(serializer.validated_data["new_password"])
    request.user.save()
    log_action(request.user, "auth.change_password", "User", request.user.id, request=request)
    return Response({"detail": "Password changed successfully."})


@api_view(["POST"])
@permission_classes([IsAuthenticated])
@parser_classes([MultiPartParser])
def upload_signature_view(request):
    file = request.FILES.get("signature")
    if not file:
        return Response({"detail": "No file provided."}, status=400)
    if file.content_type not in ["image/png", "image/jpeg", "image/jpg"]:
        return Response({"detail": "Only PNG/JPEG allowed."}, status=400)
    if file.size > 2 * 1024 * 1024:
        return Response({"detail": "File too large. Max 2MB."}, status=400)

    old_key = request.user.signature_file
    new_key = storage_client.upload_signature(str(request.user.id), file.read(), file.content_type)
    request.user.signature_file = new_key
    request.user.save(update_fields=["signature_file"])

    if old_key:
        storage_client.delete_object(old_key)

    log_action(request.user, "auth.upload_signature", "User", request.user.id, request=request)
    return Response({"signature_url": storage_client.generate_presigned_url(new_key)})
