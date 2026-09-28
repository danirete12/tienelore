"""
quick_publish.py
Publica artículos en tienelore.com usando la API custom del plugin
"TieneLore Publish API" (wp-plugin/tienelore-publish-api/).

Mismo patrón que projects/plottwist-web/scripts/quick_publish.py en el
repo de PlotTwist — si ya conoces ese script, este es prácticamente
idéntico salvo por el namespace de la API y la falta de integración con
Google Indexing (TieneLore no la tiene configurada).

Flujo: crear borrador -> asignar categoría/autor/etiquetas (API estándar
de WordPress) -> subir imágenes -> publicar (o dejar en borrador).
"""

import io
import json
import logging
import mimetypes
import sys
import time
import unicodedata
from pathlib import Path

import requests

try:
    from PIL import Image
except ImportError:
    Image = None

logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(message)s")
logger = logging.getLogger(__name__)

SETTINGS_FILE = Path(__file__).parent.parent / "config" / "settings.json"

MAX_IMAGE_DIMENSION = 1600
WEBP_QUALITY = 82

REQUEST_TIMEOUT = 45
MAX_RETRIES = 4
RETRY_BACKOFF = 5  # segundos: 5, 10, 20, 40

# Aproximación del ancho real de .prose (max-width: 66ch, ~17.5px de
# fuente) menos el padding lateral del .container que lo envuelve — a
# falta de una auditoría de PageSpeed real (TieneLore todavía no tiene
# tráfico), es una estimación razonable, no un valor medido. Revisar si
# alguna vez se audita el sitio en producción.
_ARTICLE_BODY_WIDTH = 640
_ARTICLE_BODY_MOBILE_PADDING = 32  # 16px de gutter a cada lado


def _optimize_image(path: Path) -> tuple[bytes, str, str]:
    """Redimensiona (si hace falta) y convierte a WebP antes de subir.
    Devuelve (bytes, content_type, filename)."""
    original_bytes = path.read_bytes()
    original_type = mimetypes.guess_type(str(path))[0] or "image/jpeg"

    if not Image:
        return original_bytes, original_type, path.name

    try:
        img = Image.open(io.BytesIO(original_bytes))
        img.load()
        width, height = img.size
        if max(width, height) > MAX_IMAGE_DIMENSION:
            scale = MAX_IMAGE_DIMENSION / max(width, height)
            img = img.resize((round(width * scale), round(height * scale)), Image.LANCZOS)

        if img.mode in ("RGBA", "P", "LA"):
            rgba = img.convert("RGBA")
            background = Image.new("RGB", rgba.size, (255, 255, 255))
            background.paste(rgba, mask=rgba.split()[-1])
            img = background

        buffer = io.BytesIO()
        img.save(buffer, format="WEBP", quality=WEBP_QUALITY, method=6)
        optimized_bytes = buffer.getvalue()

        if len(optimized_bytes) >= len(original_bytes):
            return original_bytes, original_type, path.name

        filename = path.stem + ".webp"
        logger.info(
            f"Imagen optimizada: {path.name} {len(original_bytes) // 1024}KB → {filename} {len(optimized_bytes) // 1024}KB"
        )
        return optimized_bytes, "image/webp", filename
    except Exception as e:
        logger.warning(f"No se pudo optimizar {path.name}, se sube tal cual: {e}")
        return original_bytes, original_type, path.name


_CONTENT_IMAGE_SIZE_PREFERENCE = ["medium_large", "tl-card", "large", "tl-hero", "medium", "tl-thumb"]


def _pick_content_image_size(media: dict) -> tuple[str, int, int]:
    sizes = (media.get("media_details") or {}).get("sizes") or {}
    for size_name in _CONTENT_IMAGE_SIZE_PREFERENCE:
        size = sizes.get(size_name)
        if size and size.get("source_url"):
            return size["source_url"], size.get("width", 0), size.get("height", 0)
    details = media.get("media_details") or {}
    return media.get("source_url", ""), details.get("width", 0), details.get("height", 0)


def _build_content_image_srcset(media: dict) -> tuple[str, str]:
    sizes = (media.get("media_details") or {}).get("sizes") or {}
    candidates = []
    for size in sizes.values():
        url = size.get("source_url")
        width = size.get("width")
        if url and width:
            candidates.append((width, url))
    details = media.get("media_details") or {}
    full_url, full_width = media.get("source_url"), details.get("width")
    if full_url and full_width and not any(w == full_width for w, _ in candidates):
        candidates.append((full_width, full_url))

    candidates = sorted(set(candidates))
    srcset = ", ".join(f"{url} {width}w" for width, url in candidates)
    sizes_attr = (
        f"(max-width: 700px) calc(100vw - {_ARTICLE_BODY_MOBILE_PADDING}px), "
        f"{_ARTICLE_BODY_WIDTH}px"
    )
    return srcset, sizes_attr


_SOURCE_LABELS = {
    "wikimedia": "Wikimedia Commons", "official": "Fuente oficial",
    "press": "Nota de prensa", "openverse": "Openverse",
}


def _build_credit_text(credit: str | None, source: str | None) -> str:
    if not credit:
        return ""
    label = _SOURCE_LABELS.get(source or "")
    return f"{credit} / {label}" if label and label not in credit else credit


def _build_credit_overlay(credit_text: str) -> str:
    if not credit_text:
        return ""
    return (
        f'<figcaption style="font-size:0.6rem;line-height:1.4;color:#666;'
        f'text-align:center;margin-top:4px;">{credit_text}</figcaption>'
    )


CATEGORY_ALIASES = {
    "noticias": "actualidad",
    "corazón": "corazon",
    "deportes": "deporte",
    "cocina": "paladar",
    "viral": "virales",
    "casa": "cosas-de-casa",
    "frase": "frase-del-dia",
    "pandora": "caja-de-pandora",
}


def _load_settings() -> dict:
    with open(SETTINGS_FILE, encoding="utf-8") as f:
        return json.load(f)


def _request(method: str, url: str, **kwargs) -> requests.Response:
    kwargs.setdefault("timeout", REQUEST_TIMEOUT)
    last_exc = None
    for attempt in range(1, MAX_RETRIES + 1):
        try:
            resp = requests.request(method, url, **kwargs)
            if resp.status_code >= 500 and attempt < MAX_RETRIES:
                logger.warning(f"{method} {url} -> HTTP {resp.status_code}, reintentando ({attempt}/{MAX_RETRIES})")
                time.sleep(RETRY_BACKOFF * attempt)
                continue
            return resp
        except (requests.exceptions.Timeout, requests.exceptions.ConnectionError) as e:
            last_exc = e
            if attempt < MAX_RETRIES:
                logger.warning(
                    f"{method} {url} -> {type(e).__name__}, reintentando ({attempt}/{MAX_RETRIES}) en {RETRY_BACKOFF * attempt}s"
                )
                time.sleep(RETRY_BACKOFF * attempt)
            else:
                logger.error(f"{method} {url} -> falló tras {MAX_RETRIES} intentos")
    raise last_exc


class TieneLoreAPI:
    def __init__(self):
        s = _load_settings()
        wp = s["wordpress"]
        self.base = wp["base_url"].rstrip("/")
        self.api_url = f"{self.base}/wp-json/tienelore/v1"
        self.wp_api_url = f"{self.base}/wp-json/wp/v2"
        self.api_key = wp["tienelore_api_key"]
        self.wp_auth = (wp["username"], wp["app_password"])
        self.category_ids = s.get("wordpress_category_ids", {})
        self.author_ids = s.get("wordpress_author_ids", {})
        self.custom_headers = {
            "X-Tienelore-Key": self.api_key,
            "User-Agent": "TieneLore/1.0",
        }
        self.wp_headers = {"User-Agent": "TieneLore/1.0"}

    def create_draft(self, article: dict) -> dict:
        payload = {
            "title": article["title"],
            "content": article["content"],
            "status": "draft",
        }
        if article.get("excerpt"):
            payload["excerpt"] = article["excerpt"]
        if article.get("slug"):
            payload["slug"] = article["slug"]

        resp = _request("POST", f"{self.api_url}/posts", headers=self.custom_headers, json=payload)
        resp.raise_for_status()
        data = resp.json()
        logger.info(f"Draft created: ID {data.get('id')}")
        return data

    def get_post(self, post_id: int) -> dict:
        resp = _request("GET", f"{self.api_url}/posts/{post_id}", headers=self.custom_headers)
        resp.raise_for_status()
        return resp.json()

    def update_post(self, post_id: int, fields: dict) -> dict:
        resp = _request("PUT", f"{self.api_url}/posts/{post_id}", headers=self.custom_headers, json=fields)
        resp.raise_for_status()
        return resp.json()

    def publish_post(self, post_id: int) -> dict:
        resp = _request("POST", f"{self.api_url}/posts/{post_id}/publish", headers=self.custom_headers)
        resp.raise_for_status()
        logger.info(f"Post published: ID {post_id}")
        return resp.json()

    def delete_post(self, post_id: int) -> dict:
        resp = _request("DELETE", f"{self.api_url}/posts/{post_id}", headers=self.custom_headers)
        resp.raise_for_status()
        logger.info(f"Post deleted: ID {post_id}")
        return resp.json()

    def upload_local_image(self, image_path: str, alt_text: str = "", caption: str = "") -> dict | None:
        path = Path(image_path)
        if not path.exists():
            logger.warning(f"Image not found: {image_path}")
            return None

        image_bytes, content_type, filename = _optimize_image(path)
        try:
            resp = _request(
                "POST",
                f"{self.wp_api_url}/media",
                auth=self.wp_auth,
                headers={
                    **self.wp_headers,
                    "Content-Disposition": f'attachment; filename="{filename}"',
                    "Content-Type": content_type,
                },
                data=image_bytes,
            )
            resp.raise_for_status()
            media = resp.json()
            fields = {}
            if alt_text:
                fields["alt_text"] = alt_text
            if caption:
                fields["caption"] = caption
            if fields:
                _request(
                    "POST", f"{self.wp_api_url}/media/{media['id']}",
                    auth=self.wp_auth, headers=self.wp_headers, json=fields,
                )
            logger.info(f"Image uploaded: {media.get('source_url')} (ID {media['id']})")
            return media
        except Exception as e:
            logger.warning(f"Image upload failed: {e}")
            return None

    def set_featured_image(self, post_id: int, media_id: int):
        _request(
            "POST", f"{self.wp_api_url}/posts/{post_id}",
            auth=self.wp_auth, headers=self.wp_headers, json={"featured_media": media_id},
        )
        logger.info(f"Featured image set: media {media_id} on post {post_id}")

    def _find_existing_tag(self, tag_name: str) -> int | None:
        try:
            resp = _request(
                "GET", f"{self.wp_api_url}/tags",
                auth=self.wp_auth, headers=self.wp_headers, params={"search": tag_name},
            )
            resp.raise_for_status()
            for tag in resp.json():
                if tag["name"].strip().lower() == tag_name.strip().lower():
                    return tag["id"]
            return None
        except Exception as e:
            logger.warning(f"Error buscando tag '{tag_name}': {e}")
            return None

    def set_tags(self, post_id: int, tags: list[str]) -> bool:
        tag_ids = [tid for tid in (self._find_existing_tag(t) for t in tags) if tid]
        if not tag_ids:
            return False
        resp = _request(
            "POST", f"{self.wp_api_url}/posts/{post_id}",
            auth=self.wp_auth, headers=self.wp_headers, json={"tags": tag_ids},
        )
        resp.raise_for_status()
        logger.info(f"Tags set: {tags} on post {post_id}")
        return True

    def set_author(self, post_id: int, writer: str) -> bool:
        normalized = unicodedata.normalize("NFKD", writer.lower())
        normalized = "".join(c for c in normalized if not unicodedata.combining(c))
        author_id = self.author_ids.get(normalized)
        if not author_id:
            logger.warning(f"No author ID for writer '{writer}', skipping author assignment")
            return False
        resp = _request(
            "POST", f"{self.wp_api_url}/posts/{post_id}",
            auth=self.wp_auth, headers=self.wp_headers, json={"author": author_id},
        )
        resp.raise_for_status()
        logger.info(f"Author set: '{writer}' (ID {author_id}) on post {post_id}")
        return True

    def set_category(self, post_id: int, category: str) -> bool:
        category = CATEGORY_ALIASES.get(category, category)
        category_id = self.category_ids.get(category)
        if not category_id:
            logger.warning(f"No category ID for '{category}', skipping category assignment")
            return False
        resp = _request(
            "POST", f"{self.wp_api_url}/posts/{post_id}",
            auth=self.wp_auth, headers=self.wp_headers, json={"categories": [category_id]},
        )
        resp.raise_for_status()
        logger.info(f"Category set: '{category}' (ID {category_id}) on post {post_id}")
        return True


def publish_with_images(
    article: dict,
    image_paths: list[str] | None = None,
    auto_publish: bool = False,
) -> dict:
    """Crea un borrador en tienelore.com con imágenes locales.
    Si auto_publish=True, publica directamente sin pasar por borrador."""
    api = TieneLoreAPI()

    draft = api.create_draft(article)
    post_id = draft.get("id")

    if article.get("category"):
        api.set_category(post_id, article["category"])

    if article.get("writer"):
        api.set_author(post_id, article["writer"])

    if article.get("tags"):
        api.set_tags(post_id, article["tags"])

    content_updated = False
    content = article.get("content", "")
    if image_paths:
        for i, img_item in enumerate(image_paths[:2]):
            if not img_item:
                continue
            img_path = img_item["path"] if isinstance(img_item, dict) else img_item
            credit = img_item.get("credit") if isinstance(img_item, dict) else None
            credit_source = img_item.get("source") if isinstance(img_item, dict) else None
            credit_text = _build_credit_text(credit, credit_source)

            media = api.upload_local_image(
                img_path, alt_text=article.get("title", ""), caption=credit_text or "",
            )
            if not media:
                continue

            if i == 0:
                api.set_featured_image(post_id, media["id"])
            elif i == 1:
                img_url, img_w, img_h = _pick_content_image_size(media)
                srcset, sizes_attr = _build_content_image_srcset(media)
                overlay = _build_credit_overlay(credit_text)
                srcset_attrs = f'srcset="{srcset}" sizes="{sizes_attr}" ' if srcset else ""
                img_html = (
                    f'<figure style="position:relative;text-align:center;margin:2em 0;">'
                    f'<img src="{img_url}" {srcset_attrs}alt="{article.get("title", "")}" '
                    f'width="{img_w}" height="{img_h}" loading="lazy" '
                    f'style="max-width:100%;height:auto;display:block;margin:0 auto;">'
                    f'{overlay}'
                    f'</figure>'
                )
                parts = content.split("</p>")
                if len(parts) >= 3:
                    mid = len(parts) // 2
                    content = "</p>".join(parts[:mid]) + "</p>" + img_html + "</p>".join(parts[mid:])
                else:
                    mid = len(content) // 2
                    content = content[:mid] + img_html + content[mid:]
                content_updated = True

    result = {
        "status": "draft",
        "post_id": post_id,
        "title": article["title"],
        "wp_admin_url": f"{api.base}/wp-admin/post.php?post={post_id}&action=edit",
    }

    if auto_publish:
        api.publish_post(post_id)
        result["status"] = "published"

    # Igual que en PlotTwist: el contenido con la segunda imagen se
    # guarda DESPUÉS de publicar, vía la REST API estándar (PATCH
    # parcial de verdad), nunca con update_post() del endpoint custom.
    if content_updated:
        _request(
            "POST", f"{api.wp_api_url}/posts/{post_id}",
            auth=api.wp_auth, headers=api.wp_headers, json={"content": content},
        )

    return result


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Uso: python quick_publish.py <article.json> [imagen1] [imagen2]")
        sys.exit(1)

    with open(sys.argv[1], encoding="utf-8") as f:
        article = json.load(f)

    images = sys.argv[2:] if len(sys.argv) > 2 else None
    result = publish_with_images(article, images)
    print(json.dumps(result, indent=2, ensure_ascii=False))
