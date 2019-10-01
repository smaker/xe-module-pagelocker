<?php
/**
 * @class pagelocker
 * @author 퍼니XE (contact@funnyxe.com)
 * @brief pagelocker 모듈의 high class
 **/

class pagelocker extends ModuleObject
{
	private $triggers = array(
		// before_module_proc 시점의 trigger
		array('moduleObject.proc', 'pagelocker', 'controller', 'triggerBeforeModuleProc','before'),
		// after_module_proc 시점의 trigger
		array('moduleObject.proc', 'pagelocker', 'controller', 'triggerAfterModuleProc','after'),
		// before_display_content 시점의 trigger
		array('display', 'pagelocker', 'controller', 'triggerBeforeDisplayContent', 'before'),
		// 추가 설정 페이지에서 호출되는 trigger
		array('module.dispAdditionSetup', 'pagelocker', 'controller', 'triggerDispAdditionSetup', 'before')
	);

	/**
	 * 모듈 설치
	 **/
	public function moduleInstall()
	{
		return $this->makeObject();
	}

	/**
	 * @brief 업데이트가 필요한지 확인
	 **/
	function checkUpdate()
	{
		// moduleModel 객체 생성
		$oModuleModel = getModel('module');

		// 트리거가 등록되어 있는지 확인
		foreach($this->triggers as $no => $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				return true;
			}
		}

		return false;
	}

	/**
	* @brief 모듈 업데이트
	**/
	function moduleUpdate()
	{
		// moduleModel 객체 생성
		$oModuleModel = getModel('module');
		// moduleController 객체 생성
		$oModuleController = getController('module');

		// 트리거 등록
		foreach($this->triggers as $no => $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
			}
		}

		return $this->makeObject(0,'success_updated');
	}

	/**
	 * 모듈 삭제
	 */
	public function moduleUninstall()
	{
		// moduleController 객체 생성
		$oModuleController = getController('module');

		// 트리거 삭제
		foreach($this->triggers as $no => $trigger)
		{
			$oModuleController->deleteTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
		}
	}

	/**
	 * @brief 캐시 파일 재생성
	 **/
	public function recompileCache()
	{
	}

    public function makeObject($code = 0, $msg = 'success')
    {
        return class_exists('BaseObject') ? new BaseObject($code, $msg) : new Object($code, $msg);
    }
}