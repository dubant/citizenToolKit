<?php

class CO2Stat {

	const COLLECTION = "co2stats";	


	public static function incNbLoad($hash){

		$week = self::getTodayWeek();
		//echo "week : ".$week; exit;
		$CO2DomainName = Yii::app()->params["CO2DomainName"];

		$query = array( "week"=>$week, 
						"domainName"=> $CO2DomainName);

		$stat = PHDB::findOne(self::COLLECTION, $query);

		
		if($stat==false){
			self::initWeek($week);
			error_log("Stats initialisée pour cette semaine (n°$week)");
		}else{
			$today = date("D");
			
			$nbLoad = @$stat["hash"][$hash][$today]["nbLoad"];
			$stat["hash"][$hash][$today]["nbLoad"] = $nbLoad+1;
			//var_dump($stat); //exit;
			$resUpdate = PHDB::update(self::COLLECTION, $query, array('$set' => $stat));

			return $stat;
		}

		$stat = PHDB::findOne(self::COLLECTION, $query);
		return $stat;
	} 


	public static function getStatsByHash($week=null, $hash=null){

		// $role = Role::getRolesUserId(@Yii::app()->session["userId"]) ; 
  //       if(!Role::isSuperAdmin($role) && !Role::isSourceAdmin($role) ){
		// 	echo "Access deny";
		// 	exit;
		// }
		
		if($week==null) //si le numéro de semaine n'est pas indiqué
		$week = self::getTodayWeek(); //prend la semaine en cours

		$CO2DomainName = Yii::app()->params["CO2DomainName"]; //récupère le nom de domaine courrant (co2 || kgougle)

		$query = array( "week"=>$week, 
						"domainName"=> $CO2DomainName);	
		
		$stat = PHDB::findOne(self::COLLECTION, $query);

		if($stat==false){ //si les stats de la semaine ne sont pas encore créées
			$stat = self::initWeek($week); //initialise les stats
		}

		//extrait les données du hash demandé (s'il y en a un, sinon return les stats de tous les hash)
		$statByHash = ($hash!=null) ? @$stat["hash"][$hash] : $stat;

		if($CO2DomainName!="kgougle"){
			unset($statByHash["hash"]["co2-web"]);
			unset($statByHash["hash"]["co2-websearch"]);
			unset($statByHash["hash"]["co2-referencement"]);
		}
		$statByHash["week"] = substr($week, 0, 2)." - ".substr($week, 2, 4);
		$statByHash["numweek"] = intval(substr($week, 0, 2));
		$statByHash["year"] = substr($week, 2, 4);
		//var_dump($statByHash);
		return $statByHash;
	}


	private static function initWeek($week){
		$CO2DomainName = Yii::app()->params["CO2DomainName"];

		$newWeekStat = array("week"      => $week,
							 "domainName"=> $CO2DomainName,
							 "hash"		 => array("co2-login"=>array(), 
							 					  "co2-web"=>array(), 
							 					  "co2-websearch"=>array(),
							 					  "co2-referencement"=>array(),
							 					  "co2-page"=>array(),
							 					  "co2-search"=>array(),
							 					  "co2-live"=>array(),
							 					  "co2-annonces"=>array(),
							 					  "co2-agenda"=>array(),
							 					  //"co2-power"=>array()
							 					  ),
                        );

		foreach ($newWeekStat["hash"] as $domain => $days) {
			$newWeekStat["hash"][$domain] = array("Mon"=>array("nbLoad"=>0), 
                             					  "Tue"=>array("nbLoad"=>0), 
                             					  "Wed"=>array("nbLoad"=>0), 
                             					  "Thu"=>array("nbLoad"=>0), 
                             					  "Fri"=>array("nbLoad"=>0), 
                             					  "Sat"=>array("nbLoad"=>0), 
                             					  "Sun"=>array("nbLoad"=>0), 
                             					  );
		}

        PHDB::insert(self::COLLECTION, $newWeekStat);

        $query = array("week"=>$week);
		$stat = PHDB::findOne(self::COLLECTION, $query);
		return $stat;
	}

	public static function getTodayWeek(){ //error_log("getTodayWeek");
		//date_default_timezone_set('Pacific/Noumea');
		$w = date("W");
		$y = date("Y");
		$week = $w.$y;
		return $week;
	}
	
}
