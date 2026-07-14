<?php include 'blocks/header.html' ?>
<!--/HEADER-->
<div id="mainWrapper" class="bg-cabinet-wrapp">
	<div class="js-wrapp-sidebar wrapp-sidebar">
		<!--SIDEBAR-->
		<?php include 'blocks/sidebar.html' ?>
		<!--/SIDEBAR-->
		<!--CONTENT-->
		<div class="wrappCont">
			<div class="b-cabinet">
				<div class="b-container-content">
					<h1 class="b-cabinet__title">Здравствуйте, Василий!</h1>
					<hr>
					<a href="#" class="cabinet-out">Выйти</a>
					<div class="b-sale">
						<div class="b-sale__title">Ваша персональная скидка:</div>
						<span class="note">Скидка в интернет-магазине 5%</span> 
						<br>
						<span class="note">Скидка в магазине</span> (доступна при самовывозе и покупке в м-нах Маэстрия) <span class="note">3%</span>
						<br>
						<a href="#">Как увеличить скидку?</a>
					</div>
					<hr>
					<form action="" method="post" id="form-cabinet" class="form-cabinet" target="formIframe">
						<div class="form-order__info-client">
							<h4 class="font1">Расскажите о себе</h4>
							<div class="js-form-cabinet-phone-user">
								<div class="field">
									<input type="text" name="name" placeholder="Вас зовут?" value="Василий">
								</div>
								<div class="field">
									<input type="text" name="phone" placeholder="Ваш контактный телефон">
								</div>
							</div>
							<div class="field">
								<span class="i-green i-pluse add-phone js-add-phone"><a href="javascript:void(0);">Еще телефон</a></span>

							</div>
							<div class="field">
								<input type="text" name="email" placeholder="Ваша электропочта">
							</div>
						</div>
						<div class="form-cabinet__psw">
							<h4 class="font1">Сменить пароль</h4>
							<div class="field">
								<input type="password" name="oldpsw" placeholder="Старый пароль">
							</div>
							<div class="field">
								<input type="password" name="newpsw" placeholder="Новый пароль">
							</div>
							<div class="field">
								<a href="javascript:void(0);" class="btn btn_in btn_green btn_mid">Сменить пароль</a>
							</div>
						</div>
						<hr>
						<div class="form-cabinet__delivery">
							<div class="form-cabinet-adress-title">
								<h4 class="font1">Адреса доставки:</h4>
								<span class="i-green i-pluse add-adress js-add-adress"><a href="javascript:void(0);">Добавить адрес</a></span>
							</div>
							<div class="form-cabinet-adress-box">
								<div class="form-cabinet-adress-box__item">
									<div class="field-row field-row_inline">
										<div class="field field_select">
											<span>Выберите Ваш город</span>
											<select name="" id="">
												<option value="1">Екатеринбург</option>
												<option value="2">Нижний Тагил</option>
											</select>
										</div>
										<div class="field field_mid">
											<span>Введите Ваш индекс<span>
											<input type="text" name="postindex" placeholder="">
										</div>
									</div>
									<div class="field-row field-row_adress field-row_just">
										<div class="field field_long">
											<span>Улица</span>
											<input type="text" name="street" placeholder="">
										</div>
										<div class="field field_small">
											<span>Дом</span>
											<input type="text" name="house" placeholder="">
										</div>
										<div class="field field_small">
											<span>Подъезд</span>
											<input type="text" name="porch" placeholder="">
										</div>
										<div class="field field_small">
											<span>Этаж</span>
											<input type="text" name="floor" placeholder="">
										</div>
										<div class="field field_small">
											<span>Квартира</span>
											<input type="text" name="apartment" placeholder="">
										</div>
									</div>
									<div class="field-row field-row_well">
										<div class="field field_select">
											<span>Лифт</span>
											<select name="" id="">
												<option value="1">Пассажирский</option>
												<option value="2">Грузовой</option>
												<option value="3">Нет лифта</option>
											</select>
										</div>
										<div class="field">
											<label for="check-well">
												<input type="checkbox" id="check-well"> <span>Лифт работает</span>
											</label>
										</div>
									</div>
									<div class="field">
										
									</div>
									<hr>
								</div>
							</div>
							<div class="distribution">
								<h4 class="font1">Рассылка:</h4>
								<div class="field-row field-row_inline">
									<div class="field field_long">
										<input type="text" name="distribution" placeholder="E-mail" value="info@elgrow.ru">
									</div>
									<div class="field">
										<a href="javascript:void(0);" class="btn btn_in btn_green btn_mid">Сохранить</a>
									</div>
									<div class="field">
										<a href="javascript:void(0);" class="txt-red" onclick="$('#modal-unsubscribe').arcticmodal();">Отписаться</a>
									</div>


								</div>
								<hr>
							</div>
							<div class="favorite-products">
								<h4 class="font1">Отложенные товары:</h4>
								<div class="tdLeft">
									<ul class="product-menu">
										<li><a href="#">Живопись</a>
										</li>
										<li><a href="#">Скульптура</a>
										</li>
										<li><a href="#">Оригами</a>
										</li>
										<li><a href="#">Декорирование поверхностей</a>
										</li>
										<li><a href="#">Изготовление кукол</a>
										</li>
										<li class="on"><a href="#">Скрапбукинг и кардмейкинг</a>
											<ul>
												<li><a href="#">Украшения</a>
												</li>
												<li><a href="#">Основы и подложки</a>
												</li>
												<li><a href="#">Инструменты</a>
												</li>
												<li><a href="#">Штампинг и эмбоссинг</a>
												</li>
												<li><a href="#">Клевые материалы</a>
												</li>
												<li><a href="#">Бумага</a>
												</li>
											</ul>
										</li>
										<li><a href="#">Изготовление кукол</a>
										</li>
									</ul>
								</div>
								<div class="content">

									<!--CATALOG-->
									<div class="b-catalog b-catalog_favorite">
										<ul class="prod-list">
											<li class="prod-list__item">
												<div class="prod-list__item-box">
													<div class="carousel-item__container">
														<a href="javascript:void(0)"></a>
														<div class="carousel-item__top">
															<div class="carousel-item__title">
																
																<div class="name">MAESTRIA</div>
															</div>
														</div>
														<div class="carousel-item__center">
															<img src="img/carousel-item1.jpg" alt="">
															<div class="carousel-item__button">
																<div class="field"><a href="javascript:void(0);" class="btn btn_white btn_small">Купить</a>
																</div>
																
															</div>
														</div>
														<div class="carousel-item__bottom">
															<div class="price"><span class="price__summ">1 250.<sup class="price__penny">20</sup></span> Р</div>
														</div>
													</div>
												</div>

											</li>
											<li class="prod-list__item">
												<div class="prod-list__item-box">
													<div class="carousel-item__container">
														<a href="javascript:void(0)"></a>
														<div class="carousel-item__top">
															<div class="carousel-item__title">
																
																<div class="name">MAESTRIA</div>
															</div>
														</div>
														<div class="carousel-item__center">
															<img src="img/carousel-item1.jpg" alt="">
															<div class="carousel-item__button">
																<div class="field"><a href="javascript:void(0);" class="btn btn_white btn_small">Купить</a>
																</div>
																
															</div>
														</div>
														<div class="carousel-item__bottom">
															<div class="price"><span class="price__summ">1 250.<sup class="price__penny">20</sup></span> Р</div>
														</div>
													</div>
												</div>

											</li>
											<li class="prod-list__item">
												<div class="prod-list__item-box">
													<div class="carousel-item__container">
														<a href="javascript:void(0)"></a>
														<div class="carousel-item__top">
															<div class="carousel-item__title">
																
																<div class="name">MAESTRIA</div>
															</div>
														</div>
														<div class="carousel-item__center">
															<img src="img/carousel-item1.jpg" alt="">
															<div class="carousel-item__button">
																<div class="field"><a href="javascript:void(0);" class="btn btn_white btn_small">Купить</a>
																</div>
																
															</div>
														</div>
														<div class="carousel-item__bottom">
															<span class="status status_under-the-order">Под заказ</span>
														</div>
													</div>
												</div>

											</li>
											<li class="prod-list__item">
												<div class="prod-list__item-box">
													<div class="carousel-item__container">
														<a href="javascript:void(0)"></a>
														<div class="carousel-item__top">
															<div class="carousel-item__title">
																
																<div class="name">MAESTRIA</div>
															</div>
														</div>
														<div class="carousel-item__center">
															<img src="img/carousel-item1.jpg" alt="">
															<div class="carousel-item__button">
																<div class="field"><a href="javascript:void(0);" class="btn btn_white btn_small">Купить</a>
																</div>
																
															</div>
														</div>
														<div class="carousel-item__bottom">
															<div class="price"><span class="price__summ">1 250.<sup class="price__penny">20</sup></span> Р</div>
														</div>
													</div>
												</div>

											</li>
										</ul>
										<div class="pagination">
											<ul>
												<li class="pagination__nav pagination__nav_left">
													<a href="#" class="i-gray i-arrow-left"></a>
												</li>
												<li><a href="#">1</a>
												</li>
												<li><a href="#">2</a>
												</li>
												<li><a href="#">3</a>
												</li>
												<li><a href="#" class="on">4</a>
												</li>
												<li><a href="#">5</a>
												</li>
												<li><a href="#">...</a>
												</li>
												<li><a href="#">160</a>
												</li>
												<li class="pagination__nav pagination__nav_right">
													<a href="#" class="i-gray i-arrow-right"></a>
												</li>
											</ul>
										</div>
									</div>
									<!--/CATALOG-->
								</div>
							</div>
					</form>
					</div>
				</div>

			</div>
			<!--/CONTENT-->

		</div>
		<div id="footWrap"></div>
	</div>

	<!--FOOTER-->
	<?php include 'blocks/footer.html' ?>