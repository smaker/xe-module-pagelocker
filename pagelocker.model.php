<?php
class pagelockerModel extends pagelocker
{
	public function getModuleConfig()
	{
		static $config = NULL;
		if(is_null($config))
		{
			$oModuleModel = getModel('module');
			$config = $oModuleModel->getModuleConfig('pagelocker');
			if(!isset($config))
			{
				$config = new stdClass();
			}
		}

		return $config;
	}

	public function getPagelockerPartConfig($module_srl)
	{
		if(!$module_srl)
		{
			return new stdClass();
		}

		$config = getModel('module')->getModulePartConfig('pagelocker', $module_srl);

		if(!isset($config))
		{
			$config = new stdClass();
		}

		return $config;
	}
}