# BSC-158 — Visible Mojibake Cleanup

## Objective
Corregir texto mojibake visible en frontend y pantallas BSC admin para que la UI vuelva a renderizar tildes, signos y labels correctamente.

## Context
Hay strings rotos en el código fuente del theme, por ejemplo `EnvÃ­os gratis`, `ConfiguraciÃ³n`, `CupÃ³n` y `PÃ¡gina`. El problema está en templates PHP y mensajes localizados, no en dependencias ni binarios.

## Files To Inspect
- front-page.php
- admin/bsc-admin-menu.php
- admin/bsc-access-page.php
- admin/bsc-coupons-page.php
- admin/bsc-orders-page.php
- admin/bsc-products-page.php
- admin/bsc-reports-page.php

## Implementation Plan
- corregir solo strings visibles de frontend/admin
- dejar fuera comentarios, arte ASCII y dependencias externas
- normalizar mensajes, labels, títulos y copy roto
- validar sintaxis PHP y comprobar render en Home y páginas BSC admin

## Acceptance Criteria
- Home ya no muestra texto mojibake
- Settings, Access y Coupons renderizan acentos/signos correctos
- Orders, Products y Reports no exponen labels o mensajes rotos
- no se altera lógica funcional, solo texto visible

## Manual QA
- abrir Home y validar títulos, badges y CTA del newsletter
- abrir `admin.php?page=bsc-settings` y revisar labels/descripciones
- abrir `admin.php?page=bsc-access` y revisar tabla/notice
- abrir `admin.php?page=bsc-coupons` y revisar notices, form y tabla
- abrir `admin.php?page=bsc-orders`, `bsc-products` y `bsc-reports` y revisar labels corregidos

## Rollback Notes
- revertir los templates/admin files tocados en este ticket
