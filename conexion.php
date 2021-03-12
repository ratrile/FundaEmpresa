<?php
function getConnection() {
	
	
	// $dbhost="localhost";
	// $dbuser="rigoarri_reggat";
	// $dbpass='reggat123';
	// $dbname="rigoarri_reggat";


	$dbhost="134.209.73.127:3306";
 	$dbuser="userappreggat";
 	$dbpass='R33g4t30*s';
  	$dbname="appreggat";
	$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
	$dbh->exec("SET time_zone='-4:00';");
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $dbh;
}
// $rango = getRangoBusqueda();
// AND distancia<=:rango
// $stmt ->bindParam('rango', $rango);
?>
