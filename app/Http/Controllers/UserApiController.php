<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Helpers\Helper;

use Log;

use Hash;

use Validator;

use File;

use DB;

use App\User;

use App\ProviderService;

use App\Requests;

use App\Admin;

use App\RequestsMeta;

use App\ServiceType;

use App\Provider;

use App\Settings;

use App\RequestPayment;

use App\UserRating;

use App\Userwallet;

use App\ProviderRating;

use App\Cards;

use App\ChatMessage;

use App\Jobs\NormalPushNotification;

use App\Jobs\sendPushNotification;

use App\PromoCode;

//Braintree Classes
use Braintree_Transaction;
use Braintree_Customer;
use Braintree_WebhookNotification;
use Braintree_Subscription;
use Braintree_CreditCard;
use Braintree_PaymentMethod;
use Braintree_ClientToken;


if (!defined('USER')) define('USER',1);
if (!defined('PROVIDER')) define('PROVIDER',1);

if (!defined('NONE')) define('NONE', 0);

if (!defined('DEFAULT_FALSE')) define('DEFAULT_FALSE', 0);
if (!defined('DEFAULT_TRUE')) define('DEFAULT_TRUE', 1);

// Payment Constants
if (!defined('COD')) define('COD',   'cod');
if (!defined('PAYPAL')) define('PAYPAL', 'paypal');
if (!defined('CARD')) define('CARD',  'card');
if (!defined('WALLET')) define('WALLET',  'wallet');

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


class UserApiController extends Controller
{

    public function __construct(Request $request)
    {
        $this->middleware('UserApiVal' , array('except' => ['register' , 'login' , 'forgot_password']));

    }
    public function register(Request $request)
    {
        $response_array = array();
        $operation = false;
        $new_user = DEFAULT_TRUE;

        // validate basic field

        $basicValidator = Validator::make(
            $request->all(),
            array(
                'device_type' => 'required|in:'.DEVICE_ANDROID.','.DEVICE_IOS,
                'device_token' => 'required',
                'login_by' => 'required|in:manual,facebook,google',
            )
        );

        if($basicValidator->fails()) {

            $error_messages = implode(',', $basicValidator->messages()->all());
            $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=> $error_messages);
            Log::info('Registration basic validation failed');

        } else {

            $login_by = $request->login_by;
            $allowedSocialLogin = array('facebook','google');

            // check login-by

            if(in_array($login_by,$allowedSocialLogin)){

                // validate social registration fields

                $socialValidator = Validator::make(
                            $request->all(),
                            array(
                                'social_unique_id' => 'required',
                                'first_name' => 'required|max:255',
                                'last_name' => 'max:255',
                                'email' => 'required|email|max:255',
                                 'mobile' => 'required|min:13',
                                'picture' => 'mimes:jpeg,jpg,bmp,png',
                                'gender' => 'in:male,female,others',
                            )
                        );

                if($socialValidator->fails()) {

                    $error_messages = implode(',', $socialValidator->messages()->all());
                    $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=> $error_messages);

                    Log::info('Registration social validation failed');

                }else {

                    $check_social_user = User::where('email' , $request->email)->first();

                    if($check_social_user) {
                        $new_user = DEFAULT_FALSE;
                    }

                    Log::info('Registration passed social validation');
                    $operation = true;
                }

            } else {

                // Validate manual registration fields

                $manualValidator = Validator::make(
                    $request->all(),
                    array(
                        'first_name' => 'required|max:255',
                        'last_name' => 'required|max:255',
                        'email' => 'required|email|max:255',
                         'mobile' => 'required|min:13',
                        'password' => 'required|min:6',
                        'picture' => 'mimes:jpeg,jpg,bmp,png',
                    )
                );

                // validate email existence

                $emailValidator = Validator::make(
                    $request->all(),
                    array(
                        'email' => 'unique:users,email',
                    )
                );

                if($manualValidator->fails()) {

                    $error_messages = implode(',', $manualValidator->messages()->all());
                    $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=> $error_messages);
                    Log::info('Registration manual validation failed');

                } elseif($emailValidator->fails()) {

                    $error_messages = implode(',', $emailValidator->messages()->all());
                    $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=> $error_messages);
                    Log::info('Registration manual email validation failed');

                } else {
                    Log::info('Registration passed manual validation');
                    $operation = true;
                }

            }

            if($operation) {

                // Creating the user
                if($new_user) {
                    $user = new User;
                    // Settings table - COD Check is enabled
                    if(Settings::where('key' , COD)->where('value' , DEFAULT_TRUE)->first()) {
                        // Save the default payment method
                        $user->payment_mode = COD;
                    }

                } else {
                    $user = $check_social_user;
                }

                if($request->has('first_name')) {
                    $user->first_name = $request->first_name;
                }

                if($request->has('last_name')) {
                    $user->last_name = $request->last_name;
                }

                if($request->has('timezone')){
                    $user->timezone = $request->timezone;
                }

                if($request->has('currency_code')){
                    $user->currency_code = $request->currency_code;
                }

                if($request->has('country')){
                    $user->country = $request->country;
                }

                if($request->has('email')) {
                    $user->email = $request->email;
                }

                if($request->has('mobile')) {
                    $user->mobile = $request->mobile;
                }

                if($request->has('password'))
                    $user->password = Hash::make($request->password);

                $user->gender = $request->has('gender') ? $request->gender : "male";

                $user->token = Helper::generate_token();
                $user->token_expiry = Helper::generate_token_expiry();

                $check_device_exist = User::where('device_token', $request->device_token)->first();

                if($check_device_exist){
                    $check_device_exist->device_token = "";
                    $check_device_exist->save();
                }

                $user->device_token = $request->has('device_token') ? $request->device_token : "";
                $user->device_type = $request->has('device_type') ? $request->device_type : "";
                $user->login_by = $request->has('login_by') ? $request->login_by : "manual";
                $user->social_unique_id = $request->has('social_unique_id') ? $request->social_unique_id : '';

                // Upload picture
                if($request->hasFile('picture')) {
                    $user->picture = Helper::upload_picture($request->file('picture'));
                }

                $user->is_activated = 1;
                $user->is_approved = 1;

                $user->save();
                $user->password = $request->password;
                $payment_mode_status = $user->payment_mode ? $user->payment_mode : 0;

                // Send welcome email to the new user:
                if($new_user) {
                    $subject = Helper::tr('user_welcome_title');
                    $email_data = $user;
                    $page = "emails.user.welcome";
                    $email = $user->email;
                    Helper::send_email($page,$subject,$email,$email_data);

                    register_mobile($user->device_type);
                }

                // Response with registered user details:

                $response_array = array(
                    'success' => true,
                    'id' => $user->id,
                    'name' => $user->first_name.' '.$user->last_name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'mobile' => $user->mobile,
                    'gender' => $user->gender,
                    'email' => $user->email,
                    'picture' => $user->picture,
                    'token' => $user->token,
                    'token_expiry' => $user->token_expiry,
                    'login_by' => $user->login_by,
                    'social_unique_id' => $user->social_unique_id,
                    'payment_mode_status' =>  $payment_mode_status,
                    'currency_code' => $user->currency_code,
                    'country' => $user->country,
                    'timezone' => $user->timezone,
                );

                $response_array = Helper::null_safe($response_array);

                Log::info('Registration completed');

            }

        }

        $response = response()->json($response_array, 200);
        return $response;
    }

    public function login(Request $request)
    {
        $response_array = array();
        $operation = false;

        $basicValidator = Validator::make(
            $request->all(),
            array(
                'device_token' => 'required',
                'device_type' => 'required|in:'.DEVICE_ANDROID.','.DEVICE_IOS,
                'login_by' => 'required|in:manual,facebook,google',
            )
        );

        if($basicValidator->fails()){
            $error_messages = implode(',',$basicValidator->messages()->all());
            $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=> $error_messages);
        }else{

            $login_by = $request->login_by;
            if($login_by == 'manual'){

                /*validate manual login fields*/
                $manualValidator = Validator::make(
                    $request->all(),
                    array(
                        'email' => 'required|email',
                        'password' => 'required',
                    )
                );

                if ($manualValidator->fails()) {
                    $error_messages = implode(',',$manualValidator->messages()->all());
                    $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=> $error_messages);
                } else {

                    $email = $request->email;
                    $password = $request->password;

                    // Validate the user credentials
                    if($user = User::where('email', '=', $email)->first()){
                        if($user->is_activated) {
                            if(Hash::check($password, $user->password)){

                                /*manual login success*/
                                $operation = true;

                            }else{
                                $response_array = array( 'success' => false, 'error' => Helper::get_error_message(105), 'error_code' => 105 );
                            }
                        } else {
                            $response_array = array('success' => false , 'error' => Helper::get_error_message(144),'error_code' => 144);
                        }

                    } else {
                        $response_array = array( 'success' => false, 'error' => Helper::get_error_message(100), 'error_code' => 100 );
                    }
                }

            } else {
                /*validate social login fields*/
                $socialValidator = Validator::make(
                    $request->all(),
                    array(
                        'social_unique_id' => 'required',
                    )
                );

                if ($socialValidator->fails()) {
                    $error_messages = implode(',',$socialValidator->messages()->all());
                    $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=> $error_messages);
                } else {
                    $social_unique_id = $request->social_unique_id;
                    if ($user = User::where('social_unique_id', '=', $social_unique_id)->first()) {
                        if($user->is_activated) {
                            /*social login success*/
                            $operation = true;
                        } else {
                            $response_array = array('success' => false , 'error' => Helper::get_error_message(144),'error_code' => 144);
                        }

                    }else{
                        $response_array = array('success' => false, 'error' => Helper::get_error_message(125), 'error_code' => 125);
                    }

                }
            }

            if($operation){

                $device_token = $request->device_token;
                $device_type = $request->device_type;

                // Generate new tokens
                $user->token = Helper::generate_token();
                $user->token_expiry = Helper::generate_token_expiry();

                // Save device details
                $user->device_token = $device_token;
                $user->device_type = $device_type;
                $user->login_by = $login_by;

                $user->save();

                $payment_mode_status = $user->payment_mode ? $user->payment_mode : 0;

                // Respond with user details

                $response_array = array(
                    'success' => true,
                    'id' => $user->id,
                    'name' => $user->first_name.' '.$user->last_name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'mobile' => $user->mobile,
                    'email' => $user->email,
                    'gender' => $user->gender,
                    'picture' => $user->picture,
                    'token' => $user->token,
                    'token_expiry' => $user->token_expiry,
                    'login_by' => $user->login_by,
                    'social_unique_id' => $user->social_unique_id,
                    'payment_mode_status' => $payment_mode_status,
                    'currency_code' => $user->currency_code,
                    'country' => $user->country,
                    'timezone' => $user->timezone,
                );

                $response_array = Helper::null_safe($response_array);
            }
        }

        $response = response()->json($response_array, 200);
        return $response;
    }

    public function forgot_password(Request $request)
    {
        $email =$request->email;
        // Validate the email field
        $validator = Validator::make(
            $request->all(),
            array(
                'email' => 'required|email|exists:users,email',
            )
        );
        if ($validator->fails()) {
            $error_messages = implode(',',$validator->messages()->all());
            $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=> $error_messages);
        }
        else
        {
            $user = User::where('email' , $email)->first();
            $new_password = Helper::generate_password();
            $user->password = Hash::make($new_password);

            $email_data = array();
            $subject = Helper::tr('user_forgot_email_title');
            $email_data['password']  = $new_password;
            $email_data['user']  = $user;
            $page = "emails.user.forgot_password";
            $email_send = Helper::send_email($page,$subject,$user->email,$email_data);

            $response_array['success'] = true;
            $response_array['message'] = Helper::get_message(106);
            $user->save();
        }

        $response = response()->json($response_array, 200);
        return $response;
    }

    public function change_password(Request $request) {

        $old_password = $request->old_password;
        $new_password = $request->password;
        $confirm_password = $request->confirm_password;

        $validator = Validator::make($request->all(), [
                'password' => 'required|confirmed',
                'old_password' => 'required',
            ]);

        if($validator->fails()) {
            $error_messages = implode(',',$validator->messages()->all());
            $response_array = array('success' => false, 'error' => 'Invalid Input', 'error_code' => 401, 'error_messages' => $error_messages );
        } else {
            $user = User::find($request->id);

            if(Hash::check($old_password,$user->password))
            {
                $user->password = Hash::make($new_password);
                $user->save();

                $response_array = Helper::null_safe(array('success' => true , 'message' => Helper::get_message(102)));

            } else {
                $response_array = array('success' => false , 'error' => Helper::get_error_message(131), 'error_code' => 131);
            }

        }

        $response = response()->json($response_array,200);
        return $response;

    }

    public function user_details(Request $request)
    {
        $user = User::find($request->id);

        $response_array = array(
            'success' => true,
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'mobile' => $user->mobile,
            'gender' => $user->gender,
            'email' => $user->email,
            'picture' => $user->picture,
            'token' => $user->token,
            'token_expiry' => $user->token_expiry,
            'login_by' => $user->login_by,
            'social_unique_id' => $user->social_unique_id
        );
        $response = response()->json(Helper::null_safe($response_array), 200);
        return $response;
    }

    public function update_profile(Request $request)
    {
        $user_id = $request->id;

        $validator = Validator::make(
            $request->all(),
            array(
                'id' => 'required',
                'first_name' => 'required|max:255',
                'last_name' => 'required|max:255',
                'email' => 'email|unique:users,email,'.$user_id.'|max:255',
                'mobile' => 'required|digits_between:6,13',
                'picture' => 'mimes:jpeg,bmp,png',
                'gender' => 'in:male,female,others',
                'device_token' => '',
            ));

        if ($validator->fails()) {
            // Error messages added in response for debugging
            $error_messages = implode(',',$validator->messages()->all());
            $response_array = array(
                    'success' => false,
                    'error' => Helper::get_error_message(101),
                    'error_code' => 101,
                    'error_messages' => $error_messages
            );
        } else {

            $name = $request->name;
            $email = $request->email;
            $mobile = $request->mobile;
            $picture = $request->file('picture');

            $user = User::find($user_id);
            if($request->has('first_name')) {
                $user->first_name = $request->first_name;
            }
            if($request->has('last_name')) {
                $user->last_name = $request->last_name;
            }
            if($request->has('email')) {
                $user->email = $email;
            }
            if ($mobile != "")
                $user->mobile = $mobile;
            // Upload picture
            if ($picture != "") {
                Helper::delete_picture($user->picture); // Delete the old pic
                $user->picture = Helper::upload_picture($picture);
            }
            if($request->has('gender')) {
                $user->gender = $request->gender;
            }

            // Generate new tokens
            // $user->token = Helper::generate_token();
            // $user->token_expiry = Helper::generate_token_expiry();

            $user->save();

            $payment_mode_status = $user->payment_mode ? $user->payment_mode : "";

            $response_array = array(
                'success' => true,
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'mobile' => $user->mobile,
                'gender' => $user->gender,
                'email' => $user->email,
                'picture' => $user->picture,
                'token' => $user->token,
                'token_expiry' => $user->token_expiry,
                'login_by' => $user->login_by,
                'social_unique_id' => $user->social_unique_id,
                'payment_mode_status' => $payment_mode_status
            );
            $response_array = Helper::null_safe($response_array);
        }

        $response = response()->json($response_array, 200);
        return $response;
    }

    public function token_renew(Request $request)
    {

        $user_id = $request->id;

        $token_refresh = $request->token;

        // Check if refresher token is valid

        if ($user = User::where('id', '=', $user_id)->where('token', '=', $token_refresh)->first()) {

            // Generate new tokens
            $user->token = Helper::generate_token();
            $user->token_expiry = Helper::generate_token_expiry();

            $user->save();
            $response_array = Helper::null_safe(array('success' => true,'token' => $user->token));
        } else {
            $response_array = array('success' => false,'error' => Helper::get_error_message(115),'error_code' => 115);
        }

        $response = response()->json($response_array, 200);
        return $response;

    }

    public function service_list(Request $request) {

	    $origins = $request->origins;
	    $destinations = $request->destinations;
	    $mode = $request->mode;
	    $language = $request->language;
	    $sensor = $request->sensor;
	    
	    
	   // $cekjarak = "http://maps.googleapis.com/maps/api/distancematrix/json?origins=$origins&destinations=$destinations&mode=driving&language=en-EN&sensor=false";
	    
	$url = "http://maps.googleapis.com/maps/api/distancematrix/json?origins=".$origins."&destinations=".$destinations."&mode=driving&language=en-EN&sensor=false";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $response = curl_exec($ch);
    curl_close($ch);
    $response_a = json_decode($response, true);
    $distance2 = $response_a['rows'][0]['elements'][0]['distance']['value']/1000;
    $time = $response_a['rows'][0]['elements'][0]['duration']['value']/60;

            $distance = json_decode(round($distance2,0));
            
        if($serviceList = ServiceType::all()) {
            $response_array = Helper::null_safe(array('success' => true,'services' => $serviceList, 'datajarak' => $distance));
        } else {
            $response_array = array('success' => false,'error' => Helper::get_error_message(115),'error_code' => 115);
        }
        $response = response()->json($response_array, 200);
        return $response;

    }


    public function single_service(Request $request) {

        $validator = Validator::make(
                $request->all(),
                array(
                    'service_id' => 'required',
                ));

        if ($validator->fails())
        {
            $error_messages = implode(',', $validator->messages()->all());
            $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages' => $error_messages);
        }
        else
        {
            if($serviceList = ServiceType::find($request->id))
            {
                $providerList = ProviderService::where('service_type_id',$request->id)->get();
                $provider_details = array();
                $provider_details_data = array();

                foreach ($providerList as $provider_details)
                {
                    $provider = Provider::find($provider_details->id);
                    $provider_details['id'] = $provider->id;
                    $provider_details['name'] = $provider->name;
                    $provider_details['latiude'] = $provider->latiude;
                    $provider_details['longitude'] = $provider->longitude;
                    $provider_details_data[] = $provider_details;
                    $provider_details = array();
                }
                $response_array = array('success' => true,'provider_details' => $provider_details_data);
                $response_array = Helper::null_safe($response_array);
            } else {
                $response_array = array('success' => false,'error' => Helper::get_error_message(115),'error_code' => 115);
            }
        }

        $response = response()->json($response_array, 200);
        return $response;
    }

    public function guest_provider_list(Request $request) {
        $validator = Validator::make(
            $request->all(),
            array(
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'service_id' => 'exists:service_types,id',
            ));

        if ($validator->fails()) {
            $error_messages = implode(',',$validator->messages()->all());
            $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages' => $error_messages);
        } else {
            $latitude = $request->latitude;
            $longitude = $request->longitude;

            /*Get default search radius*/
            $settings = Settings::where('key', 'search_radius')->first();
            $distance = $settings->value;

            $service_type_id = $request->service_id;

            if(!$request->service_id) {
                if($service_type = ServiceType::where('status' , DEFAULT_TRUE)->first()) {
                    $service_type_id = $service_type->id;
                }
            }

           $query = "SELECT DISTINCT providers.id,providers.first_name,providers.last_name,providers.latitude,providers.longitude,
                            1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) AS distance
                      FROM providers 
                      LEFT JOIN provider_services ON providers.id = provider_services.provider_id
                      LEFT JOIN provider_ratings ON providers.id = provider_ratings.provider_id
                     
                      WHERE provider_services.service_type_id = $service_type_id AND
                       providers.is_available = 1 AND providers.is_activated = 1 AND providers.is_approved = 1
                            AND (1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance
                      ORDER BY distance";

           $providers = DB::select(DB::raw($query));
          //  Log::info('search query'.print_r($query,true));
          //  Log::info("search provider:".print_r($providers,true));
         
           $provider_details = array();
           if($providers)
           {
              foreach ($providers as $provider) {
                  $provider_detail = array();
                  $provider_detail['id'] = $provider->id;
                  $provider_detail['first_name'] = $provider->first_name;
                  $provider_detail['last_name'] = $provider->last_name;
                  $provider_detail['latitude'] = $provider->latitude;
                  $provider_detail['longitude'] = $provider->longitude;
                  $provider_detail['distance'] = $provider->distance;
                 
                  //$provider_detail['name'] = DB::table('provider_services')->where('provider_id',$provider->id)->first();
                  $provider_detail['rating'] = DB::table('user_ratings')->where('provider_id', $provider->id)->avg('rating') ?: 0;
                  array_push($provider_details,$provider_detail);
              }
           }

            $response_array = array(
                'success' => true,
                'providers' => $provider_details
            );
            // Log::info($response_array);

        }

        return response()->json($response_array , 200);
    }

    public function fare_calculator(Request $request){

      $validator = Validator::make(
          $request->all(),
          array(
            'distance' => 'required',
            'time' => 'required',
          ));

      if ($validator->fails()) {
          $error_messages = implode(',',$validator->messages()->all());
          $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages' => $error_messages);
      } else {
          $time = $request->time;
          $distance = $request->distance;
          // Tax price
          $service_tax_details = Settings::where('key','tax_price')->first();

          if($request->has('service_id')){
              // Get base price from provider service table.
              // Check in settings table single price for service is activated.
              $check_per_service_status = Settings::where('key','price_per_service')->first();
              if($check_per_service_status->value == 1){
                  $get_price_details = ServiceType::where('id',$request->service_id)->first();
                  $timeMinutes = $time * 0.0166667;
                  $price_per_unit_time = $get_price_details->price_per_min*$timeMinutes;

                  $base_price = $get_price_details->min_fare;

                  $unit = $get_price_details->distance_unit;
                  if($unit == 'kms'){
                  $distanceKm = $distance * 0.001;
                  $setdistance_price = $get_price_details->price_per_unit_distance;
                  $price_per_unit_distance = $setdistance_price*($distanceKm-1);
                  }else{
                  $distanceMiles = $distance * 0.000621371;
                  $setdistance_price = $get_price_details->price_per_unit_distance;
                  $price_per_unit_distance = $setdistance_price*$distanceMiles;
                  }
              }
              else{
                $response_array = array('success' => false,'error' => Helper::get_error_message(157),'error_code' => 157);
                return response()->json($response_array , 200);
              }
          }
          else {
              // Base price from settings table
              $setbase_price = Settings::where('key','base_price')->first();
              $base_price = $setbase_price->value;
              $distance_unit = Settings::where('key','default_distance_unit')->first();
              $unit = $distance_unit->value;
              if($unit == 'kms'){
              $distanceKm = $distance * 0.001;
              $setdistance_price = Settings::where('key','price_per_unit_distance')->first();
              $price_per_unit_distance = $setdistance_price->value*($distanceKm-1);
              }else{
              $distanceMiles = $distance * 0.000621371;
              $setdistance_price = Settings::where('key','price_per_unit_distance')->first();
              $price_per_unit_distance = $setdistance_price->value*$distanceMiles;
              }
              $timeMinutes = $time * 0.0166667;
              $settime_price = Settings::where('key','price_per_minute')->first();
              $price_per_unit_time = $settime_price->value*$timeMinutes;
          }
          $semi_total = $base_price+$price_per_unit_distance+$price_per_unit_time;
          $total = $semi_total * ($service_tax_details->value/100) +$semi_total;

          $hitung1 = $base_price+$price_per_unit_distance;
		  $totalbiaya = $hitung1;
		  
          if($total <= 25)
          {
              $es_total_to = 25;
              $es_total_from = 50;
          }
          else
          {
              $es_total = round($total,2);
              $es_total_from = $es_total + 25;
              $es_total_to = $es_total - 25;
          }

          $response_array = Helper::null_safe(array(
            'success' => true,
            'estimated_fare_from' => $es_total_from,
            'estimated_fare_to' => $es_total_to,
            'total_biaya' => $totalbiaya
          ));

      }

      return response()->json($response_array , 200);
    }

    // Automated Request
    public function send_request(Request $request) {

        Log::info('send_request'.print_r($request->all() ,true));

        $validator = Validator::make(
                $request->all(),
                array(
                    's_latitude' => 'required|numeric',
                    's_longitude' => 'required|numeric',
                    'service_type' => 'numeric|exists:service_types,id',
                ), array( 'required' => 'Location Selected was incorrect! Please try again!'));

        if ($validator->fails())
        {
            $error_messages = implode(',', $validator->messages()->all());
            $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages' => $error_messages);
        } else 
        {
            Log::info('Create request start');
            // Check the user filled the payment details

            $user = User::find($request->id);
            // Save the user location
            $user->latitude = $request->s_latitude;
            $user->longitude = $request->s_longitude;
            $user->save();

            if(!$user->payment_mode) {
                // Log::info('Payment Mode is not available');
                $response_array = array('success' => false , 'error' => Helper::get_error_message(134) , 'error_code' => 134);
            } else {

                $allow = DEFAULT_FALSE;
                // if the payment mode is CARD , check if any default card is available
                if($user->payment_mode == CARD) {
                    if($user_card = Cards::find($user->default_card)) {
                        $allow = DEFAULT_TRUE;
                    }
                } else {
                    $allow = DEFAULT_TRUE;
                }

                if($allow == DEFAULT_TRUE) {

                    // Check already request exists
                    $check_status = array(REQUEST_NO_PROVIDER_AVAILABLE,REQUEST_CANCELLED,REQUEST_COMPLETED);

                    $check_requests = Requests::where('user_id' , $request->id)->whereNotIn('status' , $check_status)->count();

                    if($check_requests == 0) {

                        Log::info('Previous requests check is done');
                        $service_type = $request->service_type; // Get the service type

                        // Initialize the variable
                        $first_provider_id = 0; $list_fav_provider = array();

                        $latitude = $request->s_latitude;
                        $longitude = $request->s_longitude;
                        $request_start_time = time();

                        $latitudeD = $request->d_latitude;
                        $longitudeD = $request->d_longitude;
                        /*Get default search radius*/
                        $settings = Settings::where('key', 'search_radius')->first();
                        $distance = $settings->value;

                        // Search Providers
                        $providers = array();   // Initialize providers variable

                        // Check the service type value to search the providers based on the nearby location
                        if($service_type) {

                            Log::info('Location Based search started - service_type');
                            // Get the providers based on the selected service types

                            $service_providers = ProviderService::where('service_type_id' , $service_type)->where('is_available' , 1)->select('provider_id')->get();

                            $list_service_ids = array();    // Initialize list_service_ids
                            if($service_providers) {
                                foreach ($service_providers as $sp => $service_provider) {
                                    $list_service_ids[] = $service_provider->provider_id;
                                }
                                $list_service_ids = implode(',', $list_service_ids);
                            }

                            if($list_service_ids) {
                                $query = "SELECT providers.id,providers.waiting_to_respond as waiting, 1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) AS distance FROM providers
                                        WHERE id IN ($list_service_ids) AND is_available = 1 AND is_activated = 1 AND is_approved = 1
                                        AND (1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance
                                        ORDER BY distance";

                                $providers = DB::select(DB::raw($query));

                            }
                        } else {
                            Log::info('Location Based search started - without service_type');

                            $query = "SELECT providers.id,providers.waiting_to_respond as waiting, 1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) AS distance FROM providers
                                    WHERE is_available = 1 AND is_activated = 1 AND is_approved = 1
                                    AND (1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance
                                    ORDER BY distance";
                            $providers = DB::select(DB::raw($query));

                        }
                        // Log::info('List of providers'." ".print_r($providers));

                        // Initialize Final list of provider variable
                        $search_providers = array();
                        $search_provider = array();
                        Log::info("Provider list: ".print_r($providers,true));
                        if ($providers) {

                            foreach ($providers as $provider) {
                                $search_provider['id'] = $provider->id;
                                $search_provider['waiting'] = $provider->waiting;
                                $search_provider['distance'] = $provider->distance;

                                array_push($search_providers, $search_provider);
                            }
                        } else {
                            if(!$search_providers) {
                                Log::info("No Provider Found");
                                // Send push notification to User

                                $title = Helper::get_push_message(601);
                                $messages = Helper::get_push_message(602);
                                $this->dispatch( new NormalPushNotification($user->id, USER,$title, $messages));
                                $response_array = array('success' => false, 'error' => Helper::get_error_message(112), 'error_code' => 112);
                            }
                        }


                        // Sort the providers based on the waiting time
                        $sort_waiting_providers = Helper::sort_waiting_providers($search_providers);

                        // Get the final providers list
                        $final_providers = $sort_waiting_providers['providers'];
                                //
                        $check_waiting_provider_count = $sort_waiting_providers['check_waiting_provider_count'];

                        $url = "http://maps.googleapis.com/maps/api/distancematrix/json?origins=".$latitude.",".$longitude."&destinations=".$latitudeD.",".$longitudeD."&mode=driving&language=en-EN&sensor=false";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $response = curl_exec($ch);
            curl_close($ch);
            $response_a = json_decode($response, true);
            $distance2 = $response_a['rows'][0]['elements'][0]['distance']['value']/1000;
            $time = $response_a['rows'][0]['elements'][0]['duration']['value']/60;

            $distance = json_decode(round($distance2,0));
            
                        // Create Requests
                        $requests = new Requests;
                        $requests->user_id = $user->id;

                        if($service_type)
                            $requests->request_type = $service_type;

                        $requests->status = REQUEST_NEW;
                        $requests->confirmed_provider = NONE;
                        $requests->request_start_time = date("Y-m-d H:i:s", $request_start_time);
                        $requests->s_address = $request->s_address ? $request->s_address : "";
                        $requests->d_address = $request->d_address ? $request->d_address : "";
                        $requests->d_latitude = $request->d_latitude ? $request->d_latitude : "";
                        $requests->d_longitude = $request->d_longitude ? $request->d_longitude : "";
                        $requests->jarak = $distance;
                        $requests->amount = $request->info_biaya ? $request->info_biaya : "";
                        $requests->pesandriver = $request->pesan_driver ? $request->pesan_driver : "";
                        $requests->tipe_bayar = $request->tipe_biaya ? $request->tipe_biaya : "";
                        if($latitude){ $requests->s_latitude = $latitude; }
                        if($longitude) { $requests->s_longitude = $longitude; }

                        $requests->save();

                        if($requests) {
                            $requests->status = REQUEST_WAITING;
                            //No need fo current provider state
                            // $requests->current_provider = $first_provider_id;
                            $requests->save();

                            // Save all the final providers
                            $first_provider_id = 0;

                            if($final_providers) {
                                foreach ($final_providers as $key => $final_provider) {

                                    $request_meta = new RequestsMeta;

                                    if($first_provider_id == 0) {

                                        $first_provider_id = $final_provider;

                                        $request_meta->status = REQUEST_META_OFFERED;  // Request status change

                                        // Availablity status change
                                        if($current_provider = Provider::find($first_provider_id)) {
                                            $current_provider->waiting_to_respond = WAITING_TO_RESPOND;
                                            $current_provider->save();
                                        }

                                        // Send push notifications to the first provider
                                        $title = Helper::get_push_message(604);
                                        $message = "You got a new request from".$user->first_name." ".$user->last_name;

                                        $this->dispatch(new sendPushNotification($first_provider_id,2,$requests->id,$title,$message,''));

                                        // Push End
                                    }

                                    $request_meta->request_id = $requests->id;
                                    $request_meta->provider_id = $final_provider;
                                    $request_meta->save();
                                }
                            }

                            $response_d_address = $requests->d_address ? $requests->d_address : "";
                            $response_d_latitude = $requests->d_latitude ? $requests->d_latitude : "";
                            $response_d_longitude = $requests->d_longitude ? $requests->d_longitude : "";

                            $response_array = array(
                                'success' => true,
                                'request_id' => $requests->id,
                                'current_provider' => $first_provider_id,
                                'address' => $requests->s_address,
                                'latitude' => $requests->s_latitude,
                                'longitude' => $requests->s_longitude,
                                'd_address' => $response_d_address,
                                'd_latitude' => $response_d_latitude,
                                'd_longitude' => $response_d_longitude,
                            );

                            $response_array = Helper::null_safe($response_array); Log::info('Create request end');
                        } else {
                            $response_array = array('success' => false , 'error' => Helper::get_error_message(126) , 'error_code' => 126 );
                        }
                    } else {
                        $response_array = array('success' => false , 'error' => Helper::get_error_message(127) , 'error_code' => 127);
                    }

                } else {
                    $response_array = array('success' => false , 'error' => Helper::get_error_message(142) ,'error_code' => 142);
                }
            }
        }
        $response = response()->json($response_array, 200);
        return $response;

    }

    // Manual request
    public function manual_create_request(Request $request) {

        $validator = Validator::make(
                $request->all(),
                array(
                    's_latitude' => 'required|numeric',
                    's_longitude' => 'required|numeric',
                    'service_type' => 'numeric|exists:service_types,id',
                    'provider_id' => 'required|exists:providers,id',
                ));

        if ($validator->fails())
        {
            $error_messages = implode(',', $validator->messages()->all());
            $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages' => $error_messages);
        } else {
            Log::info('Create request start');
            // Check the user filled the payment details
            $user = User::find($request->id);
            if(!$user->payment_mode) {
                Log::info('Payment Mode is not available');
                $response_array = array('success' => false , 'error' => Helper::get_error_message(134) , 'error_code' => 134);
            } else {

                $allow = DEFAULT_FALSE;
                // if the payment mode is CARD , check if any default card is available
                if($user->payment_mode == CARD) {
                    if($user_card = Cards::find($user->default_card)) {
                        $allow = DEFAULT_TRUE;
                    }
                } else {
                    $allow = DEFAULT_TRUE;
                }

                if($allow == DEFAULT_TRUE) {

                    // Check the provider is available
                    if($provider = Provider::where('id' , $request->provider_id)->where('is_available' , DEFAULT_TRUE)->where('is_activated' , DEFAULT_TRUE)->where('is_approved' , DEFAULT_TRUE)->where('waiting_to_respond' ,DEFAULT_FALSE)->first()) {

                        // Check already request exists
                        $check_status = array(REQUEST_NO_PROVIDER_AVAILABLE,REQUEST_CANCELLED,REQUEST_COMPLETED);

                        $check_requests = Requests::where('user_id' , $request->id)->whereNotIn('status' , $check_status)->count();

                        if($check_requests == 0) {

                            Log::info('Previous requests check is done');

                            // Create Requests
                            $requests = new Requests;
                            $requests->user_id = $user->id;

                            if($request->service_type)
                                $requests->request_type = $request->service_type;

                            $requests->status = REQUEST_NEW;
                            $requests->confirmed_provider = NONE;
                            $requests->request_start_time = date("Y-m-d H:i:s");
                            $requests->s_address = $request->s_address ? $request->s_address : "";
                            $requests->d_address = $request->d_address ? $request->d_address : "";
                            $requests->d_latitude = $request->d_latitude ? $request->d_latitude : "";
                            $requests->d_longitude = $request->d_longitude ? $request->d_longitude : "";

                            if($request->s_latitude){ $requests->s_latitude = $request->s_latitude; }
                            if($request->s_longitude) { $requests->s_longitude = $request->s_longitude; }

                            $requests->save();

                            if($requests) {
                                $requests->status = REQUEST_WAITING;

                                $request_meta = new RequestsMeta;

                                $request_meta->status = REQUEST_META_OFFERED;  // Request status change

                                // Availablity status change
                                    $provider->waiting_to_respond = WAITING_TO_RESPOND;
                                    $provider->save();


                                // Send push notifications to the first provider
                                $title = Helper::get_push_message(604);
                                $message = "You got a new request from".$user->name;

                                $this->dispatch(new sendPushNotification($request->provider_id,2,$requests->id,$title,$message,''));

                                // Push End

                                $request_meta->request_id = $requests->id;
                                $request_meta->provider_id = $request->provider_id;
                                $request_meta->save();

                                $response_array = array(
                                    'success' => true,
                                    'request_id' => $requests->id,
                                    'current_provider' => $request->provider_id,
                                    'address' => $requests->s_address,
                                    'latitude' => $requests->s_latitude,
                                    'longitude' => $requests->s_longitude,
                                );

                                $response_array = Helper::null_safe($response_array); Log::info('Create request end');
                            } else {
                                $response_array = array('success' => false , 'error' => Helper::get_error_message(126) , 'error_code' => 126 );
                            }

                        } else {
                            $response_array = array('success' => false , 'error' => Helper::get_error_message(127) , 'error_code' => 127);
                        }

                    } else {
                        $response_array = array('success' => false , 'error' => Helper::get_error_message(153) ,'error_code' => 153);
                    }
                } else {
                    $response_array = array('success' => false , 'error' => Helper::get_error_message(142) ,'error_code' => 142);
                }
            }
        }
        $response = response()->json($response_array, 200);
        return $response;
    }

    public function cancel_request(Request $request) {

        $user_id = $request->id;

        $validator = Validator::make(
            $request->all(),
            array(
                'request_id' => 'required|numeric|exists:requests,id,user_id,'.$user_id,
            ));

        if ($validator->fails())
        {
            $error_messages = implode(',', $validator->messages()->all());
            $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=>$error_messages);
        }else
        {
            $request_id = $request->request_id;
            $requests = Requests::find($request_id);
            $requestStatus = $requests->status;
            $providerStatus = $requests->provider_status;
            $allowedCancellationStatuses = array(
                PROVIDER_ACCEPTED,
                PROVIDER_STARTED,
            );

            // Check whether request cancelled previously
            if($requestStatus != REQUEST_CANCELLED)
            {
                // Check whether request eligible for cancellation

                if(in_array($providerStatus, $allowedCancellationStatuses)) {

                    // Update status of the request to cancellation
                    $requests->status = REQUEST_CANCELLED;
                    $requests->save();

                    // If request has confirmed provider then release him to available status
                    if($requests->confirmed_provider != DEFAULT_FALSE){

                        $provider = Provider::find( $requests->confirmed_provider );
                        $provider->is_available = PROVIDER_AVAILABLE;
                        $provider->waiting_to_respond = WAITING_TO_RESPOND_NORMAL;
                        $provider->save();

                        // Send Push Notification to Provider
                        $title = Helper::tr('cancel_by_user_title');
                        $message = Helper::tr('cancel_by_user_message');

                        $this->dispatch(new sendPushNotification($requests->confirmed_provider,2,$requests->id,$title,$message,''));

                        Log::info("Cancelled request by user");
                        // Send mail notification to the provider
                        $email_data = array();

                        $subject = Helper::tr('request_cancel_user');

                        $email_data['provider_name'] = $email_data['username'] = "";

                        if($user = User::find($requests->user_id)) {
                            $email_data['username'] = $user->first_name." ".$user->last_name;
                        }

                        if($provider = Provider::find($requests->confirmed_provider)) {
                            $email_data['provider_name'] = $provider->first_name. " " . $provider->last_name;
                        }

                        $page = "emails.user.request_cancel";
                        $email_send = Helper::send_email($page,$subject,$provider->email,$email_data);
                    }

                    // No longer need request specific rows from RequestMeta
                    RequestsMeta::where('request_id', '=', $request_id)->delete();

                    $response_array = Helper::null_safe(array('success' => true,'request_id' => $request->id));

                } else {
                    $response_array = array( 'success' => false, 'error' => Helper::get_error_message(114), 'error_code' => 114 );
                }

            } else {
                $response_array = array( 'success' => false, 'error' => Helper::get_error_message(113), 'error_code' => 113 );
            }
        }

        $response = response()->json($response_array, 200);
        return $response;
    }

    public function waiting_request_cancel(Request $request) {

        $get_requests = Requests::where('user_id' , $request->id)->where('status' , REQUEST_WAITING)->get();

        if($get_requests) {
            foreach ($get_requests as $key => $requests) {
                $requests->status = REQUEST_CANCELLED;
                $requests->save();

                $requests_meta = RequestsMeta::where('request_id' , $requests->id);
                $current_provider = $requests_meta->where('status' , DEFAULT_TRUE)->first()->provider_id;
                if($provider = Provider::find($current_provider)) {
                    $provider->waiting_to_respond = WAITING_TO_RESPOND_NORMAL;
                    $provider->save();
                }

                $delete_request_meta = RequestsMeta::where('request_id' , $requests->id)->delete();

                //Send notification to the provider
                $title = Helper::tr('waiting_cancel_by_user_title');
                $message =  Helper::tr('waiting_cancel_by_user_message');

                Log::info("waiting cancelled - current provider".$current_provider);

                $this->dispatch(new sendPushNotification($current_provider,2,$requests->id,$title,$message,''));
            }
        }

        $response_array = array('success' => true);

        return response()->json($response_array , 200);

    }

    public function request_status_check(Request $request) {

        $user = User::find($request->id);

        $check_status = array(REQUEST_COMPLETED,REQUEST_CANCELLED,REQUEST_NO_PROVIDER_AVAILABLE);

        $requests = Requests::where('requests.user_id', '=', $request->id)
                            ->where('requests.request_type', '=', $request->service_id)
                            ->whereNotIn('requests.status', $check_status)
                            ->leftJoin('users', 'users.id', '=', 'requests.user_id')
                            ->leftJoin('providers', 'providers.id', '=', 'requests.confirmed_provider')
                            ->leftJoin('service_types', 'service_types.id', '=', 'requests.request_type')
                            ->select(
                                'requests.id as request_id',
                                'requests.request_type as request_type',
                                'service_types.name as service_type_name',
                                'service_types.provider_name as service_provider_name',
                                'requests.end_time as end_time',
                                'request_start_time as request_start_time',
                                'requests.status','providers.id as provider_id',
                                DB::raw('CONCAT(providers.first_name, " ", providers.last_name) as provider_name'),
                                'providers.picture as provider_picture',
                                'providers.mobile as provider_mobile', 'providers.latitude as driver_latitude',
                                'providers.longitude as driver_longitude',
                                'requests.provider_status',
                                'requests.amount',
                                DB::raw('CONCAT(users.first_name, " ", users.last_name) as user_name'),
                                'users.picture as user_picture',
                                'users.id as user_id',
                                'requests.s_latitude','requests.d_latitude',
                                'requests.s_longitude','requests.d_longitude',
                                'requests.s_address','requests.d_address',
                                'service_types.picture as type_picture'
                            )->get()->toArray();

        $requests_data = array();
        $invoice = array();

        if($requests) {
            foreach ($requests as  $req) {


                $req['rating'] = DB::table('user_ratings')->where('provider_id', $req['provider_id'])->avg('rating') ?: 0;

                // unset($req['provider_id']);
                $requests_data[] = $req;

                $allowed_status = array(REQUEST_COMPLETE_PENDING,REQUEST_COMPLETED,REQUEST_RATING,WAITING_FOR_PROVIDER_CONFRIMATION_COD);

                if( in_array($req['status'], $allowed_status)) {

                    $invoice_query = RequestPayment::where('request_id' , $req['request_id'])
                                    ->leftJoin('requests' , 'request_payments.request_id' , '=' , 'requests.id')
                                    ->leftJoin('users' , 'requests.user_id' , '=' , 'users.id')
                                    ->leftJoin('cards' , 'users.default_card' , '=' , 'cards.id');
                    if($user->payment_mode == CARD) {
                        $invoice_query = $invoice_query->where('cards.is_default' , DEFAULT_TRUE) ;
                    }

                    $invoice = $invoice_query->select('requests.confirmed_provider as provider_id' , 'request_payments.total_time',
                                        'request_payments.payment_mode as payment_mode' , 'request_payments.base_price',
                                        'request_payments.time_price' ,'request_payments.distance_travel',
                                        'request_payments.distance_price','request_payments.tax_price' , 'request_payments.total',
                                        'cards.card_token','cards.customer_id','cards.last_four')
                                    ->get()->toArray();
                }
            }
        }

        $data = Helper::null_safe($requests_data);
        $invoice = Helper::null_safe($invoice);

        $response_array = array(
            'success' => true,
            'data' => $data,
            'invoice' => $invoice
        );

        $response = response()->json($response_array, 200);
        return $response;
    }

    public function paybypaypal(Request $request) {

        $user = User::find($request->id);

        $validator = Validator::make($request->all() ,
            array(

                'request_id' => 'required|exists:requests,id,user_id,'.$request->id,
                'payment_mode' => 'required|in:'.PAYPAL.'|exists:settings,key,value,1',
                'is_paid' => 'required|in:'.DEFAULT_TRUE,
                'payment_id' => 'required',
            ),array(
                'exists' => 'The :attribute doesn\'t belong to user:'.$user->firstname.' '.$user->last_name,
                'in'      => 'The :attribute must be one of the following types: :values',
            )
            );
        if($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());
            $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages' => $error_messages);

        } else {
            $requests = Requests::where('id',$request->request_id)->where('status' , REQUEST_COMPLETE_PENDING)->first();
            // Check the status is completed
            if( $requests && $requests->status != REQUEST_RATING) {

                if($requests->status == REQUEST_COMPLETE_PENDING) {

                    $requests->status = REQUEST_RATING;

                    $requests->is_paid = DEFAULT_TRUE;
                    $requests->amount = $request->amount;
                    $requests->save();

                    if($request_payment = RequestPayment::where('request_id' , $request->request_id)->first()) {

                        $request_payment->payment_id = $request->payment_id;
                        $request_payment->payment_mode = $request->payment_mode;
                        $request_payment->status = DEFAULT_TRUE;
                        $request_payment->save();
                    }

                    // Send push notification to provider

                    if($user)
                        $title =  "The"." ".$user->first_name.' '.$user->last_name." done the payment";
                    else
                        $title = Helper::tr('request_completed_user_title');

                    $message = Helper::tr('request_completed_user_message');
                    $this->dispatch(new sendPushNotification($requests->confirmed_provider,2,$requests->id,$title,$message,''));

                     // Send mail notification to the provider
                    $subject = Helper::tr('request_completed_bill');
                    $email = Helper::get_emails(3,$request->id,$requests->confirmed_provider);
                    $page = "emails.user.invoice";
                    Helper::send_invoice($requests->id,$page,$subject,$email);

                    // Send Response
                    $response_array =  Helper::null_safe(array('success' => true , 'message' => Helper::get_message(107)));

                } else {
                    $response_array = array('success' => 'false' , 'error' => Helper::get_error_message(137) , 'error_code' => 137);
                }

            } else {
                $response_array = array('success' => 'false' , 'error' => Helper::get_error_message(138) , 'error_code' => 138);
            }
        }

        return response()->json($response_array,200);

    }

    public function paynow(Request $request) {

        $validator = Validator::make($request->all(),
            array(
                    'request_id' => 'required|exists:requests,id,user_id,'.$request->id,
                    'payment_mode' => 'required|in:'.COD.','.PAYPAL.','.WALLET.','.CARD.'|exists:settings,key,value,1',
                    'is_paid' => 'required',
                ),
            array(
                    'exists' => Helper::get_error_message(139),
                )
            );

        if($validator->fails()) {
            $error_messages = implode(',', $validator->messages()->all());
            $response_array = array('success' => false , 'error' => $error_messages , 'error_messages' => Helper::get_error_message(101));
        } else {

            $requests = Requests::where('id',$request->request_id)->where('status' , REQUEST_COMPLETE_PENDING)->first();
            $user = User::find($request->id);

            //Check current status of the request
            if($requests && intval($requests->status) != REQUEST_RATING ) {

                $total = 0;

                if($request_payment = RequestPayment::where('request_id' , $request->request_id)->first()) {
                    $request_payment->payment_mode = $request->payment_mode;
                    $request_payment->save();
                    $total = $request_payment->total;
                }

                if($request->payment_mode == COD) {

                    $requests->status = REQUEST_RATING;
                    // $requests->is_paid = DEFAULT_TRUE;

                    $request_payment->payment_id = uniqid();
                    $request_payment->status = DEFAULT_TRUE;

                } elseif($request->payment_mode == WALLET) {

                    $requests->status = REQUEST_RATING;
                    // $requests->is_paid = DEFAULT_TRUE;

                    $request_payment->payment_id = uniqid();
                    $request_payment->status = DEFAULT_TRUE;

                }elseif($request->payment_mode == CARD || $request->payment_mode == PAYPAL) {

                    $check_card_exists = User::where('users.id' , $request->id)
                                ->leftJoin('cards' , 'users.id','=','cards.user_id')
                                ->where('cards.id' , $user->default_card)
                                ->where('cards.is_default' , DEFAULT_TRUE);

                    if($check_card_exists->count() != 0) {

                        $user_card = $check_card_exists->first();

                      if($total != 0){
                            //BRAINTREE PAYMENT
                          $transaction = Helper::createTransaction($user_card->customer_id,$requests->id,$total);

                          if($transaction == '0'){
                            $response_array = array('success' => false, 'error' => Helper::get_error_message(158) , 'error_code' => 158);
                            return response()->json($response_array , 200);
                          }
                          else {
                              $request_payment->status = DEFAULT_TRUE;
                              $request_payment->payment_id = $transaction;
                              $requests->is_paid = DEFAULT_TRUE;
                              $requests->status = REQUEST_RATING;
                              $requests->amount = $total;
                          }
                      }else {
                          $response_array = array('success' => false, 'error' => Helper::get_error_message(158) , 'error_code' => 159);
                          return response()->json($response_array , 200);
                      }

                    } else {
                        $response_array = array('success' => false, 'error' => Helper::get_error_message(140) , 'error_code' => 140);
                        return response()->json($response_array , 200);
                    }

                }

                $requests->save();
                $request_payment->save();

                // Send notification to the provider Start
                if($user)
                    $title =  "The"." ".$user->first_name.' '.$user->last_name." done the payment";
                else
                    $title = Helper::tr('request_completed_user_title');

                $message = Helper::get_push_message(603);
                $this->dispatch(new sendPushNotification($requests->confirmed_provider,2,$requests->id,$title,$message,''));
                // Send notification end

                // Send invoice notification to the user, provider and admin
                $subject = Helper::tr('request_completed_bill');
                $email = Helper::get_emails(3,$request->id,$requests->confirmed_provider);
                $page = "emails.user.invoice";
                Helper::send_invoice($requests->id,$page,$subject,$email);

                $response_array = array('success' => true);

            } else {
                $response_array = array('success' => false,'error' => Helper::get_error_message(138) , 'error_code' => 138);
            }
        }

        return response()->json($response_array , 200);

    }

     public function rate_provider(Request $request) {

        $user = User::find($request->id);

        $validator = Validator::make(
            $request->all(),
            array(
                'request_id' => 'required|integer|exists:requests,id,user_id,'.$user->id.'|unique:user_ratings,request_id',
                'rating' => 'required|integer|in:'.RATINGS,
                'comments' => 'max:255',
                'is_favorite' => 'in:'.DEFAULT_TRUE.','.DEFAULT_FALSE,
            ),
            array(
                'exists' => 'The :attribute doesn\'t belong to user:'.$user->id,
                'unique' => 'The :attribute already rated.'
            )
        );

        if ($validator->fails()) {
            $error_messages = implode(',', $validator->messages()->all());
            $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=>$error_messages);

        } else {

            $request_id = $request->request_id;
            $comment = $request->comment;

            $req = Requests::where('id' ,$request_id)
                    ->where('status' ,REQUEST_RATING)
                    ->first();

            if ($req && intval($req->status) != REQUEST_COMPLETED) {
                //Save Rating
                $rev_user = new UserRating();
                $rev_user->provider_id = $req->confirmed_provider;
                $rev_user->user_id = $req->user_id;
                $rev_user->request_id = $req->id;
                $rev_user->rating = $request->rating;
                $rev_user->comment = $comment ? $comment: '';
                $rev_user->save();

                $req->status = REQUEST_COMPLETED;
                $req->save();


                // Send Push Notification to Provider
                // $title = Helper::tr('provider_rated_by_user_title');
                // $messages = Helper::tr('provider_rated_by_user_message');
                // $this->dispatch( new sendPushNotification($req->confirmed_provider, PROVIDER,$req->id,$title, $messages));
                $response_array = array('success' => true);

            } else {
                $response_array = array('success' => false,'error' => Helper::get_error_message(150),'error_code' => 150);
            }
        }

        $response = response()->json($response_array, 200);
        return $response;
    }

    public function history(Request $request) {

        // Get the completed request details
        $requests = Requests::where('requests.user_id', '=', $request->id)
                            ->where('requests.status', '=', REQUEST_COMPLETED)
                            ->leftJoin('providers', 'providers.id', '=', 'requests.confirmed_provider')
                            ->leftJoin('users', 'users.id', '=', 'requests.user_id')
                            ->leftJoin('service_types','service_types.id','=','requests.request_type')
                            ->leftJoin('request_payments', 'requests.id', '=', 'request_payments.request_id')
                            ->orderBy('request_start_time','desc')
                            ->select('requests.id as request_id','service_types.name as taxi_name',
                            'requests.s_address as s_address','requests.d_address as d_address', 'requests.request_type as request_type', 'request_start_time as date',
                                    DB::raw('CONCAT(providers.first_name, " ", providers.last_name) as provider_name'), 'providers.picture',
                                    DB::raw('ROUND(request_payments.total) as total'))
                                    ->get()
                                    ->toArray();
        $requests = Helper::null_safe($requests);

        $response_array = array('success' => true,'requests' => $requests);

        return response()->json($response_array , 200);
    }

    public function single_request(Request $request) {

        $user = User::find($request->id);

        $validator = Validator::make(
            $request->all(),
            array(
                'request_id' => 'required|integer|exists:requests,id,user_id,'.$user->id,
            ),
            array(
                'exists' => 'The :attribute doesn\'t belong to user:'.$user->id,
            )
        );

        if ($validator->fails()) {
            $error_messages = implode(',', $validator->messages()->all());
            $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=>$error_messages);

        } else {

            $requests = Requests::where('requests.id' , $request->request_id)
                                ->leftJoin('providers' , 'requests.confirmed_provider','=' , 'providers.id')
                                ->leftJoin('users' , 'requests.user_id','=' , 'users.id')
                                ->leftJoin('user_ratings' , 'requests.id','=' , 'user_ratings.request_id')
                                ->leftJoin('request_payments' , 'requests.id','=' , 'request_payments.request_id')
                                ->leftJoin('service_types', 'service_types.id', '=', 'requests.request_type')
                                ->leftJoin('cards','users.default_card','=' , 'cards.id')
                                ->select('providers.id as provider_id' , 'providers.picture as provider_picture','request_payments.payment_mode as payment_mode',
                                    DB::raw('CONCAT(providers.first_name, " ", providers.last_name) as provider_name'),'user_ratings.rating','user_ratings.comment',
                                     DB::raw('ROUND(request_payments.base_price) as base_price'), DB::raw('ROUND(request_payments.tax_price) as tax_price'),
                                     DB::raw('ROUND(request_payments.time_price) as time_price'), DB::raw('ROUND(request_payments.total) as total'),
                                    'cards.id as card_id','cards.customer_id as customer_id',
                                    'cards.card_token','cards.last_four',
                                    'requests.id as request_id','requests.before_image','requests.after_image',
                                    'requests.user_id as user_id',
                                    'requests.request_type as request_type',
                                    'service_types.name as service_type_name',
                                    'service_types.provider_name as service_provider_name',
                                    DB::raw('CONCAT(users.first_name, " ", users.last_name) as user_name'))
                                ->get()->toArray();

            $response_array = Helper::null_safe(array('success' => true , 'data' => $requests));
        }

        return response()->json($response_array , 200);

    }

    public function get_payment_modes(Request $request) {

        $payment_modes = array();
        $modes = Settings::whereIn('key' , array('cod','paypal','card','wallet'))->where('value' , 1)->get();
        if($modes) {
            foreach ($modes as $key => $mode) {
                $payment_modes[$key] = $mode->key;
            }
        }
        $user = User::find($request->id);
        if($user){
	        $paymenterpilih = $user->payment_mode;
        }
        $response_array = Helper::null_safe(array('success' => true , 'payment_modes' => $payment_modes,'payment_terpilih' =>$paymenterpilih));

        return response()->json($response_array,200);
    }

    public function get_user_payment_modes(Request $request) {

        $user = User::find($request->id);

        if($user->payment_mode) {

            $payment_data = $data = $card_data = array();

            if($user_cards = Cards::where('user_id' , $request->id)->get()) {
                foreach ($user_cards as $c => $card) {
                    $data['id'] = $card->id;
                    $data['customer_id'] = $card->customer_id;
                    $data['card_id'] = $card->card_token;
                    $data['last_four'] = $card->last_four;
                    $data['is_default']= $card->is_default;

                    array_push($card_data, $data);
                }
            }

            $response_array = Helper::null_safe(array('success' => true, 'payment_mode' => $user->payment_mode , 'card' => $card_data));

        } else {
            $response_array = array('success' => false , 'error' => Helper::get_error_message(130) , 'error_code' => 130);
        }
        return response()->json($response_array , 200);

    }

    public function payment_mode_update(Request $request) {

        $validator = Validator::make($request->all() ,
            array(
                'payment_mode' => 'required|in:'.COD.','.PAYPAL.','.WALLET.','.CARD,
                )
            );
         if($validator->fails()) {
            $error_messages = implode(',', $validator->messages()->all());
            $response_array = array('success' => false ,'error' => $error_messages , 'error_messages' => Helper::get_error_message(101));
        } else {
            $user = User::where('id', '=', $request->id)->update( array('payment_mode' => $request->payment_mode));

            $response_array = Helper::null_safe(array('success' => true , 'message' => Helper::get_message(109)));
        }

        return response()->json($response_array , 200);

    }

     public function user_wallet(Request $request) {
     
     $payment = Userwallet::where('user_id','=',$request->id)->first();
        $cardArray = array();
        if($payment){

           

            $response_array = array(
                'success' => true ,
                'balance' => $payment->amount_earned -$payment->amount_spent
            );
        }else{
      						        $user_promo_entry = new Userwallet;
                                                        $user_promo_entry->user_id = $request->id;
                                                        $user_promo_entry->save();
            //no payments
            $response_array = array(
                'success' => false ,
                'error_message' => 'balance not found'
          );
        }

        $response = response()->json($response_array, 200);
        return $response;
      
        
    }
    
    public function add_card(Request $request) {

        $validator = Validator::make(
                    $request->all(),
                    array(
                        'last_four' => 'required',
                        'card_token' => 'required',
                        'customer_id' => 'required',
                    )
                );

        if ($validator->fails())
        {
           $error_messages = implode(',', $validator->messages()->all());
           $response_array = array('success' => false , 'error' => Helper::get_error_message(101) , 'error_code' => 101 , 'error_messages' => $error_messages);

        } else {

            $user = User::find($request->id);

            $customer_id = $request->customer_id;

            $cards = new Cards;
            $cards->user_id = $request->id;
            $cards->customer_id = $request->customer_id;
            $cards->last_four = $request->last_four;
            $cards->card_token = $request->card_token;

            // Check is any default is available
            $check_card = Cards::where('user_id',$request->id)->first();

            if($check_card ) {
                $cards->is_default = 0;
            } else {
                $cards->is_default = 1;
            }

            $cards->save();

            if($user) {
                // $user->payment_mode = CARD;
                $user->default_card = $check_card ? $user->default_card : $cards->id;
                $user->save();
            }

            $response_array = Helper::null_safe(array('success' => true));
        }

        $response = response()->json($response_array,200);
        return $response;
    }

    public function delete_card(Request $request) {

        $card_id = $request->card_id;

        $validator = Validator::make(
            $request->all(),
            array(
                'card_id' => 'required|integer|exists:cards,id,user_id,'.$request->id,
            ),
            array(
                'exists' => 'The :attribute doesn\'t belong to user:'.$request->id
            )
        );

        if ($validator->fails()) {
           $error_messages = implode(',', $validator->messages()->all());
           $response_array = array('success' => false , 'error' => Helper::get_error_message(101) , 'error_code' => 101 , 'error_messages' => $error_messages);
        } else {

            Cards::where('id',$card_id)->delete();

            $user = User::find($request->id);

            if($user) {

                if($user->payment_mode = CARD) {
                    // Check he added any other card
                    if($check_card = Cards::where('user_id' , $request->id)->first()) {
                        $check_card->is_default =  DEFAULT_TRUE;
                        $user->default_card = $check_card->id;
                        $check_card->save();
                    } else {
                        $user->payment_mode = COD;
                        $user->default_card = DEFAULT_FALSE;
                    }
                }

                $user->save();
            }

            $response_array = array('success' => true );
        }

        return response()->json($response_array , 200);
    }

    public function default_card(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'card_id' => 'required|integer|exists:cards,id,user_id,'.$request->id,
            ),
            array(
                'exists' => 'The :attribute doesn\'t belong to user:'.$request->id
            )
        );

        if($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());
            $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=>$error_messages);

        } else {

            $user = User::find($request->id);

            $old_default = Cards::where('user_id' , $request->id)->where('is_default', DEFAULT_TRUE)->update(array('is_default' => DEFAULT_FALSE));

            $card = Cards::where('id' , $request->card_id)->update(array('is_default' => DEFAULT_TRUE));

            if($card) {
                if($user) {
                    // $user->payment_mode = CARD;
                    $user->default_card = $request->card_id;
                    $user->save();
                }
                $response_array = Helper::null_safe(array('success' => true));
            } else {
                $response_array = array('success' => false , 'error' => 'Something went wrong');
            }
        }
        return response()->json($response_array , 200);

    }

    public function message_get(Request $request)
    {
        $Messages = ChatMessage::where('user_id', $request->id)
                ->where('request_id', $request->request_id);
                // ->orderBy('id', 'desc');

        $response_array = Helper::null_safe(array(
            'success' => true,
            'data' => $Messages->get()->toArray(),
        ));

        return response()->json($response_array, 200);
    }


    public function getBraintreeToken(Request $request)
    {
        $clientToken = Braintree_ClientToken::generate();
        $response_array = array(
                'success' => true,
                'client_token' => $clientToken
        );
        $response = response()->json($response_array, 200);
        return $response;

    }


    public function userAddCard(Request $request)
    {

        $payment_method_nonce = $request->payment_method_nonce;
        $user = User::find($request->id);
        $payment = Cards::where('user_id',$request->id)->where('is_deleted',0)->first();


        try{

            if(!$payment){

                $result = Braintree_Customer::create(array(
                    'firstName' => $user->first_name,
                    'lastName' => $user->last_name,
                    'paymentMethodNonce' => $payment_method_nonce
                 ));
                //dd($result->customer->creditCards[0]->cardType);

                if($result->success){

                    Log::info('New User creation success');
                    if($result->customer->creditCards){

                        $payment = new Cards;
                        $payment->user_id = $request->id;
                        $payment->customer_id = $result->customer->id;
                        $payment->payment_method_nonce = $request->payment_method_nonce;
                        $payment->last_four = (string)$result->customer->creditCards[0]->last4;
                        $payment->is_default=1;
                        $payment->card_type = $result->customer->creditCards[0]->cardType;
                        $payment->card_token = 'na';
                        $payment->save();

                        Log::info("First card");

                        if($user) {
                            // $user->payment_mode = CARD;

                            $user->default_card = $payment->id;
                            $user->save();
                            Log::info('Default card changed');
                        }

                        $response_array = array('success' => true,'message'=>'Thank you for adding your first card.');


                    }elseif($result->customer->paypalAccounts){
                        //adding paypal
                        $payment = new Cards;
                        $payment->user_id = $request->id;
                        $payment->customer_id = $result->customer->id;
                        $payment->payment_method_nonce = $request->payment_method_nonce;
                        $payment->paypal_email = $result->customer->paypalAccounts[0]->email;
                        $payment->card_type = 'na';
                        $payment->card_token = 'na';
                        $payment->save();
                        $response_array = array('success' => true,'message'=>'Thank you for adding your first paypal account.');
                    }

                }else{// $result->success failed

                    $response_array = array('success' => true,'message'=>'Braintree Adding Card Error: '.$result->message);

                }


            }else{ //if payment exist in payment table

                $customer_id = $payment->customer_id;
                $result = Braintree_PaymentMethod::create(array(
                    'customerId' => $customer_id,
                    'paymentMethodNonce' => $payment_method_nonce
                ));
                //dd($result);
                if($result->success)  {

                if(preg_match('/Braintree_CreditCard/', $result->paymentMethod)){
                    //credit card

                    $payment = new Cards;
                    $payment->user_id = $request->id;
                    $payment->customer_id = $customer_id;
                    $payment->payment_method_nonce = $request->payment_method_nonce;
                    $payment->last_four = (string)$result->paymentMethod->last4;
                    $payment->card_type = $result->paymentMethod->cardType;
                    $payment->card_token = 'na';
                    $payment->save();

                    Log::info("Second card");

                    $response_array = array('success' => true,'message'=>'Thank you for adding your card.');


                }elseif(preg_match('/Braintree_PayPalAccount/', $result->paymentMethod)
                    ){
                    //paypal
                    $payment = new Cards;
                    $payment->user_id = $request->id;
                    $payment->customer_id = $customer_id;
                    $payment->payment_method_nonce = $request->payment_method_nonce;
                    $payment->paypal_email = $result->paymentMethod->email;
                    $payment->card_type = 'na';
                    $payment->card_token = 'na';
                    $payment->save();
                    $response_array = array('success' => true,'message'=>'Thank you for adding your paypal account.');
                }
            }else{
                // failed

                $response_array = array('success' => true,'message'=>'Braintree Adding Card Error: '.$result->message);
            }
            }

        }catch(Braintree_Exception_Authorization $e){
            Log::error('Error = '.$e->getMessage());
            $response_array = array('success' => true,'message'=>'Something went wrong. Please try again later or contact us.');
        }

        $response = response()->json($response_array, 200);
        return $response;
    }


    public function selectCard(Request $request)
    {

        $validator = Validator::make(
            $request->all(),
            array(
                'card_id' => 'required|integer|exists:cards,id,user_id,'.$request->id,
                'payment_mode' => 'required|in:'.COD.','.PAYPAL.','.WALLET.','.CARD.'|exists:settings,key,value,1',
            ),
            array(
                    'exists' => Helper::get_error_message(139),
                ));

        if ($validator->fails()) {
            $error_messages = implode(',', $validator->messages()->all());
            $response_array = array('success' => false, 'error_message' =>$error_messages , 'error_code' => 101, 'error_messages'=>$error_messages);

        } else {

            if($request->payment_mode == CARD){

                $user = User::find($request->id);

                $old_default = Cards::where('user_id' , $request->id)->where('is_default', DEFAULT_TRUE)->update(array('is_default' => DEFAULT_FALSE));

                $card = Cards::where('id' , $request->card_id)->update(array('is_default' => DEFAULT_TRUE));

                if($card) {
                    if($user) {
                        // $user->payment_mode = CARD;
                        $user->default_card = $request->card_id;
                        $user->save();
                    }
                    $response_array = Helper::null_safe(array('success' => true));
                } else {
                    $response_array = array('success' => false , 'error' => 'Something went wrong');
                }

                Log::info("default card changed");
            }else{
                Log::info("payment_mode is different".print_r($request->payment_mode,true));

                $response_array = array(
                'success' => true
                );
            }

        }
        $response = response()->json($response_array, 200);
        return $response;

    }

    public function getCards(Request $request)
    {
        $payment = Cards::where('user_id',$request->id)->first();
        $cardArray = array();
        if($payment){

            $payment_data = Cards::where('user_id',$request->id)
                            ->where('is_deleted',0)
                            ->get();

            foreach($payment_data as $pay){
                $card['id'] = $pay->id;
                $card['customer_id'] = $pay->customer_id;
                if($pay->last_four){
                    $card['last_four'] = $pay->last_four;
                    $card['type'] = 'card';
                    $card['card_type'] = $pay->card_type;
                    $card['email'] = '';

                }else{
                    $card['last_four'] = '0';
                    $card['type'] = 'paypal';
                    $card['email'] = $pay->paypal_email;
                    $card['card_type'] = $pay->card_type;
                }

                $card['is_default'] = $pay->is_default;
                array_push($cardArray, $card);
            }


            $response_array = array(
                'success' => true ,
                'cards' => $cardArray
            );
        }else{
            //no payments
            $response_array = array(
                'success' => false ,
                'error_message' => 'No Card Found'
          );
        }

        $response = response()->json($response_array, 200);
        return $response;

    }

    public function deleteCard(Request $request)
    {

        $validator = Validator::make(
            $request->all(),
            array(
                'card_id' => 'required',
            ));

        if ($validator->fails()) {
            $error_messages = implode(',', $validator->messages()->all());
            $response_array = array('success' => false, 'error_message' =>$error_messages , 'error_code' => 101, 'error_messages'=>$error_messages);

        } else {

            $payment = Cards::find($request->card_id);
            if($payment){
                if($payment->is_default == 1){
                    $response_array = array(
                    'success' => false,
                    'message' => 'Cant able to delete default card. Please change default card and try again');
                }else{
                    $payment->is_deleted = 1;
                    $payment->save();
                    
                    $response_array = array(
                    'success' => true,
                    'message' => 'Card deleted succesfully');
                }
            }else{
                $response_array = array(
                'success' => false,
                'error_message' => 'wrong card id');

            }

        }
        $response = response()->json($response_array, 200);
        return $response;
    }

    public function testPayment(Request $request)
    {
        $payment= Cards::where('user_id',$request->id)->first();


        $trans = Helper::createTransaction($payment->customer_id,30,10);
        dd($trans);
    }
}
