import json
import logging
import os
import time

from cryptography.fernet import Fernet

from backend.config.settings import settings

logger = logging.getLogger(__name__)

_client = None  # instagrapi.Client instance
_logged_username: str | None = None
_login_time: float | None = None  # Unix timestamp of last successful login
_last_login_error: str | None = None


def _classify_login_error(exc: Exception) -> str:
    msg = str(exc)
    low = msg.lower()
    if "submit_phone" in low:
        return (
            "Instagram requiere verificación por teléfono para esta cuenta. "
            "Completa el challenge en la app de Instagram y vuelve a intentarlo."
        )
    if "challenge" in low:
        return (
            "Instagram ha solicitado una verificación de seguridad (challenge). "
            "Abre Instagram, completa la verificación y vuelve a iniciar sesión."
        )
    if "two_factor" in low or "2fa" in low:
        return "Esta cuenta requiere verificación en dos pasos (2FA). Completa la verificación e inténtalo de nuevo."
    if "bad password" in low or "incorrect" in low:
        return "Usuario o contraseña incorrectos."
    return "No se pudo iniciar sesión en Instagram en este momento. Inténtalo de nuevo en unos minutos."


def _get_fernet() -> Fernet:
    """Return a Fernet instance, generating and persisting the key if needed."""
    key = settings.session_key.strip()
    if not key:
        # Check if key is stored in a key file next to session file
        key_file = settings.session_file + ".key"
        if os.path.exists(key_file):
            with open(key_file, "rb") as f:
                key = f.read().decode()
        else:
            key = Fernet.generate_key().decode()
            os.makedirs(os.path.dirname(key_file), exist_ok=True)
            with open(key_file, "wb") as f:
                f.write(key.encode())
            logger.info("Generated new session encryption key: %s", key_file)
    return Fernet(key.encode())


def is_logged_in() -> bool:
    return _client is not None


def get_session_info() -> dict:
    """Return session status with username and age in hours."""
    if _client is None:
        return {"logged_in": False, "username": None, "session_age_hours": None}
    age_hours = None
    if _login_time is not None:
        age_hours = round((time.time() - _login_time) / 3600, 1)
    return {"logged_in": True, "username": _logged_username, "session_age_hours": age_hours}


def get_last_login_error() -> str | None:
    return _last_login_error


def get_client():
    """Return the active instagrapi client or raise if not logged in."""
    if _client is None:
        raise RuntimeError("No active Instagram session. Login first.")
    return _client


async def login(username: str, password: str) -> bool:
    """Login to Instagram and save encrypted session."""
    global _client, _logged_username, _login_time, _last_login_error
    try:
        from instagrapi import Client  # lazy import

        cl = Client()

        # Apply proxy if configured
        if settings.proxy_url:
            cl.set_proxy(settings.proxy_url)
            logger.info("Using proxy for instagrapi client: %s", settings.proxy_url.split("://")[1] if "://" in settings.proxy_url else settings.proxy_url)

        cl.login(username, password)
        _client = cl
        _logged_username = username
        _login_time = time.time()
        _last_login_error = None
        _save_session(cl)
        logger.info("Instagram login successful for %s", username)
        return True
    except Exception as exc:
        logger.error("Instagram login failed: %s", exc)
        _last_login_error = _classify_login_error(exc)
        return False


async def load_session() -> bool:
    """Try to restore session from encrypted file. Returns True if successful."""
    global _client
    session_file = settings.session_file
    if not os.path.exists(session_file):
        return False
    try:
        fernet = _get_fernet()
        with open(session_file, "rb") as f:
            encrypted = f.read()
        data = json.loads(fernet.decrypt(encrypted).decode())

        from instagrapi import Client  # lazy import

        cl = Client()

        # Apply proxy if configured
        if settings.proxy_url:
            cl.set_proxy(settings.proxy_url)
            logger.info("Using proxy for restored session: %s", settings.proxy_url.split("://")[1] if "://" in settings.proxy_url else settings.proxy_url)

        cl.set_settings(data)
        cl.get_timeline_feed()  # Verify session is still valid
        _client = cl
        _login_time = os.path.getmtime(session_file)
        try:
            _logged_username = cl.username
        except Exception:
            pass
        logger.info("Restored Instagram session from %s", session_file)
        return True
    except Exception as exc:
        logger.warning("Could not restore session: %s", exc)
        _client = None
        return False


async def logout() -> None:
    global _client, _logged_username, _login_time
    if _client:
        try:
            _client.logout()
        except Exception:
            pass
        _client = None
    _logged_username = None
    _login_time = None
    # Remove session file from disk
    session_file = settings.session_file
    if os.path.exists(session_file):
        try:
            os.remove(session_file)
        except Exception:
            pass


def _save_session(cl) -> None:
    session_file = settings.session_file
    os.makedirs(os.path.dirname(session_file), exist_ok=True)
    fernet = _get_fernet()
    data = json.dumps(cl.get_settings()).encode()
    encrypted = fernet.encrypt(data)
    with open(session_file, "wb") as f:
        f.write(encrypted)
    logger.debug("Session saved to %s", session_file)


# ── Multi-account session helpers ─────────────────────────────────────────────

def _account_session_path(username: str) -> str:
    """Return the encrypted session file path for a pool account."""
    return os.path.join(settings.sessions_dir, f"{username}.json.enc")


async def login_account(username: str, password: str, proxy_url: str | None = None):
    """Login an account for the pool. Returns the instagrapi Client or None on failure."""
    try:
        from instagrapi import Client  # lazy import

        cl = Client()
        if proxy_url:
            cl.set_proxy(proxy_url)
        elif settings.proxy_url:
            cl.set_proxy(settings.proxy_url)

        cl.login(username, password)

        os.makedirs(settings.sessions_dir, exist_ok=True)
        session_path = _account_session_path(username)
        fernet = _get_fernet()
        encrypted = fernet.encrypt(json.dumps(cl.get_settings()).encode())
        with open(session_path, "wb") as f:
            f.write(encrypted)

        logger.info("Pool account login successful: %s", username)
        return cl
    except Exception as exc:
        logger.error("Pool account login failed for %s: %s", username, exc)
        return None


async def load_account_session(username: str, proxy_url: str | None = None):
    """Restore an encrypted session for a pool account. Returns Client or None."""
    session_path = _account_session_path(username)
    if not os.path.exists(session_path):
        return None
    try:
        fernet = _get_fernet()
        with open(session_path, "rb") as f:
            encrypted = f.read()
        data = json.loads(fernet.decrypt(encrypted).decode())

        from instagrapi import Client  # lazy import

        cl = Client()
        if proxy_url:
            cl.set_proxy(proxy_url)
        elif settings.proxy_url:
            cl.set_proxy(settings.proxy_url)

        cl.set_settings(data)
        cl.get_timeline_feed()  # Verify session still valid
        logger.info("Pool account session restored: %s", username)
        return cl
    except Exception as exc:
        logger.warning("Could not restore pool session for %s: %s", username, exc)
        return None


async def logout_account(username: str) -> None:
    """Clear the session file for a pool account."""
    session_path = _account_session_path(username)
    if os.path.exists(session_path):
        try:
            os.remove(session_path)
        except Exception:
            pass
    logger.info("Pool account session cleared: %s", username)
