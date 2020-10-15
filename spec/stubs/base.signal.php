<html>
<head>
  <title>App Name - @yield{'title'}</title>
  <style>html,body{margin:0;padding:0}</style>
  @stack{'css'}
</head>
<body>
  <header>
    @block{'header'}
      <h1>@yield{'title'}</h1>
    @show
  </header>

  <div class="container">
    @yield{'content', 'No content provided'}
  </div>
</body>
</html>
