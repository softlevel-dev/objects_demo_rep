<?php

namespace objects\model;

trait datamodel {
    
    function CreateTable($table_name, $fields)
    {
        $result = \objects\table\DatabaseManager::CreateTable($table_name, $fields);
        
        if (!$result)
        {
           // print \objects\table\DatabaseManager::LastQuery();
            //print \objects\table\DatabaseManager::LastError()."<br>";
        }
        
        return $result;
    }
    
    function DropTables($tables_array)
    {
        $result = \objects\table\DatabaseManager::DropTables($tables_array);
        
        return $result;
    }
   
}

