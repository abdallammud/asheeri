<?php 
if(isset($_GET['action'])) {
	if($_GET['action'] == 'login') {
		$result['msg'] = 'Correct action';
		$result['status'] = 201;

		$username = $_POST['username'];
	    $password = $_POST['password'];

	    $getUser = "SELECT * FROM `users` WHERE (`username` = '$username' OR `email` LIKE '$username')";
	    $userSet = $GLOBALS['conn']->query($getUser);
	    if($userSet->num_rows < 1) {
	        $result['error'] = true;
	        $result['errType']  = 'username';
	        $result['msg'] = ' Username is not found.';
	        echo json_encode($result); 
	        exit();
	    }

	    while($row = $userSet->fetch_assoc()) {
	        $user_id    = $row['user_id'];
	        $passDB     = $row['password'];
	        $status     = $row['status'];
	        $role     	= $row['role'];
	        $full_name  = $row['full_name'];
	        $language  	= $row['language'];

	        $user_actions     = $row['user_actions'];
	        $user_privileges  = $row['user_privileges'];

	        if (!password_verify($password, $passDB)) {
	            $result['error'] = true;
	            $result['errType']  = 'password';
	            $result['msg'] = ' Incorrect password.';
	            echo json_encode($result); 
	            exit();
	        }

	        if(strtolower($status) != 'active') {
	            $result['error'] = true;
	            $result['errType']  = 'username';
	            $result['msg'] = ' Inactive user. Please contact system adminstrator.';
	            echo json_encode($result); 
	            exit();
	        }
	    }

	    if(set_sessions($username, $user_actions, $user_privileges, $role, $full_name, $user_id, $language)) {
	        setLoginInfo($user_id);
	    } else {
	        $result['msg']    = ' Couln\'t set sessions.';
	        $result['error'] = true;
	        $result['errType']  = 'sessions';
	        echo json_encode($result); exit();
	    }

	    $result['msg'] = "Succefully logged in.";
	    $result['error'] = false;
	    $result['actions'] = $user_actions;
	    $result['privilegs'] = strtolower($user_privileges);
	    echo json_encode($result); exit(); 

	} else if($_GET['action'] == 'language') {
		$user_id = $_SESSION['user_id'];
		$lang = $_POST['lang'];
		$stmt = $GLOBALS['conn']->prepare("UPDATE `users` SET `language` =? WHERE `user_id` = ?");
    	$stmt->bind_param("ss", $lang, $user_id);
    	if($stmt->execute()) {
    		$getUser = "SELECT * FROM `users` WHERE `user_id` = '$user_id'";
		    $userSet = $GLOBALS['conn']->query($getUser);
		    while($row = $userSet->fetch_assoc()) {
		        $user_id    = $row['user_id'];
		        $username   = $row['username'];
		        $passDB     = $row['password'];
		        $status     = $row['status'];
		        $role     	= $row['role'];
		        $full_name  = $row['full_name'];
		        $language  	= $row['language'];

		        $user_actions     = $row['user_actions'];
		        $user_privileges  = $row['user_privileges'];
		    }

		    if(set_sessions($username, $user_actions, $user_privileges, $role, $full_name, $user_id, $language)) {
		        setLoginInfo($user_id);
		    } else {
		        $result['msg']    = ' Couln\'t set sessions.';
		        $result['error'] = true;
		        $result['errType']  = 'sessions';
		        echo json_encode($result); exit();
		    }
    		echo 'changed';
    	} else {echo $stmt->error;}
	}
}


function set_sessions($username, $actions, $privileges, $role = 'user', $fullName = '', $user_id = '', $language = 'en') {
	$_SESSION['role'] = $role;
	$_SESSION['myUser'] = $username;
	$_SESSION['fullName'] = $fullName;
	$_SESSION['user_id'] = $user_id;
	$_SESSION['language'] = $language;
	$_SESSION['isLogged'] = true;

	$actions 	= explode(",", $actions);
	$privileges = explode(",", $privileges);

	$_SESSION['dashboard'] = 'off';
	$_SESSION['books'] = 'off';
	$_SESSION['customers'] = 'off';
	$_SESSION['categories'] = 'off';
	$_SESSION['users'] = 'off';
	$_SESSION['transactions'] = 'off';
	$_SESSION['reports'] = 'off';

	$_SESSION['create'] = 'off';
	$_SESSION['update'] = 'off';
	$_SESSION['delete'] = 'off';

	if(in_array(ucfirst("dashboard"), $privileges)) $_SESSION['dashboard'] = 'on';
	if(in_array(ucfirst("books"), $privileges)) $_SESSION['books'] = 'on';
	if(in_array(ucfirst("customers"), $privileges)) $_SESSION['customers'] = 'on';
	if(in_array(ucfirst("categories"), $privileges)) $_SESSION['categories'] = 'on';
	if(in_array(ucfirst("users"), $privileges)) $_SESSION['users'] = 'on';
	if(in_array(ucfirst("transactions"), $privileges)) $_SESSION['transactions'] = 'on';
	if(in_array(ucfirst("reports"), $privileges)) $_SESSION['reports'] = 'on';

	if(in_array("create", $actions)) $_SESSION['create'] = 'on';
	if(in_array("update", $actions)) $_SESSION['update'] = 'on';
	if(in_array("delete", $actions)) $_SESSION['delete'] = 'on';

	// $_SESSION['role'] = 'admin';
	return true;
}

function reload() {
	if(!isset($_SESSION['myUser']) || !$_SESSION['myUser']) {
        return false;
    }

    $username = $_SESSION['myUser'];

    $getUser = "SELECT * FROM `users` WHERE (`username` = '$username' OR `email` LIKE '$username') AND `status` NOT IN ('deleted')";
    $userSet = $GLOBALS['conn']->query($getUser);
    $status = '';
    while($row = $userSet->fetch_assoc()) {
        $user_id    = $row['user_id'];
        $passDB     = $row['password'];
        $status     = $row['status'];
        $role     	= $row['role'];
        $full_name  = $row['full_name'];
        $language  = $row['language'];
        $user_actions  		= $row['user_actions'];
        $user_privileges  	= $row['user_privileges'];
    }

    if(strtolower($status) != 'active') {
    	$_SESSION['isLogged'] = false;
    	return;
    }

    set_sessions($username, $user_actions, $user_privileges, $role, $full_name, $user_id, $language); 
}

function setLoginInfo($userID, $logout = false) {
    $this_time = date('Y-m-d h:i:s');
    $is_logged = 'yes';
    $column = 'this_time';
    if($logout) { $is_logged = 'no'; $column = 'last_logged';}
    $stmt = $GLOBALS['conn']->prepare("UPDATE `users` SET `is_logged` = ?, `$column` = ? WHERE `user_id` = ?");
    $stmt->bind_param("sss", $is_logged, $this_time, $userID);
    if(!$stmt->execute()) {
        echo $stmt->error;
    }
}


?>