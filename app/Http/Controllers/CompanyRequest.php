<?php

namespace App\Http\Controllers;

use App\Mail\CompanyRegistration;
use App\Models\institutions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CompanyRequest extends Controller
{
    public function index(){

        return view('saccossRegistration');
    }

    public function create(Request $request){

       $request->validate([
            'admin_email'=>'required',
            'manager_email'=>'required',
            'phone_number'=>'required|numeric',
            'tin_number'=>'required',
            'tcdc_form'=>'required',
            'microfinance_license'=>'required',
            'region'=>'required|string',
            'name'=>'required|string',
            'wilaya'=>'required|string',
       ]);

//        'microfinance_licence'=>'required|mimes:jpeg,png,pdf'

        // Generate a unique filename
        if($request->has('tcdc_form')){


        $filename = time().'_'.$request->file('tcdc_form')->getClientOriginalName();

        // Store the file in the 'public' disk under the 'Saccoss-request' directory
        $request->file('tcdc_form')->storeAs('Saccoss-request', $filename, 'public');

        // Save the file path
        $file1Path = 'Saccoss-request/' . $filename;
        }
        else {
            dd("no file found");
        }

        if($request->has('microfinance_license')){

// Generate a unique filename
        $filename2 = time() . '_' . $request->file('microfinance_license')->getClientOriginalName();

        // Store the file in the 'public' disk under the 'Saccoss-request' directory
        $request->file('microfinance_license')->storeAs('Saccoss-request', $filename2, 'public');

        // Save the file path
        $file2Path = 'Saccoss-request/' . $filename2;
        }
        else{
            dd('FILE TWO NOT FOUND');
        }

          DB::table('institutions')->insert(['admin_email'=>$request->post('admin_email'),
              'manager_email'=>$request->post('manager_email'),
              'phone_number'=>$request->post('phone_number'),
              'tin_number'=>$request->post('tin_number'),
              'tcdc_form'=>$file1Path,
              'status'=>'PENDING',
              'microfinance_license'=>$file2Path ,
              'name'=>$request->input('name'),
              'wilaya'=>$request->input('wilaya'),
              'region'=>$request->input('region')
              ]);


// send mail notification;

          Mail::to('percyegno@gmail.com')->send(new CompanyRegistration('company registration'));

          session()->flash('message','Request has been sent successfully');
          return back();

//        required|mimes:jpeg,png,pdf|max:2048



    }
}
