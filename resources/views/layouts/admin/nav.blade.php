<aside class="main-sidebar">

    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">

        <!-- Sidebar user panel -->
        <div class="user-panel">
            <div class="pull-left image">
                <img src="@if(Auth::guard('admin')->user()->picture){{Auth::guard('admin')->user()->picture}} @else {{asset('admin-css/dist/img/avatar.png')}} @endif" class="img-circle" alt="User Image">
            </div>
            <div class="pull-left info">
                <p>{{Auth::guard('admin')->user()->name}}</p>
                <a href="{{route('admin.profile')}}">{{ tr('admin') }}</a>
            </div>
        </div>

        <!-- sidebar menu: : style can be found in sidebar.less -->
        <ul class="sidebar-menu">

            <li id="dashboard">
              <a href="{{route('admin.dashboard')}}">
                <i class="fa fa-dashboard"></i> <span>{{tr('dashboard')}}</span>
              </a>

            </li>

            <li class="treeview" id="maps">

                <a href="#">
                    <i class="fa fa-map"></i> <span>{{tr('map')}}</span> <i class="fa fa-angle-left pull-right"></i>
                </a>

                <ul class="treeview-menu">
                    <li id="user-map"><a href="{{route('admin.usermapview')}}"><i class="fa fa-circle-o"></i>{{tr('user_map_view')}}</a></li>
                    <li id="provider-map"><a href="{{route('admin.mapview')}}"><i class="fa fa-circle-o"></i>{{tr('provider_map_view')}}</a></li>
                </ul>

            </li>

            <li class="treeview" id="users">

                <a href="#">
                    <i class="fa fa-user"></i> <span>{{tr('users')}}</span> <i class="fa fa-angle-left pull-right"></i>
                </a>

                <ul class="treeview-menu">
                    <li id="add-user"><a href="{{route('admin.add.user')}}"><i class="fa fa-circle-o"></i>{{tr('add_user')}}</a></li>
                    <li id="view-user"><a href="{{route('admin.users')}}"><i class="fa fa-circle-o"></i>{{tr('view_users')}}</a></li>
                </ul>

            </li>

            <li class="treeview" id="providers">

                <a href="#">
                    <i class="fa fa-users"></i> <span>{{tr('providers')}}</span> <i class="fa fa-angle-left pull-right"></i>
                </a>

                <ul class="treeview-menu">
                    <li id="add-provider"><a href="{{route('admin.add.provider')}}"><i class="fa fa-circle-o"></i>{{tr('add_provider')}}</a></li>
                    <li id="view-provider"><a href="{{route('admin.providers')}}"><i class="fa fa-circle-o"></i>{{tr('view_providers')}}</a></li>
                </ul>

            </li>

            <li id="requests">
                <a href="{{route('admin.requests')}}">
                    <i class="fa fa-credit-card"></i> <span>{{tr('requests')}}</span>
                </a>
            </li>

            <li class="treeview" id="service_types">

                <a href="#">
                    <i class="fa fa-users"></i> <span>{{tr('service_types')}}</span> <i class="fa fa-angle-left pull-right"></i>
                </a>

                <ul class="treeview-menu">
                    <li id="add-service"><a href="{{route('admin.add.service.type')}}"><i class="fa fa-circle-o"></i>{{tr('add_service_type')}}</a></li>
                    <li id="view-service"><a href="{{route('admin.service.types')}}"><i class="fa fa-circle-o"></i>{{tr('view_service_type')}}</a></li>
                </ul>

            </li>

            <li class="treeview" id="rating_review">

                <a href="#">
                    <i class="fa fa-users"></i> <span>{{tr('rating_review')}}</span> <i class="fa fa-angle-left pull-right"></i>
                </a>

                <ul class="treeview-menu">
                    <li id="user-review"><a href="{{route('admin.user_reviews')}}"><i class="fa fa-circle-o"></i>{{tr('user_review')}}</a></li>
                    <li id="provider-review"><a href="{{route('admin.provider_reviews')}}"><i class="fa fa-circle-o"></i>{{tr('provider_review')}}</a></li>
                </ul>

            </li>

            <li class="treeview" id="documents">

                <a href="#">
                    <i class="fa fa-users"></i> <span>{{tr('documents')}}</span> <i class="fa fa-angle-left pull-right"></i>
                </a>

                <ul class="treeview-menu">
                    <li id="view-document"><a href="{{route('admin.documents')}}"><i class="fa fa-circle-o"></i>{{tr('view_documents')}}</a></li>
                    <li id="add-document"><a href="{{route('admin.add_document')}}"><i class="fa fa-circle-o"></i>{{tr('add_documents')}}</a></li>
                </ul>

            </li>

            <li id="payments">
                <a href="{{route('admin.payments')}}">
                    <i class="fa fa-credit-card"></i> <span>{{tr('user_payments')}}</span>
                </a>
            </li>

            <li id="settings">
                <a href="{{route('admin.settings')}}">
                    <i class="fa fa-gears"></i> <span>{{tr('settings')}}</span>
                </a>
            </li>

            <li id="profile">
                <a href="{{route('admin.profile')}}">
                    <i class="fa fa-diamond"></i> <span>{{tr('account')}}</span>
                </a>
            </li>

            <li>
                <a href="{{route('admin.logout')}}">
                    <i class="fa fa-sign-out"></i> <span>{{tr('sign_out')}}</span>
                </a>
            </li>

        </ul>

    </section>

    <!-- /.sidebar -->

</aside>
