<?php
$this->breadcrumbs=array(
	'Members',
);

?>

<h1>Import Completed</h1>

<h3>Successfully Added</h3>
<?php
echo '<p>Items added: '.$count_added.'</p>';
?>

<?php

echo $added;
?>

<h3>Not Added due to Errors</h3>
<?php
echo '<p>Items with errors: '.$count_errors.'</p>';
?>
 <?php
 echo $errors;
 ?>
