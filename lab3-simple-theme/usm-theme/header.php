<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php bloginfo('name'); ?> | <?php wp_title('|'); ?></title>
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<header class="site-header">
  <h1><a href="<?php echo esc_url(home_url('/')); ?>" style="color:white;text-decoration:none;"><?php bloginfo('name'); ?></a></h1>
  <p><?php bloginfo('description'); ?></p>
</header>

<div class="layout">
  <main class="content">
