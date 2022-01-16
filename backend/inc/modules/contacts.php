<?php

class contacts
{

	public function __construct()
	{
		global $control;
		$this->item();
	}


	private function item()
	{
		global $control;

		$sign = md5($control->template . $control->module_url . $control->urlparams);
		phpFastCache::$storage = "auto";
		//$content = phpFastCache::get($sign);
        
		if ($content == null) {
			$page = all::c_data_all($control->cid,'contacts');
		//	print_r($page);
			$this->html['text'] = sprintt($page, 'templates/' . $control->template . '/' . $control->template . '.html');
		} else {
			$this->html['text'] = $content;
		}
	}
}

?>