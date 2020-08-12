<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief       FrontPage WYSIWYG Widget
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4.10 FINAL
 * @subpackage	FrontPage
 * @version     1.0.5 Stable
 * @source      https://github.com/devCU/IPS-FrontPage
 * @Issue Trak  https://www.devcu.com/devcu-tracker/
 * @Created     25 APR 2019
 * @Updated     12 AUG 2020
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
 * WYSIWYG Widget
 */
class _Wysiwyg extends \IPS\Widget\StaticCache
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'Wysiwyg';
	
	/**
	 * @brief	App
	 */
	public $app = 'frontpage';
		
	/**
	 * @brief	Plugin
	 */
	public $plugin = '';

	/**
	 * Specify widget configuration
	 *
	 * @param	\IPS\Helpers\Form|NULL	$form	Form helper
	 * @return	null|\IPS\Helpers\Form
	 */
	public function configuration( &$form=null )
 	{
		$form = parent::configuration( $form );
 		
		$form->add( new \IPS\Helpers\Form\Editor( 'content', ( isset( $this->configuration['content'] ) ? $this->configuration['content'] : NULL ), FALSE, array(
			'app'			=> $this->app,
			'key'			=> 'Widgets',
			'autoSaveKey' 	=> 'widget-' . $this->uniqueKey,
			'attachIds'	 	=> isset( $this->configuration['content'] ) ? array( 0, 0, $this->uniqueKey ) : NULL
		) ) );
		
		return $form;
 	}
 	
 	/**
	 * Before the widget is removed, we can do some clean up
	 *
	 * @return void
	 */
	public function delete()
	{
		foreach( \IPS\Db::i()->select( '*', 'core_attachments_map', array( array( 'location_key=? and id3=?', 'frontpage_Widgets', $this->uniqueKey ) ) ) as $map )
		{
			try
			{				
				$attachment = \IPS\Db::i()->select( '*', 'core_attachments', array( 'attach_id=?', $map['attachment_id'] ) )->first();
				
				\IPS\Db::i()->delete( 'core_attachments_map', array( array( 'attachment_id=?', $attachment['attach_id'] ) ) );
				\IPS\Db::i()->delete( 'core_attachments', array( 'attach_id=?', $attachment['attach_id'] ) );
				
				
				\IPS\File::get( 'core_Attachment', $attachment['attach_location'] )->delete();
				if ( $attachment['attach_thumb_location'] )
				{
					\IPS\File::get( 'core_Attachment', $attachment['attach_thumb_location'] )->delete();
				}
			}
			catch ( \Exception $e ) { }
		}
	}
	
 	/**
 	 * Pre-save config method
 	 *
 	 * @param	array	$values		Form values
 	 * @return void
 	 */
 	public function preConfig( $values=array() )
 	{
	 	\IPS\File::claimAttachments( 'widget-' . $this->uniqueKey, 0, 0, $this->uniqueKey );
	 	
	 	return $values;
 	}
 	
	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render()
	{
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'front_core.js', 'core' ) );
		return $this->output( isset( $this->configuration['content'] ) ? $this->configuration['content'] : '' );
	}
}