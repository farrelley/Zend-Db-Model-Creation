<?php
class My_Scripts_ModelCreation
{
	protected $_db;
	protected $_dbVendor;
	protected $_schema;
	protected $_appNamespace;
	
	protected $_logWriter;
	protected $_logger;
	
	public function __construct()
	{
		$this->_logWriter = new Zend_Log_Writer_Stream('php://output');
		$this->_logger = new Zend_Log($this->_logWriter);
		$this->_logger->info('Logging Initialized <br />');
		
		$this->getDbAdapter();
		
		$this->_logger->info('Loading Application Config File <br />');
		$config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);
		$this->_appNamespace = $config->appnamespace;
		$this->_logger->info('Using Namespace ' . $this->_appNamespace . '<br />');
	}
			
	protected function getDbAdapter() 
	{
		$this->_logger->info('Getting Database Stuff <br />');
		$this->_db = Zend_Db_Table::getDefaultAdapter();
		$this->setDbVendor();
		$this->setSchema();
	}
	
	protected function setDbVendor()
	{
		if ($this->_db instanceof Zend_Db_Adapter_Oracle) {
			$this->_dbVendor = 'oracle';
			return;
		}
		$this->_dbVendor = 'mysql';
		
		$this->_logger->info('Vendor Set');
		return; 
	}
	
	protected function setSchema()
	{
		if ('oracle' === $this->_dbVendor) {
			$dbConfig = $this->_db->getConfig();
			$this->_schema = Zend_Filter::filterStatic($dbConfig['username'], 'StringToUpper');
			return;
		}
		$this->_schema = null;
		
		$this->_logger->info('Schema Set');
		return;
	}
	
	public function getDbTables()
	{
		return $this->_db->listTables();
	}
	
	public function describeTable($table)
	{
		return $this->_db->describeTable($table, $this->_schema);
	}
		
	public function fixColumnName($columnName, $firstUc = false)
	{
		$lowerName = Zend_Filter::filterStatic($columnName, 'StringToLower');
		$name = Zend_Filter::filterStatic($lowerName, 'Word_UnderscoreToCamelCase');
		if (false === $firstUc) {
			$name{0} = strtolower($name{0});
		}
		return $name;
	}
	
	public function fixTableName($tableName)
	{
		$lowerName = Zend_Filter::filterStatic($tableName, 'StringToLower');
		$name = Zend_Filter::filterStatic($lowerName, 'Word_UnderscoreToCamelCase');
		return $name;
	}
	
	protected function getSchemaTables($tableDescriptions)
	{
		$tableValues = array_values($tableDescriptions);
		if ($this->_schema == $tableValues[0]['SCHEMA_NAME']) {
			return $tableValues[0]['TABLE_NAME'];
		}
		return;
	}
	
	protected function phpDataTypeConversion($dataType) 
	{
		switch ($dataType) {
			case 'int' :
				return 'int';
				break;
			default :
				return 'string';
		}
	}
	
	protected function getPrimaryKey($table) 
	{
		foreach($table as $metadata) {
			if ($metadata['PRIMARY']) {	
				return $metadata['COLUMN_NAME'];
			}
		}
	}
}