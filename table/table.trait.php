<?php

namespace objects\table;

trait table {

    protected $datarows = null;
    protected $name = "";
    protected $db = null;
    public $PageNumber = 1;
    protected $TotalPages = 0;
    public $ItemsPerPage = 15;
    protected $rows_count = 0;
    private $show_paginator = true;
    protected $options = null;
    protected $isview = false;
    
    public function IsView($val)
    {
        $this->isview = $val;
    }
    
    public function ItemsPerPage()
    {
        return $this->PageNumber;
    }
   
    function InitTable($tableName, $options) {
        $this->name = $tableName;
        $this->options = $options;
        $this->db = \objects\table\DatabaseManager::Instance();

        $this->ItemsPerPage = isset($this->options['items_per_page']) ? intval($this->options['items_per_page']) : $this->ItemsPerPage;

        // получаю количество записей и количество страниц в таблице
        $Builder = DatabaseManager::CreateBuilderQuery();
        $this->rows_count = DatabaseManager::TableRowsCount($this->name);
        $this->TotalPages = ceil($this->rows_count / $this->ItemsPerPage);

        // 
        $uri = mb_substr($_SERVER['REQUEST_URI'], 1, strlen($_SERVER['REQUEST_URI']));

        if (strpos($uri, "page_number") > 0) {
            $uripage = mb_substr($uri, strpos($uri, "page_number") + mb_strlen("page_number") + 1);
            $this->PageNumber = intval($uripage);
        }
    }

    function Page($page_number, $items_per_page, $relations = null, $extfields = null, $orders = null) {
        $Builder = DatabaseManager::CreateBuilderQuery();
        $page_index = ($page_number - 1) * $items_per_page;
        $fields_list = "";

        // надо залочить все таблицы которые могут быть в заросе!!!
        $lockTables = ["read" => [$this->name]];
        if (is_array($relations) && sizeof($relations) > 0) {
            foreach ($relations as $key => $value) {
                $lockTables["read"][] = $value['DESTTABLE'];
            }
        }

        $fields_list = "{$this->name}.*";
        
        if ($this->isview)
           $Sql = $Builder->Get($this->name)->Limits($page_index, $items_per_page);
        else
           $Sql = $Builder->Lock($lockTables)->Get($this->name)->Limits($page_index, $items_per_page);
        
        if (is_array($orders) && sizeof($orders)>0)
        {
            $Sql->Order($orders);
        }
        
        if ($orders == null)
        {
            $Sql->Order(["{$this->name}.ID"=>"ASC"]);
        }
        
        if (is_array($relations) && sizeof($relations) > 0) {
            $fields_list = "{$this->name}.*,";
            foreach ($relations as $key => $value) {
                $Sql->LeftJoin($value['DESTTABLE'], [$key => $value['DESTRELFIELD']]);
                $fields_list .= "{$value['DESTTABLE']}.*,";
            }

            $fields_list = mb_substr($fields_list, 0, mb_strlen($fields_list, "UTF8") - 1);
        }

        if (is_array($extfields) && sizeof($extfields) > 0) {
            $fields_list .= (mb_strlen($fields_list, "UTF8") > 0) ? "," : "";
            foreach ($extfields as $key => $value) {
                $fields_list .= "{$key} as {$value},";
            }

            $fields_list = mb_substr($fields_list, 0, mb_strlen($fields_list, "UTF8") - 1);
        }

        if (mb_strlen($fields_list, "UTF8") > 0)
            $Sql->Fields($fields_list);
        
        $this->datarows = $Sql->Build()->Run()->Unlock()->Rows();
        
        if (!$this->isview) $Sql->Unlock();

        return $this->datarows;
    }

    function ToOptionsList($codeField, $captionField, $SelectedID = -1) {
        $Builder = DatabaseManager::CreateBuilderQuery();
        $rows = $Builder->Lock(["read" => [$this->name]])->Get($this->name)->Fields("{$codeField},{$captionField}")->Build()->Run()->Unlock()->Rows();

        $list = "";
        foreach ($rows as $rowdata) {

            $selected = $SelectedID == $rowdata[$codeField] ? "selected" : "";
            $list .= "<option value='{$rowdata[$codeField]}' {$selected}>{$rowdata[$captionField]}</option>";
        }

        return $list;
    }

    function Row($id) {
        $Builder = DatabaseManager::CreateBuilderQuery();
        return $Builder->Lock(["read" => [$this->name]])->Get($this->name)->Where("ID={$id}")->Build()->Run()->Unlock()->Row();
    }

    function InsertEmpty() {
        
    }

    function Insert(TableRow $row, int $index = 999999999) {
        $Builder = DatabaseManager::CreateBuilderQuery();
        $result = $Builder->Lock(["write" => [$this->name]])->Insert($this->name)->FieldValues($row->GetFieldNames(), $row->GetFields())->Build()->Run()->Unlock()->Result();
        return $Builder->LastNewId();
    }

    function Find($condition_fields) {
        
    }

    function Exists() {

        global $DB;
        $numargs = func_num_args();

        if ($numargs < 2)
            return false;

        $fields_to_compare_array = explode(";", func_get_arg(0));

        if (is_array($fields_to_compare_array) && sizeof($fields_to_compare_array) > 0) {
            if (sizeof($fields_to_compare_array) == $numargs - 1) {
                $where = "";
                $i = 1;
                foreach ($fields_to_compare_array as $field_name) {
                    $argvalue = $DB->EscapeString(func_get_arg($i));

                    list($field_name, $opcode) = explode(",", $field_name);

                    $opens = strpos($field_name, "(") !== FALSE;
                    $closeds = strpos($field_name, ")") !== FALSE;

                    $open_skiba = $opens ? "(" : "";
                    $close_skiba = $closeds ? ")" : "";

                    $field_name = str_replace(["(", ")"], "", $field_name);

                    if ($opcode != "")
                        $where .= "{$open_skiba}{$field_name} = '{$argvalue}'{$close_skiba} {$opcode} ";
                    else
                        $where .= "{$open_skiba}{$field_name} = '{$argvalue}'{$close_skiba} and ";
                    $i++;
                }

                $where = mb_substr($where, 0, mb_strlen($where) - 4);

                $Builder = DatabaseManager::CreateBuilderQuery();
                $ret = $Builder->Lock(["read" => [$this->name]])->Get($this->name)->Where($where)->Build()->Run()->Unlock()->Count() > 0;
                return $ret;
            } else {
                throw new \Exception("table.Exists принимает не сопоставимое количество аргументов.");
            }
        }

        return false;
    }

    function RowsCount() {
        $Builder = DatabaseManager::CreateBuilderQuery();
        $count = $Builder->Lock(["read" => [$this->name]])->RowsCount($this->name)->Build()->Run()->Unlock()->Result();
        return $count;
    }

    function ColumnsCount() {
        
    }

    function Delete($condition_fields) {
        
        $Builder = DatabaseManager::CreateBuilderQuery();
        return $Builder->Lock(["write" => [$this->name]])->Drop($this->name)->Where($condition_fields)->Build()->Run()->Unlock()->Result();
    }

    function Update(TableRow $row, $IndexField = "ID") {
        $Builder = DatabaseManager::CreateBuilderQuery();

        $rowid = $row->$IndexField;

        if (intval($rowid) > 0) {
            unset($row->$IndexField);
            $result = $Builder->Lock(["write" => [$this->name]])->Update($this->name)->Set($row->GetFields())->Where("{$IndexField}={$rowid}")->Build()->Run()->Unlock()->Result();
            return $result;
        }

        return false;
    }

    function Filter($condition, $page_number = 1, $items_per_page = 9999999, $relations = null, $extfields = null, $orders = null) {
        $Builder = DatabaseManager::CreateBuilderQuery();
        $page_index = ($page_number - 1) * $items_per_page;
        $fields_list = "";

        // надо залочить все таблицы которые могут быть в заросе!!!
        $lockTables = ["read" => [$this->name]];

        if (is_array($relations)) {
            foreach ($relations as $key => $value) {
                $lockTables["read"][] = $value['DESTTABLE'];
            }
        }

        $fields_list = "{$this->name}.*";
        
        if ($this->isview)
            $Sql = $Builder->Get($this->name)->Limits($page_index, $items_per_page);
        else
            $Sql = $Builder->Lock(["read"=>[$this->name]])->Get($this->name)->Limits($page_index, $items_per_page);
        
        if (is_array($orders) && sizeof($orders)>0)
        {
            $Sql->Order($orders);
        }
        
        if (is_array($relations) && sizeof($relations) > 0) {
            $fields_list = "{$this->name}.*,";
            foreach ($relations as $key => $value) {
                $Sql->LeftJoin($value['DESTTABLE'], [$key => $value['DESTRELFIELD']]);
                $fields_list .= "{$value['DESTTABLE']}.*,";
            }

            $fields_list = mb_substr($fields_list, 0, mb_strlen($fields_list, "UTF8") - 1);
        }

        if (is_array($extfields) && sizeof($extfields) > 0) {
            $fields_list .= (mb_strlen($fields_list, "UTF8") > 0) ? "," : "";
            foreach ($extfields as $key => $value) {
                $fields_list .= "{$key} as {$value},";
            }

            $fields_list = mb_substr($fields_list, 0, mb_strlen($fields_list, "UTF8") - 1);
        }

        if (mb_strlen($fields_list, "UTF8") > 0)
            $Sql->Fields($fields_list);
        
        $this->datarows = $Sql->Where($condition)->Build()->Run()->Rows();
        
        if (!$this->isview) $Sql->Unlock();
        
        $SqlTotal = $Builder->RowsCount($this->name);
        $this->rows_count = $SqlTotal->Where($condition)->Build()->Run()->Unlock()->Result();
        $this->TotalPages = ceil($this->rows_count / $this->ItemsPerPage);

        return $this->datarows;
    }

}
