# Cómo levantar Moodle — Guía paso a paso

## Lo que necesitás tener instalado

- **Docker Desktop** (ya instalado en esta máquina)
- **WSL2** (ya configurado — se instaló automáticamente)

---s

## Levantar Moodle (uso normal)

### Paso 1 — Abrir Docker Desktop

Buscar "Docker Desktop" en el menú de inicio y abrirlo.
Esperar hasta que el ícono de la ballena 🐋 en la barra de tareas deje de animarse.

> Si Docker Desktop no abre o da error, ver la sección **Solución de problemas** más abajo.

### Paso 2 — Abrir una terminal en la carpeta correcta

Abrir PowerShell o CMD y ejecutar:

```bash
cd "D:\datos\Documents\Laura Valero\2026\Univalle\Trabajo integrador\moodle"
```

### Paso 3 — Levantar los contenedores

```bash
docker compose up -d
```

**Primera vez:** tarda ~5 minutos mientras inicializa la base de datos. Las siguientes veces es casi instantáneo.

### Paso 4 — Verificar que está corriendo

```bash
docker compose ps
```

Deberías ver algo así:

```
NAME         IMAGE                    STATUS
moodle_app   bitnamilegacy/moodle:4   Up
moodle_db    mariadb:10.11            Up (healthy)
```

### Paso 5 — Abrir Moodle en el navegador

Ir a: **http://localhost:8080**

| Campo | Valor |
|-------|-------|
| Usuario admin | `admin` |
| Contraseña | `Admin1234!` |

---

## Detener Moodle

```bash
# Detener (los datos se conservan para la próxima vez)
docker compose down
```

```bash
# Detener y BORRAR TODOS LOS DATOS (vuelve a cero)
docker compose down -v
```

---

## Ver qué está pasando (logs)

```bash
# Ver logs en tiempo real de Moodle
docker logs -f moodle_app

# Ver logs de la base de datos
docker logs -f moodle_db

# Salir de los logs: Ctrl + C
```

---

## Solución de problemas

### Docker Desktop no inicia / "Docker Desktop is unable to start"

Esto pasa cuando el servicio de WSL está detenido. Solución:

**1.** Abrir PowerShell **como Administrador** (clic derecho → "Ejecutar como administrador")

**2.** Ejecutar:
```powershell
sc.exe config LxssManager start= auto
net start LxssManager
```

**3.** Cerrar Docker Desktop si está abierto y volver a abrirlo.

**4.** Esperar ~1 minuto y volver al **Paso 2** de la guía normal.

> Alternativa más fácil: hacer doble clic en el archivo `habilitar-wsl-admin.bat` que está en la carpeta del proyecto (pide confirmación de administrador, acepta).

---

### Moodle carga muy lento la primera vez

Normal. La primera vez que se levanta, Moodle instala la base de datos. Esperar 5 minutos y refrescar la página.

Para ver el progreso:
```bash
docker logs -f moodle_app
```
Cuando aparezca una línea con `apache2` o `Starting Apache`, ya está listo.

---

### "Port 8080 is already in use"

Algún otro programa está usando el puerto 8080. Opciones:

**Opción A** — Encontrar y cerrar el programa que lo usa:
```powershell
netstat -ano | findstr :8080
# Anotar el PID que aparece y cerrarlo desde el Administrador de tareas
```

**Opción B** — Cambiar el puerto de Moodle en `docker-compose.yml`:
```yaml
ports:
  - "8081:8080"   # cambiar 8080 por 8081 (o cualquier otro libre)
```
Luego acceder en http://localhost:8081

---

### Los contenedores aparecen pero Moodle no abre en el navegador

Esperar un poco más (sobre todo la primera vez) y refrescar.
Si después de 10 minutos sigue sin responder:

```bash
docker compose down
docker compose up -d
```

---

### Quiero empezar desde cero (borrar todo)

```bash
docker compose down -v
docker compose up -d
```

Esto borra usuarios, cursos y configuración. Para volver a poblar los datos de prueba:

```bash
# Recrear webservices y token
docker cp setup_ws.php moodle_app:/tmp/setup_ws.php
docker exec moodle_app bash -c "php /tmp/setup_ws.php"

# Recrear usuarios, curso y foro
python seed_moodle.py

# Recrear actividades (glosario, tareas, quiz, páginas)
docker cp create_forum.php moodle_app:/tmp/create_forum.php
docker exec moodle_app bash -c "php /tmp/create_forum.php"

docker cp create_posts.php moodle_app:/tmp/create_posts.php
docker exec moodle_app bash -c "php /tmp/create_posts.php"

docker cp add_activities.php moodle_app:/tmp/add_activities.php
docker exec moodle_app bash -c "php /tmp/add_activities.php"

docker cp fix_quiz.php moodle_app:/tmp/fix_quiz.php
docker exec moodle_app bash -c "php /tmp/fix_quiz.php"
```

> El token cambia cada vez que se ejecuta `setup_ws.php`. Actualizar el valor en `seed_moodle.py` y en `mira/.env.test`.

---

## Resumen rápido (para el día a día)

```
1. Abrir Docker Desktop → esperar a que cargue
2. cd moodle-local
3. docker compose up -d
4. Abrir http://localhost:8080
```

```
Al terminar:
docker compose down
```

---

*Proyecto MIRA / Trabajo Integrador Univalle — 2026*
