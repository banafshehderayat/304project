<html>
    <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="css/custom.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
    <script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
<body>
<nav class="navbar navbar-default">
    <div class="navbar-header">
      <a class="navbar-brand" href="employee.php">Employee Console</a>
      <?php
        if(session_status() == PHP_SESSION_NONE) {
            session_save_path("php_sessions");
            session_start();
        }

        if ($_SESSION['user_type'] == 'MANAGER') {
          echo '<a class="navbar-brand" href="manager.php">Go to Manager Page</a>';
        }
     ?>
     <a class="navbar-brand" href="login.php?action=logout">Log out</a>
    </div>
</nav>
<div class="container">
<form method="POST" action="employee.php">
    	<div class="form-inline">
    	<select name="loc" class="form-control" style="width:200px">
    		  		<?php
    		  			require_once 'util.php';
    		  			$util2 = new Util;
      					$debug = False;
      					if ($debug) {
      					}
      		  		$db_conn = OCILogon("ora_j7l8", "a31501125", "ug");
      					if ($db_conn) {
      						$result = $util2->executePlainSQL("select * from location");
      						$util2->printResultDropdown($result, 'LOCATION_ADDRESS');
      						OCILogoff($db_conn);
      					}
    				?>
    		</select>
            <input type="submit" value="View Rooms" name="viewRooms" class="btn btn-default .pull-right"></p>
    	</div>
    </div>
</form>

<div class="container">
    <?php
    error_reporting(-1);
    ini_set('display_errors',1);

    require_once 'util.php';
    $util = new Util;
    $debug = False;
    // User is not logged in; redirect to login page
    if(session_status() == PHP_SESSION_NONE) {
            session_save_path("php_sessions");
            session_start();
    }
    
    if (empty($_SESSION['user_is_logged_in']) || !($_SESSION['user_is_logged_in']) || ($_SESSION['user_type'] == 'CUSTOMER')) {
    	 	echo "<meta http-equiv=\"refresh\" content=\"0; URL='login.php?action=logout'\" />";
    	 	return;
    }

    $db_conn = OCILogon("ora_j7l8", "a31501125", "ug");
    if ($db_conn) {
            if ($debug) {
                    echo "Successfully connected to Oracle. \n";
            }

    		if (array_key_exists('viewRooms', $_POST)) {

              			$statement = "SELECT * FROM rooms where location_address = :bind";
                              	$stid = oci_parse($db_conn, $statement);
                              	$bind = $_POST['loc'];
                              	OCIBindByName($stid, ':bind', $bind);
                              	OCIExecute($stid);
                              	$util->printResultTable($stid , ["ROOM_NUMBER", "LOCATION_ADDRESS", "TYPE", "MAX_OCCUPANCY", "COST_PER_DAY"]);
                              	OCICommit($db_conn);
            } 
    	   echo '<a href="updateRooms.php" class="btn btn-default"> Update Rooms</a>';

            OCILogoff($db_conn);

          } else {
            $err = OCIError();
            echo "Oracle Connect Error" . $err['message'];
    }
    ?>
</div>
</body>
</html>
