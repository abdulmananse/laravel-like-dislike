<?php

namespace Abdulmananse\LaravelLikeDislike\Traits;

use Illuminate\Database\Eloquent\Model;

trait Likeable
{
    /**
     * @param \Illuminate\Database\Eloquent\Model $user
     *
     * @return bool
     */
    public function isLikedBy(Model $user): bool
    {
        if (\is_a($user, config('auth.providers.users.model'))) {
            if ($this->relationLoaded('likers')) {
                return $this->likers->contains($user);
            }

            return $this->likers()->where(\config('like.user_foreign_key'), $user->getKey())->exists();
        }

        return false;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $user
     *
     * @return bool
     */
    public function isDislikedBy(Model $user): bool
    {
        if (\is_a($user, config('auth.providers.users.model'))) {
            if ($this->relationLoaded('likers')) {
                return $this->dislikers->contains($user);
            }

            return $this->dislikers()->where(\config('like.user_foreign_key'), $user->getKey())->exists();
        }

        return false;
    }

    /**
     * Return likers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function likers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            config('auth.providers.users.model'),
            config('like.likes_table'),
            'likeable_id',
            config('like.user_foreign_key')
        )
            ->where('likeable_type', $this->getMorphClass())
            ->where('type', 'like');
    }

    /**
     * Return dislikers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function dislikers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            config('auth.providers.users.model'),
            config('like.likes_table'),
            'likeable_id',
            config('like.user_foreign_key')
        )
            ->where('likeable_type', $this->getMorphClass())
            ->where('type', 'dislike');
    }

    /**
     * Get Total Likers Count
     *
     * @return string
     */
    public function getTotalLikersAttribute()
    {
        return $this->likers()->count();
    }
    
    /**
     * Get Total Dislikers Count
     *
     * @return string
     */
    public function getTotalDislikersAttribute()
    {
        return $this->dislikers()->count();
    }
}
