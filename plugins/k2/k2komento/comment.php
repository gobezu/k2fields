<?php
/**
 * @package		Komento
 * @copyright	Copyright (C) 2012 Stack Ideas Private Limited. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 *
 * Komento is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 */

defined('_JEXEC') or die('Restricted access');

/**
 * All the logics, event trigger, integration regarding to comment's CRUD operation,
 * handles all application interaction with comment.
 */
class KomentoComment extends JObject
{
	public function __construct( $id )
	{
		if( $id )
		{
			$this->load( $id );
		}
	}

	public function load( $id )
	{
		$commentTable = Komento::getTable( 'Comments' );

		if (!$commentTable->load($id))
		{
			$this->setError( 'Invalid Comment ID' );
			return false;
		}

		// bind with comment table data
		$this->setProperties($commentTable->getProperties());

		return true;
	}

	// Bind an object or array to this comment object
	public function bind( $data )
	{
		if( isset( $data['commentid'] ) )
		{
			$this->id = $data['commentid'];
		}

		$this->id		= $data['id'] ? (int) $data[$id] : null;

		$filter			= JFilterInput::getInstance();
		$this->comment	= $filter->clean($data['comment']);

		if( isset( $data['name'] ) )
		{
			$this->name		= $filter->clean($data['name']);
		}

		if( isset( $data['title'] ) )
		{
			$this->title	= $filter->clean($data['title']);
		}

		if( isset( $data['email'] ) )
		{
			$this->email	= $filter->clean($data['email']);
		}

		if( isset( $data['url'] ) )
		{
			$this->url		= $filter->clean($data['url']);
		}
	}

	public function save()
	{
		$config		= Komento::getConfig();

		// Create the comment table object
		$commentsModel	= Komento::getModel( 'comments' );
		$commentTable = Komento::getTable( 'Comments' );
		$commentTable->bind($this->getProperties());

		// empty name, email defaults to user
		// or if guest, then default empty name to 'Guest'
		$profile	= Komento::getProfile();
		$now		= Komento::getDate()->toMySQL();

		// @rule: Determine if this record is new or not.
		$isNew  	= ( empty( $this->id) ) ? true : false;

		// trigger onBeforeSaveComment
		// ...

		if( !$commentTable->store() )
		{
			$this->setError( 'Comment save failed' );
			return false;
		}

		$this->id = $commentTable->id;

		// trigger onAfterSaveComment
		// ...

		// Add activity
		$action = $commentTable->parent_id ? 'reply' : 'comment';
		$activity = Komento::getHelper( 'activity' )->process( $action, $commentTable->id );

		// Send notifications
		if( $config->get( 'notification_enable' ) )
		{
			if( $commentTable->published == 1 && $isNew && ( ( $action == 'comment' && $config->get( 'notification_event_new_comment' ) ) || ( $action == 'reply' && $config->get( 'notification_event_new_reply' ) ) ) )
			{
				Komento::getHelper( 'Notification' )->push( $action, 'subscribers,author,usergroups', array( 'commentId' => $this->id ) );
			}

			if( $commentTable->published == 2 && $config->get( 'notification_event_new_pending' ) )
			{
				Komento::getHelper( 'Notification' )->push( 'pending', 'author,usergroups', array( 'commentId' => $this->id ) );
			}
		}

		return true;
	}

	public function delete()
	{
		// Create the comment table object
		$commentTable = Komento::getTable( 'Comments' );
		$commentTable->bind($this->getProperties());

		// trigger onBeforeDeleteComment
		// ..
                Komento::trigger( 'onBeforeDeleteComment', array( 'component' => $commentTable->component, 'cid' => $commentTable->cid, 'comment' => &$commentTable ) );

		if( !$commentTable->delete() )
		{
			$this->setError( 'Comment delete failed' );
			return false;
		}

		// trigger onAfterDeleteComment
		// ..
                Komento::trigger( 'onAfterDeleteComment', array( 'component' => $commentTable->component, 'cid' => $commentTable->cid, 'comment' => &$commentTable ) );

		// Always move child up regardless of deleting child or not
		$commentModel = Komento::getModel( 'Comments' );
		$commentModel->moveChildsUp( $this->id );

		// Clear activities
		$activityModel	= Komento::getModel( 'Activity' );
		$activityModel->delete( $this->id );

		// Clear actions
		$actionsModel = Komento::getModel( 'Actions' );
		$actionsModel->removeAction('all', $this->id, 'all');

		// Process activities
		$activity = Komento::getHelper( 'activity' )->process( 'remove', $this->id );

		// Delete attachments
		Komento::getHelper( 'file' )->clearAttachments( $this->id );

		return true;
	}

	public function publish( $type = '1' )
	{
		// set new = false
		$new = false;

		// Create the comment table object
		$commentTable = Komento::getTable( 'Comments' );
		$commentTable->bind($this->getProperties());

		// get date
		$now = Komento::getDate()->toMySQL();

		// check original status == 2
		if( $commentTable->published == 2 )
		{
			$new = true;
		}

		$commentTable->published = $type;
                
                $event = $type == '1' ? 'onBeforePublishComment' : 'onBeforeUnpublishComment';
                
                Komento::trigger( $event, array( 'component' => $commentTable->component, 'cid' => $commentTable->cid, 'comment' => &$commentTable ) );

		if($type == '1')
		{
			$commentTable->publish_up = $now;
		}
		else
		{
			$commentTable->publish_down = $now;
		}

		if( !$commentTable->store() )
		{
			$this->setError( 'Comment publish/unpublish failed' );
			return false;
		}

                $event = str_replace('onBefore', 'onAfter', $event);
                
                Komento::trigger( $event, array( 'component' => $commentTable->component, 'cid' => $commentTable->cid, 'comment' => &$commentTable ) );
		// bind with comment table data after successfully saving comment table.
		$this->setProperties($commentTable->getProperties());

		if( $new )
		{
			// send email
			$notificationType = $commentTable->parent_id == 0 ? 'comment' : 'reply';
			Komento::getHelper( 'Notification' )->push( $notificationType, 'subscribers,author,usergroups', array( 'commentId' => $commentTable->id ) );

			// process activities
			$action = $commentTable->parent_id ? 'reply' : 'comment';
			$activity = Komento::getHelper( 'activity' )->process( $action, $commentTable->id );
		}

		return true;
	}

	public function mark( $type = '0' )
	{
		$commentTable = Komento::getTable( 'Comments' );
		$commentTable->bind($this->getProperties());
		$userId		= JFactory::getUser()->id;

		// remove all reported flags
		$actionsModel = Komento::getModel( 'Actions' );
		$actionsModel->removeAction('spam', $commentTable->id, 'all');
		$actionsModel->removeAction('offensive', $commentTable->id, 'all');
		$actionsModel->removeAction('offtopic', $commentTable->id, 'all');

		$commentTable->flag = $type;
		$commentTable->flag_by = $userId;

		if( !$commentTable->store() )
		{
			$this->setError( 'Comment mark failed' );
			return false;
		}

		return true;
	}
}