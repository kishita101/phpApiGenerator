<?php
	// include_once __DIR__.'/../config/database.php';
	// header('content-type: application/json');
	// specify your own database credentials
	include_once __DIR__.'/ObjectGenerator.php';
	include_once __DIR__.'/ControllerGenerator.php';
	include_once __DIR__.'/IndexGenerator.php';
	include_once __DIR__.'/KotlinGenerator.php';

	$host = $_POST['host'];
    $database = $_POST['database'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $project_name = $_POST['projectName'];
    $destination = $_POST['destination'];
	$pathBase = $destination.'/'.$project_name;
	
	if (file_exists($pathBase)) {
		echo "Folder already exists!";
		die();
	}
	$pathApi = $pathBase.'/api';
	if (!file_exists($pathApi))
		mkdir($pathApi, 0775, true);
	
	$pathConfig = $pathApi.'/config';	
	$pathControllers = $pathApi.'/controllers';	
	$pathKotlin = $pathApi.'/kotlin';	
	$pathObjects = $pathApi.'/objects';	
	$pathUtil = $pathApi.'/util';	
	if (!file_exists($pathConfig))
		mkdir($pathConfig, 0775, true);
	if (!file_exists($pathControllers))
		mkdir($pathControllers, 0775, true);
	if (!file_exists($pathKotlin))
		mkdir($pathKotlin, 0775, true);
	if (!file_exists($pathObjects))
		mkdir($pathObjects, 0775, true);
	if (!file_exists($pathUtil))
		mkdir($pathUtil, 0775, true);
	
	$tableDetails = getTableDetails();
	generateConfig();
	generateUtil();
	generateComposerJson();
	generateGitIgnore();

	$objGen = new ObjectGenerator();
	$controlGen = new ControllerGenerator();
	
	foreach($tableDetails as $table){
		$table->className = tableNameToFileName($table->tableName,true); 

		$objGen->setTableDetails($pathObjects, $table);
		$objGen->generateFile();		
		
		$controlGen->setTableDetails($pathControllers, $table);
		$controlGen->generateFile();		
	}

	$iGen = new IndexGenerator();
	$iGen->setTableDetails($pathBase, $tableDetails);
	$iGen->generateFile();

	$iGen = new KotlinGenerator();
	$iGen->setTableDetails($pathKotlin, $tableDetails);
	$iGen->generateFile();
		
		
	/*  ================================ Functions ======================= */
	function getTableDetails(){	
		global $host;
		global $database;
		global $username;
		global $password;
		try{
			$conn = new PDO("mysql:host=" . $host . ";dbname=" . $database, $username, $password);
			$conn->exec("set names utf8");
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			}catch(PDOException $exception){
			die("Connection error: " . $exception->getMessage());
		}
		
		$query = "SELECT t.TABLE_NAME as tableName, group_concat(c.COLUMN_NAME ORDER BY ORDINAL_POSITION) as variables
		FROM INFORMATION_SCHEMA.TABLES t, INFORMATION_SCHEMA.COLUMNS c
		WHERE t.TABLE_SCHEMA = '$database'
		and c.TABLE_SCHEMA = '$database'
		and c.table_name = t.table_name
		GROUP BY t.TABLE_NAME;
		";
		$stmt = $conn->prepare($query);
		$stmt->execute();
		$tables = $stmt->fetchAll(PDO::FETCH_OBJ);		
		
		foreach($tables as $table){
			$colQuery = "SELECT COLUMN_NAME as name, 
			IS_NULLABLE as nullable, 
			DATA_TYPE as type, 
			CHARACTER_MAXIMUM_LENGTH as maxLength  
			from INFORMATION_SCHEMA.COLUMNS 
			WHERE TABLE_NAME = '{$table->tableName}' 
			and TABLE_SCHEMA = '$database'
			ORDER BY ORDINAL_POSITION;
			";
			$stmt = $conn->prepare($colQuery);
			$stmt->execute();
			$table->columns = $stmt->fetchAll(PDO::FETCH_OBJ);		
		}
		
		echo "<br/><br/>".json_encode($tables)." <br/><br/>";
		return $tables;
	}	
	
	function generateConfig(){
		global $pathConfig;
		global $host;
		global $database;
		global $username;
		global $password;
		$file = fopen($pathConfig."/database.php", "w") or die("Unable to open file!");
		$data = 
"<?php
		class Database{
		
		// specify your own database credentials
		private \$host = '".$host . "';
		private \$db_name = '".$database."';
		private \$username = '".$username."';
		private \$password = '".$password."';
		public \$conn;
		
		// get the database connection
		public function getConnection(){
		
		\$this->conn = null;
		
		try{
			\$this->conn = new PDO(\"mysql:host=\" . \$this->host . \";dbname=\" . \$this->db_name, \$this->username, \$this->password);
			\$this->conn->exec(\"set names utf8\");
			\$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}catch(PDOException \$exception){
		echo \"Connection error: \" . \$exception->getMessage();
		}
		
		return \$this->conn;
		}
		}
		";	
		
		fwrite($file, $data);
		fclose($file);
		echo "<br/>Database.php generated Successfully!";

	}
	
	function generateUtil(){
		global $pathUtil;
		$file = fopen($pathUtil."/util.php", "w") or die("Unable to open file!");
		$data = 
"<?php
		
		class Util {
		
		public static function echoResp(\$resp){
		\$result = new stdClass();
		\$result->s = 0;
		\$result->d = \$resp;
		echo json_encode(\$result);
		}
		public static function echoErrResp(\$errMsg){
		\$result = new stdClass();
		\$result->s = 1;
		\$result->e = \$errMsg;
		echo json_encode(\$result);
		}
		
		}";	
		
		fwrite($file, $data);
		fclose($file);
		echo "<br/>Util.php generated Successfully!";
	}

	function generateComposerJson(){
		global $pathBase;
		$file = fopen($pathBase."/composer.json", "w") or die("Unable to open file!");
		$data = "
{
    \"require\": {
        \"slim/slim\": \"^3.12\"
    }
}

";	
		fwrite($file, $data);
		fclose($file);
		echo "<br/>composer.json generated Successfully!";
	}

	function generateGitIgnore(){
		global $pathBase;
		$file = fopen($pathBase."/.gitignore", "w") or die("Unable to open file!");
		$data = "vendor";	
		fwrite($file, $data);
		fclose($file);
		echo "<br/>.gitignore generated Successfully!";
	}

	function tableNameToFileName($string, $capitalizeFirstCharacter = false) 
	{
		
		$str = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
		
		if (!$capitalizeFirstCharacter) {
			$str[0] = strtolower($str[0]);
		}
		
		return $str;
	}
	

	
