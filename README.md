# API REST - Docker Swarm Taller

Una API REST completa para gestión de usuarios, productos y ventas, desarrollada en PHP con Slim Framework y PostgreSQL, desplegada usando Docker Swarm para alta disponibilidad y escalabilidad.

## 🚀 Características

- **CRUD completo** para usuarios, productos y ventas
- **Validaciones robustas** de datos de entrada
- **Manejo de errores** con códigos HTTP apropiados
- **Integridad referencial** en la base de datos
- **Respuestas JSON estructuradas** con formato consistente
- **Documentación completa** con ejemplos de uso
- **Alta disponibilidad** con Docker Swarm
- **Escalabilidad horizontal** con múltiples réplicas
- **Load balancing** automático entre servicios

## 📋 Requisitos del Sistema

### Tecnologías Requeridas
- **Docker Engine** 20.10+ (con modo Swarm habilitado)
- **Docker CLI** con comando `docker stack`
- **Sistema Operativo**: Linux (Ubuntu 20.04+, CentOS 8+, RHEL 8+) o macOS
- **Arquitectura**: x86_64 (AMD64) o ARM64
- **RAM mínima**: 2GB por nodo
- **Espacio en disco**: 10GB mínimo

### Componentes de la Aplicación
- **API Backend**: PHP 8.1 con Slim Framework 4
- **Base de Datos**: PostgreSQL 16 Alpine
- **Proxy/Load Balancer**: Docker Swarm routing mesh integrado
- **Orquestación**: Docker Swarm mode

## � Configuración de Docker Swarm

### Paso 1: Inicializar Docker Swarm

**En el nodo manager (líder del cluster):**
```bash
# Inicializar Swarm (reemplaza la IP por la del nodo manager)
docker swarm init --advertise-addr <IP_MANAGER_NODE>

# Ejemplo:
docker swarm init --advertise-addr 192.168.1.100
```

**Para agregar nodos worker (opcional):**
```bash
# En el nodo manager, obtén el token para workers
docker swarm join-token worker

# En cada nodo worker, ejecuta el comando mostrado:
docker swarm join --token <TOKEN> <IP_MANAGER>:2377
```

### Paso 2: Verificar el estado del cluster
```bash
# Verificar nodos en el swarm
docker node ls

# Debería mostrar al menos un nodo MANAGER con estado Ready
```

## 🛠️ Despliegue de la Aplicación

### Paso 1: Clonar el repositorio
```bash
git clone <repository-url>
cd tallerDockerSwarm
```

### Paso 2: Construir la imagen de la API (en el nodo manager)
```bash
# Construir la imagen Docker para la API
docker build -t php-api:latest ./php-api/

# Verificar que la imagen se creó correctamente
docker images | grep php-api
```

### Paso 3: Desplegar el stack
```bash
# Desplegar el stack completo desde el directorio deploy/
docker stack deploy -c deploy/docker-stack..yml api-stack

# Verificar el despliegue
docker stack ls
```

### Paso 4: Monitorear los servicios
```bash
# Ver estado de todos los servicios
docker service ls

# Ver detalles de un servicio específico
docker service ps api-stack_api
docker service ps api-stack_db

# Ver logs de los servicios
docker service logs api-stack_api
docker service logs api-stack_db
```

### Paso 5: Verificar el funcionamiento
```bash
# Verificar salud del API
curl http://localhost:8080/health

# Debería retornar: {"ok":true}
```

## ⚖️ Escalado y Alta Disponibilidad

### Configuración actual del stack:
- **Base de Datos (PostgreSQL)**: 1 réplica (modo single)
- **API (PHP)**: 3 réplicas con load balancing automático
- **Red overlay**: Comunicación segura entre servicios
- **Volumen persistente**: Datos de PostgreSQL preservados

### Escalar servicios manualmente:
```bash
# Escalar el servicio API a 5 réplicas
docker service scale api-stack_api=5

# Verificar el escalado
docker service ps api-stack_api
```

### Rolling updates:
```bash
# Actualizar la imagen de la API sin downtime
docker service update --image php-api:v2.0 api-stack_api

# Verificar el progreso de la actualización
docker service ps api-stack_api
```

## 📚 Documentación del API

### Formato de Respuesta

Todas las respuestas siguen el formato JSON estándar:

**Respuesta exitosa:**
```json
{
  "success": true,
  "data": { /* objeto o array de datos */ },
  "message": "Mensaje descriptivo (opcional)"
}
```

**Respuesta con error:**
```json
{
  "success": false,
  "error": "Descripción del error",
  "message": "Detalles adicionales (opcional)"
}
```

### 👥 Endpoints de Usuarios

#### Listar Usuarios
- **GET** `/api/users`
- **Descripción:** Obtiene una lista de todos los usuarios
- **Respuesta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Juan Pérez",
      "email": "juan@example.com",
      "created_at": "2024-01-15T10:30:00Z"
    }
  ],
  "count": 1
}
```

#### Obtener Usuario por ID
- **GET** `/api/users/{id}`
- **Parámetros:** `id` (integer) - ID del usuario
- **Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "created_at": "2024-01-15T10:30:00Z"
  }
}
```
- **Error 404:** Usuario no encontrado

#### Crear Usuario
- **POST** `/api/users`
- **Content-Type:** `application/json`
- **Body:**
```json
{
  "name": "Juan Pérez",
  "email": "juan@example.com"
}
```
- **Validaciones:**
  - `name`: requerido, no vacío
  - `email`: requerido, formato válido, único
- **Respuesta exitosa (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "created_at": "2024-01-15T10:30:00Z"
  },
  "message": "Usuario creado exitosamente"
}
```
- **Error 409:** Email ya en uso

#### Actualizar Usuario
- **PUT** `/api/users/{id}`
- **Content-Type:** `application/json`
- **Body (campos opcionales):**
```json
{
  "name": "Juan Carlos Pérez",
  "email": "juan.carlos@example.com"
}
```
- **Respuesta exitosa (200):** Usuario actualizado
- **Error 404:** Usuario no encontrado

#### Eliminar Usuario
- **DELETE** `/api/users/{id}`
- **Respuesta exitosa (200):**
```json
{
  "success": true,
  "message": "Usuario eliminado exitosamente"
}
```
- **Error 409:** Usuario tiene ventas asociadas

### 📦 Endpoints de Productos

#### Listar Productos
- **GET** `/api/products`
- **Respuesta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "sku": "PROD-001",
      "name": "Laptop Gaming",
      "price": "1299.99",
      "created_at": "2024-01-15T10:30:00Z"
    }
  ],
  "count": 1
}
```

#### Obtener Producto por ID
- **GET** `/api/products/{id}`
- **Parámetros:** `id` (integer) - ID del producto
- **Respuesta similar a usuarios**

#### Crear Producto
- **POST** `/api/products`
- **Body:**
```json
{
  "sku": "PROD-001",
  "name": "Laptop Gaming",
  "price": 1299.99
}
```
- **Validaciones:**
  - `name`: requerido, no vacío
  - `price`: requerido, número >= 0
  - `sku`: opcional, único, mínimo 3 caracteres si se proporciona
- **Respuesta exitosa (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "sku": "PROD-001",
    "name": "Laptop Gaming",
    "price": "1299.99",
    "created_at": "2024-01-15T10:30:00Z"
  },
  "message": "Producto creado exitosamente"
}
```

#### Actualizar Producto
- **PUT** `/api/products/{id}`
- **Body (campos opcionales):**
```json
{
  "sku": "PROD-001-V2",
  "name": "Laptop Gaming Pro",
  "price": 1499.99
}
```

#### Eliminar Producto
- **DELETE** `/api/products/{id}`
- **Error 409:** Producto tiene ventas asociadas

### 🛒 Endpoints de Ventas

#### Listar Ventas
- **GET** `/api/sales`
- **Respuesta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "product_id": 1,
      "user_name": "Juan Pérez",
      "user_email": "juan@example.com",
      "sku": "PROD-001",
      "product_name": "Laptop Gaming",
      "quantity": 2,
      "unit_price": "1299.99",
      "total": "2599.98",
      "created_at": "2024-01-15T14:30:00Z"
    }
  ],
  "count": 1
}
```

#### Obtener Venta por ID
- **GET** `/api/sales/{id}`
- **Respuesta similar a la lista pero con un solo elemento**

#### Crear Venta
- **POST** `/api/sales`
- **Body:**
```json
{
  "user_id": 1,
  "product_id": 1,
  "quantity": 2,
  "unit_price": 1299.99
}
```
- **Validaciones:**
  - `user_id`: requerido, debe existir
  - `product_id`: requerido, debe existir
  - `quantity`: requerido, número positivo
  - `unit_price`: opcional (usa precio del producto), >= 0
- **Respuesta exitosa (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "product_id": 1,
    "quantity": 2,
    "unit_price": "1299.99",
    "total": "2599.98",
    "created_at": "2024-01-15T14:30:00Z"
  },
  "message": "Venta creada exitosamente"
}
```

#### Actualizar Venta
- **PUT** `/api/sales/{id}`
- **Body (campos opcionales):**
```json
{
  "quantity": 3,
  "unit_price": 1199.99
}
```

#### Eliminar Venta
- **DELETE** `/api/sales/{id}`
- Sin restricciones especiales

## 🔧 Ejemplos de Uso con cURL

### Crear un usuario
```bash
curl -X POST http://localhost:8080/api/users \
  -H "Content-Type: application/json" \
  -d '{"name":"Ana García","email":"ana@example.com"}'
```

### Crear un producto
```bash
curl -X POST http://localhost:8080/api/products \
  -H "Content-Type: application/json" \
  -d '{"sku":"PHONE-001","name":"iPhone 15","price":999.99}'
```

### Crear una venta
```bash
curl -X POST http://localhost:8080/api/sales \
  -H "Content-Type: application/json" \
  -d '{"user_id":1,"product_id":1,"quantity":1}'
```

### Actualizar un usuario
```bash
curl -X PUT http://localhost:8080/api/users/1 \
  -H "Content-Type: application/json" \
  -d '{"name":"Ana García Martínez"}'
```

### Obtener un producto
```bash
curl http://localhost:8080/api/products/1
```

### Eliminar una venta
```bash
curl -X DELETE http://localhost:8080/api/sales/1
```

## 🛠️ Gestión de Docker Swarm

### Comandos útiles de administración

```bash
# Ver información detallada del swarm
docker system info | grep -A 10 Swarm

# Inspeccionar un servicio
docker service inspect api-stack_api

# Ver métricas de recursos
docker stats

# Limpiar recursos no utilizados
docker system prune -f

# Backup del stack (exportar configuración)
docker stack config api-stack > backup-stack-config.yml
```

### Actualización y rollback

```bash
# Actualizar imagen de la API con rolling update
docker service update --image php-api:v2.0 api-stack_api

# Hacer rollback si hay problemas
docker service rollback api-stack_api

# Ver historial de actualizaciones
docker service ps api-stack_api --no-trunc
```

### Remover el stack y limpiar

```bash
# Remover el stack completo
docker stack rm api-stack

# Verificar que se removió
docker stack ls

# Limpiar volúmenes (CUIDADO: elimina datos de BD)
docker volume prune

# Salir del modo swarm (solo si es necesario)
docker swarm leave --force
```

## 🚨 Troubleshooting

### Problemas comunes y soluciones

#### El stack no se despliega
```bash
# Verificar sintaxis del docker-stack.yml
docker stack config -c deploy/docker-stack..yml

# Verificar que las imágenes existen
docker images | grep php-api

# Verificar logs del servicio
docker service logs api-stack_api
```

#### Servicios en estado "pending"
```bash
# Verificar restricciones de recursos
docker service ps api-stack_api --no-trunc

# Verificar disponibilidad de nodos
docker node ls

# Verificar si hay errores de networking
docker network ls | grep backend
```

#### Base de datos no se conecta
```bash
# Verificar que el servicio db está corriendo
docker service ps api-stack_db

# Conectarse directamente a PostgreSQL
docker exec -it $(docker ps -q -f name=api-stack_db) psql -U admin -d store

# Verificar variables de entorno
docker service inspect api-stack_db | grep -A 20 Env
```

#### API no responde
```bash
# Verificar que el puerto está expuesto
netstat -tlnp | grep :8080

# Probar conectividad interna
docker exec -it $(docker ps -q -f name=api-stack_api) curl localhost:8080/health

# Verificar logs detallados
docker service logs --details api-stack_api
```

## ⚠️ Códigos de Error HTTP

- **400 Bad Request:** Datos de entrada inválidos
- **404 Not Found:** Recurso no encontrado
- **409 Conflict:** Conflicto (email duplicado, referencias existentes)
- **500 Internal Server Error:** Error del servidor

## 📊 Consideraciones de Producción

### Seguridad
- Cambiar credenciales por defecto de PostgreSQL
- Usar Docker secrets para información sensible
- Configurar firewall en los nodos del swarm
- Usar TLS para comunicación entre nodos

### Rendimiento
- Monitorear uso de CPU y memoria con `docker stats`
- Ajustar número de réplicas según carga
- Considerar usar un proxy externo (nginx, traefik)
- Implementar caching (Redis) para mejor rendimiento

### Backup y recuperación
- Hacer backup regular del volumen `pgdata`
- Documentar procedimientos de recuperación ante desastres
- Probar restauración en ambiente de desarrollo

## 🗂️ Estructura del Proyecto

```
tallerDockerSwarm/
├── db/
│   └── init/
│       └── 01_schema.sql          # Esquema inicial de PostgreSQL
├── php-api/
│   ├── src/
│   │   ├── Database.php           # Conexión a BD con PDO
│   │   ├── UserController.php     # CRUD usuarios
│   │   ├── ProductController.php  # CRUD productos
│   │   └── SalesController.php    # CRUD ventas
│   ├── public/
│   │   └── index.php             # Punto de entrada y rutas Slim
│   ├── composer.json             # Dependencias PHP (Slim, PDO)
│   └── Dockerfile               # Imagen Docker para API PHP
├── deploy/
│   └── docker-stack..yml        # Configuración Docker Swarm Stack
└── README.md                    # Documentación completa
```

## 🔧 Arquitectura Docker Swarm

### Servicios del Stack:
- **`api-stack_db`**: PostgreSQL 16 Alpine
  - 1 réplica (single instance)
  - Volumen persistente `pgdata`
  - Red overlay `backend`
  - Health checks integrados
  
- **`api-stack_api`**: API REST PHP
  - 3 réplicas (alta disponibilidad)
  - Load balancing automático
  - Rolling updates configurados
  - Puerto expuesto: 8080

### Red y Volúmenes:
- **Red `backend`**: Overlay network para comunicación inter-servicios
- **Volumen `pgdata`**: Almacenamiento persistente de PostgreSQL

## 🧪 Testing y Monitoreo

### Verificar el despliegue de Docker Swarm
```bash
# Verificar que el stack está desplegado
docker stack ls

# Verificar estado de los servicios
docker service ls

# Verificar réplicas y distribución
docker service ps api-stack_api
docker service ps api-stack_db
```

### Verificar conectividad y salud
```bash
# Health check de la API
curl http://localhost:8080/health
# Respuesta esperada: {"ok":true}

# Verificar que el load balancing funciona (hacer varias peticiones)
for i in {1..5}; do
  curl -s http://localhost:8080/health | jq .
  sleep 1
done
```

### Flujo completo de testing funcional
```bash
# 1. Crear usuario
echo "=== Creando usuario ==="
USER_RESPONSE=$(curl -s -X POST http://localhost:8080/api/users \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com"}')
echo $USER_RESPONSE | jq .

# 2. Crear producto  
echo "=== Creando producto ==="
PRODUCT_RESPONSE=$(curl -s -X POST http://localhost:8080/api/products \
  -H "Content-Type: application/json" \
  -d '{"sku":"TEST-001","name":"Test Product","price":99.99}')
echo $PRODUCT_RESPONSE | jq .

# 3. Crear venta
echo "=== Creando venta ==="
curl -s -X POST http://localhost:8080/api/sales \
  -H "Content-Type: application/json" \
  -d '{"user_id":1,"product_id":1,"quantity":2}' | jq .

# 4. Listar todas las entidades
echo "=== Listando usuarios ==="
curl -s http://localhost:8080/api/users | jq .

echo "=== Listando productos ==="
curl -s http://localhost:8080/api/products | jq .

echo "=== Listando ventas ==="
curl -s http://localhost:8080/api/sales | jq .
```

### Testing de alta disponibilidad
```bash
# Simular falla de una réplica de API
REPLICA_ID=$(docker service ps api-stack_api --format "{{.ID}}" | head -n 1)
docker service ps api-stack_api --filter "id=$REPLICA_ID" --format "table {{.ID}}\t{{.Name}}\t{{.Node}}\t{{.CurrentState}}"

# Durante la simulación, la API debe seguir funcionando
curl http://localhost:8080/health
```

### Monitoreo de logs
```bash
# Ver logs de la API en tiempo real
docker service logs -f api-stack_api

# Ver logs de la base de datos
docker service logs -f api-stack_db

# Ver logs de un contenedor específico
docker logs $(docker ps -q -f label=com.docker.swarm.service.name=api-stack_api)
```

## 🤝 Contribuir

1. Fork el proyecto
2. Crea una branch para tu feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit tus cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la branch (`git push origin feature/nueva-funcionalidad`)
5. Crear un Pull Request

## 📝 Licencia

Este proyecto está bajo la Licencia MIT - ver el archivo [LICENSE.md](LICENSE.md) para detalles.