# Barrio Alerta — Plataforma ciudadana para reportar problemas del barrio

Barrio Alerta es un MVP académico para que la comunidad reporte problemas del barrio en un mapa y el municipio gestione su atención con transparencia. La plataforma evita que un reporte se marque como resuelto únicamente por decisión administrativa: la comunidad debe confirmarlo.

## Filosofía del proyecto

El proyecto combate el "juega vivo" mediante tres reglas:

1. **Auto-expiración:** los reportes reflejan problemas actuales. Un reporte vence a las 24 horas si no recibe actividad y se conserva como borrado lógico.
2. **Anti-duplicados:** reportes de la misma categoría, cercanos y con títulos similares se agrupan como una oportunidad para apoyar el reporte existente.
3. **Confirmación ciudadana:** el municipio solo puede marcar "Resuelto (por confirmar)". Tres votos ciudadanos favorables, con mayoría de votos "sí", producen el estado "Resuelto".

## Características principales

- Registro, inicio y cierre de sesión.
- Roles `ciudadano` y `admin`.
- Creación de reportes con título, descripción, categoría, ubicación, dirección y una foto.
- Validación de fotos por MIME real y límite de 2 MB.
- Mapa público con Leaflet y OpenStreetMap.
- Filtros por categoría y estado.
- Detección de duplicados a menos de 50 metros y con más de 60% de similitud de título.
- Sistema de apoyos únicos por usuario.
- Extensión de vigencia por apoyos con tope de 7 días.
- Dashboard municipal con filtros y cambios controlados de estado.
- Confirmación ciudadana de resoluciones.
- Protección CSRF, sesiones PHP y consultas PDO preparadas.
- Mensajes flash, toasts y respuestas AJAX para acciones interactivas.

## Tecnologías usadas

- PHP 8+
- MySQL / MariaDB
- PDO
- Bootstrap 5
- Bootstrap Icons
- Leaflet.js
- OpenStreetMap
- Alpine.js por CDN
- Apache mediante XAMPP

## Requisitos previos

- Windows con XAMPP.
- Apache activo.
- MySQL activo.
- PHP 8.0 o superior.
- Extensiones PHP `pdo_mysql`, `fileinfo` y `mbstring` habilitadas.

## Instalación

1. Descarga o clona el proyecto.
2. Copia la carpeta en:

   ```text
   C:\xampp\htdocs\barrio-alerta\
   ```

3. Inicia Apache y MySQL desde XAMPP.
4. Abre phpMyAdmin e importa `sql/barrio_alerta.sql`.
5. Copia `.env.example` como `.env` y ajusta sus valores si es necesario:

   ```dotenv
   DB_HOST=localhost
   DB_NAME=barrio_alerta
   DB_USER=root
   DB_PASS=
   APP_URL=http://barrio-alerta.local
   UPLOAD_DIR=uploads/
   ```

6. El script principal deja preparado el administrador de prueba. Si necesitas generar otro hash:

   ```bash
   php -r "echo password_hash('tu-clave', PASSWORD_DEFAULT), PHP_EOL;"
   ```

   Actualízalo en MySQL:

   ```sql
   UPDATE usuarios
   SET password = 'HASH_GENERADO'
   WHERE email = 'admin@barrio.com';
   ```

7. Opcionalmente configura un VirtualHost de Apache para `barrio-alerta.local` y agrega `127.0.0.1 barrio-alerta.local` al archivo `C:\Windows\System32\drivers\etc\hosts`.
8. Abre:

   ```text
   http://localhost/barrio-alerta/public/
   ```

9. Para datos de demostración, importa después `sql/datos_prueba.sql`.

## Credenciales de prueba

- **Administrador:** `admin@barrio.com` / `admin123`
- **Ciudadano:** registra uno desde la opción **Registrarse**.
- **Datos de prueba:** `maria@test.com`, `pedro@test.com` y `ana@test.com` / `test123` después de importar `sql/datos_prueba.sql`.

## Estructura del proyecto

```text
barrio-alerta/
├── public/
│   ├── .htaccess
│   ├── index.php
│   ├── favicon.svg
│   ├── assets/
│   │   ├── css/style.css
│   │   └── js/app.js
│   └── uploads/
├── src/
│   ├── config/database.php
│   ├── controllers/
│   │   ├── AdminController.php
│   │   ├── AuthController.php
│   │   ├── ConfirmacionController.php
│   │   ├── MapaController.php
│   │   ├── ReporteController.php
│   │   └── VotoController.php
│   ├── helpers/
│   │   ├── auth.php
│   │   ├── duplicados.php
│   │   └── upload.php
│   └── views/
│       ├── admin/dashboard.php
│       ├── auth/
│       ├── layouts/
│       └── reportes/
├── sql/
│   ├── barrio_alerta.sql
│   └── datos_prueba.sql
├── logs/
├── .env.example
├── .gitignore
├── DEFENSA.md
└── README.md
```

## Reglas de negocio

```text
Crear reporte
     |
     v
Pendiente (1) ---> En proceso (2) ---> Resuelto por confirmar (3)
       |                                      |
       +--> Rechazado (5)                    +--> 3 sí y mayoría ---> Resuelto (4)
                                              |
                                              +--> 3 no y mayoría ---> En proceso (2)
```

- Un apoyo extiende 24 horas, sin superar siete días desde la creación.
- Una resolución confirmada extiende 48 horas, también respetando el tope de siete días.
- El administrador nunca escribe directamente el estado 4.
- Los votos de confirmación son únicos por usuario y reporte.
- Los reportes expirados se marcan con `expirado = 1` y no aparecen en el mapa.

## Capturas de pantalla

Coloca las capturas finales en `docs/capturas/` cuando estén disponibles:

- `[CAPTURA: mapa público](docs/capturas/mapa.png)`
- `[CAPTURA: creación de reporte](docs/capturas/crear-reporte.png)`
- `[CAPTURA: detalle y apoyos](docs/capturas/detalle.png)`
- `[CAPTURA: dashboard municipal](docs/capturas/dashboard.png)`
- `[CAPTURA: confirmación ciudadana](docs/capturas/confirmacion.png)`

## Licencia

Este proyecto se distribuye bajo la licencia MIT. Es un MVP académico y puede adaptarse para fines educativos o comunitarios.
