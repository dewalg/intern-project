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

/* TO DO:
1. ADD LOCATION, ROLES AND SKILLS TO VIEWING
*/

require_once("permissions.php");

class container extends permissions {
	
	public static function newDBconn() {

		try { //try connecting to $dbname at localhost
   			$db = new PDO(parent::$conn, parent::$username, parent::$password);
    		echo "Connected to ". parent::$dbname ." at ". parent::$host ." successfully. <br>";
    
    		$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
    		
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

$db = container::newDBconn();

$url = "https://api.angel.co/1/search?query=ucla&type=User";
$data = getData($url);

$users = array();
$location = array();
$skills = array();
$roles = array();
$i=0;
foreach ($data as $u) {
	
	$url = "https://api.angel.co/1/users/".$u['id'];
	$users[$i] = getData($url);
	
	foreach ($users[$i]['locations'] as $place) {
	
		$location[$i]['user_id'] = $users[$i]['id'];
		$location[$i]['location_id'] = $place['id'];
		$location[$i]['name'] = $place['name'];	
		$location[$i]['display_name'] = $place['display_name'];
		$location[$i]['angellist_url'] = $place['angellist_url'];
		
	}
	
	foreach ($users[$i]['skills'] as $sk) {
	
		$skills[$i]['user_id'] = $users[$i]['id'];
		$skills[$i]['level'] = $sk['level'];
		$skills[$i]['skill_id'] = $sk['id'];
		$skills[$i]['name'] = $sk['name'];	
		$skills[$i]['display_name'] = $sk['display_name'];
		$skills[$i]['angellist_url'] = $sk['angellist_url'];
		
	}
	
	foreach ($users[$i]['roles'] as $r) {
	
		$roles[$i]['user_id'] = $users[$i]['id'];
		$roles[$i]['role_id'] = $r['id'];
		$roles[$i]['name'] = $r['name'];	
		$roles[$i]['display_name'] = $r['display_name'];
		$roles[$i]['angellist_url'] = $r['angellist_url'];
		
	}
	
	unset($users[$i]['roles']);
	unset($users[$i]['skills']);
	unset($users[$i]['locations']);
	
	$i++;
}

try {
	$sql = $db->prepare("REPLACE INTO `users` (`name`,
	 `id`,
	  `bio`,
	   `blog_url`,
	`twitter_url`,
	`facebook_url`,
	 `linkedin_url`,
	  `online_bio_url`,
	  `aboutme_url`, 
	`github_url`,
	 `dribbble_url`,
	  `behance_url`,
	   `resume_url`,
	    `what_ive_built`, 
	`what_i_do`,
	 `criteria`,
	  `follower_count`,
	   `investor`,
	    `angellist_url`,
	     `image`)
		 
	value (:name,
	 :id,
	  :bio,
	   :blog_url,
	    :twitter_url,
	     :facebook_url,
	      :linkedin_url, 
	:online_bio_url,
	 :aboutme_url,
	  :github_url,
	   :dribbble_url,
	    :behance_url,
	     :resume_url,
	:what_ive_built,
	 :what_i_do,
	  :criteria,
	   :follower_count,
	    :investor,
	     :angellist_url,
	      :image)");
		
	foreach ($users as $u) {
		$sql->execute($u);
	}
	
	$sql2 = $db->prepare("REPLACE INTO `locations` (`user_id`, `location_id`, `name`, `display_name`, `angellist_url`)
	 value (:user_id, :location_id, :name, :display_name, :angellist_url)");
		
	foreach ($location as $place) {
		$sql2->execute($place);
	}
	
	$sql3 = $db->prepare("REPLACE INTO `skills` (`user_id`, `skill_id`, `level`, `name`, `display_name`, `angellist_url`)
	 value (:user_id, :skill_id, :level, :name, :display_name, :angellist_url)");
		
	foreach ($skills as $s) {
		$sql3->execute($s);
	}
	
	$sql4 = $db->prepare("REPLACE INTO `roles` (`user_id`, `role_id`, `name`, `display_name`, `angellist_url`)
	 value (:user_id, :role_id, :name, :display_name, :angellist_url)");
		
	foreach ($roles as $r) {
		$sql4->execute($r);
	}
	
} catch (PDOException $pe) {
	die("DB ERROR: " . $pe->getMessage());
}

//VIEW THE DATABASE CONTENTS:

try {
	$query = $db->prepare("SELECT * FROM `users`");
	$query->execute();
	
	echo '<table cellpadding="0" cellspacing="0" class="db-table">';
	echo '<tr><th>Person</th><th>bio</th><th>What I\'ve Built</th><th>What I Do</th>
		<th>Criteria</th><th>follower count</th></tr>';
	
	foreach ($query->FetchAll() as $user) {
		if ($user['pic'] == null) {
			$user['pic'] = "http://upload.wikimedia.org/wikipedia/commons/a/ac/No_image_available.svg\" height=\"140\" width=\"140";
		}
		
		if ($user['investor'] == true) {
			$user['investor'] = "(Investor)";
		} else {
			$user['investor'] = "";
		}
		
		$facebook_link = "<a href=\"".$user['facebook_url']."\">
		<img src=\"http://img2.wikia.nocookie.net/__cb20130501121248/logopedia/images/f/fb/Facebook_icon_2013.svg\" height=\"25\" width=\"25\"></a>";
		
		$twitter_link = "<a href=\"".$user['twitter_url']."\">
		<img src=\"https://cdn1.iconfinder.com/data/icons/simple-icons/4096/twitter-4096-black.png\" height=\"25\" width=\"25\"></a>";
		
		$github_link = "<a href=\"".$user['github_url']."\">
		<img src=\"http://fc05.deviantart.net/fs71/i/2012/223/4/3/github_flurry_ios_style_icon_by_flakshack-d5ariic.png\" height=\"25\" width=\"25\"></a>";
		
		$linkedin_link = "<a href=\"".$user['linkedin_url']."\">
		<img src=\"https://cdn1.iconfinder.com/data/icons/simple-icons/4096/linkedin-4096-black.png\" height=\"25\" width=\"25\"></a>";
		
		$dribbble_link = "<a href=\"".$user['dribbble_url']."\">
		<img src=\"https://cdn3.iconfinder.com/data/icons/unicons-vector-icons-pack/32/dribbble-512.png\" height=\"25\" width=\"25\"></a>";
		
		$behance_link = "<a href=\"".$user['behance_url']."\">
		<img src=\"https://cdn3.iconfinder.com/data/icons/picons-social/57/77-behance-512.png\" height=\"25\" width=\"25\"></a>";
					
		$onlinebio_link = "<br><a href=\"".$user['online_bio_url']."\">Online Bio</a><br>";
		$aboutme_link = "<a href=\"".$user['aboutme_url']."\">About me</a><br>";
		$blog_link = "<a href=\"".$user['blog_url']."\">Blog</a><br>";
		$resume_link = "<a href=\"".$user['resume_url']."\">Resume</a>";

		$URLs = $facebook_link.$twitter_link.$github_link.$linkedin_link.$dribbble_link.$behance_link 
				."<br>".$onlinebio_link." | ".$aboutme_link." | ".$blog_link." | ".$resume_link;
		
		print "<tr><td><a href=\"".$user['angellist_url']."\">".$user['name']."</a> ".$user['investor']."<p>
				<img src=\"".$user['image']."\"><br>User ID: ".$user['id']."<p>".$URLs."</td>
				<td>".$user['bio']."</td>
				<td>".$user['what_ive_built']."</td>
				<td>".$user['what_i_do']."</td>
				<td>".$user['criteria']."</td>
				<td>".$user['follower_count']."</td>
				<td></td>
				</tr>";
	}
	
	print "</table>";
	

} catch (PDOException $pe) { //if it couldn't connect
	die("DB ERROR: " . $pe->getMessage());
}

$db = null;

?>

<FORM>
<INPUT TYPE="button" onClick="history.go(0)" VALUE="Refresh">
</FORM>

</body>
</html>
