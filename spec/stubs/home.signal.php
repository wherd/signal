@extends{'base'}

@block{'title', 'Hello world!'}

@block{'header'}
  @parent
  <nav>This is the navigation.</nav>
@end

@block{'content'}
  <article>
    <p>This is the content</p>
    @include{'included'}
    @{! $content }
  </article>
@end
