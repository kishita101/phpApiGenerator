<?php

class KotlinGenerator {

	private $AlltableDetails;
	private $path;

	public function setTableDetails($path, $AlltableDetails){
		$this->path = $path;
		$this->AlltableDetails = $AlltableDetails;
	}

	public function generateFile(){
		$index = $this->generateMapping();
		$file = fopen($this->path."/Api.kt", "w") or die("Unable to open file!");
		fwrite($file, $index);
		fclose($file);
		echo "<br/>Kotlin file generated Successfully!";

	}	
	
	public function generateMapping(){
		$Alltables = $this->AlltableDetails;
		$kotlin = "";	
		foreach($Alltables as $table) {
			$filename = $table->className;
			$object_name = lcfirst($filename);
			$kotlin .= 
"
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
$entries = "";
$imports = "";
$columns = $table->columns;
foreach($columns as $var) {
	$type = $var->type;
	switch($type){
		case 'tinyint':
		case 'int':
			$type = 'Int';
			break;
		case 'datetime':
		case 'timestamp':
		case 'date':
			$imports = "import java.util.Date\n";
			$type = 'Date';
			break;
		case 'bit':
			$type = 'Boolean';
			break;
		case 'decimal':
			$type = 'Double';
			break;
		default:
		case 'varchar':
			$type = 'String';
			break;		
	}	

	if($var->name == 'id')
		$prefix = 'val';
	else
		$prefix = 'var';
	if($var->nullable == 'NO')
		$nullable = '';
	else
		$nullable = '?';

	$entries .=  "\n		$prefix {$var->name}: {$type}{$nullable},";
	
}
$model = "package com.kishulsolutions.tricketer.data.model

import com.google.gson.Gson
$imports
data class $filename($entries";

$model = rtrim($model,",");

  $model .= ") : BaseModel() {

    override fun toString(): String {
        return Gson().toJson(this)
    }
}

		";
		$file = fopen($this->path."/$filename.kt", "w") or die("Unable to open file!");
		fwrite($file, $model);
		fclose($file);
		echo "<br/>Kotlin $filename file generated Successfully!";

		}
		return $kotlin;

	}
	
	
}