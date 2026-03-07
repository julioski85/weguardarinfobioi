REGISTRO DIARIO DE TIENDA
=========================

Qué incluye
-----------
- Login por tienda y login administrador
- Cada tienda solo puede registrar 1 vez por día
- Fecha automática
- Entraron = Cliente nuevo + Recurrentes
- Validación: Compraron no puede ser mayor que Entraron
- Panel admin con filtros, gráficas, edición, eliminación y exportación a Excel
- Página para cambiar contraseñas de tiendas

Usuarios iniciales
------------------
Admin:
- usuario: admin
- contraseña: admin123

Tiendas:
- usuarios: tianguis, sanangel, sendero, metepec, delmazo, atlaco, zina, xona
- contraseña inicial de todas: 12345678

Cómo instalar en Hostinger
--------------------------
1) Sube todos los archivos a tu carpeta del dominio o subdominio.
2) Abre phpMyAdmin en Hostinger.
3) Selecciona la base de datos:
   u801126150_fern4
4) Importa el archivo:
   sql/install.sql
5) Edita el archivo config.php y coloca tu contraseña MySQL en:
   define('DB_PASS', 'AQUI_VA_TU_PASSWORD');
6) Entra al sitio y prueba:
   /login.php

Notas
-----
- La app está lista para usar con localhost, base y usuario:
  host: localhost
  db: u801126150_fern4
  user: u801126150_fern4
- Si quieres, en el siguiente paso te puedo hacer una versión 2 con:
  - dashboard más premium
  - reset de contraseña admin
  - paginación
  - búsqueda rápida
  - tarjetas más visuales
