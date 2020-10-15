<html>
<head>
  <title>App Name - @heap_d5d3db1765287eef77d7927cc956f50a</title>
  <style>html,body{margin:0;padding:0}</style>
  <?php $__context->getStack('css') ?>
</head>
<body>
  <header>
    <?php $__context->blockPush('header') ?>
      <h1>@heap_d5d3db1765287eef77d7927cc956f50a</h1>
    <?php $__context->endBlock(true) ?>
  </header>

  <div class="container">
    <?php $__context->blockPush('content', 'No content provided'); ?>@heap_9a0364b9e99bb480dd25e1f0284c8555
  </div>
</body>
</html>


<?php $__context->blockPush('title', 'Hello world!') ?>

<?php $__context->blockPush('header') ?>
  <?php echo $__context->getBlockContent($__context->getCurrentBlock()) ?>
  <nav>This is the navigation.</nav>
<?php $__context->endBlock() ?>

<?php $__context->blockPush('content') ?>
  <article>
    <p>This is the content</p>
    This was included

    <?php echo $content ?>
  </article>
<?php $__context->endBlock() ?>
