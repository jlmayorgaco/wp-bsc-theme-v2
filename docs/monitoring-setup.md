# BSC-047 — Guía de Monitoreo en Producción

## 1. Monitoreo externo (uptime)

Registrar el sitio en **UptimeRobot** (plan gratuito, checks cada 5 minutos):

1. Ir a [uptimerobot.com](https://uptimerobot.com) → Create Free Account
2. Add New Monitor:
   - **Monitor Type:** HTTP(S)
   - **Friendly Name:** bubbleskincare.co
   - **URL:** `https://bubbleskincare.co`
   - **Monitoring Interval:** 5 minutes
3. Alert Contacts: agregar email `admin@bubbleskincare.co`
4. (Opcional) Agregar alerta por SMS o Slack

**URL del dashboard público:** guardar en esta sección después de configurar.

---

## 2. Script de health check en servidor

El script `scripts/monitor-health.sh` verifica en el servidor:

| Check | Descripción | Alerta si... |
|-------|-------------|-------------|
| HTTP status | `curl` al sitio | Respuesta ≠ 200 |
| Disco | `df /` | Uso > 80% |
| SSL | `openssl s_client` | Expira en < 30 días |
| Backup | `find /backups/bsc/db/` | No hay backup en < 48h |

### Instalación del cron

```bash
# Editar crontab del usuario que ejecuta el servidor
crontab -e

# Agregar (ejecutar cada 15 minutos)
*/15 * * * * /var/www/html/wp-content/themes/wp-bsc-theme-v2/scripts/monitor-health.sh
```

### Ver logs de monitoreo

```bash
tail -f /var/log/bsc-monitor.log
grep "ALERT" /var/log/bsc-monitor.log
```

---

## 3. Cronjob de backups

```bash
crontab -e

# DB backup diario a las 2 AM
0 2 * * * /var/www/html/wp-content/themes/wp-bsc-theme-v2/scripts/backup-db.sh

# Full backup semanal los domingos a las 3 AM
0 3 * * 0 /var/www/html/wp-content/themes/wp-bsc-theme-v2/scripts/backup-full.sh

# Health check cada 15 minutos
*/15 * * * * /var/www/html/wp-content/themes/wp-bsc-theme-v2/scripts/monitor-health.sh
```

---

## 4. Qué hacer ante cada tipo de alerta

### El sitio devuelve HTTP ≠ 200

1. SSH al servidor: `ssh user@bubbleskincare.co`
2. Verificar Apache/Nginx: `systemctl status apache2` o `systemctl status nginx`
3. Ver errores recientes: `tail -50 /var/log/apache2/error.log`
4. Si el proceso está caído: `systemctl restart apache2`
5. Si el problema persiste, activar modo mantenimiento y diagnosticar

### Disco > 80%

```bash
# Ver qué ocupa más espacio
du -sh /var/www/html/wp-content/uploads/*
du -sh /backups/bsc/*

# Purgar backups manualmente si es urgente
find /backups/bsc/full -name "*.tar.gz" -mtime +15 -delete
```

### SSL expira en < 30 días

```bash
# Si usa Let's Encrypt
certbot renew --dry-run
certbot renew
```

### Backup no encontrado

```bash
# Verificar que el cron se ejecutó
grep "backup" /var/log/bsc-backup.log | tail -5

# Ejecutar manualmente
bash /var/www/html/wp-content/themes/wp-bsc-theme-v2/scripts/backup-db.sh
```

---

## 5. Logs del sistema

| Log | Ruta | Qué contiene |
|-----|------|-------------|
| Backups | `/var/log/bsc-backup.log` | Ejecuciones de backup-db.sh y backup-full.sh |
| Monitoreo | `/var/log/bsc-monitor.log` | Checks de health + alertas enviadas |
| Apache | `/var/log/apache2/error.log` | Errores del servidor web |
| PHP | `/var/log/php_errors.log` | Errores PHP de WordPress |
