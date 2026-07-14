<?php include 'blocks/header.html' ?>
<!--/HEADER-->
<div id="mainWrapper" class="bg-service-wrapp">
<div class="js-wrapp-sidebar wrapp-sidebar">
<!--SIDEBAR-->
<?php include 'blocks/sidebar.html' ?>
<!--/SIDEBAR-->
	<!--CONTENT-->
	<div class="wrappCont">
		
			<div class="b-container-content">
				<!-- КАРТОЧКА ТОВАРА -->
			<div class="b-cart-prod">
				<h2 class="b-cart-prod__title">Багет дерево (220)</h2>
				<div class="b-cart-prod__cont">
					<div class="b-cart-prod__info b-cart-prod__info_baget g-right">
						<div class="info-title">
							<span class="info-title__txt">Пожалуйста, выберите параметры товара</span>
						</div>
						<div class="section section_mid">
							
							
								<div class="field"><a href="#anchor-table-prod" class="btn btn_red btn_small anchor">Оформить (5)</a></div>
								
								
							
						</div>
						
					</div>
					<div class="b-cart-prod__foto">
						<div class="g-right ">
							<div class="bx-pager_foto">
								<div class="prod__foto prod__foto_mini active">
									<a href="javascript:void(0);" data-slide-index="0"><span><img src="img/fotobageta.png" alt=""></span></a>
								</div>
								<div class="prod__foto prod__foto_mini">
									<a href="javascript:void(0);" data-slide-index="1"><span><img src="img/fotobageta.png" alt=""></span></a>
								</div>
								<div class="prod__foto prod__foto_mini">
									<a href="javascript:void(0);" data-slide-index="2"><span><img src="img/fotobageta.png" alt=""></span></a>
								</div>
								<div class="prod__foto prod__foto_mini">
									<a href="javascript:void(0);" data-slide-index="3"><span><img src="img/fotobageta.png" alt=""></span></a>
								</div>
								<div class="prod__foto prod__foto_mini">
									<a href="javascript:void(0);" data-slide-index="4"><span><img src="img/fotobageta.png" alt=""></span></a>
								</div>
							</div>
							<div class="forbtn">
								<span class="btn btn_mid btn_in btn_green" id="next-foto"></span>
							</div>
						</div>
						<div class="prod__foto prod__foto_big">
						
							<ul class="bxslider_foto">
								<li><img src="img/fotobigbageta.png" alt=""></li>
								<li><img src="img/fotobigbageta.png" alt=""></li>
								<li><img src="img/prodfoto_big.png" alt=""></li>
								<li><img src="img/fotobigbageta.png" alt=""></li>
								<li><img src="img/prodfoto_big.png" alt=""></li>
							</ul>
						</div>
					</div>
					

					<form action="" method="post" id="form-molding" target="formIframe">
						<!-- ТАБЛИЦА С ТОВАРАМИ -->
							<a id="anchor-table-prod"></a>
							<div class="adaptive-table">
								<table class="b-table-prod">
									<tr>
										<td class="b-table-prod__td-img">
											<div class="b-table-prod__img">
												<img src="img/fotobageta.png" alt="">
											</div>
										</td>
										<td>
											<table>
												<tr>
												<td>
													<div class="b-molding-info">
														<div class="b-molding-info__name">Багет Дерево 050</div>
														<div>
															<span class="b-molding-info__label">Цвет:</span><span class="b-molding-info__val">бежевый</span>
														</div>
														<div>
															<span class="b-molding-info__label">Ширина(см):</span><span class="b-molding-info__val">1,1</span>
														</div>
													</div>
												</td>
												<td>
													<label for="check-molding-1"><input type="checkbox" class="hidden checked-molding" id="check-molding-1"><span class="btn btn_red btn_small" ><span class="no">Выбрать</span><span class="on i-green i-ok">Выбран</span></span></label>
												</td>
												<td>
													<span class="status status_in-stock">Товар в наличии</span>
												</td>
												</tr>
											</table>
										</td>
									</tr>
									<tr>
										<td class="b-table-prod__td-img">
											<div class="b-table-prod__img">
												<img src="img/fotobageta.png" alt="">
											</div>
										</td>
										<td>
											<table>
												<tr>
												<td>
													<div class="b-molding-info">
														<div class="b-molding-info__name">Багет Дерево 050</div>
														<div>
															<span class="b-molding-info__label">Цвет:</span><span class="b-molding-info__val">бежевый</span>
														</div>
														<div>
															<span class="b-molding-info__label">Ширина(см):</span><span class="b-molding-info__val">1,1</span>
														</div>
													</div>
												</td>
												<td>
													<label for="check-molding-2"><input type="checkbox" class="hidden checked-molding" id="check-molding-2"><span class="btn btn_red btn_small" ><span class="no">Выбрать</span><span class="on i-green i-ok">Выбран</span></span></label>
												</td>
												<td>
													<span class="status status_in-stock">Товар в наличии</span>
												</td>
												</tr>
											</table>
										</td>
									</tr>
								</table>
							</div>
						<!-- /ТАБЛИЦА С ТОВАРАМИ -->
						<div class="b-molding-order">
							<h2 class="b-molding-order__title">Сделать заказ</h2>
							<div class="field">
								<input type="text" placeholder="Вас зовут?" name="name">
							</div>
							<div class="field">
								<input type="text" placeholder="Ваш контактный телефон" name="phone">
							</div>
							<div class="field">
								<input type="text" placeholder="Ваша електропочта" name="email">
							</div>
							<div class="field">
								<span class="i-green i-pluse"><a href="javascript:void(0);" onclick="$('#modal-add-parameters').arcticmodal();">Дополнительные параметры</a></span>
							</div>
							<div class="field field_btn">
								<input type="submit" value="Отправить" class="btn btn_red btn_in btn_big">
							</div>
						</div>
					</form>
				</div>
			</div>
			<!-- /КАРТОЧКА ТОВАРА -->
			</div>
		
		
	</div>
	<!--/CONTENT-->

</div>
<div id="footWrap"></div>
</div>

<!--FOOTER-->
<?php include 'blocks/footer.html' ?>