<?php

namespace objects\table;

final class DatabaseManager {

    public $last_query_error = "";
    private $last_sql_query = "";
    private $db_name = "";
    private $db_password = "";
    private $db_host = "";
    private $db_user = "";
    private $db_charset = 'UTF8';
    public $pdo = null;
    private static $DatabaseManager = null;
    public static  $Stmt = null;

    private function __construct() {

        if (file_exists("db.inc"))
            include_once "db.inc";
        if (file_exists("./db.inc"))
            include_once "./db.inc";
        if (file_exists("../db.inc"))
            include_once "../db.inc";

        global $db_config;

        if (is_array($db_config)) {
            $this->db_host = $db_config['HOST'];
            $this->db_name = $db_config['DB'];
            $this->db_password = $db_config['PASSWORD'];
            $this->db_user = $db_config['USER'];

            $dsn = "mysql:host={$this->db_host};dbname={$this->db_name};charset={$this->db_charset}";

            //\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            $opt = [
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => true,
                \PDO::ATTR_PERSISTENT => true
            ];

            try {
                $this->pdo = new \PDO($dsn, $this->db_user, $this->db_password, $opt);
            } catch (\PDOException $e) {
                die("Не удалось подсоединиться к базе данных {$this->db_host}:{$this->db_name}. \n" . $e->getMessage());
            }
        }
    }

    private function __clone() {
        
    }

    public function __set($name, $value) {

        if ($name == "DB_NAME")
            $this->db_name = $value;
        else if ($name == "DB_HOST")
            $this->db_host = $value;
        else if ($name == "DB_PASSWORD")
            $this->db_password = $value;
        else if ($name == "DB_USER")
            $this->db_user = $value;
        else if ($name == "DB_CHARSET")
            $this->db_charset = $value;
        else
            throw new \Exception("Параметр не известен: {$name}\n.");
    }

    public static function Instance() {
        if (self::$DatabaseManager == null) {
            self::$DatabaseManager = new DatabaseManager();
            //DatabaseManager::Execute("SET NAMES 'utf8'");
        }

        return self::$DatabaseManager;
    }
    
    public static function QueryResult()
    {
        return DatabaseManager::$Stmt;
    }
    
    public static function QueryResultAsRows($Stmt = null)
    {
        return  $Stmt == null ? DatabaseManager::$Stmt->fetchAll(\PDO::FETCH_ASSOC) : $Stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public static function QueryResultRow($Stmt = null)
    {
        return $Stmt == null ? DatabaseManager::$Stmt->fetch(\PDO::FETCH_ASSOC) : $Stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public static function PreparedExecute($sql, $params = null) {

        $exeutedOk = true;
        DatabaseManager::Instance()->last_query_error = "";
        DatabaseManager::Instance()->last_sql_query = $sql;

        try {

            $stmt = DatabaseManager::Instance()->pdo->prepare($sql);
            
            $iscorrectStmt = get_class($stmt) == "PDOStatement";
            
            if ($iscorrectStmt) {
                $stmt->execute($params);
            } else 
            {
                throw new \PDOException ("Не удалось подготовить запрос: {$sql}.");
            }
        } catch (\PDOException $e) {
            $exeutedOk = false;
            DatabaseManager::Instance()->last_query_error = $e->getMessage();
            die("Error execure SQL: {$sql}<br><h3>".DatabaseManager::Instance()->last_query_error)."</h3><hr>";
        }

        if ($iscorrectStmt && $stmt->errorCode() > 0) {
            $err = $stmt->errorInfo();
            DatabaseManager::Instance()->last_query_error = $err[2];
            $exeutedOk = false;
        }
        
        DatabaseManager::$Stmt = $stmt;

        return $exeutedOk;
    }

    public static function Execute($sql) {

        $exeutedOk = true;
        DatabaseManager::Instance()->last_query_error = "";
        DatabaseManager::Instance()->last_sql_query = $sql;

        try {

            $stmt = DatabaseManager::Instance()->pdo->query($sql);
            
            $iscorrectStmt = get_class($stmt) == "PDOStatement";

            $lastErrorCode = DatabaseManager::Instance()->pdo->errorCode();
            
            if ($stmt == FALSE && $lastErrorCode != "00000") {
                $lastError = DatabaseManager::Instance()->pdo->errorInfo();
                // ошибка запроса
                DatabaseManager::Instance()->last_query_error = $lastError[2];
                throw new \PDOException(DatabaseManager::Instance()->last_query_error);
                $exeutedOk = false;
            }
        } catch (\PDOException $e) {
            $exeutedOk = false;
            DatabaseManager::Instance()->last_query_error = $e->getMessage();
            die("Error execure SQL: {$sql}<br><h3>".DatabaseManager::Instance()->last_query_error)."</h3><hr>";
        }
        
        $iscorrectStmt = get_class($stmt) == "PDOStatement";

        if ($iscorrectStmt && $stmt->errorCode() > 0) {
            $err = $stmt->errorInfo();
            DatabaseManager::Instance()->last_query_error = $err[2];
            $exeutedOk = false;
        }
        
        DatabaseManager::$Stmt = $stmt;

        return $exeutedOk;
    }

    public static function CreateTable($table_name, $table_cols, $table_engine = '', $code_page = '') {
        $table_name = strtolower($table_name);

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (";
        $keys = "";
        if (is_array($table_cols)) {
            foreach ($table_cols as $k => $v) {
                $col_name = strtoupper($k);
                
                list($col_type, $col_size, $def_value, $not_null, $key_field) = $v;
                $col_size1 = $col_size;
                $col_size = ($col_size != '') ? "({$col_size})" : "";
                $not_null = (true == $not_null) ? "NOT NULL" : "";
                $defaul = ($def_value != '') ? "default '" . $def_value . "'" : "";
                if (strtoupper($col_type) == 'INT')
                    $sql .= $col_name . " INT{$col_size} {$not_null} {$defaul},";
                if (strtoupper($col_type) == 'VARCHAR')
                    $sql .= $col_name . " VARCHAR{$col_size} {$not_null} {$defaul},";
                if (strtoupper($col_type) == 'TEXT')
                    $sql .= $col_name . " TEXT {$not_null} ,";
                if (strtoupper($col_type) == 'FLOAT') {
                    
                    $col_size1 = $col_size1 == "" ? 4 : $col_size1;
                    
                    $sql .= $col_name . " DECIMAL ({$col_size1},2) {$not_null} {$defaul},";
                }
                if (strtoupper($col_type) == 'BOOL')
                    $sql .= $col_name . " TINYINT(4) {$not_null} {$defaul},";
                if (strtoupper($col_type) == 'DATE')
                    $sql .= $col_name . " DATE {$not_null} {$defaul},";
                if (strtoupper($col_type) == 'TIME')
                    $sql .= $col_name . " TIME {$not_null} {$defaul},";
                if (strtoupper($col_type) == 'DATETIME')
                    $sql .= $col_name . " DATETIME {$not_null} {$defaul},";
                if (strtoupper($col_type) == 'AUTO') {
                    $sql .= $col_name . " INT(11) NOT NULL auto_increment,";
                    $keys .= " PRIMARY KEY  ({$col_name}),";
                }
                if (true == $key_field) // ��� ���� ��������
                    $keys .= " KEY $col_name ({$col_name}),";
            }
        }
        
        $engine = ($table_engine == '') ? 'InnoDB' : $table_engine;
        $charset = ($code_page == '') ? 'utf8' : $code_page;
        $sql = trim($sql);
        $keys = trim($keys);
        
        // ������ ��������� ������ "," - ���� �� ������������
        $sql = ($sql[strlen($sql) - 1] == ',') ? substr($sql, 0, strlen($sql) - 1) : $sql;
        $keys = ($keys[strlen($keys) - 1] == ',') ? substr($keys, 0, strlen($keys) - 1) : $keys;
        
        if ($keys != '')
            $keys = ", " . $keys;
        
        $sql .= $keys . ") ENGINE={$engine} DEFAULT CHARSET={$charset};";

        return DatabaseManager::Execute($sql);
    }

    public static function DropTables($tables_array) {

        $no_errors = true;
        if (is_array($tables_array)) {
            foreach ($tables_array as $k => $Table) {
                $no_errors = DatabaseManager::Execute("DROP TABLE IF EXISTS {$Table}");
            }
            
            DatabaseManager::Execute("COMMIT;");
        }

        return $no_errors;
    }

    public static function LastQuery() {
        return DatabaseManager::Instance()->last_sql_query;
    }

    public static function LastError() {

        $err = DatabaseManager::$DatabaseManager->last_query_error;

        if ($err == "") {
            $lastError = DatabaseManager::Instance()->pdo->errorInfo();
            return is_array($lastError) ? $lastError[2] : "";
        }

        return $err;
    }
    
    public static function TableRowsCount($tableName)
    {
        DatabaseManager::Instance()->Execute("select COUNT(*) as CNT from {$tableName}");
        $row = DatabaseManager::QueryResultRow();
        return $row['CNT'];
    }

    public static function LastInsertID() {
        return DatabaseManager::$DatabaseManager->pdo->lastInsertId();
    }

    public static function EscapeString($str) {
        return DatabaseManager::$DatabaseManager->pdo->quote($str);
    }

    public static function LockTables($tableslist) {
        $rezult = false;

        if (is_array($tableslist)) {
            $tables_locks_list = "";
            foreach ($tableslist as $table_name => $lock_type) {
                $tables_locks_list .= strtolower($table_name) . " " . strtoupper($lock_type) . ", ";
            }
            $tables_locks_list = trim($tables_locks_list);
            $tables_locks_list = substr($tables_locks_list, 0, strlen($tables_locks_list) - 1);

            $lock_sql = "LOCK TABLES " . $tables_locks_list;
            $rezult = DatabaseManager::Instance()->Execute($lock_sql);
        }
        return $rezult;
    }

    public static function UnLockTables() {
        DatabaseManager::Instance()->Execute("UNLOCK TABLES");
    }

    public static function CreateBuilderQuery() {
        return new QueryBuilder();
    }

}
