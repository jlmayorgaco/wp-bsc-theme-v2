# BSC-050 — Optimización de Imágenes y Media Pipeline

## 1. Soporte WebP

WordPress 5.8+ genera versiones WebP automáticamente cuando el servidor tiene soporte GD con WebP.

El theme activa la conversión en `inc/setup/theme-setup.php`:
- Nuevas imágenes subidas se guardan como `.webp` si `imagewebp()` está disponible.
- Las imágenes existentes NO se convierten automáticamente — ver sección 2.

**Verificar soporte:**
```bash
# En el servidor
php -r "echo function_exists('imagewebp') ? 'WebP OK' : 'WebP NO soportado';"
```

---

## 2. Regenerar imágenes existentes con WebP

Después de activar el soporte WebP, correr en el servidor:
```bash
# Via WP-CLI
wp media regenerate --yes
```

Este proceso puede tardar varios minutos dependiendo del número de imágenes. Ejecutar en horario de bajo tráfico.

---

## 3. Tamaños de imagen registrados

| Nombre | Dimensiones | Crop | Uso |
|--------|-------------|------|-----|
| `bsc-card` | 400×400 | Sí | Tarjetas de producto |
| `bsc-hero` | 1440×600 | Sí | Hero slider |
| `bsc-thumb` | 120×120 | Sí | Thumbnails |
| `woocommerce_thumbnail` | variable | — | Galería WC |
| `woocommerce_single` | variable | — | Producto individual |

---

## 4. Guía para subir imágenes optimizadas

### Imágenes de productos
- **Formato:** JPEG o WebP (WP convertirá a WebP automáticamente si está habilitado)
- **Dimensión mínima:** 800×800 px
- **Peso máximo recomendado al subir:** 300 KB (WP redimensionará para las tarjetas)
- **Fondo:** blanco o transparente

### Imágenes del hero
- **Formato:** JPEG o WebP
- **Dimensión:** mínimo 1440×600 px
- **Peso máximo recomendado:** 400 KB
- El slide 0 tiene `fetchpriority="high"` — debe estar bien optimizado para LCP

### Imágenes decorativas (menú, banners)
- **Formato:** WebP preferido, PNG si tiene transparencia
- **Peso:** < 150 KB

---

## 5. Herramientas de compresión

| Herramienta | Tipo | Descripción |
|-------------|------|-------------|
| [Squoosh](https://squoosh.app) | Online | Compresión manual con preview |
| [TinyPNG](https://tinypng.com) | Online | Compresión batch PNG/JPEG |
| `cwebp` | CLI | Conversión manual a WebP |
| WP plugin: Imagify / ShortPixel | Plugin | Compresión automática en uploads |

---

## 6. Lazy loading

- Las tarjetas de producto (`card.php`) usan `wp_get_attachment_image()` que genera `loading="lazy"` automáticamente.
- El slide 0 del hero usa `loading="eager"` y `fetchpriority="high"` (LCP crítico).
- Todos los demás slides del hero usan `loading="lazy"`.

---

## 7. Placeholder de producto

Si un producto no tiene imagen asignada, se usa:
`/wp-content/themes/wp-bsc-theme-v2/images/bsc__placeholder_product.jpg`

Este placeholder debe pesar < 5 KB. Verificar con:
```bash
du -sh wp-content/themes/wp-bsc-theme-v2/images/bsc__placeholder_product.jpg
```
