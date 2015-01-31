<form action="/index.php" method="get" class="search">
	<input type="text" placeholder="Поиск..." value="<?php echo get_search_query(); ?>" name="s" />
	<input type="submit" value="Найти" />
</form>