<?php

namespace Whitecube\LaravelPrices\Models;

use DateTime;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Model;
use Whitecube\Price\Price as PhpPrices;
use Whitecube\LaravelPrices\Concerns\HasUuid;
use Whitecube\LaravelPrices\Exceptions\PriceValueNotDefinedException;

class Price extends Model
{
    use HasUuid;

    public $timestamps = ['activated_at'];
    protected $fillable = ['id', 'type', 'amount', 'currency', 'activated_at'];

    public function __construct(
        array $arguments = null,
        int|string|float $amount = null,
        int $minor = null,
        string $currency = null,
        string $type = null,
        DateTime $activated_at = null
    ) {
        if (! is_null($arguments) && $arguments !== []) {
            return $this->constructFromArgumentsArray($arguments);
        }

        if ((! is_null($amount) && ! is_null($currency)) && is_null($minor)) {
            $amount = Money::of($amount, $currency)->getMinorAmount()->toInt();
        }

        parent::__construct([
            'amount' => $minor ?? $amount,
            'currency' => $currency,
            'type' => $type,
            'activated_at' => $activated_at
        ]);
    }

    private function constructFromArgumentsArray($arguments)
    {
        $amount = $arguments['amount'] ?? null;
        $minor = $arguments['minor'] ?? null;
        $currency = $arguments['currency'];

        if (is_null($amount) && is_null($minor)) {
            throw new PriceValueNotDefinedException('No value provided for price object.');
        }

        $arguments['amount'] = $minor ?? Money::of($amount, $currency)->getMinorAmount()->toInt();

        return parent::__construct($arguments);
    }

    public function priceable()
    {
        return $this->morphTo();
    }

    public function scopeCurrent($query)
    {
        return $query->whereDate('activated_at', '<', now())->latest();
    }

    public function toObject()
    {
        return PhpPrices::ofMinor($this->amount, $this->currency);
    }
}
