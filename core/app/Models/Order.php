<?php

namespace App\Models;
use DB;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'user_info',
        'cart',
        'shipping',
        'discount',
        'payment_method',
        'txnid',
        'charge_id',
        'transaction_number',
        'order_status',
        'payment_status',
        'shipping_info',
        'billing_info',
        'currency_sign',
        'currency_value',
        'tax',
        'state_price',
        'state'
    ];

    public function user()
    {
    	return $this->belongsTo('App\Models\User')->withDefault();
    }

    public function tracks()
    {
    	return $this->belongsTo('App\Models\TrackOrder','order_id')->withDefault();
    }

    public function tranaction()
    {
    	return $this->hasOne('App\Models\Transaction','order_id')->withDefault();
    }

    public function tracks_data()
    {
    	return $this->hasMany('App\Models\TrackOrder','order_id');
    }

    public function notificaton()
    {
    	return $this->hasMany('App\Models\Notification','order_id');
    }

    public function ViewScaleVolumetric($ct)
    {
        // Generamos GUIA
        $cart = json_decode($ct, true);
        $ScaleWeight = 0;
        $VolumetricWeight = 0;

        foreach ($cart as $key => $item) {
          
            // Calculamos el volumetrico
            $length = (isset($item['largo'])) ? $item['largo'] : 10;
            $width  = (isset($item['ancho'])) ? $item['ancho']  : 10;
            $height = (isset($item['alto'])) ? $item['alto']   : 10;
            $realWeight = (isset($item['peso'])) ? $item['peso'] : 1; // suponiendo que capturas peso real
            
            // Calcular peso volumétrico
            $volumetricWeight = ($length * $width * $height) / 5000;

            // Usar el mayor
            $weight = max($realWeight, $volumetricWeight);
            $ScaleWeight += $weight;
            $VolumetricWeight += $volumetricWeight;
        }

        return [
            'ScaleWeight' => $ScaleWeight,
            'VolumetricWeight' => $VolumetricWeight,
        ];
    }

}
