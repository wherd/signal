<html>
<head>
  @section{'head'}
    <title>App Name - @yield{'title'}</title>
    <style>html,body{margin:0;padding:0}</style>
    @yield{ 'css' }
    @{- This is a comment -}
  @show
</head>
<body>
  <header>
    @section{'header'}
      <h1>@yield{'title'}</h1>
    @show
  </header>
  <div class="container">
    @yield{'content', 'No content provided'}
  </div>
</body>
</html>
