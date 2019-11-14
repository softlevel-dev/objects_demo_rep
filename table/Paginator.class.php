<?php

    namespace objects\table;
    
    class Paginator
    {
        use \objects\table\utilites;
        
        private $max_pages = 0;
        private $current_page = 0;
        private $page_link_template = "";
        private $current_page_link_template = "/core/templates/paginator_spnum.frm";
        private $paginator_wrapper = "/core/templates/paginator.frm";
        private $paginator_empty_wrapper = "/core/templates/emptypaginator.frm";
        private $link_text = "";
        private $visible_pages = array();
        
        public function __construct($current_page, $max_pages)
        {
            $this->page_link_template = $_SERVER['DOCUMENT_ROOT']."/core/templates/paginator_pnum.frm";
            $this->current_page_link_template = $_SERVER['DOCUMENT_ROOT']."/core/templates/paginator_spnum.frm";
            $this->paginator_wrapper = $_SERVER['DOCUMENT_ROOT']."/core/templates/paginator.frm";
            $this->paginator_empty_wrapper = $_SERVER['DOCUMENT_ROOT']."/core/templates/emptypaginator.frm";
            
            $this->max_pages = $max_pages;
            $this->current_page = $current_page;
        }
        
        public function SetTemplates($page_link_template, $page_selected_link_template, $paginator_wrapper="")
        {
            $this->page_link_template = $page_link_template;
            $this->current_page_link_template = $page_selected_link_template;
            
            if ($paginator_wrapper!="")
            { 
               $this->paginator_wrapper = $paginator_wrapper;
            }
        }
        
        public function SetLinkText($link_text)
        {
            $this->link_text = $link_text;
        }
        
        public function GetPageLinkHtml($page_number)
        {
            $ret = '';
            if (in_array($page_number,$this->visible_pages)) return '';
            array_push($this->visible_pages,$page_number);
            
            if ($this->current_page==$page_number)
            {
              $ret = $this->FormatByTemplate($page_number, "~", $this->current_page_link_template);
            }
            else
            {
              $ret = $this->FormatByTemplate($page_number."~"."/".$this->link_text."/{$page_number}", "~", $this->page_link_template);            
            }
            
            return $ret;
        }
        
        public function GetPagenator($variablesarray, $emptytemplate = false)
        {
            $result = "";
            
            if ($this->max_pages>0)
            {
                $result = $this->GetPageLinkHtml(1);
            
                if ($this->current_page-3>1)
                {
                    $result .= $this->FormatByTemplate('...'."~"."/".$this->link_text."/".floor(($this->current_page) / 2), "~", $this->page_link_template);   
                }
            
                $curr_page_dec1 = $this->current_page-3;
                if ($curr_page_dec1>1) $result .= $this->GetPageLinkHtml($curr_page_dec1);
                $curr_page_dec2 = $this->current_page-2;
                if ($curr_page_dec2>1) $result .= $this->GetPageLinkHtml($curr_page_dec2);            
                $curr_page_dec3 = $this->current_page-1;
                if ($curr_page_dec3>1) $result .= $this->GetPageLinkHtml($curr_page_dec3);            
            
                if ($this->current_page!==1)
                    $result .= $this->GetPageLinkHtml($this->current_page);
           
                $curr_page_inc1 = min($this->max_pages ,$this->current_page+1);
                if ($curr_page_inc1>$this->current_page && $curr_page_inc1<$this->max_pages) $result .= $this->GetPageLinkHtml($curr_page_inc1);            
                $curr_page_inc2 = min($this->max_pages ,$this->current_page+2);
                if ($curr_page_inc2>$this->current_page && $curr_page_inc2<$this->max_pages) $result .= $this->GetPageLinkHtml($curr_page_inc2);            
                $curr_page_inc3 = min($this->max_pages ,$this->current_page+3);            
                if ($curr_page_inc3>$this->current_page && $curr_page_inc3<$this->max_pages) $result .= $this->GetPageLinkHtml($curr_page_inc3);            
            
                if ($this->max_pages>$curr_page_inc3+1)
                    $result .= $this->FormatByTemplate('...'."~"."/".$this->link_text."/".($curr_page_inc3+1), "~", $this->page_link_template);  
            
                if ($this->max_pages>1)
                {
                    $result .= $this->GetPageLinkHtml($this->max_pages);
                }
            }
            
            if ($emptytemplate == true)
            {
                return $this->LoadTemplate($this->paginator_empty_wrapper, array_merge($variablesarray, array("#PAGES#"=>$result)));
            } else
            {
                $paginator = $this->LoadTemplate($this->paginator_wrapper, 
                        array_merge($variablesarray,array("#PAGES#"=>$result,
                "#NEXTLINK#"=>"/".$this->link_text."/nextpage",
                "#PREVLINK#"=>"/".$this->link_text."/prevpage")));
                
                return $paginator;
            }
        }
    }

?>
