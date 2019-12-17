/**
 * This file is part of Elgrow CMS
 * Copyright 2012 Innokenty Sarayev <6319432@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

var advanced_settings = "";

jQuery(document).ready(function() {
	jQuery("form").on("submit", function(e) {
		var $t = $(this);

		if ($(this).attr("id")=="formOrder"){//Переопределил для этой формы в cart.js для проверки цены
			return false;
		}

		if ($t.attr("data-validate") != "undefined") {
			fsubmit(jQuery(this).attr('name'));
			return false;
		}
	});

});
function fsubmit(key) {
	
	var $form = jQuery("#"+key),
		_showErrorMethod = validatorConfig[key].showErrorMethod,
		result = [],
		emptyResult = true;


	$form.find("*").removeClass("error");

	jQuery(".error-desc").text("");

	if (_showErrorMethod.indexOf("#") != -1) {
		jQuery(_showErrorMethod).empty();
	}

	// Проверяем форму на стороне клиента

	result = checkForm(key);

	for (element in result) {
		if (element.length > 0) {
			emptyResult = false;
			displayErrors(result, key);
			break;
		}
	}

	// Если нет ошибок на стороне клиента - отправляем форму на сервер
	if (emptyResult) {
		post(key);
	}

}

// Отправка формы на сервер средствами Ajax
function post(fName) {
	var $form = jQuery("#"+fName),
		actionUrl = $form.attr("action"),
		sendString = $form.serialize() + '&ajax=true&formName=' + fName + advanced_settings,
		_preloaderId = validatorConfig[fName].preloaderId,
		_showErrorMethod = validatorConfig[fName].showErrorMethod,
		_lastaction = validatorConfig[fName].lastaction,
		_callback = validatorConfig[fName].callback,
		_sendId = validatorConfig[fName].sendId,
		_param = validatorConfig[fName].param,
		_capId = validatorConfig[fName].capId;
		_buttonDisabled = validatorConfig[fName].buttonDisabled;
	


	if (validatorReady) {
		jQuery.ajax({
			type: "POST",
			url: actionUrl,
			data: sendString,
            beforeSend: function(){
               if ((_buttonDisabled) && ($(_buttonDisabled).length)){
                   //отключаем кнопку
                   $(_buttonDisabled).attr("disabled", true);
               }
            },
			success: function(msg) {
				var text = "",
					capSrc = "",
					response;

				// Скрываем прелоадер
				jQuery(_preloaderId).hide();
                //включаем кнопку
                if ((_buttonDisabled) && ($(_buttonDisabled).length)) {
                    $(_buttonDisabled).attr("disabled", false);
                }

				// Отправка без ошибок
				
				if (msg.match('@s@')) {

					text = msg.replace(/@s@/g,'');

					// Действие при успешной отправке формы

					// Callback
					if (_lastaction == 'callback') {
						if (typeof(window[_callback]) == "function") {
							if (_param == '$return') {
								_param = text;
							}
							window[_callback](_param);
						}
					}

					// Показ в элементе
					if (_showErrorMethod.match("#")) {
						jQuery(_showErrorMethod).html(text);
					}

					// Скрытие формы
					if (_lastaction == 'hide') {
						jQuery("#"+fName).hide();
					}

					// Скрытие кнопки отправки
					if (_lastaction == 'hideSend') {
						if (_sendId != "") {
							jQuery(_sendId).hide();
						}
					}

					// Переход на специальную страницу
					if (_lastaction.indexOf("sp") > - 1) {
						window.location.href = '/' + _lastaction + '/';
					}

					// Ставим флаг отправки в false и задержку в 2 секунды на возможность отправки
					validatorReady = false;
					setTimeout(readyFalse, 2000);
				}
				// Ошибки с сервера
				else {

					response = jQuery.parseJSON(msg);
					// Заменяем капчу
					capSrc = response.captcha;
					capSrc = "/libs/imgcode/"+capSrc+".jpg";
					jQuery(_capId).attr("src", capSrc);
					
					// Принимаем ошибки
					text = response.errors;
					
					// Показываем в элементе
					if (_showErrorMethod.indexOf("#") != -1) {
						text = "<p>"+text+"</p>";
						jQuery(text).appendTo(_showErrorMethod);
					}
				}
			}
		});

		// Показываем прелоадер
		jQuery(_preloaderId).show();
	}

	else {
		if (_showErrorMethod.indexOf("#")!= -1) {
			jQuery("<p>Слишком частое отправление запроса, подождите, пожалуйста</p>").appendTo(_showErrorMethod);
		}
	}

}

function readyFalse() {
	validatorReady = true;
}



function displayErrors(array, fName) {
	var _showErrorMethod = validatorConfig[fName].showErrorMethod,
		_highlight = validatorConfig[fName].highlight;

	// Подсветка полей
	if (_highlight) {
		highlight(array, fName);
	}
	else {
		if (_showErrorMethod.indexOf("#") != -1) {
			showErrorInDiv(array, _showErrorMethod);
		}
	}
}

// Подсветка полей и вывод ошибок пол полями
function highlight(array, fName) {
	var $errorDiv,
		$field;

	for (field in array) {
		for (error in array[field]) {
			$errorDiv = jQuery("<div class='error-desc'></div>");
			$field = jQuery("#"+field);

			$errorDiv.css({
				color: "#c21818",
				fontSize: "0.75rem",
				marginTop: 4
			});

			$errorDiv.insertAfter($field);
			if ($errorDiv.text() == "") {
				var newError = getError(error, array[field]['caption'], error);
				$errorDiv.text(newError);
			}

			$field.addClass("error");
			break;
		}
	}
}

function showErrorInDiv(array, id) {
	var string = "";
	out:
	for (field in array) {
		for (error in array[field]) {
			var newError = getError(error, array[field]['caption'], array[field][error]);
			string += "<p>"+newError+"</p>";
			continue out;
		}
	}
	jQuery(id).append(string);
}


function getError(errorType, fieldName, value) {
	var string;
	switch (errorType) {
		case "maxlength": string = "У поля `" + fieldName + "` превышена максимальная длина"; break;
		case "minlength": string = "Поле `" + fieldName + "` - недостаточно символов" + value; break;
		case "email": string = "Поле `" + fieldName + "` заполнено неверно"; break;
		case "noempty": string = "Поле `" + fieldName + "` обязательно для заполнения"; break;
		case "nonumber": string = "Поле `" + fieldName + "` не должно содержать цифры"; break;
		case "selectinarray": string = "Выберите `" + fieldName + "` из выпадающего списка"; break;
		case "numberonly": string = "Поле `" + fieldName + "` должно содержать только цифры"; break;
		case "captcha": string = "В поле `" + fieldName + "` введен неверный код"; break;
		case "login": string = "Адрес E-mail в поле `" + fieldName + "` уже зарегистрирован"; break;
		case "sameas": string = "Пароли не совпадают"; break;
		default: string = "Ошибка";
	};
	return string;
}


// Проверка формы
function checkForm(fName) {
	var res,
		error = [],
		newArr = validatorConfig[fName].fields;

	for(elem in newArr) {

		res = checkField(elem, newArr[elem], fName);
		for (what in res) {
			if (what.length > 0) {
				error[elem] = res;
				error[elem]['caption'] = newArr[elem]['caption'];
				break;
			}
			else {
				delete error[elem];
				break;
			}
		}
	}

	return error;
}

// Проверка поля
// field - имя поля
// value - массив с ограничителями и их значениями
// formName - имя формы
function checkField(field, value, formName) {
	var error = [],
		res,
		newArr = value,
		handler;

	for (handler in newArr) {
		if (handler !== "caption") {
			functionName = "check_"+handler;

			if (typeof window[functionName] === "function") {
				res = window[functionName](field, newArr[handler], formName);

				// Если ошибка в поле
				if (!res) {
					error[handler] = newArr[handler];
				}
				else {
					delete error[handler];
				}
			}

		}
	}

	return error;
}




// Max-lenght validation
function check_maxlength(field, value, formName) {
	var string = jQuery("#"+field+"").val();

	if (string.length > parseInt(value)) {
		return false;
	}
	return true;
}


// Min-lenght validation
function check_minlength(field, value, formName) {
	var string = jQuery("#"+field+"").val();

	if (string.length < parseInt(value)) {
		return false;
	}
	return true;
}


// Captcha validation
function check_captcha(field, value, formName) {
	return true;
}

// Only numbers validation
function check_numberonly(field, value, formName) {
	var string = jQuery("#"+field+"").val(),
		reg = new RegExp("[^0-9]+", 'i'),
		result = reg.test(string);

	return !result;
}

function in_array(value, array) {
    for(var i=0; i<array.length; i++){
        if(value == array[i]) return true;
    }
    return false;
}

// number in array validation
function check_selectinarray(field, value, formName) {
	var number = jQuery("#"+field+"").val();
    var result;
    if (in_array(number, value))
        result = true;
    else
        result = false;

	return result;
}

// No numbers validation
function check_nonumber(field, value, formName) {
	var string = jQuery("#"+field+"").val(),
		reg = new RegExp("[0-9]", 'i'),
		result = reg.test(string);

	return !result;
}

// Only numbers and symbols validation
function check_numberandsimbols(field, value, formName) {
	var string = jQuery("#"+field+"").val(),
		reg = new RegExp("[^0-9-_+@\s]", 'i'),
		result = reg.test(string);

	return result;
}

// E-mail validation
function check_email(field, value, formName) {
	return true;
}

// No empty validation
function check_noempty(field, value, formName) {
	var string = jQuery("#"+field+"").val();

	if (string.length < 1) {
		return false;
	}
	if (string === value && value > 1) {
		return false;
	}

	return true;
}

// Checkbox checked validation
function check_checkbox(field, value, formName) {
	var checked = jQuery("#"+field+"").prop('checked');
	return checked;
}

// sameAs (password reply) validation
function check_sameas(field, value, formName) {
	var string = jQuery("#"+field+"").val(),
		string2 = jQuery("#"+value+"").val();

	return string === string2;
}