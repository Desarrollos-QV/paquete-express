<?php

namespace App\Http\Controllers\User;

use App\Services\EstafetaCotizadorService;

use App\{
    Http\Requests\UserRequest,
    Http\Controllers\Controller,
    Repositories\Front\UserRepository
};
use App\Helpers\ImageHelper;
use App\Models\Order;
use App\Models\Geozones;
use Illuminate\Http\Request;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;

class AccountController extends Controller
{

    /**
     * Constructor Method.
     *
     * Setting Authentication
     *
     * @param  \App\Repositories\Back\UserRepository $repository
     *
     */
    public function __construct(UserRepository $repository)
    {
        $this->middleware('auth');
        $this->middleware('localize');
        $this->repository = $repository;
    }

    public function index()
    {
        return view('user.dashboard.dashboard', [
            'allorders' => Order::whereUserId(Auth::user()->id)->count(),
            'pending' => Order::whereUserId(Auth::user()->id)->whereOrderStatus('pending')->count(),
            'progress' => Order::whereUserId(Auth::user()->id)->whereOrderStatus('In Progress')->count(),
            'delivered' => Order::whereUserId(Auth::user()->id)->whereOrderStatus('Delivered')->count(),
            'canceled' => Order::whereUserId(Auth::user()->id)->whereOrderStatus('Canceled')->count()

        ]);
    }


    public function profile()
    {
        $user = Auth::user();
        $check_newsletter = Subscriber::where('email', $user->email)->exists();
        return view('user.dashboard.index', [
            'user' => $user,
            'check_newsletter' => $check_newsletter,
        ]);
    }



    public function profileUpdate(UserRequest $request)
    {
        $this->repository->profileUpdate($request);
        Session::flash('success', __('Profile Updated Successfully.'));
        return redirect()->back();
    }

    public function addresses()
    {
        $user = Auth::user();
        return view('user.dashboard.address', [
            'user' => $user
        ]);
    }

    public function billingSubmit(Request $request)
    {

        $request->validate([
            'bill_address1' => 'required|max:100',
            'bill_address2' => 'nullable|max:100',
            'bill_zip'      => 'nullable|max:100', 
            'bill_company'   => 'nullable|max:100',
            'bill_country'   => 'required|max:100',
        ]);
        $user =  Auth::user();
        $input = $request->all();
        $user->update($input);
        Session::flash('success', __('Address update successfully'));
        return back();
    }

    public function shippingSubmit(Request $request)
    {
        $request->validate([
            'ship_address1' => 'required|max:100',
            'ship_address2' => 'nullable|max:100',
            'ship_zip'      => 'nullable|max:100',
            'ship_city'      => 'required|max:100',
            'ship_company'   => 'nullable|max:100',
            'ship_country'   => 'required|max:100',
        ]);
        $user =  Auth::user();
        $input = $request->all();
        $user->update($input);
        Session::flash('success', __('Address update successfully'));
        return back();
    }


    public function removeAccount()
    {
        $user = User::where('id', Auth::user()->id)->first();
        ImageHelper::handleDeletedImage($user, 'photo', 'assets/images/');
        $user->delete();
        Session::flash('success', __('Your account successfully remove'));
        return redirect(route('front.index'));
    }

    /**
     * Summary of geozones
     * @return \Illuminate\Http\JsonResponse
     */
    public function geozones()
    {
        $local_shipping = GeoZones::whereStatus(1)->get()->makeHidden(['created_at', 'updated_at']);
 
        return response()->json([
            'code' => 200,
            'geozones' => $local_shipping 
        ]);
    }

    /**
     * Summary of shippingSubmitCode
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function shippingSubmitCode(Request $request)
    {

        $client = new Client();

        $code  = $request->codezip;
        $token = $request->token_compomex;

        $response = $client->get('https://api.copomex.com/query/info_cp/' . $code . '?token=' . $token);
        $data = json_decode($response->getBody());

        $colonias = [];
        $ciudad = null;

        if (isset($data) && is_array($data)) {
            foreach ($data as $item) {
                if (isset($item->response->asentamiento)) {
                    $colonias[] = $item->response->asentamiento;
                }
                // Solo la primera vez guardamos ciudad
                if ($ciudad === null && isset($item->response->ciudad)) {
                    $ciudad = $item->response->ciudad;
                }
            }
        } else {
            // Si la respuesta no es válida
            $colonias = ['Sin resultados'];
            $ciudad = 'Desconocida';
        }

        return response()->json(['code' => 200, 'colonias' => $colonias, 'ciudad' => $ciudad, 'message' => 'Se ha obtenido la siguiente información.']);
    }


    public function shippingPaquete(Request $request)
    {

        try {
            $code             = $request->codezip;
            $code_zip_tienda  = $request->code_zip_tienda;

            $alto   = $request->alto;
            $ancho  = $request->ancho;
            $largo  = $request->largo;
            $peso   = $request->peso;

            $length = ($largo > 0) ? $largo : 10;
            $width  = ($ancho  > 0) ? $ancho  : 10;
            $height = ($alto   > 0) ? $alto   : 10;
            $realWeight = ($peso > 0) ? $peso : 1; // suponiendo que capturas peso real

            // Calcular peso volumétrico
            $volumetricWeight = ($length * $width * $height) / 5000;

            // Usar el mayor
            $weight = max($realWeight, $volumetricWeight);

            $data = [
                "Origin" => $code_zip_tienda,
                "Destination" => [$code],
                "PackagingType" => "Paquete",
                "IsInsurance" => false, // Bandera para calcular el seguro
                "ItemValue" => 0, // Si Insurance es TRUE del 0 al 9
                "Dimensions" => [
                    "Length" => $length,
                    "Width" => $width,
                    "Height" => $height,
                    "Weight" => $weight // ya calculado
                ]
            ];

            $estafeta = new EstafetaCotizadorService;

            $cotizacion = $estafeta->cotizar($data);

            // Extraemos el resultado
            $quotation = $cotizacion['Quotation'][0] ?? null;
            $services  = $quotation['Service'] ?? [];

            // Detalles de cada servicio
            $serviceDescriptions = [
                '09:30'           => 'Entrega al siguiente día hábil antes de las 09:30 a.m. 1 KG',
                '11:30'           => 'Entrega al siguiente día hábil antes de las 11:30 a.m. 1 KG',
                '12:30'           => 'Entrega al siguiente día hábil antes de las 11:30 a.m. 1 KG',
                'Dia Sig.'        => 'Entrega al siguiente día hábil en horario abierto 1 KG',
                '2 Dias'          => 'Entrega en dos días hábiles en horario abierto 1 KG',
                'Terrestre'       => 'Entrega de 2 a 5 días hábiles en horario abierto 5 KG',
                'Terrestre 1 KG'  => 'Entrega de 2 a 5 días hábiles en horario abierto 1 KG',
            ];

            // Array a entregar
            $shippingOptions = [];

            foreach ($services as $service) {
                $name = trim($service['ServiceName']); // Algunos servicios pueden venir con espacios

                if($name == 'Terrestre')
                {
                    $shippingOptions[] = [
                        'code'       => $service['ServiceCode'],
                        'raw_name'   => $name,
                        'name'       => $serviceDescriptions[$name] ?? 'Servicio desconocido',
                        'price'      => $service['TotalAmount'],
                        'modality'   => $service['Modality'],
                        'vat'        => $service['VATApplied'],
                        'warranty'   => strtolower($service['CoversWarranty']) === 'true',
                    ];
                }
            }

            return response()->json(['code' => 200, 'data' => $shippingOptions, 'allData' => $quotation]);
        } catch (\Exception $th) {
            return response()->json(['data' => 'error', 'message' => $th->getMessage()]);
        }
    }
}
