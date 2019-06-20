<?php
/*
This file generates basic class and Controller for list of table details passed to it in the following format:
[{"tableName":"ball","variables":"inningsId,battingPlayerId,matchId,outStyle,overNumber,id,runs,bowlingPlayerId,highlights,caughtById,ballNumber","class":"Ball"},{"tableName":"community","variables":"id,name","class":"Community"},{"tableName":"community_member","variables":"id,playerId,communityId","class":"CommunityMember"},{"tableName":"innings","variables":"id,battingTeamId,matchId","class":"Innings"},{"tableName":"match","variables":"tournamentId,teamB,result,status,id,teamA,matchDate,communityId,venue","class":"Match"},{"tableName":"otp","variables":"isUsed,playerId,createdOn,OTP","class":"Otp"},{"tableName":"player","variables":"name,dateOfBirth,bowlingStyle,id,phoneNo,battingStyle,wicketKeeper","class":"Player"},{"tableName":"playing_styles","variables":"id,style,type","class":"PlayingStyles"},{"tableName":"prizes","variables":"name,description,id,amount","class":"Prizes"},{"tableName":"team","variables":"scorerId,name,id","class":"Team"},{"tableName":"team_player","variables":"teamId,type,isExtra,status,id,playerId,isCaptain,isWicketKeeper","class":"TeamPlayer"},{"tableName":"tournament","variables":"description,prizeId,name,startDate,venue,logoURL,cummunityId,id,noOfTeams,endDate","class":"Tournament"}]
*/
$params = json_decode(file_get_contents("php://input"));
$pathAPI = $params->path;
$list = $params->list;
$kotlin = "";
	foreach ($list as $table) {
	$filename = $table->class;
	$object_name = lcfirst($filename);
	$table_name = $table->tableName;
	$variables = explode(" ",$table->variables);
	$commaSeparated = join(', ',$variables);
	$commaSeparated = ltrim($commaSeparated,'id,');
	$data = 
	"<?php
	class $filename {
		
		protected \$conn;
		protected \$table_name = '$table_name';
		";	
	$colonedVars ="";
	foreach($variables as $variable){
		$data .= "
		protected $$variable;";
		if($variable != 'id'){
		$colonedVars .=":$variable, ";
		}
	}
	$colonedVars = rtrim($colonedVars,', ');
	
	$data .= "
		
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
	$data .= "
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
	$data .="
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

*/
}";
	
	$control ="<?php
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
	
	$control .= "
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
	
	$kotlin .= "
	@GET(\"/{$object_name}s\")
    fun get{$filename}s(): Call<ResponseDS<List<$filename>>>

    @GET(\"/$object_name/{id}\")
    fun get{$filename}ById(@Path(value = \"id\", encoded = true) id: String): Call<ResponseDS<{$filename}>>

    @POST(\"/{$object_name}\")
    fun post{$filename}(@Body {$object_name}: {$filename}): Call<ResponseDS<{$filename}>>

    @PUT(\"/{$object_name}/{id}\")
    fun post{$filename}(@Path(value = \"id\", encoded = true) id: String, @Body {$object_name}: {$filename}): Call<ResponseDS<{$filename}>>

    @DELETE(\"/{$object_name}/{id}\")
    fun delete{$filename}(@Path(value = \"id\", encoded = true) id: String): Call<ResponseDS<{$filename}>>

";
	
	$myfile = fopen($path."/objects/".$filename.".php", "w") or die("Unable to open file!");
	fwrite($myfile, $data);
	fclose($myfile);
	
	$myControlfile = fopen($path."/controllers/".$filename."Controller.php", "w") or die("Unable to open file!");
	fwrite($myControlfile, $control);
	fclose($myControlfile);

	echo $filename." created successfully!\n";
	}
		$kotlinfile = fopen($path."/kotlin/Api.kt", "w") or die("Unable to open file!");
	fwrite($kotlinfile, $kotlin);
	fclose($kotlinfile);
	echo "Kotlin APIs created successfully!\n";


?>