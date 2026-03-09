# Bubble Skin Care — ToDos detallados para Notion

## P0 — Bloqueadores para salir a producción

- [ ] Hacer backup completo de archivos + base de datos antes de tocar producción.
- [ ] Confirmar que todos los cambios se implementarán dentro de la theme custom y no en plugins.
- [ ] Levantar staging actualizado y validar que refleja producción.
- [ ] Auditar la lentitud general del sitio:
  - [ ] medir home, categorías, marca y menú mobile
  - [ ] revisar plugins activos y detectar cuáles sobran
  - [ ] revisar scripts y estilos cargados globalmente
  - [ ] revisar imágenes pesadas en home/banners
  - [ ] revisar si WooCommerce cart fragments / AJAX están afectando performance
  - [ ] documentar causas raíz y quick wins
- [ ] Revisar si existe cache, minificación, lazy load y compresión de imágenes, y configurar correctamente sin romper el theme.
- [ ] Revisar errores JS en consola y errores PHP del theme en logs.

## P0 — Header / home hero / slider principal

- [ ] Corregir solape del carrito y botones alineados a la derecha en iPad y desktop pequeño.
- [ ] Corregir solape entre texto del slide y botones/íconos.
- [ ] Quitar la frase “K Beauty para cada tipo de piel” del hero.
- [ ] Cambiar color de textos del slider al negro BSC.
- [ ] Corregir el símbolo de apertura “¡” que aparece mal en “¡Mantén tus labios saludables!”.
- [ ] Reemplazar imágenes placeholder del banner por imágenes reales.
- [ ] En mobile, mover controles/botones del slider más hacia las esquinas.
- [ ] Revisar el slider en Safari iPhone y Safari iPad, no solo Chrome.

## P0 — Carrito / cards producto / interacción

- [ ] Corregir badge del carrito para que si la cantidad de productos baja a 0, el contador también se actualice a 0.
- [ ] Revisar sincronización entre:
  - [ ] mini cart
  - [ ] badge header
  - [ ] cantidades en cards
  - [ ] fragments AJAX
- [ ] Corregir “agregar al carrito” en iPad; actualmente el click no hace nada.
- [ ] Verificar si el problema en iPad es:
  - [ ] z-index
  - [ ] overlay invisible
  - [ ] evento touch/pointer
  - [ ] botón deshabilitado
  - [ ] conflicto JS
- [ ] Cambiar texto de botón a “¡Lo quiero!”.
- [ ] Ajustar hover de botón para usar línea negra BSC.
- [ ] Dar más aire horizontal a las cajas/cards del carrusel o grid.
- [ ] Revisar responsive de cards producto en mobile para evitar recortes y huecos raros.

## P0 — Marcas y páginas de marca

- [ ] Corregir lentitud al hacer click en bolitas de marcas.
- [ ] Revisar cómo está construido el bloque de marcas:
  - [ ] query
  - [ ] imágenes
  - [ ] navegación
  - [ ] sliders/carrusel
- [ ] Al entrar a una marca, mostrar los productos reales de esa marca.
- [ ] Revisar template de la URL `/product-category/group-skin-care/sk-marcas/`.
- [ ] Verificar taxonomías, parent/child categories y filtros usados para marcas.
- [ ] Asegurar que si una marca no tiene subcategorías visibles, al menos renderice su listado de productos.
- [ ] Validar que cada bolita de marca apunte a la URL correcta y no a `#`.
- [ ] Rediseñar la disposición de bolitas de marca a una composición más cerrada tipo 3x3 visual.

## P0 — Menú mobile

- [ ] Poner logo BSC centrado en la parte media del header mobile.
- [ ] Hacer que el logo BSC siempre lleve al home.
- [ ] Poner las 3 rayitas del menú en negro BSC.
- [ ] Cambiar icono de bolsa por el icono correcto usado en desktop.
- [ ] Agregar icono de perfil/usuario en mobile.
- [ ] Quitar del menú mobile:
  - [ ] Registrarme
  - [ ] Políticas de privacidad
  - [ ] Olvidé mi contraseña
- [ ] Dejar visibles en menú mobile:
  - [ ] Iniciar sesión
  - [ ] Mis pedidos
  - [ ] Bubble Points
- [ ] En menú mobile de Skin Care, replicar el menú desktop.
- [ ] Quitar ítems duplicados como “mascarilla” repetida.
- [ ] Quitar estructura vieja de “piel seca, mixta, etc.” si ya no aplica ahí.
- [ ] Rediseñar visualmente el menú mobile para que no se vea plano.
- [ ] Quitar lo de “wappy” del menú.
- [ ] Probar apertura/cierre del menú en iPhone, Android e iPad.

## P1 — Sección favoritos / tipos de piel

- [ ] Reducir espacio vertical entre el carrusel de productos y el inicio de “Favoritos en BSC” en celular.
- [ ] Acercar visualmente las dos columnas de iconos para que se vean integradas.
- [ ] Implementar scroll automático:
  - [ ] click en Piel Seca -> baja al bloque correcto
  - [ ] click en Piel Mixta -> baja al bloque correcto
  - [ ] click en Piel Normal -> baja al bloque correcto
  - [ ] click en Piel Grasa -> baja al bloque correcto
  - [ ] click en Hair Care -> baja al bloque correcto
  - [ ] click en Maquillaje -> baja al bloque correcto
- [ ] Revisar que el scroll no tape el título por header sticky.
- [ ] Agregar offset de scroll si hay header fijo.

## P1 — Bloques institucionales y navegación secundaria

- [ ] Quitar el espacio entre la foto textura crema y el bloque azul.
- [ ] En celular, reducir tamaño del título “Bubble Lover / Conoce más de nosotros”.
- [ ] Acercar los 3 ítems institucionales; hoy están demasiado espaciados.
- [ ] Hacer clickeables los links:
  - [ ] Envíos y devoluciones -> página correcta
  - [ ] Preguntas frecuentes -> página correcta
  - [ ] Contacto -> página contacto
- [ ] Cambiar el título “Tienda” por “K-Beauty”.
- [ ] Hacer que “Entrega inmediata” dirija a la página que ya diseñaron.
- [ ] Hacer que “Encargos” abra WhatsApp.
- [ ] Configurar mensaje prellenado de WhatsApp para encargos, por ejemplo:
  - [ ] “Hola, quiero hacer un encargo coreano.”
- [ ] Verificar que el link de WhatsApp funcione bien en móvil y desktop.

## P1 — Mi cuenta / programas

- [ ] Quitar la opción “Datos” del bloque de Mi cuenta o del footer, según donde aplique.
- [ ] Revisar si “Bubble Points” lleva a la página correcta y si requiere login.
- [ ] Revisar si “Pedidos” lleva a la página correcta y si requiere login.
- [ ] Hacer funcional “Bubble Creators”.
- [ ] Conectar “Bubble Creators” con el mini formulario que ya fue diseñado.
- [ ] Definir el comportamiento para usuarios no logueados en estos links.

## P1 — Newsletter / comunidad BSC

- [ ] Revisar si el formulario de correo realmente guarda o envía datos.
- [ ] Si el usuario deja su correo, mostrar confirmación visible:
  - [ ] popup
  - [ ] toast
  - [ ] mensaje inline de éxito
- [ ] Mostrar error si el correo es inválido o si el envío falla.
- [ ] Evitar que el botón no haga nada en silencio.
- [ ] Verificar integración real:
  - [ ] Mailchimp
  - [ ] plugin actual
  - [ ] custom handler
  - [ ] envío por AJAX
- [ ] Revisar protección básica anti spam sin dañar UX.

## P1 — Footer / bloque azul / Bubble Blog / WhatsApp

- [ ] Quitar el espacio entre el bloque azul y el footer negro.
- [ ] Cambiar texto de Bubble Blog a “Próximamente”.
- [ ] Corregir responsive del bloque Bubble Blog en celular.
- [ ] Restaurar botón flotante de WhatsApp en celular.
- [ ] Verificar posición, z-index y separación del botón flotante respecto al carrito u otros botones.
- [ ] Cambiar copyright a “2020 - 2026” en desktop, iPad y celular.
- [ ] Revisar que footer y bloque azul no tengan márgenes/paddings heredados indeseados.

## P1 — Contenido y consistencia visual

- [ ] Unificar color negro de marca en textos donde el cliente lo pidió.
- [ ] Revisar ortografía y tildes en textos visibles.
- [ ] Revisar símbolos especiales “¡”.
- [ ] Reemplazar imágenes rotas o placeholders.
- [ ] Revisar que no haya textos fuera del diseño original.
- [ ] Validar spacing general entre secciones en home.

## P1 — Performance técnica puntual

- [ ] Revisar qué plugins pueden eliminarse sin romper el sitio.
- [ ] Detectar assets pesados cargados en todas las páginas aunque solo se usan en home.
- [ ] Separar CSS/JS por template si hace falta.
- [ ] Revisar si sliders/carruseles están inicializados más de una vez.
- [ ] Revisar lazy load de imágenes above-the-fold para no empeorar LCP.
- [ ] Revisar compresión y tamaños reales de imágenes de banner.
- [ ] Revisar fuentes, icon packs y librerías de terceros.
- [ ] Revisar consultas de productos repetidas en la home.
- [ ] Evaluar cache de fragmentos donde aplique.
- [ ] Revisar si el botón flotante de WhatsApp o widgets externos están ralentizando.

## P2 — Cosas que se pueden pasar y hay que revisar igual

- [ ] Verificar que no existan links rotos en header, footer, marcas y bloques intermedios.
- [ ] Verificar que el home no tenga CLS por imágenes sin dimensiones.
- [ ] Verificar que el header sticky no tape contenido al hacer scroll a anchors.
- [ ] Verificar que no haya elementos invisibles montados encima de botones.
- [ ] Verificar estados hover/focus/active en botones y links.
- [ ] Verificar accesibilidad básica de tap targets en mobile.
- [ ] Verificar que el menú mobile se pueda cerrar bien.
- [ ] Verificar que los iconos del header mobile conserven alineación.
- [ ] Verificar que el mini cart no se abra fuera de pantalla en mobile.
- [ ] Verificar que el footer no meta scroll horizontal.
- [ ] Verificar que no haya imágenes que carguen desde dominios lentos o externos.
- [ ] Verificar que las páginas nuevas o existentes tengan títulos correctos y no queden vacías.
- [ ] Verificar que la navegación de marcas y categorías no genere páginas 404.
- [ ] Verificar que el theme no dependa de contenido hardcodeado difícil de editar después.

## QA final antes de producción

- [ ] Probar home completa en desktop ancho.
- [ ] Probar home en laptop pequeña.
- [ ] Probar en iPad.
- [ ] Probar en iPhone Safari.
- [ ] Probar en Android Chrome.
- [ ] Probar:
  - [ ] abrir menú
  - [ ] cerrar menú
  - [ ] ir al home por logo
  - [ ] abrir categoría
  - [ ] abrir marca
  - [ ] agregar producto
  - [ ] dejar cantidad en 0
  - [ ] revisar badge carrito
  - [ ] abrir contacto
  - [ ] abrir FAQ
  - [ ] abrir envíos y devoluciones
  - [ ] abrir encargos por WhatsApp
  - [ ] enviar correo newsletter
- [ ] Validar que no haya errores JS en consola después de todos los cambios.
- [ ] Validar que no haya warnings PHP nuevos.
- [ ] Hacer checklist de aceptación con screenshots por desktop / tablet / mobile.