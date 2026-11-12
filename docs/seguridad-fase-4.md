# Seguridad de la fase 4

El PDF solicita correo único, contraseñas protegidas, manejo de sesión o token, validaciones y protección de rutas. El proyecto utiliza sesiones de Laravel, contraseñas almacenadas mediante hash, validación de formularios, protección CSRF, limitación de intentos de inicio de sesión y control de acceso por roles, permisos y propiedad de los recursos. La sesión se regenera al iniciar y se invalida al cerrar; la opción Recuérdame utiliza el mecanismo de Laravel.

El perfil empresarial Soporte puede atender tickets desde su propio panel. Sus permisos adicionales de CRM, SCM o ERP se asignan por separado. Los clientes solo consultan sus propios tickets y comprobantes. Las acciones de atención se registran en auditoría.

Los comprobantes de correo mostrados son simulaciones locales: no existe entrega de correo ni pasarela de pago real. Para un despliegue real faltaría evaluar, entre otros, HTTPS, configuración de cookies y secretos, recuperación y verificación de cuentas, segundo factor, correo confiable y una revisión de seguridad independiente.

La recuperación de contraseña académica está habilitada solo cuando `APP_ENV=local` y permite cambiar una contraseña conociendo únicamente el correo: no verifica la identidad y no es segura para un sistema publicado. Debe sustituirse por un enlace temporal de un solo uso enviado por un canal verificado antes de cualquier despliegue.
