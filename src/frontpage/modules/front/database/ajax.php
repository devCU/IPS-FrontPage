<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief		FrontPage Ajax only methods
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

namespace IPS\frontpage\modules\front\database;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Ajax only methods
 */
class _ajax extends \IPS\Dispatcher\Controller
{
	/**
	 * Return a FURL
	 *
	 * @return	void
	 */
	protected function makeFurl()
	{
		return \IPS\Output::i()->json( array( 'slug' => \IPS\Http\Url\Friendly::seoTitle( \IPS\Request::i()->slug ) ) );
	}

	/**
	 * Find Record
	 *
	 * @return	void
	 */
	public function findRecord()
	{
		$results  = array();
		$database = \IPS\frontpage\Databases::load( \IPS\Request::i()->id );
		$input    = mb_strtolower( \IPS\Request::i()->input );
		$field    = "field_" . $database->field_title;
		$class    = '\IPS\frontpage\Records' . $database->id;
		$category = '';

		$where = array( $field . " LIKE CONCAT('%', ?, '%')" );
		$binds = array( $input );

		foreach ( \IPS\Db::i()->select( '*', 'frontpage_custom_database_' . $database->id, array_merge( array( implode( ' OR ', $where ) ), $binds ), 'LENGTH(' . $field . ') ASC', array( 0, 20 ) ) as $row )
		{
			$record = $class::constructFromData( $row );
			
			if ( ! $record->canView() )
			{
				continue;
			}
			
			if ( $database->use_categories )
			{
				$category = \IPS\Member::loggedIn()->language()->addToStack( 'frontpage_autocomplete_category', NULL, array( 'sprintf' => array( $record->container()->_title ) ) );
			}

			$results[] = array(
				'id'	   => $record->_id,
				'value'    => $record->_title,
				'category' => $category,
				'date'	   => \IPS\DateTime::ts( $record->record_publish_date )->html(),
			);
		}

		\IPS\Output::i()->json( $results );
	}
	
}