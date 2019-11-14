<?php

namespace objects\table;

trait utilites {
    
    private function ExtractFilePath($FullFileName) 
    {
        return substr($FullFileName, 0, strrpos($FullFileName, '/'));
    }

    private function ExtractFileExt($FullFileName) 
    {
        return strtoupper(trim(substr($FullFileName, strrpos($FullFileName, '.') + 1, strlen($FullFileName))));
    }
    
    private function FormatByTemplate($string, $splitter = "", $formatFile = "") {
        if ($string == '' or $formatFile == '')
            return;
        
         if ($formatFile!="" && !file_exists($formatFile)) {
            $formatFile = DBTableDecorator::$templatesDirectory . "/" . $formatFile;

            //print "<br>".$file."<br>";
        }

        $data = ($splitter != '') ? explode($splitter, $string) : "";
        $format_str = file_get_contents($formatFile);

        $tmp = "";
        if (stripos($format_str, "#DATA#")) {
            if ($splitter != '') {
                foreach ($data as $data_key => $data_value) {
                    if ($data_value != '') {
                        $tmp1 = str_replace("#DATA_KEY#", $data_key, $format_str);
                        $tmp .= str_replace("#DATA#", $data_value, $tmp1);
                    }
                }
            } else {
                $tmp1 = str_replace("#DATA_KEY#", "", $format_str);
                $tmp = str_replace("#DATA#", $string, $tmp1);
            }
        }
        if (stripos($format_str, "#ELEMENT") !== false) {

            $search = array();

            for ($i = 1; $i <= sizeof($data); $i++)
                $search[] = "#ELEMENT{$i}#";

            $tmp = str_replace($search, $data, $format_str);
            $tmp = preg_replace("/#ELEMENT([^#]{1,4})#/", "", $tmp);
        }

        return $tmp;
    }
    
    
    private function PrepareTemplate($templateFile, $vars = null) {
        
//        rmdir(DBTableDecorator::$templatesDirectory."/builded");
        mkdir(DBTableDecorator::$templatesDirectory."/builded", 0777, true);
        
        $filename = DBTableDecorator::$templatesDirectory."/builded/".md5(DBTableDecorator::$templatesDirectory . "/" . $templateFile).".php";
        file_put_contents($filename, $this->LoadTemplate($templateFile, $vars));
        return $filename;
    }

    private function LoadTemplate($templateFile, $vars = null) {
        $file = $templateFile;

        if (!file_exists($file)) {
            $file = DBTableDecorator::$templatesDirectory . "/" . $templateFile;

            //print "<br>".$file."<br>";
        }

        if (file_exists($file)) {

            $template_content = file_get_contents($file);
            preg_match_all("/{tmpl\:*(.*?)\}/ies", $template_content, $matches, PREG_OFFSET_CAPTURE);
            $tmpl_queries = null;

            foreach ($matches[0] as $key => $value) {
                if (is_array($value) && $value != null && sizeof($value) > 0) {
                    $tmpl_queries[$key] = array('FULL' => trim($value[0]), 'START_POSITION' => $value[1], 'LENGTH' => strlen(trim($value[0])));
                }
            }

            foreach ($matches[1] as $key => $value) {
                if (is_array($value) && $value != null && sizeof($value) > 0) {
                    $tmpl_queries[$key]['TMPL_NAME'] = trim($value[0]);
                    $tmpl_queries[$key]['TMPL_POSITION'] = $value[1];
                    $tmpl_queries[$key]['TMPL_LENGTH'] = strlen(trim($value[0]));
                }
            }

            $path = DBTableDecorator::$templatesDirectory . "/" . $this->ExtractFilePath($templateFile);
            if (is_array($tmpl_queries) && sizeof($tmpl_queries) > 0) {
                foreach ($tmpl_queries as $key => $template_detail) {
                    $file_name1 = $template_detail['TMPL_NAME'] . ".frm";
                    $file_name2 = $template_detail['TMPL_NAME'] . ".tmpl";
                    $file_name3 = $template_detail['TMPL_NAME'] . "";

                    if (file_exists($path . "/" . $file_name1))
                        $tmpl_content = file_get_contents($path . "/" . $file_name1);
                    else if (file_exists($path . "/" . $file_name2))
                        $tmpl_content = file_get_contents($path . "/" . $file_name2);
                    else if (file_exists($path . "/" . $file_name3))
                        $tmpl_content = file_get_contents($path . "/" . $file_name3);

                    $template_content = str_replace($template_detail['FULL'], $tmpl_content, $template_content);
                }
            }

            if (is_array($vars)) {
                foreach ($vars as $var_name => $var_value) {
                    $template_content = str_replace($var_name, $var_value, $template_content);
                }
            }
            return $template_content;
        } else {
            throw new \Exception("Template not found: {$file}.<hr>");
        }
    }

}
