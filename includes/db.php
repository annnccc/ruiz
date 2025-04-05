<?php
/**
 * Archivo de conexión a la base de datos
 * Gestiona las conexiones PDO y proporciona funciones para interactuar con la base de datos
 */

// Prevenir acceso directo al archivo
if (!defined('BASE_URL')) {
    http_response_code(403);
    exit('Acceso prohibido');
}

/**
 * Función para obtener una instancia de conexión a la base de datos
 * Utiliza la clase Database para mantener una única instancia
 * 
 * @return PDO Instancia de conexión PDO
 */
function getDB() {
    global $db;
    
    if ($db instanceof PDO) {
        return $db;
    } else if ($db instanceof Database) {
        return $db->getConnection();
    } else {
        // Si no hay instancia creada, crear una nueva conexión
        static $pdoInstance = null;
        
        if ($pdoInstance === null) {
            try {
                $dsn = 'mysql:host=' . DB_HOST . ';port=' . (defined('DB_PORT') ? DB_PORT : '3306') . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                $options = [
                    PDO::ATTR_PERSISTENT => true,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ];
                
                $pdoInstance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                error_log("Error de conexión a la base de datos: " . $e->getMessage());
                die("Error de conexión a la base de datos. Por favor, contacte al administrador.");
            }
        }
        
        return $pdoInstance;
    }
}

/**
 * Función para escapar y sanitizar entradas (XSS prevention)
 * 
 * @param string $data Datos a sanitizar
 * @return string Datos sanitizados
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Función para generar un número único de expediente para pacientes
 * 
 * @return string Número de expediente único
 */
function generarNumExpediente() {
    $fecha = date('Ymd');
    $aleatorio = mt_rand(1000, 9999);
    return 'EXP-' . $fecha . '-' . $aleatorio;
}

// Clase para manejar la conexión a la base de datos
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    private $port = DB_PORT ?? '3306';
    
    private $dbh;
    private $stmt;
    private $error;
    
    public function __construct() {
        $dsn = 'mysql:host=' . $this->host . ';port=' . $this->port . ';dbname=' . $this->dbname . ';charset=utf8mb4';
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        );
        
        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Error de conexión a la base de datos: " . $this->error);
            echo "Error de conexión a la base de datos. Por favor, contacte al administrador.";
            exit;
        }
    }
    
    /**
     * Obtener la conexión PDO subyacente
     * @return PDO Instancia de PDO
     */
    public function getConnection() {
        return $this->dbh;
    }
    
    public function query($sql) {
        $this->stmt = $this->dbh->prepare($sql);
        return $this;
    }
    
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
        return $this;
    }
    
    public function execute() {
        return $this->stmt->execute();
    }
    
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function single() {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function rowCount() {
        return $this->stmt->rowCount();
    }
    
    public function lastInsertId() {
        return $this->dbh->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->dbh->beginTransaction();
    }
    
    public function commit() {
        return $this->dbh->commit();
    }
    
    public function rollBack() {
        return $this->dbh->rollBack();
    }
    
    // Verificar si la conexión está activa
    public function isConnected() {
        return ($this->dbh !== null);
    }
}

// Instancia de la base de datos
$db = new Database(); 