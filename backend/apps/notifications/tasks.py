from .models import Notification


def create_notification(recipient_id: str, title_ar: str, title_en: str,
                        entity_type: str = "", entity_id: str = "",
                        body_ar: str = "", body_en: str = ""):
    try:
        from apps.authentication.models import User
        recipient = User.objects.get(id=recipient_id)
        Notification.objects.create(
            recipient=recipient,
            title_ar=title_ar,
            title_en=title_en,
            body_ar=body_ar,
            body_en=body_en,
            entity_type=entity_type,
            entity_id=entity_id,
        )
    except Exception:
        pass
