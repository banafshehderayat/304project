
<html>
<form method="POST" action="main.php">
	Customer Name: <input type="text" name="custName"> <br>
	Customer Address: <input type="text" name="custAddr"> <br>
	<input type="submit" value="Add Customer" name="addCust"></p>
</form>


<?php

include 'util.php';
$util = new util();

// function executeBoundSQL($cmdstr, $list) {
// 	/* Sometimes a same statement will be excuted for severl times, only
// 	 the value of variables need to be changed.
// 	 In this case you don't need to create the statement several times; 
// 	 using bind variables can make the statement be shared and just 
// 	 parsed once. This is also very useful in protecting against SQL injection. See example code below for       how this functions is used */

// 	global $db_conn, $success;
// 	$statement = OCIParse($db_conn, $cmdstr);

// 	if (!$statement) {
// 		echo "<br>Cannot parse the following command: " . $cmdstr . "<br>";
// 		$e = OCI_Error($db_conn);
// 		echo htmlentities($e['message']);
// 		$success = False;
// 	}

// 	foreach ($list as $tuple) {
// 		foreach ($tuple as $bind => $val) {
// 			//echo $val;
// 			//echo "<br>".$bind."<br>";
// 			OCIBindByName($statement, $bind, $val);
// 			unset ($val); //make sure you do not remove this. Otherwise $val will remain in an array object wrapper which will not be recognized by Oracle as a proper datatype

// 		}
// 		$r = OCIExecute($statement, OCI_DEFAULT);
// 		if (!$r) {
// 			echo "<br>Cannot execute the following command: " . $cmdstr . "<br>";
// 			$e = OCI_Error($statement); // For OCIExecute errors pass the statementhandle
// 			echo htmlentities($e['message']);
// 			echo "<br>";
// 			$success = False;
// 		}
// 	}
// }

// function executePlainSQL($cmdstr) { //takes a plain (no bound variables) SQL command and executes it
// 	//echo "<br>running ".$cmdstr."<br>";
// 	global $db_conn, $success;
// 	$statement = OCIParse($db_conn, $cmdstr); //There is a set of comments at the end of the file that describe some of the OCI specific functions and how they work

// 	if (!$statement) {
// 		echo "<br>Cannot parse the following command: " . $cmdstr . "<br>";
// 		$e = OCI_Error($db_conn); // For OCIParse errors pass the       
// 		// connection handle
// 		echo htmlentities($e['message']);
// 		$success = False;
// 	}

// 	$r = OCIExecute($statement, OCI_DEFAULT);
// 	if (!$r) {
// 		echo "<br>Cannot execute the following command: " . $cmdstr . "<br>";
// 		$e = oci_error($statement); // For OCIExecute errors pass the statementhandle
// 		echo htmlentities($e['message']);
// 		$success = False;
// 	} else {

// 	}
// 	return $statement;
// }

// function printResult($result) { //prints results from a select statement
// 	echo "<br>Got data from table customers:<br>";
// 	echo "<table>";
// 	echo "<tr><th>cname</th><th>address</th></tr>";

// 	while ($row = OCI_Fetch_Array($result, OCI_BOTH)) {
// 		// Row indices MUST BE IN CAPS
// 		echo "<tr><td>" . $row["CNAME"] . "</td><td>" . $row["ADDRESS"] . "</td></tr>"; //or just use "echo $row[0]" 
// 	}
// 	echo "</table>";

// }


$debug = True;
$db_conn = OCILogon("ora_b9y8", "a38319125", "ug");
if ($db_conn) {
	
	if ($debug) {
		echo "Successfully connected to Oracle. \n";
		$result = $util->executePlainSQL("select * from customers");
		printResult($result);
	}

	if (array_key_exists('addCust', $_POST)) {
		$tuple = array (
				":bind1" => $_POST['custName'],
				":bind2" => $_POST['custAddr']
			);
		$allTuple = array (
			$tuple
		);

		$util->executeBoundSQL("insert into customers values (:bind1, :bind2)", $allTuple);
		OCICommit($db_conn);
		echo "Customer added\n";
	}

	OCILogoff($db_conn);
}
else {
	$err = OCIError();
	echo "Oracle Connect Error" . $err['message'];
}

?>
</html>