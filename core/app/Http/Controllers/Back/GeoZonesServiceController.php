<?php
namespace App\Http\Controllers\Back;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Geozones;
use App\Models\Currency;

class GeoZonesServiceController extends Controller
{

    /**
     * Constructor Method.
     *
     * Setting Authentication
     */
    public function __construct()
    {
        $this->middleware('auth:admin');
        $this->middleware('adminlocalize');
    }

    /**
     * Summary of index
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        return view('back.geozones.index', [
            'data' => Geozones::orderBy('id','DESC')->get()
        ]);
    }

    /**
     * Summary of create
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create()
    {
        $data = new Geozones;
        return view('back.geozones.add', compact('data'));
    }

    /**
     * Summary of store
     * @param \App\Http\Controllers\Back\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        Geozones::create($request->all());
        return redirect()->route('back.geozones.index')->withSuccess(__('New geozones Service Added Successfully.'));
    }

    /**
     * Summary of edit
     * @param \App\Models\Geozones $geozone
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit(Geozones $geozone)
    { 
        $data = $geozone;
        return view('back.geozones.edit', compact('geozone', 'data'));
    }


    /**
     * Change the status for editing the specified resource.
     *
     * @param  int  $id
     * @param  int  $status
     * @return \Illuminate\Http\Response
     */
    public function status($id, $status)
    {
        Geozones::find($id)->update(['status' => $status]);
        return redirect()->route('back.geozones.index')->withSuccess(__('Status Updated Successfully.'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Geozones $geozone)
    {
        $input = $request->all();
        $geozone->update($input);
        return redirect()->route('back.geozones.index')->withSuccess(__('geozones Service Updated Successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Geozones $geozone)
    {
        $geozone->delete();
        return redirect()->route('back.geozones.index')->withSuccess(__('geozones Service Deleted Successfully.'));
    }

}