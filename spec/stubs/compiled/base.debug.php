<html>
<head>
  <?php $this->section('head') ?>
    <title>App Name - <?= $this->displaySection('title') ?></title>
    <style>html,body{margin:0;padding:0}</style>
    <?= $this->displaySection('css') ?>
    
  <?php $this->endSection(true) ?>
</head>
<body>
  <header>
    <?php $this->section('header') ?>
      <h1><?= $this->displaySection('title') ?></h1>
    <?php $this->endSection(true) ?>
  </header>
  <div class="container">
    <?= $this->displaySection('content', 'No content provided') ?>
  </div>
</body>
</html>
