import boto3
from botocore.client import Config
from django.conf import settings
from datetime import datetime


class MinIOClient:
    def __init__(self):
        self.client = boto3.client(
            "s3",
            endpoint_url=settings.MINIO_ENDPOINT,
            aws_access_key_id=settings.MINIO_ACCESS_KEY,
            aws_secret_access_key=settings.MINIO_SECRET_KEY,
            config=Config(signature_version="s3v4"),
            region_name="us-east-1",
        )
        self.bucket = settings.MINIO_BUCKET

    def _ensure_bucket(self):
        try:
            self.client.head_bucket(Bucket=self.bucket)
        except Exception:
            self.client.create_bucket(Bucket=self.bucket)

    def upload_file(self, key: str, file_data: bytes, content_type: str = "application/octet-stream") -> str:
        self._ensure_bucket()
        self.client.put_object(
            Bucket=self.bucket,
            Key=key,
            Body=file_data,
            ContentType=content_type,
        )
        return key

    def upload_signature(self, user_id: str, file_data: bytes, content_type: str = "image/png") -> str:
        ts = int(datetime.now().timestamp())
        key = f"signatures/{user_id}/signature_{ts}.png"
        return self.upload_file(key, file_data, content_type)

    def upload_attachment(self, prefix: str, filename: str, file_data: bytes, content_type: str) -> str:
        key = f"{prefix}/{filename}"
        return self.upload_file(key, file_data, content_type)

    def get_object(self, key: str):
        return self.client.get_object(Bucket=self.bucket, Key=key)["Body"]

    def generate_presigned_url(self, key: str, expires_in: int = 3600) -> str:
        if not key:
            return ""
        try:
            return self.client.generate_presigned_url(
                "get_object",
                Params={"Bucket": self.bucket, "Key": key},
                ExpiresIn=expires_in,
            )
        except Exception:
            return ""

    def delete_object(self, key: str):
        try:
            self.client.delete_object(Bucket=self.bucket, Key=key)
        except Exception:
            pass


storage_client = MinIOClient()
