<?php


/**
 * This class defines the structure of the 'ar_itc_organizations_debug_copy' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 * @package    lib.model.map
 */
class ArItcOrganizationsDebugCopyTableMap extends TableMap {

	/**
	 * The (dot-path) name of this class
	 */
	const CLASS_NAME = 'lib.model.map.ArItcOrganizationsDebugCopyTableMap';

	/**
	 * Initialize the table attributes, columns and validators
	 * Relations are not initialized by this method since they are lazy loaded
	 *
	 * @return     void
	 * @throws     PropelException
	 */
	public function initialize()
	{
	  // attributes
		$this->setName('ar_itc_organizations_debug_copy');
		$this->setPhpName('ArItcOrganizationsDebugCopy');
		$this->setClassname('ArItcOrganizationsDebugCopy');
		$this->setPackage('lib.model');
		$this->setUseIdGenerator(true);
		// columns
		$this->addPrimaryKey('ID', 'Id', 'INTEGER', true, null, null);
		$this->addColumn('LAST_UPDATE', 'LastUpdate', 'TIMESTAMP', false, null, null);
		$this->addColumn('ORG', 'Org', 'VARCHAR', false, 2048, null);
		$this->addColumn('NAME', 'Name', 'VARCHAR', false, 2048, null);
		$this->addColumn('DESCRIPTION', 'Description', 'VARCHAR', false, 2048, null);
		$this->addColumn('ACCOUNTCODE', 'Accountcode', 'VARCHAR', false, 2048, null);
		$this->addColumn('PARENT', 'Parent', 'VARCHAR', false, 2048, null);
		$this->addColumn('EMAIL', 'Email', 'VARCHAR', false, 1024, null);
		// validators
	} // initialize()

	/**
	 * Build the RelationMap objects for this table relationships
	 */
	public function buildRelations()
	{
	} // buildRelations()

	/**
	 * 
	 * Gets the list of behaviors registered for this table
	 * 
	 * @return array Associative array (name => parameters) of behaviors
	 */
	public function getBehaviors()
	{
		return array(
			'symfony' => array('form' => 'true', 'filter' => 'true', ),
		);
	} // getBehaviors()

} // ArItcOrganizationsDebugCopyTableMap
