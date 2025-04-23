# Chatbot con IA para Sistema Escolar

Este chatbot inteligente está diseñado para responder preguntas frecuentes de estudiantes, padres y personal de la escuela. Utiliza una combinación de respuestas predefinidas y una base de datos de preguntas y respuestas para proporcionar asistencia inmediata.

## Características

- Interfaz de chat amigable y fácil de usar
- Respuestas automáticas basadas en palabras clave
- Base de datos de preguntas y respuestas frecuentes
- Panel de administración para gestionar las respuestas
- Indicador de escritura para mejorar la experiencia del usuario
- Diseño responsive que funciona en dispositivos móviles y de escritorio

## Requisitos

- PHP 7.4 o superior
- MySQL/PostgreSQL
- Servidor web (Apache, Nginx, etc.)
- Conexión a Internet para cargar las bibliotecas de Bootstrap y Font Awesome

## Instalación

1. **Crear la tabla en la base de datos**

   Ejecuta el archivo SQL `chatbot_faq.sql` en tu base de datos para crear la tabla necesaria:

   ```sql
   mysql -u usuario -p nombre_base_datos < chatbot_faq.sql
   ```

   O copia y pega el contenido del archivo en tu gestor de base de datos.

2. **Colocar los archivos en el servidor**

   - Coloca `includes/chatbot.php` en la carpeta de includes de tu proyecto
   - Coloca `includes/chatbot_responses.php` en la carpeta de includes de tu proyecto
   - Coloca `css/chatbot.css` en la carpeta de CSS de tu proyecto
   - Coloca `admin/chatbot_admin.php` en la carpeta de administración de tu proyecto

3. **Incluir el chatbot en tus páginas**

   Para incluir el chatbot en cualquier página, agrega la siguiente línea:

   ```php
   <?php include 'includes/chatbot.php'; ?>
   ```

   O si estás en un subdirectorio:

   ```php
   <?php include '../includes/chatbot.php'; ?>
   ```

## Uso

### Para usuarios

1. Haz clic en el botón de chat en la esquina inferior derecha de la página
2. Escribe tu pregunta en el campo de texto
3. Presiona Enter o haz clic en el botón "Enviar"
4. El chatbot responderá automáticamente

### Para administradores

1. Accede al panel de administración en `admin/chatbot_admin.php`
2. Agrega, edita o elimina preguntas y respuestas según sea necesario
3. Organiza las preguntas por categorías para facilitar la gestión

## Personalización

### Estilos

Puedes personalizar la apariencia del chatbot editando el archivo `css/chatbot.css`. Los principales elementos que puedes modificar son:

- Colores de fondo y texto
- Tamaños de fuente
- Bordes y sombras
- Animaciones

### Respuestas

Puedes modificar las respuestas predefinidas en el archivo `includes/chatbot_responses.php`. Busca la sección con las condiciones `if (strpos($question, ...))` y ajusta las respuestas según tus necesidades.

## Solución de problemas

### El chatbot no aparece

- Verifica que la ruta al archivo `chatbot.php` sea correcta
- Asegúrate de que el archivo CSS se esté cargando correctamente
- Revisa la consola del navegador para ver si hay errores JavaScript

### Las respuestas no se muestran

- Verifica que la conexión a la base de datos esté funcionando correctamente
- Asegúrate de que la tabla `respuestas_chatbot` exista y tenga datos
- Revisa los logs de error de PHP para ver si hay mensajes de error

## Mejoras futuras

- Integración con APIs de procesamiento de lenguaje natural para respuestas más inteligentes
- Capacidad para aprender de las interacciones con los usuarios
- Integración con sistemas de tickets para escalar preguntas complejas a agentes humanos
- Análisis de sentimiento para detectar frustración del usuario
- Soporte para múltiples idiomas

## Licencia

Este proyecto está disponible bajo la licencia MIT. Consulta el archivo LICENSE para más detalles. 