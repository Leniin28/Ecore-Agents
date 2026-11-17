# Seguridad de la fase 4

El PDF solicita correo único, contraseñas protegidas, manejo de sesión o token, validaciones y protección de rutas. El proyecto utiliza sesiones de Laravel, contraseñas almacenadas mediante hash, validación de formularios, protección CSRF, limitación de intentos de inicio de sesión y control de acceso por roles, permisos y propiedad de los recursos. La sesión se regenera al iniciar y se invalida al cerrar; la opción Recuérdame utiliza el mecanismo de Laravel.

El perfil empresarial Soporte puede atender tickets desde su propio panel. Sus permisos adicionales de CRM, SCM o ERP se asignan por separado. Los clientes solo consultan sus propios tickets y comprobantes. Las acciones de atención se registran en auditoría.

La recuperación usa un código enviado por correo, con caducidad de 15 minutos, un solo uso y límite de intentos. Verifica el acceso al correo durante la recuperación; no es autenticación de dos factores para el inicio de sesión. Los pagos permanecen simulados. Para un despliegue real se debe evaluar HTTPS, cookies, secretos, verificación de cuentas, segundo factor de inicio de sesión y una revisión de seguridad independiente.
