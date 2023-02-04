<?php $this->extends('base') ?>

<?php $this->section('css') ?>
<link rel="stylesheet" src="style1.css" />
<link rel="stylesheet" src="style2.css" />
<?php $this->endSection() ?>

<?php $this->section('title', 'Hello world!') ?>

<?php $this->section('header') ?>
  <!-- PARENT(<?php echo $this->getCurrentSection() ?>) -->
  <nav>This is the navigation.</nav>
<?php $this->endSection() ?>

<?php $this->section('content') ?>
  <article>
    <p>This is the content</p>
    <?php $this->include('included') ?>
    <?php echo $content ?>
  </article>
<?php $this->endSection() ?>
