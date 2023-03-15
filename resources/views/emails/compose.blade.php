@component('mail::message')

Dear Customer,

{{$data['description']}},

Thanks,<br>
<!-- {{ config('app.name') }} -->
@endcomponent