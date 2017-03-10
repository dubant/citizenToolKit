<?php 
class ActionRoom {

	const COLLECTION 		= "actionRooms";
	const CONTROLLER 		= "rooms";
	
	const TYPE_SURVEY 		= "survey"; //sondage à la Google Forms
	const TYPE_DISCUSS 		= "discuss"; // systeme de discussioin voir avec dialoguea
	const TYPE_FRAMAPAD 	= "framapad"; // systeme de discussioin voir avec dialoguea
	const TYPE_BRAINSTORM 	= "proposals"; //systeme de rpopositions pour prendre des décision
	const TYPE_VOTE 		= "vote"; //vote
    const TYPE_ENTRY        = "entry"; //vote
	const TYPE_DISTRIBUTE	= "distribute"; //vote par distribution sur des proposition
	
	const STATE_ARCHIVED 		= "archived";

	const TYPE_ACTIONS 		= "actions"; //things to do 
	const TYPE_ACTION 		= "action"; //things to do 
	const COLLECTION_ACTIONS= "actions";
	const ACTIONS_PARENT	= "rooms";

	//ACTION STATES
	const ACTION_TODO = "todo";
	const ACTION_INPROGRESS = "inprogress";
	const ACTION_LATE = "late";	
	const ACTION_CLOSED = "closed";	
	/**
	 * get a action room By Id
	 * @param String $id : is the mongoId of the action room
	 * @return array Document of the action room
	 */
	public static function getById($id) {
	  	return PHDB::findOne( self::COLLECTION,array("_id"=>new MongoId($id)));
	}
	public static function getActionById($id) {
	  	return PHDB::findOne( self::COLLECTION_ACTIONS,array("_id"=>new MongoId($id)));
	}

	public static function getWhereSortLimit($params,$sort,$limit=1) {
	  	return PHDB::findAndSort( self::COLLECTION,$params,$sort,$limit);
	}

	public static function getSingleActionRoomByOrgaParent($idOrga){
		error_log("idOrga " . $idOrga);
		return PHDB::findOne( self::COLLECTION, 
										array("parentId"=> $idOrga, 
											  //"parentType" => "organizations",
											  //"type" => "vote"
											  ));
	}

	public static function canParticipate($userId,$id=null,$type=null) {
		$showAddBtn = false;
        if( ( $type == Organization::COLLECTION && Authorisation::isOrganizationMember( $userId , $id ) )
            || ( $type == Project::COLLECTION && Authorisation::isProjectMember( $userId , $id ) )
            || ( $type == Event::COLLECTION && Authorisation::isEventMember( $userId , $id ) ) )
            $showAddBtn = true;
	  	return $showAddBtn;
	}

	public static function isModerator($userId,$app) {
     	$app = PHDB::findOne(PHType::TYPE_APPLICATIONS, array("key"=> $app ) );
     	$res = false;
     	if( isset($app["moderator"] ))
    		$res = ( isset( $userId ) && in_array(Yii::app()->session["userId"], $app["moderator"]) ) ? true : false;
    	return $res;
     }

    public static function canAdministrate($userId, $id) {
        $actionRoom = self::getById($id);

        $parentId = @$actionRoom["parentId"];
        $parentType = @$actionRoom["parentType"];

        $isAdmin = false;
        if( ( $parentType == Organization::COLLECTION && Authorisation::isOrganizationAdmin ( $userId , $parentId ) )
            || ( $parentType == Project::COLLECTION && Authorisation::isProjectAdmin( $userId , $parentId ) )
            || ( $parentType == Event::COLLECTION && Authorisation::isEventAdmin( $userId , $parentId ) ) )
            $isAdmin = true;
        return $isAdmin;
    }

     /**
    *
    * @return [json Map] list
    */
 	public static function insert($parentRoom,$type,$copyOf=null)
 	{
 	    /*if (! Authorisation::canParticipate(Yii::app()->session['userId'],$parentRoom['parentType'],$parentRoom['parentId']) ) {
			throw new CTKException("Can not update the event : you are not authorized to update that event!");	
		}*/
 	    
        $newInfos = array();
        $newInfos['email'] = Yii::app()->session['userEmail'];
        $newInfos['name'] = $parentRoom['name'];
        $newInfos['type'] = $type;
        if( @$copyOf )
        	$newInfos['copyOf'] = $copyOf;
        $newInfos['parentType'] = $parentRoom['parentType'];
        $newInfos['parentId'] = $parentRoom['parentId'];
        if( count(@$parentRoom['tags'])>0 )
            $newInfos['tags'] = $parentRoom['tags'];
        $newInfos['created'] = time();
        PHDB::insert( ActionRoom::COLLECTION, $newInfos );
        return $newInfos;
 	}

    /**
     * Delete an action room and its children (comments, votes...)
     * @param String $id id of the room to delete
     * @param String $userId userId making the delete
     * @return array result => boolean, msg => String
     */
    public static function deleteActionRoom($id, $userId){
     	error_log("Try to delete the actionroom ".$id);
        $res = array( "result" => false, "msg" => "Something went wrong : contact your admin !");;
     	
        $actionRoom = self::getById($id);
        if (empty($actionRoom)) return array("result" => false, "the action room does not exist");
        
        if (! self::canAdministrate($userId, $id)) return array("result" => false, "msg" => "You must be admin of the parent of this room if you want delete it");
        
        //Remove actionRoom of type discuss : remove all comments linked
        if (@$actionRoom["type"] == self::TYPE_DISCUSS) {
            $resChildren = Comment::deleteAllContextComments($id, self::COLLECTION, $userId);
        //Remove actionRoom of type vote : remove all survey linked
        } else if (@$actionRoom["type"] == self::TYPE_VOTE) {
            //Delete all surveys of this action room
            $resChildren = Survey::deleteAllSurveyOfTheRoom($id, $userId);
        } else if (@$actionRoom["type"] == self::TYPE_ACTIONS) {
            //Delete all actions of this action room
            $resChildren = Actions::deleteAllActionsOfTheRoom($id, $userId);
        } else {
            $resChildren = array("result" => false, "msg" => "This delete of this type of action room '".@$actionRoom["type"]."' is not yet implemented.");
        }

        if (isset($resChildren["result"]) && !$resChildren["result"]) return $resChildren;

        //Remove the action room
        if (PHDB::remove(self::COLLECTION,array("_id"=>new MongoId($id)))) {
            $res = array( "result" => true, "msg" => "The action room has been deleted with success");
        } 

        return $res;
    }

    /**
     * Delete all rooms (comments, votes...) of an element
     * @param String $elementType type of the element
     * @param String $elementId id of the element
     * @param String $userId userId making the delete
     * @return array result => boolean, msg => String
     */
    public static function deleteElementActionRooms($elemenType, $elementId, $userId){        
        //Check if the $userId can delete the element
        $canDelete = Authorisation::canDeleteElement($elementType, $elementId, $userId);
        if (! $canDelete) {
            return array("result" => false, "msg" => "You do not have enough credential to delete this element rooms.");
        }

        //get all actions
        $actionRooms2delete = self::getAllRoomsByTypeId($elemenType, $elementId);
        
        foreach ($actionRooms2delete as $id => $anActionRoom) {
            self::deleteActionRoom($id, $userId);
            $nbActionRoom++;
        }

        return array("result" => true, "msg" => $nbActionRoom." actionRoom of the element ".$id." of type ".$elementType." have been removed with succes.");
    }

    public static function closeAction($params){
     	$res = array( "result" => false );
     	if( isset( Yii::app()->session["userId"] ))
     	{ 
     		if( $action = PHDB::findOne( self::COLLECTION_ACTIONS, array("_id"=>new MongoId($params["id"])) ) ) 
     		{
	     		if( Yii::app()->session["userEmail"] == $action["email"] ) 
	     		{
			     	//then remove the parent survey
			     	$status = ( @$action["status"] == self::ACTION_CLOSED) ? self::ACTION_INPROGRESS : self::ACTION_CLOSED; 
	     			PHDB::update( self::COLLECTION_ACTIONS,
	     							array("_id" => $action["_id"]), 
                          			array('$set' => array("status"=> $status )));
                    Action::updateParent($_POST['id'], self::COLLECTION_ACTIONS);
	     			$res["result"] = true;
			     } else 
			     	$res["msg"] = "restrictedAccess";
		     } else
		     	$res["msg"] = "SurveydoesntExist";
	     } else 
	     	$res["msg"] = "mustBeLoggued";
		return $res;
    }

     /**
        * must be part of the organisation or project to take action 
        * on city actions anyone can participate
        * @return [json Map] list
        */
    public static function assignMe($params){
     	$res = array( "result" => false );
     	if( isset( Yii::app()->session["userId"] ))
     	{ 
     		if( $action = PHDB::findOne( self::COLLECTION_ACTIONS, array("_id"=>new MongoId($params["id"])) ) ) 
     		{
	     		if( Authorisation::canParticipate(Yii::app()->session["userId"], $action["parentType"], $action["parentId"]) ) 
	     		{
			     	$res = Link::connect($params["id"], self::COLLECTION_ACTIONS,Yii::app()->session["userId"], Person::COLLECTION, Yii::app()->session["userId"], "contributors", true );
                    Action::updateParent($_POST['id'], self::COLLECTION_ACTIONS);
			     } else 
			     	$res["msg"] = "restrictedAccess";
		     } else
		     	$res["msg"] = "SurveydoesntExist";
	     } else 
	     	$res["msg"] = "mustBeLoggued";
		return $res;
     }

     public static function getAllRoomsByTypeId($type, $id, $archived=null){

        $where = array("created"=>array('$exists'=>1) ) ;
     	$where["status"] = ($archived) ? self::STATE_ARCHIVED : array('$exists' => 0 );
        
        if(isset($type))
        	$where["parentType"] = $type;
        if(isset($id))
        	$where["parentId"] = $id;

        if( $type == Person::COLLECTION )
            $roomsActions = Person::getActionRoomsByPersonId($id, $archived);
        else if( isset( Yii::app()->session['userId'] ))
            $roomsActions = Person::getActionRoomsByPersonIdByType( Yii::app()->session['userId'] ,$type ,$id, $archived );
        else 
            $rooms = self::getWhereSortLimit( $where, array("date"=>1), 0);

        $actionHistory = array();
        if( isset($roomsActions) && isset($roomsActions["rooms"]) && isset($roomsActions["actions"])  ){
            $rooms   = $roomsActions["rooms"];
            $actionHistory = $roomsActions["actions"];
        }
        
        //error_log("count rooms : ".count($rooms));

        $discussions = array();
        $votes = array();
        $actions = array();
        foreach ($rooms as $e) 
        { 
            if( in_array($e["type"], array(self::TYPE_DISCUSS, self::TYPE_FRAMAPAD) )  ){
                array_push($discussions, $e);
            }
            else if ( $e["type"] == self::TYPE_VOTE ){
                array_push($votes, $e);
            } else if ( $e["type"] == self::TYPE_ACTIONS ){
                array_push($actions, $e);
            }
        }
        $params = array(    "discussions" => $discussions, 
                            "votes" => $votes, 
                            "actions" => $actions, 
                            "history" => $actionHistory );
       	return $params;
     }

     public static function getAllRoomsActivityByTypeId($type, $id, $archived=null){

        $where = array("created"=>array('$exists'=>1) ) ;
        $where["status"] = ($archived) ? self::STATE_ARCHIVED : array('$exists' => 0 );
        
        if(isset($type))
            $where["parentType"] = $type;
        if(isset($id))
            $where["parentId"] = $id;

        if( $type == Person::COLLECTION )
            $roomsActions = Person::getActionRoomsByPersonId($id, $archived);
        else if( isset( Yii::app()->session['userId'] ))
            $roomsActions = Person::getActionRoomsByPersonIdByType( Yii::app()->session['userId'] ,$type ,$id, $archived );
        else 
            $rooms = self::getWhereSortLimit( $where, array("date"=>1), 0);

        if( isset($roomsActions) && isset($roomsActions["rooms"]) && isset($roomsActions["actions"])  ){
            $rooms   = $roomsActions["rooms"];
        }
        
        //error_log("count rooms : ".count($rooms));

        $discussions = array();
        $proposals = array();
        $actions = array();
        foreach ($rooms as $e) 
        { 
            if( in_array($e["type"], array(self::TYPE_DISCUSS, self::TYPE_FRAMAPAD) )  ){
                //ordonner par updated
                array_push($discussions, $e);
            }
            else if ( $e["type"] == self::TYPE_VOTE ){
                //get all survey for this room by sorting
                $surveys = PHDB::findAndSort( Survey::COLLECTION,array("survey"=>(string)$e["_id"],"updated"=>array('$exists'=>1)),array("updated"=>1), 10);
                foreach ($surveys as $s) 
                { 
                    array_push($proposals, $s);
                }
            } else if ( $e["type"] == self::TYPE_ACTIONS ){
                //get all survey for this room by sorting
                $actionElements = PHDB::findAndSort( self::TYPE_ACTIONS,array("room"=>(string)$e["_id"],"updated"=>array('$exists'=>1)),array("updated"=>1), 10);
                foreach ($actionElements as $a) 
                { 
                    array_push($actions, $a);
                }
            }
        }
        
        function mySort($a, $b){ 
            if( isset($a['updated']) && isset($b['updated']) ){
                return (strtolower(@$b['updated']) > strtolower(@$a['updated']));
            }else{
                return false;
            }
        }
        
        $list = array_merge($discussions,$proposals,$actions);
        usort($list,"mySort");

        return $list;
     }
     
}
