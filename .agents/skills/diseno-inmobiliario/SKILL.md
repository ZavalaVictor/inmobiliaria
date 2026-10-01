---
name: diseno-inmobiliario
description: Diseñar e implementar interfaces públicas inmobiliarias para explorar propiedades, consultar sus detalles y solicitar información, respetando la marca, la accesibilidad y la conexión real con el backend.
---

# Diseño inmobiliario

Usa esta skill cuando el trabajo consista en crear o mejorar una página pública de inmuebles con catálogo, detalle de propiedad y solicitud de información. El resultado debe ser una experiencia navegable y conectada; no inventes integraciones ni confirmaciones de envío.

## Flujo de trabajo

1. Revisa primero las tecnologías, rutas, componentes, sistema de estilos, fuentes, assets, modelos, endpoints y validaciones existentes. Reutiliza el backend y los componentes disponibles cuando sea posible; adapta la implementación al stack del proyecto en vez de introducir una arquitectura paralela.
2. Identifica la identidad de marca y las referencias visuales proporcionadas. Conserva sus rasgos reconocibles y define una jerarquía coherente de tipografía legible, espaciado, colores, bordes, estados y fotografías grandes. Si faltan referencias, elige una dirección visual sobria y consistente con el sector inmobiliario, sin inventar una marca específica.
3. Diseña primero para celular y adapta después a tablet y escritorio. Verifica que el contenido principal, filtros, tarjetas, galería y formulario sigan siendo utilizables con una mano y con pantallas pequeñas.
4. Implementa el recorrido completo:
   - catálogo de inmuebles con filtros por ubicación, precio, tipo de inmueble y operación (venta o renta);
   - tarjetas con fotografía, precio, ubicación y características relevantes;
   - navegación desde cada tarjeta a una página individual del inmueble;
   - detalle con galería, descripción, características y botón «Solicitar información»;
   - formulario breve asociado al inmueble seleccionado, con nombre, medio de contacto y mensaje opcional;
   - enlace visible al aviso de privacidad.
5. Conecta el formulario al endpoint, acción o servicio real disponible. Si la integración todavía no existe, deja el flujo en un estado honesto (por ejemplo, pendiente o no disponible), explica qué falta y nunca muestres un éxito simulado.
6. Diseña explícitamente los estados de carga, sin resultados, error y envío exitoso. El estado exitoso solo puede aparecer después de una respuesta confirmada por la integración real; conserva mensajes comprensibles y evita perder los datos del usuario ante errores recuperables.

## Criterios de calidad

- Prioriza contenido y llamadas a la acción claras: el usuario debe entender qué propiedad está viendo y qué ocurrirá al solicitar información.
- Mantén contraste suficiente, foco visible, navegación completa con teclado, etiquetas asociadas a los campos, mensajes de validación accesibles y semántica adecuada para botones, enlaces, encabezados, imágenes y formularios.
- Proporciona textos alternativos útiles para las fotografías y controla su proporción para evitar saltos de diseño. Optimiza tamaño, formato y carga diferida de imágenes sin degradar innecesariamente su lectura.
- Evita animaciones o movimientos que distraigan; respeta `prefers-reduced-motion` cuando haya transiciones.
- No ocultes filtros o acciones esenciales detrás de interacciones difíciles de descubrir. En móvil, usa patrones compactos pero accesibles y mantén visible el contexto del inmueble seleccionado en el formulario.
- Trata precios, ubicaciones, tipos, operación y características como datos; no dependas de texto visual fijo si el backend ya ofrece esos valores.

## Verificación

Antes de entregar, prueba el recorrido catálogo → inmueble → solicitud con datos reales o fixtures representativos, incluyendo filtros combinados, ausencia de resultados, carga, error y respuesta exitosa. Comprueba que el formulario se asocia al inmueble correcto y que los estados reflejan la respuesta real del backend.

Verifica el recorrido al menos en un ancho móvil y uno de escritorio, además de teclado y foco. Revisa contraste, textos alternativos, validación, enlace al aviso de privacidad, imágenes y ausencia de errores de consola. Si el proyecto tiene pruebas o herramientas de lint/build, ejecútalas y reporta cualquier bloqueo restante.
