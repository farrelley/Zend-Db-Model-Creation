<?php
class My_Scripts_DbTable extends My_Scripts_ModelCreation
{
	protected $_path;
	protected $_dbTableName;
	
	public function __construct()
	{
		parent::__construct();
		$this->_logger->info('Create DbTable  <br />');
			
		$this->_path = APPLICATION_PATH . '/models/DbTable';
		$this->setupDbTableFolder();
		$this->generateDbTables();
	}
	
	protected function setupDbTableFolder()
	{
		if (!is_dir($this->_path)) {
			mkdir($this->_path, 0755);
			$this->_logger->info('DbTable Folder Created <br />');
			return;
		}
		return;
	}
	
	protected function generateDbTables()
	{
		//get the tables in the database
		$dbTables = $this->getDbTables();
		
		//get the describe of the table
		foreach ($dbTables as $table) {
			$describeTable = $this->describeTable($table);
			
			if ($describeTable) {
				$primaryKey = $this->getPrimaryKey($describeTable);
				$code = $this->generateDbTableCode($table, $primaryKey);
				$this->saveDbTableFile($code);
				
			}
		}
	}
		
	protected function generateDbTableCode($table, $primaryKey) 
	{
		$this->_dbTableName = $this->fixTableName($table);
		$dbTableClass = new Zend_CodeGenerator_Php_Class();
		$dbTableClass->setName($this->_appNamespace . '_Model_DbTable_' . $this->_dbTableName)
			->setExtendedClass('Zend_Db_Table_Abstract')
    		->setProperties(array(
    			array(
	            	'name' => '_name',
    	        	'visibility'   => 'protected',
					'defaultValue' => $table,
        		),
        		array(
        			'name' => '_primary',
    	        	'visibility'   => 'protected',
					'defaultValue' => $primaryKey,
        		),
        	)
        );
        
        if (null != $this->_schema) {
        	$dbTableClass->setProperty(
        		array(
        			'name' => '_schmea',
    	        	'visibility'   => 'protected',
					'defaultValue' => $this->_schema,
        		)
        	);
        }
        return $dbTableClass;
	}
	
	protected function saveDbTableFile($code) 
	{
		$file = new Zend_CodeGenerator_Php_File(array(
			'classes'  => array($code),
			'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
				'shortDescription' => 'DbTable class file',
				'tags' => array(
					array(
						'name' => 'license',
						'description' => 'New BSD',
					),
				),
			)),
		));
		$filename = $this->_path . '/' . $this->_dbTableName . '.php';
		$f = fopen($filename, 'w');
        if (!$f) {
            return false;
        } else {
            $bytes = fwrite($f, $file);
            fclose($f);
            $this->_logger->info('Created DbTable ' . $this->_dbTableName . ' @ ' . $filename . ' <br />');
            return $bytes;
        }
	}
}