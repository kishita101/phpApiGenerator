<?php

class IndexGenerator {

	private $AlltableDetails;
	private $path;

	public function setTableDetails($path, $AlltableDetails){
		$this->path = $path;
		$this->AlltableDetails = $AlltableDetails;
	}
	public function generateIncludeControllers(){
		$Alltables = $this->AlltableDetails;
		$includes = "";	
		foreach($Alltables as $table) {
			$includes .="
	include_once __DIR__.'/api/controllers/".$table->className."Controller.php';";
		}
		return $includes;
	}

	public function generateFile(){
		$index =
"<?php
	
	use \\Psr\\Http\\Message\\ServerRequestInterface as Request;
	use \\Psr\\Http\\Message\\ResponseInterface as Response;
	require 'vendor/autoload.php';
	";
		$index .= $this->generateIncludeControllers();
		
		$index .= 
"
	\$configuration = [
		'settings' => [
			'displayErrorDetails' => true,
		],
	];
	\$c = new \\Slim\\Container(\$configuration);
	\$app = new \\Slim\\App(\$c);
	
	\$app->add( function(\$request, \$response, \$next)
    {
		\$response = \$response->withHeader('Content-type', 'application/json');
        \$response = \$next(\$request, \$response);
        return \$response;
	}
	);	
	
	\$app->add(function (\$req, \$res, \$next) {
		\$response = \$next(\$req, \$res);
		return \$response
		->withHeader('Access-Control-Allow-Origin', 'http://localhost')
		->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
		->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
	});
	\$app->group('/api'	, function(Slim\\App \$app){
		\$app->group('/v1'	, function(Slim\\App \$app){";
	
		$index .= $this->generateMapping();
		
		$index .= 
"
		});
	});
	error_reporting(E_ERROR | E_WARNING);
	ini_set('display_errors', FALSE); 
	ini_set('log_errors', TRUE); 
	\$app->run();";
	
	$file = fopen($this->path."/index.php", "w") or die("Unable to open file!");
	fwrite($file, $index);
	fclose($file);
	echo "<br/>Index file generated Successfully!";

	}	
	
	public function generateMapping(){
		$Alltables = $this->AlltableDetails;
		$map = "";	
		foreach($Alltables as $table) {
			$filename = $table->className;
			$object_name = lcfirst($filename);
			$map .=
"
			\$app->get('/{$object_name}s', function (Request \$request, Response \$response, array \$args) {
				\$controller = new {$filename}Controller();
				\$controller->read();
			});
			\$app->get('/{$object_name}/{id}', function (Request \$request, Response \$response, array \$args) {
				\$id = \$args['id'];
				\$controller = new {$filename}Controller();
				\$controller->readById(\$id);
			});
			\$app->post('/{$object_name}', function (Request \$request, Response \$response, array \$args) {
				\$data = \$request->getParsedBody();
				\$controller = new {$filename}Controller();
				\$controller->create(\$data);
			});
			\$app->put('/{$object_name}/{id}', function (Request \$request, Response \$response, array \$args) {
				\$id = \$args['id'];
				\$data = \$request->getParsedBody();
				\$controller = new {$filename}Controller();
				\$controller->update(\$id,\$data);
			});
			\$app->delete('/{$object_name}/{id}', function (Request \$request, Response \$response, array \$args) {
				\$id = \$args['id'];
				\$data = \$request->getParsedBody();
				\$controller = new {$filename}Controller();
				\$controller->delete(\$id);
			});
";
		}
		return $map;

	}
	
	
}