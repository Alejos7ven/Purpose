<?php
/**
 * Clase de Conexión a la Base de Datos
 * Lee las credenciales desde el archivo .env en la raíz del proyecto.
 * Utiliza PDO con consultas preparadas y modo estricto de errores.
 */
class Database {

    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset;
    private $conn = null;

    public function __construct() {
        $this->loadEnv();
    }

    /**
     * Lee y parsea el archivo .env ubicado en la raíz del proyecto.
     */
    private function loadEnv(): void {
        $envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';

        if (!file_exists($envPath) || !is_readable($envPath)) {
            throw new RuntimeException(
                "Archivo .env no encontrado o sin permisos de lectura en: {$envPath}"
            );
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);
            if (str_starts_with($line, '#') || $line === '') {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);

            if (preg_match('/^(["\'])(.+)\1$/', $value, $matches)) {
                $value = $matches[2];
            }

            if (!isset($_ENV[$key])) {
                $_ENV[$key]    = $value;
                $_SERVER[$key] = $value;
                putenv("{$key}={$value}");
            }
        }

        $this->host     = $_ENV['DB_HOST']    ?? 'localhost';
        $this->db_name  = $_ENV['DB_NAME']    ?? 'proposito';
        $this->username = $_ENV['DB_USER']    ?? 'root';
        $this->password = $_ENV['DB_PASS']    ?? '';
        $this->charset  = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
    }

    /**
     * Obtiene la conexión PDO.
     */
    public function getConnection(): ?PDO {
        if ($this->conn !== null) {
            return $this->conn;
        }

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);

        } catch (PDOException $e) {
            error_log("Error de conexión a la Base de Datos: " . $e->getMessage());
            $this->conn = null;
        }

        return $this->conn;
    }
}
