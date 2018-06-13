@extends('layouts.admin')

@section('title', tr('settings'))

@section('content-header', tr('settings'))

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li class="active"><i class="fa fa-gears"></i> {{tr('settings')}}</li>
@endsection

@section('content')

@include('notification.notify')

  <section class="content">
                

    <div class="row">
        <div class="site_setting_outer">

            <div class="box box-info">

                <div class="box-header with-border">
                    <h3 class="box-title">{{tr('site_settings')}}</h3>
                </div>
                    <div class="box-body">

                        <div class="col-md-6">
                          <form action="{{route('admin.save.settings')}}" method="POST" enctype="multipart/form-data" role="form">

                            <div class="form-group">
                                <label>{{ tr('site_name') }}</label>
                                 <input type="text" name="site_name" value="{{ Setting::get('site_name', '')  }}" required class="form-control">
                            </div>

                            <div class="form-group">
                                <label>{{ tr('site_logo') }}</label>
                                   @if(Setting::get('site_logo')!='')
                                   <img class="setting_logo"  src="{{Setting::get('site_logo')}}">
                                   @endif
                                    <input name="picture" type="file">
                                    <p class="help-block">{{ tr('upload_message') }}</p>
                            </div>

                            <div class="form-group">
                                <label>{{ tr('site_icon') }}</label>
                                   @if(Setting::get('site_icon')!='')
                                  <img class="setting_logo"  src="{{Setting::get('site_icon')}}">
                                  @endif
                                    <input name="site_icon" type="file">
                                    <p class="help-block">{{ tr('upload_message') }}</p>
                            </div>

                            <div class="form-group">
                                <label>{{ tr('email_logo') }}</label>
                                 @if(Setting::get('mail_logo')!='')
                                <img class="setting_logo"  src="{{Setting::get('mail_logo')}}">
                                @endif
                                  <input name="email_logo" type="file">
                                  <p class="help-block">{{ tr('upload_message') }}</p>
                            </div>
                          
                  
                        </div>

                        <div class="col-md-6">
                              <div class="form-group">
                                <label>{{ tr('provider_time') }}</label>
                                 <input type="number" name="provider_select_timeout" value="{{ Setting::get('provider_select_timeout', '')  }}" required class="form-control">
                            </div>

                             <div class="form-group">
                                <label>{{ tr('search_radius') }}</label>
                                <input type="number" name="search_radius" value="{{ Setting::get('search_radius', '')  }}" required class="form-control">
                            </div>

                            <div class="form-group">
                                <label>{{ tr('base_price') }}</label>
                                 <input type="number" name="base_price" value="{{ Setting::get('base_price', '')  }}" required class="form-control">
                            </div>

                            <div class="form-group">
                                <label>{{ tr('price_per_min') }}</label>
                               <input type="number" name="price_per_minute" value="{{ Setting::get('price_per_minute', '')  }}" required class="form-control">
                            </div>

                            <div class="form-group">
                                <label>{{ tr('price_per_unit_distance') }}</label>
                                <input type="number" name="price_per_unit_distance" value="{{ Setting::get('price_per_unit_distance', '')  }}" required class="form-control">
                            </div>

                            <div class="form-group">
                                <label>{{ tr('default_distance_unit') }}</label>
                                 <select name="default_distance_unit" value="" required class="form-control">
                                 <option value="">{{ tr('select') }}</option>
                                    @if(Setting::get('default_distance_unit')!='')
                                      @if(Setting::get('default_distance_unit') == 'miles')
                                        <option value="miles" selected="true">miles</option>
                                        <option value="kms" >kms</option>
                                      @else
                                        <option value="miles" >miles</option>
                                        <option value="kms" selected="true">kms</option>
                                      @endif
                                    @else
                                    <option value="miles">miles</option>
                                    <option value="kms">kms</option>
                                    @endif
                                    
                                  </select>
                             <!--     <select name="default_distance_unit" value="" required class="form-control">
                                    <option value="">{{ tr('select') }}</option>
                                    @if(Setting::get('default_distance_unit')!='')
                                    <option value="miles">{{ Setting::get('default_distance_unit') }}</option>
                                    @else
                                    
                                    <option value="miles">miles</option>
                                    <option value="kms">kms</option>
                                    @endif

                                  </select> -->
                            </div>

                            <div class="form-group">
                                <label>{{ tr('tax_price') }}</label>
                                <input type="number" name="tax_price" value="{{ Setting::get('tax_price', '')  }}" required class="form-control">
                            </div>

                            <div class="form-group">
                                <label>{{ tr('price_per_service') }}</label>
                                 <select name="price_per_service" value="" required class="form-control">
                                 <option value="">{{ tr('select') }}</option>
                                    @if(Setting::get('price_per_service')!='')
                                      @if(Setting::get('price_per_service') == 1)
                                        <option value="1" selected="true">Yes</option>
                                        <option value="0" >No</option>
                                      @else
                                        <option value="1" >Yes</option>
                                        <option value="0" selected="true">No</option>
                                      @endif
                                    @else
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                    @endif
                                    
                                  </select>
                              <!--   <input type="number" name="price_per_service" value="{{ Setting::get('price_per_service', '')  }}" required class="form-control"> -->
                            </div>

                            <div class="form-group">
                                <label>
                                  {{ tr('currency') }} ( <strong>{{ Setting::get('currency', '')  }} </strong>)
                                </label>
                                 <select name="currency" value="" required class="form-control">
                                    @if(Setting::get('currency')!='')
                                    <option value="{{ $symbol }}">{{ $currency }}</option>
                                    @else
                                    <option value="">{{ tr('select') }}</option>
                                    @endif
                                    <option value="$">US Dollar (USD)</option>
                                    <option value="₹"> Indian Rupee (INR)</option>
                                    <option value="د.ك">Kuwaiti Dinar (KWD)</option>
                                    <option value="د.ب">Bahraini Dinar (BHD)</option>
                                    <option value="﷼">Omani Rial (OMR)</option>
                                    <option value="£">British Pound (GBP)</option>
                                    <option value="€">Euro (EUR)</option>
                                    <option value="CHF">Swiss Franc (CHF)</option>
                                    <option value="ل.د">Libyan Dinar (LYD)</option>
                                    <option value="B$">Bruneian Dollar (BND)</option>
                                    <option value="S$">Singapore Dollar (SGD)</option>
                                    <option value="AU$"> Australian Dollar (AUD)</option>
                                    </select>
                            </div>
                         </div>

                  </div>
                  <!-- /.box-body -->

                  <div class="box-footer">
                      <button type="reset" class="btn btn-danger">Cancel</button>
                      <button type="submit" class="btn btn-success pull-right">Submit</button>
                  </div>
                </form>

            </div>
        </div>

    </div>


            </section>  
   <!--  <div class="row">

        <div class="col-md-12">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">{{tr('site_settings')}}</h3>
                </div>

                <form class="form-horizontal bordered-group" action="{{route('admin.save.settings')}}" method="POST" enctype="multipart/form-data" role="form">
                  <div class="form-group">
                    <label class="col-sm-2 control-label">{{ tr('site_name') }}</label>
                    <div class="col-sm-8">
                      <input type="text" name="site_name" value="{{ Setting::get('site_name', '')  }}" required class="form-control">
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="col-sm-2 control-label">{{ tr('site_logo') }}</label>
                    <div class="col-sm-8">
                    @if(Setting::get('site_logo')!='')
                    <img style="height: 90px; margin-bottom: 15px; border-radius:2em;" src="{{Setting::get('site_logo')}}">
                    @endif
                      <input name="picture" type="file">
                      <p class="help-block">{{ tr('upload_message') }}</p>
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="col-sm-2 control-label">{{ tr('site_icon') }}</label>
                    <div class="col-sm-8">
                    @if(Setting::get('site_icon')!='')
                    <img style="height: 90px; margin-bottom: 15px; border-radius:2em;" src="{{Setting::get('site_icon')}}">
                    @endif
                      <input name="site_icon" type="file">
                      <p class="help-block">{{ tr('upload_message') }}</p>
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="col-sm-2 control-label">{{ tr('email_logo') }}</label>
                    <div class="col-sm-8">
                    @if(Setting::get('mail_logo')!='')
                    <img style="height: 90px; margin-bottom: 15px; border-radius:2em;" src="{{Setting::get('mail_logo')}}">
                    @endif
                      <input name="email_logo" type="file">
                      <p class="help-block">{{ tr('upload_message') }}</p>
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="col-sm-2 control-label">{{ tr('provider_time') }}</label>
                    <div class="col-sm-8">
                      <input type="number" name="provider_select_timeout" value="{{ Setting::get('provider_select_timeout', '')  }}" required class="form-control">
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="col-sm-2 control-label">{{ tr('search_radius') }}</label>
                    <div class="col-sm-8">
                      <input type="number" name="search_radius" value="{{ Setting::get('search_radius', '')  }}" required class="form-control">
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="col-sm-2 control-label">{{ tr('base_price') }}</label>
                    <div class="col-sm-8">
                      <input type="number" name="base_price" value="{{ Setting::get('base_price', '')  }}" required class="form-control">
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="col-sm-2 control-label">{{ tr('price_per_min') }}</label>
                    <div class="col-sm-8">
                      <input type="number" name="price_per_minute" value="{{ Setting::get('price_per_minute', '')  }}" required class="form-control">
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="col-sm-2 control-label">{{ tr('price_per_unit_distance') }}</label>
                    <div class="col-sm-8">
                      <input type="number" name="price_per_unit_distance" value="{{ Setting::get('price_per_unit_distance', '')  }}" required class="form-control">
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="col-sm-2 control-label">{{ tr('default_distance_unit') }}</label>
                    <div class="col-sm-8">
                      <select name="default_distance_unit" value="" required class="form-control">
                      @if(Setting::get('default_distance_unit')!='')
                      <option value="miles">{{ Setting::get('default_distance_unit') }}</option>
                      @else
                      <option value="">{{ tr('select') }}</option>
                      @endif
                      <option value="miles">miles</option>
                      <option value="kms">kms</option>
                      </select>
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="col-sm-2 control-label">{{ tr('tax_price') }}</label>
                    <div class="col-sm-8">
                      <input type="number" name="tax_price" value="{{ Setting::get('tax_price', '')  }}" required class="form-control">
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="col-sm-2 control-label">{{ tr('price_per_service') }}</label>
                    <div class="col-sm-8">
                      <input type="number" name="price_per_service" value="{{ Setting::get('price_per_service', '')  }}" required class="form-control">
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="col-sm-2 control-label">{{ tr('currency') }} ( <strong>{{ Setting::get('currency', '')  }} </strong>)</label>
                    <div class="col-sm-8">
                      <select name="currency" value="" required class="form-control">
                      @if(Setting::get('currency')!='')
                      <option value="{{ $symbol }}">{{ $currency }}</option>
                      @else
                      <option value="">{{ tr('select') }}</option>
                      @endif
                      <option value="$">US Dollar (USD)</option>
                      <option value="₹"> Indian Rupee (INR)</option>
                      <option value="د.ك">Kuwaiti Dinar (KWD)</option>
                      <option value="د.ب">Bahraini Dinar (BHD)</option>
                      <option value="﷼">Omani Rial (OMR)</option>
                      <option value="£">British Pound (GBP)</option>
                      <option value="€">Euro (EUR)</option>
                      <option value="CHF">Swiss Franc (CHF)</option>
                      <option value="ل.د">Libyan Dinar (LYD)</option>
                      <option value="B$">Bruneian Dollar (BND)</option>
                      <option value="S$">Singapore Dollar (SGD)</option>
                      <option value="AU$"> Australian Dollar (AUD)</option>
                      </select>
                    </div>
                  </div>


                  <!-- <div class="form-group">
                   <label class="col-sm-2 control-label">{{ tr('default_lang') }}</label>
                    <div class="col-sm-8">
                      <div class="checkbox">
                            <select name="default_lang" class="form-control">
                            <option value="en">en</option>
                            </select>
                        </div>

                      </div>
                  </div>
                  <div class="form-group">
                    <label class="col-sm-2 control-label">{{ tr('manual_request') }}</label>
                    <div class="col-sm-8">
                      <div class="checkbox">
                          <label>
                            <input name="manual_request"  @if(Setting::get('manual_request') ==1) checked  @else  @endif  value="1"  type="checkbox">{{ tr('manual_request') }}</label>
                        </div>
                    </div>
                  </div> -->

                  <!-- <div class="box-footer">
                      <button type="reset" class="btn btn-danger">{{tr('cancel')}}</button>
                      <button type="submit" class="btn btn-success pull-right">{{tr('submit')}}</button>
                  </div>

                </form>

            </div>
        </div>

    </div> --> 


@endsection
