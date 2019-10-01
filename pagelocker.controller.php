<?php

class pagelockerController extends pagelocker
{
	public $pagelockerConfig;

	/**
	 * constructor
	 * @return void
	 */
	public function __construct()
	{
		// pagelockerModel 객체 생성
		$oPagelockerModel = getModel('pagelocker');

		// pagelocker 설정을 쉽게 참조할 수 있도록 멤버 변수에 선언
		$this->pagelockerConfig = $oPagelockerModel->getModuleConfig();
	}

	/**
	 * 페이지 잠금 해제
	 * @return void
	 */
	public function procPagelockerPageAuthorize()
	{
		// 페이지 비밀번호
		$page_password = Context::get('page_password');
		
		$oPagelockerModel = getModel('pagelocker');
		$config = $oPagelockerModel->getPagelockerPartConfig($this->module_info->module_srl);

		// 문서 번호
		$document_srl = Context::get('document_srl');

		if($config->use_each_document_lock === 'Y')
		{
			$oDocument = getModel('document')->getDocument($document_srl);
		}

		// 비밀번호 잠금이면서 비밀번호를 입력하지 않은 경우
		if(!$page_password && $config->page_lock_type == 'password')
		{
			return $this->makeObject(-1, '페이지 비밀번호를 입력해주세요.');
		}

		if(!is_array($_SESSION['XE_PAGE_AUTHORIZED']))
		{
			$_SESSION['XE_PAGE_AUTHORIZED'] = array();
		}

		if(!is_array($_SESSION['XE_PAGE_AUTHORIZED_TIME']))
		{
			$_SESSION['XE_PAGE_AUTHORIZED_TIME'] = array();
		}

		if(!is_array($_SESSION['XE_DOCUMENT_AUTHORIZED']))
		{
			$_SESSION['XE_DOCUMENT_AUTHORIZED'] = array();
		}

		if(!is_array($_SESSION['XE_DOCUMENT_AUTHORIZED_TIME']))
		{
			$_SESSION['XE_DOCUMENT_AUTHORIZED_TIME'] = array();
		}

		$bIsCorrectPassword = $config->page_password != $page_password && ($oDocument && $oDocument->getExtraEidValue('lock_password') == $page_password);

		// 비밀번호 잠금이면서 비밀번호가 틀린 경우
		if(!$bIsCorrectPassword && $config->page_lock_type == 'password')
		{
			if($config->use_each_document_lock == 'Y')
			{
				$_SESSION['XE_DOCUMENT_AUTHORIZED'][$document_srl] = false;
			}
			else
			{
				$_SESSION['XE_PAGE_AUTHORIZED'][$this->module_info->module_srl] = false;
			}

			return $this->makeObject(-1, '비밀번호가 맞지 않습니다.');
		}

		$logged_info = Context::get('logged_info');

		// 페이지 잠금 해제 방식이 포인트 차감이면서 로그인을 하지 않았을 때
		if($config->page_lock_type == 'point' && !$logged_info)
		{
			return $this->makeObject(-1, 'msg_unlock_login_required');
		}

		$expireTime = $config->page_auth_expire_time;

		switch($config->page_auth_expire_time_unit)
		{
			case 'MINUTES':
				$expireTime *= 60;
				break;
			case 'HOURS':
				$expireTime *= 60 * 60;
				break;
			case 'DAYS':
				$expireTime *= 60 * 60 * 24;
				break;
			case 'MONTHS':
				$expireTime *= 60 * 60 * 24 * 30;
				break;
		}

		$usePointUnlock = $config->page_unlock_point > 0 && $config->page_lock_type == 'point';

		$bIsPageAuthorized = (!$_SESSION['XE_PAGE_AUTHORIZED_TIME'][$this->module_info->module_srl] || time() <= $_SESSION['XE_PAGE_AUTHORIZED_TIME'][$this->module_info->module_srl] + $expireTime);
		$bIsDocumentAuthorized = (!$_SESSION['XE_DOCUMENT_AUTHORIZED_TIME'][$document_srl] || time() <= $_SESSION['XE_DOCUMENT_AUTHORIZED_TIME'][$document_srl] + $expireTime);

		if($config->page_auth_expire_time > 0 && ($bIsPageAuthorized || $bIsDocumentAuthorized))
		{
			if($usePointUnlock)
			{
				$args = new stdClass;
				$args->module_srl = $this->module_info->module_srl;
				$args->member_srl = $logged_info->member_srl;
				$output = executeQuery('pagelocker.getPageAuthorizeLogByMemberSrl', $args);
			}

			if($bIsPageAuthorized)
			{
				$args = new stdClass;
				$args->module_srl = $this->module_info->module_srl;
				$args->member_srl = $logged_info->member_srl;
				$args->ipaddress = $_SERVER['REMOTE_ADDR'];
				$args->time = $expireTime;
				$output = executeQuery('pagelocker.insertPageAuthorizeLog', $args);
				if(!$output->toBool())
				{
					return $output;
				}
			}

			$config->page_unlock_point = (int) $config->page_unlock_point;

			// 포인트로 잠금 해제를 할 경우
			if($usePointUnlock)
			{
				// pointController 객체 생성
				$oPointController = getController('point');
				// 포인트 차감
				$oPointController->setPoint($logged_info->member_srl, $config->page_unlock_point, 'subtract');
			}

			if($bIsPageAuthorized)
			{
				// 세션에 인증 여부 저장
				$_SESSION['XE_PAGE_AUTHORIZED'][$this->module_info->module_srl] = true;
				// 세션에 인증 시간 저장
				$_SESSION['XE_PAGE_AUTHORIZED_TIME'][$this->module_info->module_srl] = time();
			}

			if($bIsDocumentAuthorized)
			{
				// 세션에 인증 여부 저장
				$_SESSION['XE_DOCUMENT_AUTHORIZED'][$document_srl] = true;
				// 세션에 인증 시간 저장
				$_SESSION['XE_DOCUMENT_AUTHORIZED_TIME'][$document_srl] = time();
			}
		}

		$returnUrl = Context::get('success_return_url');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * before_module_proc 시점에서 실행되는 trigger
	 */
	public function triggerBeforeModuleProc(&$oModule)
	{
		if(Context::getResponseMethod() !== 'HTML')
		{
			return $this->makeObject();
		}

		switch($oModule->module)
		{
			case 'board':
			case 'wiki':
			case 'page':
				// pagelockerxeModel 객체 생성
				$oPagelockerModel = getModel('pagelocker');

				$config = $oPagelockerModel->getPagelockerPartConfig($oModule->module_info->module_srl);

				// 로그인 정보를 가져옵니다
				$logged_info = Context::get('logged_info');

				$supportedListAct = array(
					'dispBoardContent'
				);
				$supportedViewAct = array(
					'dispPageIndex',
					'dispWikiContent'
				);
				$supportedWriteAct = array(
					'dispBoardWrite',
					'dispWikiEditPage'
				);

				// 각 모듈의 목록 페이지인 경우
				if(in_array($oModule->act, $supportedListAct))
				{
					$document_srl = Context::get('document_srl');

					// documentModel 객체 생성
					$oDocumentModel = getModel('document');

					// before_module_proc 시점에서는 아직 document 정보를 가져오지 않았으니 별도로 가져온다
					$oDocument = $oDocumentModel->getDocument($document_srl, false, true);
				}

				break;
		}

		return $this->makeObject();
	}
	/**
	 * after_module_proc 시점에 실행되는 trigger
	 */
	public function triggerAfterModuleProc(&$oModule)
	{
		// view 이외에서는 동작하지 않도록 한다
		if(!in_array($oModule->module_info->module_type, array('view', 'mobile')))
		{
			return $this->makeObject();
		}

		// pagelocker 모듈의 model 객체 생성
		$oPagelockerModel = getModel('pagelocker');

		// 잠금 설정을 가져옵니다
		$pagelockerConfig = $oPagelockerModel->getPagelockerPartConfig($oModule->module_info->module_srl);

		// 잠금 설정을 하지 않았다면
		if($pagelockerConfig->enabled !== 'Y')
		{
			return $this->makeObject();
		}

		$isAuthorized = getView('pagelocker')->_isAuthorized();
		if($isAuthorized === true)
		{

		}
		else
		{
			$oDocument = Context::get('oDocument');

			// 게시물별 잠금을 사용중인지 확인
			$bUseEachDocumentLock = ($pagelockerConfig->use_each_document_lock && $pagelockerConfig->use_each_document_lock != 'Y');
			// 게시물 읽기 화면인지 확인
			$bIsBoardReadPage = $oModule->module_info->module == 'board' && $oDocument && $oDocument->isExists() && $oDocument->getExtraEidValue('lock_password');

			if((Context::get('act') !== 'dispMemberLoginForm' && $bUseEachDocumentLock) || ($bIsBoardReadPage))
			{
				$oModule->setTemplatePath($this->module_path . 'tpl');
				$oModule->setTemplateFile('page_authorize');
				Context::set('pagelockerConfig', $pagelockerConfig);
			}
		}	


		return $this->makeObject();
	}

	/**
	 * before_display_content 시점에서 실행되는 trigger
	 */
	public function triggerBeforeDisplayContent(&$output)
	{
		$module_info = Context::get('module_info');
		$act = Context::get('act');

		if($act === 'dispPageAdminPageAdditionSetup')
		{
			$oPagelockerModel = getModel('pagelocker');
			$pagelockerConfig = $oPagelockerModel->getPagelockerPartConfig($module_info->module_srl);

			Context::set('pagelockerConfig', $pagelockerConfig);
			$oTemplate = TemplateHandler::getInstance();
			$tpl = $oTemplate->compile($this->module_path . 'tpl', 'addition_setup');

			$setup_content = Context::get('setup_content');
			$output = str_replace($setup_content, $setup_content . $tpl, $output);
		}

		return $this->makeObject();
	}

	/** 
	 * 추가 설정 페이지 접근 시 호출되는 trigger
	 */
	public function triggerDispAdditionSetup(&$content)
	{
		// 사이트 정보를 구합니다
		$current_module_info = Context::get('current_module_info');

		$current_module_srl = Context::get('module_srl');
		$current_module_srls = Context::get('module_srls');

		if(!$current_module_srl && !$current_module_srls)
		{
			$current_module_srl = $current_module_info->module_srl;
			if(!$current_module_srl) return $this->makeObject();
		}

		// memberModel 객체 생성
		$oMemberModel = getModel('member');

		// 생성된 그룹을 가져옵니다
		$group_list = $oMemberModel->getGroups($current_module_info->site_srl);
		Context::set('group_list', $group_list);

		$oPagelockerModel = getModel('pagelocker');
		$pagelockerConfig = $oPagelockerModel->getPagelockerPartConfig($current_module_srl);

		Context::set('pagelockerConfig', $pagelockerConfig);

		$oTemplate = TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path . 'tpl', 'addition_setup');
		$content .= $tpl;

		return $this->makeObject();
	}
}