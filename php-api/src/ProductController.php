<?php
namespace App;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDOException;

/**
 * ProductController - Maneja las operaciones CRUD para productos
 * 
 * Endpoints disponibles:
 * - GET /api/products - Lista todos los productos
 * - GET /api/products/{id} - Obtiene un producto específico por ID
 * - POST /api/products - Crea un nuevo producto
 * - PUT /api/products/{id} - Actualiza un producto existente
 * - DELETE /api/products/{id} - Elimina un producto
 */
class ProductController {
  
  /**
   * Lista todos los productos
   * 
   * @param Request $req
   * @param Response $res
   * @return Response
   */
  public function list(Request $req, Response $res): Response {
    try {
      $q = Database::conn()->query("SELECT id,sku,name,price,created_at FROM products ORDER BY id");
      $products = $q->fetchAll();
      
      $res->getBody()->write(json_encode([
        'success' => true,
        'data' => $products,
        'count' => count($products)
      ]));
      return $res->withHeader('Content-Type','application/json');
    } catch (PDOException $e) {
      $res->getBody()->write(json_encode([
        'success' => false,
        'error' => 'Error al obtener productos',
        'message' => $e->getMessage()
      ]));
      return $res->withHeader('Content-Type','application/json')->withStatus(500);
    }
  }
  
  /**
   * Obtiene un producto específico por ID
   * 
   * @param Request $req
   * @param Response $res
   * @param array $args
   * @return Response
   */
  public function get(Request $req, Response $res, array $args): Response {
    try {
      $id = (int)$args['id'];
      
      if ($id <= 0) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'ID de producto inválido'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(400);
      }
      
      $st = Database::conn()->prepare("SELECT id,sku,name,price,created_at FROM products WHERE id = :id");
      $st->execute([':id' => $id]);
      $product = $st->fetch();
      
      if (!$product) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'Producto no encontrado'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(404);
      }
      
      $res->getBody()->write(json_encode([
        'success' => true,
        'data' => $product
      ]));
      return $res->withHeader('Content-Type','application/json');
    } catch (PDOException $e) {
      $res->getBody()->write(json_encode([
        'success' => false,
        'error' => 'Error al obtener producto',
        'message' => $e->getMessage()
      ]));
      return $res->withHeader('Content-Type','application/json')->withStatus(500);
    }
  }
  
  /**
   * Crea un nuevo producto
   * 
   * @param Request $req
   * @param Response $res
   * @return Response
   */
  public function create(Request $req, Response $res): Response {
    try {
      $d = json_decode((string)$req->getBody(), true);
      
      // Validaciones
      if (!isset($d['name']) || empty(trim($d['name']))) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'El nombre del producto es requerido'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(400);
      }
      
      if (!isset($d['price']) || !is_numeric($d['price']) || $d['price'] < 0) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'El precio debe ser un número válido mayor o igual a cero'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(400);
      }
      
      // Validar SKU si se proporciona
      if (isset($d['sku']) && !empty(trim($d['sku']))) {
        if (strlen(trim($d['sku'])) < 3) {
          $res->getBody()->write(json_encode([
            'success' => false,
            'error' => 'El SKU debe tener al menos 3 caracteres'
          ]));
          return $res->withHeader('Content-Type','application/json')->withStatus(400);
        }
      }
      
      $st = Database::conn()->prepare(
        "INSERT INTO products(sku,name,price) VALUES(:sku,:name,:price) RETURNING id,sku,name,price,created_at"
      );
      $st->execute([
        ':sku' => isset($d['sku']) ? trim($d['sku']) : null, 
        ':name' => trim($d['name']), 
        ':price' => (float)$d['price']
      ]);
      $product = $st->fetch();
      
      $res->getBody()->write(json_encode([
        'success' => true,
        'data' => $product,
        'message' => 'Producto creado exitosamente'
      ]));
      return $res->withHeader('Content-Type','application/json')->withStatus(201);
    } catch (PDOException $e) {
      if (strpos($e->getMessage(), 'products_sku_key') !== false) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'El SKU ya está en uso'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(409);
      }
      
      $res->getBody()->write(json_encode([
        'success' => false,
        'error' => 'Error al crear producto',
        'message' => $e->getMessage()
      ]));
      return $res->withHeader('Content-Type','application/json')->withStatus(500);
    }
  }
  
  /**
   * Actualiza un producto existente
   * 
   * @param Request $req
   * @param Response $res
   * @param array $args
   * @return Response
   */
  public function update(Request $req, Response $res, array $args): Response {
    try {
      $id = (int)$args['id'];
      $d = json_decode((string)$req->getBody(), true);
      
      if ($id <= 0) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'ID de producto inválido'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(400);
      }
      
      // Verificar si el producto existe
      $checkSt = Database::conn()->prepare("SELECT id FROM products WHERE id = :id");
      $checkSt->execute([':id' => $id]);
      if (!$checkSt->fetch()) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'Producto no encontrado'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(404);
      }
      
      // Validaciones
      if (isset($d['name']) && empty(trim($d['name']))) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'El nombre del producto no puede estar vacío'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(400);
      }
      
      if (isset($d['price']) && (!is_numeric($d['price']) || $d['price'] < 0)) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'El precio debe ser un número válido mayor o igual a cero'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(400);
      }
      
      if (isset($d['sku']) && !empty(trim($d['sku'])) && strlen(trim($d['sku'])) < 3) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'El SKU debe tener al menos 3 caracteres'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(400);
      }
      
      // Construir query dinámicamente
      $updates = [];
      $params = [':id' => $id];
      
      if (isset($d['sku'])) {
        $updates[] = "sku = :sku";
        $params[':sku'] = empty(trim($d['sku'])) ? null : trim($d['sku']);
      }
      
      if (isset($d['name'])) {
        $updates[] = "name = :name";
        $params[':name'] = trim($d['name']);
      }
      
      if (isset($d['price'])) {
        $updates[] = "price = :price";
        $params[':price'] = (float)$d['price'];
      }
      
      if (empty($updates)) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'No hay datos para actualizar'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(400);
      }
      
      $sql = "UPDATE products SET " . implode(', ', $updates) . " WHERE id = :id RETURNING id,sku,name,price,created_at";
      $st = Database::conn()->prepare($sql);
      $st->execute($params);
      $product = $st->fetch();
      
      $res->getBody()->write(json_encode([
        'success' => true,
        'data' => $product,
        'message' => 'Producto actualizado exitosamente'
      ]));
      return $res->withHeader('Content-Type','application/json');
    } catch (PDOException $e) {
      if (strpos($e->getMessage(), 'products_sku_key') !== false) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'El SKU ya está en uso'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(409);
      }
      
      $res->getBody()->write(json_encode([
        'success' => false,
        'error' => 'Error al actualizar producto',
        'message' => $e->getMessage()
      ]));
      return $res->withHeader('Content-Type','application/json')->withStatus(500);
    }
  }
  
  /**
   * Elimina un producto
   * 
   * @param Request $req
   * @param Response $res
   * @param array $args
   * @return Response
   */
  public function delete(Request $req, Response $res, array $args): Response {
    try {
      $id = (int)$args['id'];
      
      if ($id <= 0) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'ID de producto inválido'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(400);
      }
      
      // Verificar si el producto existe
      $checkSt = Database::conn()->prepare("SELECT id FROM products WHERE id = :id");
      $checkSt->execute([':id' => $id]);
      if (!$checkSt->fetch()) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'Producto no encontrado'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(404);
      }
      
      // Verificar si hay ventas asociadas
      $salesSt = Database::conn()->prepare("SELECT COUNT(*) FROM sales WHERE product_id = :id");
      $salesSt->execute([':id' => $id]);
      $salesCount = $salesSt->fetchColumn();
      
      if ($salesCount > 0) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'No se puede eliminar el producto porque tiene ventas asociadas',
          'sales_count' => $salesCount
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(409);
      }
      
      $st = Database::conn()->prepare("DELETE FROM products WHERE id = :id");
      $st->execute([':id' => $id]);
      
      $res->getBody()->write(json_encode([
        'success' => true,
        'message' => 'Producto eliminado exitosamente'
      ]));
      return $res->withHeader('Content-Type','application/json');
    } catch (PDOException $e) {
      $res->getBody()->write(json_encode([
        'success' => false,
        'error' => 'Error al eliminar producto',
        'message' => $e->getMessage()
      ]));
      return $res->withHeader('Content-Type','application/json')->withStatus(500);
    }
  }
}
