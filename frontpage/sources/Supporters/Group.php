<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief       FrontPage Supporters Group Node
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

namespace IPS\frontpage\Supporters;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Supporters Group Node
 */
class _Group extends \IPS\Node\Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'frontpage_supporters_groups';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'group_';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static $databaseColumnId = 'id';
	
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static $databaseColumnOrder = 'position';
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static $nodeTitle = 'head_frontpage_supporters';
	
	/**
	 * @brief	[Node] Subnode class
	 */
	public static $subnodeClass = 'IPS\frontpage\Supporters\User';
	
	/**
	 * @brief	[Node] Show forms modally?
	 */
	public static $modalForms = TRUE;
	
	/**
	 * @brief	[Node] ACP Restrictions
	 */
	protected static $restrictions = array(
		'app'		=> 'frontpage',
		'module'	=> 'extras',
		'prefix'	=> 'supporters_',
	);

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static $titleLangPrefix = 'frontpage_extras_groups_';
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form )
	{
		/* Title field */
		$form->add( new \IPS\Helpers\Form\Translatable( 'extras_group_title', NULL, TRUE, array( 'app' => 'frontpage', 'key' => ( $this->id ? "frontpage_extras_groups_{$this->id}" : NULL ) ) ) );

		$form->add( new \IPS\Helpers\Form\YesNo( 'extras_group_button', $this->id ? $this->button : NULL, FALSE, array( 'togglesOn' => array( 'extras_group_button_text', 'extras_group_button_url' ) ), NULL, NULL, NULL, 'extras_group_button' ) );
        $form->add( new \IPS\Helpers\Form\Text( 'extras_group_button_text', $this->id ? $this->button_text : NULL, FALSE, array( ), NULL, NULL, NULL, 'extras_group_button_text' ) );
        $form->add( new \IPS\Helpers\Form\Text( 'extras_group_button_url', $this->id ? $this->button_url : NULL, FALSE, array( ), NULL, NULL, NULL, 'extras_group_button_url' ) );

		/* Build the layout selection radios */
		$templates = array();
		foreach ( \IPS\Theme::i()->getRawTemplates( 'frontpage', 'front', 'support', \IPS\Theme::RETURN_ARRAY_BIT_NAMES | \IPS\Theme::RETURN_NATIVE ) as $template )
		{
			if ( mb_strpos( $template, 'layout_' ) === 0 && mb_strpos( $template, '_preview' ) === FALSE )
			{
				$realTemplate = $template . '_preview';
				$templates[ $template ] = \IPS\Theme::i()->getTemplate( 'support', 'frontpage', 'front' )->$realTemplate( );
			}
		}
		$form->add( new \IPS\Helpers\Form\Radio( 'extras_group_template', $this->id ? $this->template : NULL, TRUE, array( 'options' => $templates, 'parse' => 'none' ) ) );
	}

	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{
		if ( !$this->id )
		{
			$this->save();
		}

		if( isset( $values['extras_group_title'] ) )
		{
			\IPS\Lang::saveCustom( 'frontpage', "frontpage_extras_groups_{$this->id}", $values['extras_group_title'] );
			unset( $values['extras_group_title'] );
		}

		if( isset( $values['extras_group_template'] ) )
		{
			$values['template']	= $values['extras_group_template'];
			unset( $values['extras_group_template'] );
		}

		return $values;
	}
	
	/**
	 * [Node] Get buttons to display in tree
	 * Example code explains return value
	 *
	 * @code
	 	array(
	 		array(
	 			'icon'	=>	'plus-circle', // Name of FontAwesome icon to use
	 			'title'	=> 'foo',		// Language key to use for button's title parameter
	 			'link'	=> \IPS\Http\Url::internal( 'app=foo...' )	// URI to link to
	 			'class'	=> 'modalLink'	// CSS Class to use on link (Optional)
	 		),
	 		...							// Additional buttons
	 	);
	 * @endcode
	 * @param	string	$url		Base URL
	 * @param	bool	$subnode	Is this a subnode?
	 * @return	array
	 */
	public function getButtons( $url, $subnode=FALSE )
	{
		$buttons = parent::getButtons( $url, $subnode );
		
		if ( isset( $buttons['add'] ) )
		{
			$buttons['add']['title'] = 'extras_add_record';
		}
		
		return $buttons;
	}
	
	/**
	 * [Node] Does the currently logged in user have permission to edit permissions for this node?
	 *
	 * @return	bool
	 */
	public function canManagePermissions()
	{
		return false;
	}

	/**
	 * Get members
	 *
	 * @return	array
	 */
	public function members()
	{
		$members = array();
		foreach ( $this->children() as $child )
		{
			if ( $child->type === 'm' )
			{
				$members[ $child->type_id ] = $child;
			}
			else
			{
				foreach ( \IPS\Db::i()->select( '*', 'core_members', array( 'member_group_id=? OR FIND_IN_SET( ?, mgroup_others )', $child->type_id, $child->type_id ), 'name' ) as $m )
				{
					if ( !isset( $members[ $m['member_id'] ] ) )
					{
						$member = new User;
						$memberObj = \IPS\Member::constructFromData( $m );
						$member->member = $memberObj;
						$members[ $memberObj->member_id ] = $member;
					}
				}
			}
		}
		
		return $members;
	}
}