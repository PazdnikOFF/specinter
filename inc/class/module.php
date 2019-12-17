<?php
class module {

	public function __construct($currentModule) {
		if (defined('ENVIRONMENT')) {
			switch (ENVIRONMENT) {
				case 'development':
					echo get_class($currentModule);
					break;
				
				default: 
					break;
				
			}
		}
	}

	
}
?>