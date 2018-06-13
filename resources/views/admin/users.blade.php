@extends('layouts.admin')

@section('title', tr('users'))

@section('content-header', tr('users'))

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li class="active"><i class="fa fa-user"></i> {{tr('users')}}</li>
@endsection

@section('content')

	@include('notification.notify')

	<div class="row">
        <div class="col-xs-12">
          <div class="box box-info">
            <div class="box-body">

            	@if(count($users) > 0)

	              	<table id="example1" class="table table-bordered table-striped">

						<thead>
						    <tr>
						      <th>{{tr('id')}}</th>
						      <th>{{tr('first_name')}}</th>
						      <th>{{tr('email')}}</th>
						      <th>{{tr('mobile')}}</th>
						      <th>{{tr('address')}}</th>
						      <!-- <th>{{tr('status')}}</th> -->
						      <th>{{tr('action')}}</th>
						    </tr>
						</thead>

						<tbody>
							@foreach($users as $i => $user)

							    <tr>
							      	<td>{{$i+1}}</td>
							      	<td>{{$user->first_name}}</td>
							      	<td>{{$user->email}}</td>
                      <td>{{$user->mobile}}</td>
							      	<td>{{$user->address}}</td>
							      <!-- <td>
							      		if($user->is_activated)
							      			<span class="label label-success">{{tr('approved')}}</span>
							       		else
							       			<span class="label label-warning">{{tr('pending')}}</span>
							       		endif
							       </td> -->
							      <td>
            							<ul class="admin-action btn btn-default">
            								<li class="dropdown">
								                <a class="dropdown-toggle" data-toggle="dropdown" href="#">
								                  {{tr('action')}} <span class="caret"></span>
								                </a>
								                <ul class="dropdown-menu">
								                  	<li role="presentation"><a role="menuitem" tabindex="-1" href="{{route('admin.edit.user' , array('id' => $user->id))}}">{{tr('edit_user')}}</a></li>
                                    <li role="presentation"><a role="menuitem" tabindex="-1" href="{{route('admin.view.user' , array('option' => 'user_details','id' => $user->id))}}">{{tr('view_user')}}</a></li>
								                  	<li role="presentation"><a role="menuitem" tabindex="-1" href="{{route('admin.user.history' , array('option' => 'user_history', 'id' => $user->id))}}">{{tr('view_history')}}</a></li>
								                  	<li role="presentation" class="divider"></li>
								                  	<li role="presentation">

								                  	 @if(Setting::get('admin_delete_control'))
								                  	 	<a role="button" href="javascript:;" class="btn disabled" style="text-align: left">{{tr('delete_user')}}</a>
								                  	 @else

								                  	 	<a role="menuitem" tabindex="-1"
								                  			onclick="return confirm('Are you sure?');" href="{{route('admin.delete.user', array('id' => $user->id))}}">{{tr('delete_user')}}
								                  		</a>

								                  	 @endif

								                  	</li>

								                </ul>
              								</li>
            							</ul>
							      </td>
							    </tr>
							@endforeach
						</tbody>
					</table>
				@else
					<h3 class="no-result">{{tr('no_user_found')}}</h3>
				@endif
            </div>
          </div>
        </div>
    </div>

@endsection
