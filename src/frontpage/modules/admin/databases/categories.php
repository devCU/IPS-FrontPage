<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief		Fields Model
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4+
 * @subpackage	FrontPage
 * @version     1.0.0
 * @source      https://github.com/devCU/IPS-FrontPage
 * @Issue Trak  https://www.devcu.com/devcu-tracker/
 * @Created     25 APR 2019
 * @Updated     02 MAY 2019
 *
 *                    GNU General Public License v3.0
 *    This program is free software: you can redistribute it and/or modify       
 *    it under the terms of the GNU General Public License as published by       
 *    the Free Software Foundation, either version 3 of the License, or          
 *    (at your option) any later version.                                        
 *                                                                               
 *    This program is distributed in the hope that it will be useful,            
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of             
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *                                                                               
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see http://www.gnu.org/licenses/
 */

namespace IPS\frontpage\modules\admin\databases;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * categories
 */
class _categories extends \IPS\Node\Controller
{
	/**
	 * Node Class
	 */
	protected $nodeClass = '\IPS\frontpage\Categories';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		/* This controller can not be accessed without a database ID */
		if( !\IPS\Request::i()->database_id )
		{
			\IPS\Output::i()->error( 'node_error', '2S390/1', 404, '' );
		}

		$this->url = $this->url->setQueryString( array( 'database_id' => \IPS\Request::i()->database_id ) );
		
		/* Assign the correct nodeClass so contentItem is specified */
		$this->nodeClass = '\IPS\frontpage\Categories' . \IPS\Request::i()->database_id;
		
		\IPS\Dispatcher::i()->checkAcpPermission( 'categories_manage' );
		
		$nodeClass = $this->nodeClass;

		$childLang = \IPS\Member::loggedIn()->language()->addToStack( $nodeClass::$nodeTitle . '_add_child' );
		$nodeClass::$nodeTitle = \IPS\Member::loggedIn()->language()->addToStack('content_cat_db_title', FALSE, array( 'sprintf' => array( \IPS\frontpage\Databases::load( \IPS\Request::i()->database_id )->_title ) ) );
		\IPS\Member::loggedIn()->language()->words[ $nodeClass::$nodeTitle . '_add_child' ] = $childLang;
		parent::execute();
	}
	
	/**
	 * Get Root Rows
	 *
	 * @return	array
	 */
	public function _getRoots()
	{
		$nodeClass = $this->nodeClass;
		$rows = array();
	
		foreach( $nodeClass::roots( NULL ) as $node )
		{
			if ( $node->database_id == \IPS\Request::i()->database_id )
			{
				$rows[ $node->_id ] = $this->_getRow( $node );
			}
		}
	
		return $rows;
	}

	/**
	 * Function to execute after nodes are reordered. Do nothing by default but plugins can extend.
	 *
	 * @param	array	$order	The new ordering that was saved
	 * @return	void
	 * @note	Contents needs to readjust category_full_path values when a category is moved to a different category
	 */
	protected function _afterReorder( $order )
	{
		$categoryClass = $this->nodeClass;

		foreach( $order as $parent => $nodes )
		{
			foreach ( $nodes as $id => $position )
			{
				$categoryClass::resetPath( $id );
			}
		}

		return parent::_afterReorder( $order );
	}
}