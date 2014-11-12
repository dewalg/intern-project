<!DOCTYPE html>
<html>
<head>
	<style>
	table.db-table 		{ border-right:1px solid #ccc; border-bottom:1px solid #ccc; }
	table.db-table th	{ background:#eee; padding:5px; border-left:1px solid #ccc; border-top:1px solid #ccc; }
	table.db-table td	{ padding:5px; border-left:1px solid #ccc; border-top:1px solid #ccc; }
	</style>
</head>

<body>
<?php

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
	
	echo "successfully inserted all items.<br>";
	
} catch (PDOException $pe) {
	die("DB ERROR: " . $pe->getMessage());
}


//VIEW THE DATABASE CONTENTS:

try {
	$query = $db->prepare("SELECT * FROM `ucla`");
	$query->execute();
	
	echo '<table cellpadding="0" cellspacing="0" class="db-table">';
	echo '<tr><th>Id</th><th>Picture</th><th>URL</th><th>Name</th><th>User Type</th></tr>';
	
	foreach ($query->FetchAll() as $user) {
		print "<tr><td>".$user['id']."</td><td><img src=\"".$user['pic']."\"></td><td>".$user['url']."</td><td>".$user['name']."</td><td>".$user['type']."</td></tr>";
	}
	
	print "</table>";
	

} catch (PDOException $pe) { //if it couldn't connect
	die("DB ERROR: " . $pe->getMessage());
}


?>

