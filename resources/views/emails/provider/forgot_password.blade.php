<?php
use App\Helpers\Helper;
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1" />
    <title>Email Verification</title>
    <style type="text/css">
.ReadMsgBody { width: 100%; background-color: #ffffff; }
.ExternalClass { width: 100%; background-color: #ffffff; }
.ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div { line-height: 100%; }
html { width: 100%; }
body { -webkit-text-size-adjust: none; -ms-text-size-adjust: none; margin: 0; padding: 0; }
table { border-spacing: 0; border-collapse: collapse; table-layout: fixed; margin: 0 auto; }
table table table { table-layout: auto; }
img { display: block !important; }
table td { border-collapse: collapse; }
.yshortcuts a { border-bottom: none !important; }
a { color: #21b6ae; text-decoration: none; }

 @media only screen and (max-width: 640px) {
body { width: auto !important; }
table[class="table600"] { width: 450px !important; }
table[class="table-inner"] { width: 90% !important; }
table[class="table3-3"] { width: 100% !important; text-align: center !important; }
}
 @media only screen and (max-width: 479px) {
body { width: auto !important; }
table[class="table600"] { width: 290px !important; }
table[class="table-inner"] { width: 82% !important; }
table[class="table3-3"] { width: 100% !important; text-align: center !important; }
}
</style>

</head>

<body>

    <!-- Layout -->
    <table data-thumb="noti-1.jpg" data-module="Layou-1" data-bgcolor="Background Color" width="100%" border="0" align="center" cellpadding="0" cellspacing="0" bgcolor="#21b6ae">
        <tr>
            <td data-bg="Background" align="center" background="{{$site_url}}/email/bg-3.jpg" style="background-size:cover; background-position:top;">
                <table class="table600" width="600" border="0" align="center" cellpadding="0" cellspacing="0">
                    <tr>
                        <td height="50"></td>
                    </tr>

                    <!-- logo -->
                    <tr>
                        <td align="center" style="line-height: 0px;">
                            <img data-crop="false" mc:edit="logo" style="display:block; line-height:0px; font-size:0px; border:0px;max-height:70px;" src="{{Setting::get('site_logo')}}" alt="logo" />
                        </td>
                    </tr>
                    <!-- end logo -->


                    <tr>
                        <td height="30"></td>
                    </tr>
                    <tr>
                        <td align="center">
                            <table align="center" bgcolor="#FFFFFF" style="border-radius:4px; box-shadow: 0px -3px 0px #d4d2d2;" width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td height="50"></td>
                                </tr>

                                <tr>
                                    <td align="center">
                                        <table align="center" class="table-inner" width="500" border="0" cellspacing="0" cellpadding="0">
                                            <!-- title -->
                                            <tr>
                                                <td data-link-style="text-decoration:none; color:#21b6ae;" data-link-color="Content Link" data-color="Headline" data-size="Headline" mc:edit="title" align="center" style="font-family: 'Open Sans', Arial, sans-serif; font-size:28px; color:#3b3b3b; font-weight: bold; letter-spacing:1px;line-height:45px;">Don't worry, we all forget sometimes</td>
                                            </tr>
                                            <!-- end title -->

                                            <tr>
                                                <td align="center">
                                                    <table width="25" border="0" cellspacing="0" cellpadding="0">
                                                        <tr>
                                                            <td data-border-bottom-color="Main Color" height="20" style="border-bottom:2px solid #21b6ae;"></td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td height="20"></td>
                                            </tr>
                                            <!-- content -->
                                            <tr>
                                                <td data-link-style="text-decoration:none; color:#21b6ae;" data-link-color="Content Link" data-color="Main Text" data-size="Main Text" mc:edit="content" align="center" style="font-family: 'Open Sans', Arial, sans-serif; font-size:13px; color:#7f8c8d; line-height:30px;">
                                                    Hi, {{$email_data['user']->first_name}} {{$email_data['user']->last_name}}. You've recently asked to reset the password for this Smart Car account: {{$email_data['user']->email}}.

                                                    <br>
                                                    <!-- To update your password, click the button below -->
                                                    Your New Password : {{$email_data['password']}}
                                                    <br>
                                                    Thank you!
                                                </td>
                                            </tr>
                                            <!-- end content -->
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td height="30"></td>
                                </tr>

                                <!-- button -->
                                <!-- <tr>
                                    <td align="center">
                                        <table data-bgcolor="Main Color" align="center" bgcolor="#21b6ae" border="0" cellspacing="0" cellpadding="0" style=" border-radius:30px; box-shadow: 0px 1px 0px #d4d2d2;">
                                            <tr>
                                                <td mc:edit="button" height="55" align="center" style="font-family: 'Open Sans', Arial, sans-serif; font-size:18px; color:#7f8c8d; line-height:30px; font-weight: bold;padding-left: 25px;padding-right: 25px;">
                                                    <a href="#" style="color:#ffffff;text-decoration:none;" data-color="Button Link">Reset My Password</a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr> -->
                                <!-- end button -->

                                <tr>
                                    <td height="45"></td>
                                </tr>

                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td height="30"></td>
                    </tr>

                    <!-- copyright -->
                    <tr>
                        <td data-link-style="text-decoration:none; color:#3cb2d0;" data-link-color="Copyright Link" data-color="Copyright" data-size="Copyright" mc:edit="copyright" align="center" style="font-family: 'Open Sans', Arial, sans-serif; font-size:13px; color:#ffffff; line-height:30px;">
                            © 2016
                            <span style="color:#3cb2d0; font-weight: bold;">{{Setting::get('site_name')}}</span>
                            . All Rights Reserved.
                        </td>
                    </tr>
                    <!-- end copyright -->


                    <tr>
                        <td height="30"></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <!-- end Layout -->


</body>
</html>
