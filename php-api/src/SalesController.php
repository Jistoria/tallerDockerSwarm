<?php
namespace App;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDOException;

/**
 * SalesController - Maneja las operaciones CRUD para ventas
 * 
 * Endpoints disponibles:
 * - GET /api/sales - Lista todas las ventas
 * - GET /api/sales/{id} - Obtiene una venta específica por ID
 * - POST /api/sales - Crea una nueva venta
 * - PUT /api/sales/{id} - Actualiza una venta existente
 * - DELETE /api/sales/{id} - Elimina una venta
 */
class SalesController {
  
  /**
   * Lista todas las ventas
   * 
   * @param Request $req
   * @param Response $res
   * @return Response
   */
  public function list(Request $req, Response $res): Response {
    try {
      $sql = "SELECT s.id, s.user_id, s.product_id, u.name AS user_name, u.email AS user_email, 
                     p.sku, p.name AS product_name, s.quantity, s.unit_price, s.total, s.created_at
              FROM sales s
              JOIN users u ON u.id = s.user_id
              JOIN products p ON p.id = s.product_id
              ORDER BY s.id DESC LIMIT 100";
      $q = Database::conn()->query($sql);
      $sales = $q->fetchAll();
      
      $res->getBody()->write(json_encode([
        'success' => true,
        'data' => $sales,
        'count' => count($sales)
      ]));
      return $res->withHeader('Content-Type','application/json');
    } catch (PDOException $e) {
      $res->getBody()->write(json_encode([
        'success' => false,
        'error' => 'Error al obtener ventas',
        'message' => $e->getMessage()
      ]));
      return $res->withHeader('Content-Type','application/json')->withStatus(500);
    }
  }
  
  /**
   * Obtiene una venta específica por ID
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
          'error' => 'ID de venta inválido'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(400);
      }
      
      $sql = "SELECT s.id, s.user_id, s.product_id, u.name AS user_name, u.email AS user_email, 
                     p.sku, p.name AS product_name, s.quantity, s.unit_price, s.total, s.created_at
              FROM sales s
              JOIN users u ON u.id = s.user_id
              JOIN products p ON p.id = s.product_id
              WHERE s.id = :id";
      $st = Database::conn()->prepare($sql);
      $st->execute([':id' => $id]);
      $sale = $st->fetch();
      
      if (!$sale) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'Venta no encontrada'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(404);
      }
      
      $res->getBody()->write(json_encode([
        'success' => true,
        'data' => $sale
      ]));
      return $res->withHeader('Content-Type','application/json');
    } catch (PDOException $e) {
      $res->getBody()->write(json_encode([
        'success' => false,
        'error' => 'Error al obtener venta',
        'message' => $e->getMessage()
      ]));
      return $res->withHeader('Content-Type','application/json')->withStatus(500);
    }
  }
  
  /**
   * Crea una nueva venta
   * 
   * @param Request $req
   * @param Response $res
   * @return Response
   */
  public function create(Request $req, Response $res): Response {
    try {
      $d = json_decode((string)$req->getBody(), true);
      
      // Validaciones
      if (!isset($d['user_id']) || !is_numeric($d['user_id']) || $d['user_id'] <= 0) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'ID de usuario inválido'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(400);
      }
      
      if (!isset($d['product_id']) || !is_numeric($d['product_id']) || $d['product_id'] <= 0) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'ID de producto inválido'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(400);
      }
      
      if (!isset($d['quantity']) || !is_numeric($d['quantity']) || $d['quantity'] <= 0) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'La cantidad debe ser un número positivo'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(400);
      }
      
      // Verificar que el usuario existe
      $userSt = Database::conn()->prepare("SELECT id FROM users WHERE id = :id");
      $userSt->execute([':id' => $d['user_id']]);
      if (!$userSt->fetch()) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'Usuario no encontrado'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(404);
      }
      
      // Verificar que el producto existe y obtener su precio
      $productSt = Database::conn()->prepare("SELECT id, price FROM products WHERE id = :id");
      $productSt->execute([':id' => $d['product_id']]);
      $product = $productSt->fetch();
      if (!$product) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'Producto no encontrado'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(404);
      }
      
      // Usar precio del producto si no se especifica unit_price
      $unitPrice = isset($d['unit_price']) ? (float)$d['unit_price'] : (float)$product['price'];
      
      if ($unitPrice < 0) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'El precio unitario debe ser mayor o igual a cero'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(400);
      }
      
      $st = Database::conn()->prepare(
        "INSERT INTO sales(user_id,product_id,quantity,unit_price)
         VALUES(:u,:p,:q,:up) RETURNING id,user_id,product_id,quantity,unit_price,total,created_at"
      );
      $st->execute([
        ':u' => (int)$d['user_id'], 
        ':p' => (int)$d['product_id'], 
        ':q' => (int)$d['quantity'], 
        ':up' => $unitPrice
      ]);
      $sale = $st->fetch();
      
      $res->getBody()->write(json_encode([
        'success' => true,
        'data' => $sale,
        'message' => 'Venta creada exitosamente'
      ]));
      return $res->withHeader('Content-Type','application/json')->withStatus(201);
    } catch (PDOException $e) {
      $res->getBody()->write(json_encode([
        'success' => false,
        'error' => 'Error al crear venta',
        'message' => $e->getMessage()
      ]));
      return $res->withHeader('Content-Type','application/json')->withStatus(500);
    }
  }
  
  /**
   * Actualiza una venta existente
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
          'error' => 'ID de venta inválido'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(400);
      }
      
      // Verificar si la venta existe
      $checkSt = Database::conn()->prepare("SELECT id FROM sales WHERE id = :id");
      $checkSt->execute([':id' => $id]);
      if (!$checkSt->fetch()) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'Venta no encontrada'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(404);
      }
      
      // Validaciones
      if (isset($d['user_id'])) {
        if (!is_numeric($d['user_id']) || $d['user_id'] <= 0) {
          $res->getBody()->write(json_encode([
            'success' => false,
            'error' => 'ID de usuario inválido'
          ]));
          return $res->withHeader('Content-Type','application/json')->withStatus(400);
        }
        
        $userSt = Database::conn()->prepare("SELECT id FROM users WHERE id = :id");
        $userSt->execute([':id' => $d['user_id']]);
        if (!$userSt->fetch()) {
          $res->getBody()->write(json_encode([
            'success' => false,
            'error' => 'Usuario no encontrado'
          ]));
          return $res->withHeader('Content-Type','application/json')->withStatus(404);
        }
      }
      
      if (isset($d['product_id'])) {
        if (!is_numeric($d['product_id']) || $d['product_id'] <= 0) {
          $res->getBody()->write(json_encode([
            'success' => false,
            'error' => 'ID de producto inválido'
          ]));
          return $res->withHeader('Content-Type','application/json')->withStatus(400);
        }
        
        $productSt = Database::conn()->prepare("SELECT id FROM products WHERE id = :id");
        $productSt->execute([':id' => $d['product_id']]);
        if (!$productSt->fetch()) {
          $res->getBody()->write(json_encode([
            'success' => false,
            'error' => 'Producto no encontrado'
          ]));
          return $res->withHeader('Content-Type','application/json')->withStatus(404);
        }
      }
      
      if (isset($d['quantity']) && (!is_numeric($d['quantity']) || $d['quantity'] <= 0)) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'La cantidad debe ser un número positivo'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(400);
      }
      
      if (isset($d['unit_price']) && (!is_numeric($d['unit_price']) || $d['unit_price'] < 0)) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'El precio unitario debe ser mayor o igual a cero'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(400);
      }
      
      // Construir query dinámicamente
      $updates = [];
      $params = [':id' => $id];
      
      if (isset($d['user_id'])) {
        $updates[] = "user_id = :user_id";
        $params[':user_id'] = (int)$d['user_id'];
      }
      
      if (isset($d['product_id'])) {
        $updates[] = "product_id = :product_id";
        $params[':product_id'] = (int)$d['product_id'];
      }
      
      if (isset($d['quantity'])) {
        $updates[] = "quantity = :quantity";
        $params[':quantity'] = (int)$d['quantity'];
      }
      
      if (isset($d['unit_price'])) {
        $updates[] = "unit_price = :unit_price";
        $params[':unit_price'] = (float)$d['unit_price'];
      }
      
      if (empty($updates)) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'No hay datos para actualizar'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(400);
      }
      
      $sql = "UPDATE sales SET " . implode(', ', $updates) . " WHERE id = :id RETURNING id,user_id,product_id,quantity,unit_price,total,created_at";
      $st = Database::conn()->prepare($sql);
      $st->execute($params);
      $sale = $st->fetch();
      
      $res->getBody()->write(json_encode([
        'success' => true,
        'data' => $sale,
        'message' => 'Venta actualizada exitosamente'
      ]));
      return $res->withHeader('Content-Type','application/json');
    } catch (PDOException $e) {
      $res->getBody()->write(json_encode([
        'success' => false,
        'error' => 'Error al actualizar venta',
        'message' => $e->getMessage()
      ]));
      return $res->withHeader('Content-Type','application/json')->withStatus(500);
    }
  }
  
  /**
   * Elimina una venta
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
          'error' => 'ID de venta inválido'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(400);
      }
      
      // Verificar si la venta existe
      $checkSt = Database::conn()->prepare("SELECT id FROM sales WHERE id = :id");
      $checkSt->execute([':id' => $id]);
      if (!$checkSt->fetch()) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'Venta no encontrada'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(404);
      }
      
      $st = Database::conn()->prepare("DELETE FROM sales WHERE id = :id");
      $st->execute([':id' => $id]);
      
      $res->getBody()->write(json_encode([
        'success' => true,
        'message' => 'Venta eliminada exitosamente'
      ]));
      return $res->withHeader('Content-Type','application/json');
    } catch (PDOException $e) {
      $res->getBody()->write(json_encode([
        'success' => false,
        'error' => 'Error al eliminar venta',
        'message' => $e->getMessage()
      ]));
      return $res->withHeader('Content-Type','application/json')->withStatus(500);
    }
  }
}
