<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Bestellungen') · Laravel Fortgeschrittene</title>
    <style>
        :root {
            --navy: #0F1626; --coral: #FF5A3C; --ink: #1F2733; --mute: #55617A;
            --light: #F4F6FB; --line: #E2E7F1; --green: #1F8A4C; --red: #C0392B;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; font-family: -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: var(--ink); background: var(--light); line-height: 1.5;
        }
        header {
            background: var(--navy); color: #fff; padding: 16px 24px;
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;
        }
        header .brand { font-weight: 700; font-size: 18px; }
        header .brand small { display: block; font-weight: 400; color: #9FB0D0; font-size: 12px; }
        header nav a, header form button { color: #fff; }
        .tenant-switch { display: flex; align-items: center; gap: 8px; font-size: 14px; }
        .tenant-switch select { padding: 6px 8px; border-radius: 6px; border: none; }
        .container { max-width: 960px; margin: 24px auto; padding: 0 24px; }
        nav.tabs { display: flex; gap: 8px; margin-bottom: 20px; }
        nav.tabs a { text-decoration: none; padding: 8px 14px; border-radius: 8px; color: var(--navy); background: #fff; border: 1px solid var(--line); font-weight: 600; }
        nav.tabs a.active { background: var(--coral); color: #fff; border-color: var(--coral); }
        h1 { font-size: 22px; margin: 0 0 16px; }
        .card { background: #fff; border: 1px solid var(--line); border-radius: 12px; padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--line); font-size: 14px; }
        th { color: var(--mute); font-weight: 600; text-transform: uppercase; letter-spacing: .03em; font-size: 12px; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; background: #EEF1F7; color: var(--mute); }
        .btn { display: inline-block; text-decoration: none; cursor: pointer; border: none; border-radius: 8px; padding: 9px 16px; font-size: 14px; font-weight: 600; }
        .btn-primary { background: var(--coral); color: #fff; }
        .btn-ghost { background: #fff; color: var(--navy); border: 1px solid var(--line); }
        .row { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
        label { display: block; font-size: 13px; font-weight: 600; color: var(--mute); margin-bottom: 4px; }
        input[type=text], input[type=email], input[type=number], select {
            padding: 9px 10px; border: 1px solid var(--line); border-radius: 8px; font-size: 14px; width: 100%; background: #fff;
        }
        .field { margin-bottom: 16px; }
        .item-row { display: grid; grid-template-columns: 1fr 120px 40px; gap: 10px; align-items: end; margin-bottom: 10px; }
        .flash { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        .flash-ok { background: #E7F6EC; color: var(--green); border: 1px solid #BFE6CC; }
        .flash-err { background: #FBEAE8; color: var(--red); border: 1px solid #F2C7C2; }
        .flash-err ul { margin: 6px 0 0; padding-left: 18px; }
        .muted { color: var(--mute); font-size: 13px; }
        .right { text-align: right; }
        .link-remove { background: none; border: none; color: var(--red); cursor: pointer; font-size: 20px; line-height: 1; }
    </style>
</head>
<body>
    <header>
        <div class="brand">
            Bestell-Backoffice
            <small>Laravel für Fortgeschrittene · GFU s5618</small>
        </div>
        @isset($tenants)
            <form class="tenant-switch" method="POST" action="{{ route('tenant.switch') }}">
                @csrf
                <label for="tenant_id" style="margin:0;color:#9FB0D0;">Mandant</label>
                <select id="tenant_id" name="tenant_id" onchange="this.form.submit()">
                    @foreach ($tenants as $t)
                        <option value="{{ $t->id }}" @selected(isset($currentTenant) && $currentTenant->id === $t->id)>
                            {{ $t->name }}
                        </option>
                    @endforeach
                </select>
            </form>
        @endisset
    </header>

    <div class="container">
        <nav class="tabs">
            <a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.index') ? 'active' : '' }}">Bestellungen</a>
            <a href="{{ route('orders.create') }}" class="{{ request()->routeIs('orders.create') ? 'active' : '' }}">Neue Bestellung</a>
            <a href="{{ route('exercises.dto') }}" class="{{ request()->routeIs('exercises.dto') ? 'active' : '' }}">Übung 1: DTO</a>
            <a href="{{ route('exercises.service') }}" class="{{ request()->routeIs('exercises.service') ? 'active' : '' }}">Übung 2: Service</a>
            <a href="{{ route('exercises.action') }}" class="{{ request()->routeIs('exercises.action') ? 'active' : '' }}">Übung 3: Action</a>
            <a href="{{ route('exercises.event') }}" class="{{ request()->routeIs('exercises.event') ? 'active' : '' }}">Übung 4: Event</a>
        </nav>

        @if (session('status'))
            <div class="flash flash-ok">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="flash flash-err">
                <strong>Bitte prüfen:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </div>
</body>
</html>
