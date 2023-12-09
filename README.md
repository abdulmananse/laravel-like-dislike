# Laravel Like Dislike

👍 User like and dislike features for Laravel Application.

## Installing

```shell
composer require abdulmananse/laravel-like-dislike
```

### Configuration

This step is optional

```php
php artisan vendor:publish --provider="Abdulmananse\\LaravelLikeDislike\\LikeServiceProvider" --tag=config
```

### Migrations

This step is also optional, if you want to custom likes table, you can publish the migration files:

```php
php artisan vendor:publish --provider="Abdulmananse\\LaravelLikeDislike\\LikeServiceProvider" --tag=migrations
```

## Usage

### Traits

#### `Abdulmananse\LaravelLikeDislike\Traits\Liker`

```php

use Illuminate\Foundation\Auth\User as Authenticatable;
use Abdulmananse\LaravelLikeDislike\Traits\Liker;

class User extends Authenticatable
{
    use Liker;

    <...>
}
```

#### `Abdulmananse\LaravelLikeDislike\Traits\Likeable`

```php
use Illuminate\Database\Eloquent\Model;
use Abdulmananse\LaravelLikeDislike\Traits\Likeable;

class Post extends Model
{
    use Likeable;

    <...>
}
```

### API

```php
$user = User::find(1);
$post = Post::find(2);

$user->like($post);
$user->dislike($post);
$user->unlike($post);

$user->toggleLike($post);
$user->toggleDislike($post);

$user->hasLiked($post);
$user->hasDisliked($post);
$post->isLikedBy($user);
$post->isDislikedBy($user);
```

Get user likes with pagination:

```php
$likes = $user->likes()->with('likeable')->paginate(20);

foreach ($likes as $like) {
    $like->likeable; // App\Post instance
}
```

Get user dislikes with pagination:

```php
$dislikes = $user->dislikes()->with('likeable')->paginate(20);

foreach ($dislikes as $dislike) {
    $dislike->likeable; // App\Post instance
}
```

Get object likers:

```php
foreach($post->likers as $user) {
    // echo $user->name;
}
```

with pagination:

```php
$likers = $post->likers()->paginate(20);

foreach($likers as $user) {
    // echo $user->name;
}
```

Get object dislikers:

```php
foreach($post->dislikers as $user) {
    // echo $user->name;
}
```
with pagination:

```php
$dislikers = $post->dislikers()->paginate(20);

foreach($dislikers as $user) {
    // echo $user->name;
}
```


### Aggregations

```php
// all likes
$user->likes()->count();

// short way
$user->totalLikes;

// with type
$user->likes()->withType(Post::class)->count();

// all dislikes
$user->dislikes()->count();

// short way
$user->totalDislikes;

// with type
$user->dislikes()->withType(Post::class)->count();

// likers count
$post->likers()->count();

// short way
$post->totalLikers

// dislikers count
$post->dislikers()->count();

// short way
$post->totalDislikers
```

List with `*_count` attribute:

```php
// likes_count
$users = User::withCount('likes')->get();

foreach($users as $user) {
    // $user->likes_count;
}

// dislikes_count
$users = User::withCount('dislikes')->get();

foreach($users as $user) {
    // $user->dislikes_count;
}

// likers_count
$posts = Post::withCount('likers')->get();

foreach($posts as $post) {
    // $post->likers_count;
}

// dislikers_count
$posts = Post::withCount('dislikers')->get();

foreach($posts as $post) {
    // $post->dislikers_count;
}
```

### N+1 issue

To avoid the N+1 issue, you can use eager loading to reduce this operation to just 2 queries. When querying, you may specify which relationships should be eager loaded using the `with` method:

```php
// Liker
$users = User::with('likes')->get();

foreach($users as $user) {
    $user->hasLiked($post);
}

// Disliker
$users = User::with('dislikes')->get();

foreach($users as $user) {
    $user->hasDisliked($post);
}

// Likeable
$posts = App\Post::with('likes')->get();
// or
$posts = App\Post::with('likers')->get();

foreach($posts as $post) {
    $post->isLikedBy($user);
}

// Dislike
$posts = Post::with('dislikes')->get();
// or
$posts = Post::with('dislikers')->get();

foreach($posts as $post) {
    $post->isDislikedBy($user);
}
```

Of course we have a better solution, which can be found in the following section：

### Attach user like/dislike status to likeable collection

You can use `Liker::attachLikeDislikeStatus($likeables)` to attach the user like status, it will attach `has_liked` and `has_disliked` attributes to each model of `$likeables`:

#### For model
```php
$post = Post::find(1);

$post = $user->attachLikeDislikeStatus($post);

// result
[
    "id" => 1
    "title" => "Add socialite login support."
    "has_liked" => true
    "has_disliked" => false
],
```

#### For `Collection | Paginator | LengthAwarePaginator | array`:

```php
$posts = Post::oldest('id')->get();

$posts = $user->attachLikeDislikeStatus($posts);

$posts = $posts->toArray();

// result
[
  [
    "id" => 1
    "title" => "Post title1"
    "has_liked" => true
    "has_disliked" => false
  ],
  [
    "id" => 2
    "title" => "Post title2"
    "has_liked" => fasle
    "has_disliked" => true
  ],
  [
    "id" => 3
    "title" => "Post title3"
    "has_liked" => true
    "has_disliked" => false
  ],
]
```

#### For pagination

```php
$posts = Post::paginate(20);

$user->attachLikeDislikeStatus($posts);
```

### Events

| **Event**                                        | **Description**                             |
| ------------------------------------------------ | ------------------------------------------- |
| `Abdulmananse\LaravelLikeDislike\Events\Liked`   | Triggered when the relationship is created. |
| `Abdulmananse\LaravelLikeDislike\Events\Unliked` | Triggered when the relationship is deleted. |

## License

MIT
