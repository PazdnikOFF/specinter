<?php include 'blocks/header.html' ?>
<!--/HEADER-->
<div id="mainWrapper" class="bg-cart-wrapp">
<div class="js-wrapp-sidebar wrapp-sidebar">
<!--SIDEBAR-->
<?php include 'blocks/sidebar.html' ?>
<!--/SIDEBAR-->
	<!--CONTENT-->
	<div class="wrappCont">
		<div class="b-bg-cart">
			<div class="b-container-content ">
				<div class="b-cart">
					<h1 class="b-cart__title">Корзина</h1>
					<hr>
					<div class="b-cart__txt">
						<h3>Ваш заказ:</h3>
						<p>Проверьте, пожалуйста, еще раз комлпктацию Вашего заказа. При необходимосте измените заказ.</p>
					</div>
					<table class="b-cart-table responsive">
						<tr>
							<th>Фото</th>
							<th>Товар</th>
							<th>Цена (руб.)</th>
							<th>Кол.-во</th>
							<th>Стоимость</th>
							<th>Срок доставки</th>
							<th>Удалить</th>
						</tr>
						<tr>
							<td class="b-cart-table__imgtd"><a href="#" class="b-cart-table__img"><img src="img/korzina.png" alt=""></a></td>
							<td><a href="#" class="b-cart-table__name">Краски акварельные Maestria</a></td>
							<td><div class="b-cart-table__price">250.50</div></td>
							<td>
								<div class="b-decinc">
									<form action="" method="post" id="" target="formIframe">
										<a href="javascript:void(0);" class="inc"></a>
										<input type="text" value="1" name="amount" class="amount">
										<a href="javascript:void(0);" class="dec"></a>
									</form>
								</div>
							</td>
							<td><div class="b-cart-table__price-summ">2 500.50</div></td>
							<td><a href="javascript:void(0);" class="b-cart-table__date data-tooltip" data-title="Вы можете ускорить срок доставки заменив товар на подобный" onclick="$('#modal-replacement').arcticmodal();">18.11.2014</a></td>
							<td><a href="javascript:void(0);" class="b-cart-table__del i-red i-remove js-remove">Удалить</a></td>
						</tr>
						<tr>
							<td class="b-cart-table__imgtd"><a href="#" class="b-cart-table__img"><img src="img/korzina.png" alt=""></a></td>
							<td><a href="#" class="b-cart-table__name">Краски акварельные Maestria</a>
							<p class="b-cart-table__col">Цвет: <b class="val">Желтая светлая</b></p>
							</td>
							<td><div class="b-cart-table__price">250</div></td>
							<td>
								<div class="b-decinc">
									<form action="" method="post" id="" target="formIframe">
										<a href="javascript:void(0);" class="inc"></a>
										<input type="text" value="1" name="amount" class="amount">
										<a href="javascript:void(0);" class="dec"></a>
									</form>
								</div>
							</td>
							<td><div class="b-cart-table__price-summ">2 500</div></td>
							<td><a href="javascript:void(0);" class="b-cart-table__date data-tooltip" data-title="Вы можете ускорить срок доставки заменив товар на подобный">21.11.2014</a></td>
							<td><a href="javascript:void(0);" class="b-cart-table__del i-red i-remove js-remove">Удалить</a></td>
						</tr>
						<tr class="b-cart-table__result">
							<td></td>
							<td colspan="4">
								<div class="result-date">
									Все вместе сможем привезти <span class="val">21.11.2014</span>
								</div>
							</td>
							<td colspan="2" class="b-cart-table__result-summ">
								Итого: <span class="val">24 500</span> р
							</td>
						</tr>
					</table>
					
				</div>
			</div>
		</div>
		<div class="b-container-content">
			<form action="" method="post" id="form-order" class="form-order" target="formIframe">
				<div class="form-order__info-client">
					<h3 class="font1">Информация о вас</h3>
					<p>Пожалуйста, представьтесь и укажите удобный для вас способ связи</p>
					<hr>
					<h4 class="font1">Расскажите о себе</h4>
					<div class="field">
						<input type="text" name="name" placeholder="Вас зовут?">
						<span class="hint">Ведь нам же нужно знать, как к Вам обращаться</span>
					</div>
					<div class="field">
						<input type="text" name="patronymic" placeholder="Отчество">
					</div>
					<div class="field">
						<input type="text" name="surname" placeholder="Фамилия">
					</div>
					<div class="field">
						<input type="text" name="phone" placeholder="Ваш контактный телефон">
						<span class="hint">Для согласования даты и времени доставки</span>
					</div>
					<div class="field">
						<input type="text" name="phone" placeholder="Запасной телефон" class="js-dop-phone">
						<span class="i-green i-pluse dop-phone js-dop-phone-show"><a href="javascript:void(0);">Запасной телефон</a></span>
						<label for="check-call"><input type="checkbox" id="check-call"> <span>Можно не звонить</span></label>
					</div>
					<div class="field">
						<input type="text" name="email" placeholder="Ваша электропочта">
						<span class="hint">Чтобы Вы знали, на какой стадии находится Ваш заказ и смогли получить СКИДКУ в дальнейшем</span>
					</div>
				</div>
				<hr>
				<div class="form-order__delivery">
					<h4 class="font1">Куда же привезти товар?</h4>
					<div class="field">
						<label for="check-pickup"><input type="checkbox" id="check-pickup"> <span>Заберу товар из магазина самостоятельно лучше любой службы доставки!</span></label>
					</div>
					<div class="field field_pickup-adress">
					<p>По какому адресу удобнее забрать?</p>
						<label for="check-pickup-1"><input type="checkbox" id="check-pickup-1" class="check-min"> <span>ул.Первомайская, 33.</span></label>
						<label for="check-pickup-2"><input type="checkbox" id="check-pickup-2" class="check-min"> <span>ул.Первомайская, 33.</span></label>
					</div>
					<div class="field-row field-row_inline">
						<div class="field field_select">
							<select name="" id="">
								<option value="0">Выберите Ваш город</option>
								<option value="1">Екатеринбург</option>
								<option value="2">Нижний Тагил</option>
							</select>
						</div>
						<div class="field field_mid"><input type="text" name="postindex" placeholder="Введите Ваш индекс"></div>
						<div class="field">
							<span class="pickup-price">Стоимость доставки: <span class="pickup-price__val">800</span>  р</span>
						</div>
					</div>
					<div class="field-row field-row_adress field-row_just">
						<div class="field field_long">
							<input type="text" name="street" placeholder="Улица">
						</div>
						<div class="field field_small">
							<input type="text" name="house" placeholder="Дом">
						</div>
						<div class="field field_small">
							<input type="text" name="porch" placeholder="Подъезд">
						</div>
						<div class="field field_small">
							<input type="text" name="floor" placeholder="Этаж">
						</div>
						<div class="field field_small">
							<input type="text" name="apartment" placeholder="Квартира">
						</div>
					</div>
					<div class="field-row">
						<div class="field field_select">
							<select name="" id="">
								<option value="0">Лифт</option>
								<option value="1">Пассажирский</option>
								<option value="2">Грузовой</option>
								<option value="3">Нет лифта</option>
							</select>
						</div>
						<div class="field">
							<label for="check-well"><input type="checkbox" id="check-well"> <span>Лифт работает</span></label>
						</div>
					</div>
					
						<div class="field">
							<input type="text" name="whence" placeholder="Как Вы о нас узнали?">
						</div>
					
					<div class="field">
						<textarea name="comment" id="" cols="30" rows="10" placeholder="Хотите сообщить что-нибудь еще?"></textarea>
					</div>
					<div class="field">
						<div class="form-order__result-price">
							ИТОГО с учетом доставки: <span class="val">25 300 р</span>
						</div>
					</div>
				</div>
				<hr>
				<h4 class="font1">Способ оплаты:</h4>
				<p>Выберите удобный для Вас способ оплаты</p>
					<div class="field field_select">
						<select name="" id="">
							<option value="0">При получении</option>
							<option value="1">способ 2</option>
							<option value="2">способ 3</option>
							<option value="3">способ4</option>
						</select>
					</div>
				<div class="payment-method js-payment-method">
					<div class="invisible">
						<input type="radio" value="Наличными" name="radiogroup-payment" id="radio-pay-1" class="required">
						<input type="radio" value="Пластиковой картой" name="radiogroup-payment" id="radio-pay-2">
						<input type="radio" value="Яндекс.деньги" name="radiogroup-payment" id="radio-pay-3">
						<input type="radio" value="Перевод на р/с" name="radiogroup-payment" id="radio-pay-4">
					</div>
					<div class="field-row">
						<div class="field"><label for="radio-pay-1" class="i-gray i-many-1 js-payment-method__item">Наличными</label></div>
						<div class="field"><label for="radio-pay-2" class="i-gray i-many-2 js-payment-method__item">Пластиковой картой</label></div>
					</div>
					<div class="field-row">
						<div class="field"><label for="radio-pay-3" class="i-gray i-many-3 js-payment-method__item">Яндекс.деньги</label></div>
						<div class="field"><label for="radio-pay-4" class="i-gray i-many-4 js-payment-method__item" onclick="$('#modal-current-account').arcticmodal();">Переводом на р/с</label></div>
					</div>
				</div>
				<div class="field">
					<input type="submit" class="btn btn_red btn_big btn_in" value="Купить">
				</div>
			</form>
		</div>
	</div>
	<!--/CONTENT-->

</div>
<div id="footWrap"></div>
</div>

<!--FOOTER-->
<?php include 'blocks/footer.html' ?>