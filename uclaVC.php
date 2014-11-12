<?php 
/*
TO DO: 
1. ADD VIEWING/QUERYING ABILITY 
2. COMMENT CODE
*/

require_once("permissions.php");

class container extends permissions {
	
	public static function newDBconn() {

		try { //try connecting to $dbname at localhost
   			$db = new PDO(parent::$conn, parent::$username, parent::$password);
    		echo "Connected to ". parent::$dbname ." at ". parent::$host ." successfully. <br>";
    
    		$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
    		
			} catch (PDOException $pe) { //if it couldn't connect
				die("DB ERROR: " . $pe->getMessage());
			}
			
		return $db;	
	}
}

function getData($url) {
	$raw_data = file_get_contents($url);
	$parsed_data = json_decode($raw_data, true);
	
	return $parsed_data;
}


$url = "https://api.angel.co/1/search?query=ucla&type=User";
$data = getData($url);


$db = container::newDBconn();

try {
	$sql = $db->prepare("INSERT INTO `ucla` (`id`, `pic`, `url`, `name`, `type`) value (:id, :pic, :url, :name, :type)");
	foreach ($data as $user) {
		$sql->execute($user);
	}
	
	echo "successfully inserted all items";
	
} catch (PDOException $pe) {
	die("DB ERROR: " . $pe->getMessage());
}

$db = null;

?>