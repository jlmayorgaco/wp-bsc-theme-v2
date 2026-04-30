# BSC-153 — Admin Copy And Labels Cleanup

## Objective
Corregir copy roto y acciones poco claras en pantallas admin operativas.

## Context
Showroom y productos tenían texto mojibake y acciones icon-only o poco explícitas.

## Files To Inspect
- admin/bsc-showroom-page.php
- js/admin/bsc-showroom.js
- admin/bsc-products-page.php

## Implementation Plan
- corregir strings visibles de showroom
- normalizar labels de métodos de pago, placeholders y mensajes
- reemplazar acciones icon-only por labels explícitos en productos

## Acceptance Criteria
- showroom no muestra caracteres rotos
- los métodos de pago son legibles
- productos muestra Ver tienda / Historial en texto claro

## Manual QA
- abrir BSC > Showcase y revisar labels y notice
- abrir BSC > Productos y revisar acciones por fila

## Rollback Notes
- revertir admin/bsc-showroom-page.php
- revertir js/admin/bsc-showroom.js
- revertir admin/bsc-products-page.php
