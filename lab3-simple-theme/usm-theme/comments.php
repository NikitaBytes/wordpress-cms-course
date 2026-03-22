<div class="comments-area">
  <h3>Комментарии</h3>

  <?php if (have_comments()) : ?>
    <ul>
      <?php wp_list_comments(); ?>
    </ul>
  <?php else : ?>
    <p>Комментариев пока нет.</p>
  <?php endif; ?>

  <?php comment_form(); ?>
</div>
