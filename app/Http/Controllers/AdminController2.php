<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Helpers\Helper;

use App\User;

use App\Provider;

use App\Document;

use App\ProviderDocument;

use App\ProviderRating;

use App\ChatMessage;

use App\Admin;

use App\ServiceType;

use App\Requests;

use App\UserRating;

use App\RequestPayment;

use App\ProviderService;

use App\Settings;

use Validator;

use Hash;

use Mail;

use DB;

use Auth;

use Redirect;

use Setting;

define('UNIT_DISTANCE', 'kms,miles');
if (!defined('USER')) define('USER',1);
if (!defined('PROVIDER')) define('PROVIDER',1);

if (!defined('NONE')) define('NONE', 0);

if (!defined('DEFAULT_FALSE')) define('DEFAULT_FALSE', 0);
if (!defined('DEFAULT_TRUE')) define('DEFAULT_TRUE', 1);

// Payment Constants
if (!defined('COD')) define('COD',   'cod');
if (!defined('PAYPAL')) define('PAYPAL', 'paypal');
if (!defined('CARD')) define('CARD',  'card');

if (!defined('REQUEST_NEW')) define('REQUEST_NEW',        0);
if (!defined('REQUEST_WAITING')) define('REQUEST_WAITING',      1);
if (!defined('REQUEST_INPROGRESS')) define('REQUEST_INPROGRESS',    2);
if (!defined('REQUEST_COMPLETE_PENDING')) define('REQUEST_COMPLETE_PENDING',  3);
if (!defined('REQUEST_RATING')) define('REQUEST_RATING',      4);
if (!defined('REQUEST_COMPLETED')) define('REQUEST_COMPLETED',      5);
if (!defined('REQUEST_CANCELLED')) define('REQUEST_CANCELLED',      6);
if (!defined('REQUEST_NO_PROVIDER_AVAILABLE')) define('REQUEST_NO_PROVIDER_AVAILABLE',7);
if (!defined('WAITING_FOR_PROVIDER_CONFRIMATION_COD')) define('WAITING_FOR_PROVIDER_CONFRIMATION_COD',  8);


// Only when manual request
if (!defined('REQUEST_REJECTED_BY_PROVIDER')) define('REQUEST_REJECTED_BY_PROVIDER', 9);

if (!defined('PROVIDER_NOT_AVAILABLE')) define('PROVIDER_NOT_AVAILABLE', 0);
if (!defined('PROVIDER_AVAILABLE')) define('PROVIDER_AVAILABLE', 1);

if (!defined('PROVIDER_NONE')) define('PROVIDER_NONE', 0);
if (!defined('PROVIDER_ACCEPTED')) define('PROVIDER_ACCEPTED', 1);
if (!defined('PROVIDER_STARTED')) define('PROVIDER_STARTED', 2);
if (!defined('PROVIDER_ARRIVED')) define('PROVIDER_ARRIVED', 3);
if (!defined('PROVIDER_SERVICE_STARTED')) define('PROVIDER_SERVICE_STARTED', 4);
if (!defined('PROVIDER_SERVICE_COMPLETED')) define('PROVIDER_SERVICE_COMPLETED', 5);
if (!defined('PROVIDER_RATED')) define('PROVIDER_RATED', 6);

if (!defined('REQUEST_META_NONE')) define('REQUEST_META_NONE',   0);
if (!defined('REQUEST_META_OFFERED')) define('REQUEST_META_OFFERED',   1);
if (!defined('REQUEST_META_TIMEDOUT')) define('REQUEST_META_TIMEDOUT', 2);
if (!defined('REQUEST_META_DECLINED')) define('REQUEST_META_DECLINED', 3);

if (!defined('RATINGS')) define('RATINGS', '0,1,2,3,4,5');

if (!defined('DEVICE_ANDROID')) define('DEVICE_ANDROID', 'android');
if (!defined('DEVICE_IOS')) define('DEVICE_IOS', 'ios');

if (!defined('WAITING_TO_RESPOND')) define('WAITING_TO_RESPOND', 1);
if (!defined('WAITING_TO_RESPOND_NORMAL')) define('WAITING_TO_RESPOND_NORMAL',0);

class AdminController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * Show the admin dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function dashboard() {

        $total = RequestPayment::sum('total');

        $paypal_total = RequestPayment::where('payment_mode','paypal')->sum('total');

        $card_total = RequestPayment::where('payment_mode','card')->sum('total');

        $cod_total = RequestPayment::where('payment_mode','cod')->sum('total');


        $total_requests = Requests::count();

        $completed = Requests::where('status','5')->count();

        $ongoing = Requests::where('status','4')->count();

        $cancelled = Requests::where('status','6')->count();


        $provider_reviews = UserRating::leftJoin('providers', 'user_ratings.provider_id', '=', 'providers.id')
                            ->leftJoin('users', 'user_ratings.user_id', '=', 'users.id')
                            ->select('user_ratings.id as review_id', 'user_ratings.rating', 'user_ratings.comment', 'users.first_name as user_first_name', 'users.last_name as user_last_name', 'providers.first_name as provider_first_name', 'providers.last_name as provider_last_name', 'users.id as user_id', 'users.picture as user_picture', 'providers.id as provider_id', 'user_ratings.created_at')
                            ->orderBy('user_ratings.created_at', 'desc')
                            ->get();

        $get_registers = get_register_count();
        $recent_users = get_recent_users();
        $total_revenue = 10;

        $view = last_days(10);

        return view('admin.dashboard')
                ->with('total' , $total)
                ->with('paypal_total' , $paypal_total)
                ->with('card_total' , $card_total)
                ->with('cod_total' , $cod_total)
                ->with('view' , $view)
                ->with('get_registers' , $get_registers)
                ->with('recent_users' , $recent_users)
                ->with('provider_reviews' , $provider_reviews)
                ->with('total_requests' , $total_requests)
                ->with('completed' , $completed)
                ->with('cancelled' , $cancelled)
                ->with('ongoing' , $ongoing)
                ->withPage('dashboard')
                ->with('sub_page','');
    }

    public function profile() {

        $admin = Admin::first();
        return view('admin.profile')->with('admin' , $admin)->withPage('profile')->with('sub_page','');
    }

    public function profile_process(Request $request) {

        $validator = Validator::make( $request->all(),array(
                'name' => 'max:255',
                'email' => 'email|max:255',
                'mobile' => 'digits_between:6,13',
                'address' => 'max:300',
                'id' => 'required|exists:admins,id'
            )
        );

        if($validator->fails()) {
            $error_messages = implode(',', $validator->messages()->all());
            return back()->with('flash_errors', $error_messages);
        } else {

            $admin = Admin::find($request->id);

            $admin->name = $request->has('name') ? $request->name : $admin->name;

            $admin->email = $request->has('email') ? $request->email : $admin->email;

            $admin->mobile = $request->has('mobile') ? $request->mobile : $admin->mobile;

            $admin->gender = $request->has('gender') ? $request->gender : $admin->gender;

            $admin->address = $request->has('address') ? $request->address : $admin->address;

            if($request->hasFile('picture')) {
                Helper::delete_picture($admin->picture);
                $admin->picture = Helper::normal_upload_picture($request->picture);
            }

            $admin->remember_token = Helper::generate_token();
            $admin->is_activated = 1;
            $admin->save();

            return back()->with('flash_success', Helper::tr('admin_not_profile'));

        }

    }

    public function change_password(Request $request) {

        $old_password = $request->old_password;
        $new_password = $request->password;
        $confirm_password = $request->confirm_password;

        $validator = Validator::make($request->all(), [
                'password' => 'required|min:6',
                'old_password' => 'required',
                'confirm_password' => 'required|min:6',
                'id' => 'required|exists:admins,id'
            ]);

        if($validator->fails()) {

            $error_messages = implode(',',$validator->messages()->all());

            return back()->with('flash_errors', $error_messages);

        } else {

            $admin = Admin::find($request->id);

            if(Hash::check($old_password,$admin->password))
            {
                $admin->password = Hash::make($new_password);
                $admin->save();

                return back()->with('flash_success', "Password Changed successfully");

            } else {
                return back()->with('flash_error', "Pasword is mismatched");
            }
        }

        $response = response()->json($response_array,$response_code);

        return $response;
    }

    public function payment()
    {
        $payment = DB::table('request_payments')
                    ->leftJoin('requests','requests.id','=','request_payments.request_id')
                    ->leftJoin('users','users.id','=','requests.user_id')
                    ->leftJoin('providers','providers.id','=','requests.confirmed_provider')
                    ->select('request_payments.*','users.first_name as user_first_name','users.last_name as user_last_name','providers.first_name as provider_first_name','providers.last_name as provider_last_name')
                    ->orderBy('created_at','desc')
                    ->get();

        return view('admin.payments')->with('payments',$payment)
                    ->withPage('payments')->with('sub_page','');
    }

    public function paymentSettings()
    {
        $settings = Settings::all();
        return view('admin.paymentSettings')->with('setting',$settings);
    }

    //User Functions

    public function users()
    {
        $user = User::orderBy('created_at' , 'desc')->get();

        return view('admin.users')->withPage('users')->with('sub_page','view-user')->with('users',$user);
    }

    public function add_user()
    {
        return view('admin.add-user')->withPage('users')->with('sub_page','add-user');
    }

    public function add_user_process(Request $request)
    {
            $first_name = $request->first_name;
            $last_name = $request->last_name;
            $email = $request->email;
            $mobile = $request->mobile;
            $gender = $request->gender;
            $picture = $request->file('picture');
            $address = $request->address;

            if($request->id != '')
            {
                $validator = Validator::make(
                    $request->all(),
                    array(
                        'first_name' => 'required|max:255',
                        'last_name' => 'required|max:255',
                        'email' => 'required|email|max:255',
                        'mobile' => 'required|digits_between:6,13',
                        'address' => 'required|max:300',

                    )
                );
            }
            else
            {
                $validator = Validator::make(
                    $request->all(),
                    array(
                        'first_name' => 'required|max:255',
                        'last_name' => 'required|max:255',
                        'email' => 'required|email|max:255|unique:users,email',
                        'mobile' => 'required|digits_between:6,13',
                        'address' => 'required|max:300',
                        'picture' => 'required|mimes:jpeg,jpg,bmp,png',

                    )
                );
            }

        if($validator->fails())
        {
            $error_messages = implode(',', $validator->messages()->all());
            return back()->with('flash_errors', $error_messages);
        }
        else
        {

                if($request->id != '')
                {
                    // Edit User
                    $user = User::find($request->id);
                    if($picture != ''){
                    $user->picture = Helper::upload_picture($picture);
                    }
                }
                else
                {
                    //Add New User
                    $user = new User;
                    $new_password = time();
                    $new_password .= rand();
                    $new_password = sha1($new_password);
                    $new_password = substr($new_password, 0, 8);
                    $user->password = Hash::make($new_password);
                    $user->picture = Helper::upload_picture($picture);
                }
                    $user->first_name = $first_name;
                    $user->last_name = $last_name;
                    $user->email = $email;
                    $user->mobile = $mobile;
                    $user->token = Helper::generate_token();
                    $user->token_expiry = Helper::generate_token_expiry();
                    $user->gender = $gender;
                    $user->is_activated = 1;
                    $user->is_approved = 1;
                    $user->payment_mode = 1;
                    $user->address = $address;


                    if($request->id == ''){
                    $email_data['first_name'] = $user->first_name;
                    $email_data['last_name'] = $user->last_name;
                    $email_data['password'] = $new_password;
                    $email_data['email'] = $user->email;

                    $subject = Helper::tr('user_welcome_title');
                    $page = "emails.admin.welcome";
                    $email = $user->email;
                    Helper::send_email($page,$subject,$email,$email_data);
                    }

                    $user->save();

                if($user)
                {
                    return back()->with('flash_success', tr('admin_not_user'));
                }
                else
                {
                    return back()->with('flash_error', tr('admin_not_error'));
                }

            }
    }

    public function edit_user(Request $request)
    {
        $user = User::find($request->id);
        return view('admin.add-user')->with('name', 'Edit User')->with('user',$user)
                      ->withPage('users')->with('sub_page','view-user');
    }

    public function delete_user(Request $request)
    {

        if($user = User::find($request->id))
        {

            $user = User::find($request->id)->delete();
        }

        if($user)
        {
            return back()->with('flash_success',tr('admin_not_user_del'));
        }
        else
        {
            return back()->with('flash_error',tr('admin_not_error'));
        }
    }


    //Provider Functions

    public function providers()
    {
        $subQuery = DB::table('requests')
                ->select(DB::raw('count(*)'))
                ->whereRaw('confirmed_provider = providers.id and status != 0');
        $subQuery1 = DB::table('requests')
                ->select(DB::raw('count(*)'))
                ->whereRaw('confirmed_provider = providers.id and status in (1,2,3,4,5)');
        $providers = DB::table('providers')
                ->select('providers.*', DB::raw("(" . $subQuery->toSql() . ") as 'total_requests'"), DB::raw("(" . $subQuery1->toSql() . ") as 'accepted_requests'"))
                ->orderBy('providers.id', 'DESC')
                ->get();

        return view('admin.providers')->with('providers',$providers)
                                      ->withPage('providers')->with('sub_page','view-provider');;
    }

    public function add_provider()
    {
        $service_type = ServiceType::all();
        return view('admin.add-provider')->with('service_types',$service_type)->withPage('providers')->with('sub_page','add-provider');
    }

    public function add_provider_process(Request $request)
    {
         $first_name = $request->first_name;
        $last_name = $request->last_name;
       $email = $request->email;
       $mobile = $request->mobile;
        $validator = Validator::make(
                    $request->all(),
                    array(
                        'first_name' => 'required|max:255',

                    )
                );
            if($validator->fails())
        {
            $error_messages = implode(',', $validator->messages()->all());
            return back()->with('flash_errors', $error_messages);
        }
        else
        {
            if($request->id != '')
            {
                $document = Provider::find($request->id);
                $message = tr('admin_not_doc_updated');
            }
            else
            {
                $document = new Provider;
                $message = tr('admin_not_doc');
            }
                $document->first_name = $request->first_name;
                 $document->last_name = $last_name;
              //  $document->email = $email;
               $document->mobile = $request->last_name2;
                 $document->save();
            
                

        if($document)
        {
            return back()->with('flash_success',$message);
        }
        else
        {
            return back()->with('flash_error',tr('admin_not_error'));
        }
        }
    }

    public function edit_provider(Request $request)
    {
        $provider = Provider::find($request->id);
        $check_provider_service = ProviderService::where('provider_id' , $request->id)->first();
        if($check_provider_service)
            $provider_type = $check_provider_service->service_type_id;
        else
            $provider_type = "";
        $service_types = ServiceType::all();
        return view('admin.add-provider')->with('name', 'Edit Provider')->with('provider_type',$provider_type)->with('provider',$provider)->with('service_types',$service_types)
                                  ->withPage('providers')->with('sub_page','view-provider');
    }

    public function provider_documents(Request $request) {
        $provider_id = $request->id;
        $provider = Provider::find($provider_id);
        $documents = Document::all();
        $provider_document = DB::table('provider_documents')
                            ->leftJoin('documents', 'provider_documents.document_id', '=', 'documents.id')
                            ->select('provider_documents.*', 'documents.name as document_name')
                            ->where('provider_id', $provider_id)->get();


        return view('admin.provider-document')
                        ->with('provider', $provider)
                        ->with('document', $documents)
                        ->with('documents', $provider_document)
                        ->withPage('providers')->with('sub_page','view-provider');
    }

    public function Provider_approve(Request $request)
    {
        $providers = Provider::orderBy('created_at' , 'asc')->get();
        $provider = Provider::find($request->id);
        $provider->is_approved = $request->status;
        $provider->save();
        if($request->status ==1)
        {
            $message = tr('admin_not_provider_approve');
        }
        else
        {
            $message = tr('admin_not_provider_decline');
        }
        return back()->with('flash_success', $message)->with('providers',$providers);
    }

    public function delete_provider(Request $request)
    {

        if($provider = Provider::find($request->id))
        {

            $provider = Provider::find($request->id)->delete();
        }

        if($provider)
        {
            return back()->with('flash_success',tr('admin_not_provider_del'));
        }
        else
        {
            return back()->with('flash_error',tr('admin_not_error'));
        }
    }

    public function settings()
    {
        $settings = Settings::all();
        switch (Setting::get('currency')) {
            case '$':
                $symbol = '$';
                $currency = 'US Dollar (USD)';
                break;

            case '₹':
                $symbol = '₹';
                $currency = 'Indian Rupee (INR)';
                break;
            case 'د.ك':
                $symbol = 'د.ك';
                $currency = 'Kuwaiti Dinar (KWD)';
                break;
            case 'د.ب':
                $symbol = 'د.ب';
                $currency = 'Bahraini Dinar (BHD)';
                break;
            case '﷼':
                $symbol = '﷼';
                $currency = 'Omani Rial (OMR)';
                break;
            case '£':
                $symbol = '£';
                $currency = 'Euro (EUR)';
                break;
            case '€':
                $symbol = '€';
                $currency = 'British Pound (GBP)';
                break;
            case 'ل.د':
                $symbol = 'ل.د';
                $currency = 'Libyan Dinar (LYD)';
                break;
            case 'B$':
                $symbol = 'B$';
                $currency = 'Bruneian Dollar (BND)';
                break;
            case 'S$':
                $symbol = 'S$';
                $currency = 'Singapore Dollar (SGD)';
                break;
            case 'AU$':
                $symbol = 'AU$';
                $currency = 'Australian Dollar (AUD)';
                break;
            case 'CHF':
                $symbol = 'CHF';
                $currency = 'Swiss Franc (CHF)';
                break;
            default:
                $symbol = '$';
                $currency = 'US Dollar (USD)';
                break;
        }
        return view('admin.settings')->with('symbol',$symbol)->with('currency',$currency)
                            ->withPage('settings')->with('sub_page','');
    }


    public function settings_process(Request $request)
    {
        $settings = Settings::all();
        foreach ($settings as $setting) {
            $key = $setting->key;

                $temp_setting = Settings::find($setting->id);

                if($temp_setting->key == 'site_icon'){
                    $site_icon = $request->file('site_icon');
                    if($site_icon == null)
                    {

                        $icon = $temp_setting->value;
                    }
                    else
                    {

                        $icon = Helper::upload_picture($site_icon);

                    }
                    $temp_setting->value = $icon;
                    $temp_setting->save();
                }

               else if($temp_setting->key == 'site_logo'){
                    $picture = $request->file('picture');
                    if($picture == null){
                    $logo = $temp_setting->value;
                    }
                    else
                    {
                        $logo = Helper::upload_picture($picture);
                    }
                    $temp_setting->value = $logo;
                    $temp_setting->save();
                }
                else if($temp_setting->key == 'mail_logo'){
                    $picture = $request->file('email_logo');
                    if($picture == null){
                    $logo = $temp_setting->value;
                    }
                    else
                    {
                        $logo = Helper::upload_picture($picture);
                    }
                    $temp_setting->value = $logo;
                    $temp_setting->save();
                }

                 else if($temp_setting->key == 'card'){
                      if($request->$key==1)
                      {
                          $temp_setting->value   = 1;

                      }
                      else
                      {
                          $temp_setting->value = 0;
                      }
                      $temp_setting->save();
                  }
                 else if($temp_setting->key == 'paypal'){
                      if($request->$key==1)
                      {
                          $temp_setting->value   = 1;
                      }
                      else
                      {
                          $temp_setting->value = 0;
                      }
                      $temp_setting->save();
                  }
                  else if($temp_setting->key == 'manual_request'){
                      if($request->$key==1)
                      {
                          $temp_setting->value   = 1;
                      }
                      else
                      {
                          $temp_setting->value = 0;
                      }
                      $temp_setting->save();
                  }
                  else if($request->$key!=''){
                $temp_setting->value = $request->$key;
                $temp_setting->save();
                }
            }
        return back()->with('setting', $settings)->with('flash_success','Settings Updated Successfully');
    }

    //Documents

    public function documents()
    {
        $document = Document::orderBy('created_at' , 'asc')->get();
        return view('admin.documents')->with('documents',$document)
                    ->withPage('documents')->with('sub_page','view-document');
    }

    public function document_edit(Request $request)
    {
        $document = Document::find($request->id);
        return view('admin.add-documents')->with('name', 'Edit Document')->with('document',$document)
                    ->withPage('documents')->with('sub_page','view-document');
    }

    public function add_document()
    {
        return view('admin.add-documents')->withPage('documents')->with('sub_page','add-document');
    }

    public function add_document_process(Request $request)
    {

                $validator = Validator::make(
                    $request->all(),
                    array(
                        'document_name' => 'required|max:255',

                    )
                );
            if($validator->fails())
        {
            $error_messages = implode(',', $validator->messages()->all());
            return back()->with('flash_errors', $error_messages);
        }
        else
        {
            if($request->id != '')
            {
                $document = Document::find($request->id);
                $message = tr('admin_not_doc_updated');
            }
            else
            {
                $document = new Document;
                $message = tr('admin_not_doc');
            }
                $document->name = $request->document_name;
                $document->save();

        if($document)
        {
            return back()->with('flash_success',$message);
        }
        else
        {
            return back()->with('flash_error',tr('admin_not_error'));
        }
        }
    }

    public function delete_document(Request $request)
    {

        $document = Document::find($request->id)->delete();

        if($document)
        {
            return back()->with('flash_success',tr('admin_not_doc_del'));
        }
        else
        {
            return back()->with('flash_error',tr('admin_not_error'));
        }
    }

    //Service Types

    public function service_types()
    {
        $service = ServiceType::orderBy('created_at' , 'asc')->get();
        return view('admin.service_types')->with('services',$service)->withPage('service_types')->with('sub_page','view-service');
    }

    public function edit_service(Request $request)
    {
        $service = ServiceType::find($request->id);
        return view('admin.add_service_types')->with('name', 'Edit Service Types')->with('service',$service)
                    ->withPage('service_types')->with('sub_page','view-service');
    }

    public function add_service_type()
    {
        return view('admin.add_service_types')->withPage('service_types')->with('sub_page','add-service');
    }

    public function add_service_process(Request $request)
    {
      $picture = $request->file('picture');
      $namapaket = $request->service_name;
      $jumlahseat = $request->number_seat;
      $harga      = $request->price_per_min;
      $hrgperkm = $request->price_per_unit_distance;
      $tipejarak = $request->distance_unit;
      $minimalfare = $request->min_fare;
      
      
      if($request->id != '')
      {
          $validator = Validator::make(
              $request->all(),
              array(
                  'service_name' => 'required|max:255',
                  'number_seat' => 'required|max:10',
                  'price_per_min' => 'required',
                  'price_per_unit_distance' => 'required',
                  'distance_unit' => 'required|in:'.UNIT_DISTANCE,

              )
          );
      }
      else {
        $validator = Validator::make(
              $request->all(),
              array(
                  'service_name' => 'required|max:255',
                  'number_seat' => 'required|max:10',
                  'price_per_min' => 'required',
                  'price_per_unit_distance' => 'required',
                  'distance_unit' => 'required|in:'.UNIT_DISTANCE,

              )
          );
      }
        if($validator->fails())
        {
            $error_messages = implode(',', $validator->messages()->all());
            return back()->with('flash_errors', $error_messages);
        }
        else
        {
           
	         if($request->id != '')
              {
	              $service1 = ServiceType::find($request->id);
                  $message = tr('admin_not_doc_updated');
           }else{
	           $service1 = new ServiceType;
	        $message = tr('admin_not_doc');
            }
	        $service1->picture = Helper::upload_picture($picture);   
	                 
            $service1->status = 0;
            $service1->name = $namapaket;
            $service1->provider_name = $namapaket;
            $service1->number_seat = $jumlahseat;
            $service1->price_per_min = $harga ;
            $service1->price_per_unit_distance = $hrgperkm;
            $service1->distance_unit = $tipejarak;
            $service1->min_fare = $minimalfare;
              
            $service1->save();
        

        if($service1)
        {
            return back()->with('flash_success',$message);
        }
        else
        {
            return back()->with('flash_error',tr('admin_not_error'));
        }
        }
    }

    public function delete_service(Request $request)
    {

        $service = ServiceType::find($request->id)->delete();

        if($service)
        {
            return back()->with('flash_success',tr('admin_not_st_del'));
        }
        else
        {
            return back()->with('flash_error',"Something went Wrong");
        }
    }

    public function provider_reviews()
    {
            $provider_reviews = DB::table('provider_ratings')
                ->leftJoin('providers', 'provider_ratings.provider_id', '=', 'providers.id')
                ->leftJoin('users', 'provider_ratings.user_id', '=', 'users.id')
                ->select('provider_ratings.id as review_id', 'provider_ratings.rating', 'provider_ratings.comment', 'users.first_name as user_first_name', 'users.last_name as user_last_name', 'providers.first_name as provider_first_name', 'providers.last_name as provider_last_name', 'users.id as user_id', 'providers.id as provider_id', 'provider_ratings.created_at')
                ->orderBy('provider_ratings.id', 'DESC')
                ->get();


            return view('admin.reviews')
                        ->with('reviews', $provider_reviews)
                        ->withPage('rating_review')->with('sub_page','provider-review');
    }

    public function user_reviews()
    {

            $user_reviews = DB::table('user_ratings')
                ->leftJoin('providers', 'user_ratings.provider_id', '=', 'providers.id')
                ->leftJoin('users', 'user_ratings.user_id', '=', 'users.id')
                ->select('user_ratings.id as review_id', 'user_ratings.rating', 'user_ratings.comment', 'users.first_name as user_first_name', 'users.last_name as user_last_name', 'providers.first_name as provider_first_name', 'providers.last_name as provider_last_name', 'users.id as user_id', 'providers.id as provider_id', 'user_ratings.created_at')
                ->orderBy('user_ratings.id', 'ASC')
                ->get();
            return view('admin.reviews')->with('name', 'User')->with('reviews', $user_reviews)
                      ->withPage('rating_review')->with('sub_page','user-review');;
    }

    public function delete_user_reviews(Request $request) {
        $user = UserRating::find($request->id)->delete();
        return back()->with('flash_success', tr('admin_not_ur_del'));
    }

    public function delete_provider_reviews(Request $request) {
        $provider = ProviderRating::find($request->id)->delete();
        return back()->with('flash_success', tr('admin_not_pr_del'));
    }

    public function user_history(Request $request)
    {
        $requests = DB::table('requests')
                ->Where('user_id',$request->id)
                ->leftJoin('providers', 'requests.confirmed_provider', '=', 'providers.id')
                ->leftJoin('users', 'requests.user_id', '=', 'users.id')
                ->leftJoin('request_payments', 'requests.id', '=', 'request_payments.request_id')
                ->select('users.first_name as user_first_name', 'users.last_name as user_last_name', 'providers.first_name as provider_first_name', 'providers.last_name as provider_last_name', 'users.id as user_id', 'providers.id as provider_id', 'requests.is_paid',  'requests.id as id', 'requests.created_at as date', 'requests.confirmed_provider', 'requests.status', 'requests.provider_status', 'requests.amount', 'request_payments.payment_mode as payment_mode', 'request_payments.status as payment_status')
                ->orderBy('requests.created_at', 'ASC')
                ->get();
        return view('admin.request')->with('requests', $requests)->withPage('users')->with('sub_page','view-user')
              ->with('name','view_user_history');
    }

    public function provider_history(Request $request)
    {
        $requests = DB::table('requests')
                ->Where('confirmed_provider',$request->id)
                ->leftJoin('providers', 'requests.confirmed_provider', '=', 'providers.id')
                ->leftJoin('users', 'requests.user_id', '=', 'users.id')
                ->leftJoin('request_payments', 'requests.id', '=', 'request_payments.request_id')
                ->select('users.first_name as user_first_name', 'users.last_name as user_last_name', 'providers.first_name as provider_first_name', 'providers.last_name as provider_last_name', 'users.id as user_id', 'providers.id as provider_id', 'requests.is_paid',  'requests.id as id', 'requests.created_at as date', 'requests.confirmed_provider', 'requests.status', 'requests.provider_status', 'requests.amount', 'request_payments.payment_mode as payment_mode', 'request_payments.status as payment_status')
                ->orderBy('requests.created_at', 'DESC')
                ->get();
        return view('admin.request')->with('requests', $requests)->withPage('providers')->with('sub_page','view-provider')
              ->with('name','view_provider_history');
    }

    public function requests()
    {
        $requests = DB::table('requests')
                ->leftJoin('providers', 'requests.confirmed_provider', '=', 'providers.id')
                ->leftJoin('users', 'requests.user_id', '=', 'users.id')
                ->leftJoin('request_payments', 'requests.id', '=', 'request_payments.request_id')
                ->select('users.first_name as user_first_name', 'users.last_name as user_last_name', 'providers.first_name as provider_first_name', 'providers.last_name as provider_last_name', 'users.id as user_id', 'providers.id as provider_id', 'requests.is_paid',  'requests.id as id', 'requests.created_at as date', 'requests.confirmed_provider', 'requests.status', 'requests.provider_status', 'request_payments.total as amount', 'request_payments.payment_mode as payment_mode', 'request_payments.status as payment_status')
                ->orderBy('requests.created_at', 'desc')
                ->get();
        return view('admin.request')->with('requests', $requests)->withPage('requests')->with('sub_page','');
    }
    public function view_request(Request $request)
    {

        $requests = DB::table('requests')
                ->where('requests.id',$request->id)
                ->leftJoin('providers', 'requests.confirmed_provider', '=', 'providers.id')
                ->leftJoin('users', 'requests.user_id', '=', 'users.id')
                ->leftJoin('request_payments', 'requests.id', '=', 'request_payments.request_id')
                ->select('users.first_name as user_first_name', 'users.last_name as user_last_name', 'providers.first_name as provider_first_name', 'providers.last_name as provider_last_name', 'users.id as user_id', 'providers.id as provider_id', 'requests.is_paid',  'requests.id as id', 'requests.created_at as date', 'requests.confirmed_provider', 'requests.status', 'requests.provider_status', 'requests.amount', 'request_payments.payment_mode as payment_mode', 'request_payments.status as payment_status', 'request_payments.total_time as total_time','request_payments.base_price as base_price', 'request_payments.time_price as time_price', 'request_payments.tax_price as tax', 'request_payments.total as total_amount', 'requests.s_latitude as latitude', 'requests.s_longitude as longitude','requests.start_time','requests.end_time','requests.s_address as request_address','requests.before_image' , 'requests.after_image')
                ->first();
        return view('admin.request-view')->with('page' ,'requests')->with('sub-page' , "")->with('request', $requests);
    }

    public function mapview()
    {
        // dd(\Auth::guard('admin')->user());
        $Providers = Provider::all();
        $page = 'maps';
        $sub_page = 'provider-map';
        return view('admin.map', compact('Providers','page','sub_page'));
    }

    public function usermapview()
    {
        // dd(\Auth::guard('admin')->user());
        $Users = User::where('latitude', '!=', '0')->where('longitude', '!=', '0')->get();
        $page = 'maps';
        $sub_page = 'user-map';
        return view('admin.user-map', compact('Users','page','sub_page'));

    }

    public function help()
    {
        return view('admin.help');
    }

    public function provider_details(Request $request)
    {
        $provider = Provider::find($request->id);
        $avg_rev = ProviderRating::where('provider_id',$request->id)->avg('rating');


        if($provider) {
            $service = "";
            $service_type = ProviderService::where('provider_id' ,$provider->id)
                                ->leftJoin('service_types' ,'provider_services.service_type_id','=' , 'service_types.id')
                                ->first();
            if($service_type) {
                $service = $service_type->name;
            }
            return view('admin.provider-details')->with('provider' , $provider)->withService($service)->with('review',$avg_rev)
                      ->withPage('maps')->with('sub_page','provider-map');
        } else {
            return back()->with('error' , "Provider details not found");
        }
    }

    public function user_details(Request $request)
    {
        $user = User::find($request->id);
        $avg_rev = UserRating::where('user_id',$request->id)->avg('rating');

        if($user) {
          if($request->option !='')
            return view('admin.user-details')->with('user' , $user)->with('review',$avg_rev)
                      ->withPage('users')->with('sub_page','view-user')->with('name',$request->option);
          else
            return view('admin.user-details')->with('user' , $user)->with('review',$avg_rev)
                        ->withPage('maps')->with('sub_page','user-map');
        } else {
            return back()->with('error' , "User details not found");
        }
    }


}
