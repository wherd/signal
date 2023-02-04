@extends{'base'}

@section{'css'}
<link rel="stylesheet" src="style1.css" />
<link rel="stylesheet" src="style2.css" />
@end

@section{'title', 'Hello world!'}

@section{'header'}
  @parent
  <nav>This is the navigation.</nav>
@end

@section{'content'}
  <article>
    <p>This is the content</p>
    @include{'included'}
    @{! $content }
  </article>
@end
