<?php include 'blocks/header.html' ?>
<!--/HEADER-->
<div id="mainWrapper" class="bg-cartprod-wrapp">
<div class="js-wrapp-sidebar wrapp-sidebar">
<!--SIDEBAR-->
<?php include 'blocks/sidebar.html' ?>
<!--/SIDEBAR-->
	<!--CONTENT-->
	<div class="wrappCont">
		<div class="b-container-content">
			<!-- КАРТОЧКА ТОВАРА -->
			<div class="b-cart-prod">
				<h2 class="b-cart-prod__title">Краски акварельные "Maestria"</h2>
				<div class="b-cart-prod__cont">
					<div class="b-cart-prod__info b-cart-prod__info_lot g-right">
						<div class="info-title">
							<span class="info-title__txt">Пожалуйста, выберите параметры товара</span>
						</div>
						<div class="section section_mid">
							
							
								<div class="field"><a href="#anchor-table-prod" class="btn btn_red btn_small anchor">Купить (18)</a></div>
								<div class="field"><a href="#anchor-table-prod" class="btn btn_green btn_small anchor">Отложить</a></div>
								
							
						</div>
						<div class="section section_bottom">
							<div class="b-delivery">
								
								<div class="dop">
									<span class="i-red i-auto2 warm data-tooltip" data-title="Товар требует перевозку в тепле в холодное время года">Теплая доставка</span>
								</div>
							</div>
						</div>
					</div>
					<div class="b-cart-prod__foto">
						
						<div class="prod__foto prod__foto_big">
							<a href="#anchor-master" class="foto__name anchor" title="Мастер-класс!">Мастер-класс!</a>
							<img src="img/prodfoto_big.png" alt="">
						</div>
					</div>
					
					<div class="b-cart-prod__link">
						<ul class="">
							<li><a href="#" class="i-gray i-auto"><span>Бесплатная доставка</span></a></li>
							<li><a href="#" class="i-gray i-portmone"><span>Способ оплаты</span></a></li>
							<li><a href="#" class="i-gray i-info"><span>О производителе</span></a></li>
							<li><a href="javascript:void(0);" class="i-gray i-tag" onclick="$('#modal-actions').arcticmodal();"><span>Акций с этим товаром: <span class="val">2</span></span></a></li>
						</ul>
					</div>
					<!-- ТАБЛИЦА С ТОВАРАМИ -->
					<a id="anchor-table-prod"></a>
					<div class="adaptive-table">
						<table class="b-table-prod">
							<tr>
								<td class="b-table-prod__td-img">
									<div class="b-table-prod__img">
										<img src="img/kraski.png" alt="">
									</div>
								</td>
								<td>
									<table>
										<tr>
										<td>
											<div class="b-table-prod__color">
												Цвет: <span class="val">Английская красная</span>
											</div>
										</td>
										<td>
											<span class="status status_in-stock">Товар в наличии</span>
										</td>
										<td rowspan="2">
											<div class=""><a href="#" class="btn btn_red btn_small" onclick="$('#modal-add-to-cart').arcticmodal();">Купить</a></div>
											<div class=""><a href="#" class="btn btn_green btn_small ">Отложить</a></div>
										</td>
								<td rowspan="2">
									<div class="b-delivery">
									<div class="b-delivery__date">Доставка <span class="date-val">14.08.2014</span></div>
									<div class="pickup-adress">
										<p class="pickup-adress__title">Самовывоз по адресу:</p>
										<div>
											<div class="pickup-adress__item pickup-adress__item_ok"><span>ул.Первомайская, 33</span>
											<div class="pickup-adress__tooltip">
												<div class="working-time">
													<table class="responsive">
														<tr><th colspan="7">Время работы магазина:</th></tr>
														<tr>
															<td>Пн</td>
															<td>Вт</td>
															<td>Ср</td>
															<td>Чт</td>
															<td>Пт</td>
															<td>Сб</td>
															<td>Вс</td>
														</tr>
														<tr>
															<td colspan="5">С 10.00 до 20.00</td>
															<td><span class="font-min">С 10.00 - 18.00</span></td>
															<td><span class="font-min">С 10.00 - 17.00</span></td>
														</tr>
														
													</table>
												</div>
											</div>
											</div>
										</div>
									<div>
												<div class="pickup-adress__item pickup-adress__item_no"><span>ул.Первомайская, 33</span></div>
										</div>
									</div>
								</div>
								</td>
							</tr>
										<tr>
								<td>
									<div class="price i-red"><span class="price__summ">1 250.<sup class="price__penny">20</sup></span> Р</div>
								</td>
								<td>
									<div class="b-decinc">
											<form action="">
												<a href="javascript:void(0);" class="inc"></a>
												<input type="text" value="1" name="amount" class="amount">
												<a href="javascript:void(0);" class="dec"></a>
											</form>
									</div>
								</td>
									</tr>
									</table>
								</td>
								
							</tr>
							<tr>
								<td class="b-table-prod__td-img">
									<div class="b-table-prod__img">
										<img src="img/kraski.png" alt="">
									</div>
								</td>
								<td>
									<table>
										<tr>
										<td>
											<div class="b-table-prod__color">
												Цвет: <span class="val">Английская красная</span>
											</div>
										</td>
										<td>
											<span class="status status_under-the-order">Под заказ</span>
										</td>
										<td rowspan="2">
											<div class=""><a href="javascript:void(0);" class="btn btn_gray btn_small disabled ">Купить</a></div>
											<div class=""><a href="#" class="btn btn_green btn_small ">Отложить</a></div>
										</td>
								<td rowspan="2">
									<div class="b-delivery">
									<div class="b-delivery__date">Доставка <span class="date-val">14.08.2014</span></div>
									<div class="pickup-adress">
										<p class="pickup-adress__title">Самовывоз по адресу:</p>
										<div>
											<div class="pickup-adress__item pickup-adress__item_no"><span>ул.Первомайская, 33</span>
											
											</div>
										</div>
									<div>
												<div class="pickup-adress__item pickup-adress__item_no"><span>ул.Первомайская, 33</span></div>
										</div>
									</div>
								</div>
								</td>
							</tr>
										<tr>
								<td>
									<div class="price i-red"><span class="price__summ">1 250.<sup class="price__penny">20</sup></span> Р</div>
								</td>
								<td>
									<div class="b-decinc">
											<form action="">
												<a href="javascript:void(0);" class="inc"></a>
												<input type="text" value="1" name="amount" class="amount">
												<a href="javascript:void(0);" class="dec"></a>
											</form>
									</div>
								</td>
									</tr>
									</table>
								</td>
								
							</tr>
						</table>
					</div>
					<!-- /ТАБЛИЦА С ТОВАРАМИ -->
				</div>
			</div>
			<!-- /КАРТОЧКА ТОВАРА -->
			<!-- С ЭТИМ ТОВАРОМ ПОКУПАЮТ -->
			<div class="b-carousel b-carousel_similar">
				<div class="b-carousel__title-box">
					<h2 class="b-carousel__title">С ЭТИМ ТОВАРОМ ОБЫЧНО ПОКУПАЮТ</h2>
					<ul>
						<li><a href="#">Все товары</a> &nbsp;(180) &nbsp; <i class="i-green i-arrow"> </i></li>
					</ul>
				</div>
				<div class="b-carousel__cont js-scroll-pane">
					<ul class="carousel-list">
						<li class="carousel-item">
							<div class="carousel-item__container">
								<a href="javascript:void(0)"></a>
								<div class="carousel-item__top">
									<div class="carousel-item__title">
										<div class="category">Краски акварельные</div>
										<div class="name">MAESTRIA</div>
									</div>
								</div>
								<div class="carousel-item__center">
									<img src="img/carousel-item1.jpg" alt="">
									<div class="carousel-item__button">
										<div class="field"><a href="javascript:void(0);" class="btn btn_white btn_small">Купить</a></div>
										<div class="field"><a href="javascript:void(0);" class="btn btn_white btn_small">Отложить</a></div>
									</div>
								</div>
								<div class="carousel-item__bottom">
									<div class="price"><span class="price__summ">1 250.<sup class="price__penny">20</sup></span> Р</div>
								</div>
							</div>
						</li>
						<li class="carousel-item">
							<div class="carousel-item__container">
								<a href="javascript:void(0)"></a>
								<div class="carousel-item__top">
									<div class="carousel-item__title">
										<div class="category">Краски акварельные</div>
										<div class="name">MAESTRIA</div>
									</div>
								</div>
								<div class="carousel-item__center">
									<img src="img/carousel-item1.jpg" alt="">
									<div class="carousel-item__button">
										<div class="field"><a href="javascript:void(0);" class="btn btn_white btn_small">Купить</a></div>
										<div class="field"><a href="javascript:void(0);" class="btn btn_white btn_small">Отложить</a></div>
									</div>
								</div>
								<div class="carousel-item__bottom">
									<div class="price"><span class="price__summ">1 250.<sup class="price__penny">20</sup></span> Р</div>
								</div>
							</div>
						</li>
						<li class="carousel-item">
							<div class="carousel-item__container">
								<a href="javascript:void(0)"></a>
								<div class="carousel-item__top">
									<div class="carousel-item__title">
										<div class="category">Краски акварельные</div>
										<div class="name">MAESTRIA</div>
									</div>
								</div>
								<div class="carousel-item__center">
									<img src="img/carousel-item1.jpg" alt="">
									<div class="carousel-item__button">
										<div class="field"><a href="javascript:void(0);" class="btn btn_white btn_small">Купить</a></div>
										<div class="field"><a href="javascript:void(0);" class="btn btn_white btn_small">Отложить</a></div>
									</div>
								</div>
								<div class="carousel-item__bottom">
									<div class="price"><span class="price__summ">1 250.<sup class="price__penny">20</sup></span> Р</div>
								</div>
							</div>
						</li>
						<li class="carousel-item">
							<div class="carousel-item__container">
								<a href="javascript:void(0)"></a>
								<div class="carousel-item__top">
									<div class="carousel-item__title">
										<div class="category">Краски акварельные</div>
										<div class="name">MAESTRIA</div>
									</div>
								</div>
								<div class="carousel-item__center">
									<img src="img/carousel-item1.jpg" alt="">
									<div class="carousel-item__button">
										<div class="field"><a href="javascript:void(0);" class="btn btn_white btn_small">Купить</a></div>
										<div class="field"><a href="javascript:void(0);" class="btn btn_white btn_small">Отложить</a></div>
									</div>
								</div>
								<div class="carousel-item__bottom">
									<div class="price"><span class="price__summ">1 250.<sup class="price__penny">20</sup></span> Р</div>
								</div>
							</div>
						</li>
						<li class="carousel-item">
							<div class="carousel-item__container">
								<a href="javascript:void(0)"></a>
								<div class="carousel-item__top">
									<div class="carousel-item__title">
										<div class="category">Краски акварельные</div>
										<div class="name">MAESTRIA</div>
									</div>
								</div>
								<div class="carousel-item__center">
									<img src="img/carousel-item1.jpg" alt="">
									<div class="carousel-item__button">
										<div class="field"><a href="javascript:void(0);" class="btn btn_white btn_small">Купить</a></div>
										<div class="field"><a href="javascript:void(0);" class="btn btn_white btn_small">Отложить</a></div>
									</div>
								</div>
								<div class="carousel-item__bottom">
									<div class="price"><span class="price__summ">1 250.<sup class="price__penny">20</sup></span> Р</div>
								</div>
							</div>
						</li>
						<li class="carousel-item">
							<div class="carousel-item__container">
								<a href="javascript:void(0)"></a>
								<div class="carousel-item__top">
									<div class="carousel-item__title">
										<div class="category">Краски акварельные</div>
										<div class="name">MAESTRIA</div>
									</div>
								</div>
								<div class="carousel-item__center">
									<img src="img/carousel-item1.jpg" alt="">
									<div class="carousel-item__button">
										<div class="field"><a href="javascript:void(0);" class="btn btn_white btn_small">Купить</a></div>
										<div class="field"><a href="javascript:void(0);" class="btn btn_white btn_small">Отложить</a></div>
									</div>
								</div>
								<div class="carousel-item__bottom">
									<div class="price"><span class="price__summ">1 250.<sup class="price__penny">20</sup></span> Р</div>
								</div>
							</div>
						</li>
					</ul>
				</div>
				<div class="b-carousel__count js-carousel-count">
					<span class="js-count"></span> из <span class="js-summItem"></span>
				</div>
			</div>
			<!-- /С ЭТИМ ТОВАРОМ ПОКУПАЮТ -->
			<!-- ОПИСАНИЕ ТОВАРА -->
			<div class="b-txt">
				<h3 class="b-txt__title">Описание товара</h3>
				<p>С другой стороны дальнейшее развитие различных форм деятельности требуют от нас анализа соответствующий условий активизации. Не следует, однако забывать, что начало повседневной работы по формированию позиции требуют определения и уточнения существенных финансовых и административных условий. Задача организации, в особенности же дальнейшее развитие различных форм деятельности позволяет оценить значение новых предложений. Задача организации, в особенности же сложившаяся структура организации требуют от нас анализа соответствующий условий активизации. Не следует, однако забывать, что дальнейшее развитие различных форм деятельности позволяет выполнять важные задания по разработке новых предложений. Равным образом реализация намеченных плановых заданий обеспечивает широкому кругу (специалистов) участие в формировании дальнейших направлений развития.</p>
		<p>Повседневная практика показывает, что консультация с широким активом позволяет выполнять важные задания по разработке соответствующий условий активизации. Равным образом постоянный количественный рост и сфера нашей активности способствует подготовки и реализации существенных финансовых и административных условий. Равным образом укрепление и развитие структуры влечет за собой процесс внедрения и модернизации форм развития. Товарищи! начало повседневной работы по формированию позиции позволяет оценить значение существенных финансовых и административных условий.</p>
			</div>
			<!-- /ОПИСАНИЕ ТОВАРА -->
			<!-- МАСТЕР КЛАССЫ -->
			<a id="anchor-master"></a>
			<div class="b-masterclass">
				<div class="b-title-box">
						<h3 class="b-title-box__title">Мастер классы</h3>
						<ul>
							<li><a href="#">Все мастер-классы с этим товаром</a> &nbsp;(180) &nbsp; <i class="i-green i-arrow"> </i></li>
						</ul>
				</div>
				<ul class="b-masterclass__list">
					<li class="b-masterclass__item">
						<a href="#">
							<img src="img/bg-master-1.jpg" alt="">
							<span class="b-masterclass__name"><span>Создание мягкой игрушки из шерсти</span></span>
						</a>
					</li>
					<li class="b-masterclass__item">
						<a href="#">
							<img src="img/bg-master-2.jpg" alt="">
							<span class="b-masterclass__name"><span>Подарки на новый год своими руками</span></span>
						</a>
					</li>
					<li class="b-masterclass__item">
						<a href="#">
							<img src="img/bg-master-3.jpg" alt="">
							<span class="b-masterclass__name"><span>Винтажное украшение</span></span>
						</a>
					</li>
					<li class="b-masterclass__item">
						<a href="#">
							<img src="img/bg-master-4.jpg" alt="">
							<span class="b-masterclass__name"><span>Каллиграфия</span></span>
						</a>
					</li>
				</ul>
			</div>
			<!-- /МАСТЕР КЛАССЫ -->
			<!-- ОТЗЫВЫ О ТОВАРЕ -->
			<div class="b-review">
				<div class="b-title-box">
						<h3 class="b-title-box__title">Отзывы о товаре:</h3>
				</div>
				<div class="b-tabs">
					<ul class="b-tabs__nav js-tab-nav">
						<li class="on"><a href="javascript:void(0);">Комментарии Вконтакте</a></li>
						<li><a href="javascript:void(0);">Комментарии Facebook</a></li>
					</ul>
					<div class="b-tabs__cont">
						<div class="b-tabs__item js-tab-cont on"><img src="img/examplecomment.jpg" alt=""></div>
						<div class="b-tabs__item js-tab-cont">2</div>
					</div>
				</div>
			</div>
			<!-- /ОТЗЫВЫ О ТОВАРЕ -->
		</div>
	</div>
	<!--/CONTENT-->
</div>
<div id="footWrap"></div>
</div>

<!--FOOTER-->
<?php include 'blocks/footer.html' ?>