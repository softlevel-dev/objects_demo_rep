<?php

namespace objects\model;

interface IModel {
    
    function CreateTable($table_name, $fields);
    function DropTables($table_name);
    
}
