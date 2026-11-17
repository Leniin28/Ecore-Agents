# Correo de recuperación y comprobantes

La aplicación envía un código temporal de recuperación y un comprobante de compra al correo registrado. Los pagos son simulados. Para enviar correos reales configura un proveedor SMTP autorizado en el archivo local `.env` de cada equipo: `MAIL_MAILER=smtp`, `MAIL_SCHEME`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` y `MAIL_FROM_NAME` según tu proveedor. No compartas ni subas credenciales a Git.

El transporte por defecto `log` no entrega mensajes reales y se bloquea para estos envíos. Después de cambiar la configuración ejecuta `php artisan config:clear` y reinicia el servidor. Ejecuta `php artisan migrate` para crear la tabla de códigos sin borrar datos existentes.

El comprobante se intenta enviar después de registrar la compra. Si falla, la compra se conserva y la pantalla informa del fallo; el cliente puede reintentar desde el comprobante. La aceptación por SMTP no demuestra entrega en la bandeja. Si la conexión falla después de que el proveedor acepte el mensaje, un reintento podría duplicar el correo.
