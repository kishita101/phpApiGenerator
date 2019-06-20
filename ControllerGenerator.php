<?php

class ControllerGenerator {

	private $tableDetails;
	private $path;

	public function setTableDetails($path, $tableDetails){
		$this->path = $path;
		$this->tableDetails = $tableDetails;
	}

	public function generateFile(){
		$table = $this->tableDetails;
		$filename =	 $filename = $table->className;
		$object_name = lcfirst($filename);
		$variables = explode(",",$table->variables);
			
		$control =
"<?php
	include_once __DIR__.'/../objects/{$filename}.php';
	include_once __DIR__.'/../config/Database.php';
	
	class {$filename}Controller {
		
		public function create(\$data){
			\$db = new Database();
			\$conn = \$db->getConnection();
			extract(\$data);
			\$$object_name = new $filename(\$conn);
			";
				
		foreach($variables as $variable){
			$control .= "
				\$".$object_name."->set".ucfirst($variable)."($$variable);";
		}
	
		$control .= 
"
			if($".$object_name."->create(\$db)){				
				Util::echoResp($$object_name);
			}else{
				Util::echoErrResp(99);
			}
		}
		
		public function update(\$recordId,\$data){
			\$db = new Database();
			\$conn = \$db->getConnection();
			extract(\$data);
			\$id = \$recordId;
			\$$object_name = new $filename(\$conn);
";
							
	foreach($variables as $variable){
		$control .= "
			\$".$object_name."->set".ucfirst($variable)."($$variable);";
	}

		$control .=	"
			\$count = $".$object_name."->update(\$db); 
			if(\$count > 0){
				Util::echoResp(\$count);
				}else{
				Util::echoErrResp(99);
			}
		}
		
		public function read(){
			\$db = new Database();
			\$conn = \$db->getConnection();
			\$$object_name = new $filename(\$conn);
			\$list = \$".$object_name."->read();
			Util::echoResp(\$list);
		}	

		public function readById(\$id){
			\$db = new Database();
			\$conn = \$db->getConnection();
			\$$object_name = new $filename(\$conn);
			\$record = \$".$object_name."->readById(\$id);
			Util::echoResp(\$record);
		}	

		public function delete(\$id){
			\$db = new Database();
			\$conn = \$db->getConnection();
			\$$object_name = new $filename(\$conn);
			\$record = \$".$object_name."->delete(\$id);
			Util::echoResp(\$record);
		}	
	}					
	";
	
	$myControlfile = fopen($this->path.'/'.$filename."Controller.php", "w") or die("Unable to open file!");
	fwrite($myControlfile, $control);
	fclose($myControlfile);
	echo "<br/>{$filename}Controller generated Successfully!";

	}

}