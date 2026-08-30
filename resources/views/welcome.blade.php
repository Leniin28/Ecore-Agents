<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <main class="welcome-card">
            <h1>ECore Agents</h1>
            <p>Proyecto académico de Negocios Electrónicos 2.</p>
            <p>La aplicación Laravel está instalada y lista para iniciar su desarrollo.</p>
        </main>
    </body>
</html>
