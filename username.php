<?php

	// Keep track of users accessing the site in order to be able to do temporary blocking of usernames for 6 minutes
	session_start();
	$sess_id = session_id();
	
	if (isset($_REQUEST["reserve"]))
	{	// We clicked on reserve; we will reserve that user in the database
		$reserve_uname = true;
		$uname = ($_REQUEST["reserve"]);
	} else
	{	// We still haven't decided on the username, we will temporary block it in the username_temp table
		$reserve_uname = false;
		$uname = $_REQUEST["uname"];
	}

	// This function will generate a JSON response to the jQuery script, with statuses on the username reservation
	function generate_response($response)
	{
		if(ob_get_length()) {
			ob_clean();
		}
		header('Content-Type: application/json');
		echo json_encode($response);
		ob_end_flush();
		exit();
	}
	
	try {
		// Connect to the database using PHP Data Objects
		// Replce the following with your database information:
		// <database> - database name
		// <username> - database user name
		// <password> - database password
		$conn = new PDO('mysql:host=localhost;dbname=<database>', '<username>', '<password>');

		// We first check if the username has already been reserved
		$query = $conn->prepare('SELECT * FROM username WHERE uname = ?');
		if ($query->execute(array($uname))) {
			if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
				// Another user has taken this username
				$res["res"]="taken";
			
				$result = null;
				$conn = null;
				generate_response($res);
			} else {
				// We check if the username is temporary blocked by another user
				$query = $conn->prepare('SELECT * FROM username_temp WHERE uname = ?');
				if ($query->execute(array($uname))) {
					if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
						if (time()>$row["timestamp"] || ($sess_id == $row["sess_id"])) {
							// 6 minutes have passed but we have the same session id, so we can still rserve this username
							
							if ($reserve_uname) {
								// Make this username final, reserve it on the main table
								$query = $conn->prepare('INSERT INTO username (uname) VALUES(?)');
								$result = $query->execute(array($uname));
								
								// Delete the record from the temporary table
								$query = $conn->prepare('DELETE FROM username_temp WHERE uname = ?');
								$result = $query->execute(array($uname));
								$res["res"]="reserved";
				
								$result = null;
								$conn = null;
								generate_response($res);
							} else {
								// We are still haven't determined what username we want, so currently we renew the the temporary reservation
								$new_ts = time()+360;
								$query = $conn->prepare('UPDATE username_temp SET sess_id = ?, timestamp = ? WHERE uname = ?');

								$result = $query->execute(array($sess_id,$new_ts,$uname));
								
								$res["res"]="avail";
								
								$result = null;
								$conn = null;
								generate_response($res);
							}	
						} else {
							// Another user has taken this username
							$res["res"]="taken";
								
							$result = null;
							$conn = null;
							generate_response($res);
						}
					} else {
						// username is available, lets keep it for 6 minutes
						$ts = time()+360;
						$query = $conn->prepare('INSERT INTO username_temp (uname,sess_id,timestamp) VALUES (?,?,?)');
	
						$result = $query->execute(array($uname,$sess_id,$ts));
						$res["res"]="avail";
						
						// clear old records, all expired records will be removed
						$sql = "DELETE FROM username_temp WHERE timestamp < '".time()."'";
 						$result = $conn->exec($sql);
						
						$result = null;
						$conn = null;
						generate_response($res);
					}
				}
			}
		} else {
			$res["res"]="error";
			
			$result = null;
			$conn = null;
			generate_response($res);
		}
	} catch (PDOException $e) {

		$res["res"]="error";
			
		$result = null;
		$conn = null;
		generate_response($res);
		die();
	}
	
?>