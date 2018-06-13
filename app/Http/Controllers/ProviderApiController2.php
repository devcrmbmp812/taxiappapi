<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Helpers\Helper;

use Log;

use Hash;

use Validator;

use DB;

use App\Admin;

use App\User;

use App\Provider;

use App\ProviderService;

use App\ServiceType;

use App\Requests;

use App\RequestsMeta;

use App\RequestPayment;

use App\Settings;

use App\ProviderDocument;

use App\ProviderRating;

use App\UserRating;

use App\Balancedriver;


use App\Cards;

use App\Document;

use App\Jobs\sendPushNotification;

use App\Jobs\NormalPushNotification;

// Braintree Classes
use Braintree_Transaction;
use Braintree_Customer;
use Braintree_WebhookNotification;
use Braintree_Subscription;
use Braintree_CreditCard;

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


class ProviderApiController extends Controller
{
    public function __construct(Request $request)
	{
		$this->middleware('ProviderApiVal' , ['except' => ['register' , 'login' , 'forgot_password']]);
	}

	public function register(Request $request) {

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
                'service_type' => 'numeric|exists:service_types,id',
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
                            'mobile' => 'required|mobile|max:11',
                            'picture' => 'mimes:jpeg,jpg,bmp,png',
                            'gender' => 'in:male,female,others',
                        )
                    );

                if($socialValidator->fails()) {

                    $error_messages = implode(',', $socialValidator->messages()->all());
                    $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=> $error_messages);

                    Log::info('Registration social validation failed');

                } else {

                    $check_social_user = Provider::where('email' , $request->email)->first();

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
                        'mobile' => 'required|min:11',
                        'password' => 'required|min:6',
                        'picture' => 'mimes:jpeg,jpg,bmp,png',
                    )
                );

                // validate email existence

                $emailValidator = Validator::make(
                    $request->all(),
                    array(
                        'email' => 'unique:providers,email',
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
                    $provider = new Provider;
                    $provider->is_approved = DEFAULT_TRUE;
					$provider->is_available = DEFAULT_TRUE;
					$provider->is_activated = DEFAULT_TRUE;
					$provider->is_email_activated = DEFAULT_TRUE;
					$provider->email_activation_code = uniqid();

                } else {
                    $provider = $check_social_user;
                }

                if($request->has('first_name')) {
                    $provider->first_name = $request->first_name;
                }

                if($request->has('last_name')) {
                    $provider->last_name = $request->last_name;
                }

                if($request->has('email')) {
                    $provider->email = $request->email;
                }

                if($request->has('mobile')) {
                    $provider->mobile = $request->mobile;
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

                if($request->has('password'))
                    $provider->password = Hash::make($request->password);

                $provider->gender = $request->has('gender') ? $request->gender : "male";

                $provider->token = Helper::generate_token();
                $provider->token_expiry = Helper::generate_token_expiry();

                $check_device_exist = Provider::where('device_token', $request->device_token)->first();

                if($check_device_exist){
                    $check_device_exist->device_token = "";
                    $check_device_exist->save();
                }

                $provider->device_token = $request->has('device_token') ? $request->device_token : "";
                $provider->device_type = $request->has('device_type') ? $request->device_type : "";
                $provider->login_by = $request->has('login_by') ? $request->login_by : "";
                $provider->social_unique_id = $request->has('social_unique_id') ? $request->social_unique_id : '';

                // Upload picture
                if($request->hasFile('picture')) {
                	$provider->picture = Helper::upload_picture($request->file('picture'));
                }

                $provider->save();

                if($provider) {

					if($request->has('service_type')) {

                        $check_provider_service = ProviderService::where('provider_id' , $provider->id)
                                                ->first();

                        if(!$check_provider_service) {
                            $provider_service = new ProviderService;
                        } else {
                            $provider_service = $check_provider_service;
                        }

						$provider_service->provider_id = $provider->id;
						$provider_service->service_type_id = $request->service_type;
						$provider_service->is_available = DEFAULT_TRUE;
						$provider_service->save();
					}
				}
                $provider->password = $request->password;

				if($new_user) {
					// Send welcome email to the new provider
		            $email_data = array();
		            $subject = Helper::tr('provider_welcome_title');
		            $email_data  = $provider;
		            $page = "emails.provider.welcome";
		            $email_send = Helper::send_email($page,$subject,$provider->email,$email_data);

		            // Send mail notification to the Admin
		            $email_data = array(); $admin_email = "appoetstest@gmail.com";
		            $subject = Helper::tr('new_provider_signup');
		            if($admin = Admin::first()) {
		            	$admin_email = $admin->email;
		            }
		            $email_data  = $provider;
		            $page = "emails.admin_new_provider_notify";
		            $email_send = Helper::send_email($page,$subject,$admin_email,$email_data);
		        }

            	Log::info("New provider registration: ".print_r($provider, true));

                $service_name = ServiceType::find($request->service_type);

				$response_array = array(
					'success' => true ,
					//'message' => $provider ? Helper::get_message(105) : Helper::get_error_message(126),
					'id' 	=> $provider->id,
	                'first_name' => $provider->first_name,
	                'last_name' => $provider->last_name,
	                'mobile' => $provider->mobile,
	                'gender' => $provider->gender,
	                'email' => $provider->email,
	                'picture' => $provider->picture,
	                'token' => $provider->token,
	                'token_expiry' => $provider->token_expiry,
	                'login_by' => $provider->login_by,
                  'currency_code' => $provider->currency_code,
                  'country' => $provider->country,
                  'timezone' => $provider->timezone,
	                'social_unique_id' => $provider->social_unique_id,
                    'service_type' => $request->service_type,
	                'service_type_name' => $service_name->name,
				);
				
				$response_array = Helper::null_safe($response_array);
            }
		}

		$response = response()->json($response_array, 200);
		return $response;

	}

	// public function email_verification(Request $request) {

	// 	$validator = Validator::make($request->all() ,
	// 		array(
	// 				'email_activation_code' => 'required|exists:providers,email_activation_code,id,'.$request->id,
	// 			)
	// 		);

	// 	if($validator->fails()) {
	// 		$error_messages = implode(',', $validator->messages()->all());
	// 		$response_array = array('success' => false , 'error' => $error_messages , 'error_code' => Helper::get_error_message(101));
	// 		return redirect()->route('/');
	// 	} else {
	// 		$provider = Provider::find($request->id);
	// 		$provider->is_email_activated = DEFAULT_TRUE;
	// 		$provider->save();

	// 		return view('provider_email_verified')->with('email_data' , $provider);
	// 	}
	// }

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
                    if($provider = Provider::where('email', '=', $email)->first()){
                        if($provider->is_activated) {
                            if(Hash::check($password, $provider->password)){

                                /*manual login success*/
                                $operation = true;
                                $service_type_id = $service_name = "";

                                if($provider_service = ProviderService::where('provider_id' , $provider->id)
                                                        ->leftJoin('service_types' , 'provider_services.service_type_id' , '=' , 'service_types.id')
                                                        ->select('provider_services.service_type_id' , 'service_types.name')
                                                        ->first()) {
                                    $service_type_id = $provider_service->service_type_id;

                                    $service_name = $provider_service->name;
                                }

                            }else{
                                $response_array = array( 'success' => false, 'error' => Helper::get_error_message(105), 'error_code' => 105 );
                            }
                        } else {
                            $response_array = array('success' => false , 'error' => Helper::get_error_message(144),'error_code' => 144);
                        }

                    } else {
                        $response_array = array( 'success' => false, 'error' => Helper::get_error_message(100), 'error_code' => 105 );
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
                    if ($provider = Provider::where('social_unique_id', '=', $social_unique_id)->first()) {
                        if($provider->is_activated) {
                            /*social login success*/
                            $operation = true;
                            $service_type_id = $service_name = "";

                            if($provider_service = ProviderService::where('provider_id' , $provider->id)
                                                    ->leftJoin('service_types' , 'provider_services.service_type_id' , '=' , 'service_types.id')
                                                    ->select('provider_services.service_type_id' , 'service_types.name')
                                                    ->first()) {
                                $service_type_id = $provider_service->service_type_id;

                                $service_name = $provider_service->name;
                            }
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
                $provider->token = Helper::generate_token();
                $provider->token_expiry = Helper::generate_token_expiry();

                // Save device details
                $provider->device_token = $device_token;
                $provider->device_type = $device_type;
                $provider->login_by = $login_by;

                $provider->save();

                $payment_mode_status = $provider->payment_mode ? $provider->payment_mode : 0;

                // Respond with provider details

                $response_array = array(
                            'success' => true,
                            'id' => $provider->id,
                            'name' => $provider->first_name.' '.$provider->last_name,
                            'first_name' => $provider->first_name,
                            'last_name' => $provider->last_name,
                            'mobile' => $provider->mobile,
                            'gender' => $provider->gender,
                            'email' => $provider->email,
                            'picture' => $provider->picture,
                            'login_by' => $provider->login_by,
                            'token' => $provider->token,
                            'currency_code' => $provider->currency_code,
                            'country' => $provider->country,
                            'timezone' => $provider->timezone,
                            'token_expiry' => $provider->token_expiry,
                            'active' => boolval($provider->is_activated),
                            'service_type' => $service_type_id,
                            'service_type_name' => $service_name,
                        );

                $response_array = Helper::null_safe($response_array);
            }
        }

        $response = response()->json($response_array, 200);
        return $response;
    }

    public function forgot_password(Request $request)
    {
        $email = $request->email;

         // Validate the email field

        $validator = Validator::make(
            $request->all(),
            array(
                'email' => 'required|email|exists:providers,email',
            )
        );

        if ($validator->fails()) {

			$error_messages = implode(',', $validator->messages()->all());
            $response_array = array('success' => false, 'error' => $error_messages, 'error_code' => 101, 'error_messages'=> Helper::get_error_message(101));

        } else {

	        $provider = Provider::where('email',$email)->first();

            $new_password = Helper::generate_password();
            $provider->password = Hash::make($new_password);
            // Email start
            $email_data = array();
			$subject = Helper::tr('provider_forgot_email_title');
			$email_data['password']  = $new_password;
			$email_data['user']  = $provider;
            $page = "emails.provider.forgot_password";
			$email_send = Helper::send_email($page,$subject,$provider->email,$email_data);

			// Email end
            $response_array['success'] = true;
            $response_array['message'] = Helper::get_message(106);
            $provider->save();
	    }

	    $response = response()->json($response_array, 200);
	    return $response;

    }

    public function changePassword(Request $request) {

        $old_password = $request->old_password;
        $new_password = $request->password;
        $confirm_password = $request->confirm_password;

        $validator = Validator::make($request->all(), [
                'password' => 'required|min:6',
                'old_password' => 'required',
                'confirm_password' => 'required|min:6',
            ]);

        if($validator->fails()) {

            $error_messages = implode(',',$validator->messages()->all());

            $response_array = array('success' => false, 'error' => 'Invalid Input', 'error_code' => 401, 'error_messages' => $error_messages );

            $response_code = 200;

        } else {

            if($provider = Provider::find($request->id)) {

                if(Hash::check($old_password,$provider->password))
                {
                    $provider->password = Hash::make($new_password);
                    $provider->save();

                    $response_array = array('success' => true , 'message' => Helper::get_message(102));
                    $response_code = 200;

                } else {
                    $response_array = array('success' => false , 'error' => Helper::get_error_message(131), 'error_code' => 131);
                    $response_code = 200;
                }

            } else {

                $response_array = array('success' => false , 'error' => Helper::get_error_message(133) , 'error_code' => 133);
                $response_code = 200;
            }

        }

        $response = response()->json($response_array,$response_code);

        return $response;

    }

	public function profile(Request $request)
	{
		$provider = Provider::where('providers.id' ,$request->id)
                        ->leftJoin('provider_services' , 'providers.id' , '=' , 'provider_services.provider_id')
                        ->leftJoin('service_types' , 'provider_services.service_type_id' , '=' , 'service_types.id')
                        ->select('providers.*' , 'service_types.id as service_type' , 'service_types.provider_name' , 'service_types.name as service_name')
                        ->first();

		$response_array = array(
            'success' => true,
            'id' => $provider->id,
            'email' => $provider->email,
            'first_name' => $provider->first_name,
            'last_name' => $provider->last_name,
            'mobile' => $provider->mobile,
            'picture' => $provider->picture,
            'service_type' => $provider->service_type,
            'service_name' => $provider->service_name,
            'token' => $provider->token,
            'token_expiry' => $provider->token_expiry,
            'active' => boolval($provider->is_activated)
		);
		$response_array = Helper::null_safe($response_array);

		$response = response()->json($response_array, 200);
		return $response;
	}

	public function update_profile(Request $request)
	{
		$validator = Validator::make(
				$request->all(),
				array(
					'first_name' => 'required|max:255',
					'last_name' => 'required|max:255',
					'mobile' => 'required|digits_between:6,13',
					'picture' => 'mimes:jpeg,bmp,png',
					'gender' => 'in:male,female,others',
					'email' => 'email|max:255|unique:providers,email,'.$request->id,
                    'service_type' => 'numeric|exists:service_types,id',
				),
				array(
						'unique' => 'Email ID already exists',
					));

		if ($validator->fails()) {
            $error_messages = implode(',', $validator->messages()->all());
            $response_array = array(
                'success' => false,
                'error' => $error_messages,
                'error_code' => 101,
                'error_messages' => Helper::get_error_message(101)
            );
		} else {

			$provider = Provider::find($request->id);

			if($request->has('first_name')) {
				$provider->first_name = $request->first_name;
			}

			if($request->has('last_name')) {
				$provider->last_name = $request->last_name;
			}

			if($request->has('email')) {
				$provider->email = $request->email;
			}

			if ($request->has('mobile')) {
				$provider->mobile = $request->mobile;
			}

			if ($request->has('gender')) {
				$provider->gender = $request->gender;
			}

			$picture = $request->file('picture');

			// Upload picture
            if ($picture != ""){

                //deleting old image if exists
                Helper::delete_picture($provider->picture);
                $provider->picture = Helper::upload_picture($picture);
            }

            // Generate new tokens
            // $provider->token = Helper::generate_token();
            // $provider->token_expiry = Helper::generate_token_expiry();

			$provider->save();

			$service_type_id = NONE;

			if($request->has('service_type')) {

				$service_type_id = $request->service_type_id;

				$check_provider_service = ProviderService::where('provider_id' , $request->id)
											->first();

				if(!$check_provider_service) {
					$provider_service = new ProviderService;
				} else {
					$provider_service = $check_provider_service;
				}

				$provider_service->provider_id = $request->id;
				$provider_service->service_type_id = $request->service_type;
				$provider_service->is_available = DEFAULT_TRUE;
				$provider_service->save();


			}

            $response_array = array(
                'success' => true,
                'id' => $provider->id,
                'first_name' => $provider->first_name,
                'last_name' => $provider->last_name,
                'mobile' => $provider->mobile,
                'email' => $provider->email,
                'picture' => $provider->picture,
                'gender' => $provider->gender,
                'token' => $provider->token,
                'token_expiry' => $provider->token_expiry,
                'service_type' => $service_type_id,
            );

            $response_array = Helper::null_safe($response_array);
		}

		$response = response()->json($response_array, 200);
		return $response;
	}

  public function documents(Request $request)
  {
       $documentss = Document::all();
       $documents = array();
       foreach($documentss as $doc)
       {
          $pro_doc = DB::table('provider_documents')
                      ->where('provider_documents.provider_id','=',$request->id)
                      ->where('provider_documents.document_id','=',$doc->id)->first();
          $document['id'] = $doc->id;
          $document['name'] = $doc->name;
          if($pro_doc)
          $document['document_url'] = $pro_doc->document_url;
          else {
            $document['document_url'] = "";
          }
          array_push($documents,$document);
       }
       $provider = Provider::find($request->id);

          $response_array = array('success' => true, 'documents' => $documents);
          $response_array = Helper::null_safe($response_array);
       
      $response = response()->json($response_array, 200);
  		return $response;
   }


   public function upload_documents(Request $request)
   {
     $document_url = $request->file('document_url');
     $validator = Validator::make(
         $request->all(),
         array(
           'document_url' => 'required',
           'document_id' => 'required|exists:documents,id',

         ),
         array(
             'exists' => 'Invalid Document ID',
           ));

     if ($validator->fails()) {
             $error_messages = implode(',', $validator->messages()->all());
             $response_array = array(
                 'success' => false,
                 'error' => $error_messages,
                 'error_code' => 101,
                 'error_messages' => Helper::get_error_message(101)
             );
     } else {

       $documentss = Document::all();
       $documents = array();
       foreach($documentss as $doc)
       {
          $pro_doc = DB::table('provider_documents')
                      ->where('provider_documents.provider_id','=',$request->id)
                      ->where('provider_documents.document_id','=',$doc->id)->first();
          $document['id'] = $doc->id;
          $document['name'] = $doc->name;
          if($pro_doc)
          $document['document_url'] = $pro_doc->document_url;
          else {
            $document['document_url'] = "";
          }
          array_push($documents,$document);
       }

       if($document = ProviderDocument::where('provider_id',$request->id)->where('document_id',$request->document_id)->first())
       {
         if ($document_url != ""){
             //deleting old image if exists
             Helper::delete_picture($document->document_url);
             $document->document_url = Helper::upload_picture($document_url);
             $document->save();
         }
       }
       else {
         $document = new ProviderDocument;
         $document->document_url = Helper::upload_picture($document_url);
         $document->provider_id = $request->id;
         $document->document_id = $request->document_id;
         $document->save();
       }
     }
     $response_array = array('success' => true, 'documents' => $documents);
     $response_array = Helper::null_safe($response_array);
     $response = response()->json($response_array, 200);
     return $response;

   }


   public function delete_document(Request $request)
   {
       if($check_document = ProviderDocument::where('provider_id',$request->id)
                       ->where('document_id',$request->document_id)->first())
      {
           $check_document->delete();

           // $provider = Provider::find($request->id);
           // $provider->is_approved = 0;
           // $provider->save();

           $response_array = array('success' => true);
        }else{
           $response_array = array('success'=> false,'error' => Helper::get_error_message(159) , 'error_code' => 159);
         }
        $response = response()->json($response_array, 200);
   		  return $response;

   }

	public function tokenRenew(Request $request)
	{

		$provider_id = $request->id;
		$token_refresh = $request->token_refresh;

		// Check if refresher token is valid
		if ($provider = Provider::where('id', '=', $provider_id)->first()) {

			// Generate new tokens
			$provider->token = Helper::generate_token();
			$provider->token_expiry = Helper::generate_token_expiry();
			$provider->token_refresh = Helper::generate_token();

			$provider->save();

			$response_array = array(
					'success' => true,
					'token' => $provider->token,
			);
		} else {
			$response_array = array(
					'success' => false,
					'error' => Helper::get_error_message(115),
					'error_code' => 115
			);
			$response_array = Helper::null_safe($response_array);
		}

		$response = response()->json($response_array, 200);
		return $response;
	}

	public function location_update(Request $request)
	{
		$validator = Validator::make(
				$request->all(),
				array(
						'latitude' => 'required',
						'longitude' => 'required'
				));

		if ($validator->fails()) {
			$error_messages = implode(',', $validator->messages()->all());
            $response_array = array('success' => false, 'error' => $error_messages, 'error_messages' => Helper::get_error_message(101) ,'error_code' => 101);
		} else {
			$provider = Provider::find($request->id);

			$provider->latitude = $request->latitude;
			$provider->longitude = $request->longitude;
			$provider->save();

			$response_array = Helper::null_safe(array(
                'success' => true,
                'id' => $provider->id,
                'latitude' => $provider->latitude,
                'longitude' => $provider->longitude
            ));
		}

		$response = response()->json($response_array, 200);
		return $response;
	}

	 public function get_balance(Request $request)
    {
    	
    	$ledgercek = Balancedriver::where('driver_id', $request->id)->orderBy('updated_at', 'desc')->count();
    	
    	 if($ledgercek == 0) {
    	 $addbalance = new Balancedriver;
    	 $addbalance->driver_id = $request->id;
    	 $addbalance->amount_earned = 0;
    	 $addbalance->amount_spent = 0;
    	 
    	 $addbalance->save();
    	 
    	 }else {
        $ledger = Balancedriver::where('driver_id', $request->id)->orderBy('updated_at', 'desc')->first();
       
       $response_array = Helper::null_safe(array(
                        'success' => true, 
                        'balance' => number_format($ledger->amount_earned - $ledger->amount_spent)
                      
                        
                        ));

}
        $response = response()->json($response_array, 200);
        return $response;
    }
    
    public function check_available(Request $request)
    {
    	$active = 0;

        $provider = Provider::find($request->id);
        if($provider)
        	$active = $provider->is_available;

        $response_array = Helper::null_safe(array(
            'success' => true,
            'id' => $request->id,
            'active' => $active
        ));

        $response = response()->json($response_array, 200);
        return $response;
    }

	public function available_update(Request $request)
	{
		$validator = Validator::make(
				$request->all(),
				array(
					'status' => 'required|in:'.DEFAULT_TRUE.','.DEFAULT_FALSE,
				));

		if ($validator->fails()) {
			$error_messages = implode(',', $validator->messages()->all());
            $response_array = array('success' => false, 'error' => $error_messages, 'error_messages' => Helper::get_error_message(101) ,'error_code' => 101);
		} else {


	        $provider = Provider::find($request->id);

	        $availableState = $provider->is_available ? DEFAULT_FALSE : DEFAULT_TRUE;
	        $provider->is_available = $request->status;
	        $provider->save();

	        $response_array = Helper::null_safe(array('success' => true,'id' => $provider->id,'active' => $provider->is_available));
	    }

		$response = response()->json($response_array, 200);
		return $response;
	}

	public function service_reject(Request $request)
	{
		$validator = Validator::make(
				$request->all(),
				array(
					'request_id' => 'required|integer|exists:requests,id',
				));

		if ($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

			$response_array = array('success' => false, 'error' => $error_messages, 'error_messages' => Helper::get_error_message(101) ,'error_code' => 101);

		} else {
			$provider = Provider::find($request->id);
			$request_id = $request->request_id;
            $requests = Requests::find($request_id);
            $user = User::find($requests->user_id);
            //Check whether the request is cancelled by user.
            if($requests->status == REQUEST_CANCELLED) {
                $response_array = array(
                    'success' => false,
                    'error' => Helper::get_error_message(117),
                    'error_code' => 117
                );
            }else {
                // Verify if request was indeed offered to this provider
                $request_meta = RequestsMeta::where('request_id', '=', $request_id)
                    ->where('provider_id', '=', $provider->id)
                    ->where('status', '=', REQUEST_META_OFFERED)->first();

                if (!$request_meta) {
                    // This request has not been offered to this provider. Abort.
                    $response_array = array(
                        'success' => false,
                        'error' => Helper::get_error_message(135),
                        'error_code' => 135);
                } else {
                    // Decline this offer
                    $request_meta->status = REQUEST_CANCELLED;
                    $request_meta->save();

                    // change waiting to respond state to normal state
                    $provider->waiting_to_respond = WAITING_TO_RESPOND_NORMAL;
					$provider->save();

                    $response_array = Helper::null_safe(array(
                    	'success' => true,
                    	'id' => $request->id,
                    	'request_id' => $request->request_id,
                    	'message' => Helper::get_message(118),
                    	));

                    // Check for manual request status
                    $manual_request = Settings::where('key','manual_request')->first();
                    if($manual_request->manual_request == 1){
                    	// Change status as providers rejected in request table
                    	 Requests::where('id', '=', $requests->id)->update( array('status' => REQUEST_REJECTED_BY_PROVIDER) );
                    	 // Send push notification to user "Provider rejected your request"
                    }

                    //Select the new provider who is in the next position.
                    $request_meta_next = RequestsMeta::where('request_id', '=', $request_id)->where('status', REQUEST_META_NONE)
                                        ->leftJoin('providers', 'providers.id', '=', 'requests_meta.provider_id')
                                        ->where('providers.is_activated',DEFAULT_TRUE)
                                        ->where('providers.is_approved',DEFAULT_TRUE)
                                        ->where('providers.is_available',DEFAULT_TRUE)
                                        ->where('providers.waiting_to_respond',WAITING_TO_RESPOND_NORMAL)
                                        ->select('requests_meta.id','requests_meta.status','requests_meta.provider_id')
                                        ->orderBy('requests_meta.created_at')->first();
                    if($request_meta_next){

                    	Log::info('Request Reject - Next Provider');

                    	// change waiting to respond state
                    	$provider_detail = Provider::where('id',$request_meta_next->provider_id)->first();
                    	$provider_detail->waiting_to_respond = WAITING_TO_RESPOND;
                    	$provider_detail->save();

                        //Assign the next provider.
                        $request_meta_next->status = REQUEST_META_OFFERED;
                        $request_meta_next->save();
                        //Update the request start time in request table
                        Requests::where('id', '=', $request->id)->update( array('request_start_time' => date("Y-m-d H:i:s")) );
                    } else {
                    	/**************************/
                    	// Change status as no providers avaialable in request table
                    	 Requests::where('id', '=', $requests->id)->update( array('status' => REQUEST_NO_PROVIDER_AVAILABLE) );

	                    // No longer need request specific rows from RequestMeta
	                    RequestsMeta::where('request_id', '=', $requests->id)->delete();
	                    Log::info('assign_next_provider ended the request_id:'.$request->id);

	                    //send pushnotification to user "No provider found"
                    }

                }
            }

		}

		$response = response()->json($response_array , 200);
		return $response;
	}

	public function service_accept(Request $request)
	{
		$validator = Validator::make(
				$request->all(),
				array(
					'request_id' => 'required|integer|exists:requests,id'
				));

		if ($validator->fails()) {
            $error_messages = implode(',', $validator->messages()->all());
			$response_array = array('success' => false, 'error' => $error_messages, 'error_messages' => Helper::get_error_message(101) ,'error_code' => 101);
		} else {

			$provider = Provider::find($request->id);
			$request_id = $request->request_id;
            $requests = Requests::find($request_id);
            //Check whether the request is cancelled by user.
		    if($requests->status == REQUEST_CANCELLED) {
                $response_array = array(
                    'success' => false,
                    'error' => Helper::get_error_message(117),
                    'error_code' => 117
                );
            }else{
                // Verify if request was indeed offered to this provider
                $request_meta = RequestsMeta::where('request_id', '=', $request_id)
                    ->where('provider_id', '=', $provider->id)
                    ->where('status', '=', REQUEST_WAITING)->first();

                if (!$request_meta) {
                    // This request has not been offered to this provider. Abort.
                    $response_array = array(
                        'success' => false,
                        'error' => Helper::get_error_message(149),
                        'error_code' => 149);
                } else {
                    // Accept the offer
                    $requests->confirmed_provider = $provider->id;
                    $requests->status = REQUEST_INPROGRESS;
                    $requests->provider_status = PROVIDER_ACCEPTED;
                    $requests->save();

                    // change waiting to respond state to normal state
                    $provider->waiting_to_respond = WAITING_TO_RESPOND_NORMAL;

                    // update is available state
                    $provider->is_available = PROVIDER_NOT_AVAILABLE;
                    $provider->save();

                    // Send Push Notification to User
                    $title = Helper::tr('request_accepted_title');
                    $message = Helper::tr('request_accepted_message');

                    $this->dispatch( new sendPushNotification($requests->user_id, USER,$requests->id,$title, $message,''));

                    // No longer need request specific rows from RequestMeta
                    RequestsMeta::where('request_id', '=', $request_id)->delete();

                    $user = User::find($requests->user_id);
                    $services = ServiceType::find($requests->request_type);
                    $d_address = $requests->d_address;
                    $d_latitude = $requests->d_latitude;
                    $d_longitude = $requests->d_longitude;
                    $s_address = $requests->s_address;

                    $requestData = array(
                        'request_id' => $requests->id,
                        'user_id' => $requests->user_id,
                        'request_type' => $requests->request_type,
                        'status' => $requests->status,
                        'provider_status' => $requests->provider_status,
                        's_longitude' => $requests->s_longitude,
                        's_latitude' => $requests->s_latitude,
                        's_address' => $s_address,
                        'd_latitude' => $d_latitude,
                        'd_longitude' => $d_longitude,
                        'd_address' => $d_address,
                        'service_type_name' => $services->name,
                        'user_name' => $user->first_name." ".$user->last_name,
                        'user_picture' => $user->picture,
                        'user_mobile' => $user->mobile,
                        'user_rating' => UserRating::where('user_id', $requests->user_id)->avg('rating') ?: 0,
                    );
                    $response_array = Helper::null_safe(array(
                        'success' => true,
                        'message' => Helper::get_message(111),
                        'data' => $requestData,
                        ));
                }
            }
		}

		$response = response()->json($response_array , 200);
		return $response;
	}

	public function providerstarted(Request $request)
	{
        $provider = Provider::find($request->id);

		$validator = Validator::make(
            $request->all(),
            array(
                'request_id' => 'required|integer|exists:requests,id,confirmed_provider,'.$provider->id,
            ),
            array(
                'exists' => 'The :attribute doesn\'t belong to provider:'.$provider->id
            )
        );

		if ($validator->fails())
		{
            $error_messages = implode(',', $validator->messages()->all());

            $response_array = array('success' => false, 'error' => $error_messages, 'error_messages' => Helper::get_error_message(101) ,'error_code' => 101);
		}
		else
		{

			$request_id = $request->request_id;
			$current_state = PROVIDER_STARTED;

			$check_status = array(REQUEST_CANCELLED,REQUEST_NO_PROVIDER_AVAILABLE,);

			$requests = Requests::where('id', '=', $request_id)
								->where('confirmed_provider', '=', $provider->id)
								->where('provider_status' , PROVIDER_ACCEPTED)
								->where('status', REQUEST_INPROGRESS)
								->first();

			// Current state being validated in order to prevent accidental change of state
			if ($requests && intval($requests->provider_status) != $current_state)
			{
	            $requests->status = REQUEST_INPROGRESS;
	            $requests->provider_status = PROVIDER_STARTED;
    			$requests->save();

    			$new_state = $requests->status;

	            // Send Push Notification to User
	            $title = Helper::tr('provider_started_title');
                $message = Helper::tr('provider_started_message');

                $this->dispatch( new sendPushNotification($requests->user_id, USER,$requests->id,$title, $message,''));

				$response_array = Helper::null_safe(array(
						'success' => true,
						'status' => $new_state,
						'message' => Helper::get_message(112)
				));
			} else {
				$response_array = array('success' => false, 'error' => Helper::get_error_message(145), 'error_code' => 145);
                // Log::info('Provider status Error:: Old state='.$requests->provider_status.' and current state='.$current_state);
			}
		}

		$response = response()->json($response_array , 200);
		return $response;
	}

	public function arrived(Request $request)
	{
        $provider = Provider::find($request->id);
		$validator = Validator::make(
            $request->all(),
            array(
                'request_id' => 'required|integer|exists:requests,id,confirmed_provider,'.$provider->id,
            ),
            array(
                'exists' => 'The :attribute doesn\'t belong to provider:'.$provider->id
            )
        );

		if ($validator->fails())
		{
			$error_messages = implode(',', $validator->messages()->all());
            $response_array = array('success' => false, 'error' => $error_messages, 'error_messages' => Helper::get_error_message(101) ,'error_code' => 101);
		}
		else
		{

			$request_id = $request->request_id;
			$current_state = PROVIDER_ARRIVED;

			$requests = Requests::where('id', '=', $request_id)
								->where('confirmed_provider', '=', $provider->id)
								->where('provider_status' , PROVIDER_STARTED)
								->where('status', REQUEST_INPROGRESS)
								->first();

			// Current state being validated in order to prevent accidental change of state
			if ($requests && intval($requests->provider_status) != $current_state)
			{
	            $requests->status = REQUEST_INPROGRESS;
	            $requests->provider_status = PROVIDER_ARRIVED;
    			$requests->save();

	            // Send Push Notification to User
	            $title = Helper::tr('provider_arrived_title');
                $message = Helper::tr('provider_arrived_message');
                $this->dispatch( new sendPushNotification($requests->user_id, USER,$requests->id,$title, $message,''));

				$response_array = Helper::null_safe(array(
						'success' => true,
						'status' => REQUEST_INPROGRESS,
						'message' => Helper::get_message(113)
				));
			} else {
				$response_array = array('success' => false, 'error' => Helper::get_error_message(146), 'error_code' => 146);
                // Log::info('Provider status Error:: Old state='.$requests->provider_status.' and current state='.$current_state);
			}
		}

		$response = response()->json($response_array , 200);
		return $response;
	}

	public function servicestarted(Request $request)
	{
        $provider = Provider::find($request->id);
		$validator = Validator::make(
            $request->all(),
            array(
                'request_id' => 'required|integer|exists:requests,id,confirmed_provider,'.$provider->id,
            ),
            array(
                'exists' => 'The :attribute doesn\'t belong to provider:'.$provider->id
            )
        );

		if ($validator->fails())
		{
			$error_messages = implode(',', $validator->messages()->all());
            $response_array = array('success' => false, 'error' => $error_messages, 'error_messages' => Helper::get_error_message(101) ,'error_code' => 101);
		}
		else
		{

			$request_id = $request->request_id;
			$current_state = PROVIDER_SERVICE_STARTED;

			$requests = Requests::where('id', '=', $request_id)
								->where('confirmed_provider', '=', $provider->id)
								->where('provider_status' , PROVIDER_ARRIVED)
								->where('status', REQUEST_INPROGRESS)
								->first();

			// Current state being validated in order to prevent accidental change of state
			if ($requests && intval($requests->provider_status) != $current_state)
			{
				if($request->hasFile('before_image'))
				{
					$image = $request->file('before_image');
					$requests->before_image = Helper::upload_picture($image);
				}
				$requests->start_time = date("Y-m-d H:i:s");
	            $requests->status = REQUEST_INPROGRESS;
	            $requests->provider_status = PROVIDER_SERVICE_STARTED;
    			$requests->save();

	            // Send Push Notification to User
	            $title = Helper::tr('request_started_title');
                $message = Helper::tr('request_started_message');
				$this->dispatch( new sendPushNotification($requests->user_id, USER,$requests->id,$title, $message,''));

				$response_array = Helper::null_safe(array(
						'success' => true,
						'request_id' => $request->request_id,
						'status' => REQUEST_INPROGRESS,
						'message' => Helper::get_message(114)
				));
			} else {
				$response_array = array('success' => false, 'error' => Helper::get_error_message(147), 'error_code' => 147);
                Log::info(Helper::get_error_message(147));
			}
		}

		$response = response()->json($response_array , 200);
		return $response;
	}

	public function servicecompleted(Request $request)
	{
    $provider = Provider::find($request->id);
		$validator = Validator::make(
            $request->all(),
            array(
                'request_id' => 'required|integer|exists:requests,id,confirmed_provider,'.$provider->id,
                'time' => 'required',
                'distance' => 'required',
            ),
            array(
                'exists' => 'The :attribute doesn\'t belong to provider: '.$provider->first_name.''.$provider->last_name
            )
        );

		if ($validator->fails())
		{
			$error_messages = implode(',', $validator->messages()->all());
            $response_array = array('success' => false, 'error' => $error_messages, 'error_messages' => Helper::get_error_message(101) ,'error_code' => 101);
		}  else {

			$request_id = $request->request_id;
			$current_state = PROVIDER_SERVICE_COMPLETED;

			$requests = Requests::where('id', '=', $request_id)
								->where('confirmed_provider', '=', $provider->id)
								->where('provider_status' , PROVIDER_SERVICE_STARTED)
								->where('status', REQUEST_INPROGRESS)
								->first();

			// Current state being validated in order to prevent accidental change of state
			if ($requests && intval($requests->provider_status) != $current_state)
			{
          $requests->status = REQUEST_COMPLETE_PENDING;
          $requests->end_time = date("Y-m-d H:i:s");
          $requests->provider_status = PROVIDER_SERVICE_COMPLETED;
    			$requests->save();

    			//The availability will be change after the provider rated for user

    			// Initialize variables
    			$base_price = $price_per_minute = $tax_price = $total_time = $total_time_price = $total =  $distanceKm = $distanceMiles = 0;

    			// Invoice details

          $time = $request->time;
          $distance = $requests->jarak;//$request->distance;
          // Tax price
          $service_tax_details = Settings::where('key','tax_price')->first();

          if($requests->request_type != 0){
              // Get base price from provider service table.
              // Check in settings table single price for service is activated.
              $check_per_service_status = Settings::where('key','price_per_service')->first();
              if($check_per_service_status->value == 1){
                  $get_price_details = ServiceType::where('id',$requests->request_type)->first();
                  $timeMinutes = $time * 0.0166667;
                  $price_per_unit_time = $get_price_details->price_per_min*$timeMinutes;

                  $base_price = $get_price_details->min_fare;

                  $unit = $get_price_details->distance_unit;
                  if($unit == 'kms'){
                  $distanceKm = $distance;
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
              $distanceKm = $distance;
              $setdistance_price = Settings::where('key','price_per_unit_distance')->first();
              $price_per_unit_distance = $setdistance_price->value*($distanceKm-1);
              }else{
              $distanceMiles = $distance * 0.000621371;
              $setdistance_price = Settings::where('key','price_per_unit_distance')->first();
              $price_per_unit_distance = $setdistance_price->value*($distanceMiles-1);
              }
              $timeMinutes = $time * 0.0166667;
              $settime_price = Settings::where('key','price_per_minute')->first();
              $price_per_unit_time = $settime_price->value*$timeMinutes;
          }
          //$semi_total = $base_price+$price_per_unit_distance+$price_per_unit_time;
          
          $semi_total = $base_price+$price_per_unit_distance;
          $total = $semi_total;// * ($service_tax_details->value/100) +$semi_total;

	    		// get payment mode from user table.
	    		$user_payment_mode = $card_token = $customer_id = $last_four = "";

	    		if($user = User::find($requests->user_id)) {

    				$user_payment_mode = $user->payment_mode;

    				if($user_payment_mode == CARD) {
    					if($user_card = Cards::find($user->default_card)) {
    						$card_token = $user_card->card_token;
    						$customer_id = $user_card->customer_id;
    						$last_four = $user_card->last_four;
    					}
    				}
    			}

                 /*
                    //BRAINTREE PAYMENT

                    $payment = Cards::where('user_id',$requests->user_id)
                                        ->where('is_default',1)
                                        ->first();
                    $transaction = Helper::createTransaction($payment->customer_id,$requests->id,$total);

                  * if($transaction == 0) payment failed
                  * else payment succesfull

                 */

    			// Save the payment details
    			if(!RequestPayment::where('request_id' , $requests->id)->first()) {
	    			$request_payment = new RequestPayment;
	    			$request_payment->request_id = $requests->id;
	    			$request_payment->payment_mode = $user_payment_mode;
	    			$request_payment->base_price = $base_price;
	    			$request_payment->time_price = $price_per_unit_time;
	    			$request_payment->tax_price = $tax_price;
	    			$request_payment->total_time = $timeMinutes;

                    if($distanceKm != 0){
                        $request_payment->distance_travel = $distanceKm;
                    }else{
                        $request_payment->distance_travel = $distanceMiles;
                    }

                    $request_payment->distance_price = $price_per_unit_distance;
	    			$request_payment->total = $total;
	    			$request_payment->save();
	    		}

	    		$request_save = Requests::find($requests->id);
	    		$request_save->amount = $total;
	    		$request_save->save();

    			$invoice_data = array();

    			$user = User::find($requests->user_id);
    			$provider = Provider::find($requests->confirmed_provider);

    			$invoice_data['request_id'] = $requests->id;
    			$invoice_data['user_id'] = $requests->user_id;
    			$invoice_data['provider_id'] = $requests->confirmed_provider;
    			$invoice_data['provider_name'] = $provider->first_name." ".$provider->last_name;
    			$invoice_data['provider_address'] = $provider->address;
    			$invoice_data['user_name'] = $user->first_name." ".$user->last_name;
    			$invoice_data['user_address'] = $requests->s_address;
          $invoice_data['d_latitude'] = $requests->d_latitude;
          $invoice_data['d_longitude'] = $requests->d_longitude;
          $invoice_data['picture'] = $user->picture;
          if($service_type_details = ServiceType::find($requests->request_type)){
            $invoice_data['type_picture'] = $service_type_details->picture;
          }else {
            $invoice_data['type_picture'] = "";
          }
            if($requests_payments = RequestPayment::where('request_id' , $requests->id)->first()){
                //do nothing
            }else{
                $requests_payments = $request_payment;
            }
    			$invoice_data['base_price'] = $base_price;
    			$invoice_data['other_price'] = 0;
    			$invoice_data['total_time_price'] = $requests_payments->time_price;
                $invoice_data['total_distance_price'] = $requests_payments->distance_price;
                $invoice_data['total_time'] = $requests_payments->total_time;
                $invoice_data['distance_travel'] = $requests_payments->distance_travel;
    			$invoice_data['sub_total'] = $semi_total;
    			$invoice_data['tax_price'] = $service_tax_details->value;
    			$invoice_data['total'] = $requests_payments->total;
    			$invoice_data['payment_mode'] = $user_payment_mode;
    			$invoice_data['payment_mode_status'] = $user_payment_mode ? 1 : 0;
    			$invoice_data['bill_no'] = "Not paid";
    			$invoice_data['card_token'] = $card_token;
    			$invoice_data['customer_id'] = $customer_id;
    			$invoice_data['last_four'] = $last_four;
          $invoice_array_conver = array();
          array_push($invoice_array_conver,$invoice_data);

	            // Send Push Notification to User
	            $title = Helper::tr('request_complete_payment_title');
                // $message = $invoice_data;
	            $message = Helper::tr('request_complete_payment_message');

	            $this->dispatch( new sendPushNotification($requests->user_id, USER,$requests->id,$title, $message,''));

	            // Send invoice notification to the user and provider
                $subject = Helper::tr('request_completed_invoice');
                $email = Helper::get_emails(3,$requests->user_id,$requests->confirmed_provider);
                $page = "emails.provider.invoice";
                $email_send = Helper::send_email($page,$subject,$user->email,$invoice_data);

	            //Invoice details to Provider as well
				$response_array = Helper::null_safe(array(
						'success' => true,
						'request_id' => $request->request_id,
						'status' => REQUEST_COMPLETE_PENDING,
            'provider_status' => PROVIDER_SERVICE_COMPLETED,
						'invoice' => $invoice_array_conver,
						'message' => Helper::get_message(115)
				));
                Log::info('service completed api'.print_r($response_array,true));
			} else {
				$response_array = array('success' => false, 'error' => Helper::get_error_message(148), 'error_code' => 148);
                Log::info(Helper::get_error_message(148));
			}
		}

		$response = response()->json($response_array , 200);
		return $response;
	}

	public function rate_user(Request $request)
	{
        $provider = Provider::find($request->id);

		$validator = Validator::make(
            $request->all(),
            array(
                'request_id' => 'required|integer|exists:requests,id,confirmed_provider,'.$provider->id.'|unique:provider_ratings,request_id',
                'rating' => 'integer|in:'.RATINGS,
                'comments' => 'max:255'
            ),
            array(
                'exists' => 'The :attribute doesn\'t belong to provider:'.$provider->id,
                'unique' => 'The :attribute already rated.'
            )
        );

		if ($validator->fails()) {
            $error_messages = implode(',', $validator->messages()->all());
            $response_array = array('success' => false, 'error' => $error_messages, 'error_messages' => Helper::get_error_message(101) ,'error_code' => 101);
		} else {
            $request_id = $request->request_id;
            $comments = $request->comments;

            $req = Requests::where('id' ,$request_id)
            		->whereIn('status' , array(REQUEST_COMPLETE_PENDING,REQUEST_RATING,REQUEST_COMPLETED))
            		->where('provider_status' , PROVIDER_SERVICE_COMPLETED)
            		->first();

            if ($req && intval($req->provider_status) != PROVIDER_RATED) {

            	if($request->has('rating')) {
		            //Save Rating
		            $rev_user = new ProviderRating();
		            $rev_user->provider_id = $req->confirmed_provider;
		            $rev_user->user_id = $req->user_id;
		            $rev_user->request_id = $req->id;
		            $rev_user->rating = $request->rating;
		            $rev_user->comment = $comments ?: '';
		            $rev_user->save();
		        }

	            $req->provider_status = PROVIDER_RATED;
	            $req->save();

	            //Update provider availability
	            $provider = Provider::find($req->confirmed_provider);
	            $provider->is_available = PROVIDER_AVAILABLE;
	            $provider->save();

	            // Send Push notification to user
	            // $title = Helper::tr('user_rated_by_provider_title');
	            // $message = Helper::tr('user_rated_by_provider_title');
              //
	            // $this->dispatch( new sendPushNotification($req->user_id, USER,$req->id,$title, $message));

	            $response_array = Helper::null_safe(array('success' => true,'status' => REQUEST_COMPLETE_PENDING,'message' => Helper::get_message(116)));
	        } else {
	        	$response_array = array('success' => false , 'error' => Helper::get_error_message(150) , 'error_code' => 150);
	        }
		}
		return response()->json($response_array , 200);
	}

	public function cancelrequest(Request $request)
    {
        $provider_id = $request->id;
        $validator = Validator::make(
            $request->all(),
            array(
                'request_id' => 'required|numeric|exists:requests,id,confirmed_provider,'.$provider_id,
            ));

        if ($validator->fails())
        {
            $error_messages = implode(',', $validator->messages()->all());
            $response_array = array('success' => false, 'error' => $error_messages, 'error_messages' => Helper::get_error_message(101) ,'error_code' => 101);
        }else
        {
            $request_id = $request->request_id;
            $requests = Requests::find($request_id);
            $requestStatus = $requests->status;
            $providerStatus = $requests->provider_status;
            $allowedCancellationStatuses = array(
                PROVIDER_NONE,
                PROVIDER_ACCEPTED,
                PROVIDER_STARTED,
            );

            // Check whether request cancelled previously
            if($requestStatus != REQUEST_CANCELLED)
            {
                // Check whether request eligible for cancellation
                if( in_array($providerStatus, $allowedCancellationStatuses) )
                {
                    /*Update status of the request to cancellation*/
                    $requests->status = REQUEST_CANCELLED;
                    $requests->save();

                    // Send Push Notification to User
                    $title = Helper::tr('cancel_by_provider_title');
                    $message = Helper::tr('cancel_by_provider_message');

					// Send notifications to the user
                    $this->dispatch(new sendPushNotification($requests->user_id,USER,$requests->id,$title,$message,''));

                    // Send email notification to the user
                    /*If request has confirmed provider then release him to available status*/
                    if($requests->confirmed_provider != DEFAULT_FALSE){
                        $provider = Provider::find( $requests->confirmed_provider );
                        $provider->is_available = PROVIDER_AVAILABLE;
                        $provider->save();
                    }

                    // No longer need request specific rows from RequestMeta
                    RequestsMeta::where('request_id', '=', $request_id)->delete();

                   	// Send mail notification

                   	$email_data = array();

                   	$email_data['provider_name'] = $email_data['username'] = "";

                   	 if($user = User::find($requests->user_id)) {
                        $email_data['username'] = $user->first_name." ".$user->last_name;
                    }

                    if($provider = Provider::find($requests->confirmed_provider)) {
                        $email_data['provider_name'] = $provider->first_name. " " . $provider->last_name;
                    }

                   	$subject = Helper::tr('request_cancel_provider');
                   	$page = "emails.provider.request_cancel";

                   	Helper::send_email($page,$subject,$user->email,$email_data);

                    $response_array = Helper::null_safe(array(
                        'success' => true,
                        'request_id' => $request->id,
                        'status' => REQUEST_CANCELLED,
                        'message' => Helper::get_message(117),
                    ));
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

    public function history(Request $request)
	{
		$provider = Provider::find($request->id);

		$requests = Requests::where('confirmed_provider', '=', $provider->id)
							->where('requests.status', '=', REQUEST_COMPLETED)
							->where('requests.provider_status', '=', PROVIDER_RATED)
							->leftJoin('request_payments', 'requests.id', '=', 'request_payments.request_id')
							->leftJoin('providers', 'providers.id', '=', 'requests.confirmed_provider')
              ->leftJoin('service_types','service_types.id','=','requests.request_type')
							->leftJoin('users', 'users.id', '=', 'requests.user_id')
							->orderBy('request_start_time','desc')
							->select('requests.id','service_types.name as taxi_name',
              'requests.s_address as s_address','requests.d_address as d_address', 'requests.request_type as request_type', 'request_start_time as date',
									DB::raw('CONCAT(users.first_name, " ", users.last_name) as user_name'), 'users.picture',
									DB::raw('ROUND(request_payments.total) as total'))
									->get()
									->toArray();

		$requests = Helper::null_safe($requests);

        $response_array = array('success' => true,'requests' => $requests);
        
		$response = response()->json($response_array, 200);
		return $response;
	}

	public function single_request(Request $request) {

        $provider = Provider::find($request->id);

        $validator = Validator::make(
            $request->all(),
            array(
                'request_id' => 'required|integer|exists:requests,id,confirmed_provider,'.$request->id,
            ),
            array(
                'exists' => 'The :attribute doesn\'t belong to user:'.$provider->id,
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
                                ->leftJoin('cards','users.default_card','=' , 'cards.id')
                                ->select('providers.id as provider_id' , 'providers.picture as provider_picture',
                                    DB::raw('CONCAT(providers.first_name, " ", providers.last_name) as provider_name'),'user_ratings.rating','user_ratings.comment',
                                    DB::raw('ROUND(request_payments.base_price) as base_price'), DB::raw('ROUND(request_payments.tax_price) as tax_price'),
                                     DB::raw('ROUND(request_payments.time_price) as time_price'), DB::raw('ROUND(request_payments.total) as total'),
                                     'request_payments.payment_mode as payment_mode',
                                    'cards.id as card_id','cards.customer_id as customer_id',
                                    'cards.card_token','cards.last_four',
                                    'requests.id as request_id','requests.before_image','requests.after_image',
                                    'requests.user_id as user_id','users.picture as user_picture',
                                    DB::raw('CONCAT(users.first_name, " ", users.last_name) as user_name'))
                                ->get()->toArray();

            $response_array = Helper::null_safe(array('success' => true , 'data' => $requests));
        }

        return response()->json($response_array , 200);

    }

	// Get incoming requests

	public function get_incoming_request(Request $request)
	{
		$provider = Provider::find($request->id);

		// Don't check availability

		$request_meta = RequestsMeta::where('requests_meta.provider_id',$provider->id)
                        ->where('requests_meta.status',REQUEST_META_OFFERED)
                        ->where('requests_meta.is_cancelled',0)
                        ->leftJoin('requests', 'requests.id', '=', 'requests_meta.request_id')
                        ->leftJoin('service_types', 'service_types.id', '=', 'requests.request_type')
                        ->leftJoin('users', 'users.id', '=', 'requests.user_id')
                        ->select('requests.id as request_id', 'requests.request_type as request_type', 'service_types.name as service_type_name', 'request_start_time as request_start_time', 'requests.status', 'requests.jarak','requests.tipe_bayar','requests.pesandriver', 'requests.provider_status', 'requests.amount',  DB::raw('CONCAT(users.first_name, " ", users.last_name) as user_name'), 'users.picture as user_picture', 'users.id as user_id','requests.s_latitude','requests.d_latitude',
                        'requests.s_longitude','requests.d_longitude',
                        'requests.s_address','requests.d_address')
                        ->get()->toArray();

        $settings = Settings::where('key', 'provider_select_timeout')->first();
        $provider_timeout = $settings->value;

        $request_meta_data = array();
        foreach($request_meta as $each_request_meta){
            $each_request_meta['user_rating'] = DB::table('user_ratings')->where('user_id', $each_request_meta['user_id'])->avg('rating') ?: 0;

            $time_left_to_respond = $provider_timeout - (time() - strtotime($each_request_meta['request_start_time']) );
            $each_request_meta['time_left_to_respond'] = $time_left_to_respond;
            // Check the time is negative
            if($time_left_to_respond < 0) {
            	// Assign to the next provider
            	Helper::assign_next_provider($each_request_meta['request_id'],$request->id);
            }

            $request_meta_data[] = $each_request_meta;
        }

		$response_array = Helper::null_safe(array(
				'success' => true,
				'data' => $request_meta_data
		));

		$response = response()->json($response_array, 200);
		return $response;
	}

	public function request_status_check(Request $request)
	{
		$provider = Provider::find($request->id);

		$check_status = array(REQUEST_COMPLETED,REQUEST_CANCELLED,REQUEST_NO_PROVIDER_AVAILABLE);

		   $requests = Requests::where('requests.confirmed_provider', '=', $provider->id)
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

	public function message_get(Request $request)
	{
        $Messages = ChatMessage::where('provider_id', $request->id)
                ->where('request_id', $request->request_id);
                // ->orderBy('id', 'desc');

        $response_array = Helper::null_safe(array(
            'success' => true,
            'data' => $Messages->get()->toArray(),
        ));

        return response()->json($response_array, 200);
	}

	public function cod_paid_confirmation(Request $request) {


		$validator = Validator::make(
            $request->all(),
            array(
                'request_id' => 'required|integer|exists:requests,id,confirmed_provider,'.$request->id,
            ),
            array(
                'exists' => 'The :attribute doesn\'t belong to user:'.$request->id,
            )
        );

        if ($validator->fails()) {
            $error_messages = implode(',', $validator->messages()->all());
            $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=>$error_messages);

        } else {

        	$requests = Requests::find($request->request_id);

        	if($requests->status == WAITING_FOR_PROVIDER_CONFRIMATION_COD && $requests->status != REQUEST_RATING) {
	        	$requests->status = REQUEST_RATING;
	        	$requests->is_paid = DEFAULT_TRUE;
	        	$requests->save();

                 // Send Push Notification to User
                $title = Helper::tr('cod_paid_confirmation_title');
                $message = Helper::tr('cod_paid_confirmation_message');

                // Send notifications to the user
                $this->dispatch(new sendPushNotification($requests->user_id,USER,$requests->id,$title,$message,''));

                $data = array();

                $data['request_id'] = $requests->id;
                $data['user_id'] = $requests->user_id;
                $data['user_name'] = $data['user_picture'] = "";

                if($user = User::find($requests->user_id)) {
                    $data['user_name'] = $user->first_name.' '.$user->last_name;
                    $data['user_picture'] = $user->picture;
                }

	        	$response_array = array('success' => true , 'message' => Helper::get_message(119) , 'data' => $data);
	        } else {
	        	$response_array = array('success' => false , 'error' => Helper::get_error_message(155) ,'error_code' =>155);
	        }

        }

        return response()->json($response_array, 200);
	}
}
