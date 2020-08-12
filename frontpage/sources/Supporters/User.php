<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief       FrontPage Supporters User Node
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
 * Supporters User Node
 */
class _User extends \IPS\Node\Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'frontpage_supporters';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'supporter_';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static $databaseColumnId = 'id';

	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static $databaseIdFields = array( 'supporter_type_id' );
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static $multitonMap	= array();
	
	/**
	 * @brief	[Node] Parent Node ID Database Column
	 */
	public static $parentNodeColumnId = 'group_id';
	
	/**
	 * @brief	[Node] Parent Node Class
	 */
	public static $parentNodeClass = 'IPS\frontpage\Supporters\Group';
	
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static $databaseColumnOrder = 'position';
	
	/**
	 * @brief	[Node] ACP Restrictions
	 */
	protected static $restrictions = array(
		'app'		=> 'frontpage',
		'module'	=> 'extras',
		'prefix'	=> 'supporters_',
	);
	
	/**
	 * Get extras users in a group
	 *
	 * @param	int|null		$group	[Optional] Group to return users from
	 * @return	array
	 */
	public static function extras( $group=NULL )
	{
		if( $group === NULL )
		{
			return static::roots();
		}

		$users	= array();

		foreach ( static::roots() as $user )
		{
			if( $user->group_id === $group )
			{
				$users[]	= $user;
			}
		}
				
		return $users;
	}

	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form )
	{
		$form->add( new \IPS\Helpers\Form\Radio( 'supporter_type', $this->type ?: 'm', TRUE, array(
			'options' 	=> array( 'm' => 'supporter_type_member', 'g' => 'supporter_type_group' ),
			'toggles'	=> array(
				'm'			=> array( 'supporter_id_member', 'supporter_name_toggle', 'supporter_title_toggle', 'supporter_custom_bio_id' ),
				'g'			=> array( 'supporter_id_group' )
			),
		) ) );

		$form->add( new \IPS\Helpers\Form\Select( 'supporter_id_group', $this->type === 'g' ? $this->type_id : NULL, FALSE, array( 'options' => \IPS\Member\Group::groups( TRUE, FALSE ), 'parse' => 'normal' ), NULL, NULL, NULL, 'supporter_id_group' ) );
		$form->add( new \IPS\Helpers\Form\Member( 'supporter_id_member', ( $this->type === 'm' AND !$this->_new ) ? \IPS\Member::load( $this->type_id )->name : NULL, FALSE, array(), function( $val )
		{
			if ( !$val and \IPS\Request::i()->supporter_type === 'm' )
			{
				throw new \DomainException('form_required');
			}
		}, NULL, NULL, 'supporter_id_member' ) );
		$form->add( new \IPS\Helpers\Form\Radio( 'supporter_use_custom_name', ( \IPS\Member::loggedIn()->language()->checkKeyExists("frontpage_extras_supporters_name_{$this->id}") ) ? 1 : 0, FALSE, array( 'options' => array( 0 => 'supporter_custom_name_default', 1 => 'supporter_custom_name_custom' ), 'toggles' => array( 1 => array( 'supporter_custom_name' ) ) ), NULL, NULL, NULL, 'supporter_name_toggle' ) );
		$form->add( new \IPS\Helpers\Form\Translatable( 'supporter_custom_name', NULL, FALSE, array( 'app' => 'frontpage', 'key' => ( $this->id ? "frontpage_extras_supporters_name_{$this->id}" : NULL ) ), NULL, NULL, NULL, 'supporter_custom_name' ) );
		$form->add( new \IPS\Helpers\Form\Radio( 'supporter_use_custom_title', ( \IPS\Member::loggedIn()->language()->checkKeyExists("frontpage_extras_supporters_title_{$this->id}") ) ? 1 : 0, FALSE, array( 'options' => array( 0 => 'supporter_custom_title_default', 1 => 'supporter_custom_title_custom' ), 'toggles' => array( 1 => array( 'supporter_custom_title' ) ) ), NULL, NULL, NULL, 'supporter_title_toggle' ) );
		$form->add( new \IPS\Helpers\Form\Translatable( 'supporter_custom_title', NULL, FALSE, array( 'app' => 'frontpage', 'key' => ( $this->id ? "frontpage_extras_supporters_title_{$this->id}" : NULL ) ), NULL, NULL, NULL, 'supporter_custom_title' ) );
		$form->add( new \IPS\Helpers\Form\Translatable( 'supporter_custom_bio', NULL, FALSE, array(
			'app'			=> 'frontpage',
			'key'			=> ( $this->id ) ? "frontpage_extras_supporters_bio_{$this->id}" : NULL,
			'editor'		=> array(
				'app'			=> 'frontpage',
				'key'			=> 'Supportersdirectory',
				'autoSaveKey'	=> ( $this->id ) ? "supporter-{$this->id}" : 'supporter-new',
				'attachIds'		=> ( $this->id ) ? array( $this->id, NULL, NULL ) : NULL
			),
		), NULL, NULL, NULL, 'supporter_custom_bio_id' ) );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{
		if( isset( $values['supporter_type'] ) )
		{
			try
			{
				if( \IPS\Db::i()->select( '*', 'frontpage_supporters', array( 'supporter_type=? AND supporter_type_id=?', $values['supporter_type'], $values['supporter_type'] === 'm' ? $values['supporter_id_member']->member_id : $values['supporter_id_group'] ) )->first() )
				{
					if( !$this->id OR ( $this->id AND $this->type != $values['supporter_type'] OR $this->type_id != ( $values['supporter_type'] === 'm' ? $values['supporter_id_member']->member_id : $values['supporter_id_group'] ) ) )
					{
						throw new \LogicException( \IPS\Member::loggedIn()->language()->addToStack("only_one_supporter") );
					}
				}
			}
			catch( \UnderflowException $e ){}
		}

		if( isset( $values['supporter_type'] ) AND ( isset( $values['supporter_id_member'] ) OR $values['supporter_id_group'] ) )
		{
			$values['type_id']			= $values['supporter_type'] === 'm' ? $values['supporter_id_member']->member_id : $values['supporter_id_group'];
			unset( $values['supporter_id_group'], $values['supporter_id_member'] );
			$this->type		= $values['supporter_type'];
			$this->type_id	= $values['type_id'];
		}
		
		/* Is this a new entry? */
		if ( !$this->id )
		{
			$this->save();
			\IPS\File::claimAttachments( 'supporter-new', $this->id, NULL, NULL, TRUE );

			$values['position']			= ( \IPS\Db::i()->select( 'MAX(supporter_position)', 'frontpage_supporters' )->first() + 1 );
		}
		else
		{
			\IPS\File::claimAttachments( "supporter-{$this->id}", $this->id, NULL, NULL, TRUE );
		}

		$toUnset	= array();

		if ( isset( $values['supporter_use_custom_name'] ) )
		{
			$toUnset[]	= 'supporter_use_custom_name';
			$toUnset[]	= 'supporter_custom_name';

			\IPS\Lang::deleteCustom( 'frontpage', "frontpage_extras_supporters_name_{$this->id}" );
		}

		if( isset( $values['supporter_custom_name'] ) AND isset( $values['supporter_use_custom_name'] ) AND $values['supporter_use_custom_name'] )
		{
			\IPS\Lang::saveCustom( 'frontpage', "frontpage_extras_supporters_name_{$this->id}", $values['supporter_custom_name'] );
		}

		if ( isset( $values['supporter_use_custom_title'] ) )
		{
			$toUnset[]	= 'supporter_use_custom_title';
			$toUnset[]	= 'supporter_custom_title';

			\IPS\Lang::deleteCustom( 'frontpage', "frontpage_extras_supporters_title_{$this->id}" );
		}
		
		if( isset( $values['supporter_custom_title'] ) AND isset( $values['supporter_use_custom_title'] ) AND $values['supporter_use_custom_title'] )
		{
			\IPS\Lang::saveCustom( 'frontpage', "frontpage_extras_supporters_title_{$this->id}", $values['supporter_custom_title'] );
		}

		if( array_key_exists( 'supporter_custom_bio', $values ) )
		{
			$toUnset[]	= 'supporter_custom_bio';
			if ( isset ( $values['supporter_custom_bio'] ) )
			{
				\IPS\Lang::saveCustom( 'frontpage', "frontpage_extras_supporters_bio_{$this->id}", $values['supporter_custom_bio'] );
			}
		}

		if( \count( $toUnset ) )
		{
			foreach( $toUnset as $_key )
			{
				unset( $values[ $_key ] );
			}
		}

		return $values;
	}
	
	/**
	 * [Node] Get Node Title
	 *
	 * @return	string
	 */
	protected function get__title()
	{
		if ( !$this->id )
		{
			return '';
		}
		
		if ( $this->type === 'm' )
		{
			if( \IPS\Member::loggedIn()->language()->checkKeyExists("frontpage_extras_supporters_name_{$this->id}") and \IPS\Member::loggedIn()->language()->get("frontpage_extras_supporters_name_{$this->id}") )
			{
				return \IPS\Member::loggedIn()->language()->addToStack( "frontpage_extras_supporters_name_{$this->id}", FALSE, array( 'escape' => TRUE ) );
			}

			$member = \IPS\Member::load( $this->type_id );

			if( $member->member_id )
			{
				return $member->name;
			}

			return \IPS\Member::loggedIn()->language()->addToStack('deleted_member');
		}
		else
		{
			try
			{
				return \IPS\Member\Group::load( $this->type_id )->name;
			}
			catch( \OutOfRangeException $e )
			{
				return \IPS\Member::loggedIn()->language()->addToStack('deleted_group');
			}
		}
	}
	
	/**
	 * [Node] Get Node Icon
	 *
	 * @return	string
	 */
	protected function get__icon()
	{
		return $this->type === 'm' ? 'fa-user' : 'fa-group';
	}
	
	/**
	 * @brief	Member
	 */
	public $member;

	/**
	 * Get member data for user
	 *
	 * @return	\IPS\Member
	 */
	public function member()
	{
		if ( $this->member === NULL )
		{
			$this->member =  \IPS\Member::load( $this->type_id );
		}
		
		return $this->member;
	}

	/**
	 * [Node] Does the currently logged in user have permission to edit permissions for this node?
	 *
	 * @return	bool
	 */
	public function canAdd()
	{
		return FALSE;
	}

	/**
	 * [Node] Does the currently logged in user have permission to copy this node?
	 *
	 * @return	bool
	 */
	public function canCopy()
	{
		return FALSE;
	}

	/**
	 * [Node] Does the currently logged in user have permission to edit permissions for this node?
	 *
	 * @return	bool
	 */
	public function canManagePermissions()
	{
		return FALSE;
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	public function delete()
	{
		\IPS\File::unclaimAttachments( 'frontpage_Supportersdirectory', $this->id );
		parent::delete();
		static::updateEmptySetting();
	}
	
	/**
	 * Save Changed Columns
	 *
	 * @return	void
	 */
	public function save()
	{
		parent::save();
		static::updateEmptySetting( FALSE );
	}
	
	/**
	 * Check if there are any records and update setting so we can hide the link if there is nothing
	 *
	 * @param	bool|NULL	$value	If we already know the value (because we've just set it), will save a query
	 * @return	void
	 */
	public static function updateEmptySetting( $value = NULL )
	{
		if ( $value == NULL )
		{
			$value = !( (bool) \IPS\Db::i()->select( 'COUNT(*)', 'frontpage_supporters', NULL, NULL, NULL, NULL, NULL, \IPS\Db::SELECT_FROM_WRITE_SERVER )->first() );
		}
		
		\IPS\Settings::i()->changeValues( array( 'extras_supporters_empty' => $value ) );
	}
}