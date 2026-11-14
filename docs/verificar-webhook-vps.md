# Verificar el webhook de despliegue en el VPS

Después de fusionar un PR a `main`, comprueba qué commit tiene el tema en producción. Ejecuta este bloque como `root` en la terminal del VPS. Los comandos de Git usan `www-data`, el usuario con el que trabaja el webhook.

```bash
(
  set -euo pipefail
  cd /var/www/bubblesskincare.com/htdocs/wp-content/themes/wp-bsc-theme-v2

  g() { runuser -u www-data -- git "$@"; }

  test "$(g symbolic-ref --short HEAD)" = main
  vps=$(g rev-parse HEAD)
  github=$(g ls-remote --exit-code origin refs/heads/main | cut -f1)
  printf 'VPS:         %s\nGitHub main: %s\n\n' "$vps" "$github"
  g --no-pager reflog show -3 --date=iso --format='%h %gd %gs' main

  if [ "$vps" != "$github" ]; then
    echo 'PENDIENTE: el VPS todavía tiene otro commit.'
    exit 1
  fi
  echo 'OK: el VPS tiene el mismo commit que main.'
)
```

El commit de la fusión debe aparecer en el historial con `pull --ff-only: Fast-forward`. Para atribuir ese `pull` al webhook, verifica que tenga un `200` en **Settings → Webhooks → Recent Deliveries** para ese mismo commit. En **Actions**, abre **Verificar deploy en produccion** y comprueba que el commit publicado y las páginas de la tienda hayan pasado sus verificaciones.
