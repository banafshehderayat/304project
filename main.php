
<html>
<p>If you wish to reset the table press on the reset button. If this is the first time you're running this page, you MUST use reset</p>
<form method="POST" action="oracle-test.php">
   
<p><input type="submit" value="Reset" name="reset"></p>
</form>

<p>Insert values into tab1 below:</p>
<p><font size="2"> Number&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 
Name</font></p>
<form method="POST" action="oracle-test.php">
<!--refresh page when submit-->

   <p><input type="text" name="insNo" size="6"><input type="text" name="insName" 
size="18">
<!--define two variables to pass the value-->
      
<input type="submit" value="insert" name="insertsubmit"></p>
</form>
<!-- create a form to pass the values. See below for how to 
get the values--> 

<p> Update the name by inserting the old and new values below: </p>
<p><font size="2"> Old Name&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 
New Name</font></p>
<form method="POST" action="oracle-test.php">
<!--refresh page when submit-->

   <p><input type="text" name="oldName" size="6"><input type="text" name="newName" 
size="18">
<!--define two variables to pass the value-->
      
<input type="submit" value="update" name="updatesubmit"></p>
<input type="submit" value="run hardcoded queries" name="dostuff"></p>
</form>

<?php

$db_conn = OCILogon("ora_b9y8", "a38319125", "ug");

if ($db_conn) {
	echo "Successfully connected to Oracle. \n";
	OCILogoff($db_conn);
}
else {
	$err = OCIError();
	echo "Oracle Connect Error" . $err['message'];
}

?>
</html>