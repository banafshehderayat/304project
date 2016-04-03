<html>
	<form method="POST" action="manager.php">
	<div style="border: 1px solid black;">
		Employee ID: <input type="text" name="empId">
		Employee Name: <input type="text" name="empName">
		Location Address: <select name="empLoc">
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
		Manager ID: <input type="text" name="manId">
		Password: <input type="text" name="pass">
        	<input type="submit" value="Add Employee" name="addEmployee"></p>		
	</div>
	<div style="border: 1px solid black;">
		Employee ID: <input type="text" name="eID">
        	<input type="submit" value="Find Employee" name="findEmployee"></p>		
	</div>
	
	</form>

	<?php
		error_reporting(-1);
		ini_set('display_errors',1);

		require_once 'util.php';
		$util = new Util;
		$debug = False;

		$db_conn = OCILogon("ora_j7l8", "a31501125", "ug");
		if ($db_conn) {
        		if ($debug) {
               		echo "Successfully connected to Oracle. \n";
        		}
				if (array_key_exists('findEmployee', $_POST)){
					$statement = "SELECT employee_id, name, location_address, manager_id 
							  	  FROM employee 
							  	  WHERE EMPLOYEE_ID = :bind";
                			$stid = oci_parse($db_conn, $statement);
                			$bind = $_POST['eID'];
               				OCIBindByName($stid, ':bind', $bind);
                			OCIExecute($stid);
					
					$employee = OCI_Fetch_Array($stid);

					// Get manager name
					$manStatement = "SELECT name
									 FROM employee
									 WHERE employee_id = :manID";
					$st = oci_parse($db_conn, $manStatement);
					OCIBindByName($st, ':manID', $employee["MANAGER_ID"]);
					OCIExecute($st);
					$manager = OCI_Fetch_Array($st);

					echo '<form name="form1" method="post" action="">';
					echo "<table>";
        				echo "<tr><th>Employee ID</th><th>Name</th><th>Location Address</th><th>Manager Name</th></tr>";
					echo "<tr><td><input type='text' name='eid' value='" . $employee["EMPLOYEE_ID"] . "' readonly></td>
						      <td><input type='text' name='name' value='" . $employee["NAME"] . "'></td>
						      <td><input type='text'name='loc' value='" .$employee["LOCATION_ADDRESS"]."'></td>
						      <td><input type='text'name='manName' value='" .$manager["NAME"]."' readonly></td>
						  </tr>"; 
					
        				echo "</table>";
					echo "<br>";
					echo "<input type='submit' value='update' name='update'>";
					echo "<input type='submit' value='delete' name='delete'>";
					echo "</form>";
                	OCICommit($db_conn);
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
               				$success = @OCIExecute($stid);
							
							if ($success) {
								echo '<script type="text/javascript" >alert("Employee: ' . $bind1 . ' has been updated!"); </script>';
							}
							else {
								echo '<script type="text/javascript" >alert("Location \"' . $bind2 . '\" is not valid"); </script>';
							}
							
                			OCICommit($db_conn);
										
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
								$statement = "INSERT into employee (EMPLOYEE_ID, NAME, LOCATION_ADDRESS, MANAGER_ID, PASSWORD)		
								values (:bind1 , :bind2 , :bind3, :bind4, :bind5)";
								$stid = oci_parse($db_conn, $statement);
								$bind1 = $_POST['empId'];
                						$bind2 = $_POST['empName'];
								$bind3 = $_POST['empLoc'];
								$bind4 = $_POST['manId'];
								$bind5 = $_POST['pass'];	
								OCIBindByName($stid, ':bind1', $bind1);
								OCIBindByName($stid, ':bind2', $bind2);
								OCIBindByName($stid, ':bind3', $bind3);
								OCIBindByName($stid, ':bind4', $bind4);
								OCIBindByName($stid, ':bind5', $bind5);
								OCIExecute($stid);
								OCICommit($db_conn);	
							}


        		OCILogoff($db_conn);
		}else {
        		$err = OCIError();
        		echo "Oracle Connect Error" . $err['message'];
		}		

	?>
</html>
