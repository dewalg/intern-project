<html>
<head>
	<link href='http://fonts.googleapis.com/css?family=Open+Sans:600,300' rel='stylesheet' type='text/css'>
	<style>
	
table.db-table 		
{ 
	border-right:1px solid #ccc; 
	border-bottom:1px solid #ccc; 
	border-top:1px solid #ccc; 
}

table.db-table td	
{  
	border-left:0px solid #ccc; border-top:1px solid #ccc; 
	border-bottom:1px solid #ccc; 
	font-family: 'Open Sans', serif; 
	font-size:15px; 
}

table.db-table tr	
{ 
	padding:0px;
	border-left:1px solid #ccc; 
	border-top:1px solid #ccc; 
	font-family: 'Open Sans', serif; 
	font-size:10px; 
}
	
table, tr, td, th
{
    border-collapse:collapse;
    
}
body 
{
	font-family: 'Open Sans', serif;
}
	
tr.header
{
	background:#eee;
  	cursor:pointer;
  	display: table-row;
}

tr 
{
    display: none;
}

	</style>
	
<script type='text/javascript' src='//code.jquery.com/jquery-1.9.1.js'></script>
<script type='text/javascript'> 
$(function(){
	$('.header').click(function(){
    	$(this).find('span').text(function(_, value){return value=='-'?'+':'-'});
    	$(this).nextUntil('tr.header').slideToggle(100, function(){
    	});
	});
}); 

</script>
</head>

<body>
<?php

//contains information on DB access
require_once("permissions.php"); 

//use container class for maximal security (restricted access to permissions)?
class container extends permissions {
	
	//static function allows for access without necessarily creating class instance
	public static function newDBconn() {

		//try to connect to a DB
		try { 
   			$db = new PDO(parent::$conn, parent::$username, parent::$password);
    
    		$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
    		
			} catch (PDOException $pe) { //if it couldn't connect
				die("DB ERROR: " . $pe->getMessage());
			}
			
		return $db;	
	}
}

//get data from API url and parse it
function getData($url) {
	$raw_data = file_get_contents($url);
	$parsed_data = json_decode($raw_data, true);
	
	return $parsed_data;
}

//create a new database connection
$db = container::newDBconn();

//angellist api url
$url = "https://api.angel.co/1/search?query=ucla&type=User";
$data = getData($url);

$users = array();
$location = array();
$skills = array();
$roles = array();

$i=0; //counter 
foreach ($data as $u) {
	
	//query the API to find more info on each user
	$url = "https://api.angel.co/1/users/".$u['id']; 
	$users[$i] = getData($url); //parse the data
	
	//put it into respective arrays
	//use separate arrays for location, roles and skills because
	//each user can be associated with multiple (must create relational tables in our DB)
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
	
	//take them out of the main array so we can input 
	//the data into separate tables
	unset($users[$i]['roles']);
	unset($users[$i]['skills']);
	unset($users[$i]['locations']);
	
	$i++;
}

try {
	//place the data into the users table
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

echo "<center><font size=8><img src=\"http://brand.ucla.edu/wp-content/uploads/2013/08/ucla-wordmark-main-1.jpg\" height=\"50\" align=CENTER><br> Tech Alumni</font><p>"; 

try {
	$query = $db->prepare("SELECT * FROM `users`");
	$query->execute();
	
	foreach ($query->FetchAll() as $user) {
		if ($user['pic'] == null) {
			$user['pic'] = "http://upload.wikimedia.org/wikipedia/commons/a/ac/No_image_available.svg\" height=\"140\" width=\"140";
		}
		
		///////////////////////////////////
		//URLs HANDLING: add icons
		
		if ($user['facebook_url']) {
			$facebook = "<a href=\"".$user['facebook_url']."\">
			<img src=\"http://img2.wikia.nocookie.net/__cb20130501121248/logopedia/images/f/fb/Facebook_icon_2013.svg\" height=\"25\" width=\"25\"></a>";
		} else {
			$facebook = false;
		}
	
		if ($user['twitter_url']) {
			$twitter = "<a href=\"".$user['twitter_url']."\">
			<img src=\"https://cdn1.iconfinder.com/data/icons/simple-icons/4096/twitter-4096-black.png\" height=\"25\" width=\"25\"></a>";
		} else {
			$twitter = false;
		}
	
		if ($user['github_url']) {	
			$github = "<a href=\"".$user['github_url']."\">
			<img src=\"http://fc05.deviantart.net/fs71/i/2012/223/4/3/github_flurry_ios_style_icon_by_flakshack-d5ariic.png\" height=\"25\" width=\"25\"></a>";
		} else {
			$github = false;
		}
	
		if ($user['linkedin_url']) {	
			$linkedin = "<a href=\"".$user['linkedin_url']."\">
			<img src=\"https://cdn1.iconfinder.com/data/icons/simple-icons/4096/linkedin-4096-black.png\" height=\"25\" width=\"25\"></a>";
		} else {
			$linkedin = false;
		}
	
		if ($user['dribbble_url']) {		
			$dribbble = "<a href=\"".$user['dribbble_url']."\">
			<img src=\"https://cdn3.iconfinder.com/data/icons/unicons-vector-icons-pack/32/dribbble-512.png\" height=\"25\" width=\"25\"></a>";
		} else {
			$dribbble = false;
		}	
	
		if ($user['behance_url']) {	
			$behance = "<a href=\"".$user['behance_url']."\">
			<img src=\"https://cdn3.iconfinder.com/data/icons/picons-social/57/77-behance-512.png\" height=\"25\" width=\"25\"></a>";
		} else {
			$behance = false;
		}			

		$URLs = null;
		if ($facebook) {
			$URLs .= $facebook." ";
		} 
		if ($twitter) {
			$URLs .= $twitter." ";
		} 
		 if ($github) {
			$URLs .= $github." ";
		} 
		 if ($linkedin) {
			$URLs .= $linkedin." ";
		} 
		 if ($dribbble) {
			$URLs .= $dribbble." ";
		} 
		 if ($behance) {
			$URLs .= $behance." ";
		} 
		
		
		
		if ($user['online_bio_url']) {	
			$onlinebio = "<a href=\"".$user['online_bio_url']."\">Online Bio</a><br>";
		} else {
			$onlinebio = false;
		}	
			
		if ($user['aboutme_url']) {	
			$aboutme = "<a href=\"".$user['aboutme_url']."\">About Me</a><br>";
		} else {
			$aboutme = false;
		}	
		
		if ($user['blog_url']) {	
			$blog = "<a href=\"".$user['blog_url']."\">Blog</a><br>";
		} else {
			$blog = false;
		}	
		
		if ($user['resume_url']) {	
			$resume = "<a href=\"".$user['resume_url']."\">Resume</a><br>";
		} else {
			$resume = false;
		}	
			

		$URLs2 = null;
		if ($onlinebio) {
			$URLs2 .= $onlinebio;
		}
		if ($aboutme) {
			$URLs2 .= $aboutme;
		}
		if ($blog) {
			$URLs2 .= $blog;
		}
		if ($resume) {
			$URLs2 .= $resume;
		}
		
		
		///////////////////////////////////
					
		//GET LOCATIONS ASSOCIATED WITH USER		
		
		$query = $db->prepare("SELECT * FROM `locations` WHERE user_id = :id");
		$query->bindParam(':id', $user['id']);
		$query->execute();
		
		$location_of_person = null;
		foreach ($query->fetchAll() as $loc) {
			$count = 0;
			if ($count==0) {
				$location_of_person = "<b>Located at:</b> <img src=\"https://cdn3.iconfinder.com/data/icons/unicons-vector-icons-pack/32/location-512.png\" height=\"15\" width=\"15\">".$loc['display_name'];
			} else {
				$location_of_person .= ", ".$loc['display_name'];
			}
			$count++;
		}
		
		//GET SKILL ASSOCIATED WITH USER		
		
		$query = $db->prepare("SELECT * FROM `skills` WHERE user_id = :id");
		$query->bindParam(':id', $user['id']);
		$query->execute();
		
		$skills_of_person = null;
		foreach ($query->fetchAll() as $sk) {
			$count = 0;
			if ($count==0) {
				$skills_of_person = "<b>Skills:</b> ".$sk['display_name'];
			} else {
				$skills_of_person .= ", ".$sk['display_name'];
			}
			$count++;
		}
		
		//GET ROLES ASSOCIATED WITH USER		
		
		$query = $db->prepare("SELECT * FROM `roles` WHERE user_id = :id");
		$query->bindParam(':id', $user['id']);
		$query->execute();
		
		$roles_of_person = null;
		foreach ($query->fetchAll() as $ro) {
			$count = 0;
			if ($count==0) {
				$roles_of_person = "<b>My Role(s):</b> ".$ro['display_name'];
			} else {
				$roles_of_person .= ", ".$ro['display_name'];
			}
			$count++;
		}
		
		//HANDLE NULL VALUES
		if ($user['what_i_do']) {
			$whatido = "<b>What I do:</b> ".$user['what_i_do']."<br>";
		}
		
		if ($user['what_ive_built']) {
			$whativebuilt = "<b>What I've built:</b> ".$user['what_ive_built']."<br>";
		}
		
		if ($user['criteria']) {
			$criteria = "<b>My Criteria:</b> ".$user['criteria']."<br>";
		}
		
		if ($user['investor']) {
			$investor = "I am an <b>investor</b>.";
		}
				
		echo '<table cellpadding="0" cellspacing="0" class="db-table" width="80%">';
	
		print "<tr class=\"header\">
		<td><a href=\"".$user['angellist_url']."\"><img src=\"".$user['image']."\" align=LEFT a></a>
		<font size=\"4\">".$user['name']."</font><br>".$URLs."<br><font size=\"2\">User ID: ".$user['id']."<br>
			 <b>".$user['follower_count']."</b> followers <br>".$URLs2."</font></td>
		</tr>
		
        
		<tr><td>
		".$whatido." <p>
		".$whativebuilt." <p>
		".$criteria." <p>
		".$location_of_person."<br>
		".$roles_of_person."<br>
		".$skills_of_person."<p>
		".$investor."
		</td><br></tr>";
		
	
	}

	print "</table><p><font size=2>Created by Dewal Gupta</font>";
	

} catch (PDOException $pe) { //if it couldn't connect
	die("DB ERROR: " . $pe->getMessage());
}

$db = null;

?>

</body>
</html>
