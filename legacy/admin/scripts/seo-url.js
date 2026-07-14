$(document).ready(function() {
	var cat_name = $("#cat_name").attr("id"),
		cat_key = $("#cat_key").attr("id"),
		blockUrlName = $("#first-value").attr("id"),
		field,
		needUrlConvert = typeof cat_name !== "undefined" && typeof cat_key !== "undefined",
		needBlockUrlConvert = typeof blockUrlName !== "undefined" && typeof cat_key !== "undefined";


	// Есть необходимость генерировать УРЛ у страницы
	if (needUrlConvert) {
		field = $("#cat_name");

		field.change(function() {
			$("#cat_key").val(toTranslit(field.val()));
		});
	}

	// Есть необходимость генерировать УРЛ у блока
	if (needBlockUrlConvert) {
		field = $("#"+$("#first-value").find("input").attr("id"));

		field.change(function() {
			$("#cat_key").val(toTranslit(field.val()));
		});
	}


});



function toTranslit( text ) {
	var text = text.toLowerCase();
	return text.replace( /([а-яё])|([\s_-])|([^a-z\d])/gi,
		function( all, ch, space, words, i ) {
			if ( space || words ) {
				return space ? '-' : '';
			}
			var code = ch.charCodeAt(0),
				next = text.charAt( i + 1 ),
				index = code == 1025 || code == 1105 ? 0 :
					code > 1071 ? code - 1071 : code - 1039,
				t = ['yo','a','b','v','g','d','e','zh',
					'z','i','y','k','l','m','n','o','p',
					'r','s','t','u','f','h','c','ch','sh',
					'shch','','y','','e','yu','ya'
				],
				next = next && next.toUpperCase() === next ? 1 : 0;
			return ch.toUpperCase() === ch ? next ? t[ index ].toUpperCase() :
				t[ index ].substr(0,1).toUpperCase() +
					t[ index ].substring(1) : t[ index ];
		}
	);
}