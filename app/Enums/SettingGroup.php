<?php

declare(strict_types=1);

namespace App\Enums;

enum SettingGroup: string
{
    case General = 'general';
    case Prefix = 'prefix';
    case Contact = 'contact';
    case Social = 'social';
    case Localization = 'localization';
    case Appearance = 'appearance';

    /**
     * Human-readable tab label.
     */
    public function label(): string
    {
        return match ($this) {
            self::General => 'General',
            self::Prefix => 'Prefix',
            self::Contact => 'Contact',
            self::Social => 'Social links',
            self::Localization => 'Languages',
            self::Appearance => 'Theme Colors',
        };
    }

    /**
     * FontAwesome icon class for the tab.
     */
    public function icon(): string
    {
        return match ($this) {
            self::General => 'fa-sliders',
            self::Prefix => 'fa-hashtag',
            self::Contact => 'fa-address-book',
            self::Social => 'fa-share-nodes',
            self::Localization => 'fa-language',
            self::Appearance => 'fa-palette',
        };
    }

    /**
     * Rendering type: fixed fields or a dynamic repeater.
     */
    public function type(): string
    {
        return $this === self::Social ? 'repeater' : 'fields';
    }

    public function isRepeater(): bool
    {
        return $this->type() === 'repeater';
    }
}
