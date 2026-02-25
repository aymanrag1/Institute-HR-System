def log_action(user, action: str, entity_type: str, entity_id,
               old_data: dict = None, new_data: dict = None, request=None):
    from apps.audit.models import AuditLog
    ip = None
    user_agent = ""
    if request:
        x_forwarded = request.META.get("HTTP_X_FORWARDED_FOR")
        ip = x_forwarded.split(",")[0].strip() if x_forwarded else request.META.get("REMOTE_ADDR")
        user_agent = request.META.get("HTTP_USER_AGENT", "")

    AuditLog.objects.create(
        user=user,
        action=action,
        entity_type=entity_type,
        entity_id=str(entity_id),
        old_data=old_data or {},
        new_data=new_data or {},
        ip_address=ip,
        user_agent=user_agent,
    )
