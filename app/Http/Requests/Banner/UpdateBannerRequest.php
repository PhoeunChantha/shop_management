<?php

namespace App\Http\Requests\Banner;

class UpdateBannerRequest extends BaseBannerRequest
{
    /**
     * On update the image is optional — the existing one is kept when none is sent.
     */
    protected function imageRule(): string
    {
        return 'nullable';
    }
}
