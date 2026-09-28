# Guion para la defensa de Barrio Alerta

## 1. Pitch de 30 segundos

Barrio Alerta es una plataforma ciudadana para reportar problemas del barrio en un mapa y darles seguimiento junto con el municipio. La aplicación organiza los reportes, evita duplicados y permite que la comunidad confirme si una solución realmente funcionó. Así, el municipio puede priorizar problemas visibles y la ciudadanía conserva una voz verificable sobre el resultado.

## 2. Problema que resuelve

- Los problemas se reportan por canales dispersos y se pierde su ubicación.
- Muchos reportes repetidos generan ruido en vez de prioridad.
- Una institución puede declarar resuelto un problema que la comunidad todavía observa.

## 3. Solución propuesta

### Auto-expiración

Los reportes activos se muestran durante 24 horas. Los apoyos extienden la vigencia, pero existe un tope de siete días desde la creación. La eliminación es lógica mediante `expirado = 1`.

### Anti-duplicados

Al crear un reporte se comparan categoría, distancia geográfica mediante Haversine y similitud del título. Si la coincidencia supera los límites, se propone apoyar el reporte existente.

### Confirmación ciudadana

El municipio puede pasar un reporte a "Resuelto (por confirmar)". Solo tres confirmaciones ciudadanas con mayoría de votos "sí" lo convierten en "Resuelto". Tres votos "no" con mayoría lo devuelven a "En proceso".

```text
Pendiente -> En proceso -> Resuelto por confirmar
     |                              |
     +-> Rechazado                  +-> 3 sí: Resuelto
                                    +-> 3 no: En proceso
```

## 4. Demo paso a paso

1. Mostrar el mapa público y un popup con categoría, estado y apoyos.
2. Registrarse como ciudadano y crear un reporte con ubicación y foto.
3. Crear un reporte similar para mostrar el modal anti-duplicados.
4. Apoyar el reporte existente y mostrar el contador actualizado.
5. Entrar como administrador y abrir el dashboard.
6. Cambiar un reporte a "En proceso" y después a "Resuelto (por confirmar)".
7. Entrar con tres ciudadanos, votar "Sí, se resolvió" y mostrar el estado `Resuelto`.
8. Mostrar la consulta SQL de estados, votos, confirmaciones y vigencia.

## 5. Preguntas probables

### ¿Por qué PHP sin framework?

El alcance es un MVP académico pequeño. PHP nativo permite mostrar claramente sesiones, PDO, CSRF, validaciones y el flujo MVC sin agregar dependencias innecesarias.

### ¿Cómo evitas SQL injection?

Todas las entradas que llegan a SQL se procesan mediante PDO con consultas preparadas y parámetros nombrados. Los valores impresos en HTML se escapan con `htmlspecialchars()`.

### ¿Cómo funciona la detección de duplicados?

Se filtra la misma categoría y una distancia menor a 50 metros usando Haversine en SQL. Después `similar_text()` compara los títulos y se acepta una coincidencia superior al 60%.

### ¿Por qué el admin no puede marcar resuelto?

Porque la regla central del proyecto exige evidencia comunitaria. El controlador administrativo solo permite estados 2, 3 y 5; el estado 4 solo aparece en `ConfirmacionController` después del umbral ciudadano.

### ¿Cómo escalarías esto a nivel municipal?

Separaría servicios y módulos, agregaría índices espaciales, colas para tareas programadas, almacenamiento externo de imágenes, auditoría de cambios y autenticación institucional.

### ¿Qué mejorarías con más tiempo?

Pruebas automatizadas, paginación, moderación de imágenes, notificaciones opt-in, accesibilidad avanzada, monitoreo y una API versionada.

## 6. Mejoras futuras

- Notificaciones por correo con consentimiento.
- Aplicación móvil o PWA.
- Panel de auditoría de cambios.
- Paginación y búsqueda avanzada.
- Moderación de contenido e imágenes.
- Geocodificación opcional.
- Exportación de reportes para el municipio.
- Índices espaciales para mayor volumen.

## 7. Tecnologías justificadas

- **PHP:** disponible en XAMPP y adecuado para un MVP académico server-side.
- **MySQL:** persistencia relacional para usuarios, reportes, votos y confirmaciones.
- **PDO:** consultas preparadas y acceso seguro a la base de datos.
- **Bootstrap:** interfaz responsive sin dependencias de compilación.
- **Leaflet:** mapa interactivo ligero y compatible con OpenStreetMap.
- **Alpine.js:** disponible para pequeñas interacciones sin introducir un framework pesado.
- **Apache/XAMPP:** entorno local solicitado para la entrega.
