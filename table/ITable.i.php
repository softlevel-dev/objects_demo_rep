<?php

namespace objects\table;

interface ITable {
    function InsertEmpty();
    function Insert(TableRow $row, int $index = 999999999);
    
    function Find($condition_fields);
    
    function Exists();
    
    function RowsCount();
    function ColumnsCount();
    
    function Delete($condition_fields);
    
    function Update(TableRow $row);
}
