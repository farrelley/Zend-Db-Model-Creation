<?php

class My_Scripts_DbMapper extends My_Scripts_ModelCreation
{
	protected $_path;
	protected $_tableNameOriginal;
	protected $_tableName;
	protected $_populateSetters;
	protected $_primaryKey;
	protected $_insertSaveArray;
	
	public function __construct()
	{
		parent::__construct();
		
		$this->_path = APPLICATION_PATH . '/models/mappers';
		$this->setupDbMapperFolder();
		$this->generateMapper();
	}
	
	protected function setupDbMapperFolder()
	{	
		if (!is_dir($this->_path)) {
			mkdir($this->_path, 0755);
			$this->_logger->info('Mappers Folder Created <br />');
			return;
		}
		return;
	}
	
	protected function generateMapper()
	{
		//get the tables in the database
		$dbTables = $this->getDbTables();
		
		//get the describe of the table
		foreach ($dbTables as $table) {
			$describeTable = $this->describeTable($table);
			
			if ($describeTable) {
				$this->_tableNameOriginal = $table;
				$this->_tableName = Zend_Filter::filterStatic($table, 'StringToLower');
				$this->_tableName = Zend_Filter::filterStatic($this->_tableName, 'Word_UnderscoreToCamelCase');
							
				$this->_primaryKey = $this->getPrimaryKey($describeTable);
				$fields = array();
				foreach ($describeTable as $tableMeta)
				{
					$fields[] = array(
						'Name' => (string) $this->fixColumnName($tableMeta['COLUMN_NAME'], true),
						'Database_Column_Name' => $tableMeta['COLUMN_NAME'],
					);
				}
				$this->generatePopulateSetters($fields);
				$this->generateInsertSaveArray($fields);
				
				$code = $this->createMapper();
				$this->saveMapperFile($code);
			}
		}
	}
	
	public function dbTableMethods()
	{
		//setDbTable
		$methods[] = array(
			'name' => 'setDbTable',
	        'parameters' => array(
	        	array(
	            	'name' => 'dbTable',
				),
			),
	        'body' => 'if (is_string($dbTable)) {' . "\n    " .
				'$dbTable = new $dbTable();' . "\n" .
				'}' . "\n" .
				'if (!$dbTable instanceof Zend_Db_Table_Abstract) {' . "\n    " .
				'throw new Exception(\'Invalid table data gateway provided\');' . "\n" .
				'}' . "\n" .
				'$this->_dbTable = $dbTable;' . "\n" .
				'return $this;',
	        'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
		    	'shortDescription' => 'setDbTable',
	    	    'tags' => array(
	            	new Zend_CodeGenerator_Php_Docblock_Tag_Param(array(
	                	'paramName' => 'dbTable',
	                    'datatype'  => 'object',
					)),
	                new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
	                	'datatype'  => 'object',
					)),
				),
			)),
		);
		
		//getDbTable
		$methods[] = array(
			'name' => 'getDbTable',
	        'parameters' => array(
	        	array(
	            	'name' => 'dbTable',
				),
			),
	        'body' => 'if (null === $this->_dbTable) {' . "\n    " .
				'$this->setDbTable(\'' . $this->_appNamespace . '_Model_DbTable_' . $this->_tableName . '\');' . "\n" .
				'}' . "\n" .
				'return $this->_dbTable;',
	        'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
		    	'shortDescription' => 'getDbTable',
	    	    'tags' => array(
	                new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
	                	'datatype'  => 'object',
					)),
				),
			)),
		);
		return $methods;
	}
	
	protected function fetchAllSql()
	{
		$method = array(
			'name' => 'fetchAll',
	        'body' => '$resultSet = $this->getDbTable()->fetchAll();' . "\n" .
				'$entries = array();' . "\n" .
				'foreach ($resultSet AS $row) {' . "\n    " .
				'$entry = new ' . $this->_appNamespace . '_Model_' . $this->_tableName . '();' . "\n    " .
				'$this->_populateModel($row, $entry);' . "\n    " .
				'$entries[] = $entry;' . "\n" .
				'}' . "\n" .
				'return $entries;',
	        'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
		    	'shortDescription' => 'fetchAll',
	    	    'tags' => array(
	                new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
	                	'datatype'  => 'Array',
					)),
				),
			)),
		);
		return $method;
	}
	
	protected function deleteSql()
	{
		$primaryGet = $this->fixColumnName($this->_primaryKey, true);
		$method = array(
			'name' => 'delete',
			'parameters' => array(
	        	array(
	            	'name' => 'model',
	        		'type' => $this->_appNamespace . '_Model_' . $this->_tableName,
				),
			),
	        'body' => '$this->getDbTable()->delete(array(\'' . $this->_primaryKey . ' = ?\' => $model->get' . $primaryGet . '()));',
	        'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
		    	'shortDescription' => 'delete',
	    	    'tags' => array(
	                new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
	                	'datatype'  => 'Array',
					)),
				),
			)),
		);
		return $method;
	}
	
	protected function findSql()
	{
		$method = array(
			'name' => 'find',
			'parameters' => array(
	        	array(
	            	'name' => 'id',
				),
				array(
	            	'name' => 'model',
	        		'type' => $this->_appNamespace . '_Model_' . $this->_tableName,
				),
			),
	        'body' => '$result = $this->getDbTable()->find($id);' . "\n" . 
				'if (0 == count($result)) {' . "\n    " . 
				'return;' . "\n" . 
				'}' . "\n" . 
				'$row = $result->current();' . "\n" . 
				'$this->_populateModel($row, $model);',
	        'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
		    	'shortDescription' => 'find',
	    	    'tags' => array(
	                new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
	                	'datatype'  => 'object',
					)),
				),
			)),
		);
		return $method;
	}
	
	protected function insertSql()
	{
		$primaryGet = $this->fixColumnName($this->_primaryKey, true);
		$method = array(
			'name' => 'insert',
			'parameters' => array(
				array(
	            	'name' => 'model',
	        		'type' => $this->_appNamespace . '_Model_' . $this->_tableName,
				),
			),
	        'body' => $this->_insertSaveArray . "\n" . 
				'$id = $this->getDbTable()->insert($data);' . "\n" . 
				'$model->set' . $primaryGet . '($id);',
	        'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
		    	'shortDescription' => 'insert',
	    	    'tags' => array(
	                new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
	                	'datatype'  => 'object',
					)),
				),
			)),
		);
		return $method;
	}
	
	protected function saveSql()
	{
		$primaryGet = $this->fixColumnName($this->_primaryKey, true);
		$method = array(
			'name' => 'save',
			'parameters' => array(
				array(
	            	'name' => 'model',
	        		'type' => $this->_appNamespace . '_Model_' . $this->_tableName,
				),
			),
	        'body' => $this->_insertSaveArray . "\n" .
				'$id = $model->get' . $primaryGet . '();' . "\n" .
				'$this->getDbTable()->update($data, array(\'' . $this->_primaryKey . ' = ?\' => $id));',
	        'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
		    	'shortDescription' => 'save',
	    	    'tags' => array(
	                new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
	                	'datatype'  => 'object',
					)),
				),
			)),
		);
		return $method;
	}
	
	protected function generatePopulateSetters($fields)
	{
		$pop = "\$model";
		$i = count($fields);
		$c = 1;
		foreach ($fields as $field) {
			$pop .= "->set" . $field['Name'] . "(\$row->" . $field['Database_Column_Name']. ")";
			if ($c < $i ) {
				$pop .= "\n    ";
			}
			$c++;
		}
		$pop .= ";\n return;";
		$this->_populateSetters = $pop;
	}
	
	protected function generateInsertSaveArray($fields)
	{
		$insertSave = '$data = array(' . "\n\t";
		$c = count($fields);
		$i = 1;
		foreach ($fields as $field) {
			$insertSave .= "'" . $field['Database_Column_Name'] ."'" . ' => $model->get' . 
				$this->fixColumnName($field['Database_Column_Name']) . '(),' . "\n";
			if ($i < $c) {
				$insertSave .= "\t";	
			}
			$i++;
		}
		$insertSave .= ');';

		$this->_insertSaveArray = $insertSave;
	}
	
	protected function populateMethod()
	{
		$method = array(
			'name' => '_populateModel',
			'parameters' => array(
	        	array(
	            	'name' => 'row',
	        		'type' => 'Zend_Db_Table_Row_Abstract'
				),
				array(
	            	'name' => 'model',
	        		'type' => $this->_appNamespace . '_Model_' . $this->_tableName,
				),
			),
	        'body' => $this->_populateSetters,
	        'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
		    	'shortDescription' => 'Populate Model',
	    	    'tags' => array(
	                new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
	                	'datatype'  => 'void',
					)),
				),
			)),
		);
		return $method;
	}
	
	protected function createMapper()
	{	

		$dbTableMethods = $this->dbTableMethods();
		$insertSql = $this->insertSql();
		$saveSql = $this->saveSql();
		$findSql = $this->findSql();
		$fetchAllSql = $this->fetchAllSql();
		$deleteSql = $this->deleteSql();
		$populateModel = $this->populateMethod();
		
		
		$mapperClass = new Zend_CodeGenerator_Php_Class();
		$docblock = new Zend_CodeGenerator_Php_Docblock(array(
    		'shortDescription' => 'Table Mapper ' . $this->_tableNameOriginal,
    		'longDescription'  => 'This is a class generated with Zend_CodeGenerator.',
    		'tags' => array(
				array(
            		'name' => 'version',
            		'description' => '$Rev:$',
        		),
    		),
		));
		
		
		$mapperClass->setName($this->_appNamespace . '_Model_Mapper_' . $this->_tableName )
    		->setDocblock($docblock)
    		->setProperty(
    			array(
    				'name' => '_dbTable',
            		'visibility'   => 'protected',
					'defaultValue' => '',)
    		)
    		->setMethods($dbTableMethods)
    		->setMethod($insertSql)
    		->setMethod($saveSql)
    		->setMethod($findSql)
    		->setMethod($fetchAllSql)
    		->setMethod($deleteSql)
    		->setMethod($populateModel);
    		
    	return $mapperClass;
	}
	
	protected function saveMapperFile($code) 
	{
		$file = new Zend_CodeGenerator_Php_File(array(
			'classes'  => array($code),
			'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
				'shortDescription' => 'Mapper class file',
				'tags' => array(
					array(
						'name' => 'license',
						'description' => 'New BSD',
					),
				),
			)),
		));
		$filename = $this->_path . '/' . $this->_tableName . '.php';
		$f = fopen($filename, 'w');
        if (!$f) {
            return false;
        } else {
            $bytes = fwrite($f, $file);
            fclose($f);
            
            $this->_logger->info('Created Mapper' . $this->_tableName . ' @ ' . $filename . ' <br />');
            return $bytes;
        }
	}
	
}
