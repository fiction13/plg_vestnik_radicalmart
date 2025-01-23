<?php
/*
 * @package   Vestnik - RadicalMart
 * @version   __DEPLOY_VERSION__
 * @author    Dmitriy Vasyukov - https://fictionlabs.ru
 * @copyright Copyright (c) 2025 Fictionlabs. All rights reserved.
 * @license   GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link      https://fictionlabs.ru/
 */

namespace Joomla\Plugin\Vestnik\RadicalMart\Extension;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\RadicalMart\Site\Helper\RouteHelper;
use Joomla\Component\Vestnik\Administrator\Helper\VestnikHelper;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;

class Radicalmart extends CMSPlugin implements SubscriberInterface
{
	/**
	 * Application object.
	 *
	 * @var    CMSApplicationInterface
	 * @since  4.1.0
	 */
	protected $app;

	/**
	 * Affects constructor behavior.
	 *
	 * @var  boolean
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $autoloadLanguage = true;

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onVestnikPrepareForm' => 'onVestnikPrepareForm',
			'onVestnikAfterSave'   => 'onVestnikAfterSave',
		];
	}

	/**
	 * Method to change forms.
	 *
	 * @param   Form   $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @throws  \Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onVestnikPrepareForm($form, $data)
	{
		$formName = $form->getName();

		if ($formName === 'com_config.component')
		{
			// Add path
			Form::addFormPath(JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name . '/forms');

			// Load config
			$form->loadFile('com_config.component');
		}

		if (VestnikHelper::check($this->_name) && $formName === 'com_radicalmart.product' && Factory::getApplication()->isClient('administrator'))
		{
			// Load language
			Factory::getApplication()->getLanguage()->load('com_vestnik', JPATH_ADMINISTRATOR);

			// Add path
			Form::addFormPath(JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name . '/forms');

			// Load config
			$form->loadFile('com_radicalmart.product');
		}
	}

	/**
	 * Listener for the `onVestnikAfterSave` event.
	 *
	 * @param   string  $context  Context
	 * @param   object  $item     Saved item
	 * @param   bool    $isNew    Is new item glag
	 *
	 * @throws  \Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onVestnikAfterSave($context, $item, $isNew)
	{
		if ($isNew && VestnikHelper::check($this->_name) && $context === 'com_radicalmart.product' && Factory::getApplication()->isClient('administrator'))
		{
			$params = new Registry($item->params);
			if ((int) $params->get('vestnik_add', 0))
			{
				$media = new Registry($item->media);
				$text = $item->fulltext;
				$data = array(
					'title'    => $item->title,
					'context'  => $context,
					'item_id'  => $item->id,
					'content'  => $text,
					'state'    => $item->state,
					'hashtags' => $params->get('hashtags', []),
					'date'     => $params->get('vestnik_date'),
					'link'     => rtrim(Uri::root(), '/') . Route::link('site', RouteHelper::getProductRoute($item->id, $item->category)),
					'image'    => $media->get('image')
				);

				// Add post
				VestnikHelper::addPost($data);
			}
		}
	}
}