<?php
namespace App;

use PDO;

class Database {
  private static ?PDO $pdo = null;

  public static function conn(): PDO {
    if (!self::$pdo) {
      $host = getenv('DB_HOST') ?: 'localhost';
      $port = getenv('DB_PORT') ?: '5432';
      $db   = getenv('DB_NAME') ?: 'store';
      $user = getenv('DB_USER') ?: 'admin';
      $pass = getenv('DB_PASS') ?: 'admin';

      $dsn = "pgsql:host=$host;port=$port;dbname=$db";
      self::$pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      ]);
    }
    return self::$pdo;
  }
}
