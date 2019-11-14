<?php

namespace objects\table;

class DBTableDecorator {

    use \objects\table\utilites;

    private $table_Template = "";
    private $row_Template = "";
    private $uri = "";
    private $table_paginator_Template = "";
    private $paginator_activerow_Template = "";
    private $paginator_inactiverow_Template = "";
    public static $templatesDirectory = "";

    public function Uri() {
        return $this->uri;
    }

    function __construct($tableTemplate, $rowTemplate, $paginatorTemplate, $pag_activeRowTemplate, $pag_inactiveRowTemplate) {

        global $APPLICATION;

        DBTableDecorator::$templatesDirectory = $APPLICATION->GetActiveTemplateViewsDirectory();
        $this->table_Template = $tableTemplate;
        $this->row_Template = $rowTemplate;

        $this->table_paginator_Template = $paginatorTemplate;
        $this->paginator_activerow_Template = $pag_activeRowTemplate;
        $this->paginator_inactiverow_Template = $pag_inactiveRowTemplate;

        $uri = mb_substr($_SERVER['REQUEST_URI'], 1, strlen($_SERVER['REQUEST_URI']));
        $this->uri = strpos($uri, "page_number") > 0 ? mb_substr($uri, 0, strpos($uri, "page_number") - 1) : $uri;
        $this->uri = strpos($this->uri, "editrow") > 0 ? mb_substr($this->uri, 0, strpos($this->uri, "editrow") - 1) : $this->uri;
        $this->uri = strpos($this->uri, "droprow") > 0 ? mb_substr($this->uri, 0, strpos($this->uri, "droprow") - 1) : $this->uri;
    }

    public function PaginatorTable($ActivePage, $TotalPages) {
        $paginator = new \objects\table\Paginator($ActivePage, $TotalPages);

        $paginator->SetLinkText("{$this->uri}/page_number");

        $paginator->SetTemplates($this->paginator_inactiverow_Template, $this->paginator_activerow_Template, $this->table_paginator_Template);

        return $paginator->GetPagenator(array("#MAX_PAGES#" => $TotalPages,
                    "#PAGE_NUMBER#" => $ActivePage,
                    "#CONTROLLER#" => ""), false);
    }

    public function WrapRow($datarow, $edit_btn = 0, $drop_btn = 0, $join_btn = 0) {

        $datarow['EDITBTN'] = $edit_btn;
        $datarow['DROPBTN'] = $drop_btn;
        $datarow['JOINBTN'] = $join_btn;
        $datarow['TABLE_ROW_DROP_LINK'] = "/" . $this->uri . "/droprow";
        $datarow['TABLE_ROW_EDIT_LINK'] = "/" . $this->uri . "/editrow";
        return $this->LoadTemplate($this->row_Template, $datarow);
    }

    public function WrapTable($tableRowsContent, $edit_btn = 0, $drop_btn = 0, $join_btn = 0) {

        ob_start();
        include ($this->PrepareTemplate($this->table_Template, ["#ROWS#" => $tableRowsContent, "EDITBTN" => $edit_btn, "DROPBTN" => $drop_btn, "JOINBTN" => $join_btn]));
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }

}

class DBTableEditor {

    use \objects\table\utilites;

    private $editor_Template = "";
    private $uri = "";
    public static $templatesDirectory = "";

    function __construct($rowEditorTemplate) {

        global $APPLICATION;

        DBTableEditor::$templatesDirectory = $APPLICATION->GetActiveTemplateViewsDirectory();
        $this->editor_Template = $rowEditorTemplate;

        $uri = mb_substr($_SERVER['REQUEST_URI'], 1, strlen($_SERVER['REQUEST_URI']));
        $this->uri = strpos($uri, "page_number") > 0 ? mb_substr($uri, 0, strpos($uri, "page_number") - 1) : $uri;
        $this->uri = strpos($this->uri, "editrow") > 0 ? mb_substr($this->uri, 0, strpos($this->uri, "editrow") - 1) : $this->uri;
        $this->uri = strpos($this->uri, "droprow") > 0 ? mb_substr($this->uri, 0, strpos($this->uri, "droprow") - 1) : $this->uri;
    }

    public function View($datarow) {
        ob_start();
        include ($this->PrepareTemplate($this->editor_Template, $datarow));
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }

}

class TableRow
{
     private $fields = [];
     
     public function __construct($rowdata = null) {
         
         foreach ($rowdata as $colname=>$colvalue)
         {
             $this->fields[$colname] = $colvalue;    
         }
     }   
     
     public function __unset($name) {
         unset($this->fields[$name]);
     }
     
     public function __set($name, $value) {
         if (isset($value))
         {
             $this->fields[$name] = $value;
         }
     }   
     
     public function __get($name) {
         return $this->fields[$name];
     }
     
     public function GetFields()
     {
         return $this->fields;
     }
     
     public function GetFieldNames()
     {
         $fieldnames = [];
         
         foreach($this->fields as $field_name=>$field_value)
         {
             $fieldnames[$field_name] = $field_name;
         }
         
         return $fieldnames;
     }
}

class DBTable implements ITable {

    use table,
        utilites;

    private $uri = [];
    protected $events = [];
    protected $decorator = null;
    protected $relations_list = [];
    protected $relations = [];
    protected $extfields = [];
    protected $orders = [];
    

    public function __construct(string $table_name, array $options = null) {
        $this->InitTable($table_name, $options);

        $uri = mb_substr($_SERVER['REQUEST_URI'], 1, strlen($_SERVER['REQUEST_URI']));
        $this->uri = strpos($uri, "page_number") > 0;
        $this->editrow = strpos($uri, "editrow") > 0;
        $this->droprow = strpos($uri, "droprow") > 0;
    }

    public static function CreateWrapper(string $table_name, array $options = null) {
        return new DBTable($table_name, $options);
    }

    public function Decorator(DBTableDecorator $decorator) {
        $this->decorator = $decorator;
    }

    public function Editor(DBTableEditor $editor) {
        $this->editor = $editor;
    }

    public function Relations($relationsArray) {
        //["COUTRY_ID from countries_edit:ID as COUNTRY_{$lang}"]
        $this->relations_list = $relationsArray;
        $this->relations = [];
        $this->extfields = [];
        $this->orders = [];

        foreach ($this->relations_list as $relationItem) {

            //"countries_edit.ID alias CNTID", "regions_edit.ID alias REGID"
            $aliasIndex = strpos($relationItem, "alias");
            $orderIndex = strpos($relationItem, "orderby");
            
            
            if ($aliasIndex === FALSE && $orderIndex === FALSE) 
            {
                $fromIndex = strpos($relationItem, "from");
                $asIndex = strpos($relationItem, " as ");
                
                $localTableField = mb_strtoupper(trim(mb_substr($relationItem, 0, $fromIndex)), "UTF8");
                $destTableField = mb_strtoupper(trim(mb_substr($relationItem, $asIndex + 2)), "UTF8");
                $destTable = trim(mb_substr($relationItem, $fromIndex + mb_strlen("from"), ($asIndex - ($fromIndex + mb_strlen("from")))));

                list($tableName, $relfieldInTable) = explode(":", $destTable);

                $this->relations[$localTableField] = ["DESTFIELD" => $destTableField, "DESTTABLE" => $tableName, "DESTRELFIELD" => $relfieldInTable];
            }
            
            if ($orderIndex >0)
            {
                list($fieldName, $orderDirectio) = explode("orderby", $relationItem);
                $this->orders[$fieldName] = $orderDirectio;
            }

            if ($aliasIndex > 0) {
                list($fieldName, $aliasName) = explode("alias", $relationItem);
                $this->extfields[$fieldName] = $aliasName;
            }
        }

        //print_r($this->relations);
    }
    
    public function Rows($page_numaber=1, $items_per_page=9999999999)
    {
        $this->rows = $this->Page($page_numaber, $items_per_page, $this->relations, $this->extfields, $this->orders);
        foreach ($this->rows as $rowdata) {
            yield $rowdata;
        }
    }

    public function View($filter = "") {

        $editbtn = isset($this->options['edit']) ? ($this->options['edit'] ? 1 : 0) : 0;
        $dropbtn = isset($this->options['drop']) ? ($this->options['drop'] ? 1 : 0) : 0;
        $joinbtn = isset($this->options['join']) ? ($this->options['join'] ? 1 : 0) : 0;
        
        $visiblealways = isset($this->options['alwaysvisible']) ? $this->options['alwaysvisible'] : false;

        if ($this->editrow) {

            $id = 1;
            $rowdata = $this->Row($id);
            $viewhtml = $this->editor->View($rowdata);
        } else {
            
            if ($filter == "")
            {
                $this->rows = $this->Page($this->PageNumber, $this->ItemsPerPage, $this->relations, $this->extfields, $this->orders);
            } else
            {
                $this->rows = $this->Filter($filter, $this->PageNumber, $this->ItemsPerPage, $this->relations, $this->extfields,$this->orders);
            }
            
            $viewhtml = "";

            if (is_array($this->rows) && ($visiblealways || sizeof($this->rows) >= 0)) {

                $rowshtml = "";
                $index = 1;

                foreach ($this->rows as $rowdata) {

                    $rowdata['ROW_NUMBER'] = $index;
                    
                    if (array_key_exists("datarow", $this->events))
                    {
                        $function = $this->events["datarow"];
                        $rowdata = $function($rowdata);
                    }
                    
                    $rowshtml .= $this->decorator->WrapRow($rowdata, $editbtn, $dropbtn, $joinbtn);
                    $index++;
                }

                $viewhtml = $this->decorator->WrapTable($rowshtml, $editbtn, $dropbtn, $joinbtn);

                if ($this->show_paginator) {
                    $viewhtml .= $this->decorator->PaginatorTable($this->PageNumber, $this->TotalPages);
                }
            }
        }

        return $viewhtml;
    }

    public function EventHandler($eventtype, $handler) {
        $this->events[$eventtype] = $handler;
    }

}
