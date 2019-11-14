<?php

$autoload_function = function ($class_name)
{
    
    $class_full_path = str_replace("\\", DIRECTORY_SEPARATOR, $class_name);
    $class_full_path2 = "../".str_replace("\\", DIRECTORY_SEPARATOR, $class_name);
    
    $class_name_chanks = explode("\\", $class_name);
    
    $IName = $class_name_chanks[sizeof($class_name_chanks)-1];
    
    if (mb_substr($IName, 0,1, "utf-8") == "I" && !interface_exists($IName))
    {
        $file1 = $class_full_path.".i.php";
        $file11 = $class_full_path2.".i.php";
        if (file_exists($file1)) include_once $file1;
        else if (file_exists($file11)) include_once $file11;
    }
    
    if (!class_exists($IName))
    {
        $file1 = $class_full_path.".trait.php";
        $file2 = $class_full_path.".class.php";
        $file3 = $class_full_path.".php";
        
        $file11 = "../".$class_full_path.".trait.php";
        $file22 = "../".$class_full_path.".class.php";
        $file33 = "../".$class_full_path.".php";
        
        if (file_exists($file1)) 
        {
            include_once $file1;
        } else if (file_exists($file11)) 
        {
            include_once $file11;
        }
        
        if (file_exists($file2)) 
        {
            include_once $file2;
        } else if (file_exists($file22)) 
        {
            include_once $file22;
        }
        
        if (file_exists($file3)) 
        {
            include_once $file3;
        } else if (file_exists($file33)) 
        {
            include_once $file33;
        }
    }
};

spl_autoload_register($autoload_function);

?>