# BSC-052 — Runbook de Deploy

## Pre-requisitos

- [ ] Acceso SSH al servidor de producción
- [ ] WP-CLI instalado (`wp --info`)
- [ ] Repositorio git clonado en el servidor (`/var/www/html/wp-content/themes/wp-bsc-theme-v2/`)
- [ ] Backup reciente del día verificado (ver `docs/runbook-restore.md`)

---

## Paso 1: Pre-deploy

```bash
# 1. Verificar que el backup del día existe
ls -lh /backups/bsc/db/ | tail -5

# 2. Activar modo mantenimiento
wp maintenance-mode activate --path=/var/www/html

# 3. Ejecutar el QA checklist en staging (QA checklist)
#    Si staging falla → NO continuar con el deploy en producción
```

---

## Paso 2: Deploy del código

```bash
cd /var/www/html/wp-content/themes/wp-bsc-theme-v2

# 4. Pull del branch main (o tag de release)
git fetch origin
git pull origin main

# 5. Verificar el último commit desplegado
git log --oneline -5
```

---

## Paso 3: Post-deploy

```bash
# 6. Limpiar caché de WordPress
wp cache flush --path=/var/www/html

# 7. Limpiar todos los transients BSC
wp transient delete --all --path=/var/www/html

# 8. Actualizar BSC_THEME_VERSION en functions.php si se hicieron cambios de assets
#    (editar manualmente: define('BSC_THEME_VERSION', 'X.X.X'))

# 9. Verificar que no hay errores PHP
tail -20 /var/log/php_errors.log

# 10. Desactivar modo mantenimiento
wp maintenance-mode deactivate --path=/var/www/html
```

---

## Paso 4: Smoke tests post-deploy

Ejecutar manualmente en producción:

- [ ] Home carga sin errores (verificar consola JS)
- [ ] Agregar producto al carrito → badge se actualiza
- [ ] Ir a checkout → formulario visible y funcional
- [ ] Admin BSC (`/wp-admin/admin.php?page=bsc-orders`) carga
- [ ] No hay PHP warnings en `/wp-admin/`

---

## Rollback

Si el deploy causó un problema crítico:

```bash
# Opción 1: Revertir el código (si el problema es en el theme)
cd /var/www/html/wp-content/themes/wp-bsc-theme-v2
git revert HEAD --no-edit
git push origin main  # solo si se usa git en producción

# Opción 2: Restaurar desde backup (si el problema afecta DB o es irrecuperable)
# Seguir docs/runbook-restore.md — Escenario 3
wp maintenance-mode activate --path=/var/www/html
gunzip -c /backups/bsc/db/db-FECHA.sql.gz | mysql -u bsc_user -p bsc_production
wp maintenance-mode deactivate --path=/var/www/html
```

---

## Deploy rápido (hotfix)

Para fixes urgentes en producción sin pasar por staging:

```bash
git checkout -b fix/BSC-XXX-descripcion main
# hacer el fix
git commit -m "fix(scope): [BSC-XXX] descripcion"
git push origin fix/BSC-XXX-descripcion

# Merge a main
git checkout main
git merge fix/BSC-XXX-descripcion
git push origin main

# Deploy inmediato
wp maintenance-mode activate --path=/var/www/html
git pull origin main
wp cache flush --path=/var/www/html
wp maintenance-mode deactivate --path=/var/www/html
```

---

## Actualizar BSC_THEME_VERSION

Al hacer un deploy que incluye cambios de CSS o JS:

1. Abrir `functions.php`
2. Cambiar `define('BSC_THEME_VERSION', 'X.X.X')` con la versión nueva
3. Seguir el versionado semántico: `MAJOR.MINOR.PATCH`
   - PATCH: bug fixes, ajustes menores
   - MINOR: features nuevas sin romper nada
   - MAJOR: cambios que podrían romper compatibilidad
