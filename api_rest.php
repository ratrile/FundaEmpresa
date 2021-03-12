<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;




/*LLAMADO A LAS FUNCIONES DEL SLIM*/
	date_default_timezone_set('America/Mexico_City');
	require './vendor/autoload.php';
  // $app = new \Slim\App;
  $app = new \Slim\App(['settings' => ['displayErrorDetails' => true]]);

	// $app->setBasePath("/API/api_rest.php");
	//$app->setBasePath("./api_rest.php");
	// $app->addRoutingMiddleware();
	// $errorMiddleware = $app->addErrorMiddleware(true, true, true);
	$app->get('/', function (Request $request, Response $response, $args) {
		$response->getBody()->write("Hello world!");
		return $response;
	});

/*CONEXION DE LA BASE DE DATOS*/
	require 'conexion.php';
	define("error_keys", $error);
	define("value_credential", $valuecredential);
	//define("PATHIMAGEN", "http://medicapp.clanbolivia.tech/IMGUSERS/");

	//GETS

	$app->get('/getTest','getTest');

	function getTest(){
		$db = getConnection();
		if($db){
			echo 'Conexion Exitosa: ';
		}else{
			echo 'No se pudo conectar: ';
		}
	}

	// require 'function/generalFunction.php';


//   require 'function/functionWSadmin.php';
	require 'function.php';
	// require 'function/functionWSConductor.php';
	// require 'function/functionWS.php';

	$app->run();

