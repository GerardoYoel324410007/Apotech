<?php
declare(strict_types=1);

// ─── Database Configuration ───────────────────────────────────────────────────
class Database {
    private static ?self $instance = null;
    private mysqli $conn;

    private function __construct() {
        $this->conn = new mysqli(
            getenv('DB_HOST') ?: 'localhost',
            getenv('DB_USER') ?: 'root',
            getenv('DB_PASS') ?: '',
            getenv('DB_NAME') ?: 'apotech_db'
        );
        if ($this->conn->connect_error) {
            error_log("DB Error: " . $this->conn->connect_error);
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Database tidak tersedia']));
        }
        $this->conn->set_charset("utf8mb4");
    }

    public static function getInstance(): self {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    public function getConnection(): mysqli {
        return $this->conn;
    }
}

$conn = Database::getInstance()->getConnection();

// ─── Helper Functions ─────────────────────────────────────────────────────────
function jsonResponse(bool $success, string $message = '', array $data = []): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit();
}

function sanitize(mixed $data): mixed {
    if ($data === null) return null;
    if (is_array($data)) return array_map('sanitize', $data);
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function formatRupiah(float $amount): string {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Base URL for assets (Dynamic detection)
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = str_replace('\\', '/', dirname($script));
    $path = ($dir === '/' || $dir === '.') ? '/' : rtrim($dir, '/') . '/';
    define('BASE_URL', $protocol . $host . $path);
}
define('BASE_PATH', dirname(__DIR__));
?>