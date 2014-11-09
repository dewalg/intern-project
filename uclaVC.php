<?php 
/*
TO DO: 
3. ADD GLOBAL HANDLER FOR DB CONNECTION 
4. CLEAN UP CODE
5. ADD VIEWING/QUERYING ABILITY 
*/

require_once("permissions.php");

$raw_data = file_get_contents($url);

$data = json_decode($raw_data, true);

//var_dump($data);


//CONNECTING TO THE DATABASE


try { //try connecting to $dbname at localhost
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    echo "Connected to $dbname at $host successfully. <br>";
    
    $db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
    
    $db = null;

} catch (PDOException $pe) { //if it couldn't connect
   
   	try { //...is it a connection problem? 
    	$db = new PDO("mysql:host=$host", $username, $password);
    } catch (PDOException $pe) {
    	die("DB ERROR: " . $pe->getMessage());
    } 
    
    $db = new PDO("mysql:host=$host", $username, $password);
    //if not connection, then db doesn't exist, so set it up:
    $db->query("CREATE DATABASE IF NOT EXISTS `$dbname`");
	$db->query("USE `$dbname`");
	
	$table_setup = "CREATE TABLE IF NOT EXISTS `ucla`(
  		`id` int(10) NOT NULL,
  		`pic` varchar(150) NULL,
  		`url` varchar(150) NULL,
  		`name` varchar(150)  NULL,
  		`type` varchar(20)  NULL
	)";
	
	$db->exec($table_setup);
		
	print "created new database $dbname! <br> ";
} 

//adding data to the database

try {
	$db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
	$sql = $db->prepare("INSERT INTO `ucla` (`id`, `pic`, `url`, `name`, `type`) value (:id, :pic, :url, :name, :type)");
	foreach ($data as $user) {
		$sql->execute($user);
	}
	
	echo "successfully inserted all items";
	
} catch (PDOException $pe) {
	die("DB ERROR: " . $pe->getMessage());
}

 
    
 ?>