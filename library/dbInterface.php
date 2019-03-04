<?php
	//error_reporting(0);
	include "path.php";
	require_once($path);
	/*
		- Takes in an array, string, and int.
		- Returns a string comprised of each element in the array, 
		with each element separated by the bisector. The last len 
		chars are removed from the end of the string to "clean it 
		up."
	*/
	if(!function_exists("stringify")){
		function stringify($arr, $bisector, $len){
			for($a = 0; $a < count($arr); $a++){
				$str .= cleanSql($arr[$a]) . $bisector;
			} 
			return substr($str, 0, -$len);
		}
	}
	
	/*
		- Takes in an array.
		- Returns a string that lists in order the var types of the
		elements of the input array in mysqli format (ignoring blobs). 
	*/
	if(!function_exists("getElementTypes")){
		function getElementTypes($arr){
			$types = "";
			for($a = 0; $a < count($arr); $a++){
				if(gettype($arr[$a]) == "integer") $types .= "i";
				else if(gettype($arr[$a]) == "double") $types .= "d";
				else $types .= "s";
			}
			return $types;
		}
	}

	/*
		- Takes in input string.
		- Returns string that has been cleaned of potential malicious sql. 
	*/
	if(!function_exists("cleanSql")){
		function cleanSql($str){
			return preg_replace("/[^A-Za-z0-9,._]/", '', $str);
		}
	}

	/*
		- Takes in a string, string array, string array, and a dbc 
		connect object. 
		- Returns "success" if the result of the query given by the
		inputs succesfully inserted a row into the given table. 
		Else it returns "error".
		- Note: Only supports very basic queries
	*/
	if(!function_exists("dbInsert")){
		function dbInsert($table, $cols, $vals, $dbc){
			$table = cleanSql($table);
			$types = getElementTypes($vals);
			$columns = " (" . stringify($cols, ", ", 2) . ") ";
			$questionMarks = "";
			for($a = 0; $a < count($vals); $a++){
				$questionMarks .= "?,";
				$vals[$a] = strip_tags(trim($vals[$a]));
			}
			$questionMarks = substr($questionMarks, 0, -1);
			$questionMarks = " VALUES(" . $questionMarks . ")";
			$query = $dbc->prepare(
				$query = "INSERT INTO " . 
					$table . 
					$columns .  
					$questionMarks
			);
			$query->bind_param($types, ...$vals);
			$query->execute();
			if($query->affected_rows == 1){
				$query->close();
				return "success";
			} else {
				$query->close(); 
				return "error";
			}
		}
	}

	/*
		- Takes in a string, string array, string array, string array, 
		array, and a dbc connect object. 
		- Returns "success" if the result of the query given by the
		inputs succesfully updaed a row/rows into the given table. 
		Else it returns "error".
		- Note: Only supports very basic queries
	*/
	if(!function_exists("dbUpdate")){
		function dbUpdate($table, $cols, $vals, $where1, $where2, $dbc){
			$table = cleanSql($table);
			$types = getElementTypes($vals);
			$types .= getElementTypes($where2);
			$vals = array_merge($vals, $where2);
			for($a = 0; $a < count($vals); $a++){
				$vals[$a] = strip_tags(trim($vals[$a]));
			}
			$columns = " SET " . stringify($cols, " = ?, ", 2);
			$where = " WHERE " . stringify($where1, " = ? AND ", 4);
			$query = $dbc->prepare(
					$query = "UPDATE " . 
						$table . 
						$columns . 
						$where
				);
			$query->bind_param($types, ...$vals);
			$query->execute();
			if($query->affected_rows > 0){
				$query->close();
				return "success";
			} else {
				$query->close(); 
				return "error";
			}
		}
	}

	/*
		- Takes in a string, string array, string array, array, int, 
		string, and dbc connect object.
		- Returns the result of a mysql query for the table and columns
		given with the restrictions given by where1, where2, limit, 
		and orderBy.
		- Note: Only supports very basic queries
	*/ 
	if(!function_exists("dbSelect")){
		function dbSelect($table, $cols, $where1, $where2, $limit, $orderBy, $dbc){
			$extra = cleanSql($table);
			$table = " FROM " . $extra;
			$columns = stringify($cols, ", ", 2);
			if($limit != "") $limit = " LIMIT " . cleanSql($limit); 
			// Join requires following format:
			// table 1 has name x (first char of x is lower case)
			// table 2 contains a column with name idX (first char of x is upper case)
			$join = "";
			if(strpos($extra, ',')){
				$temp = explode(",", $extra);
				$join = " " . $temp[0] . ".id = " . $temp[1] . ".id" . ucfirst($temp[0]) . " ";
			}
			if($orderBy != ""){
				$temp = explode(" ", $orderBy);
				$orderBy = " ORDER BY " . cleanSql($temp[0]) . " " . cleanSql($temp[1]);
			}
			if(count($where1) == 0){
				if($join != "") $join = " WHERE " . $join;
				$query = $dbc->prepare(
						$query = "SELECT " . 
							$columns . 
							$table . 
							$join . 
							$orderBy .
							$limit
					);
			} else {
				$types = getElementTypes($where2);
				if(strlen($join) > 0) $join = " AND " . $join;
				$where = " WHERE " . stringify($where1, " = ? AND ", 4);
				$query = $dbc->prepare(
						$query = "SELECT " . 
							$columns . 
							$table . 
							$where . 
							$join . 
							$orderBy .
							$limit
					);
				$query->bind_param($types, ...$where2);
			}
			
			$a = 0; 
			$data = array();
			$query->execute();
			$meta = $query->result_metadata();
			while ($field = $meta->fetch_field()){ 
				$var = $a++;
				$$var = null; 
				$data[$var] = &$$var;   
			}
			call_user_func_array(array($query,'bind_result'), $data);
			$ret = array();
			while ($query->fetch()) {
			    $temp = array();
			    for ($a = 0; $a < count($data); $a++) { 
			    	array_push($temp, $data[$a]);
			  	}
			  	array_push($ret, $temp);
			} 
			$query->close();
			return $ret;
		}
	}
?>