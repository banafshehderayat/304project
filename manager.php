<html>
	<form method="POST" action="manager.php">
	<div style="border: 1px solid black;">
		Employee Name: <input type="text" name="empName">
		Location Address: <select name="empLoc">
		  		<?php 
		  			require_once 'util.php';
		  			$util2 = new Util;
					$debug = True;
		  			$db_conn = OCILogon("ora_j7l8", "a31501125", "ug");
					if ($db_conn) {
						$result = $util2->executePlainSQL("select * from location");
						$util2->printResultDropdown($result, 'LOCATION_ADDRESS');
						OCILogoff($db_conn);
					}
				?>
			</select>
		Manager ID: <input type="text" name="manId">
		Password: <input type="text" name="pass" >
        	<input type="submit" value="Add Employee" name="addEmployee"></p>		
	</div>
	<div style="border: 1px solid black;">
		Employee ID:<select name="eID">
		  		<?php 
		  			require_once 'util.php';
		  			$util2 = new Util;
					$debug = True;
		  			$db_conn = OCILogon("ora_j7l8", "a31501125", "ug");
					if ($db_conn) {
						$result = $util2->executePlainSQL("select employee_id from employee");
						$util2->printResultDropdown($result, 'EMPLOYEE_ID');
						OCILogoff($db_conn);
					}
				?>
			</select>
        	<input type="submit" value="Find Employee" name="findEmployee" ></p>		
	</div>
	

       	Find customers that have booked all rooms in a locations:
	<select name="custLoc">
		  		<?php 
		  			require_once 'util.php';
		  			$util2 = new Util;
					$debug = True;
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
	<input type="submit" value="Find Customer" name="findCust"></p>	

	Find the location with the highest/lowest average price for rooms: 
	 <input type="radio" name="order" value="MIN"> Min
	 <input type="radio" name="order" value="MAX"> Max
	<input type="submit" value="Find Price" name="findExpensiveLoc"></p>
	
	
	</form>

	<?php
		error_reporting(E_ERROR);
		ini_set('display_errors',1);

		require_once 'util.php';
		$util = new Util;
		$debug = False;
		// User is not logged in; redirect to login page
		session_save_path("php_sessions");
        	session_start();
	 	if (empty($_SESSION['user_is_logged_in']) || !($_SESSION['user_is_logged_in']) || ($_SESSION['user_type'] != 'MANAGER')) {
	 		echo "<meta http-equiv=\"refresh\" content=\"0; URL='login.php?action=logout'\" />";
	 		return;
	 	}
		$db_conn = OCILogon("ora_j7l8", "a31501125", "ug");
		if ($db_conn) {
        		if ($debug) {
               		echo "Successfully connected to Oracle. \n";
        		}
				if (array_key_exists('findEmployee', $_POST)){
					$statement = "SELECT * FROM employee where EMPLOYEE_ID = :bind";
                			$stid = oci_parse($db_conn, $statement);
                			$bind = $_POST['eID'];
               				OCIBindByName($stid, ':bind', $bind);
                			$r = OCIExecute($stid);
					if ($r == FALSE){

								echo '<script type="text/javascript">'. 'alert("The employee ID you have entered is incorrect.")</script>';
							} else {
					
					//$util->printResultTable($stid , ["EMPLOYEE_ID", "NAME", "LOCATION_ADDRESS", "MANAGER_ID"]);
					$row = OCI_Fetch_Array($stid);
					echo '<form name="form1" method="post" action="">';
					echo "<table>";
        				echo "<tr><th>employee_id</th><th>name</th><th>location_address</th><th>manager_id</th></tr>";
					echo "<tr><td><input type='text' name='eid' value='" . $row["EMPLOYEE_ID"] . "' readonly></td><td><input type='text' name='name' value='" . $row["NAME"] . "'></td><td><input type='text'name='loc' value='" .$row["LOCATION_ADDRESS"]."'></td><td><input type='text'name='mid' value='" .$row["MANAGER_ID"]."' readonly></td></tr>"; 
					
        				echo "</table>";
					echo "<br>";
					echo "<input type='submit' value='update' name='update'>";
					echo "<input type='submit' value='delete' name='delete'>";
					echo "</form>";
                			OCICommit($db_conn);
					}
				} else  
					if (array_key_exists('update', $_POST)){
						
							$statement = "UPDATE employee SET NAME = :bind1 , LOCATION_ADDRESS = :bind2 
							WHERE EMPLOYEE_ID = :bind3";
							$stid = oci_parse($db_conn, $statement);
							
							$bind1 = $_POST['name'];
                					$bind2 = $_POST['loc'];
							$bind3 = $_POST['eid'];
                					OCIBindByName($stid, ':bind1', $bind1);
                					OCIBindByName($stid, ':bind2', $bind2);
							OCIBindByName($stid, ':bind3', $bind3);
               						$r = OCIExecute($stid);
							if ($r == FALSE){

								echo '<script type="text/javascript">'. 'alert("The employee information you have entered is incorrect.")</script>';
							} else{
								echo "Table Updated: " . "employee: " . $bind1 . " has been updated!";
                						OCICommit($db_conn);
							}
							
										
					} else 
						if (array_key_exists('delete', $_POST)) {
							$disable = "ALTER TABLE employee DISABLE PRIMARY KEY CASCADE";
							$d = oci_parse($db_conn, $disable);
							OCIExecute($d);
							$statement = "DELETE from employee WHERE EMPLOYEE_ID = :bind";
							$stid = oci_parse($db_conn, $statement);
							$bind = $_POST['eid'];	
							OCIBindByName($stid, ':bind', $bind);
							OCIExecute($stid);
							$enable = "ALTER TABLE employee ENABLE PRIMARY KEY";
							$e = oci_parse($db_conn, $enable);
							OCIExecute($e);
							OCICommit($db_conn);
						} else 
							if (array_key_exists('addEmployee', $_POST)){
								$statement = "INSERT into employee ( NAME, LOCATION_ADDRESS, MANAGER_ID, PASSWORD)		
								values ( :bind1 , :bind2, :bind3, :bind4)";
								$stid = oci_parse($db_conn, $statement);
								
                						$bind1 = $_POST['empName'];
								$bind2 = $_POST['empLoc'];
								$bind3 = $_POST['manId'];
								$bind4 = $_POST['pass'];	
								
								OCIBindByName($stid, ':bind1', $bind1);
								OCIBindByName($stid, ':bind2', $bind2);
								OCIBindByName($stid, ':bind3', $bind3);
								OCIBindByName($stid, ':bind4', $bind4);
								$r = OCIExecute($stid);
								if ($r == FALSE){
									echo '<script type="text/javascript">'. 'alert("The employee information you have entered is invalid.")</script>';
								} else{ OCICommit($db_conn);
									}
									
							} else 
								if(array_key_exists ('findCust', $_POST)){
									$statement = "SELECT cname FROM customers where NOT EXISTS ((select room_number from location Natural JOIN rooms where location_address=:bind1) MINUS (select room_number from reserves where reserves.name = customers.cname and reserves.address = customers.address))";
											$stid = oci_parse($db_conn, $statement);											
											$bind1 = $_POST['custLoc'];
											OCIBindByName($stid, ':bind1', $bind1);
											OCIExecute($stid);
											$util->printResultTable($stid , ["CNAME"]);
											OCICommit($db_conn);
								} else
									 if (array_key_exists ('findExpensiveLoc' , $_POST)){
										$bind = $_POST['order'];
										if ($bind == 'MAX'){
											$statement = "select AVG(cost_per_day), location_address from rooms group by 											location_address HAVING AVG(cost_per_day) = (select MAX(AVG(cost_per_day)) from rooms 											group by location_address)";									
										} else {
											$statement = "select AVG(cost_per_day), location_address from rooms group by 											location_address HAVING AVG(cost_per_day) = (select MIN(AVG(cost_per_day)) from rooms 											group by location_address)";
										}
										
										$stid = oci_parse($db_conn, $statement);
										
										OCIExecute($stid);
										
										$row = OCI_Fetch_Array($stid, OCI_BOTH);
										echo "average is : " . $row[0] . " for location: " . $row[1] ;
										OCICommit($db_conn);
								}
			
			echo '<br> <a href="employee.php"> go to employee page</a>';
        		OCILogoff($db_conn);
		}else {
        		$err = OCIError();
        		echo "Oracle Connect Error" . $err['message'];
		}		

	?>
</html>
