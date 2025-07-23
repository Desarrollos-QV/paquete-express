<?php

namespace App\Traits;

use App\{
    Models\Setting,
    Models\PromoCode,
    Models\TrackOrder,
    Helpers\EmailHelper,
    Helpers\PriceHelper,
    Models\Notification,
    Models\PaymentSetting,
};
use App\Helpers\SmsHelper;
use App\Jobs\EmailSendJob;
use App\Models\Item;
use App\Models\Order;
use App\Models\ShippingService;
use App\Services\EstafetaCotizadorService;
use App\Models\State;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

use function GuzzleHttp\json_decode;

trait StripeCheckout
{


    protected $estafeta_Service;

    public function __construct()
    {
        $this->estafeta_Service = new EstafetaCotizadorService;
        $data = PaymentSetting::whereUniqueKeyword('stripe')->first();
        $paydata = $data->convertJsonData();
        Config::set('services.stripe.key', $paydata['key']);
        Config::set('services.stripe.secret', $paydata['secret']);
    }

    public function stripeSubmit($data)
    {

        $user = Auth::user();
        $setting = Setting::first();
        $cart = Session::get('cart');

        $total_tax = 0;
        $cart_total = 0;
        $total = 0;
        $option_price = 0;

        foreach ($cart as $key => $items) {

            $total += $items['main_price'] * $items['qty'];
            $option_price += $items['attribute_price'];
            $cart_total = $total + $option_price;
            $item = Item::findOrFail($key);
            if ($item->tax) {
                $total_tax += $item::taxCalculate($item) * $items['qty'];
            }
        }


        $discount = [];
        if (Session::has('coupon')) {
            $discount = Session::get('coupon');
        }

        if (!PriceHelper::Digital()) {
            $shipping = null;
        } else {
            $shipping = json_decode($data['shippingInfo'], true); //ShippingService::findOrFail();
            $shipping = ($shipping[0]) ? $shipping[0] : null;
        }

        $orderData['state'] =  $data['state_id'] ? json_encode(State::findOrFail($data['state_id']), true) : null;
        $grand_total = ($cart_total + ($shipping ? $shipping['price'] : 0)) + $total_tax;
        $grand_total = $grand_total - ($discount ? $discount['discount'] : 0);
        $grand_total += PriceHelper::StatePrce($data['state_id'], $cart_total);
        $total_amount = PriceHelper::setConvertPrice($grand_total);

        $orderData['cart'] = json_encode($cart, true);
        $orderData['discount'] = json_encode($discount, true);
        $orderData['shipping'] = json_encode($shipping, true);
        $orderData['tax'] = $total_tax;
        $orderData['state_price'] = PriceHelper::StatePrce($data['state_id'], $cart_total);
        $orderData['shipping_info'] = json_encode(Session::get('shipping_address'), true);
        $orderData['billing_info'] = json_encode(Session::get('billing_address'), true);
        $orderData['payment_method'] = 'Stripe';
        $orderData['user_id'] = isset($user) ? $user->id : 0;
        $orderData['transaction_number'] = Str::random(10);
        $orderData['currency_sign'] = PriceHelper::setCurrencySign();
        $orderData['currency_value'] = PriceHelper::setCurrencyValue();
        $orderData['order_status'] = 'Pending';

        $stripe = new \Stripe\StripeClient(Config::get('services.stripe.secret'));
        try {

            $notify_url = route('front.checkout.redirect') . '?session_id={CHECKOUT_SESSION_ID}';
            $response = $stripe->checkout->sessions->create([
                'success_url' => $notify_url,
                'customer_email' => Session::get('shipping_address')['ship_email'],
                'payment_method_types' => ['card'],

                'line_items' => [
                    [
                        'price_data' => [
                            'product_data' => [
                                'name' => $setting->title . ' ' . __('Order'),
                            ],
                            'unit_amount' => 100 * $total_amount,
                            'currency' => PriceHelper::setCurrencyName(),
                        ],
                        'quantity' => 1
                    ],
                ],

                'mode' => 'payment',
                'allow_promotion_codes' => false,
            ]);
            Session::put('order_data', $orderData);
            Session::put('order_input_data', $data);
            return [
                'status' => true,
                'link' => $response['url']
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }


    public function stripeNotify($resData)
    {

        $stripe = new \Stripe\StripeClient(Config::get('services.stripe.secret'));
        $response = $stripe->checkout->sessions->retrieve($resData['session_id']);

        /** Get Shiping Info */
        $shipping_info = Session::get('shipping_address');
        $label_rest_estafeta = []; //<- Data for Shipping

        if ($response['payment_status'] == 'paid' && $response['status'] == 'complete') {
            $cart = Session::get('cart');
            $user = Auth::user();
            $total_tax = 0;
            $cart_total = 0;
            $total = 0;
            $option_price = 0;

            $order_input_data = Session::get('order_input_data');
            if (!PriceHelper::Digital()) {
                $shipping = null;
            } else {
                $shipping = json_decode($order_input_data['shippingInfo'], true); //ShippingService::findOrFail();
                $shipping = ($shipping[0]) ? $shipping[0] : null;
            }

            foreach ($cart as $key => $items) {

                $total += $items['main_price'] * $items['qty'];
                $option_price += $items['attribute_price'];
                $cart_total = $total + $option_price;
                $item = Item::findOrFail($key);
                if ($item->tax) {
                    $total_tax += $item::taxCalculate($item) * $items['qty'];
                }

                // Calculamos el volumetrico
                $length = ($item['largo'] > 0) ? $item['largo'] : 10;
                $width  = ($item['ancho']  > 0) ? $item['ancho']  : 10;
                $height = ($item['alto']   > 0) ? $item['alto']   : 10;
                $realWeight = ($item['peso'] > 0) ? $item['peso'] : 1; // suponiendo que capturas peso real

                // Calcular peso volumétrico
                $volumetricWeight = ($length * $width * $height) / 5000;

                // Usar el mayor
                $weight = max($realWeight, $volumetricWeight);
                /**
                 * Generamos la GUIA o LABEL REST del Servicio de Estafeta
                 */
                $label_rest_estafeta = [
                    "identification" => [
                        "suscriberId"    => env("ESTAFETA_SUBSCRIBER_ID"),
                        "customerNumber" => env("ESTAFETA_CUSTOMER_NUMBER"),
                    ],
                    "systemInformation" => [
                        "id"      => 'AP' . env('ESTAFETA_SUBSCRIBER_ID'),
                        "name"    => 'AP' . env('ESTAFETA_SUBSCRIBER_ID'),
                        "version" => "1.10.20"
                    ],
                    "labelDefinitions" => [
                        [
                            "wayBillDocument" => [
                                "aditionalInfo" => "string",
                                "content"       => substr($item['slug'], 0, 20) . '...',
                                "costCenter"    => "SPMXA12345",
                                "customerShipmentId" => null,
                                "referenceNumber" => "PED-" . substr(md5(now()->addDays(1)->format('Y-m-d')), 0, 5),
                                "groupShipmentId"    => null
                            ],
                            "itemDescription" => [
                                "parcelId" => 4,
                                "weight"   => $weight,
                                "length"   => $length ?? 10,
                                "width"    => $width ?? 10,
                                "height"   => $height ?? 10,
                                "Merchandise" => [
                                    "totalGrossWeight" => $height ?? 10,
                                    "weightUnitCode" => "XLU",
                                    "merchandise" => [
                                        [
                                            "merchandiseValue" => 0.1,
                                            "currency" => "MXN",
                                            "productServiceCode" => "10131508",
                                            "merchandiseQuantity" => count($cart),
                                            "measurementUnitCode" => "F63",
                                            "tariffFraction" => null,
                                            "isInternational" => false,
                                            "isImport" => false,
                                            "isHazardousMaterial" => false,
                                            "hazardousMaterialCode" => null,
                                            "packagingCode" => "4A"
                                        ]
                                    ]
                                ]
                            ],
                            "serviceConfiguration" => [
                                "quantityOfLabels"        => 1,
                                "serviceTypeId"           => $this->estafeta_Service->mapServiceCodeToServiceTypeId($shipping['code']),
                                "salesOrganization"       => "112", // O el que te proporcione Estafeta
                                "effectiveDate"           => now()->addDays(5)->format('Ymd'),
                                "originZipCodeForRouting" => env('ESTAFETA_ORIGIN_ZIPCODE'),
                                "isInsurance"             => false,
                                "isReturnDocument"        => false,
                            ],
                            "location" => [
                                "isDRAAlternative" => false,
                                "DRAAlternative" => [
                                    "contact" => [
                                        "corporateName" => env("ESTAFETA_ORIGIN_CONTACT_NAME"),
                                        "contactName"   => env("ESTAFETA_ORIGIN_CONTACT_NAME"),
                                        "cellPhone"     => env("ESTAFETA_ORIGIN_PHONE") ?? '0000',
                                        "email"         => "soporte@paquetelleguexpress.com_orga156b5a0-e6e0-4"
                                    ],
                                    "address" => $this->estafeta_Service->getDefaultOrigin(),
                                ],
                                "origin" => [
                                    "contact" => [
                                        "corporateName" => env("ESTAFETA_ORIGIN_CONTACT_NAME"),
                                        "contactName"   => env("ESTAFETA_ORIGIN_CONTACT_NAME"),
                                        "cellPhone"     => env("ESTAFETA_ORIGIN_PHONE") ?? '0000',
                                        "email"         => "soporte@paquetelleguexpress.com_orga156b5a0-e6e0-4"
                                    ],
                                    "address" => $this->estafeta_Service->getDefaultOrigin(),
                                ],
                                "destination" => [
                                    "isDeliveryToPUDO" => false,
                                    "homeAddress" => [
                                        "contact" => [
                                            "corporateName" => $shipping_info['ship_first_name'] . ' ' . $shipping_info['ship_last_name'],
                                            "contactName"   => $shipping_info['ship_first_name'] . ' ' . $shipping_info['ship_last_name'],
                                            "cellPhone"     => $shipping_info['ship_phone'] ?? '',
                                            "email"         => $shipping_info['ship_email'] ?? "no-reply@cliente.com"
                                        ],
                                        "address" => [
                                            "zipCode"        => $shipping_info['ship_zip'],
                                            "roadName"       => $shipping_info['ship_address1'], // Asegúrate que solo sea calle
                                            "externalNum"    => $shipping_info['ship_num_ext'] ?? "S/N",
                                            "settlementName" => $shipping_info['ship_colonia'],
                                            "townshipName"   => $shipping_info['ship_city'],
                                            "stateAbbName"   => $shipping_info['ship_country'],
                                            "countryName"    => "MEX",
                                            "bUsedCode"      => false,
                                            "roadTypeAbbName"       => "string",
                                            "settlementTypeAbbName" => "string"
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];
            }

            $responseEstafService = $this->estafeta_Service->generarGuia($label_rest_estafeta);

            if ($responseEstafService['status'] == 200) {
                $waybill = $responseEstafService['result']['labelPetitionResults'][0]['elements'][0]['wayBill'] ?? null;
                $tracking_code = $responseEstafService['result']['labelPetitionResults'][0]['elements'][0]['trackingCode'] ?? null;
            }

            $discount = [];
            if (Session::has('coupon')) {
                $discount = Session::get('coupon');
            }


            $grand_total = ($cart_total + ($shipping ? $shipping['price'] : 0)) + $total_tax;
            $grand_total = $grand_total - ($discount ? $discount['discount'] : 0);
            $grand_total += PriceHelper::StatePrce($order_input_data['state_id'], $cart_total);

            $total_amount = PriceHelper::setConvertPrice($grand_total);

            $orderData = Session::get('order_data');
            $orderData['txnid'] = $response['payment_intent'];
            $orderData['payment_status'] = 'Paid';

            $order = Order::create($orderData);

            $order->transaction_number =  ($tracking_code != null) ? 'ORD-' . $tracking_code . '-' . $order->id : 'ORD-' . str_pad(Carbon::now()->format('Ymd'), 4, '0000', STR_PAD_LEFT) . '-' . $order->id;
            $order->save();

            PriceHelper::Transaction($order->id, $order->transaction_number, EmailHelper::getEmail(), PriceHelper::OrderTotal($order, 'trns'));
            PriceHelper::LicenseQtyDecrese($cart);
            PriceHelper::LicenseQtyDecrese($cart);

            if (Session::has('copon')) {
                $code = PromoCode::find(Session::get('copon')['code']['id']);
                $code->no_of_times--;
                $code->update();
            }

            if ($discount) {
                $coupon_id = $discount['code']['id'];
                $get_coupon = PromoCode::findOrFail($coupon_id);
                $get_coupon->no_of_times -= 1;
                $get_coupon->update();
            }

            TrackOrder::create([
                'title' => 'Pending',
                'order_id' => $order->id,
            ]);

            Notification::create([
                'order_id' => $order->id
            ]);

            $setting = Setting::first();
            if ($setting->is_twilio == 1) {
                // message
                $sms = new SmsHelper();
                $user_number = json_decode($order->billing_info, true)['bill_phone'];
                if ($user_number) {
                    $sms->SendSms($user_number, "'purchase'", $order->transaction_number);
                }
            }

            $emailData = [
                'to' => EmailHelper::getEmail(),
                'type' => "Order",
                'user_name' => isset($user) ? $user->displayName() : Session::get('billing_address')['bill_first_name'],
                'order_cost' => $total_amount,
                'transaction_number' => $order->transaction_number,
                'site_title' => Setting::first()->title,
            ];

            $setting = Setting::first();
            if ($setting->is_queue_enabled == 1) {
                dispatch(new EmailSendJob($emailData, "template"));
            } else {
                $email = new EmailHelper();
                $email->sendTemplateMail($emailData, "template");
            }
            Session::put('order_id', $order->id);
            Session::forget('cart');
            Session::forget('discount');
            Session::forget('order_data');
            Session::forget('order_payment_id');
            Session::forget('coupon');
            return [
                'status' => true
            ];
        } else {
            return [
                'status' => false,
                'message' => 'Payment Failed'
            ];
        }
    }
}
