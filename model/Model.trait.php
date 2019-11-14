<?php

namespace objects\model;

use \objects\table;

trait Model {
    
    private $fff = 0;
    
    function CreateTable($table_name, $fields)
    {
        return table\DatabaseManager::CreateTable($table_name, $fields);
    }
    
    function DropTable($table_name)
    {
        return table\DatabaseManager::DropTable($table_name);
    }
}

