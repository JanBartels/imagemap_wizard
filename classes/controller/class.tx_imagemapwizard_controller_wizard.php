<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008 Tolleiv Nietsch (info@tolleiv.de)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class/Function provides basic action for the Wizard-Form
 *
 * @author	Tolleiv Nietsch <info@tolleiv.de>
 */

class tx_imagemapwizard_controller_wizard {
	protected $view;
	protected $context = 'wizard';
	protected $ajax = false;
	protected $params;
	protected $forceValue;

	/**
	 * Initialize Context and required View
	 */
	public function __construct() {
		$this->initContext();
		$this->initView();
	}

	/**
	 * Default action just renders the Wizard with the default view.
	 */
	protected function wizardAction() {
		$params = GeneralUtility::_GP('P');
		$currentValue = $GLOBALS['BE_USER']->getSessionData('imagemap_wizard.value');
		// @todo use-Flex-DataObject if needed
		try {
			$this->view->setData($this->makeDataObj($params['table'], $params['field'], $params['uid'], $currentValue));
		} catch (Exception $e) {
			// @todo make something smart if params are empty and object creation failed
		}
		$this->view->renderContent();
	}

	/**
	 * Form action just renders the TCEForm which opens the wizard
	 * comes with a cool preview and Ajax functionality which updates the preview...
	 */
	protected function tceformAction() {
		try {
			$data = $this->makeDataObj($this->params['table'], $this->params['field'], $this->params['uid'], $this->forceValue);
		} catch (Exception $e) {
			// @todo make something smart if params are empty and object creation failed
		}
		$data->setFieldConf($this->params['fieldConf']);

		$this->view->setData($data);
		$this->view->setTCEForm($this->params['pObj']);

		$this->view->setFormName($this->params['itemFormElName']);
		$this->view->setWizardConf($this->params['fieldConf']['config']['wizards']);
		GeneralUtility::loadTCA($this->params['table']);

		return $this->view->renderContent();
	}

	/**
	 *
	 */
	protected function tceformAjaxAction() {
		$this->params['table'] = GeneralUtility::_GP('table');
		$this->params['field'] = GeneralUtility::_GP('field');
		$this->params['uid'] = GeneralUtility::_GP('uid');
		$this->params['fieldConf'] = unserialize(stripslashes((GeneralUtility::_GP('config'))));
		$this->params['pObj'] = GeneralUtility::makeInstance('TYPO3\CMS\Backend\Form\FormEngine');
		$this->params['pObj']->initDefaultBEMode();
		$this->params['itemFormElName'] = GeneralUtility::_GP('formField');

		$this->forceValue = GeneralUtility::_GP('value');
		$GLOBALS['BE_USER']->setAndSaveSessionData('imagemap_wizard.value', $this->forceValue);
		echo $this->tceformAction();
	}


	/**
	 * Execute required action which is determined by the given context
	 */
	public function triggerAction() {
		$action = $this->context . ($this->ajax ? 'Ajax' : '') . 'Action';
		return call_user_func_array(array($this, $action), array());
	}

	/**
	 * Determine context
	 */
	protected function initContext($forceContext = NULL) {
		$reqContext = $forceContext ? $forceContext : GeneralUtility::_GP('context');
		$this->context = ($reqContext == 'tceform') ? 'tceform' : 'wizard';
		$this->ajax = (GeneralUtility::_GP('ajax') == '1');
	}

	protected function initView() {
		$this->view = GeneralUtility::makeInstance('tx_imagemapwizard_view_' . $this->context);
		$this->view->init($this->context);
	}


	/**
	 * Generate the Form since this is directly called we have to repeat some initial steps
	 *
	 * @param	Object		PA
	 * @param	Object		fobj
	 * @return	String		HTMLCode with form-field
	 */
	public function renderForm($PA, \TYPO3\CMS\Backend\Form\FormEngine $fobj) {
		$GLOBALS['BE_USER']->setAndSaveSessionData('imagemap_wizard.value', NULL);
		$this->params['table'] = $PA['table'];
		if ($GLOBALS['TCA'][$PA['table']]['columns'][$PA['field']]['config']['type'] == 'flex') {
			$parts = array_slice(explode('][', $PA['itemFormElName']), 3);
			$field = substr(implode('/', $parts), 0, -1);
			$this->params['field'] = sprintf('%s:%d:%s:%s', $PA['table'], $PA['row']['uid'], $PA['field'], $field);
		} else {
			$this->params['field'] = $PA['field'];
		}

		$this->params['uid'] = $PA['row']['uid'];
		$this->params['pObj'] = $PA['pObj'];
		$this->params['fieldConf'] = $PA['fieldConf'];
		$this->params['itemFormElName'] = $PA['itemFormElName'];

		$this->initContext('tceform');
		$this->initView();
		return $this->triggerAction();
	}

	/**
	 * Wrapper to instaciate the dataObject
	 *
	 * @param $table
	 * @param $field
	 * @param $uid
	 * @param $value
	 * @return tx_imagemapwizard_model_dataObject
	 */
	protected function makeDataObj($table, $field, $uid, $value) {
		if (version_compare(TYPO3_version, '4.3.0', '<')) {
			$dataClass = GeneralUtility::makeInstanceClassName('tx_imagemapwizard_model_dataObject');
			$data = new $dataClass($table, $field, $uid, $value);
		} else {
			$data = GeneralUtility::makeInstance('tx_imagemapwizard_model_dataObject', $table, $field, $uid, $value);
		}
		return $data;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/imagemap_wizard/classes/controller/class.tx_imagemapwizard_controller_wizard.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/imagemap_wizard/classes/controller/class.tx_imagemapwizard_controller_wizard.php']);
}

?>