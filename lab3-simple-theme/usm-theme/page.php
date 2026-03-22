<?php get_header(); ?>

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
  <article class="post-card">
    <h1><?php the_title(); ?></h1>
    <div>
      <?php the_content(); ?>
    </div>
  </article>

  <?php comments_template(); ?>

<?php endwhile; endif; ?>

<?php get_sidebar(); ?>
<?php get_footer(); ?>
