<?php include 'process.php'; ?>
<!DOCTYPE html>
<html>
<head>
<title>
Pick a Color - PHP Example
</title>
</head>
<body>
<form action='' method='POST'>
<p><label>Pick a Color:</label></p>
<select name='color'>
<option id='0' value='red'>Red</option>
<option id='1' value='green'>Green</option>
<option id='2' value='blue'>Blue</option>
</select>
<input type='submit' name='save'/>
</form>
<?php if ($_error) {echo $_error . PHP_EOL;}?>
</body>
</html>