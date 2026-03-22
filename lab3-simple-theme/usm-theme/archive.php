<?php get_header(); ?>

<h1>Архив записей</h1>

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
  <article class="post-card">
    <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
    <div><?php the_excerpt(); ?></div>
  </article>
<?php endwhile; else : ?>
  <p>В архиве пока нет записей.</p>
<?php endif; ?>

<?php get_sidebar(); ?>
<?php get_footer(); ?>
