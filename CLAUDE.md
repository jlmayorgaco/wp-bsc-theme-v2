# Bubble Skin Care — Claude.md

## 1) Contexto del proyecto

Este proyecto es la tienda **Bubble Skin Care (BSC)** montada sobre **WordPress + WooCommerce** con una **theme custom** como capa principal de implementación.  
La regla de oro para cualquier cambio es:

- **Todo debe vivir en la theme custom**.
- **Evitar meter lógica en plugins si puede resolverse en la theme**.
- **No romper checkout, carrito, cuenta, navegación ni SEO**.
- **Priorizar performance, estabilidad y salida a producción**.
- **Toda personalización debe ser reusable, mantenible y responsive**.

## 2) Lo que ya parece existir en el proyecto

Con base en el trabajo previo del proyecto, el sitio probablemente ya tiene:

- Theme custom propia (tipo `wp-bsc-theme-v2` / equivalente).
- WooCommerce activo para catálogo, carrito, cuenta y categorías.
- Templates PHP custom para:
  - home
  - navegación de categorías / subcategorías
  - secciones tipo skin care / marcas
  - cuenta de usuario
- CSS/SCSS custom de marca.
- JS custom para sliders, menú mobile, filtros y elementos interactivos.
- Algún botón flotante de WhatsApp.
- Secciones visuales personalizadas en home:
  - hero / slider principal
  - carrusel de productos
  - favoritos por tipo de piel
  - bloque de marcas
  - bloque institucional / beneficios
  - newsletter
  - footer
  - bloque Bubble Blog
- Menú desktop y menú mobile diferenciados.
- Posibles personalizaciones previas en:
  - “Mi cuenta”
  - Bubble Points
  - Bubble Creators
  - links a páginas internas
  - campos de perfil / formularios

## 3) Restricciones técnicas obligatorias

## 3.1 Arquitectura
- No crear dependencia innecesaria de page builders pesados.
- No parchear con plugins si el cambio cabe en template + CSS + JS de theme.
- Si toca crear una página nueva, crearla usando:
  - template PHP en theme, o
  - page slug controlado por WP, pero con render y estilos desde theme.

## 3.2 Performance
- La web está reportada como **muy lenta** y esto es prioridad de producción.
- Antes de meter más features, revisar:
  - plugins innecesarios
  - imágenes demasiado pesadas
  - sliders pesados
  - scripts duplicados
  - CSS no usado
  - llamadas AJAX redundantes
  - queries lentas de WooCommerce
  - carga de fuentes / iconos / librerías externas
  - widgets de terceros
  - scripts en mobile que bloquean render

## 3.3 Calidad de implementación
- Nada de CSS suelto “quick fix” sin revisar breakpoints.
- Nada de JS inline sin encapsular.
- Nada de hardcodes frágiles si el contenido puede venir de ACF/options/theme settings.
- Cuidar:
  - desktop
  - iPad / tablet
  - mobile
  - Safari iPhone / iPad
  - Chrome desktop
- Revisar eventos táctiles, no solo click.
- Cualquier cambio UI debe validar accesibilidad básica:
  - contraste
  - foco
  - tap targets
  - textos visibles
  - hover/focus consistente

## 4) Objetivo inmediato

Salir a producción con una versión:
- visualmente fiel al diseño esperado,
- rápida,
- sin errores funcionales en carrito, menú, navegación y formularios,
- y con todos los enlaces importantes funcionando.

## 5) Resumen ejecutivo de lo pedido por cliente

El PDF de ajustes reporta cinco grandes frentes:

1. **Performance general del sitio**: navegación muy lenta.
2. **Home / responsive**: múltiples problemas visuales en desktop pequeño, iPad y celular.
3. **Catálogo, sliders y marcas**: interacción lenta o incorrecta, scroll y navegación incompletos.
4. **Footer / enlaces / formularios**: enlaces rotos o incompletos, newsletter sin feedback, textos y spacing mal resueltos.
5. **Menú mobile**: rediseño visual, limpieza de opciones, consistencia con desktop e iconografía correcta.

## 6) Cambios del cliente, traducidos a implementación

## 6.1 Performance
- Auditar por qué la navegación está lenta.
- Revisar si hay plugins sobrantes o pesados.
- Medir tiempos reales antes de tocar UI.
- Optimizar assets y JS/CSS cargados en home y categorías.

## 6.2 Hero / slider principal
- Corregir superposición entre texto, carrito y botones.
- Quitar frase “K Beauty para cada tipo de piel”.
- Ajustar color del texto al negro de marca.
- Corregir signo “¡” invertido.
- Reemplazar banners con imágenes reales.
- Empujar controles del slider hacia esquinas.
- Revisar responsive en iPad y mobile.

## 6.3 Carrusel de productos / mini cards
- Corregir carrito cuando cantidad llega a 0 y badge no se actualiza.
- Dar más aire horizontal a cajas/cards.
- Cambiar CTA a “¡Lo quiero!”.
- Hover del botón con línea negra de marca.
- Corregir add to cart en iPad.
- Reducir espacio excesivo entre secciones en mobile.

## 6.4 Favoritos por tipo de piel
- Acercar columnas A/B de iconos.
- Implementar scroll automático al bloque de productos correspondiente.
- Revisar estructura de anchors / IDs.
- Verificar que el contenido objetivo exista y coincida con el ítem pulsado.

## 6.5 Marcas
- Rehacer disposición de “bolitas” para cubrir más la imagen, tipo 3x3.
- Mejorar tiempo de respuesta al hacer click.
- Al entrar a una marca, mostrar productos reales de esa marca.
- Revisar template de taxonomía / categoría de marca y queries WooCommerce.

## 6.6 Bloques intermedios / institucionales
- Quitar espacios verticales sobrantes entre bloques.
- Reducir tamaño y spacing en mobile del bloque “Bubble Lover / conoce más”.
- Hacer funcionales links de:
  - envíos y devoluciones
  - preguntas frecuentes
  - contacto
- Renombrar “Tienda” a “K-Beauty”.
- “Entrega inmediata” debe ir a la página correcta.
- “Encargos” debe abrir WhatsApp con mensaje prellenado.

## 6.7 Cuenta / programas
- Quitar enlace “Datos”.
- Hacer funcional Bubble Creators para abrir el mini formulario o flujo definido.
- Confirmar qué hace Bubble Points y Mis pedidos, y si requieren login.

## 6.8 Newsletter / formulario
- Si el usuario deja su correo, debe haber feedback claro:
  - popup,
  - toast,
  - mensaje inline,
  - o estado de éxito/error.
- Validar envío real, errores y deduplicación.

## 6.9 Footer / blog / WhatsApp
- Quitar espacio entre bloque azul y footer negro.
- En Bubble Blog mostrar “Próximamente”.
- Corregir responsive del bloque blog en celular.
- Restaurar botón flotante de WhatsApp en celular.
- Actualizar copyright a `2020 - 2026`.

## 6.10 Menú mobile
- Logo BSC centrado y clickeable al home.
- Icono hamburguesa en negro BSC.
- Reemplazar bolsa por icono correcto y agregar muñequito de perfil.
- Limpiar opciones sobrantes: quitar registro, políticas y olvido de contraseña del menú principal visible.
- Dejar: Iniciar sesión / Mis pedidos / Bubble Points.
- En skin care, replicar estructura del menú desktop.
- Eliminar duplicados (“mascarilla” repetida, piel seca/mixta si no corresponde).
- Rediseñar visual del menú para que no se vea plano.
- Quitar “wappy” del menú.

## 7) Riesgos técnicos que hay que revisar sí o sí antes de producción

## 7.1 Riesgos funcionales
- Badge del carrito desincronizado.
- Eventos touch no funcionando en iPad.
- Links de footer apuntando a `#` o URLs viejas.
- Plantilla de marcas sin query correcta.
- Formulario newsletter que visualmente existe pero no procesa.
- Menú mobile duplicando items desde WP menu + render custom.

## 7.2 Riesgos de performance
- Imágenes de banners sin compresión.
- Carruseles con librerías pesadas.
- Scripts duplicados entre desktop/mobile.
- WooCommerce fragments cargando en exceso.
- Queries por producto hechas varias veces por bloque.
- Badges / cart fragments rompiendo cache.
- Plugins de terceros inyectando CSS/JS global.
- Botón WhatsApp o widgets externos bloqueando la carga.

## 7.3 Riesgos de producción
- Tocar header/footer puede romper todo el sitio si no se hace en child-safe structure.
- Tocar menú mobile puede afectar cuenta/carrito.
- Tocar taxonomías de marcas puede cambiar URLs indexadas.
- Tocar newsletter puede requerir revisar integración real.
- Tocar carrito sin revisar AJAX puede dejar estados inconsistentes.

## 8) Estrategia recomendada de ejecución

## Fase 1 — Congelar y auditar
1. Backup completo.
2. Staging.
3. Medición base de performance.
4. Inventario de plugins activos y qué hace cada uno.
5. Revisar plantillas custom de theme.
6. Mapear:
   - home
   - header
   - footer
   - mobile menu
   - category templates
   - brand templates
   - mini cart / cart badge
   - newsletter form

## Fase 2 — Corregir bloqueadores de producción
1. Lentitud general.
2. Slider home responsive.
3. Badge carrito / add-to-cart iPad.
4. Páginas de marca mostrando productos.
5. Links críticos del footer.
6. Menú mobile funcional.

## Fase 3 — Pulido visual y UX
1. Espaciados.
2. Textos.
3. Hover/focus.
4. Responsive fino.
5. Rediseño visual del menú.

## Fase 4 — QA de salida
1. Desktop.
2. Tablet.
3. iPhone/Safari.
4. Android/Chrome.
5. Flujos:
   - navegar home
   - entrar a categoría
   - abrir marca
   - agregar/quitar carrito
   - login
   - ir a contacto
   - abrir WhatsApp
   - enviar newsletter

## 9) Criterios de aceptación mínimos

- Home carga de forma perceptiblemente más rápida.
- No hay solapes de texto/iconos/botones.
- El slider se ve bien en desktop, iPad y mobile.
- Carrito refleja cantidades reales.
- Add to cart funciona en iPad.
- Botones y CTAs tienen texto y hover correctos.
- Click en tipos de piel lleva al bloque correcto.
- Click en marcas carga rápido y muestra productos correctos.
- Footer completo con links reales.
- Newsletter da confirmación real.
- WhatsApp flotante visible en celular.
- Menú mobile limpio, usable, consistente con desktop.
- No se rompe WooCommerce.

## 10) Reglas para cualquier developer/LLM que toque este proyecto

- Antes de proponer código, identificar el template exacto.
- Antes de crear una página, validar si ya existe un slug o template.
- No duplicar lógica de WooCommerce si ya existe un hook.
- No meter librerías nuevas salvo que sea estrictamente necesario.
- Preferir:
  - hooks de WordPress/WooCommerce,
  - template parts,
  - CSS modular del theme,
  - JS aislado y delegación de eventos.
- Cada fix debe incluir:
  - causa raíz,
  - archivo a tocar,
  - cambio,
  - riesgo,
  - cómo probar.