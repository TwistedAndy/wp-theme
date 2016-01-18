<?php if (have_comments()) { ?>

<div id="comments" class="content">

	<h3>Комментарии</h3>
	
	<?php wp_list_comments(array('callback' => 'tw_comment', 'style' => 'div', 'format' => 'xhtml')); ?>

	<?php echo tw_navigation(array('type' => 'comments')); ?>
	
</div>

<?php } ?>


<div class="content">

<?php if (comments_open()) {

	comment_form(array(
		'label_submit' => 'Отправить',
		'logged_in_as' => '<p class="logged-in-as">' . sprintf('Вы вошли как <a class="login" href="%1$s">%2$s</a> <a class="logout" href="%3$s">[выйти]</a>', admin_url('profile.php'), $user_identity, wp_logout_url(apply_filters('the_permalink', get_permalink(get_the_ID())))) . '</p>',
		'title_reply' => 'Добавить комментарий',
		'comment_notes_before' => '',
		'comment_notes_after' => '',
		'comment_field' => '<textarea id="comment" name="comment" cols="45" rows="8" placeholder="Ваше сообщение..."></textarea>',
		'fields' => array(
			'author' => '<input name="author" type="text" value="" placeholder="Ваше имя..." />',
			'email' => '<input name="email" type="text" value="" placeholder="Ваше email..." />',
			'url' => ''
		)
	));

} else { ?>

	<p class="nocomments">Комментирование данной записи отключено</p>

<?php } ?>

</div>
