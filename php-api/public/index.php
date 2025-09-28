<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use App\UserController;
use App\ProductController;
use App\SalesController;
use App\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
if (file_exists(dirname(__DIR__) . '/.env')) $dotenv->load();

$container = new Container();
AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addBodyParsingMiddleware();

$app->get('/health', function($req,$res) {
  $res->getBody()->write(json_encode(['ok'=>true]));
  return $res->withHeader('Content-Type','application/json');
});

// Users - CRUD completo
$app->get('/api/users', [UserController::class,'list']);          // Listar usuarios
$app->get('/api/users/{id}', [UserController::class,'get']);      // Obtener usuario por ID
$app->post('/api/users', [UserController::class,'create']);       // Crear usuario
$app->put('/api/users/{id}', [UserController::class,'update']);   // Actualizar usuario
$app->delete('/api/users/{id}', [UserController::class,'delete']); // Eliminar usuario

// Products - CRUD completo
$app->get('/api/products', [ProductController::class,'list']);          // Listar productos
$app->get('/api/products/{id}', [ProductController::class,'get']);      // Obtener producto por ID
$app->post('/api/products', [ProductController::class,'create']);       // Crear producto
$app->put('/api/products/{id}', [ProductController::class,'update']);   // Actualizar producto
$app->delete('/api/products/{id}', [ProductController::class,'delete']); // Eliminar producto

// Sales - CRUD completo
$app->get('/api/sales', [SalesController::class,'list']);          // Listar ventas
$app->get('/api/sales/{id}', [SalesController::class,'get']);      // Obtener venta por ID
$app->post('/api/sales', [SalesController::class,'create']);       // Crear venta
$app->put('/api/sales/{id}', [SalesController::class,'update']);   // Actualizar venta
$app->delete('/api/sales/{id}', [SalesController::class,'delete']); // Eliminar venta

$app->run();
