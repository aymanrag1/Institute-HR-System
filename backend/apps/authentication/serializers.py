from rest_framework import serializers
from rest_framework_simplejwt.serializers import TokenObtainPairSerializer
from django.contrib.auth.password_validation import validate_password
from .models import User
from apps.core.storage import storage_client


class CustomTokenObtainPairSerializer(TokenObtainPairSerializer):
    @classmethod
    def get_token(cls, user):
        token = super().get_token(user)
        token["email"] = user.email
        token["role"] = user.role
        token["full_name_ar"] = user.get_full_name("ar")
        token["full_name_en"] = user.get_full_name("en")
        return token

    def validate(self, attrs):
        data = super().validate(attrs)
        data["user"] = UserBasicSerializer(self.user).data
        return data


class UserBasicSerializer(serializers.ModelSerializer):
    full_name_ar   = serializers.SerializerMethodField()
    full_name_en   = serializers.SerializerMethodField()
    signature_url  = serializers.SerializerMethodField()
    department_name = serializers.SerializerMethodField()

    class Meta:
        model = User
        fields = [
            "id", "email", "role",
            "first_name_ar", "first_name_en",
            "last_name_ar", "last_name_en",
            "full_name_ar", "full_name_en",
            "national_id", "phone",
            "department", "department_name",
            "direct_manager",
            "signature_file", "signature_url",
            "is_active", "date_joined",
        ]
        read_only_fields = ["id", "date_joined"]

    def get_full_name_ar(self, obj):
        return obj.get_full_name("ar")

    def get_full_name_en(self, obj):
        return obj.get_full_name("en")

    def get_signature_url(self, obj):
        if obj.signature_file:
            return storage_client.generate_presigned_url(obj.signature_file)
        return None

    def get_department_name(self, obj):
        if obj.department:
            return {"ar": obj.department.name_ar, "en": obj.department.name_en}
        return None


class UserCreateSerializer(serializers.ModelSerializer):
    password  = serializers.CharField(write_only=True, validators=[validate_password])
    password2 = serializers.CharField(write_only=True)

    class Meta:
        model = User
        fields = [
            "email", "password", "password2", "role",
            "first_name_ar", "first_name_en",
            "last_name_ar", "last_name_en",
            "national_id", "phone",
            "department", "direct_manager",
        ]

    def validate(self, attrs):
        if attrs["password"] != attrs.pop("password2"):
            raise serializers.ValidationError({"password": "Passwords do not match."})
        return attrs

    def create(self, validated_data):
        return User.objects.create_user(**validated_data)


class ChangePasswordSerializer(serializers.Serializer):
    old_password = serializers.CharField(required=True)
    new_password = serializers.CharField(required=True, validators=[validate_password])

    def validate_old_password(self, value):
        user = self.context["request"].user
        if not user.check_password(value):
            raise serializers.ValidationError("Old password is incorrect.")
        return value
