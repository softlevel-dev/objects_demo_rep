<?php

namespace objects\table;

class QueryBuilder {

    private $table_operation = "";
    private $table_name = "";
    private $order_fields = "";
    private $table_fields = "*";
    private $set_table_fields = null;
    private $insert_table_fields = "";
    private $insert_table_values = "";
    private $table_where_condition = "";
    private $lastQuery = null;
    private $table_limits_start = 0;
    private $table_limits_count = 0;
    private $last_value = 0;
    private $last_sql = "";
    private $left_join = "";
    private $left_join_table = "";
    private $left_join_fields = "";
    private $join_table = "";
    private $join_fields = "";
    private $max_field = null;

    public function Lock($tables) {

        $wa = false;
        $ra = false;
        if (array_key_exists("read", $tables)) {
            if (is_array($tables["read"])) {
                $ra = true;
                foreach ($tables["read"] as $tbl) {
                    $lock_tables_array[$tbl] = "read";
                }
            }
        }

        if (array_key_exists("write", $tables)) {
            if (is_array($tables["write"])) {
                $wa = true;
                foreach ($tables["write"] as $tbl) {
                    $lock_tables_array[$tbl] = "write";
                }
            }
        }

        if (!$wa && !$ra)
            $lock_tables_array = $tables;

        DatabaseManager::LockTables($lock_tables_array);

        $this->table_operation = "";
        $this->order_fields = "";
        $this->table_fields = "*";
        $this->set_table_fields = null;
        $this->insert_table_fields = "";
        $this->insert_table_values = "";
        $this->table_where_condition = "";
        $this->lastQuery = null;
        $this->table_limits_start = 0;
        $this->table_limits_count = 0;
        $this->last_value = 0;
        $this->left_join_table = "";
        $this->left_join_fields = null;
        $this->join_table = "";
        $this->join_fields = null;
        $this->max_field = null;
        $this->left_join = [];

        return $this;
    }

    public function Unlock() {
        DatabaseManager::UnLockTables();
        return $this;
    }

    public function Join($table_name, $fields) {
        $this->join_table = $table_name;
        $this->join_fields = $fields;
        $this->left_join_table = "";
        $this->left_join_fields = null;
        return $this;
    }

    public function LeftJoin($table_name, $fields) {
        $this->left_join_table = $table_name;
        $this->left_join_fields = $fields;
        $this->join_table = "";
        $this->join_fields = null;

        $join["left_join_table"] = $table_name;
        $join["left_join_fields"] = $fields;

        $this->left_join[] = $join;

        return $this;
    }
    
    public function RowsCount($table_name)
    {
        $this->table_fields = "";
        $this->table_name = $table_name;
        $this->table_operation = "select count(*) as CNT";
        return $this;
    }

    public function Get($table_name) {
        $this->table_name = $table_name;
        $this->table_operation = "select";
        return $this;
    }

    public function Insert($table_name) { //->FieldValues([],$olduser)->Run();
        $this->table_name = $table_name;
        $this->table_operation = "insert into";
        return $this;
    }

    public function Drop($table_name) {
        $this->table_name = $table_name;
        $this->table_operation = "delete from";
        return $this;
    }

    public function Update($table_name) {
        $this->table_name = $table_name;
        $this->table_operation = "update";
        return $this;
    }

    //->Fields(["VIEWS"=>"+1")->Where("ID={$advid}")->Run()->Unlock();

    public function FieldValues($fields_array, $data_array) {
        $this->insert_table_fields = "";
        $this->insert_table_values = "";
        if (is_array($data_array) && is_array($fields_array)) {
            foreach ($data_array as $field_name => $field_value) {
                if (array_key_exists($field_name, $fields_array)) {
                    $this->insert_table_fields .= "{$fields_array[$field_name]},";
                    $this->insert_table_values .= "'{$field_value}',";
                }
            }

            $this->insert_table_fields = substr($this->insert_table_fields, 0, strlen($this->insert_table_fields) - 1);
            $this->insert_table_values = substr($this->insert_table_values, 0, strlen($this->insert_table_values) - 1);
        }

        return $this;
    }

    public function Order($arr) {
        $this->order_fields = $arr;
        return $this;
    }

    public function Limits($start_index, $count) {
        $this->table_limits_start = $start_index;
        $this->table_limits_count = $count;
        return $this;
    }

    public function Fields($fields_list) {
        $this->table_fields = $fields_list;
        return $this;
    }

    public function Set($set_fields_array) {
        $this->set_table_fields = $set_fields_array;
        return $this;
    }

    public function Where($table_where) {
        $this->table_where_condition = $table_where;
        return $this;
    }

    public function Run() {
        
        if (strpos($this->table_operation, "select")===0) {
            if (DatabaseManager::Execute($this->last_sql)) {
                $this->lastQuery = DatabaseManager::QueryResult();
            }
        }

        if ($this->table_operation == "insert into") {

            if (DatabaseManager::Execute($this->last_sql)) {
                $this->lastQuery = DatabaseManager::QueryResult();
                $this->last_inserted_id = DatabaseManager::LastInsertID();
            }
        }

        if ($this->table_operation == "delete from") {
            if (DatabaseManager::Execute($this->last_sql)) {
                $this->lastQuery = DatabaseManager::QueryResult();
            }
        }

        if ($this->table_operation == "update") {
            if (DatabaseManager::Execute($this->last_sql)) {
                $this->lastQuery = DatabaseManager::QueryResult();
            }
        }

        return $this;
    }

    public function Build() {
        $sql = $this->table_operation;
        
        if (strpos($this->table_operation, "select")!==FALSE) {
            
            if (is_array($this->max_field)) {
                $fields_list = "";
                foreach ($this->max_field as $fld => $fld_alias) {
                    $fields_list .= " MAX({$fld}) as {$fld_alias}, ";
                }
                $fields_list = trim($fields_list);
                $fields_list = substr($fields_list, 0, strlen($fields_list) - 1);

                $sql .= ($this->table_name != "") ? " {$fields_list} from " . $this->table_name : "";
            } else {
                if (strpos($this->table_operation, "select count")!==FALSE) 
                {
                    $sql .= ($this->table_name != "") ? " from " . $this->table_name : "";
                } else
                $sql .= ($this->table_name != "") ? " {$this->table_fields} from " . $this->table_name : "";
            }

            if (is_array($this->left_join) && sizeof($this->left_join) > 0) {
                foreach ($this->left_join as $joindata) {
                    
                    $this->left_join_table = $joindata["left_join_table"];
                    $this->left_join_fields = $joindata["left_join_fields"];

                    if ($this->left_join_table != "" && is_array($this->left_join_fields)) {
                        $sql .= " left join " . $this->left_join_table . " on ";
                        foreach ($this->left_join_fields as $fsrc => $fdest) {
                            $sql .= " ({$this->table_name}.{$fsrc} = {$this->left_join_table}.{$fdest}) ";
                        }
                    }
                }
            }

            if ($this->join_table != "" && is_array($this->join_fields)) {
                $sql .= " join " . $this->join_table . " on ";
                foreach ($this->join_fields as $fsrc => $fdest) {
                    $sql .= " {$this->table_name}.{$fsrc} = {$this->join_table}.{$fdest} ";
                }
            }

            $sql .= ($this->table_where_condition != "") ? " where " . $this->table_where_condition : "";

            if (is_array($this->order_fields) && sizeof($this->order_fields) > 0) {
                $sql .= " ORDER BY ";
                foreach ($this->order_fields as $key => $value) {
                    $sql .= " {$key} {$value},";
                }

                $sql = substr($sql, 0, strlen($sql) - 1);
            }

            if ($this->table_limits_start >= 0 and $this->table_limits_count > 0) {
                $sql .= " limit {$this->table_limits_start}, {$this->table_limits_count}";
            }

            // $this->lastQuery = $this->CreateQuery($sql);
        }

        if ($this->table_operation == "insert into") {
            $sql .= ($this->table_name != "") ? " {$this->table_name} ({$this->insert_table_fields}) values ({$this->insert_table_values}) " : "";
            //$this->lastQuery = $this->CreateQuery($sql);
            //$this->last_inserted_id = $this->LastInsertedId();
        }

        if ($this->table_operation == "delete from") {
            $sql .= ($this->table_name != "") ? " {$this->table_name} " : "";
            $sql .= ($this->table_where_condition != "") ? " where " . $this->table_where_condition : "";
            //$this->lastQuery = $this->CreateQuery($sql);
        }

        if ($this->table_operation == "update") {
            if (is_array($this->set_table_fields) && sizeof($this->set_table_fields) > 0) {
                $set_fields = "";
                foreach ($this->set_table_fields as $key => $value) {
                    //print $value.",";
                    if ($value === "+1")
                        $set_fields .= "{$key}={$key}+1,";
                    else if ($value === "-1")
                        $set_fields .= "{$key}={$key}-1,";
                    else
                        $set_fields .= "{$key}='{$value}',";
                }

                $set_fields = substr($set_fields, 0, strlen($set_fields) - 1);

                $sql .= ($this->table_name != "") ? " {$this->table_name}  set {$set_fields} " : "";
                $sql .= ($this->table_where_condition != "") ? " where " . $this->table_where_condition : "";
                //$this->lastQuery = $this->CreateQuery($sql);
            }
        }

        $this->last_sql = $sql;

        return $this;
    }

    public function LastQuery() {
        return $this->last_sql;
    }

    public function Max($field) {
        $this->max_field = $field;
        return $this;
    }

    public function Row() {
        return DatabaseManager::QueryResultRow($this->lastQuery);
    }

    public function Rows() {
        return DatabaseManager::QueryResultAsRows($this->lastQuery);
    }

    public function Count() {
        return  \sizeof($this->Rows());
    }

    public function Value() {
        return $this->last_value;
    }

    public function Result() {
        
        if ($this->table_operation == "select count(*) as CNT")
        {
            $row = $this->Row();
            return intval($row['CNT']);
        } else
        {
            return trim(DatabaseManager::LastError()) == "";
        }
    }

    public function LastNewId() {
        return $this->last_inserted_id;
    }

}
