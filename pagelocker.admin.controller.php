<?php
/**
 * @class pagelockerAdminController
 * @author 퍼니XE (contact@funnyxe.com)
 * @brief pagelocker 모듈의 admin controller class
 **/

class pagelockerAdminController extends pagelocker
{
	public $pagelockerConfig;

	/**
	 * constructor
	 */
	public function __construct()
	{
		// pagelocker 설정을 쉽게 참조할 수 있도록 멤버 변수에 선언
		$this->pagelockerConfig = getModel('pagelocker')->getModuleConfig();
	}

	/**
	 * pagelocker 설정 저장
	 */
	public function procPagelockerAdminSaveSetting()
	{
		$target_module_srl = Context::get('target_module_srl');
		$this->pagelockerConfig->enabled = Context::get('use_lock');
		$this->pagelockerConfig->page_auth_expire_time = Context::get('page_auth_expire_time');
		$this->pagelockerConfig->page_auth_expire_time_unit = Context::get('page_auth_expire_time_unit');
		$this->pagelockerConfig->page_lock_type = Context::get('page_lock_type');
		$this->pagelockerConfig->page_password = Context::get('page_password');
		$this->pagelockerConfig->use_each_document_lock = Context::get('use_each_document_lock');
		$this->pagelockerConfig->page_unlock_point = (int) Context::get('page_unlock_point');

		$oModuleController = getController('module');
		$oModuleController->insertModulePartConfig('pagelocker', $target_module_srl, $this->pagelockerConfig);

		Context::set('pagelockerConfig', $this->pagelockerConfig);

		$returnUrl = Context::get('success_return_url');
		if(!$returnUrl)
		{
			$returnUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispPagelockerAdminSetting');
		}

		$this->setRedirectUrl($returnUrl);
		$this->setMessage('success_saved');
	}
}