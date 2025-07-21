<?php

namespace App\Http\Controllers\Back;

use App\{
    Models\EmailTemplate,
    Http\Controllers\Controller,
};
use App\Models\Setting;
use Illuminate\Http\Request;

class EmailSettingController extends Controller
{

    /**
     * Constructor Method.
     */
    public function __construct()
    {
        $this->middleware('adminlocalize');
        $this->middleware('auth:admin');
    }

    /**
     * Summary of email
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function email()
    {
        return view('back.settings.email',[
            'datas' => EmailTemplate::get()
        ]);
    }

    /**
     * Summary of cotizador
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function cotizador()
    {
        return view('back.settings.cotizador',[
            'datas' => EmailTemplate::get()
        ]);
    }

    /**
     * Summary of cotizadorUpdate
     * @param \Illuminate\Http\Request $request
     */
    public function cotizadorUpdate(Request $request)
    {

        $request->validate([
            "code_zip" => "required:max:200",
        ]);

        $input = $request->all();
        
        Setting::first()->update($input);
        return redirect()->back()->withSuccess(__('Data Updated Successfully.'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(EmailTemplate $template)
    {
        return view('back.email_template.edit',compact('template'));
    }

    public function emailUpdate(Request $request)
    {
        $request->validate([
           
            "email_host" => "required:max:200",
            "email_port" => "required|max:10",
            "email_encryption" => "required|max:10",
            "email_user" => "required|max:100",
            "email_pass" => "required|max:100",
            "email_from" => "required|max:100",
            "email_from_name" => "required|max:100",
            "contact_email" => "required|max:100",
        ]);

        $input = $request->all();
        if(isset($request['smtp_check'])){
            $input['smtp_check'] = 1;
        }else{
            $input['smtp_check'] = 0;
        }
        if(isset($request['order_mail'])){
            $input['order_mail'] = 1;
        }else{
            $input['order_mail'] = 0;
        }
        if(isset($request['ticket_mail'])){
            $input['ticket_mail'] = 1;
        }else{
            $input['ticket_mail'] = 0;
        }
        if(isset($request['is_queue_enabled'])){
            $input['is_queue_enabled'] = 1;
        }else{
            $input['is_queue_enabled'] = 0;
        }
        if(isset($request['is_mail_verify'])){
            $input['is_mail_verify'] = 1;
        }else{
            $input['is_mail_verify'] = 0;
        }

        Setting::first()->update($input);
        return redirect()->back()->withSuccess(__('Data Updated Successfully.'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,EmailTemplate $template)
    {
        $template->update($request->all());
        return redirect()->route('back.setting.email')->withSuccess(__('Email Template Updated Successfully.'));
    }


}
