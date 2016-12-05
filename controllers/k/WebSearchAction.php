<?php
/**
* retreive dynamically 
*/
class WebSearchAction extends CAction
{
    public function run() {
    	$controller=$this->getController();
        
        $query = array('$or'=>array());
        if(isset($_POST["category"]))
        	$query['$or'][]["categories"] = array('$in' => array($_POST["category"]));

    	if(isset($_POST["search"]) && $_POST["search"] != ""){
    		$search = $_POST["search"];
    		$searchRegExp = self::accentToRegex($search);
        	$query['$or'][]["title"] = new MongoRegex("/.*{$searchRegExp}.*/i");

        	$query['$or'][]["tags"] = array('$in' => array(new MongoRegex("/.*{$searchRegExp}.*/i")));
        	$query['$or'][]["categories"] = array('$in' => array(new MongoRegex("/.*{$searchRegExp}.*/i")));
        	
        	$query['$or'][]["description"] = new MongoRegex("/.*{$searchRegExp}.*/i");
    	}

    	if(!isset($_POST["category"]) && !isset($_POST["search"]))
    		$query = array();

    	$siteurls = PHDB::findAndSort("siteurl", $query, array("nbClick"=>-1));
    	foreach ($siteurls as $key => $siteurl) {
    		$siteurls[$key]["typeSig"] = "siteurl";
    	}
    	$params = array("siteurls"=>$siteurls,
    					"search"=>@$_POST["search"],
    					"category"=>@$_POST["category"]);

    	echo $controller->renderPartial("webSearch", $params, true);
    }





    static public function accentToRegex($text)
	{

		$from = str_split(utf8_decode('ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËẼÌÍÎÏĨÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëẽìíîïĩðñòóôõöøùúûüýÿ'));
		$to   = str_split(strtolower('SOZsozYYuAAAAAAACEEEEEIIIIIDNOOOOOOUUUUYsaaaaaaaceeeeeiiiiionoooooouuuuyy'));
		//‘ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËẼÌÍÎÏĨÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëẽìíîïĩðñòóôõöøùúûüýÿaeiouçAEIOUÇ';
		//‘SOZsozYYuAAAAAAACEEEEEIIIIIDNOOOOOOUUUUYsaaaaaaaceeeeeiiiiionoooooouuuuyyaeioucAEIOUÇ';
		$text = utf8_decode($text);
		$regex = array();

		foreach ($to as $key => $value)
		{
			if (isset($regex[$value]))
				$regex[$value] .= $from[$key];
			else 
				$regex[$value] = $value;
		}

		foreach ($regex as $rg_key => $rg)
		{
			$text = preg_replace("/[$rg]/", "_{$rg_key}_", $text);
		}

		foreach ($regex as $rg_key => $rg)
		{
			$text = preg_replace("/_{$rg_key}_/", "[$rg]", $text);
		}
		return utf8_encode($text);
	}



    static public function removeEmptyWords($text)
	{
		$stopwords = array("$","£","a","A","à","afin","ah","ai","aie","aient","aies","ailleurs","ainsi","ait","alentour","alias","allais","allaient","allait","allons","allez","alors","Ap.","Apr.","après","après-demain","arrière","as","assez","attendu","au","aucun","aucune","au-dedans","au-dehors","au-delà","au-dessous","au-dessus","au-devant","audit","aujourd'","aujourd'hui","auparavant","auprès","auquel","aura","aurai","auraient","aurais","aurait","auras","aurez","auriez","aurions","aurons","auront","aussi","aussitôt","autant","autour","autre","autrefois","autres","autrui","aux","auxdites","auxdits","auxquelles","auxquels","avaient","avais","avait","avant","avant-hier","avec","avez","aviez","avions","avoir","avons","ayant","ayez","ayons","B","bah","banco","bé","beaucoup","ben","bien","bientôt","bis","bon","C","c'","ç'","c.-à-d.","Ca","ça","çà","cahin-caha","car","ce","-ce","céans","ceci","cela","celle","celle-ci","celle-là","celles","celles-ci","celles-là","celui","celui-ci","celui-là","cent","cents","cependant",
"certain","certaine","certaines","certains","certes","ces","c'est-à-dire","cet","cette","ceux","ceux-ci","ceux-là","cf.","cg","cgr","chacun","chacune","chaque","cher","chez","ci","-ci","ci-après","ci-dessous","ci-dessus","cinq","cinquante","cinquante-cinq","cinquante-deux","cinquante-et-un","cinquante-huit","cinquante-neuf","cinquante-quatre","cinquante-sept","cinquante-six","cinquante-trois","cl","cm","cm²","combien","comme","comment","contrario","contre","crescendo","D","d'","d'abord","d'accord","d'affilée","d'ailleurs","dans","d'après","d'arrache-pied","davantage","de","debout","dedans","dehors","déjà","delà","demain","d'emblée","depuis","derechef","derrière","des","dès","desdites","desdits","désormais","desquelles","desquels","dessous","dessus","deux","devant","devers","dg","die","différentes","différents","dire","dis","disent","dit","dito","divers","diverses","dix","dix-huit","dix-neuf","dix-sept","dl","dm","donc","dont","dorénavant","douze","du","dû","dudit","duquel","durant","E","eh","elle",
"-elle","elles","-elles","en","'en","-en","encore","enfin","ensemble","ensuite","entre","entre-temps","envers","environ","es","ès","est","et","et/ou","étaient","étais","était","étant","etc","été","êtes","étiez","étions","être","eu","eue","eues","euh","eûmes","eurent","eus","eusse","eussent","eusses","eussiez","eussions","eut","eût","eûtes","eux","exprès","extenso","extremis","F","facto","fallait","faire","fais","faisais","faisait","faisaient","faisons","fait","faites","faudrait","faut","fi","flac","fors","fort","forte","fortiori","frais","fûmes","fur","furent","fus","fusse","fussent","fusses","fussiez","fussions","fut","fût","fûtes","G","GHz","gr","grosso","guère","H","ha","han","haut","hé","hein","hem","heu","hg","hier","hl","hm","hm³","holà","hop","hormis","hors","hui","huit","hum","I","ibidem","ici","ici-bas","idem","il","-il","illico","ils","-ils","ipso","item","J","j'","jadis","jamais","je","-je","jusqu'","jusqu'à","jusqu'au","jusqu'aux","jusque","juste","K","kg","km","km²","L","l'","la","-la","là","-là","là-bas","là-dedans","là-dehors","là-derrière","là-dessous","là-dessus","là-devant","là-haut","laquelle","l'autre","le","-le","lequel","les","-les","lès","lesquelles","lesquels","leur","-leur","leurs","lez","loin","l'on","longtemps","lors","lorsqu'","lorsque","lui","-lui","l'un","l'une","M","m'","m²","m³","ma","maint","mainte","maintenant","maintes","maints","mais","mal","malgré","me","même","mêmes","mes","mg","mgr","MHz","mieux","mil","mille","milliards","millions","minima","ml","mm","mm²","modo","moi","-moi","moins","mon","moult","moyennant","mt","N","n'","naguère","ne","néanmoins","neuf","ni","nº","non","nonante","nonobstant","nos","notre","nous","-nous","nul","nulle","O","ô","octante","oh","on","-on","ont","onze","or","ou","où","ouais","oui","outre","P","par","parbleu","parce","par-ci","par-delà","par-derrière","par-dessous","par-dessus","par-devant","parfois","par-là","parmi","partout","pas","passé","passim","pendant","personne","petto","peu","peut","peuvent","peux","peut-être","pis","plus","plusieurs","plutôt","point","posteriori","pour","pourquoi","pourtant","préalable","près","presqu'","presque","primo","priori","prou","pu","puis","puisqu'","puisque","Q","qu'","qua","quand","quarante","quarante-cinq","quarante-deux","quarante-et-un","quarante-huit","quarante-neuf","quarante-quatre","quarante-sept","quarante-six","quarante-trois","quasi","quatorze","quatre","quatre-vingt","quatre-vingt-cinq","quatre-vingt-deux","quatre-vingt-dix","quatre-vingt-dix-huit","quatre-vingt-dix-neuf","quatre-vingt-dix-sept","quatre-vingt-douze","quatre-vingt-huit","quatre-vingt-neuf","quatre-vingt-onze","quatre-vingt-quatorze","quatre-vingt-quatre","quatre-vingt-quinze","quatre-vingts","quatre-vingt-seize","quatre-vingt-sept","quatre-vingt-six","quatre-vingt-treize","quatre-vingt-trois","quatre-vingt-un","quatre-vingt-une","que","quel","quelle","quelles","quelqu'","quelque","quelquefois","quelques","quelques-unes","quelques-uns","quelqu'un","quelqu'une","quels","qui","quiconque","quinze","quoi","quoiqu'","quoique","R","revoici","revoilà","rien","S","s'","sa","sans","sauf","se","secundo","seize","selon","sensu","sept","septante","sera","serai","seraient","serais","serait","seras","serez","seriez","serions","serons","seront","ses","si","sic","sine","sinon","sitôt","situ","six","soi","soient","sois","soit","soixante","soixante-cinq","soixante-deux","soixante-dix","soixante-dix-huit","soixante-dix-neuf","soixante-dix-sept","soixante-douze","soixante-et-onze","soixante-et-un","soixante-et-une","soixante-huit","soixante-neuf","soixante-quatorze","soixante-quatre","soixante-quinze","soixante-seize","soixante-sept","soixante-six","soixante-treize","soixante-trois","sommes","son","sont","soudain","sous","souvent","soyez","soyons","stricto","suis","sur","sur-le-champ","surtout","sus","T","-t","t'","ta","tacatac","tant","tantôt","tard","te","tel","telle","telles","tels","ter","tes","toi","-toi","ton","tôt","toujours","tous","tout","toute","toutefois","toutes","treize","trente","trente-cinq","trente-deux","trente-et-un","trente-huit","trente-neuf","trente-quatre","trente-sept","trente-six","trente-trois","très","trois","trop","tu","-tu","U","un","une","unes","uns","USD","V","va","vais","vas","vers","veut","veux","via","vice-versa","vingt","vingt-cinq","vingt-deux","vingt-huit","vingt-neuf","vingt-quatre","vingt-sept","vingt-six","vingt-trois","vis-à-vis","vite","vitro","vivo","voici","voilà","voire","volontiers","vos","votre","vous","-vous","W","X","y","-y","Z","zéro");


	}

}