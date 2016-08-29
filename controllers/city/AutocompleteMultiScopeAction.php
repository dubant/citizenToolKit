<?php

class AutocompleteMultiScopeAction extends CAction
{
    public function run( )
    {
        
        $type      = isset($_POST["type"])        ? $_POST["type"] : null;
        $scopeValue = isset($_POST["scopeValue"])   ? $_POST["scopeValue"] : null;

        if($type == null || $scopeValue == null) 
            return Rest::json( array("res"=>false, "msg" => "error with type or scopeValue" ));
       
        if($type == "city")     $where = array('$or'=> array(array("name" => new MongoRegex("/^".$scopeValue."/i")),
                                                            array("alternateName" => new MongoRegex("/^".$scopeValue."/i") )
                                                            ));
                                              
        if($type == "cp")       $where = array("postalCodes.postalCode" =>new MongoRegex("/^".$scopeValue."/i"));
        if($type == "dep")      $where = array("depName" => new MongoRegex("/^".$scopeValue."/i"));
        if($type == "region")   $where = array("regionName" => new MongoRegex("/^".$scopeValue."/i"));
        
        //var_dump($where); return;
        if($type != "dep" && $type != "region")
            $cities = PHDB::findAndSort( City::COLLECTION, $where, 
                                        array(), 15 ,
                                        array("insee", "postalCodes", "country", "name", "alternateName", "depName", "regionName"));
        else if($type == "dep")
            $cities = PHDB::distinct( City::COLLECTION, "depName", $where);
        else if($type == "region")
            $cities = PHDB::distinct( City::COLLECTION, "regionName", $where);
        
        return Rest::json( array("res"=>true, "cities" => $cities ));
       
    }
}
