@component('mail::message')

Dear Customers, <br>

Click the button below to reset password

@component('mail::button', ['url' => 'http://127.0.0.1:3000/reset-password/' . $data['token']])
Reset Password
@endcomponent

If you did not request a password reset, you can ignore this email, <br>

Kind Regards!<br>
{{ config('app.name') }} Ethiopia<br>
+251992758586<br>
@endcomponent
