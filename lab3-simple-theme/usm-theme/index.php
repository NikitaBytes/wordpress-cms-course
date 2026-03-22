<?php get_header(); ?>

<h1>Последние записи</h1>

<?php
$query = new WP_Query(array(
  'posts_per_page' => 5
));

if ($query->have_posts()) :
  while ($query->have_posts()) : $query->the_post();
    ?>
    <article class="post-card">
      <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
      <p><strong>Дата:</strong> <?php the_date(); ?></p>
      <div>
        <?php the_excerpt(); ?>
      </div>
    </article>
    <?php
  endwhile;
  wp_reset_postdata();
else :
  echo '<p>Записей пока нет.</p>';
endif;
?>

<?php get_sidebar(); ?>
<?php get_footer(); ?>
