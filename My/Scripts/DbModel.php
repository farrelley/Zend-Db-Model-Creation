<?php		
class My_Scripts_DbModel extends My_Scripts_ModelCreation
{
	protected $_tableNameOriginal;
	protected $_tableName;
	protected $_properties;
	protected $_getterSetters;
	
	public function __construct()
	{
		parent::__construct();
		
		$this->_logger->info('Create Db Model <br />');
		$this->generateModel();
	}
	
	protected function generateModel()
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
				
				$fields = array();
				foreach ($describeTable as $tableMeta)
				{
					$fields[] = array(
						'Name' => (string) $this->fixColumnName($tableMeta['COLUMN_NAME']),
						'Data_Type' => (string) $tableMeta['DATA_TYPE'],
						'Nullable' => (bool) $tableMeta["NULLABLE"],
					);
				}
				$this->createProperties($fields);
				$this->getterAndSetterMethods($fields);
				$code = $this->createModel();
				$this->saveModelFile($code);
			}
		}
	}
	
	public function createProperties($fields)
	{
		$properties = array();
		
		//add mapper
		$fields[] = array('Name' => 'mapper', 'Data_Type' => '');
		foreach ($fields as $field) {
			$properties[] = array(
            	'name' => '_' . $field['Name'],
            	'visibility'   => 'protected',
				'defaultValue' => '',
        	);
		}
		$this->_properties = $properties;
	}
	

	
	public function getterAndSetterMethods($fields)
	{
		$methods = array();
	
		foreach ($fields as $field) {
			$dataType = $this->phpDataTypeConversion($field['Data_Type']);
			
			// Create Setter
			$methods[] = array(
	        	'name' => 'set' . ucfirst($field['Name']),
	            'parameters' => array(
	             	array(
	                	'name' => $field['Name']
	                ),
	            ),
	            'body' => '$this->_' . $field['Name'] . ' = (' . $dataType . ') $' . $field['Name'] . ';' . "\n" . 'return $this;',
	            'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
		        	'shortDescription' => 'Set ' . $field['Name'] . ' property',
	    	        'tags' => array(
	            		new Zend_CodeGenerator_Php_Docblock_Tag_Param(array(
	                    	'paramName' => $field['Name'],
	                        'datatype'  => $dataType,
	                    )),
	                    new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
	                    	'datatype'  => $dataType,
	                    )),
	                ),
	            )),
	        );
	        
	        //	Create Getter
	        $methods[] = array(
	        	'name' => 'get' . ucfirst($field['Name']),
	            'body' => 'return $this->_' . $field['Name'] . ';',
	            'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
		        	'shortDescription' => 'Get ' . $field['Name'] . ' property',
	    	        'tags' => array(
	                    new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
	                    	'datatype'  => $dataType,
	                    )),
	                ),
	            )),
	        );   
		}
		$this->_getterSetters = $methods;
	}
	
	
	protected function _buildConstructor()
	{
		$constructor= array(
			'name' => '__construct',
	        'parameters' => array(
	             	array(
	                	'name' => 'options',
	             		'defaultValue' => null,
	                ),
	            ),
	            'body' => 'if (is_array($options)) {' . "\n    " . '$this->setOptions($options);' . "\n" . '}',
	            'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
		        	'shortDescription' => 'constructor',
	    	        'tags' => array(
	            		new Zend_CodeGenerator_Php_Docblock_Tag_Param(array(
	                    	'paramName' => 'options',
	                        'datatype'  => 'array',
	                    )),
	                    new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
	                    	'datatype'  => 'void',
	                    )),
	                ),
	            )),
	        );
		return $constructor;
	}
	
	protected function _buildMagicSet()
	{
		$magicSet = array(
			'name' => '__set',
	        'parameters' => array(
	             	array(
	                	'name' => 'name',
	                ),
	                array(
	                	'name' => 'value',
	                ),
	            ),
	            'body' => '$method = \'set\' . ucfirst($name);' . "\n" . 
	            	'if ((\'mapper\' == $name) || !method_exists($this, $method)) {' . "\n    " .
					'throw new Exception(\'Invalid property\');' . "\n" .
	    			'}' . "\n" .
	    			'$this->$method($value);',
	            'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
		        	'shortDescription' => 'setter',
	    	        'tags' => array(
	            		new Zend_CodeGenerator_Php_Docblock_Tag_Param(array(
	                    	'paramName' => 'name',
	                        'datatype'  => 'string',
	                    )),
	                    new Zend_CodeGenerator_Php_Docblock_Tag_Param(array(
	                    	'paramName' => 'value',
	                        'datatype'  => 'string',
	                    )),
	                    new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
	                    	'datatype'  => 'object',
	                    )),
	                ),
	            )),
	        );
		return $magicSet;
	}
	
	protected function _buildMagicGet()
	{
		$magicGet = array(
			'name' => '__get',
	        'parameters' => array(
				array(
	            	'name' => 'name',
				),
			),
	        'body' => '$method = \'get\' . ucfirst($name);' . "\n" . 
	        	'if ((\'mapper\' == $name) || !method_exists($this, $method)) {' . "\n    " .
				'throw new Exception(\'Invalid property\');' . "\n" .
	    		'}' . "\n" .
	    		'$this->$method();',
			'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
			    'shortDescription' => 'getter',
		    	'tags' => array(
	    	    	new Zend_CodeGenerator_Php_Docblock_Tag_Param(array(
	        	    	'paramName' => 'name',
	            	    'datatype'  => 'string',
					)),
	            	new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
		            	'datatype'  => 'object',
					)),
				),
			)),
		);
		return $magicGet;
	}
	
	protected function _buildSetOptions()
	{
		$setOptions = array(
			'name' => 'setOptions',
	        'parameters' => array(
				array(
	            	'name' => 'options',
				),
			),
	        'body' => '$methods = get_class_methods($this);' . "\n" .
				'foreach ($options as $key => $value) {' . "\n    " .
				'$method = \'set\' . ucfirst($key);' . "\n    " .
				'if (in_array($method, $methods)) {' . "\n        " .
				'$this->$method($value);' . "\n    " .
				'}' . "\n" .
				'}' . "\n" .
				'return $this;',
			'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
			    'shortDescription' => 'setOptions',
		    	'tags' => array(
	    	    	new Zend_CodeGenerator_Php_Docblock_Tag_Param(array(
	        	    	'paramName' => 'options',
	            	    'datatype'  => 'array',
					)),
	            	new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
		            	'datatype'  => 'object',
					)),
				),
			)),
		);
		return $setOptions;
	}
	
	protected function _buildMapperMethods() {
		$mapperMethods[] = array(
			'name' => 'getMapper',
	        'body' => 'if (null === $this->_mapper) {' . "\n    " .
				'$this->setMapper(new Application_Model_Mapper_' . $this->_tableName . '());' . "\n" .
				'}' . "\n" .
				'return $this->_mapper;',
			'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
			    'shortDescription' => 'getMapper for ' . $this->_tableName,
		    	'tags' => array(
	            	new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
		            	'datatype'  => 'object',
					)),
				),
			)),
		);
		
		$mapperMethods[] = array(
			'name' => 'setMapper',
			'parameters' => array(
				array(
	            	'name' => 'mapper',
				),
			),
	        'body' => '$this->_mapper = $mapper;' . "\n" .
				'return $this;',
			'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
			    'shortDescription' => 'setMapper',
		    	'tags' => array(
	    	    	new Zend_CodeGenerator_Php_Docblock_Tag_Param(array(
	        	    	'paramName' => 'mapper',
	            	    'datatype'  => 'object',
					)),
	            	new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
		            	'datatype'  => 'object',
					)),
				),
			)),
		);
		return $mapperMethods;
	}
	
	protected function _buildMapperSQLMethods()
	{
		$mapperSQLMethods[] = array(
			'name' => 'delete',
	        'body' => '$this->getMapper()->delete($this);' . "\n" . 'return $this;',
			'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
			    'shortDescription' => 'delete',
		    	'tags' => array(
	            	new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
		            	'datatype'  => 'object',
					)),
				),
			)),
		);
		$mapperSQLMethods[] = array(
			'name' => 'insert',
	        'body' => '$this->getMapper()->insert($this);' . "\n" . 'return $this;',
			'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
			    'shortDescription' => 'insert',
		    	'tags' => array(
	            	new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
		            	'datatype'  => 'object',
					)),
				),
			)),
		);
		$mapperSQLMethods[] = array(
			'name' => 'save',
	        'body' => '$this->getMapper()->save($this);' . "\n" . 'return $this;',
			'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
			    'shortDescription' => 'save',
		    	'tags' => array(
	            	new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
		            	'datatype'  => 'object',
					)),
				),
			)),
		);
		$mapperSQLMethods[] = array(
			'name' => 'find',
	        'parameters' => array(
				array(
	            	'name' => 'id',
				),
			),
	        'body' => '$this->getMapper()->find($id, $this);' . "\n" . 'return $this;',
			'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
			    'shortDescription' => 'find',
		    	'tags' => array(
	    	    	new Zend_CodeGenerator_Php_Docblock_Tag_Param(array(
	        	    	'paramName' => 'id',
	            	    'datatype'  => 'int',
					)),
	            	new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
		            	'datatype'  => 'object',
					)),
				),
			)),
		);
		$mapperSQLMethods[] = array(
			'name' => 'fetchAll',
	        'body' => 'return $this->getMapper()->fetchAll();',
			'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
			    'shortDescription' => 'fetchAll',
		    	'tags' => array(
	            	new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
		            	'datatype'  => 'array',
					)),
				),
			)),
		);
		return $mapperSQLMethods;
	}
	
	
	public function createModel()
	{
		$constructor = $this->_buildConstructor();
		$magicSet = $this->_buildMagicSet();
		$magicGet = $this->_buildMagicGet();
		$setOptions = $this->_buildSetOptions();
		$mapperMethods = $this->_buildMapperMethods();
		$mapperSql = $this->_buildMapperSQLMethods();
		
		$modelClass = new Zend_CodeGenerator_Php_Class();
		$docblock = new Zend_CodeGenerator_Php_Docblock(array(
    		'shortDescription' => 'Table ' . $this->_tableNameOriginal,
    		'longDescription'  => 'This is a class generated with Zend_CodeGenerator.',
    		'tags' => array(
				array(
            		'name' => 'version',
            		'description' => '$Rev:$',
        		),
    		),
		));
		
		$modelClass->setName('Application_Model_' . $this->_tableName )
    		->setDocblock($docblock)
    		->setProperties($this->_properties)
			->setMethod($constructor)
			->setMethod($magicSet)
			->setMethod($magicGet)
			->setMethod($setOptions)
			->setMethods($mapperMethods)
    		->setMethods($this->_getterSetters)
    		->setMethods($mapperSql);
    	return $modelClass;
	}
	
	protected function saveModelFile($code) 
	{
		$file = new Zend_CodeGenerator_Php_File(array(
			'classes'  => array($code),
			'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
				'shortDescription' => 'Foo class file',
				'tags' => array(
					array(
						'name' => 'license',
						'description' => 'New BSD',
					),
				),
			)),
		));
		$filename = APPLICATION_PATH . '/models/'. $this->_tableName . '.php';
		$f = fopen($filename, 'w');
        if (!$f) {
            return false;
        } else {
            $bytes = fwrite($f, $file);
            fclose($f);
            
            $this->_logger->info('Created Model ' . $this->_tableName . ' @ ' . $filename . ' <br />');
            return $bytes;
        }
	}
}