<?php

namespace objects\seo;

interface ISeo
{
    function setTitle($title);
    function getTitle();
    
    function setDescription($description);
    function getDescription();
    
    function setKeywords($keywords);
    function getKeywords();
    
    // og:
    
    function setOgTitle($og_title);
    function getOgTitle();
    
    function setOgImage($og_image);
    function getOgImage();
    
    function setOgType($og_type);
    function getOgType();
    
    function setOgUrl($og_url);
    function getOgUrl();
    
    function setOgDescription($og_description);
    function getOgDescription();
    
    // no index
    
    function NoIndexThisPage();
    function IndexThisPage();
    //<meta name="robots" content="nofollow"/>
}

