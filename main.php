
<html>
<form method="POST" action="main.php">
	Customer Name: <input type="text" name="custName"> <br>
	Customer Address: <input type="text" name="custAddr"> <br>
	<input type="submit" value="Add Customer" name="addCust"></p>
</form>


<?php

function executeBoundSQL($cmdstr, $list) {
	/* Sometimes a same statement will be excuted for severl times, only
	 the value of variables need to be changed.
	 In this case you don't need to create the statement several times; 
	 using bind variables can make the statement be shared and just 
	 parsed once. This is also very useful in protecting against SQL injection. See example code below for       how this functions is used */

	global $db_conn, $success;
	$statement = OCIParse($db_conn, $cmdstr);

	if (!$statement) {
		echo "<br>Cannot parse the following command: " . $cmdstr . "<br>";
		$e = OCI_Error($db_conn);
		echo htmlentities($e['message']);
		$success = False;
	}

	foreach ($list as $tuple) {
		foreach ($tuple as $bind => $val) {
			//echo $val;
			//echo "<br>".$bind."<br>";
			OCIBindByName($statement, $bind, $val);
			unset ($val); //make sure you do not remove this. Otherwise $val will remain in an array object wrapper which will not be recognized by Oracle as a proper datatype

		}
		$r = OCIExecute($statement, OCI_DEFAULT);
		if (!$r) {
			echo "<br>Cannot execute the following command: " . $cmdstr . "<br>";
			$e = OCI_Error($statement); // For OCIExecute errors pass the statementhandle
			echo htmlentities($e['message']);
			echo "<br>";
			$success = False;
		}
	}

}



$db_conn = OCILogon("ora_b9y8", "a38319125", "ug");
if ($db_conn) {
	echo "Successfully connected to Oracle. \n";

	if (array_key_exists('addCust', $_POST)) {
		$tuple = array (
				":bind1" => $_POST['custName'],
				":bind2" => $_POST['custAddr']
			);
		$allTuple = array (
			$tuple
		);

		executeBoundSQL("insert into customers values (:bind1, :bind2)", $allTuple);
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