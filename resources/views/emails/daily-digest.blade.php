<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trainingcamp today</title>
</head>
<body style="margin:0;padding:24px;background:#f1f5f9;font-family:Nunito,Segoe UI,Arial,sans-serif;color:#0f172a">
    <table role="presentation" width="100%" style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;padding:24px">
        <tr><td>
            <h1 style="margin:0 0 4px;font-size:20px">Good morning{{ $user->first_name ? ', ' . $user->first_name : '' }}</h1>
            <p style="margin:0 0 20px;color:#64748b">Here is what needs your attention today.</p>

            @foreach($sections as $section)
            <h2 style="margin:20px 0 8px;font-size:15px;text-transform:uppercase;letter-spacing:.04em;color:#8f6a0c">{{ $section['team']->name }}</h2>

            @if($section['sparrings']->isNotEmpty())
            <p style="margin:0 0 4px;font-weight:700">Sparrings today</p>
            <ul style="margin:0 0 12px;padding-left:18px">
                @foreach($section['sparrings'] as $sparring)
                <li>{{ $sparring->start }}–{{ $sparring->end }} · {{ $sparring->title ?: 'Sparring' }}
                    @if($sparring->participants->isNotEmpty()) ({{ $sparring->participants->pluck('full_name')->implode(' vs ') }})@endif
                </li>
                @endforeach
            </ul>
            @endif

            @if($section['reminders']['items']->isNotEmpty())
            <p style="margin:0 0 4px;font-weight:700">Reminders</p>
            <ul style="margin:0 0 12px;padding-left:18px">
                @foreach($section['reminders']['items'] as $reminder)
                <li><a href="{{ $reminder['url'] }}" style="color:#0f172a">{{ $reminder['title'] }}</a> <span style="color:#64748b">· {{ $reminder['subtitle'] }}</span></li>
                @endforeach
            </ul>
            @if($section['reminders']['count'] > $section['reminders']['items']->count())
            <p style="margin:0 0 12px;color:#64748b">+{{ $section['reminders']['count'] - $section['reminders']['items']->count() }} more</p>
            @endif
            @endif
            @endforeach

            <p style="margin:24px 0 0;font-size:13px;color:#64748b">
                <a href="{{ route('home') }}" style="color:#8f6a0c">Open Trainingcamp</a> ·
                <a href="{{ route('user.notifications') }}" style="color:#8f6a0c">Turn off this email</a>
            </p>
        </td></tr>
    </table>
</body>
</html>
