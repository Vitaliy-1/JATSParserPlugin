<?php
/**
 * @file plugins/generic/jatsParser/JatsParserPlugin.inc.php
 *
 * Copyright (c) 2017 Vitaliy Bezsheiko, MD, Department of Psychosomatic Medicine and Psychotherapy, Bogomolets National Medical University, Kyiv, Ukraine
 * Distributed under the GNU GPL v3.
 *
 * @class JatsParserSettingsForm
 * @ingroup plugins_generic_jatsParser
 *
 */

import('lib.pkp.classes.plugins.GenericPlugin');
import('plugins.generic.jatsParser.lib.main.MainJatsParser');

class JatsParserPlugin extends GenericPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return true;
		if ($success && $this->getEnabled()) {
			
			// TODO: how do you define a sequence for all plugins using this hook?
			HookRegistry::register('Templates::Article::Main', array($this, 'embedHtml'));
			
			// Add stylesheet and javascript
			HookRegistry::register('TemplateManager::display',array($this, 'displayCallback'));
			
		
		}
		return $success;
	}
	/**
	 * Get the plugin display name.
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.jatsParser.displayName');
	}
	/**
	 * Get the plugin description.
	 * @return string
	 */
	function getDescription() {
		return __('plugins.generic.jatsParser.description');
	}
	/**
	 * @copydoc Plugin::getActions()
	 */
	function getActions($request, $verb) {
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		return array_merge(
			$this->getEnabled()?array(
				new LinkAction(
					'settings',
					new AjaxModal(
						$router->url($request, null, null, 'manage', null, array('verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic')),
						$this->getDisplayName()
					),
					__('manager.plugins.settings'),
					null
				),
			):array(),
			parent::getActions($request, $verb)
		);
	}
 	/**
	 * @copydoc Plugin::manage()
	 */
	function manage($args, $request) {
		switch ($request->getUserVar('verb')) {
			case 'settings':
				$context = $request->getContext();
				AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON,  LOCALE_COMPONENT_PKP_MANAGER);
				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->register_function('plugin_url', array($this, 'smartyPluginUrl'));
				$this->import('JatsParserSettingsForm');
				$form = new JatsParserSettingsForm($this, $context->getId());
				if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						return new JSONMessage(true);
					}
				} else {
					$form->initData();
				}
				return new JSONMessage(true, $form->fetch($request));
		}
		return parent::manage($args, $request);
	}
	/**
	 * @copydoc PKPPlugin::getTemplatePath
	 */
	function getTemplatePath($inCore = false) {
		return parent::getTemplatePath($inCore) . 'templates/';
	}
	
	
	/**
	 * Insert stylesheet and js
	 */
	function displayCallback($hookName, $params) {
		$template = $params[1];
			
		if ($template != 'frontend/pages/article.tpl') return false;
		
		$templateMgr = $params[0];
		$templateMgr->addStylesheet('jatsParser', Request::getBaseUrl() . DIRECTORY_SEPARATOR . $this->getPluginPath() . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'psychosomatics.css');
		$templateMgr->addJavaScript('jatsParser', Request::getBaseUrl() . DIRECTORY_SEPARATOR . $this->getPluginPath() . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR .'psychosomatics.js');
		
		$templateMgr->addJavaScript('mathJax', '//cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.1/MathJax.js?config=MML_HTMLorMML-full');

		
		return false;
	}
	
	
	/**
	 * Convert and embed html to abstract page footer
	 * @param $hookName string
	 * @param $params array
	 */
	function embedHtml($hookName, $params) {
		$smarty =& $params[1];
		$output =& $params[2];

        $articleArrays = $smarty->get_template_vars('article');

		foreach ($articleArrays->getGalleys() as $galley) {
			if ($galley && in_array($galley->getFileType(), array('application/xml', 'text/xml'))) {
				$xmlGalley = $galley;
			}
		}

		// Return false if no XML galleys available
		if (!$xmlGalley) return false;

		// Parsing JATS XML
        $document = new DOMDocument;
        $document->load($xmlGalley->getFile()->getFilePath());
        $xpath = new DOMXPath($document);

		$body = new Body();
        $sections = $body->bodyParsing($xpath);

        /* Assigning references */
        $back = new Back();
        $references = $back->parsingBack($xpath);

		// Assigning variables to article template
        $smarty->assign('sections', $sections);
        $smarty->assign('references', $references);
        $smarty->assign('path_template',$this->getTemplatePath());
		$output .= $smarty->fetch($this->getTemplatePath() . 'articleMainText.tpl');
		
		return false;


	}
	/**
	 * Return string containing date
	 * @param $text String to be formatted
	 * @param $format Date format, default DATE_W3C
	 * @return string
	 */	

    public static function formatDate($text, $format = DATE_W3C){
        $date = new \DateTime($text);
        return $date->format($format);
    }
}
?>