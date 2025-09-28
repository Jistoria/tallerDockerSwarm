# API REST - Docker Swarm Taller

Una API REST completa para gestión de usuarios, productos y ventas, desarrollada en PHP con Slim Framework y PostgreSQL.

## 🚀 Características

- **CRUD completo** para usuarios, productos y ventas
- **Validaciones robustas** de datos de entrada
- **Manejo de errores** con códigos HTTP apropiados
- **Integridad referencial** en la base de datos
- **Respuestas JSON estructuradas** con formato consistente
- **Documentación completa** con ejemplos de uso

## 📋 Requisitos

- Docker & Docker Compose
- PHP 8.0+
- PostgreSQL 13+
- Composer

## 🛠️ Instalación y Configuración

1. **Clonar el repositorio**
```bash
git clone <repository-url>
cd tallerDockerSwarm
```

2. **Ejecutar con Docker Compose**
```bash
docker-compose up -d
```

3. **Verificar salud del API**
```bash
curl http://localhost:8080/health
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

## ⚠️ Códigos de Error HTTP

- **400 Bad Request:** Datos de entrada inválidos
- **404 Not Found:** Recurso no encontrado
- **409 Conflict:** Conflicto (email duplicado, referencias existentes)
- **500 Internal Server Error:** Error del servidor

## 🗂️ Estructura del Proyecto

```
tallerDockerSwarm/
├── db/
│   └── init/
│       └── 01_schema.sql          # Esquema de base de datos
├── php-api/
│   ├── src/
│   │   ├── Database.php           # Conexión a BD
│   │   ├── UserController.php     # CRUD usuarios
│   │   ├── ProductController.php  # CRUD productos
│   │   └── SalesController.php    # CRUD ventas
│   ├── public/
│   │   └── index.php             # Punto de entrada y rutas
│   ├── composer.json             # Dependencias PHP
│   └── Dockerfile               # Imagen Docker API
├── deploy/
│   └── docker-stack.yml         # Configuración Docker Swarm
└── README.md                    # Esta documentación
```

## 🧪 Testing

### Verificar la API está funcionando
```bash
curl http://localhost:8080/health
# Respuesta esperada: {"ok":true}
```

### Flujo completo de testing
```bash
# 1. Crear usuario
USER_RESPONSE=$(curl -s -X POST http://localhost:8080/api/users \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com"}')

# 2. Crear producto  
PRODUCT_RESPONSE=$(curl -s -X POST http://localhost:8080/api/products \
  -H "Content-Type: application/json" \
  -d '{"sku":"TEST-001","name":"Test Product","price":99.99}')

# 3. Crear venta
curl -X POST http://localhost:8080/api/sales \
  -H "Content-Type: application/json" \
  -d '{"user_id":1,"product_id":1,"quantity":2}'

# 4. Listar todas las entidades
curl http://localhost:8080/api/users
curl http://localhost:8080/api/products  
curl http://localhost:8080/api/sales
```

## 🤝 Contribuir

1. Fork el proyecto
2. Crea una branch para tu feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit tus cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la branch (`git push origin feature/nueva-funcionalidad`)
5. Crear un Pull Request

## 📝 Licencia

Este proyecto está bajo la Licencia MIT - ver el archivo [LICENSE.md](LICENSE.md) para detalles.