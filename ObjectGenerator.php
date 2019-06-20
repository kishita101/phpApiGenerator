<?php

class ObjectGenerator {

	private $tableDetails;
	private $path;

	public function setTableDetails($path, $tableDetails){
		$this->path = $path;
		$this->tableDetails = $tableDetails;
	}

	public function generateFile(){
		$table = $this->tableDetails;
		$filename = $table->className;
		$object_name = lcfirst($filename);
		$table_name = $table->tableName;
		$variables = explode(",",$table->variables);
		$commaSeparated = "";
		foreach($variables as $v){
			if($v != 'id'){
			$commaSeparated .= $v.','; 
			}
		}
		$commaSeparated = rtrim($commaSeparated,',');
		$colonedVars = "";

		$data = 
"<?php
	class $filename {
		
		protected \$conn;
		protected \$table_name = '$table_name';
		";	

		foreach($variables as $variable){
			$data .= "
			public $$variable;";
			if($variable != 'id'){
			$colonedVars .=":$variable, ";
			}
		}
		$colonedVars = rtrim($colonedVars,', ');
		
		$data .= 
"
		
		public function __construct(\$conn){
			\$this->conn = \$conn;
		}		
";

		foreach($variables as $variable){
		$data .= 
"
	public function get".ucfirst($variable)."(){
		return \$$variable;
	}
	public function set".ucfirst($variable)."($$variable){
		\$this->$variable = $$variable;
	}
";
	}
		$data .= 
	"
		public function create(){
		try{
			\$query = \"INSERT INTO \" 
			. \$this->table_name . 
			\"($commaSeparated) 
			 VALUES($colonedVars)\" ;
		 
			 \$stmt = \$this->conn->prepare(\$query);";

		foreach($variables as $variable){
			if($variable != 'id'){
				$data .= "
				\$stmt->bindParam(':$variable', \$this->$variable);";
			}
		}
		$data .= 
"
			\$stmt->execute();
			\$this->id = \$this->conn->lastInsertId();
			return true;
			}catch(Exception \$e){
				return false;
			}
		}
		
";	
		$data .= 
"
		public function update(){
			\$query = \"UPDATE \" . \$this->table_name . \" SET ";
			
	foreach($variables as $variable){
		if($variable != 'id')
		$data .= "
			$variable = :$variable,";
	}
	$data = rtrim($data,',');
	$data .= "
			WHERE id = :id \";	
			\$stmt = \$this->conn->prepare(\$query); ";

	foreach($variables as $variable){
		$data .= "
			\$stmt->bindParam(':$variable', \$this->$variable);";
	}
	$data .= "
			\$stmt->execute();
			\$result = \$stmt->rowCount();
			return \$result;			
	}
		
	";	
	
	$data .= "
		public function read(){
			\$query = \"SELECT * FROM \" . \$this->table_name;
			\$stmt = \$this->conn->prepare(\$query);
			\$stmt->execute();
			\$result = \$stmt->fetchAll(PDO::FETCH_OBJ);
			return \$result;
		}

		public function readById(\$id){
			\$query = \"SELECT * FROM \" . \$this->table_name 
				. \" WHERE id = :id ; \";
			\$stmt = \$this->conn->prepare(\$query);
			\$stmt->bindParam(':id', \$id);
			\$stmt->execute();
			\$result = \$stmt->fetch(PDO::FETCH_OBJ);
			if(\$result == false)
				\$result = null;
			return \$result;
		}

		public function delete(\$id){
			\$query = \"DELETE FROM \" . \$this->table_name 
				. \" WHERE id = :id ; \";
			\$stmt = \$this->conn->prepare(\$query);
			\$stmt->bindParam(':id', \$id);
			\$stmt->execute();
			\$result = \$stmt->rowCount();
			return \$result;
		}
		";

		
	
		$data .="\n\n/* Dummy set values\n\n";
		
		foreach($variables as $variable){
			$data .= "    \$".$object_name."->$variable = $$variable;\n";
		}
		$data .="\n\n";
		
		foreach($variables as $variable){
			$data .= "    \$".$object_name."->set".ucfirst($variable)."($$variable);\n";
		}
		
		$data .=
"
*/
}
";
	$myfile = fopen($this->path.'/'.$filename.".php", "w") or die("Unable to open file!");
	fwrite($myfile, $data);
	fclose($myfile);
	echo "<br/>$filename generated Successfully!";
	}
	

}