# Verificar el despliegue desde PowerShell

Sube tus cambios a `MVP2` y abre un pull request de `MVP2` hacia `main`. GitHub exige que pase **Sintaxis PHP 8.3**. Al fusionar el PR, el webhook actualiza el tema en producción. El workflow **Verificar deploy en produccion** comprueba el commit publicado y que respondan la portada, la tienda, el carrito y el checkout.

Después de fusionar el PR, ejecuta este bloque con Git y GitHub CLI disponibles:

```powershell
$repo = 'jlmayorgaco/wp-bsc-theme-v2'
$remote = git ls-remote "https://github.com/$repo.git" refs/heads/main
if ($LASTEXITCODE -ne 0 -or -not $remote) { throw 'No se pudo leer main.' }
$expected = ($remote -split '\s+')[0]

$run = gh run list --repo $repo --workflow verify-deploy.yml --branch main --commit $expected --event push --limit 1 --json databaseId --jq '.[0].databaseId // empty'
if ($LASTEXITCODE -ne 0 -or -not $run) { throw 'La ejecución aún no aparece en GitHub Actions. Intenta de nuevo en unos segundos.' }
gh run watch $run --repo $repo --exit-status
if ($LASTEXITCODE -ne 0) { throw 'La verificación de producción falló. Revisa esa ejecución en GitHub Actions.' }

$nonce = [guid]::NewGuid().ToString('N')
$response = Invoke-WebRequest -UseBasicParsing -ErrorAction Stop -Uri "https://bubblesskincare.com/deploy-version.txt?check=$nonce"
$published = $response.Content.Trim()
if ($published -cne $expected) { throw "GitHub esperaba $expected y producción reportó $published." }
Write-Output "Despliegue verificado: $published"
```

Si falla la entrega por una interrupción temporal, abre **Settings → Webhooks → Recent Deliveries** y usa **Redeliver** en la entrega del push a `main`. Después vuelve a ejecutar la comprobación en **Actions** y repite el bloque.
