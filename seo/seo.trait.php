<?php

namespace objects\seo;

trait Seo
{
    ///
    /// Указывает, что эта страница может быть закрыта от робота если установлен тег
    ///<meta name="robots" content="nofollow"/>
    protected $indexpage = true;
    
    protected $title = "";
    protected $og_title = "";
    
    protected $description = "";
    protected $og_description = "";
    
    protected $keywords = "";
    
    protected $og_image = "";
    
    protected $og_type = "";
    
    protected $og_url = "";
            
    function setTitle($title)
    {
        $this->title = $title;
        $this->data['#TITLE#'] = $title;
    }
    
    function getTitle()
    {
        return $this->title;
    }
    
    function setDescription($description)
    {
        $this->description = $description;
        $this->data['#DESCRIPTION#'] = $description;
    }
    
    function getDescription()
    {
        return $this->description;
    }
    
    function setKeywords($keywords)
    {
        $this->keywords = $keywords;
        $this->data['#KEYWORDS#'] = $keywords;
    }
    
    function getKeywords()
    {
        return $this->keywords;
    }
    
    // og:
    
    function setOgTitle($og_title)
    {
        $this->og_title = $og_title;
    }
    
    function getOgTitle()
    {
        return $this->og_title;
    }
    
    function setOgImage($og_image)
    {
        $this->og_image = $og_image;
    }
    
    function getOgImage()
    {
        return $this->og_image;
    }
    
    function setOgType($og_type)
    {
        $this->og_type = $og_type;
    }
    
    function getOgType()
    {
        return $this->og_type;
    }
    
    function setOgUrl($og_url)
    {
        $this->og_url = $og_url;
    }
    
    function getOgUrl()
    {
        return $this->og_url;
    }
    
    function setOgDescription($og_description)
    {
        $this->og_description = $og_description;
    }
    
    function getOgDescription()
    {
        return $this->og_description;
    }
    
    // no index
    
    function NoIndexThisPage()
    {
        $this->indexpage = false;
    }
    
    function IndexThisPage()
    {
        $this->indexpage = true;
    }
    
}

