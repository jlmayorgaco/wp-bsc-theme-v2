# BSC-046 — Runbook: Restore desde Backup

**Versión:** 1.0  
**Última revisión:** 2026-04-05  
**RTO estimado:** 30–60 minutos  
**RPO estimado:** 24 horas (backup diario de DB)

---

## Pre-requisitos

- [ ] Acceso SSH al servidor de producción
- [ ] Credenciales de base de datos (en `backup-config.sh` del servidor)
- [ ] Acceso al directorio de backups (`/backups/bsc/`) o al servidor remoto
- [ ] WP-CLI instalado (`wp --info` debe responder)

---

## Escenario 1: Restaurar solo la base de datos

Usar cuando el código está bien pero los datos están corruptos o se perdieron.

```bash
# 1. Activar modo mantenimiento (evita que usuarios vean el sitio roto)
wp maintenance-mode activate --path=/var/www/html

# 2. Listar backups disponibles
ls -lh /backups/bsc/db/

# 3. Restaurar el backup más reciente (reemplazar FECHA con el nombre real)
gunzip -c /backups/bsc/db/db-FECHA.sql.gz | mysql -u bsc_user -p bsc_production

# 4. Verificar que las tablas están presentes
mysql -u bsc_user -p bsc_production -e "SHOW TABLES;" | head -20

# 5. Limpiar caché de WordPress
wp cache flush --path=/var/www/html
wp transient delete --all --path=/var/www/html

# 6. Desactivar modo mantenimiento
wp maintenance-mode deactivate --path=/var/www/html
```

---

## Escenario 2: Restaurar archivos (uploads + theme)

Usar cuando se perdieron imágenes, assets o el código del theme.

```bash
# 1. Activar modo mantenimiento
wp maintenance-mode activate --path=/var/www/html

# 2. Ir al directorio raíz de WordPress
cd /var/www/html

# 3. Listar backups disponibles
ls -lh /backups/bsc/full/

# 4. Extraer el backup (reemplazar FECHA)
tar -xzf /backups/bsc/full/full-FECHA.tar.gz

# 5. Ajustar permisos
chown -R www-data:www-data wp-content/
chmod -R 755 wp-content/
find wp-content/ -type f -name "*.php" -exec chmod 644 {} \;

# 6. Desactivar modo mantenimiento
wp maintenance-mode deactivate --path=/var/www/html
```

---

## Escenario 3: Restauración completa (DB + archivos)

Usar ante pérdida total o migración de servidor.

```bash
# 1. Instalar WordPress limpio en el nuevo servidor
# 2. Copiar wp-config.php (actualizar DB credentials si es nuevo servidor)

# 3. Restaurar DB
gunzip -c /backups/bsc/full/full-FECHA-db.sql.gz | mysql -u NEW_USER -p NEW_DB

# 4. Restaurar archivos
cd /var/www/html
tar -xzf /backups/bsc/full/full-FECHA.tar.gz

# 5. Actualizar URLs si el dominio cambió
wp search-replace 'https://OLD_DOMAIN.co' 'https://bubbleskincare.co' --path=/var/www/html

# 6. Ajustar permisos
chown -R www-data:www-data wp-content/
chmod -R 755 wp-content/

# 7. Flush rewrite rules
wp rewrite flush --path=/var/www/html
```

---

## Restaurar en staging primero (recomendado)

**SIEMPRE probar la restauración en staging antes de hacerla en producción.**

```bash
# Clonar backup en staging
rsync -az /backups/bsc/full/full-FECHA.tar.gz staging-server:/tmp/

# En el servidor de staging: restaurar y validar
ssh staging-server
cd /var/www/staging
tar -xzf /tmp/full-FECHA.tar.gz
# ... seguir los pasos del escenario 3 con las credenciales de staging
```

---

## Checklist de validación post-restore

Ejecutar este checklist después de cualquier restauración antes de declarar el sitio operativo:

- [ ] Home carga sin errores (HTTP 200, sin PHP warnings)
- [ ] El menú de navegación funciona (desktop y mobile)
- [ ] Se puede agregar un producto al carrito
- [ ] El carrito muestra los productos correctos
- [ ] El checkout procesa correctamente (probar con pedido de prueba)
- [ ] La página de Thank You muestra el resumen del pedido
- [ ] Las imágenes de productos cargan (no hay 404 en imágenes)
- [ ] El admin de WordPress (`/wp-admin/`) es accesible
- [ ] WooCommerce está activo y no muestra errores
- [ ] Los pedidos históricos aparecen en WooCommerce → Pedidos
- [ ] El plugin de Bubble Points está activo
- [ ] El SSL está activo (candado verde en el navegador)
- [ ] El admin BSC → Pedidos carga correctamente
- [ ] Enviar un email de prueba desde WooCommerce → Configuración → Emails

---

## RTO / RPO

| Métrica | Valor | Descripción |
|---------|-------|-------------|
| **RPO** | 24 horas | El backup de DB se ejecuta diariamente a las 2 AM |
| **RTO** | 30–60 min | Tiempo estimado para restauración completa en producción |
| **RTO staging** | 15–30 min | Restauración en staging (sin ajuste de DNS) |

---

## Contactos de emergencia

| Rol | Responsabilidad | Contacto |
|-----|----------------|---------|
| Dev principal | Acceso al servidor, restauración técnica | Ing. Jorge Luis Mayorga |
| Admin negocio | Decisión de activar DR, comunicación clientes | Equipo BSC |
| Hosting | Soporte de servidor si hay fallo de hardware | Soporte del proveedor de hosting |

---

## Notas

- Los backups completos (`full-*.tar.gz`) incluyen el theme y los uploads pero NO `wp-config.php` (contiene credenciales — no se respalda intencionalmente).
- Si el backup remoto no está disponible, verificar el backup local en `/backups/bsc/`.
- Después de una restauración de DB, WooCommerce puede pedir que se ejecuten actualizaciones de BD: ir a WooCommerce → Status → Tools → Update database.
