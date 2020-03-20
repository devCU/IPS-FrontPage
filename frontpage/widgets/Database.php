<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief       FrontPage Database Widget
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4.x
 * @subpackage	FrontPage
 * @version     1.0.4 Stable
 * @source      https://github.com/devCU/IPS-FrontPage
 * @Issue Trak  https://www.devcu.com/devcu-tracker/
 * @Created     25 APR 2019
 * @Updated     20 MAR 2020
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

namespace IPS\frontpage\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Database Widget
 */
class _Database extends \IPS\Widget
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'Database';
	
	/**
	 * @brief	App
	 */
	public $app = 'frontpage';
		
	/**
	 * @brief	Plugin
	 */
	public $plugin = '';

	/**
	 * @brief	HTML if widget is called more than once, we store it.
	 */
	protected static $html = NULL;
	
	/**
	 * Specify widget configuration
	 *
	 * @param	\IPS\Helpers\Form|NULL	$form	Form helper
	 * @return	null|\IPS\Helpers\Form
	 */
	public function configuration( &$form=null )
 	{
		$form = parent::configuration( $form );

 		$databases = array();
	    $disabled  = array();

 		foreach( \IPS\frontpage\Databases::databases() as $db )
 		{
		    $databases[ $db->id ] = $db->_title;

		    if ( $db->fpage_id and $db->fpage_id != \IPS\Request::i()->fpageID )
		    {
			    $disabled[] = $db->id;

				try
				{
					$fpage = \IPS\frontpage\Fpages\Fpage::load( $db->fpage_id );
					$databases[ $db->id ] = \IPS\Member::loggedIn()->language()->addToStack( 'frontpage_db_in_use_by_fpage', NULL, array( 'sprintf' => array( $db->_title, $fpage->full_path ) ) );
				}
				catch( \OutOfRangeException $ex )
				{
					unset( $databases[ $db->id ] );
				}
		    }
 		}

	    if ( ! \count( $databases ) )
	    {
		    $form->addMessage('frontpage_err_no_databases_to_use');
	    }
 		else
	    {
			$form->add( new \IPS\Helpers\Form\Select( 'database', ( isset( $this->configuration['database'] ) ? (int) $this->configuration['database'] : NULL ), FALSE, array( 'options' => $databases, 'disabled' => $disabled ) ) );
	    }

		return $form;
 	}

	/**
	 * Pre save
	 *
	 * @param   array   $values     Form values
	 * @return  array
	 */
	public function preConfig( $values )
	{
		if ( \IPS\Request::i()->fpageID and $values['database'] )
		{
			\IPS\frontpage\Fpages\Fpage::load( \IPS\Request::i()->fpageID )->mapToDatabase( $values['database'] );
		}

		return $values;
	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render()
	{
		if ( static::$html === NULL )
		{
			if ( isset( $this->configuration['database'] ) )
			{
				try
				{
					$database = \IPS\frontpage\Databases::load( \intval( $this->configuration['database'] ) );
					
					if ( ! $database->fpage_id and \IPS\frontpage\Fpages\Fpage::$currentFpage )
					{
						$database->fpage_id = \IPS\frontpage\Fpages\Fpage::$currentFpage->id;
						$database->save();
					}

					static::$html = \IPS\frontpage\Databases\Dispatcher::i()->setDatabase( $database->id )->run();
				}
				catch ( \OutOfRangeException $e )
				{
					static::$html = '';
				}
			}
			else
			{
				return '';
			}
		}
		
		return static::$html;
	}
}