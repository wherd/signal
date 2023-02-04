@extends{'base'}

@block{ 'title', $title }

@block{'header'}
  @parent
  <nav>This is the navigation.</nav>
@end

@block{'content'}
  <article>
    @for{ $products as $product }
        @include{ 'miniature' }
    @end
  </article>
@end
