<?php

namespace Abdulmananse\LaravelLikeDislike\Traits;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Abdulmananse\LaravelLikeDislike\Like;

trait Liker
{

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $object
     *
     * @return Like
     */
    public function like(Model $object): Like
    {
        $attributes = [
            'likeable_type' => $object->getMorphClass(),
            'likeable_id' => $object->getKey(),
            config('like.user_foreign_key') => $this->getKey(),
        ];

        /* @var \Illuminate\Database\Eloquent\Model $like */
        $like = \app(config('like.like_model'));

        /* Remove if already disliked */
        $attributes['type'] = 'dislike';
        $like->where($attributes)->delete(); 
        $attributes['type'] = 'like';

        /* @var \Abdulmananse\LaravelLikeDislike\Traits\Likeable|\Illuminate\Database\Eloquent\Model $object */
        return $like->where($attributes)->firstOr(
            function () use ($like, $attributes) {
                return $like->unguarded(function () use ($like, $attributes) {
                    if ($this->relationLoaded('likes')) {
                        $this->unsetRelation('likes');
                    }
                    if ($this->relationLoaded('dislikes')) {
                        $this->unsetRelation('dislikes');
                    }
                    
                    return $like->create($attributes);
                });
            }
        );
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $object
     *
     * @return Dislike
     */
    public function dislike(Model $object): Like
    {
        $attributes = [
            'likeable_type' => $object->getMorphClass(),
            'likeable_id' => $object->getKey(),
            config('like.user_foreign_key') => $this->getKey(),
        ];

        /* @var \Illuminate\Database\Eloquent\Model $like */
        $like = \app(config('like.like_model'));

        /* Remove if already liked */
        $attributes['type'] = 'like';
        $like->where($attributes)->delete(); 
        $attributes['type'] = 'dislike';

        /* @var \Abdulmananse\LaravelLikeDislike\Traits\Likeable|\Illuminate\Database\Eloquent\Model $object */
        return $like->where($attributes)->firstOr(
            function () use ($like, $attributes) {
                return $like->unguarded(function () use ($like, $attributes) {
                    if ($this->relationLoaded('likes')) {
                        $this->unsetRelation('likes');
                    }
                    if ($this->relationLoaded('dislikes')) {
                        $this->unsetRelation('dislikes');
                    }

                    return $like->create($attributes);
                });
            }
        );
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $object
     *
     * @return bool
     * @throws \Exception
     */
    public function unlike(Model $object): bool
    {
        /* @var \Abdulmananse\LaravelLikeDislike\Like $relation */
        $relation = \app(config('like.like_model'))
            ->where('likeable_id', $object->getKey())
            ->where('likeable_type', $object->getMorphClass())
            ->where(config('like.user_foreign_key'), $this->getKey())
            ->first();
    
        if ($relation) {
            if ($this->relationLoaded('likes')) {
                $this->unsetRelation('likes');
            }
            if ($this->relationLoaded('dislikes')) {
                $this->unsetRelation('dislikes');
            }

            return $relation->delete();
        }

        return true;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $object
     *
     * @return Like|null
     * @throws \Exception
     */
    public function toggleLike(Model $object)
    {
        return $this->hasLiked($object) ? $this->unlike($object) : $this->like($object);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $object
     *
     * @return Like|null
     * @throws \Exception
     */
    public function toggleDislike(Model $object)
    {
        return $this->hasDisliked($object) ? $this->unlike($object) : $this->dislike($object);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $object
     *
     * @return bool
     */
    public function hasLiked(Model $object): bool
    {
        return ($this->relationLoaded('likes') ? $this->likes : $this->likes())
                ->where('likeable_id', $object->getKey())
                ->where('likeable_type', $object->getMorphClass())
                ->where('type', 'like')
                ->count() > 0;
    }
    
    /**
     * @param  \Illuminate\Database\Eloquent\Model  $object
     *
     * @return bool
     */
    public function hasDisliked(Model $object): bool
    {
        return ($this->relationLoaded('dislikes') ? $this->dislikes : $this->dislikes())
                ->where('likeable_id', $object->getKey())
                ->where('likeable_type', $object->getMorphClass())
                ->where('type', 'dislike')
                ->count() > 0;
    }

    /**
     * hasMany Likes
     *
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function likes(): HasMany
    {
        return $this->hasMany(config('like.like_model'), config('like.user_foreign_key'), $this->getKeyName())->where('type', 'like');
    }
    
    /**
     * hasMany Dislikes
     *
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function dislikes(): HasMany
    {
        return $this->hasMany(config('like.like_model'), config('like.user_foreign_key'), $this->getKeyName())->where('type', 'dislike');
    }

    /**
     * Get Query Builder for likes
     *
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function getLikedItems(string $model)
    {
        return app($model)->whereHas(
            'likers',
            function ($q) {
                return $q->where(config('like.user_foreign_key'), $this->getKey())->where('type', 'like');
            }
        );
    }
    
    /**
     * Get Query Builder for dislikes
     *
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function getDislikedItems(string $model)
    {
        return app($model)->whereHas(
            'likers',
            function ($q) {
                return $q->where(config('like.user_foreign_key'), $this->getKey())->where('type', 'dislike');
            }
        );
    }

    public function attachLikeDislikeStatus($likeables, callable $resolver = null)
    {
        $returnFirst = false;
        $toArray = false;

        switch (true) {
            case $likeables instanceof Model:
                $returnFirst = true;
                $likeables = \collect([$likeables]);
                break;
            case $likeables instanceof LengthAwarePaginator:
                $likeables = $likeables->getCollection();
                break;
            case $likeables instanceof Paginator:
                $likeables = \collect($likeables->items());
                break;
            case \is_array($likeables):
                $likeables = \collect($likeables);
                $toArray = true;
                break;
        }

        \abort_if(!($likeables instanceof Collection), 422, 'Invalid $likeables type.');

        $liked = $this->likes()->get()->keyBy(function ($item) {
            return \sprintf('%s-%s', $item->likeable_type, $item->likeable_id);
        });

        $disliked = $this->dislikes()->get()->keyBy(function ($item) {
            return \sprintf('%s-%s', $item->likeable_type, $item->likeable_id);
        });

        $likeables->map(function ($likeable) use ($liked, $disliked, $resolver) {
            $resolver = $resolver ?? fn ($m) => $m;
            $likeable = $resolver($likeable);

            if ($likeable && \in_array(Likeable::class, \class_uses_recursive($likeable))) {
                $key = \sprintf('%s-%s', $likeable->getMorphClass(), $likeable->getKey());
                $likeable->setAttribute('has_liked', $liked->has($key));
                $likeable->setAttribute('has_disliked', $disliked->has($key));
            }
        });

        return $returnFirst ? $likeables->first() : ($toArray ? $likeables->all() : $likeables);
    }

    /**
     * Get Total Likes Count
     *
     * @return string
     */
    public function getTotalLikesAttribute()
    {
        return $this->likes()->count();
    }
    
    /**
     * Get Total Dislikes Count
     *
     * @return string
     */
    public function getTotalDislikesAttribute()
    {
        return $this->dislikes()->count();
    }
}
