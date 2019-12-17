<?php
// Простейший модуль,
// умеет выводить список объектов
// и конкретный объект

// Подходит для новостей, статей, портфолио
// и любой неструктурированной информации
class typo {

	// Конструктор класса
	// По наличию в адресной строке специального параметра
	// определяет - выводить список или объект
	public function __construct() {
		global $control;

		if ($control->oper == 'view') {
			$this->printOne($control->bid);
		} else {
			$this->printList($control->module_parent);
		}
	}

	// Выводит конкретный объект
	private function printOne($bid) {
		global $control;

		// Функция выбирает всю информацию о объекте на основании id ($bid)
		// и типа блока ($control->module_wrap)
		$page = all::b_data_all($bid, $control->module_wrap);

		// Генерация ссылки "Назад"
		$page->back = all::getUrl($control->module_parent).all::addUrl($this->page);

		// Отправка объекта $page в шаблонизатор (используется ETS)
		// Вторым параметром передается путь к шаблону html
		$this->html['text'] = sprintt($page, 'templates/'.$control->template.'/'.$control->template.'_one.html');
	}


	// Выводит список объектов (новостей, например)
	private function printList($cid) {
		global $control;

		// Создаем объект класса Listing
		// 1) параметр - тип блока объекта
		// 2) парамерт - тип выборки - в данном случае blocks
		// 3) параметр - родитель, содержащий объекты
		$list = new Listing($control->module_wrap, 'blocks',$cid);
//var_Dump($control->module_wrap);
		// Делаем выборку
		$list->getList();

		// Формируем выборку
		$list->getItem();

		// Передаем результаты выборки в объект
		$page->item = $list->item;

		// В этот же объект передаем название текущей страницы
		$page->name = $control->name;

		// И передаем полученный объект шаблонизатору
		$this->html['text'] = sprintt($page, 'templates/'.$control->template.'/'.$control->template.'.html');

	}
}
?>