# Moodle Local

Instancia local de Moodle para desarrollo y pruebas del middleware.

## Levantar

```bash
cd moodle
docker compose up -d
```

La primera vez tarda ~5 minutos mientras inicializa la base de datos.

## Acceso

| Campo    | Valor                    |
|----------|--------------------------|
| URL      | http://localhost:8080    |
| Usuario  | admin                    |
| Password | Admin1234!               |

## Obtener token

1. Entrar como admin en http://localhost:8080
2. Ir a: Administración del sitio → Plugins → Webservices → Gestionar tokens
3. O acceder directo: http://localhost:8080/admin/webservice/tokens.php
4. Crear un token para el usuario admin y el servicio "REST"
5. Copiar el token generado

## Configurar el middleware para usar este Moodle

Editar `lv-llm/.env`:

```
MOODLE_TOKEN_LOCAL=<token-generado-en-el-paso-anterior>
```

## Detener

```bash
docker compose down        # detiene los contenedores
docker compose down -v     # detiene y borra todos los datos
```
