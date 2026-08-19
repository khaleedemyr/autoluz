<?php

namespace App\Models;

use App\Support\AdminPermissions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Role extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'type',
        'permissions',
        'is_super',
        'is_default',
    ];

    public const TYPE_ADMIN = 'admin';

    public const TYPE_VISITOR = 'visitor';

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_super' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function hasPermission(string $key): bool
    {
        if ($this->is_super) {
            return true;
        }

        return in_array($key, $this->permissions ?? [], true);
    }

    public function grantsAdminAccess(): bool
    {
        return $this->is_super || $this->type === self::TYPE_ADMIN;
    }

    public function isVisitor(): bool
    {
        return $this->type === self::TYPE_VISITOR;
    }

    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type ?: self::TYPE_ADMIN,
            'is_super' => (bool) $this->is_super,
            'is_default' => (bool) $this->is_default,
            'permissions' => $this->is_super ? AdminPermissions::keys() : array_values($this->permissions ?? []),
            'users_count' => $this->users_count ?? $this->users()->count(),
        ];
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'role';
        $slug = $base;
        $i = 2;

        while (
            static::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    public static function visitor(): ?self
    {
        return static::query()
            ->where('type', self::TYPE_VISITOR)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    public static function super(): ?self
    {
        return static::query()->where('is_super', true)->orderBy('id')->first();
    }
}
