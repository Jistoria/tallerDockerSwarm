<?php
namespace App;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDOException;

/**
 * UserController - Maneja las operaciones CRUD para usuarios
 * 
 * Endpoints disponibles:
 * - GET /api/users - Lista todos los usuarios
 * - GET /api/users/{id} - Obtiene un usuario específico por ID
 * - POST /api/users - Crea un nuevo usuario
 * - PUT /api/users/{id} - Actualiza un usuario existente
 * - DELETE /api/users/{id} - Elimina un usuario
 */
class UserController {
  
  /**
   * Lista todos los usuarios
   * 
   * @param Request $req
   * @param Response $res
   * @return Response
   */
  public function list(Request $req, Response $res): Response {
    try {
      $q = Database::conn()->query("SELECT id,name,email,created_at FROM users ORDER BY id");
      $users = $q->fetchAll();
      
      $res->getBody()->write(json_encode([
        'success' => true,
        'data' => $users,
        'count' => count($users)
      ]));
      return $res->withHeader('Content-Type','application/json');
    } catch (PDOException $e) {
      $res->getBody()->write(json_encode([
        'success' => false,
        'error' => 'Error al obtener usuarios',
        'message' => $e->getMessage()
      ]));
      return $res->withHeader('Content-Type','application/json')->withStatus(500);
    }
  }
  
  /**
   * Obtiene un usuario específico por ID
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
          'error' => 'ID de usuario inválido'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(400);
      }
      
      $st = Database::conn()->prepare("SELECT id,name,email,created_at FROM users WHERE id = :id");
      $st->execute([':id' => $id]);
      $user = $st->fetch();
      
      if (!$user) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'Usuario no encontrado'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(404);
      }
      
      $res->getBody()->write(json_encode([
        'success' => true,
        'data' => $user
      ]));
      return $res->withHeader('Content-Type','application/json');
    } catch (PDOException $e) {
      $res->getBody()->write(json_encode([
        'success' => false,
        'error' => 'Error al obtener usuario',
        'message' => $e->getMessage()
      ]));
      return $res->withHeader('Content-Type','application/json')->withStatus(500);
    }
  }
  
  /**
   * Crea un nuevo usuario
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
          'error' => 'El nombre es requerido'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(400);
      }
      
      if (!isset($d['email']) || empty(trim($d['email']))) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'El email es requerido'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(400);
      }
      
      if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'El formato del email es inválido'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(400);
      }
      
      $st = Database::conn()->prepare("INSERT INTO users(name,email) VALUES(:n,:e) RETURNING id,name,email,created_at");
      $st->execute([':n' => trim($d['name']), ':e' => strtolower(trim($d['email']))]);
      $user = $st->fetch();
      
      $res->getBody()->write(json_encode([
        'success' => true,
        'data' => $user,
        'message' => 'Usuario creado exitosamente'
      ]));
      return $res->withHeader('Content-Type','application/json')->withStatus(201);
    } catch (PDOException $e) {
      if (strpos($e->getMessage(), 'users_email_key') !== false) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'El email ya está en uso'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(409);
      }
      
      $res->getBody()->write(json_encode([
        'success' => false,
        'error' => 'Error al crear usuario',
        'message' => $e->getMessage()
      ]));
      return $res->withHeader('Content-Type','application/json')->withStatus(500);
    }
  }
  
  /**
   * Actualiza un usuario existente
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
          'error' => 'ID de usuario inválido'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(400);
      }
      
      // Verificar si el usuario existe
      $checkSt = Database::conn()->prepare("SELECT id FROM users WHERE id = :id");
      $checkSt->execute([':id' => $id]);
      if (!$checkSt->fetch()) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'Usuario no encontrado'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(404);
      }
      
      // Validaciones
      if (isset($d['name']) && empty(trim($d['name']))) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'El nombre no puede estar vacío'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(400);
      }
      
      if (isset($d['email']) && (!empty(trim($d['email'])) && !filter_var($d['email'], FILTER_VALIDATE_EMAIL))) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'El formato del email es inválido'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(400);
      }
      
      // Construir query dinámicamente
      $updates = [];
      $params = [':id' => $id];
      
      if (isset($d['name'])) {
        $updates[] = "name = :name";
        $params[':name'] = trim($d['name']);
      }
      
      if (isset($d['email'])) {
        $updates[] = "email = :email";
        $params[':email'] = strtolower(trim($d['email']));
      }
      
      if (empty($updates)) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'No hay datos para actualizar'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(400);
      }
      
      $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id RETURNING id,name,email,created_at";
      $st = Database::conn()->prepare($sql);
      $st->execute($params);
      $user = $st->fetch();
      
      $res->getBody()->write(json_encode([
        'success' => true,
        'data' => $user,
        'message' => 'Usuario actualizado exitosamente'
      ]));
      return $res->withHeader('Content-Type','application/json');
    } catch (PDOException $e) {
      if (strpos($e->getMessage(), 'users_email_key') !== false) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'El email ya está en uso'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(409);
      }
      
      $res->getBody()->write(json_encode([
        'success' => false,
        'error' => 'Error al actualizar usuario',
        'message' => $e->getMessage()
      ]));
      return $res->withHeader('Content-Type','application/json')->withStatus(500);
    }
  }
  
  /**
   * Elimina un usuario
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
          'error' => 'ID de usuario inválido'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(400);
      }
      
      // Verificar si el usuario existe
      $checkSt = Database::conn()->prepare("SELECT id FROM users WHERE id = :id");
      $checkSt->execute([':id' => $id]);
      if (!$checkSt->fetch()) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'Usuario no encontrado'
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(404);
      }
      
      // Verificar si hay ventas asociadas
      $salesSt = Database::conn()->prepare("SELECT COUNT(*) FROM sales WHERE user_id = :id");
      $salesSt->execute([':id' => $id]);
      $salesCount = $salesSt->fetchColumn();
      
      if ($salesCount > 0) {
        $res->getBody()->write(json_encode([
          'success' => false,
          'error' => 'No se puede eliminar el usuario porque tiene ventas asociadas',
          'sales_count' => $salesCount
        ]));
        return $res->withHeader('Content-Type','application/json')->withStatus(409);
      }
      
      $st = Database::conn()->prepare("DELETE FROM users WHERE id = :id");
      $st->execute([':id' => $id]);
      
      $res->getBody()->write(json_encode([
        'success' => true,
        'message' => 'Usuario eliminado exitosamente'
      ]));
      return $res->withHeader('Content-Type','application/json');
    } catch (PDOException $e) {
      $res->getBody()->write(json_encode([
        'success' => false,
        'error' => 'Error al eliminar usuario',
        'message' => $e->getMessage()
      ]));
      return $res->withHeader('Content-Type','application/json')->withStatus(500);
    }
  }
}
