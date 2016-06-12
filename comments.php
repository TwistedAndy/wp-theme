<?php
if (post_password_required()) {
	return;
}
?>

<div id="comments">

	<?php if (have_comments()) { ?>

	<div class="comments">

		<h3>Комментарии</h3>

		<?php wp_list_comments(array('callback' => 'tw_comment', 'style' => 'div', 'format' => 'xhtml')); ?>

		<?php echo tw_pagination(array('type' => 'comments')); ?>

	</div>

	<?php } ?>


	<?php

	$commenter = wp_get_current_commenter();

	tw_comment_form(array(
		'fields' => array(
			'author' => '<input placeholder="Ваше имя..." name="author" type="text" value="' . esc_attr($commenter['comment_author']) . '" size="30" required="required" />',
			'email'  => '<input placeholder="Ваш email..." name="email" type="text" value="' . esc_attr($commenter['comment_author_email']) . '" size="30" required="required" />',
			'url'    => '<input placeholder="Адрес сайта..." name="url" type="text" value="' . esc_attr($commenter['comment_author_url']) . '" size="30" />',
		),
		'comment_field' => '<textarea id="comment" name="comment" cols="45" rows="8" required="required"></textarea>',
		'label_submit'	=> 'Отправить',
		'title_reply'	=> 'Оставить комментарий',
		'title_reply_before' => '<div id="reply-title" class="comment-reply-title">',
		'title_reply_after'	 => '</div>'
	));

	?>

</div>