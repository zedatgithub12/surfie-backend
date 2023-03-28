<!-- @component('mail::message')

Dear {{$data['name']}},

{{$data['message']}},


With regards!<br>
{{ config('app.name') }} Ethiopia<br>
+251992758586<br>

@endcomponent -->

Hello {{ $user->name }},

This is a reminder that your subscription will expire in 7 days on {{ $user->duedate }}.

Please renew your subscription to continue using our service.

Check with customer support for further assistance..

Best regards,
Surfie Ethiopia
+251992758586
