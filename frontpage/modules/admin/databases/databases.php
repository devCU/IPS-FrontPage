<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.devcu.com/donate
 *
 * @brief		Databases Model
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.5x
 * @subpackage	FrontPage
 * @version     1.0.5 Stable
 * @source      https://github.com/devCU/IPS-FrontPage
 * @Issue Trak  https://www.devcu.com/devcu-tracker/
 * @Created     25 APR 2019
 * @Updated     19 OCT 2020
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
 * databases
 */
class _databases extends \IPS\Node\Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected $nodeClass = '\IPS\frontpage\Databases';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'databases_manage' );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Create the table */
		$table = new \IPS\Helpers\Table\Db( 'frontpage_databases', \IPS\Http\Url::internal( 'app=frontpage&module=databases&controller=databases' ) );
		$table->langPrefix = 'content_';

		/* Columns */
		$table->joins = array(
			array( 'select' => 'w.word_custom', 'from' => array( 'core_sys_lang_words', 'w' ), 'where' => "w.word_key=CONCAT( 'content_db_', frontpage_databases.database_id ) AND w.lang_id=" . \IPS\Member::loggedIn()->language()->id )
		);

		$table->include = array( 'word_custom', 'database_record_count', 'database_category_count' );
		$table->widths  = array(
			'word_custom' => '50'
		);
		
		$table->mainColumn = 'word_custom';
		$table->quickSearch = 'word_custom';
		
		$table->sortBy = $table->sortBy ?: 'word_custom';
		$table->sortDirection = $table->sortDirection ?: 'asc';
		
		/* Parsers */
		$table->parsers = array(
				'word_custom'	=> function( $val, $row )
				{
					$fpage     = NULL;
					$database = NULL;

					try
					{
						$database = \IPS\frontpage\Databases::load( $row['database_id'] );

						if ( $database->fpage_id > 0 )
						{
							try
							{
								$fpage = \IPS\frontpage\Fpages\Fpage::load( $database->fpage_id );
							}
							catch ( \OutOfRangeException $ex )
							{
								$database->fpage_id = 0;
								$database->save();
							}
						}
					}
					catch ( \OutOfRangeException $ex )
					{

					}

					return \IPS\Theme::i()->getTemplate( 'databases' )->manageDatabaseName( $database, $row, $fpage );
				},
				'database_category_count' => function( $val, $row )
				{
					try
					{
						$database = \IPS\frontpage\Databases::load( $row['database_id'] );

						if ( ! $database->use_categories )
						{
							return \IPS\Member::loggedIn()->language()->addToStack('frontpage_db_cats_disabled');
						}
					}
					catch ( \OutOfRangeException $ex )
					{

					}

					/* This sucks but adding a COUNT into a join breaks the query and sub selects not easy with DB driver */
					return \IPS\Db::i()->select( 'COUNT(*)', 'frontpage_database_categories', array( 'category_database_id=?', $row['database_id'] ) )->first();
				},
		        'database_record_count' => function( $val, $row )
				{
					return \IPS\Db::i()->select( 'SUM(category_records)', 'frontpage_database_categories', array( 'category_database_id=?', $row['database_id'] ) )->first();
				}
		);

		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'frontpage', 'databases', 'databases_add' ) )
		{
			/* Buttons */
			\IPS\Output::i()->sidebar['actions']['add'] = array(
				'primary'	=> true,
				'title'	=> 'add',
				'icon'	=> 'plus',
				'link'	=> \IPS\Http\Url::internal( 'app=frontpage&module=databases&controller=databases&do=add' ),
				'data'	=>  array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('frontpage_database_add') )
			);
		}


		/*if ( \IPS\Application::appIsEnabled('forums') )
		{
			\IPS\Output::i()->sidebar['actions']['promote'] = array(
				'title'	=> 'frontpage_database_promote',
				'icon'	=> 'comments',
				'link'	=> \IPS\Http\Url::internal( 'app=frontpage&module=databases&controller=databases&do=promote' ),
				'data'	=>  array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('frontpage_database_promote') )
			);
		}*/
		
		$table->rowButtons = function( $row )
		{
			$return = array();
			
			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'frontpage', 'databases', 'databases_edit' ) )
			{
				$return['edit']	= array(
					'title'	=> 'edit',
					'icon'	=> 'pencil',
					'link'	=> \IPS\Http\Url::internal( 'app=frontpage&module=databases&controller=databases&do=form&id=' . $row['database_id'] ),
				);
			}
			
			$return['records']	= array(
				'title'	=> 'content_database_manage_records',
				'icon'	=> 'file-text-o',
				'link'	=> \IPS\Http\Url::internal( 'app=frontpage&module=databases&controller=records&database_id=' . $row['database_id'] ),
			);
			
			$return['permissions'] = array(
					'title'	=> 'content_database_manage_permissions',
					'icon'	=> 'lock',
					'link'	=> \IPS\Http\Url::internal( 'app=frontpage&module=databases&controller=databases&do=permissions&id=' . $row['database_id'] ),
			);

			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'frontpage', 'categories', 'categories_manage' ) and $row['database_use_categories'] )
			{
				$return['categories'] = array(
					'title'	=> 'content_database_manage_categories',
					'icon'	=> 'folder-o',
					'link'	=> \IPS\Http\Url::internal( 'app=frontpage&module=databases&controller=categories&database_id=' . $row['database_id'] ),
				);
			}
			
			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'frontpage', 'fields', 'frontpage_fields_manage' ) )
			{
				$return['fields'] = array(
					'title'	=> 'content_database_manage_fields',
					'icon'	=> 'tasks',
					'link'	=> \IPS\Http\Url::internal( 'app=frontpage&module=databases&controller=fields&database_id=' . $row['database_id'] ),
				);
			}

			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'frontpage', 'databases', 'databases_edit' ) )
			{
				$return['download']	= array(
					'title'	=> 'download',
					'icon'	=> 'download',
					'link'	=> \IPS\Http\Url::internal( 'app=frontpage&module=databases&controller=databases&do=download&id=' . $row['database_id'] ),
					'data'	=> array(
						'controller'	=> 'frontpage.admin.databases.download',
						'downloadURL'	=> \IPS\Http\Url::internal( "app=frontpage&module=databases&controller=databases&do=download&id=" . $row['database_id'] . '&go=true' )
					)
				);
			}

			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'frontpage', 'databases', 'databases_delete' ) )
			{
				$return['delete'] = array(
					'title'	=> 'delete',
					'icon'	=> 'times-circle',
					'link'	=> \IPS\Http\Url::internal( 'app=frontpage&module=databases&controller=databases&do=delete&id=' . $row['database_id'] ),
					'data'  => array( 'delete' => '' )
				);
			}
			
			return $return;
		};

		/* Javascript */
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'admin_databases.js', 'frontpage', 'admin' ) );
		
		/* Display */
		\IPS\Output::i()->output = (string) $table;
		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack('menu__frontpage_databases_databases');
	}

	/**
	 * Add a database dialog
	 *
	 * @return void
	 */
	public function add()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'databases_add' );

		$form = new \IPS\Helpers\Form( 'form', 'next' );

		$form->addTab( 'frontpage_database_add_new' );
		$form->addMessage( \IPS\Member::loggedIn()->language()->addToStack('frontpage_add_db_normal') );
		$form->addTab( 'frontpage_database_add_upload' );
		$form->add( new \IPS\Helpers\Form\Upload( 'frontpage_database_import', NULL, FALSE, array( 'allowedFileTypes' => array( 'xml' ), 'temporary' => TRUE ), NULL, NULL, NULL, 'frontpage_database_import' ) );

		if ( $values = $form->values() )
		{
			if ( $values['frontpage_database_import'] )
			{
				/* Move it to a temporary location */
				$tempFile = tempnam( \IPS\TEMP_DIRECTORY, 'IPS' );
				move_uploaded_file( $values['frontpage_database_import'], $tempFile );

				/* Initate a redirector */
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=frontpage&module=databases&controller=databases&do=import' )->setQueryString( array( 'file' => $tempFile, 'key' => md5_file( $tempFile ) ) )->csrf() );
			}
			else
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=frontpage&module=databases&controller=databases&do=form' ) );
			}
		}

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global', 'core', 'admin' )->block( 'frontpage_database_add', $form, FALSE );
	}

	/**
	 * Import from upload
	 *
	 * @return	void
	 */
	public function import()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'databases_add' );
		\IPS\Session::i()->csrfCheck();

		if ( !file_exists( \IPS\Request::i()->file ) or md5_file( \IPS\Request::i()->file ) !== \IPS\Request::i()->key )
		{
			\IPS\Output::i()->error( 'generic_error', '3T259/3', 403, '' );
		}

		\IPS\Output::i()->output = new \IPS\Helpers\MultipleRedirect(
			\IPS\Http\Url::internal( 'app=frontpage&module=databases&controller=databases&do=import' )->setQueryString( array( 'file' => \IPS\Request::i()->file, 'key' =>  \IPS\Request::i()->key ) )->csrf(),
			function( $data )
			{
				/* Open XML file */
				$xml = new \IPS\Xml\XMLReader;
				$xml->open( \IPS\Request::i()->file );

				if ( ! @$xml->read() )
				{
					@unlink( \IPS\Request::i()->file );
					\IPS\Output::i()->error( 'xml_upload_invalid', '2C163/1', 403, '' );
				}

				/* Is this the first batch? */
				$database = null;
				$i        = 0;
				if ( !\is_array( $data ) )
				{
					$database = new \IPS\frontpage\Databases;
					$database->key  = 'import_' . mt_rand();
					$database->save();

					/* Set default perms, these will be editable post DB import */
					\IPS\Db::i()->replace( 'core_permission_index', array(
						'app'			=> 'frontpage',
						'perm_type'		=>  $database::$permType,
						'perm_type_id'	=> $database->id,
						'perm_view'		=> '*',
						'perm_2'		=> '*',  #read
						'perm_3'		=> \IPS\Settings::i()->admin_group,  #add
						'perm_4'		=> \IPS\Settings::i()->admin_group,  #edit
						'perm_5'		=> \IPS\Settings::i()->admin_group,  #reply
						'perm_6'		=> \IPS\Settings::i()->admin_group,  #rate
						'perm_7'		=> \IPS\Settings::i()->admin_group,  #review
					) );

					$json  = json_decode( @file_get_contents( \IPS\ROOT_PATH . "/applications/frontpage/data/databaseschema.json" ), true );
					$table = $json['frontpage_custom_database_1'];

					$table['name'] = 'frontpage_custom_database_' . $database->id;

					foreach( $table['columns'] as $name => $data )
					{
						if ( mb_substr( $name, 0, 6 ) === 'field_' )
						{
							unset( $table['columns'][ $name ] );
						}
					}

					foreach( $table['indexes'] as $name => $data )
					{
						if ( mb_substr( $name, 0, 6 ) === 'field_' )
						{
							unset( $table['indexes'][ $name ] );
						}
					}

					try
					{
						if ( ! \IPS\Db::i()->checkForTable( $table['name'] ) )
						{
							\IPS\Db::i()->createTable( $table );
						}
					}
					catch( \IPS\Db\Exception $ex )
					{
						throw new \LogicException( $ex );
					}
					
					$langs = array();
					foreach( array( 'content_db_lang_sl', 'content_db_lang_pl', 'content_db_lang_su', 'content_db_lang_pu', 'content_db_lang_ia' ) as $lang )
					{
						if ( $xml->getAttribute( $lang ) )
						{
							$langs[ $lang ] = $xml->getAttribute( $lang );
							
							\IPS\Lang::saveCustom( 'frontpage', $lang . "_" . $database->id, $xml->getAttribute( $lang ) );

							if ( $lang === 'content_db_lang_pu' )
							{
								\IPS\Lang::saveCustom( 'frontpage', $lang . "_" . $database->id . '_pl', $xml->getAttribute( $lang ) );
							}
						}
					}
					
					/* Other data */
					while ( $xml->read() )
					{
						$name = NULL;
						$desc = NULL;
						
						if ( $xml->name == 'data' )
						{
							/* Life is too short otherwise */
							$node = new \SimpleXMLElement( $xml->readOuterXML() );
							foreach( $node->attributes() as $k => $v )
							{
								$database->$k = (string) $v;
							}

							/* Any kids of your own? */
							foreach( $node->children() as $k => $v )
							{
								if ( $k === 'name' )
								{
									$name = $v;
								}
								else if ( $k === 'description' )
								{
									$desc = $v;
								}
								else
								{
									$tryJson = json_decode( $v, TRUE );
									$database->$k =  ( $tryJson ) ? $tryJson : (string) $v;
								}
							}
							
							\IPS\Lang::saveCustom( 'frontpage', "content_db_" . $database->id, $name );
							\IPS\Lang::saveCustom( 'frontpage', "content_db_" . $database->id . '_desc', $desc );
							\IPS\Lang::saveCustom( 'frontpage', "digest_area_frontpage_records" . $database->id, $langs['content_db_lang_pu'] );
							\IPS\Lang::saveCustom( 'frontpage', "digest_area_frontpage_categories" . $database->id, $langs['content_db_lang_pu'] );
		
							$menu	= array();
		
							foreach( \IPS\Lang::languages() as $id => $lang )
							{
								$menu[ $lang->_id ] = \sprintf( $lang->get('frontpage_create_menu_records_x'), $langs['content_db_lang_su'], $name );
							}
		
							/* Notification, search/followed/new content langs */
							\IPS\Lang::saveCustom( 'frontpage', "frontpage_create_menu_records_" . $database->id, $menu );
							\IPS\Lang::saveCustom( 'frontpage', "frontpage_records" . $database->id . '_pl', $langs['content_db_lang_su'] );
							\IPS\Lang::saveCustom( 'frontpage', "module__frontpage_records" . $database->id, $name );
					
							break;
						}
					}

					$database->save();

					/* set up some stores (and make sure they are clean from previous possibly broken imports */
					\IPS\Data\Store::i()->db_import_cat_map = array();
					\IPS\Data\Store::i()->db_import_cat_parent_map = array();
					\IPS\Data\Store::i()->db_import_field_map = array();
					\IPS\Data\Store::i()->db_import_db_id = $database->id;

					/* Start impoprting */
					$data = array( 'next' => 'field', 'done' => 0 );
					return array( $data, \IPS\Member::loggedIn()->language()->addToStack('processing') );
				}

				$database = \IPS\frontpage\Databases::load( \IPS\Data\Store::i()->db_import_db_id );

				$xml->read();
				$next = NULL;
				$areas = array( 'field', 'category', 'template' );
				
				if ( $data['next'] )
				{
					while ( $xml->read() )
					{
						if( ! \in_array( $xml->name, $areas ) OR $xml->nodeType != \XMLReader::ELEMENT )
						{
							continue;
						}
						
						$i++;
						
						if ( $data['done'] )
						{
							if ( $i - 1 < $data['done'] )
							{
								$xml->next();
								continue;
							}
						}
						
						$doneSomething = false;		
						if ( $xml->name == $data['next'] )
						{
							$data['done']++;
							
							$areaName = $xml->name;
							$node = new \SimpleXMLElement( $xml->readOuterXML() );
							
							/* Import */
							switch ( $areaName )
							{
								case 'field':
									$attrs = array();
									$perms = array();
									foreach( $node->attributes() as $k => $v )
									{
										if ( mb_substr( $k, 0, 5 ) === 'perm_' or \in_array( $k, array( 'app', 'owner_only', 'friend_only', 'authorized_users' ) ) )
										{
											$perms[ $k ] = (string) $v;
										}
										else
										{
											$attrs[ $k ] = (string) $v;
										}
									}
									
									/* Any kids of your own? */
									$displayJson = NULL;
									foreach( $node->children() as $k => $v )
									{
										if ( $k == 'field_name' )
										{
											$k = 'field_title';
										}

										$tryJson = json_decode( $v, TRUE );
										
										if ( $k === 'field_display_json' AND \is_array( $tryJson ) )
										{
											$displayJson = $tryJson;
											continue;
										}
										
										$attrs[ $k ] = ( $tryJson ) ? $tryJson : (string) $v;
									}
									
									/* Check to ensure we have the same field type, if not default to text so data is retained (buildHelper also does this */
									if ( ! class_exists( '\IPS\frontpage\Fields\\' . mb_ucfirst( $attrs['field_type'] ) ) and ! class_exists( '\IPS\Helpers\Form\\' . mb_ucfirst( $attrs['field_type'] ) ) )
									{
										$attrs['field_type'] = 'Text';
									}
									
									$attrs['_skip_formatting'] = TRUE;

									$originalFieldId = $attrs['field_id'];
									unset( $attrs['field_id'] );

									$fieldsClass = 'IPS\frontpage\Fields' . $database->id;

									$obj = new $fieldsClass;
									$values = $obj->formatFormValues( $attrs );
									
									if ( isset( $attrs['field_extra'] ) )
									{
										/* The export format is always correct */
										$values['field_extra'] = $attrs['field_extra'];
									}
									
									$obj->saveForm( $values );
							
									if ( $displayJson )
									{
										$obj->display_json = $displayJson;
										$obj->save();
									}
									
									if ( $originalFieldId == $database->field_title )
									{
										$database->field_title = $obj->id;
										$database->save();
									}
									else if ( $originalFieldId == $database->field_content )
									{
										$database->field_content = $obj->id;
										$database->save();
									}
									
									$map = \IPS\Data\Store::i()->db_import_field_map;

									$map[ $originalFieldId ] = $obj->id;

									\IPS\Data\Store::i()->db_import_field_map = $map;
									
									$existingPerms	= $obj->permissions();
									$newPerms		= array_merge( $perms, array( 'perm_id' => $existingPerms['perm_id'], 'perm_type_id' => $obj->id ) );

									/* Set default permissions if not defined in export */
									if( !isset( $perms['perm_view'] ) )
									{
										$newPerms['perm_view'] = '*';
									}

									if( !isset( $perms['perm_2'] ) )
									{
										$newPerms['perm_2'] = \IPS\Settings::i()->admin_group;
									}

									if( !isset( $perms['perm_3'] ) )
									{
										$newPerms['perm_3'] = \IPS\Settings::i()->admin_group;
									}
	
									\IPS\Db::i()->update( 'core_permission_index', $newPerms, array( 'perm_id=?', $existingPerms['perm_id'] ) );

									\IPS\Lang::saveCustom( 'frontpage', "content_field_" . $obj->id, $attrs['field_title'] );

									if ( isset($attrs['field_description']) )
									{
										\IPS\Lang::saveCustom( 'frontpage', "content_field_" . $obj->id . '_desc', $attrs['field_description'] );
									}

									if ( isset($attrs['field_validator_error']) )
									{
										\IPS\Lang::saveCustom( 'frontpage', "content_field_" . $obj->id . '_validation_error', $attrs['field_validator_error'] );
									}
									$next = 'category';
									$doneSomething = true;
								break;

								case 'category':
									$attrs = array();
									$perms = array();
									foreach( $node->attributes() as $k => $v )
									{
										if ( mb_substr( $k, 0, 5 ) === 'perm_' or \in_array( $k, array( 'app', 'owner_only', 'friend_only', 'authorized_users' ) ) )
										{
											$perms[ $k ] = (string) $v;
										}
										else
										{

											$attrs[ $k ] = ( \in_array( $k, array( 'category_parent_id' ) ) ) ? (int) $v : (string) $v;
										}
									}

									/* Any kids of your own? */
									foreach( $node->children() as $k => $v )
									{
										$tryJson = json_decode( $v, TRUE );
										$attrs[ $k ] =  ( $tryJson ) ? $tryJson : (string) $v;
									}

									$originalCategoryId = $attrs['category_id'];
									$originalParentId   = $attrs['category_parent_id'];

									unset( $attrs['category_id'] );
									$attrs['category_parent_id'] = 0;

									/* Create a category */
									$category = new \IPS\frontpage\Categories;
									$category->database_id = $database->id;
									$category::$permType = 'categories_' . $database->id;
									
									$category->saveForm( $category->formatFormValues( $attrs ) );
									
									if ( $category->fields AND $category->fields !== '*' )
									{
										if ( \is_array( $category->fields ) )
										{
											$field_map = \IPS\Data\Store::i()->db_import_field_map;
											$newMap    = array();
											
											foreach( $category->fields as $fid )
											{
												if ( isset( $field_map[ $fid ] ) )
												{
													$newMap[] = $field_map[ $fid ];
												}
											}
											
											if ( \count( $newMap ) )
											{
												$category->fields = json_encode( $newMap );
												$category->save();
											}
										}
									}
									
									$cat_map = \IPS\Data\Store::i()->db_import_cat_map;
									$parent_map = \IPS\Data\Store::i()->db_import_cat_parent_map;

									$cat_map[ $originalCategoryId ] = $category->id;
									$parent_map[ $category->id ] = $originalParentId;

									\IPS\Data\Store::i()->db_import_cat_map = $cat_map;
									\IPS\Data\Store::i()->db_import_cat_parent_map = $parent_map;

									/* Perms */
									$existingPerms = $category->permissions();

									\IPS\Db::i()->update( 'core_permission_index', array_merge( $perms, array( 'perm_id' => $existingPerms['perm_id'], 'perm_type_id' => $category->id ) ), array( 'perm_id=?', $existingPerms['perm_id'] ) );
									$next = 'template';
									$doneSomething = true;
								break;

								case 'template':
									$templates = \IPS\frontpage\Theme::i()->getRawTemplates( 'frontpage', 'database', NULL, \IPS\frontpage\Theme::RETURN_ALL );

									$attrs = array();
									foreach( $node->attributes() as $k => $v )
									{
										$attrs[ $k ] = (string) $v;
									}

									/* Any kids of your own? */
									foreach( $node->children() as $k => $v )
									{
										$tryJson = json_decode( $v, TRUE );
										$attrs[ $k ] =  ( $tryJson ) ? $tryJson : (string) $v;
									}

									
									$obj = new \IPS\frontpage\Templates;
									$obj->location       = $attrs['template_location'];
									$obj->group          = $attrs['template_group'];
									$obj->title          = $attrs['template_title'];
									$obj->params	     = $attrs['template_params'];
									$obj->content        = $attrs['template_content'];
									$obj->original_group = $attrs['template_original_group'];
									$obj->user_created   = 1;
									$obj->user_edited    = 1;
									$obj->desc           = '';
									
									if ( $attrs['template_group'] !== 'template_form' )
									{
										$obj->group .= '_' . $database->id;
										
										foreach( array( 'template_listing', 'template_display', 'template_categories', 'template_form', 'template_featured' ) as $name )
										{
											if ( $database->$name == $attrs['template_group'] )
											{
												$database->$name = $obj->group;
												$database->save();
											}
										}
									}
									else
									{
										$obj->title .= '_' . $database->_id;
										
										$database->template_form = $obj->title;
										$database->save();
									}
									
									$obj->save();

									$obj->key = 'database_' . \IPS\Http\Url\Friendly::seoTitle( $obj->group ) . '_' . \IPS\Http\Url\Friendly::seoTitle( $obj->title ) . '_' . $obj->id;
									$obj->save();
									$doneSomething = true;
									$next = NULL;
								break;
							}
							
							if ( $i % 10 === 0 )
							{
								return array( $data, \IPS\Member::loggedIn()->language()->addToStack('frontpage_db_import_progress', FALSE, array( 'sprintf' => array( \ucfirst( $data['next'] ) ) ) ) );
							}
							
							$xml->next();
						}
						else
						{
							/* Did we do anything? if not, skip to the next section */
							if ( ! $doneSomething )
							{
								switch ( $data['next'] )
								{
									case 'field':
										$data['next'] = 'category';
									break;
									case 'category':
										$data['next'] = 'template';
									break;
									case 'template':
										$data['next'] = null;
									break;
								}
								
								if ( $data['next'] )
								{
									return array( $data, \IPS\Member::loggedIn()->language()->addToStack('frontpage_db_import_progress', FALSE, array( 'sprintf' => array( \ucfirst( $data['next'] ) ) ) ) );
								}
							}
							
							$xml->next();
						}
					}

					/* Done */
					$data['next'] = $next;
					
					return array( $data, \IPS\Member::loggedIn()->language()->addToStack('frontpage_db_import_progress', FALSE, array( 'sprintf' => array( \ucfirst( $data['next'] ) ) ) ) );
				}
				else
				{
					return NULL;
				}
			},
			function()
			{
				/* Remap categories */
				if ( isset( \IPS\Data\Store::i()->db_import_cat_map ) and isset( \IPS\Data\Store::i()->db_import_cat_parent_map ) )
				{
					foreach( \IPS\Data\Store::i()->db_import_cat_parent_map as $new => $oldParent )
					{
						if ( $oldParent > 0 )
						{
							if ( isset( \IPS\Data\Store::i()->db_import_cat_map[ $oldParent ] ) )
							{
								$cat = \IPS\frontpage\Categories::load( $new );
								$cat->parent_id = \IPS\Data\Store::i()->db_import_cat_map[ $oldParent ];
								$cat->save();
							}
						}
					}
				}

				$databaseId = \IPS\Data\Store::i()->db_import_db_id;
				$database = \IPS\frontpage\Databases::load( $databaseId );
				
				/* SORT IT OUT! */
				if ( isset( \IPS\Data\Store::i()->db_import_field_map ) )
				{
					$map = \IPS\Data\Store::i()->db_import_field_map;
					$sortId = \intval( mb_substr( $database->field_sort, 6 ) );
				
					if ( isset( $map[ $sortId ] ) )
					{
						$database->field_sort = 'field_' . $map[ $sortId ];
						$database->save();
					}
				}

				foreach( array( 'template_listing', 'template_display', 'template_categories', 'template_form', 'template_featured' ) as $name )
				{
					\IPS\frontpage\Templates::fixTemplateTags( $database->$name );
				}
				
				unset( \IPS\Data\Store::i()->db_import_db_id );
				unset( \IPS\Data\Store::i()->db_import_cat_map );
				unset( \IPS\Data\Store::i()->db_import_cat_parent_map );
				unset( \IPS\Data\Store::i()->db_import_field_map );
					
				@unlink( \IPS\Request::i()->file );
				
				/* Done */
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=frontpage&module=databases&controller=databases&do=permissions&id=' . $databaseId ) );
			}
		);
	}

	/**
	 * Download
	 *
	 * @return void
	 */
	protected function download()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'databases_edit' );

		try
		{
			$database = \IPS\frontpage\Databases::load( \IPS\Request::i()->id );
		}
		catch( \OutofRangeException $ex )
		{
			\IPS\Output::i()->error( 'frontpage_database_not_exist', '3T259/4', 403, '' );
		}

		if( empty( \IPS\Request::i()->go ) )
		{
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'databases' )->downloadDialog( $database );
			return;
		}
		else
		{
			/* We need to know the database schema to prevent non-standard columns from add-ons being added to the XML (which then causes the import to fail) */
			$schema = json_decode( \file_get_contents( \IPS\ROOT_PATH . '/applications/frontpage/data/schema.json' ), TRUE );
			
			/* Init */
			$xml = new \XMLWriter;
			$xml->openMemory();
			$xml->setIndent( TRUE );
			$xml->startDocument( '1.0', 'UTF-8' );

			/* Root tag */
			$xml->startElement('database');
			
			foreach( array( 'content_db_lang_sl', 'content_db_lang_pl', 'content_db_lang_su', 'content_db_lang_pu', 'content_db_lang_ia' ) as $lang )
			{
				try
				{
					$xml->startAttribute( $lang );
					$xml->text( \IPS\Member::loggedIn()->language()->get( $lang  . '_' . $database->id ) );
					$xml->endAttribute();
				}
				catch( \UnderflowException $ex )
				{

				}
			}

			/* Initiate the <fields> tag */
			$xml->startElement('data');
			
			$arrays = array();
			foreach( array( 'use_categories', 'template_listing', 'template_display', 'template_categories', 'all_editable', 'revisions', 'field_title', 'field_content', 'field_sort', 'field_direction', 'field_perpage', 'comment_approve', 'record_approve', 'rss',
							'comment_bump', 'forum_record', 'forum_comments', 'forum_delete', 'forum_forum', 'forum_prefix', 'forum_suffix', 'search', 'tags_enabled', 'tags_noprefixes', 'tags_predefined', 'fixed_field_perms',
							'cat_index_type', 'template_form', 'template_featured', 'featured_settings', 'options' ) as $field )
			{
				if ( \is_array( $database->$field ) )
				{
					$arrays[ $field ] = $database->$field;
				}
				else
				{
					$store = $database->$field;

					if ( $database->$field instanceof \IPS\Patterns\Bitwise )
					{
						$bwfield = $database->$field;
						$store = \intval( $bwfield->values['options'] );
					}

					$xml->startAttribute( $field );
					$xml->text( $store );
					$xml->endAttribute();
				}
			}

			if ( \count( $arrays ) )
			{
				foreach( $arrays as $field => $v )
				{
					$xml->startElement( $field );
					$xml->writeCData( json_encode( $v ) );
					$xml->endElement();
				}
			}
			
			$xml->startElement('name');
			$xml->writeCData( $database->_title );
			$xml->endElement();
			
			$xml->startElement('description');
			$xml->writeCData( $database->_description );
			$xml->endElement();


			$xml->endElement();

			/* Custom fields */
			$textFields  	= array( 'field_extra', 'field_default_value', 'field_name', 'field_description', 'field_display_json', 'field_validator_error' );
			$removeFields	= array( 'field_database_id' );
			$fieldSchema	= $schema['frontpage_database_fields'];
			foreach ( \IPS\Db::i()->select( 'f.*, w.word_custom as field_name, w2.word_custom as field_description, w3.word_custom as field_validator_error, p.*', array('frontpage_database_fields', 'f'), array( 'field_database_id=?', $database->id ) )
				            ->join( array( 'core_sys_lang_words', 'w' ), "w.word_key=CONCAT( 'content_field_', f.field_id ) and w.lang_id=" . \IPS\Lang::defaultLanguage() )
			                ->join( array( 'core_sys_lang_words', 'w2' ), "w2.word_key=CONCAT( 'content_field_', f.field_id, '_desc' ) and w2.lang_id=" . \IPS\Lang::defaultLanguage() )
				            ->join( array( 'core_sys_lang_words', 'w3' ), "w3.word_key=CONCAT( 'content_field_', f.field_id, '_validation_error' ) and w3.lang_id=" . \IPS\Lang::defaultLanguage() )
				            ->join( array( 'core_permission_index', 'p' ), "p.app='frontpage' and p.perm_type='fields' and p.perm_type_id=f.field_id" )
				as $row )
			{
				/* Initiate the <fields> tag */
				$xml->startElement('field');

				foreach( $row as $k => $v )
				{
					if ( array_key_exists( $k, $fieldSchema['columns'] ) AND ! \in_array( $k, $removeFields ) AND ! \in_array( $k, $textFields ) )
					{
						$xml->startAttribute( $k );
						$xml->text( $v );
						$xml->endAttribute();
					}
				}

				/* Write (potential) HTML fields */
				foreach( $textFields as $field )
				{
					if ( isset( $row[ $field ] ) )
					{
						$xml->startElement( $field );
						if ( preg_match( '/<|>|&/', $row[ $field ] ) )
						{
							$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', $row[ $field ] ) );
						}
						else
						{
							$xml->text( $row[ $field ] );
						}
						$xml->endElement();
					}
				}

				/* Close the <fields> tag */
				$xml->endElement();
			}

			/* Categories */
			$textFields   = array( 'category_name', 'category_description', 'category_meta_keywords', 'category_meta_description', 'category_fpage_title' );
			$removeFields = array( 'category_database_id', 'category_last_record_id', 'category_last_record_date', 'category_last_record_member', 'category_last_record_name', 'category_last_record_seo_name', 'category_records', 'category_record_comments',
								   'category_record_comments_queued', 'category_rss_cache', 'category_rss_cached', 'category_rss_exclude', 'category_forum_override', 'category_forum_record', 'category_forum_comments', 'category_forum_delete', 'category_forum_suffix', 'category_forum_prefix',
							       'category_forum_forum', 'category_full_path', 'category_last_title', 'category_last_seo_title');
			$categorySchema	= $schema['frontpage_database_categories'];
			foreach ( \IPS\Db::i()->select( 'c.*, w.word_custom as category_name, w2.word_custom as category_description, p.*', array('frontpage_database_categories', 'c'), array( 'category_database_id=?', $database->id ) )
				          ->join( array( 'core_sys_lang_words', 'w' ), "w.word_key=CONCAT( 'content_cat_name_', c.category_id ) and w.lang_id=" . \IPS\Lang::defaultLanguage() )
				          ->join( array( 'core_sys_lang_words', 'w2' ), "w2.word_key=CONCAT( 'content_cat_name_', c.category_id, '_desc' ) and w2.lang_id=" . \IPS\Lang::defaultLanguage() )
				          ->join( array( 'core_permission_index', 'p' ), "p.app='frontpage' and p.perm_type='categories' and p.perm_type_id=c.category_id" )
			          as $row )
			{
				/* Initiate the <category> tag */
				$xml->startElement('category');

				foreach( $row as $k => $v )
				{
					if ( array_key_exists( $k, $categorySchema['columns'] ) AND ! \in_array( $k, $removeFields ) AND ! \in_array( $k, $textFields ) )
					{
						$xml->startAttribute( $k );
						$xml->text( $v );
						$xml->endAttribute();
					}
				}

				/* Write (potential) HTML fields */
				foreach( $textFields as $field )
				{
					if ( isset( $row[ $field ] ) )
					{
						$xml->startElement( $field );
						if ( preg_match( '/<|>|&/', $row[ $field ] ) )
						{
							$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', $row[ $field ] ) );
						}
						else
						{
							$xml->text( $row[ $field ] );
						}
						$xml->endElement();
					}
				}

				/* Close the <category> tag */
				$xml->endElement();
			}

			/* Templates @todo see if blocks are used in template and export too */
			$templates = \IPS\frontpage\Theme::i()->getRawTemplates( 'frontpage', 'database', NULL, \IPS\frontpage\Theme::RETURN_ALL );
			$toSave    = array();

			foreach( array('template_listing', 'template_display', 'template_categories', 'template_featured') as $area )
			{
				if ( isset( $templates['frontpage']['database'][ $database->$area ] ) and \is_array( $templates['frontpage']['database'][ $database->$area ] ) )
				{
					/* Only fetch edited/added templates, no point in fetching default theme templates */
					foreach( $templates['frontpage']['database'][ $database->$area ] as $key => $item )
					{
						if ( $item['template_user_created'] or $item['template_user_edited'] )
						{
							$toSave[ $database->$area ][ $key ] = $item;
						}
					}
				}
			}

			/* Form template */
			if ( isset( $templates['frontpage']['database']['form'][ $database->template_form ] ) )
			{
				$item = $templates['frontpage']['database']['form'][ $database->template_form ];

				if ( $item['template_user_created'] or $item['template_user_edited'] )
				{
					$toSave['form'][ $database->template_form ] = $item;
				}
			}

			if ( \count( $toSave ) )
			{
				foreach( $toSave as $group => $items )
				{
					foreach( $items as $key => $item )
					{
						/* Initiate the <template> tag */
						$xml->startElement('template');

						foreach( array( 'template_title', 'template_group', 'template_location', 'template_original_group' ) as $field )
						{
							$xml->startAttribute( $field );
							$xml->text( $item[ $field ] );
							$xml->endAttribute();
						}

						/* Write (potential) HTML fields */
						foreach( array( 'template_params', 'template_content' ) as $field )
						{
							if ( isset( $item[ $field ] ) )
							{
								$xml->startElement( $field );
								if ( preg_match( '/<|>|&/', $item[ $field ] ) )
								{
									$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', $item[ $field ] ) );
								}
								else
								{
									$xml->text( $item[ $field ] );
								}
								$xml->endElement();
							}
						}

						/* Close the <template> tag */
						$xml->endElement();
					}
				}
			}

			/* Finish */
			$xml->endDocument();

			$name = addslashes( str_replace( array( ' ', '.', ',' ), '_', \IPS\Member::loggedIn()->language()->get( 'content_db_' . $database->_id ) ) . '.xml' );

			\IPS\Output::i()->sendOutput( $xml->outputMemory(), 200, 'application/xml', array( 'Content-Disposition' => \IPS\Output::getContentDisposition( 'attachment', $name ) ) );
		}
	}

	/**
	 * Delete
	 *
	 * @return	void
	 */
	protected function delete()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'databases_delete' );
		
		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();

		/* Load the database */
		try
		{
			$database = \IPS\frontpage\Databases::load( \IPS\Request::i()->id );
		}
		catch( \OutofRangeException $ex )
		{
			\IPS\Output::i()->error( 'frontpage_database_not_exist', '3T259/2', 403, '' );
		}

		/* Delete the database and clear the 'create' menu cache */
		$database->delete();
		\IPS\Member::clearCreateMenu();

		/* Log the deletion */
		\IPS\Session::i()->log( 'acplogs__frontpage_deleted_database', array( 'content_db_' . $database->id => TRUE ) );

		/* Send the user back to the list */
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=frontpage&module=databases&controller=databases' ), 'deleted' );
	}

	/**
	 * Permissions
	 *
	 * @return	void
	 */
	protected function permissions()
	{
		parent::permissions();

		$database = \IPS\frontpage\Databases::load( \IPS\Request::i()->id );

		try
		{
			if( $database->fpage_id AND \IPS\frontpage\Pages\Page::load( $database->fpage_id )->permissions()['perm_view'] != '*' )
			{
				/* We want the message to show at the top, so get our output as we will start over */
				$output = \IPS\Output::i()->output;

				\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global', 'core' )->message( \IPS\Member::loggedIn()->language()->addToStack('database_fpage_permissions_restrict', FALSE, array( 'sprintf' => array( $database->fpage_id ) ) ), 'warning' );
				\IPS\Output::i()->output .= $output;
			}
		}
		catch( \OutOfRangeException $e ){}
	}

	/**
	 * Promote
	 *
	 * @return void
	 */
	protected function promote()
	{
		$form = new \IPS\Helpers\Form( 'form', 'save' );
		$form->add( new \IPS\Helpers\Form\YesNo( 'frontpage_promote_enabled', \IPS\Settings::i()->frontpage_promote_enabled, FALSE, array( 'togglesOn' => array( 'frontpage_promote_cat', 'frontpage_promote_groups' ) ) ) );

		$form->add( new \IPS\Helpers\Form\Node( 'frontpage_promote_cat', \IPS\Settings::i()->frontpage_promote_cat ? \IPS\Settings::i()->frontpage_promote_cat : 0, FALSE, array(
			'class'           => '\IPS\frontpage\Selector\Databases',
			'subnodes'		  => true,
		), NULL, NULL, NULL, 'frontpage_promote_cat' ) );

		$form->add( new \IPS\Helpers\Form\Select(
            'frontpage_promote_groups',
            \IPS\Settings::i()->frontpage_promote_groups != '' ? ( \IPS\Settings::i()->frontpage_promote_groups === '*' ? '*' : explode( ",", \IPS\Settings::i()->frontpage_promote_groups ) ) : '*',
            FALSE,
            array( 'options' => \IPS\Member\Group::groups(), 'multiple' => TRUE, 'parse' => 'normal', 'unlimited' => '*', 'unlimitedLang' => 'all' ),
            NULL,
            NULL,
            NULL,
            'frontpage_promote_groups'
        ) );

		if ( $values = $form->values() )
		{
			$form->saveAsSettings();

			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=frontpage&module=databases&controller=databases" ), 'saved' );
		}

		/* Display */
		\IPS\Output::i()->output .= \IPS\Theme::i()->getTemplate( 'global', 'core', 'admin' )->block( \IPS\Member::loggedIn()->language()->addToStack('frontpage_fpages_settings'), $form, FALSE );
		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack('frontpage_fpages_settings');
	}

	/**
	 * Resynchronise topic content dialog
	 *
	 * @return void
	 */
	public function rebuildTopicContent()
	{
		\IPS\Session::i()->csrfCheck();
		
		if ( isset( \IPS\Request::i()->process ) )
		{
			\IPS\Task::queue( 'frontpage', 'ResyncTopicContent', array( 'databaseId' => \IPS\Request::i()->id ), 3, array( 'databaseId' ) );
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=frontpage&module=databases&controller=databases&do=form&id=' . \IPS\Request::i()->id ), 'database_rebuild_added' );
		}
		else
		{
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'databases', 'frontpage', 'admin' )->rebuildTopics( \IPS\Request::i()->id );
		}
	}
	
	/**
	 * Resynchronise article comment counts
	 *
	 * @return void
	 */
	public function rebuildCommentCounts()
	{
		if ( isset( \IPS\Request::i()->process ) )
		{
			\IPS\Session::i()->csrfCheck();
			\IPS\Task::queue( 'core', 'RebuildItemCounts', array( 'class' => 'IPS\frontpage\Records' . \IPS\Request::i()->id ), 3, array( 'class' ) );
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=frontpage&module=databases&controller=databases&do=form&id=' . \IPS\Request::i()->id ), 'database_rebuild_added' );
		}
		else
		{
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'databases', 'frontpage', 'admin' )->rebuildCommentCounts( \IPS\Request::i()->id );
		}
	}
	
	/**
	 * Add/Edit
	 *
	 * @csrfChecked	Uses $this->_getDatabaseForm() which uses Form Helper 7 Oct 2019
	 * @return	void
	 */
	public function form()
	{
		$current  = NULL;
		$category = NULL;
		if ( \IPS\Request::i()->id )
		{
			$current = \IPS\frontpage\Databases::load( \IPS\Request::i()->id );

			if ( ! $current->use_categories )
			{
				$class    = '\IPS\frontpage\Categories' . $current->id;
				$category = $class::load( $current->_default_category );
			}
		}
	
		/* Get the database form - abstracted so plugins can adjust easier */
		$form = $this->_getDatabaseForm( $current, $category );
		
		if ( $values = $form->values() )
		{
			$new = FALSE;

			if ( empty( $current ) )
			{
				$new = TRUE;
				$current = new \IPS\frontpage\Databases;
				$current->key = mt_rand(); # This is modified below to use a proper key
				$current->featured_settings	= NULL;
				$current->fixed_field_perms	= NULL;
				$current->save();
				
				/* Create a new database table */
				try
				{
					\IPS\frontpage\Databases::createDatabase( $current );
					$current->preLoadWords();

					\IPS\Member::clearCreateMenu();
				}
				catch ( \Exception $ex )
				{
					$current->delete();
					
					\IPS\Output::i()->error( \IPS\Member::loggedIn()->language()->addToStack('content_acp_err_db_creation_fail', FALSE, array( 'sprintf' => $ex->getMessage() ) ), '4T259/1', 403, '' );
				}
			}

			if ( ! $values['database_key'] )
			{
				if ( \is_array( $values['database_name'] ) )
				{
					$keyToUse = mt_rand();
					foreach( $values['database_name'] as $langId => $word )
					{
						if ( ! empty( $word ) )
						{
							$keyToUse = $word;
							break;
						}
					}
					
					$current->key = \IPS\Http\Url\Friendly::seoTitle( $keyToUse );
				}
				else
				{
					$current->key = \IPS\Http\Url\Friendly::seoTitle( $values['database_name'] );
				}

				/* Now test it */
				try
				{
					$database = \IPS\frontpage\Databases::load( $current->key, 'database_key');

					/* It's taken... */
					if ( $current->id != $database->id )
					{
						$current->key .= '_' . mt_rand();
					}
				}
				catch( \OutOfRangeException $ex )
				{
					/* Doesn't exist? Good! */
				}
			}
			else
			{
				$current->key = $values['database_key'];
			}

			/* Bit options */
			foreach ( array( 'comments', 'comments_mod', 'reviews', 'reviews_mod', 'indefinite_own_edit' ) as $k )
			{
				if ( isset( $values[ "frontpage_bitoptions_{$k}" ] ) )
				{
					$current->options[ $k ] = $values["frontpage_bitoptions_{$k}"];
					unset( $values["frontpage_bitoptions_{$k}"] );
				}
			}

			\IPS\Lang::saveCustom( 'frontpage', "content_db_" . $current->id, $values['database_name'] );
			\IPS\Lang::saveCustom( 'frontpage', "content_db_" . $current->id . '_desc', $values['database_description'] );
			\IPS\Lang::saveCustom( 'frontpage', "content_db_lang_sl_" . $current->id, $values['database_lang_sl'] );
			\IPS\Lang::saveCustom( 'frontpage', "content_db_lang_pl_" . $current->id, $values['database_lang_pl'] );
			\IPS\Lang::saveCustom( 'frontpage', "content_db_lang_su_" . $current->id, $values['database_lang_su'] );
			\IPS\Lang::saveCustom( 'frontpage', "content_db_lang_pu_" . $current->id, $values['database_lang_pu'] );
			\IPS\Lang::saveCustom( 'frontpage', "content_db_lang_ia_" . $current->id, $values['database_lang_ia'] );
			\IPS\Lang::saveCustom( 'frontpage', "content_db_lang_sl_" . $current->id . '_pl', $values['database_lang_pu'] );
			\IPS\Lang::saveCustom( 'frontpage', "digest_area_frontpage_records" . $current->id, $values['database_lang_pu'] );
			\IPS\Lang::saveCustom( 'frontpage', "digest_area_frontpage_categories" . $current->id, $values['database_lang_pu'] );

			$menu = array();
			foreach( \IPS\Lang::getEnabledLanguages() as $id => $lang )
			{
				$menu[ $lang->_id ] = \sprintf( $lang->get('frontpage_create_menu_records_x'), $values['database_lang_su'][ $lang->_id ], $values['database_name'][ $lang->_id ] );
			}

			/* Notification, search/followed/new content langs */
			\IPS\Lang::saveCustom( 'frontpage', "frontpage_create_menu_records_" . $current->id, $menu );
			\IPS\Lang::saveCustom( 'frontpage', "frontpage_records" . $current->id . '_pl', $values['database_lang_pu'] );
			\IPS\Lang::saveCustom( 'frontpage', "module__frontpage_records" . $current->id, $values['database_name'] );

			$current->use_categories      = $values['database_use_categories'];
			$current->template_categories = $values['database_template_categories'];
			$current->template_listing    = $values['database_template_listing'];
			$current->template_display    = $values['database_template_display'];
			$current->template_form       = $values['database_template_form'];
			$current->template_featured   = $values['database_template_featured'];
			$current->cat_index_type      = $values['database_cat_index_type'];
			$current->use_as_fpage_title   = $values['database_use_as_fpage_title'];

			$current->all_editable   = (int) $values['database_all_editable'];
			$current->revisions      = (int) $values['database_revisions'];
			$current->search         = (int) $values['database_search'];
			$current->comment_bump   = $values['database_comment_bump'];
			if ( $values['database_rss_enable'] )
			{
				$current->rss		= $values['database_rss'];
			}
			else
			{
				$current->rss		= 0;
			}
			$current->record_approve = $values['database_record_approve'];

			if ( \IPS\Settings::i()->tags_enabled )
			{
				$current->tags_enabled    = (int) $values['database_tags_enabled'];
				$current->tags_noprefixes = !(bool) $values['database_tags_noprefixes'];
				$current->tags_predefined = ( \is_array( $values['database_tags_predefined'] ) ) ? implode( ',', array_filter( array_map( 'trim', $values['database_tags_predefined'] ) ) ) : NULL;

			}

			$categories = 0;
			if ( isset( $values['database_featured_categories'] ) )
			{
				if ( $values['database_featured_categories'] !== 0 )
				{
					$categories = array_keys( $values['database_featured_categories'] );
				}
			}

			/* Featured settings */
			$current->featured_settings = array(
				'featured'   => $values['database_featured_featured'],
				'perpage'    => $values['database_featured_perpage'],
				'pagination' => $values['database_featured_pagination'],
				'sort'       => $values['database_featured_sort'],
				'direction'  => $values['database_featured_direction'],
			    'categories' => $categories
			);

			$current->field_sort      = $values['database_field_sort'];
			$current->field_direction = $values['database_field_direction'];
			$current->field_perpage   = $values['database_field_perpage'];
			
			if ( \IPS\Application::appIsEnabled( 'forums' ) )
			{
				/* Are we changing where comments go? */
				if ( !$new AND ( (int) $current->forum_record != (int) $values['database_forum_record'] OR (int) $current->forum_comments != (int) $values['database_forum_comments'] ) )
				{
					\IPS\Task::queue( 'cms', 'MoveComments', array(
						'databaseId'	=> $current->id,
						'to'				=> ( $values['database_forum_comments'] AND $values['database_forum_record'] ) ? 'forums' : 'fpages',
						'deleteTopics'	=> (bool) ( !$values['database_forum_record'] )
					), 1, array( 'databaseId', 'to' ) );
				}
				
				$current->forum_record   = (int) $values['database_forum_record']; 
				$current->forum_comments = (int) $values['database_forum_comments'];
				$current->forum_forum    = ( ! $values['database_forum_forum']  ) ? 0 : $values['database_forum_forum']->id;
				$current->forum_prefix   = $values['database_forum_prefix'];
				$current->forum_suffix   = $values['database_forum_suffix'];
				$current->forum_delete   = (int) $values['database_forum_delete'];
			}
			else
			{
				$current->forum_record		= 0;
				$current->forum_comments	= 0;
				$current->forum_delete		= 0;
			}
			
			/* SEO */
			$current->canonical_flag = (int) $values['database_canonical_flag'];

			$fieldSettingJson = array();

			foreach( $values as $k => $v )
			{
				if ( mb_stristr( $k, 'fixed_field_setting__' ) )
				{
					$bits = explode( '__', $k );

					$fieldSettingJson[ $bits[1] ][ $bits[2] ] = $v;
				}
			}

			$current->fixed_field_settings = $fieldSettingJson;

			$fixedFields = $current->fixed_field_perms;
			$fixedFields['record_image']['visible'] = $values['database_record_image'];

			foreach( array( 'perm_view', 'perm_2', 'perm_3' ) as $p )
			{
				if ( ! isset( $fixedFields['record_image'][ $p ] ) )
				{
					$fixedFields['record_image'][ $p ] = '*';
				}
			}

			$current->fixed_field_perms = $fixedFields;
			$current->use_categories      = $values['database_use_categories'];
			
			$current->save();

			/* Make sure we have a default category */
			$current->default_category = $current->_default_category;

			if ( ! $current->use_categories )
			{
				$class                  = '\IPS\frontpage\Categories' . $current->id;
				$category               = $class::load( $current->get__default_category() );
				$category->allow_rating = $values['category_allow_rating'];
				$category->can_view_others = $values['category_can_view_others'];
				$category->save();
				$category->cloneDatabasePermissions();
			}
			
			if ( $new )
			{
				if ( $values['database_create_fpage'] === 'new' )
				{
					$fpageValues = array();

					foreach( $values as $k => $v )
					{
						if( mb_strpos( $k, 'fpage_' ) === 0 )
						{
							$fpageValues[ $k ]	= $v;
						}
					}

					if ( $values['fpage_type'] === 'html' )
					{
						$fpageValues['fpage_content'] = '{database="' . $current->id . '"}';
						$fpageValues['fpage_template'] = '';
					}
					$fpageValues['fpage_folder_id'] = $fpageValues['fpage_folder_id'] ?: 0;

					$newFpage = \IPS\frontpage\Fpages\Fpage::createFromForm( $fpageValues, 'html' );

					if ( $values['fpage_type'] !== 'html' )
					{
						\IPS\Db::i()->insert( 'frontpage_fpage_widget_areas', array(
							'area_fpage_id'     => $newFpage->id,
							'area_widgets'     => json_encode( array( array( 'app' => 'frontpage', 'key' => 'Database', 'unique' => mt_rand(), 'configuration' => array( 'database' => $current->id ) ) ) ),
							'area_area'        => 'col1',
							'area_orientation' => 'horizontal'
						) );
					}
					
					$current->fpage_id = $newFpage->id;
					$current->save();
				}
			}
			else
			{
				/* Use categories setting may have changed */
				$current->preLoadWords();
				\IPS\Member::clearCreateMenu();
			}
			
			if ( $new )
			{
                \IPS\Session::i()->log( 'acplogs__frontpage_added_database', array( 'content_db_' . $current->id => TRUE ) );

                \IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=frontpage&module=databases&controller=databases&do=permissions&id=' . $current->id ) );
			}

            \IPS\Session::i()->log( 'acplogs__frontpage_edited_database', array( 'content_db_' . $current->id => TRUE ) );

            \IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=frontpage&module=databases&controller=databases' ), 'saved' );
		}
		
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'admin_databases.js', 'frontpage', 'admin' ) );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global', 'core' )->block( $current ? "content_db_" . $current->id : 'add', $form, FALSE );
		\IPS\Output::i()->title  = ( $current ) ? \IPS\Member::loggedIn()->language()->addToStack('frontpage_editing_database', NULL, array( 'sprintf' => array( $current->_title ) ) ) : \IPS\Member::loggedIn()->language()->addToStack('frontpage_adding_database');
	}

	/**
	 * Generate the database form
	 *
	 * @param	\IPS\frontpage\Databases	$current	The current database
	 * @param	\IPS\frontpage\Categories	$category	The default catgory
	 * @return	\IPS\Form
	 */
	protected function _getDatabaseForm( $current, $category )
	{
		$form = new \IPS\Helpers\Form( 'form', 'save', NULL, array( 'data-controller' => 'frontpage.admin.databases.form' ) );
		
		$form->addTab( 'content_database_form_details' );
		
		$form->add( new \IPS\Helpers\Form\Translatable( 'database_name', NULL, TRUE, array( 'app' => 'frontpage', 'key' => ( ! empty( $current ) ? "content_db_" . $current->id : NULL ) ) ) );
		
		$form->add( new \IPS\Helpers\Form\Translatable( 'database_description', NULL, FALSE, array(
				'app'		  => 'frontpage',
				'key'		  => ( $current ? "content_db_" .  $current->id . "_desc" : NULL ),
				'textArea'	  => true
		) ) );
		
		$disabled = FALSE;
		if ( $current and $current->use_categories and $current->numberOfCategories() > 1 )
		{
			$disabled = TRUE;
		}
		
		$form->add( new \IPS\Helpers\Form\Radio( 'database_use_categories' , $current ? $current->use_categories : 1, FALSE, array(
            'options' => array(
	            1	=> 'database_use_categories_yes',
	            0	=> 'database_use_categories_no'
            ),
            'toggles' => array(
	            1  => array( 'database_template_categories' ),
	            0  => array( 'category_allow_rating', 'category_can_view_others' )
            ),
            'disabled' => $disabled
        ) ) );
        
        if ( $disabled === TRUE )
        {
	        $form->hiddenValues['database_use_categories_impossible'] = TRUE;
	        \IPS\Member::loggedIn()->language()->words['database_use_categories_warning'] =  \IPS\Member::loggedIn()->language()->addToStack( 'database_use_categories_impossible', NULL, array( 'sprintf' => array( $current->numberOfCategories() ) ) );
        }
        
        $form->add( new \IPS\Helpers\Form\Radio( 'database_use_as_fpage_title' , $current ? $current->use_as_fpage_title : 1, FALSE, array(
            'options' => array(
	            1	=> 'database_use_as_fpage_title_yes',
	            0	=> 'database_use_as_fpage_title_no'
            )
        ) ) );
		
		\IPS\Member::loggedIn()->language()->words['category_can_view_others_desc'] = \IPS\Member::loggedIn()->language()->addToStack('category_can_view_others_database_desc');
		$form->add( new \IPS\Helpers\Form\YesNo( 'category_allow_rating', ( $category !== NULL and $category->allow_rating ) ? TRUE : FALSE, FALSE, array(), NULL, NULL, NULL, 'category_allow_rating' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'category_can_view_others', ( $category !== NULL ) ? $category->can_view_others : TRUE, FALSE, array(), NULL, NULL, NULL, 'category_can_view_others' ) );

		$templatesCat      = NULL;
		$templatesList     = NULL;
		$templatesDisplay  = NULL;
		$templatesForm     = NULL;
		$templatesFeatured = array();

		foreach( \IPS\frontpage\Templates::getTemplates( \IPS\frontpage\Templates::RETURN_DATABASE + \IPS\frontpage\Templates::RETURN_DATABASE_AND_IN_DEV ) as $template )
		{
			$title = \IPS\frontpage\Templates::readableGroupName( $template->group );

			switch( $template->original_group )
			{
				case 'category_index':
					$templatesCat[ $template->group ] = $title;
				break;
				case 'listing':
					$templatesList[ $template->group ] = $title;
				break;
				case 'display':
					$templatesDisplay[ $template->group ] = $title;
				break;
				case 'form':
					$templatesForm[ $template->group ] = $title;
				break;
				case 'category_articles':
				case 'category_2_column_first_featured':
				case 'category_2_column_image_feature':
				case 'category_3_column_image_feature':
				case 'category_3_column_first_featured':
					$templatesFeatured[ $template->group ] = $title;
				break;
			}
		}

		$form->add( new \IPS\Helpers\Form\Radio( 'database_cat_index_type' , $current ? $current->cat_index_type : 0, FALSE, array(
				'options' => array(
						0	=> 'database_display_index_as_categories',
						1	=> 'database_display_index_as_articles'
				),
				'toggles' => array(
						'0' => array( 'database_template_categories' ),
						'1' => array( $form->id . '_header_frontpage_articles_homepage_form', 'database_template_featured', 'database_featured_featured', 'database_featured_categories', 'database_featured_perpage', 'database_featured_pagination', 'database_featured_sort', 'database_featured_direction' )
				)
		), NULL, NULL, NULL, 'database_cat_index_type' ) );

		$fields = array(
			'primary_id_field'      => 'database_field__id',
			'member_id'		        => 'database_field__member',
			'record_publish_date'   => 'database_field__saved',
			'record_updated'        => ( $current and $current->_comment_bump === \IPS\frontpage\Databases::BUMP_ON_EDIT ) ? 'database_field__edited' : 'database_field__updated',
			'record_last_comment'   => "database_field__last_comment",
			'record_rating' 	    => 'database_field__rating'
		);

		if ( $current )
		{
			$FieldsClass = '\IPS\frontpage\Fields' . $current->id;

			foreach( $FieldsClass::data() as $id => $field )
			{
				if ( \in_array( $field->type, array( 'checkbox', 'multiselect', 'attachments' ) ) )
				{
					continue;
				}

				$fields[ 'field_' . $field->id ] = $field->_title;
			}
		}

		$form->addHeader("frontpage_articles_homepage_form");

		$form->add( new \IPS\Helpers\Form\Select( 'database_template_featured',   ! empty( $current ) ? $current->template_featured   : NULL, FALSE, array( 'options' => $templatesFeatured ), NULL, NULL, NULL, 'database_template_featured' ) );

		/* Featured settings */
		$settings = ( ! empty( $current ) ) ? $current->featured_settings : array();
		$form->add( new \IPS\Helpers\Form\YesNo(  'database_featured_featured'  , ( ! empty( $current ) and isset( $settings['featured'] ) )   ? $settings['featured']   : 0, FALSE, array(), NULL, NULL, NULL, 'database_featured_featured' ) );

		if ( $current )
		{
			$form->add( new \IPS\Helpers\Form\Node( 'database_featured_categories', ( ! empty( $current ) and isset( $settings['categories'] ) ) ? $settings['categories'] : 0, FALSE, array(
	            'class'           => '\IPS\frontpage\Categories' . $current->id,
	            'zeroVal'         => 'frontpage_all_categories',
	            'permissionCheck' => 'view',
	            'multiple'        => true
            ), NULL, NULL, NULL, 'database_featured_categories' ) );
		}

		$form->add( new \IPS\Helpers\Form\Number( 'database_featured_perpage'   , ( ! empty( $current ) and isset( $settings['perpage'] ) )    ? $settings['perpage']    : 10, FALSE, array(), NULL, NULL, NULL, 'database_featured_perpage' ) );
		$form->add( new \IPS\Helpers\Form\YesNo(  'database_featured_pagination', ( ! empty( $current ) and isset( $settings['pagination'] ) ) ? $settings['pagination'] : NULL, FALSE, array(), NULL, NULL, NULL, 'database_featured_pagination' ) );
		$form->add( new \IPS\Helpers\Form\Select( 'database_featured_sort'      , ( ! empty( $current ) and isset( $settings['sort'] ) )       ? $settings['sort']       : 'record_publish_date', FALSE, array( 'options' => $fields ), NULL, NULL, NULL, 'database_featured_sort' ) );
		$form->add( new \IPS\Helpers\Form\Select( 'database_featured_direction' , ( ! empty( $current ) and isset( $settings['direction'] ) )  ? $settings['direction']  : 'desc', FALSE, array(
            'options' => array(
	            'asc'  => 'database_sort_asc',
	            'desc' => 'database_sort_desc'
            )
        ), NULL, NULL, NULL, 'database_featured_direction' ) );

		$form->addHeader( 'frontpage_database_form_display' );

		$form->add( new \IPS\Helpers\Form\Select( 'database_template_categories', ! empty( $current ) ? $current->template_categories : NULL, FALSE, array( 'options' => $templatesCat ), NULL, NULL, \IPS\Theme::i()->getTemplate( 'databases', 'frontpage' )->templateGoButton('database_template_categories'), 'database_template_categories' ) );
		$form->add( new \IPS\Helpers\Form\Select( 'database_template_listing'   , ! empty( $current ) ? $current->template_listing    : NULL, FALSE, array( 'options' => $templatesList ), NULL, NULL, \IPS\Theme::i()->getTemplate( 'databases', 'frontpage' )->templateGoButton('database_template_listing'), 'database_template_listing' ) );
		$form->add( new \IPS\Helpers\Form\Select( 'database_template_display'   , ! empty( $current ) ? $current->template_display    : NULL, FALSE, array( 'options' => $templatesDisplay ), NULL, NULL, \IPS\Theme::i()->getTemplate( 'databases', 'frontpage' )->templateGoButton('database_template_display'), 'database_template_display' ) );
		$form->add( new \IPS\Helpers\Form\Select( 'database_template_form'      , ! empty( $current ) ? $current->template_form       : NULL, FALSE, array( 'options' => $templatesForm ), NULL, NULL, \IPS\Theme::i()->getTemplate( 'databases', 'frontpage' )->templateGoButton('database_template_form'), 'database_template_form' ) );

		$form->add( new \IPS\Helpers\Form\Text( 'database_key', ! empty( $current ) ? $current->key : FALSE, FALSE, array(), function( $val )
		{
			try
			{
				if ( ! $val )
				{
					return true;
				}

				try
				{
					$database = \IPS\frontpage\Databases::load( $val, 'database_key');
				}
				catch( \OutOfRangeException $ex )
				{
					/* Doesn't exist? Good! */
					return true;
				}

				/* It's taken... */
				if ( \IPS\Request::i()->id == $database->id )
				{
					/* But it's this one so that's ok */
					return true;
				}

				/* and if we're here, it's not... */
				throw new \InvalidArgumentException('frontpage_database_key_not_unique');
			}
			catch ( \OutOfRangeException $e )
			{
				/* Slug is OK as load failed */
				return true;
			}

			return true;
		} ) );

		$form->addTab( 'content_database_form_lang' );
		
		$sl_Default = null;
		$pl_Default = null;
		$su_Default = null;
		$pu_Default = null;
		$ia_Default = null;

		if ( ! $current )
		{
			foreach ( \IPS\Lang::languages() as $lang )
			{
				$sl_Default[ $lang->id ] = $lang->get('content_database_noun_sl');
				$pl_Default[ $lang->id ] = $lang->get('content_database_noun_pl');
				$su_Default[ $lang->id ] = $lang->get('content_database_noun_su');
				$pu_Default[ $lang->id ] = $lang->get('content_database_noun_pu');
				$ia_Default[ $lang->id ] = $lang->get('content_database_noun_ia');
			}
		}

		$form->add( new \IPS\Helpers\Form\Translatable( 'database_lang_sl', $sl_Default, FALSE, array( 'app' => 'frontpage', 'key' => ( ! empty( $current ) ? "content_db_lang_sl_" . $current->id : NULL ) ) ) );
		$form->add( new \IPS\Helpers\Form\Translatable( 'database_lang_pl', $pl_Default, FALSE, array( 'app' => 'frontpage', 'key' => ( ! empty( $current ) ? "content_db_lang_pl_" . $current->id : NULL ) ) ) );
		$form->add( new \IPS\Helpers\Form\Translatable( 'database_lang_su', $su_Default, FALSE, array( 'app' => 'frontpage', 'key' => ( ! empty( $current ) ? "content_db_lang_su_" . $current->id : NULL ) ) ) );
		$form->add( new \IPS\Helpers\Form\Translatable( 'database_lang_pu', $pu_Default, FALSE, array( 'app' => 'frontpage', 'key' => ( ! empty( $current ) ? "content_db_lang_pu_" . $current->id : NULL ) ) ) );
		$form->add( new \IPS\Helpers\Form\Translatable( 'database_lang_ia', $ia_Default, FALSE, array( 'app' => 'frontpage', 'key' => ( ! empty( $current ) ? "content_db_lang_ia_" . $current->id : NULL ) ) ) );

		$form->addTab( 'content_database_form_options' );
		
		$form->add( new \IPS\Helpers\Form\YesNo( 'database_all_editable' , $current ? $current->all_editable : FALSE, FALSE, array( 'togglesOff' => array( 'frontpage_bitoptions_indefinite_own_edit' ) ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'frontpage_bitoptions_indefinite_own_edit'   , $current ? $current->options['indefinite_own_edit'] : FALSE, FALSE, array(), NULL, NULL, NULL, 'frontpage_bitoptions_indefinite_own_edit' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'database_revisions'    , $current ? $current->revisions : TRUE, FALSE ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'database_search'       , $current ? $current->search : TRUE, FALSE ) );
		$form->add( new \IPS\Helpers\Form\Radio( 'database_comment_bump' , $current ? $current->comment_bump : TRUE, FALSE, array(
			'options' => array(
				2	=> 'database_comment_bump_edit_comment',
				0	=> 'database_comment_bump_edit',
				1	=> 'database_comment_bump_comment'
			)
		) ) );

		$form->add( new \IPS\Helpers\Form\YesNo( 'database_record_approve', $current ? $current->record_approve : FALSE, FALSE, array(), NULL, NULL, NULL, 'database_record_approve' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'database_rss_enable'   , $current ? $current->rss : FALSE, FALSE, array( 'togglesOn' => array('database_rss') ), NULL, NULL, NULL, 'database_rss_enable' ) );
		$form->add( new \IPS\Helpers\Form\Number( 'database_rss'  		 , $current ? $current->rss : 0, FALSE, array(), NULL, NULL, NULL, 'database_rss' ) );

		$form->addHeader( 'frontpage_comments_and_reviews' );
		$form->add( new \IPS\Helpers\Form\YesNo( 'frontpage_bitoptions_comments', $current ? $current->options['comments'] : TRUE, FALSE, array( 'togglesOn' => array( 'frontpage_bitoptions_comment_mod' ) ), NULL, NULL, NULL, 'frontpage_bitoptions_comments' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'frontpage_bitoptions_comments_mod', $current ? $current->options['comments_mod'] : FALSE, FALSE, array(), NULL, NULL, NULL, 'frontpage_bitoptions_comment_mod' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'frontpage_bitoptions_reviews', $current ? $current->options['reviews'] : TRUE, FALSE, array( 'togglesOn' => array( 'frontpage_bitoptions_reviews_mod' ) ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'frontpage_bitoptions_reviews_mod', $current ? $current->options['reviews_mod'] : FALSE, FALSE, array(), NULL, NULL, NULL, 'frontpage_bitoptions_reviews_mod' ) );

		if ( \IPS\Settings::i()->tags_enabled )
		{
			$form->addHeader( 'tags' );

			$form->add( new \IPS\Helpers\Form\YesNo( 'database_tags_enabled'   , $current ? $current->tags_enabled : FALSE, FALSE, array( 'togglesOn' => array( 'database_tags_noprefixes', 'database_tags_predefined' ) ), NULL, NULL, NULL, 'database_tags_enabled' ) );
			$form->add( new \IPS\Helpers\Form\YesNo( 'database_tags_noprefixes', $current ? ! $current->tags_noprefixes : FALSE, FALSE, array(), NULL, NULL, NULL, 'database_tags_noprefixes' ) );
			$form->add( new \IPS\Helpers\Form\Text( 'database_tags_predefined', $current ? $current->tags_predefined: '', FALSE, array( 'autocomplete' => array( 'unique' => 'true' ), 'nullLang' => 'database_tags_predefined_unlimited' ), NULL, NULL, NULL, 'database_tags_predefined' ) );
		}
		
		$form->addHeader( 'content_database_form_options_fields' );
		
		$form->add( new \IPS\Helpers\Form\Select( 'database_field_sort'     , ! empty( $current ) ? $current->field_sort      : NULL, FALSE, array( 'options' => $fields ) ) );
		$form->add( new \IPS\Helpers\Form\Select( 'database_field_direction', ! empty( $current ) ? $current->field_direction : NULL, FALSE, array(
			'options' => array(
					'asc'  => 'database_sort_asc',
					'desc' => 'database_sort_desc'
			)
		) ) );
		
		$form->add( new \IPS\Helpers\Form\Number( 'database_field_perpage', $current ? $current->field_perpage : 25, FALSE, array( 'min' => 1 ), NULL, NULL, NULL, 'database_field_perpage' ) );

		$form->addHeader( 'content_database_form_options_field_record_image_settings' );

		$widthHeight = $thumbWidthHeight = NULL;
		$recordImagesOn = false;
		if ( $current )
		{
			$fixedFields    = $current->fixed_field_perms;
			$recordImagesOn = ( isset( $fixedFields['record_image']['visible'] ) and $fixedFields['record_image']['visible'] !== FALSE );

			$ffsettings = $current->fixed_field_settings;

			if ( isset( $ffsettings['record_image']['image_dims'] ) and \is_array( $ffsettings['record_image']['image_dims'] ) )
			{
				$widthHeight = $ffsettings['record_image']['image_dims'];
			}

			if ( isset( $ffsettings['record_image']['thumb_dims'] ) and \is_array( $ffsettings['record_image']['thumb_dims'] ) )
			{
				$thumbWidthHeight = $ffsettings['record_image']['thumb_dims'];
			}
		}
		else
		{
			$recordImagesOn = TRUE;
		}

		$form->add( new \IPS\Helpers\Form\YesNo( 'database_record_image' , $recordImagesOn, FALSE, array( 'togglesOn' => array( 'fixed_field_setting__record_image__image_dims', 'fixed_field_setting__record_image__thumb_dims' ) ), NULL, NULL, NULL, 'database_record_image' ) );
		$form->add( new \IPS\Helpers\Form\WidthHeight( 'fixed_field_setting__record_image__image_dims', ( $current AND $widthHeight !== NULL ) ? $widthHeight : array( 0, 0 ), FALSE, array( 'resizableDiv' => FALSE, 'unlimited' => array( 0, 0 ) ), NULL, NULL, NULL, 'fixed_field_setting__record_image__image_dims' ) );
		$form->add( new \IPS\Helpers\Form\WidthHeight( 'fixed_field_setting__record_image__thumb_dims', ( $current AND $thumbWidthHeight !== NULL ) ? $thumbWidthHeight : array( 200, 200 ), FALSE, array( 'resizableDiv' => FALSE, 'unlimited' => array( 0, 0 ) ), NULL, NULL, NULL, 'fixed_field_setting__record_image__thumb_dims' ) );

		if ( \IPS\Application::appIsEnabled( 'forums' ) )
		{
			$form->addTab( 'content_database_form_options_forums' );

			$databaseFpage = NULL;
			try
			{
				if ( $current )
				{
					$databaseFpage = \IPS\frontpage\Fpages\Fpage::loadByDatabaseId( $current->id );
				}
			}
			catch( \OutOfRangeException $e ) { }

			if ( ! $databaseFpage )
			{
				$form->addMessage( 'frontpage_no_db_fpage_no_forum_link', 'ipsMessage ipsMessage_info' );
			}
			
			if ( $current )
			{
				$rebuildUrl = \IPS\Http\Url::internal( 'app=frontpage&module=databases&controller=databases&id=' . $current->id . '&do=rebuildTopicContent' );
				$rebuildUrlCounts = \IPS\Http\Url::internal( 'app=frontpage&module=databases&controller=databases&id=' . $current->id . '&do=rebuildCommentCounts' );
				
				\IPS\Member::loggedIn()->language()->words['database_forum_record_desc'] = \IPS\Member::loggedIn()->language()->addToStack( 'database_forum_record__desc' ) . ' ' .
					\IPS\Member::loggedIn()->language()->addToStack( 'database_forum_record__rebuild', NULL, array( 'sprintf' => array( $rebuildUrl ) ) ) . ' ' .
					\IPS\Member::loggedIn()->language()->addToStack( 'database_forum_comments__rebuild', NULL, array( 'sprintf' => array( $rebuildUrlCounts ) ) );
			}
			else
			{
				\IPS\Member::loggedIn()->language()->words['database_forum_record_desc'] = \IPS\Member::loggedIn()->language()->addToStack( 'database_forum_record__desc' );
			}
			
			$form->add( new \IPS\Helpers\Form\YesNo( 'database_forum_record', $current ? $current->forum_record : FALSE, FALSE, array( 'togglesOn' => array(
				'database_forum_comments',
				'database_forum_forum',
				'database_forum_prefix',
				'database_forum_suffix',
				'database_forum_delete'
			) ), NULL, NULL, NULL, 'database_forum_record' ) );
			
			$form->add( new \IPS\Helpers\Form\YesNo( 'database_forum_comments', $current ? $current->forum_comments : FALSE, FALSE, array(), NULL, NULL, NULL, 'database_forum_comments' ) );
				
			$form->add( new \IPS\Helpers\Form\Node( 'database_forum_forum', $current ? $current->forum_forum : NULL, FALSE, array(
					'class'		      => '\IPS\forums\Forum',
					'disabled'	      => false,
					'permissionCheck' => function( $node )
					{
						return $node->sub_can_post;
					}
			), function( $val )
			{
				if ( ! $val and \IPS\Request::i()->database_forum_record_checkbox )
				{
					throw new \InvalidArgumentException('frontpage_database_no_forum_selected');
				}
				return true;
			}, NULL, NULL, NULL, 'database_forum_forum' ) );
			
			$form->add( new \IPS\Helpers\Form\Text( 'database_forum_prefix', $current ? $current->forum_prefix: '', FALSE, array( 'trim' => FALSE ), NULL, NULL, NULL, 'database_forum_prefix' ) );
			$form->add( new \IPS\Helpers\Form\Text( 'database_forum_suffix', $current ? $current->forum_suffix: '', FALSE, array( 'trim' => FALSE ), NULL, NULL, NULL, 'database_forum_suffix' ) );
			$form->add( new \IPS\Helpers\Form\YesNo( 'database_forum_delete' , $current ? $current->forum_delete : FALSE, FALSE, array(), NULL, NULL, NULL, 'database_forum_delete' ) );
		}
		
		/* SEO */
		$form->addTab( 'content_database_form_seo' );
		$form->addHeader( 'database_canonical_header' );
		
		$form->add( new \IPS\Helpers\Form\Radio( 'database_canonical_flag', ! empty( $current ) ? $current->canonical_flag : NULL, FALSE, array(
			'options' => array(
					'0'  => 'database_canonical_flag_0',
					'1'  => 'database_canonical_flag_1'
			)
		) ) );
		
		if ( ! $current )
		{
			$form->addTab( 'content_database_form_options_fpage' );
			$form->addMessage( 'content_database_form_options_fpage_msg' );
			
			$fpageToggles    = array();
			$fpageFormFields = array();
			
			foreach( \IPS\frontpage\Fpages\Fpage::formElements() as $name => $field )
			{
				if ( $name === 'fpage_name' )
				{
					/* Overwrite field */
					$field = new \IPS\Helpers\Form\Translatable( 'fpage_name', FALSE, NULL, array( 'app' => 'frontpage', 'key' => NULL, 'maxLength' => 64 ), function( $val )
					{
						if ( !trim( $val[ \IPS\Lang::defaultLanguage() ] ) AND \IPS\Request::i()->database_create_fpage === 'new' )
						{
							throw new \DomainException('form_required');
						}
					}, NULL, NULL, 'fpage_name' );
				}

				if ( $name !== 'fpage_content' AND $name !== 'tab_content' )
				{
					$fpageToggles[] = $name;
					$fpageFormFields[ $name ] = $field;
				}

				if ( $name === 'fpage_folder_id' )
				{
					$fpageFormFields['fpage_type'] = new \IPS\Helpers\Form\Radio(
						'fpage_type', 'builder', FALSE, array(
						'options'  => array(
							'builder' => 'fpage_type_builder',
							'html'    => 'fpage_type_manual'
						),
						'descriptions' => array(
							'builder' => 'fpage_type_builder_desc',
							'html'    => 'fpage_type_manual_custom_desc'
						),
						'toggles' => array(
							'builder' => array( 'fpage_template' ),
							'html'    => array( 'fpage_show_sidebar', 'fpage_wrapper_template', 'fpage_ipb_wrapper' )
						)
					), NULL, NULL, NULL, 'fpage_type'
					);

					$fpageToggles[] = 'fpage_type';
				}
			}
			
			$form->add( new \IPS\Helpers\Form\Radio( 'database_create_fpage', 'existing', FALSE, array(
				'options'   => array(
					'existing' => 'database_create_fpage_existing',
					'new'	   => 'database_create_fpage_new'
				),
				'toggles' => array(
					'new' => $fpageToggles
				)
			), NULL, NULL, NULL, 'database_create_fpage' ) );
			
			foreach( $fpageFormFields as $name => $field )
			{
				if ( \is_array( $field ) )
				{
					$form->addHeader( $field[0] );
				}
				else
				{
					$form->add( $field );
				}
			}
		}

		return $form;
	}
}