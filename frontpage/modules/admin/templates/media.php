<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief		FrontPage Media Management
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4+
 * @subpackage	FrontPage
 * @version     1.0.0 RC
 * @source      https://github.com/devCU/IPS-FrontPage
 * @Issue Trak  https://www.devcu.com/devcu-tracker/
 * @Created     25 APR 2019
 * @Updated     22 MAY 2019
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

namespace IPS\frontpage\modules\admin\templates;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * media
 */
class _media extends \IPS\Node\Controller
{
	/**
	 * Node Class
	 */
	protected $nodeClass = '\IPS\frontpage\Media\Folder';

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'media_manage' );
		parent::execute();
	}

	/**
	* Get Root Buttons
	*
	* @return	array
	*/
	public function _getRootButtons()
	{
		$buttons   = array();

		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'frontpage', 'templates', 'fpage_add' )  )
		{
			$buttons['add_folder'] = array(
				'icon'	=> 'folder-open',
				'title'	=> 'frontpage_add_media_folder',
				'link'	=> \IPS\Http\Url::internal( 'app=frontpage&module=templates&controller=media&do=form' ),
				'data'  => array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('frontpage_add_media_folder') )
			);

			$buttons['add_fpage'] = array(
				'icon'	=> 'plus-circle',
				'title'	=> 'frontpage_add_media',
				'link'	=>  \IPS\Http\Url::internal( 'app=frontpage&module=templates&controller=media&subnode=1&do=form' ),
				'data'  => array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('frontpage_add_media') )
			);
		}

		return $buttons;
	}
	
	/**
	 * Delete many at once
	 *
	 * @return void
	 */
	public function deleteByFileIds()
	{
		if ( isset( \IPS\Request::i()->fileIds ) )
		{
			$ids = \IPS\Request::i()->fileIds;
			
			if ( ! \is_array( $ids ) )
			{
				$try = json_decode( $ids, TRUE );
				
				if ( ! \is_array( $try ) )
				{
					$ids = array( $ids );
				}
				else
				{
					$ids = $try;
				}
			}
			
			if ( \count( $ids ) )
			{
				\IPS\frontpage\Media::deleteByFileIds( $ids );
			}
		}
	}
	
	/**
	 * Show the templates tree
	 *
	 * @return	string
	 */
	protected function manage()
	{
		if ( \IPS\Theme::designersModeEnabled() )
		{
			$link = \IPS\Http\Url::internal( 'app=core&module=customization&controller=themes&do=designersmode' );
			\IPS\Output::i()->output .= \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->message( \IPS\Member::loggedIn()->language()->addToStack('frontpage_media_designer_mode_warning', NULL, array( 'sprintf' => array( $link ) ) ), 'information', NULL, FALSE );
		}
		else
		{
			$url = \IPS\Http\Url::internal( "app=frontpage&module=templates&controller=media" );
	
			/* Display the table */
			\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack('menu__frontpage_templates_media');
			$output = new \IPS\Helpers\Tree\Tree( $url, 'menu__frontpage_templates_media',
				/* Get Roots */
				function () use ( $url )
				{
					$data = \IPS\frontpage\modules\admin\templates\media::getRowsForTree( 0 );
					$rows = array();
	
					foreach ( $data as $id => $row )
					{
						if( ( \IPS\Request::i()->isAjax() && $row instanceof \IPS\frontpage\Media ) || !\IPS\Request::i()->isAjax() )
						{
							$rows[ $id ] = ( $row instanceof \IPS\frontpage\Media) ? \IPS\frontpage\modules\admin\templates\media::getItemRow( $row, $url ) : \IPS\frontpage\modules\admin\templates\media::getFolderRow( $row, $url );	
						}					
					}
	
					if( \IPS\Request::i()->isAjax() ){
						\IPS\Output::i()->sendOutput( json_encode( $rows ), 200, 'application/json', \IPS\Output::i()->httpHeaders );
					}
	
					return $data;
				},
				/* Get Row */
				function ( $id, $root ) use ( $url )
				{
					if ( $root )
					{
						return \IPS\frontpage\modules\admin\templates\media::getFolderRow( \IPS\frontpage\Media\Folder::load( $id ), $url );
					}
					else
					{
						return \IPS\frontpage\modules\admin\templates\media::getItemRow( \IPS\frontpage\Media::load( $id ), $url );
					}
				},
				/* Get Row Parent ID*/
				function ()
				{
					return NULL;
				},
				/* Get Children */
				function ( $id ) use ( $url )
				{
					$rows = array();
					$data = \IPS\frontpage\modules\admin\templates\media::getRowsForTree( $id );
	
					if ( ! isset( \IPS\Request::i()->subnode ) )
					{
						foreach ( $data as $id => $row )
						{
							if( \IPS\Request::i()->get == 'folders' && !( $row instanceof \IPS\frontpage\Media ) )
							{
								$rows[ $id ] = \IPS\frontpage\modules\admin\templates\media::getFolderRow( $row, $url );
							}
							elseif ( \IPS\Request::i()->get == 'files' && $row instanceof \IPS\frontpage\Media )
							{
								$rows[ $id ] = \IPS\frontpage\modules\admin\templates\media::getItemRow( $row, $url );
							}
							
						}
					}
	
					if( \IPS\Request::i()->isAjax() ){
						\IPS\Output::i()->sendOutput( json_encode( $rows ), 200, 'application/json', \IPS\Output::i()->httpHeaders );
					}
	
					return $rows;
				},
	           array( $this, '_getRootButtons' ),
	           TRUE,
	           FALSE,
	           FALSE
			);
			
			\IPS\Output::i()->jsFiles  = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'admin_media.js', 'frontpage' ) );
			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'media/media.css', 'frontpage', 'admin' ) );
			
			if( \IPS\Request::i()->isAjax() )
			{
				\IPS\Output::i()->sendOutput( $output, 200, 'text/html', \IPS\Output::i()->httpHeaders );
			}
			else
			{
				\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'media', 'frontpage', 'admin' )->media( $output );	
			}
		}
	}

	/**
	 * Replace an existing file
	 *
	 * @return void
	 */
	public function replace()
	{
		if( !isset( \IPS\Request::i()->id ) OR !\IPS\Request::i()->id )
		{
			\IPS\Output::i()->error( 'missing_media_file', '3T334/2', 404, '' );
		}

		try
		{
			$media = \IPS\frontpage\Media::load( \IPS\Request::i()->id );
		}
		catch( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'missing_media_file', '3T334/3', 404, '' );
		}

		$form = new \IPS\Helpers\Form( 'form', 'upload' );
		$form->class = 'ipsForm_vertical ipsForm_noLabels';
			
		$form->add( new \IPS\Helpers\Form\Upload( 'media_filename', NULL, FALSE, array( 'allowedFileTypes' => array_merge( \IPS\File::$safeFileExtensions, array( 'pdf' ) ), 'obscure' => FALSE, 'maxFileSize' => 5, 'storageExtension' => 'frontpage_Media', 'storageContainer' => 'fpages_media', 'multiple' => FALSE, 'minimize' => FALSE ), NULL, NULL, NULL, 'media_filename' ) );

		if ( $values = $form->values() )
		{
			$existingFileExtension	= mb_substr( $media->filename_stored, mb_strrpos( $media->filename_stored, '.' ) + 1 );
			$newFileExtension		= mb_substr( $values['media_filename']->originalFilename, mb_strrpos( $values['media_filename']->originalFilename, '.' ) + 1 );

			/* If we have the same extension, we will just retain the same filename */
			if( $existingFileExtension == $newFileExtension )
			{
				$media->file_object		= (string) \IPS\File::create( 'frontpage_Media', $media->filename_stored, $values['media_filename']->contents(), 'fpages_media', TRUE, NULL, FALSE );
			}
			/* Otherwise we need to update the rest of the file info too */
			else
			{
				$media->is_image		= $values['media_filename']->isImage();
				$media->filename		= $values['media_filename']->originalFilename;
				$media->filename_stored	= $media->parent . '_' . $media->filename;
				$media->file_object		= (string) \IPS\File::create( 'frontpage_Media', $media->filename_stored, $values['media_filename']->contents(), 'fpages_media', TRUE, NULL, FALSE );
			}

			$media->setFullPath( ( $media->parent ? \IPS\frontpage\Media\Folder::load( $media->parent )->path : '' ) );
			$media->save();
			
			/* Remove the original as we created a copy with a slightly altered filename */
			try
			{
				$values['media_filename']->delete();
			}
			catch( \Exception $ex ) { }
			
			/* Wipe out included JS just in case we're using this media thing */
			\IPS\frontpage\Templates::deleteCompiledFiles();
			\IPS\frontpage\Fpages\Fpage::deleteCachedIncludes();
			
			if ( \IPS\Request::i()->isAjax() )
			{
				$url = \IPS\Http\Url::internal( "app=frontpage&module=templates&controller=media" );
				$data = \IPS\frontpage\modules\admin\templates\media::getRowsForTree( $media->parent );
				$rows = array();

				foreach ( $data as $id => $row )
				{
					if ( $row instanceof \IPS\frontpage\Media )
					{
						$rows[ $id ] = \IPS\frontpage\modules\admin\templates\media::getItemRow( $row, $url );
					}
				}

				\IPS\Output::i()->sendOutput( json_encode( array( 'fileID' => $media->id, 'folderID' => $media->parent, 'rows' => $rows ) ), 200, 'application/json', \IPS\Output::i()->httpHeaders );
			}
			else
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=frontpage&module=templates&controller=media' ) );
			}
		}

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global', 'core', 'admin' )->block( 'upload', $form, FALSE );
	}
	
	/**
	 * Upload items
	 *
	 * @return void
	 */
	public function upload()
	{
		$form = new \IPS\Helpers\Form( 'form', 'upload' );
		$form->class = 'ipsForm_vertical ipsForm_noLabels';
			
		$form->add( new \IPS\Helpers\Form\Upload( 'media_filename', NULL, FALSE, array( 'allowedFileTypes' => array_merge( \IPS\File::$safeFileExtensions, array( 'pdf', 'svg' ) ), 'obscure' => FALSE, 'maxFileSize' => 5, 'storageExtension' => 'frontpage_Media', 'storageContainer' => 'fpages_media', 'multiple' => true, 'minimize' => FALSE ), NULL, NULL, NULL, 'media_filename' ) );
			
		if ( ! isset( \IPS\Request::i()->media_parent ) and ! \IPS\Request::i()->media_parent )
		{
			$form->add( new \IPS\Helpers\Form\Node( 'media_parent', 0, FALSE, array(
				'class'    => '\IPS\frontpage\Media\Folder',
				'zeroVal'  => 'node_no_parent'
			) ) );
		}
		else
		{
			$form->hiddenValues['media_parent_inline'] = \IPS\Request::i()->media_parent;
		}
		
		if ( $values = $form->values() )
		{
			$parent = 0;
			$count = 0;

			if ( isset( $values['media_parent_inline'] ) AND $values['media_parent_inline'] )
			{
				$parent = $values['media_parent_inline'];
			}
			else
			{
				if ( isset( $values['media_parent'] ) AND ( ! empty( $values['media_parent'] ) OR $values['media_parent'] === 0 ) )
				{
					$parent = ( $values['media_parent'] === 0 ) ? 0 : $values['media_parent']->id;
				}
			}

			foreach( $values['media_filename'] as $media )
			{
				$filename = $media->originalFilename;
	
				$prefix = $parent . '_';
	
				if ( mb_strstr( $filename, $prefix ) )
				{
					$filename = mb_substr( $filename, mb_strlen( $prefix ) );
				}
				
				$new = new \IPS\frontpage\Media;
				$new->filename        = $filename;
				$new->filename_stored = $parent . '_' . $filename;
				$new->is_image        = $media->isImage();
				$new->parent          = $parent;
				$new->added           = time();
				$new->file_object     = (string) \IPS\File::create( 'frontpage_Media', $new->filename_stored, $media->contents(), 'fpages_media', TRUE, NULL, FALSE );
				$new->save();
				
				$new->setFullPath( ( $parent ? \IPS\frontpage\Media\Folder::load( $parent )->path : '' ) );
				$new->save();
				
				/* Remove the original as we created a copy with a slightly altered filename */
				try
				{
					$media->delete();
				}
				catch( \Exception $ex ) { }
				
				$count++;
			}
			
			/* Wipe out included JS just in case we're using this media thing */
			\IPS\frontpage\Templates::deleteCompiledFiles();
			\IPS\frontpage\Fpages\Fpage::deleteCachedIncludes();
			
			if ( \IPS\Request::i()->isAjax() )
			{
				$url = \IPS\Http\Url::internal( "app=frontpage&module=templates&controller=media" );
				$data = \IPS\frontpage\modules\admin\templates\media::getRowsForTree( $parent );
				$rows = array();

				foreach ( $data as $id => $row )
				{
					if ( $row instanceof \IPS\frontpage\Media )
					{
						$rows[ $id ] = \IPS\frontpage\modules\admin\templates\media::getItemRow( $row, $url );
					}
				}

				\IPS\Output::i()->sendOutput( json_encode( array( 'count' => $count, 'folderID' => $parent, 'rows' => $rows ) ), 200, 'application/json', \IPS\Output::i()->httpHeaders );
			}
			else
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=frontpage&module=templates&controller=media' ) );
			}
		}
		
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global', 'core', 'admin' )->block( 'upload', $form, FALSE );
	}
	
	/**
	 * Tree Search
	 *
	 * @return	void
	 */
	protected function search()
	{
		$rows = array();
		$url  = \IPS\Http\Url::internal( "app=frontpage&module=templates&controller=media" );

		/* Get results */
		$items   = \IPS\frontpage\Media::search( 'media_filename', \IPS\Request::i()->input, 'media_filename' );

		/* Convert to HTML */
		foreach ( $items as $id => $result )
		{
			$rows[ $id ] = $this->getItemRow( $result, $url );
		}

		if( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->sendOutput( json_encode( $rows ), 200, 'application/json', \IPS\Output::i()->httpHeaders );
		}
		else
		{
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'trees', 'core' )->rows( $rows, '' );	
		}		
	}

	/**
	 * Returns a JSON object with some file information
	 *
	 * @return	void
	 */
	protected function getFileInfo()
	{
		try
		{
			$row = \IPS\frontpage\Media::load( \IPS\Request::i()->id );
			$fileObject = \IPS\File::get( 'frontpage_Media', $row->file_object );
		}
		catch( \OutOfRangeException $ex )
		{
			return;
		}
		
		/* Make a human-readable size */
		$filesize = \IPS\Output\Plugin\Filesize::humanReadableFilesize( $fileObject->filesize() );
		\IPS\Member::loggedIn()->language()->parseOutputForDisplay( $filesize );

		$output = array( 
			'fileSize' => $filesize,
			'dimensions' => NULL
		);

		/* If this is an image we'll also show the dimensions */
		if( $row->is_image )
		{
			$dimensions = $fileObject->getImageDimensions();
			$output['dimensions'] = $dimensions[0] . ' x ' . $dimensions[1];
		}

		\IPS\Output::i()->sendOutput( json_encode( $output ), 200, 'application/json', \IPS\Output::i()->httpHeaders );
	}

	/**
	 * Return HTML for a fpage row
	 *
	 * @param   array   $item	Row data
	 * @param	object	$url	\IPS\Http\Url object
	 * @return	string	HTML
	 */
	public static function getItemRow( $item, $url )
	{
		return \IPS\Theme::i()->getTemplate( 'media', 'frontpage', 'admin' )->fileListing( $url, $item );
	}

	/**
	 * Return HTML for a folder row
	 *
	 * @param   array   $folder	Row data
	 * @param	object	$url	\IPS\Http\Url object
	 * @return	string	HTML
	 */
	public static function getFolderRow( $folder, $url )
	{
		return \IPS\Theme::i()->getTemplate( 'media', 'frontpage', 'admin' )->folderRow( $url, $folder );
	}


	/**
	 * Fetch rows of folders/templates
	 *
	 * @param	int	$folderId		Parent ID to fetch from
	 */
	public static function getRowsForTree( $folderId=0 )
	{
		try
		{
			if ( $folderId === 0 )
			{
				$folders = \IPS\frontpage\Media\Folder::roots();
			}
			else
			{
				$folders = \IPS\frontpage\Media\Folder::load( $folderId )->children( NULL, NULL, FALSE );
			}
		}
		catch( \OutOfRangeException $ex )
		{
			$folders = array();
		}

		$media = \IPS\frontpage\Media::getChildren( $folderId );

		return \IPS\frontpage\Media\Folder::munge( $folders, $media );
	}

}