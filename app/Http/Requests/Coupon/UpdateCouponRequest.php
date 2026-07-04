<?php

namespace App\Http\Requests\Coupon;

class UpdateCouponRequest extends BaseCouponRequest
{
    /**
     * Ignore the current coupon's own code during the unique check.
     */
    protected function couponId(): ?int
    {
        return (int) $this->route('id');
    }
}
