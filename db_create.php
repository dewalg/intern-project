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

/* LIST OF ALL USER ATTRIBUTES:
name
id
bio
follower_count
angellist_url
image
blog_url
online_bio_url
twitter_url
facebook_url
linkedin_url
aboutme_url
github_url
dribbble_url
behance_url
resume_url
what_ive_built
what_i_do
criteria
locations()
roles()
skills()
investor (bool)
*/

try {
	//MAIN TABLE USER
	$sql = $db->prepare("CREATE TABLE `users` (
	`name` varchar(255),
	`id` int NOT NULL PRIMARY KEY,
	`bio` varchar(255),
	`blog_url` varchar(255),
	`twitter_url` varchar(255),
	`facebook_url` varchar(255),
	`linkedin_url` varchar(255),
	`online_bio_url` varchar(255),
	`aboutme_url` varchar(255),
	`github_url` varchar(255),
	`dribbble_url` varchar(255),
	`behance_url` varchar(255),
	`resume_url` varchar(255),
	`what_ive_built` mediumtext,
	`what_i_do` mediumtext,
	`criteria` mediumtext,
	`follower_count` int,
	`investor` varchar(255),
	`angellist_url` varchar(10),
	`image` varchar(255)	
	)");
	
	$sql->execute();
	
	//TABLE FOR LOCATIONS (since one user can have multiple locations)
	$sql2 = $db->prepare("CREATE TABLE `locations` (
	`user_id` int NOT NULL,
	`location_id` int NOT NULL,
	`name` varchar(255),
	`display_name` varchar(255),
	`angellist_url` varchar(255)
	)");
	
	$sql2->execute();
	
	//TABLE FOR ROLES
	$sql3 = $db->prepare("CREATE TABLE `roles` (
	`user_id` int NOT NULL,
	`role_id` int NOT NULL,
	`name` varchar(255),
	`display_name` varchar(255),
	`angellist_url` varchar(255)
	)");
	
	$sql3->execute();
	
	//TABLE FOR SKILLS
	$sql4 = $db->prepare("CREATE TABLE `skills` (
	`user_id` int NOT NULL,
	`skill_id` int NOT NULL, 
	`name` varchar(255),
	`display_name` varchar(255),
	`angellist_url` varchar(255),
	`level` varchar(255)
	)");
	
	$sql4->execute();
	
	//success message
	echo "successfully created all items.<br>";
	
} catch (PDOException $pe) {
	die("DB ERROR: " . $pe->getMessage());
}

?>