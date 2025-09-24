<?php

namespace App\Http\Controllers\Back;

use App\{
    Models\Order,
    Models\PromoCode,
    Models\TrackOrder,
    Models\Transaction,
    Http\Controllers\Controller
};
use DB;
use App\Services\EstafetaCotizadorService;
use App\Helpers\SmsHelper;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class OrderController extends Controller
{

    /**
     * Constructor Method.
     *
     * Setting Authentication
     *
     */
    protected $estafeta_Service;

    public function __construct()
    {

        $this->estafeta_Service = new EstafetaCotizadorService;
        $this->middleware('auth:admin');
        $this->middleware('adminlocalize');
    }



    /**
     * Summary of index
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        if ($request->type) {
            if ($request->start_date && $request->end_date) {
                $datas = $start_date = Carbon::parse($request->start_date);
                $end_date = Carbon::parse($request->end_date);
                $datas = Order::latest('id')->whereOrderStatus($request->type)->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->get();
            } else {
                $datas = Order::latest('id')->whereOrderStatus($request->type)->get();
            }
        } else {
            if ($request->start_date && $request->end_date) {
                $datas = $start_date = Carbon::parse($request->start_date);
                $end_date = Carbon::parse($request->end_date);
                $datas = Order::latest('id')->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->get();
            } else {
                $datas = Order::latest('id')->get();
            }
        }

        // return response()->json([
        //     'data' => $datas
        // ]);

        return view('back.order.index', compact('datas'));
    }


    /**
     * Summary of edit
     * @param mixed $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit($id)
    {
        $order = Order::findOrFail($id);
        return view('back.order.edit', compact('order'));
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        // Check if order_id is available
        if (Order::where('transaction_number', $request->transaction_number)->where('id', '!=', $id)->exists()) {
            return redirect()->route('back.order.index')->withErrors(__('Order ID already exists.'));
        }

        $order->update($request->all());
        return redirect()->route('back.order.index')->withSuccess(__('Order Updated Successfully.'));
    }

    /**
     * Summary of invoice
     * @param mixed $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function invoice($id)
    {
        $order = Order::findOrfail($id);
        $cart = json_decode($order->cart, true);
        return view('back.order.invoice', compact('order', 'cart'));
    }

    /**
     * Summary of printOrder
     * @param mixed $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function printOrder($id)
    {
        $order = Order::findOrfail($id);
        $cart = json_decode($order->cart, true);
        return view('back.order.print', compact('order', 'cart'));
    }

    public function GeneratedGuideOrder($id)
    {
        $order = Order::find($id);

        // Generamos GUIA
        $cart = json_decode($order->cart, true);
        $shipping = json_decode($order->shipping, true);
        $shipping_info = json_decode($order->shipping_info, true);

        if ($order->type_ship == 1) {
            foreach ($cart as $key => $item) {
                // Calculamos el volumetrico
                $length = ($item['largo'] > 0) ? $item['largo'] : 10;
                $width = ($item['ancho'] > 0) ? $item['ancho'] : 10;
                $height = ($item['alto'] > 0) ? $item['alto'] : 10;
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
                        "suscriberId" => env("ESTAFETA_SUBSCRIBER_ID"),
                        "customerNumber" => env("ESTAFETA_CUSTOMER_NUMBER"),
                    ],
                    "systemInformation" => [
                        "id" => 'AP' . env('ESTAFETA_SUBSCRIBER_ID'),
                        "name" => 'AP' . env('ESTAFETA_SUBSCRIBER_ID'),
                        "version" => "1.10.20"
                    ],
                    "labelDefinitions" => [
                        [
                            "wayBillDocument" => [
                                "aditionalInfo" => "string",
                                "content" => substr($item['slug'], 0, 20) . '...',
                                "costCenter" => "SPMXA12345",
                                "customerShipmentId" => null,
                                "referenceNumber" => "PED-" . substr(md5(now()->addDays(1)->format('Y-m-d')), 0, 5),
                                "groupShipmentId" => null
                            ],
                            "itemDescription" => [
                                "parcelId" => 4,
                                "weight" => $weight,
                                "length" => $length ?? 10,
                                "width" => $width ?? 10,
                                "height" => $height ?? 10,
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
                                "quantityOfLabels" => 1,
                                "serviceTypeId" => $this->estafeta_Service->mapServiceCodeToServiceTypeId($shipping['code']),
                                "salesOrganization" => "112", // O el que te proporcione Estafeta
                                "effectiveDate" => now()->addDays(5)->format('Ymd'),
                                "originZipCodeForRouting" => env('ESTAFETA_ORIGIN_ZIPCODE'),
                                "isInsurance" => false,
                                "isReturnDocument" => false,
                            ],
                            "location" => [
                                "isDRAAlternative" => false,
                                "DRAAlternative" => [
                                    "contact" => [
                                        "corporateName" => env("ESTAFETA_ORIGIN_CONTACT_NAME"),
                                        "contactName" => env("ESTAFETA_ORIGIN_CONTACT_NAME"),
                                        "cellPhone" => env("ESTAFETA_ORIGIN_PHONE") ?? '0000',
                                        "email" => "soporte@paquetelleguexpress.com_orga156b5a0-e6e0-4"
                                    ],
                                    "address" => $this->estafeta_Service->getDefaultOrigin(),
                                ],
                                "origin" => [
                                    "contact" => [
                                        "corporateName" => env("ESTAFETA_ORIGIN_CONTACT_NAME"),
                                        "contactName" => env("ESTAFETA_ORIGIN_CONTACT_NAME"),
                                        "cellPhone" => env("ESTAFETA_ORIGIN_PHONE") ?? '0000',
                                        "email" => "soporte@paquetelleguexpress.com_orga156b5a0-e6e0-4"
                                    ],
                                    "address" => $this->estafeta_Service->getDefaultOrigin(),
                                ],
                                "destination" => [
                                    "isDeliveryToPUDO" => false,
                                    "homeAddress" => [
                                        "contact" => [
                                            "corporateName" => $shipping_info['ship_first_name'] . ' ' . $shipping_info['ship_last_name'],
                                            "contactName" => $shipping_info['ship_first_name'] . ' ' . $shipping_info['ship_last_name'],
                                            "cellPhone" => $shipping_info['ship_phone'] ?? '',
                                            "email" => $shipping_info['ship_email'] ?? "no-reply@cliente.com"
                                        ],
                                        "address" => [
                                            "zipCode" => $shipping_info['ship_zip'],
                                            "roadName" => $shipping_info['ship_address1'], // Asegúrate que solo sea calle
                                            "externalNum" => $shipping_info['ship_num_ext'] ?? "S/N",
                                            "settlementName" => $shipping_info['ship_colonia'],
                                            "townshipName" => $shipping_info['ship_city'],
                                            "stateAbbName" => $shipping_info['ship_country'],
                                            "countryName" => "MEX",
                                            "bUsedCode" => false,
                                            "roadTypeAbbName" => "string",
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

                $order->guide_generated = $tracking_code;
                $order->save();

                return redirect()->route('back.order.index')->withSuccess(__('Guide Generated Successfully.'));
            } else {
                return redirect()->route('back.order.index')->withError(__('Error Generated Guide:'));
            }
        } else {
            // Envio Local - Generamos Guia PDF
            $setting = DB::table('settings')->find(1);
            // {
            //     "ship_first_name":"Francisco",
            //     "ship_last_name":"Garcia",
            //     "ship_email":"franciscocargarcia@gmail.com",
            //     "ship_phone":"5689788978",
            //     "ship_company":"Francisco Garcia",
            //     "ship_address1":"Fernando montes de oca",
            //     "ship_address2":null,
            //     "ship_zip":"31778",
            //     "ship_city":"Nuevo Casas Grandes",
            //     "ship_colonia":"Acci\u00f3n Popular",
            //     "ship_country":"Mexico"
            // }
            $datos = [
                'nombre' => $shipping_info['ship_first_name'],
                'codigo' => $order['transaction_number'],
                'telefono' => $shipping_info['ship_phone'],
                'direccion' => $shipping_info['ship_address1'].' '.$shipping_info['ship_colonia'].', '.$shipping_info['ship_city'].' '.$shipping_info['ship_zip'],
                'referencias' => 'S/N',
                'notas' => '',
                'cobranza' => '$0.00',
                'remitente' => $shipping_info['ship_first_name'],
                'telefono_rem' => $shipping_info['ship_phone'],
                'url_tracking' => route('back.order.edit', $order->id),
                'logo' => url('/core/public/storage/images/' . $setting->logo)
            ];

            // $qrCode = base64_encode(QrCode::format('png')->size(120)->generate($datos['url_tracking']));
            // Generar QR como PNG usando GD
            $qrCode = base64_encode(
                QrCode::format('png')
                    ->size(120)
                    ->errorCorrection('H')
                    ->generate($datos['url_tracking'])
            );
            // return response()->json([
            //     'datos' => $datos
            // ]);

            $pdf = Pdf::setOptions(['isRemoteEnabled' => true])
                ->loadView('back.order.guia', compact('datos', 'qrCode'));
            return $pdf->download('guia_' . $datos['codigo'] . '.pdf');
        }


        // ($tracking_code != null) ? 'ORD-' . $tracking_code . '-' . $order->id :
        // return response()->json([
        //     'lbl_rest' => $responseEstafService
        // ]);
    }

    /**
     * Summary of DownloadOrderTrack
     * @param mixed $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function DownloadOrderTrack($id)
    {
        $track = Transaction::where('order_id', $id)->first();

        if (!$track || !$track->txn_id) {
            abort(404, 'Transacción no encontrada.');
        }

        // Desmenuzar el txn_id → ejemplo: ORD-3136927668-196
        // $parts = explode('-', $track->txn_id); // ["ORD", "3136927668", "196"]
        // if (count($parts) < 3) {
        //     abort(400, 'Formato de código inválido.');
        // }

        $trackingCode = Order::find($id)->guide_generated; //$parts[1]; // "3136927668"
        $filePath = 'guias/estafeta_' . $trackingCode . '.pdf';

        if (!Storage::disk('local')->exists($filePath)) {
            abort(404, 'Archivo PDF de guía no encontrado.');
        }

        // Opcional: descarga forzada
        // return response()->download(storage_path('app/' . $filePath));

        // Mostrar en navegador
        return response()->file(storage_path('app/' . $filePath), [
            'Content-Type' => 'application/pdf'
        ]);
    }

    /**
     * Change the status for editing the specified resource.
     *
     * @param  int  $id
     * @param  string  $field
     * @param  string  $value
     * @return \Illuminate\Http\Response
     */
    public function status($id, $field, $value)
    {

        $order = Order::find($id);
        $track = Transaction::where('order_id', $id)->first();

        if ($field == 'payment_status') {
            if ($order['payment_status'] == 'Paid') {
                return redirect()->route('back.order.index')->withErrors(__('Order is already paid.'));
            }
        }
        if ($field == 'order_status') {
            if ($order['order_status'] == 'Delivered') {
                return redirect()->route('back.order.index')->withErrors(__('Order is already Delivered.'));
            }
        }

        if ($field == 'payment_status' && $value == 'Paid') {
            $txn_id = 'ORD-' . str_pad(Carbon::now()->format('Ymd'), 4, '0000', STR_PAD_LEFT) . '-' . $order->id;
            // Actualizamos Transactions
            $track->update(['txn_id' => $txn_id]);
            // Actualizamos Orders
            $order->update(['transaction_number' => $txn_id]);
        }

        $order->update([$field => $value]);
        if ($order->payment_status == 'Paid') {
            $this->setPromoCode($order);
        }

        $this->setTrackOrder($order);

        $sms = new SmsHelper();
        $user_number = $order->user->phone;
        if ($user_number) {
            $sms->SendSms($user_number, "'order_status'", $order->transaction_number);
        }

        return redirect()->route('back.order.index')->withSuccess(__('Status Updated Successfully.'));
    }

    /**
     * Custom Function
     */
    public function setTrackOrder($order)
    {

        if ($order->order_status == 'In Progress') {
            if (!TrackOrder::whereOrderId($order->id)->whereTitle('In Progress')->exists()) {
                TrackOrder::create([
                    'title' => 'In Progress',
                    'order_id' => $order->id
                ]);
            }
        }
        if ($order->order_status == 'Canceled') {
            if (!TrackOrder::whereOrderId($order->id)->whereTitle('Canceled')->exists()) {

                if (!TrackOrder::whereOrderId($order->id)->whereTitle('In Progress')->exists()) {
                    TrackOrder::create([
                        'title' => 'In Progress',
                        'order_id' => $order->id
                    ]);
                }
                if (!TrackOrder::whereOrderId($order->id)->whereTitle('Delivered')->exists()) {
                    TrackOrder::create([
                        'title' => 'Delivered',
                        'order_id' => $order->id
                    ]);
                }

                if (!TrackOrder::whereOrderId($order->id)->whereTitle('Canceled')->exists()) {
                    TrackOrder::create([
                        'title' => 'Canceled',
                        'order_id' => $order->id
                    ]);
                }
            }
        }
        if ($order->order_status == 'Delivered') {

            if (!TrackOrder::whereOrderId($order->id)->whereTitle('In Progress')->exists()) {
                TrackOrder::create([
                    'title' => 'In Progress',
                    'order_id' => $order->id
                ]);
            }

            if (!TrackOrder::whereOrderId($order->id)->whereTitle('Delivered')->exists()) {
                TrackOrder::create([
                    'title' => 'Delivered',
                    'order_id' => $order->id
                ]);
            }
        }
    }


    public function setPromoCode($order)
    {

        $discount = json_decode($order->discount, true);
        if ($discount != null) {
            $code = PromoCode::find($discount['code']['id']);
            $code->no_of_times--;
            $code->update();
        }
    }


    public function delete($id)
    {
        $order = Order::findOrFail($id);
        $order->tranaction->delete();
        if (Notification::where('order_id', $id)->exists()) {
            Notification::where('order_id', $id)->delete();
        }
        if (count($order->tracks_data) > 0) {
            foreach ($order->tracks_data as $track) {
                $track->delete();
            }
        }
        $order->delete();
        return redirect()->back()->withSuccess(__('Order Deleted Successfully.'));
    }
}
