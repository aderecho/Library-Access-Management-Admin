<?php

namespace App\Models\Concerns;

trait HasPersonName
{
    public function getFullNameAttribute(): string
    {
        return collect([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->suffix,
        ])->filter(fn ($part) => filled($part))
            ->map(fn ($part) => trim((string) $part))
            ->implode(' ');
    }

    /**
     * Backward-compatible alias for integrations that still read `name`.
     */
    public function getNameAttribute(): string
    {
        return $this->full_name;
    }

    /**
     * Backward-compatible input bridge while callers migrate to structured names.
     */
    public function setNameAttribute(?string $name): void
    {
        foreach (self::splitPersonName($name) as $key => $value) {
            $this->attributes[$key] = $value;
        }
    }

    public static function splitPersonName(?string $name): array
    {
        $normalized = trim((string) preg_replace('/\s+/', ' ', (string) $name));
        $parts = $normalized === '' ? [] : explode(' ', $normalized);
        $suffix = null;

        if ($parts !== [] && preg_match('/^(Jr\.?|Sr\.?|II|III|IV|V)$/i', (string) end($parts))) {
            $suffix = array_pop($parts);
        }

        $firstName = array_shift($parts) ?? '';
        $lastNameParts = [];

        if ($parts !== []) {
            array_unshift($lastNameParts, array_pop($parts));
            $surnamePrefixes = ['da', 'das', 'de', 'del', 'dela', 'de la', 'de los', 'di', 'dos', 'du', 'la', 'le', 'san', 'santa', 'st.', 'van', 'von'];

            while ($parts !== [] && in_array(strtolower((string) end($parts)), $surnamePrefixes, true)) {
                array_unshift($lastNameParts, array_pop($parts));
            }
        }

        return [
            'first_name' => $firstName,
            'middle_name' => $parts === [] ? null : implode(' ', $parts),
            'last_name' => implode(' ', $lastNameParts),
            'suffix' => $suffix,
        ];
    }
}
