@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Surfie')
<img src="https://afromina-digitals.com/wp-content/uploads/2023/03/Surfie-Color-@4x.png" class="logo" alt="Surfie Ethiopia">
@else
{{ $slot }}
@endif
</a>
</td>
</tr>
