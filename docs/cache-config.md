# BSC-049 — Configuración de Caché

## 1. Browser cache y Gzip (.htaccess)

Ya aplicado en `/wp-content/themes/wp-bsc-theme-v2/../../../.htaccess` (raíz de WordPress).

| Tipo de archivo | Duración de caché |
|----------------|------------------|
| Imágenes (JPEG, PNG, WebP, GIF, SVG) | 1 año |
| Fuentes (woff, woff2) | 1 año |
| CSS | 1 mes |
| JavaScript | 1 mes |

Los assets se sirven con Gzip cuando el módulo `mod_deflate` está disponible.

**Nota:** Los assets incluyen el query string `?ver=BSC_THEME_VERSION` generado por WordPress. Al actualizar `BSC_THEME_VERSION` en `functions.php`, el browser descarga la versión nueva automáticamente.

---

## 2. Page cache (recomendado en producción)

WooCommerce complica el page cache. Usar **WP Super Cache** o **W3 Total Cache**.

### Configuración recomendada para WP Super Cache

1. Instalar y activar WP Super Cache.
2. Settings → Simple caching: Enabled.
3. Advanced → Never cache pages: agregar estas URLs:
   ```
   /carrito/
   /cart/
   /checkout/
   /mi-cuenta/
   /wp-admin/
   ```
4. Advanced → Clear all cache files on new post: habilitado.
5. TTL de caché: 2 horas.

### URLs que NUNCA deben cachearse

```
/carrito/
/cart/
/checkout/
/mi-cuenta/
/mi-cuenta/*
/wp-admin/
/?wc-ajax=*
/?add-to-cart=*
```

---

## 3. Object cache (Redis / Memcached)

Si el hosting ofrece Redis:

1. Instalar el plugin **Redis Object Cache** o **Predis**.
2. En `wp-config.php`: agregar `define('WP_REDIS_HOST', '127.0.0.1');`
3. Activar drop-in: `wp redis enable`
4. Verificar: `wp redis status`

Si el hosting no ofrece Redis, el object cache de WordPress usa la base de datos (comportamiento por defecto — aceptable para tráfico moderado).

---

## 4. Transient cache custom (BSC)

El theme BSC usa transients para cachear queries costosas:

| Transient key | TTL | Invalidado por |
|--------------|-----|----------------|
| `bsc_slider_*` | 1 hora | `save_post_product` |
| `bsc_menu_categories` | 12 horas | `edited_term`, `created_term` |
| `bsc_search_*` | 15 minutos | Expiración automática |
| `bsc_report_*` | 1 hora | Expiración automática |

Para limpiar todos los transients BSC:
```bash
wp transient delete --all
```
