# Sistema de Gestión Académica

Este es un sistema de gestión académica desarrollado en PHP que incluye funcionalidades para administración, profesores y estudiantes.
Este es un proyecto completamente personal por lo que tinen muchos errores.

## Características Principales

- Sistema de autenticación y autorización
- Gestión de admisiones
- Mapa interactivo
- Chatbot de asistencia
- Gestión de documentos
- Paneles de control para diferentes roles (admin, profesor, estudiante)

## Requisitos del Sistema

- PHP 7.4 o superior
- postgresql 15 o superior
- Servidor web Apache con mod_rewrite habilitado
- Extensión PHP para postgresql
- Extensión PHP para manejo de archivos

## Estructura del Proyecto

```
├── admin/           # Panel de administración
├── api/            # Endpoints de la API
├── assets/         # Recursos estáticos
├── config/         # Archivos de configuración
├── css/            # Hojas de estilo
├── database/       # Scripts de base de datos
├── form/           # Formularios
├── includes/       # Archivos incluidos
├── js/             # Scripts JavaScript
├── profesor/       # Panel de profesores
├── sql/            # Consultas SQL
└── estudiante/     # Panel de estudiantes
```

## Instalación

1. Clonar el repositorio
2. Configurar la base de datos (ver `database/`)
3. Copiar `config/config.example.php` a `config/config.php` y ajustar las configuraciones
4. Configurar el servidor web para apuntar al directorio del proyecto
5. Asegurarse de que los permisos de escritura estén configurados correctamente en los directorios necesarios

## Configuración

Los archivos de configuración principales se encuentran en el directorio `config/`. Asegúrate de configurar:

- Conexión a la base de datos
- Configuraciones de correo electrónico
- Configuraciones de seguridad
- Rutas del sistema

## Contribución

Las contribuciones son bienvenidas. Por favor, sigue estos pasos:

1. Haz un fork del proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Haz commit de tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles. 
