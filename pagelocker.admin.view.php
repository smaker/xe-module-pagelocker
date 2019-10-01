<?php
/**
 * @class pagelockerAdminView
 * @author 퍼니XE (contact@funnyxe.com)
 * @brief pagelocker 모듈의 admin view class
 **/

class pagelockerAdminView extends pagelocker
{
	public function init()
	{
		$this->setTemplatePath($this->module_path . 'tpl');
	}

	/** 
	 * Pagelocker 설정
	 */
	public function dispPagelockerAdminSetting()
	{
		$oPagelockerModel = getModel('pagelocker');
		$config = $oPagelockerModel->getModuleConfig();

		$oMemberModel = getModel('member');
		$group_list = $oMemberModel->getGroups(0);

		Context::set('config', $config);
		Context::set('group_list', $group_list);

		$oSecurity = new Security();
		$oSecurity->encodeHTML('group_list.');

		$this->setTemplateFile('setting');
	}
	
	public function dispPagelockerAdminPageAdditionSetup()
	{
		// call by reference content from other modules to come take a year in advance for putting the variable declaration
		$content = '';

		$oEditorView = getView('editor');
		$oEditorView->triggerDispEditorAdditionSetup($content);
		Context::set('setup_content', $content);
		// Set a template file
		$this->setTemplateFile('addition_setup');

		$security = new Security();
		$security->encodeHTML('module_info.');
	}
	/**
	 * 인증 내역
	 */
	public function dispPagelockerAdminPageAuthorizeLog()
	{
		$args = new stdClass;
		$args->sort_index = 'auth_srl';
		$args->order_type = 'desc';
		$args->page = Context::get('page');
		$output = executeQueryArray('pagelocker.getPageAuthorizeLog', $args);

		Context::set('log_list', $output->data);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);

		$this->setTemplateFile('page_authorize_log');
	}
}