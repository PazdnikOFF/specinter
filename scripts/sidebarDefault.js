$(document).ready(function(){
	var $sidebarWrapp = $('.js-wrapp-sidebar'),
		$sidebar = $('.js-sidebar'),
		$sidebarBtn = $('.js-sidebar').find('.btn'),
		$btnToogle = $('.js-toggle-sidebar'),
		flagToggle = 1;
	var openSidebar = function() {
		$sidebarWrapp.addClass('wrapp-sidebar-on');
		$sidebarWrapp.removeClass('wrapp-sidebar-off');
		$btnToogle.removeClass('js-open');
		$btnToogle.addClass('js-close');
		
	};
	var closeSidebar = function() {
		$sidebarWrapp.removeClass('wrapp-sidebar-on');
		$sidebarWrapp.addClass('wrapp-sidebar-off');
		$btnToogle.removeClass('js-close');
		$btnToogle.addClass('js-open');
	};
	
	var loadSidebar = function(){
		if ($(window).outerWidth()>1360) {
			flagToggle = 0;
			openSidebar();
			
		} else {
			flagToggle = 1;
			closeSidebar();
		}
		
		setTimeout(function(){
			$('html').addClass('js-sidebarReady');
		}, 500);
		
		

	};
	loadSidebar();
});
(function(){
	
	
}());

	
	
