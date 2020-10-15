<?php $__context->include('base') ?>

<?php $__context->blockPush('title', 'Hello world!') ?>

<?php $__context->blockPush('header') ?>
  <?php echo $__context->getBlockContent($__context->getCurrentBlock()) ?>
  <nav>This is the navigation.</nav>
<?php $__context->endBlock() ?>

<?php $__context->blockPush('content') ?>
  <article>
    <p>This is the content</p>
    <?php $__context->include('included') ?>
    <?php echo $content ?>
  </article>
<?php $__context->endBlock() ?>
