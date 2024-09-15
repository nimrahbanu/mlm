<div id="wrapper" dir="ltr" style="background-color: #f7f7f7; margin: 0; padding: 70px 0; width: 100%; -webkit-text-size-adjust: none;">
    <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
        <tbody>
            <tr>
                <td align="center" valign="top">
                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="template_container" style="background-color: #ffffff; border: 1px solid #dedede; box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1); border-radius: 3px;">
                        <tbody>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- Header -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_header" style="background-image: linear-gradient(to right, #fe7a2b , #fece3c); color: #ffffff; border-bottom: 0; font-weight: bold; line-height: 100%; vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; border-radius: 3px 3px 0 0;">
                                        <tbody>
                                            <tr>
                                                <td id="header_wrapper" style="padding: 36px 48px; display: block;">
                                                    <h1 style="font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; font-size: 30px; font-weight: 300; line-height: 150%; margin: 0; text-align: left; text-shadow: 0 1px 0 #ab79a1; color: #ffffff; background-color: inherit;">User Registration Successfully Completed</h1>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <!-- End Header -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- Body -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_body">
                                        <tbody>
                                            <tr>
                                                <td valign="top" id="body_content" style="background-color: #ffffff;">
                                                    <!-- Content -->
                                                    <table border="0" cellpadding="20" cellspacing="0" width="100%">
                                                        <tbody>
                                                            <tr>
                                                                <td valign="top" style="padding: 48px 48px 32px;">
                                                                    <div id="body_content_inner" style="color: #636363; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; font-size: 16px; line-height: 150%; text-align: left;">
                                                                        <p style="margin: 0 0 16px;">Hi {{$mailBody['name']}},</p>
                                                                        <p style="margin: 16px 0;">Your registration was successfully completed.</p>
                                                                        <p><strong>Name:</strong> {{$mailBody['name']}}</p>
                                                                        <p><strong>Email:</strong> {{$mailBody['email']}}</p>
                                                                        <p><strong>Password:</strong> {{$mailBody['password']}}</p>
                                                                        <p><strong>Sponsor ID:</strong> {{$mailBody['sponsor_id']}}</p>
                                                                        <p><strong>Phone Pay No:</strong> {{$mailBody['phone_pay_no']}}</p>
                                                                        <p style="margin: 0 0 16px;">Thanks,</p>
                                                                        <p style="margin: 0 0 16px;">The {{env('APP_NAME')}} Team</p>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    <!-- End Content -->
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <!-- End Body -->
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
</div>
