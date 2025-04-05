<?php
class Database {
    private $host = DB_HOST;
    private $port = DB_PORT;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    
    private $dbh;
    private $stmt;
    private $error;
    
    public function __construct() {
        // Configurar DSN
        $dsn = 'mysql:host=' . $this->host . ';port=' . $this->port . ';dbname=' . $this->dbname . ';charset=utf8mb4';
        
        // Configurar opciones de PDO
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        );
        
        // Crear instancia de PDO
        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Error de conexión a la base de datos: " . $this->error);
            echo "Error de conexión a la base de datos. Por favor, contacte al administrador.";
            exit;
        }
    }
    
    // Preparar la consulta
    public function query($sql) {
        $this->stmt = $this->dbh->prepare($sql);
        return $this;
    }
    
    // Vincular valores
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
    
    // Ejecutar la consulta
    public function execute() {
        return $this->stmt->execute();
    }
    
    // Obtener múltiples registros
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll();
    }
    
    // Obtener un solo registro
    public function single() {
        $this->execute();
        return $this->stmt->fetch();
    }
    
    // Obtener el número de filas afectadas
    public function rowCount() {
        return $this->stmt->rowCount();
    }
    
    // Obtener el último ID insertado
    public function lastInsertId() {
        return $this->dbh->lastInsertId();
    }
    
    // Transacciones
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