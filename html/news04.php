<?php include 'blocks/header.html' ?>
<!--/HEADER-->
<div id="mainWrapper" class="bg-news-wrapp">
<div class="js-wrapp-sidebar wrapp-sidebar">
<!--SIDEBAR-->
<?php include 'blocks/sidebar.html' ?>
<!--/SIDEBAR-->
	<!--CONTENT-->
	<div class="wrappCont">
		<div class="b-container-content">
			<div class="b-title">
				<h1 class="title title_red">Полезная информация</h1>
				<hr>
			</div>
			<p>"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum."</p>
			
			<div class="news">
				<div class="news__filter">
					<form action="" method="post" id="form-news-filter" target="formIframe">
						<div class="field">
							<label for=""><input type="radio" name="radio-news"><span>Вся информация</span></label>
							<label for=""><input type="radio" name="radio-news" checked><span>Новости компании</span></label>
							<label for=""><input type="radio" name="radio-news"><span>Акции</span></label>
							<label for=""><input type="radio" name="radio-news"><span>Мастер-классы</span></label>
							<label for=""><input type="radio" name="radio-news"><span>Производители</span></label>
						</div>
					</form>
				</div>
				<ul class="news__list">
					<li class="news__item">
						<img src="img/news1.jpg" alt="" width="255" height="255">
						<a href="#">
							<span class="news__title">Создание мягкой игрушки из шерсти</span>
						</a>
					</li>
					<li class="news__item">
						<img src="img/news2.jpg" alt="" width="255" height="255">
						<a href="#">
							<span class="news__title">Подарки на новый год своими руками</span>
						</a>
					</li>
					<li class="news__item">
						<img src="img/news3.jpg" alt="" width="255" height="255">
						<a href="#">
							<span class="news__title">Винтажное украшение</span>
						</a>
					</li>
					<li class="news__item">
						<img src="img/news4.jpg" alt="" width="255" height="255">
						<a href="#">
							<span class="news__title">Каллиграфия</span>
						</a>
					</li>
					<li class="news__item">
						<img src="img/news1.jpg" alt="" width="255" height="255">
						<a href="#">
							<span class="news__title">Создание мягкой игрушки из шерсти</span>
						</a>
					</li>
					<li class="news__item">
						<img src="img/news2.jpg" alt="" width="255" height="255">
						<a href="#">
							<span class="news__title">Подарки на новый год своими руками</span>
						</a>
					</li>
					<li class="news__item">
						<img src="img/news3.jpg" alt="" width="255" height="255">
						<a href="#">
							<span class="news__title">Винтажное украшение</span>
						</a>
					</li>
					<li class="news__item">
						<img src="img/news4.jpg" alt="" width="255" height="255">
						<a href="#">
							<span class="news__title">Каллиграфия</span>
						</a>
					</li>
				</ul>
				<div class="ta-center">
					<a href="#" class="more">Показать еще</a>
				</div>
			</div>
		</div>
	</div>
	<!--/CONTENT-->

</div>
<div id="footWrap"></div>
</div>

<!--FOOTER-->
<?php include 'blocks/footer.html' ?>