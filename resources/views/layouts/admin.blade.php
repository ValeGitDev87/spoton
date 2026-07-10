<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'SpotOn Admin' }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #101828;
            background: #f6f7f9;
        }
        a { color: inherit; }
        .topbar {
            height: 60px;
            border-bottom: 1px solid #e5e7eb;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
        }
        .brand { font-weight: 800; letter-spacing: .2px; }
        .userline { display: flex; align-items: center; gap: 12px; font-size: 14px; color: #475467; }
        .shell { display: grid; grid-template-columns: 220px 1fr; min-height: calc(100vh - 60px); }
        .sidebar {
            background: #fff;
            border-right: 1px solid #e5e7eb;
            padding: 18px 12px;
        }
        .nav-link {
            display: block;
            padding: 10px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 650;
            color: #344054;
        }
        .nav-link.active { background: #111827; color: #fff; }
        .content { padding: 24px; }
        .page-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
        }
        h1 { margin: 0; font-size: 24px; }
        h2 { margin: 0 0 14px; font-size: 18px; }
        .panel {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
        }
        .toolbar {
            display: flex;
            gap: 8px;
            align-items: end;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }
        label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #344054;
            margin-bottom: 5px;
        }
        input, select {
            width: 100%;
            height: 40px;
            border: 1px solid #d0d5dd;
            border-radius: 8px;
            padding: 8px 10px;
            font: inherit;
            background: #fff;
        }
        .field { margin-bottom: 14px; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .grid3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
        .stat-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
        }
        .stat-card span { display: block; color: #667085; font-size: 13px; font-weight: 750; }
        .stat-card strong { display: block; margin-top: 8px; font-size: 30px; line-height: 1; }
        .stat-card small { display: block; margin-top: 8px; color: #475467; }
        .check-row { display: flex; align-items: center; gap: 8px; margin: 6px 0 16px; }
        .check-row input { width: 18px; height: 18px; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            border: 0;
            border-radius: 8px;
            padding: 9px 13px;
            font: inherit;
            font-weight: 750;
            text-decoration: none;
            cursor: pointer;
            background: #111827;
            color: #fff;
        }
        .btn.secondary { background: #e5e7eb; color: #111827; }
        .btn.danger { background: #b42318; color: #fff; }
        .btn.link { background: transparent; color: #344054; padding-inline: 6px; }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            padding: 11px 10px;
            border-bottom: 1px solid #eef0f3;
            text-align: left;
            vertical-align: middle;
            font-size: 14px;
        }
        th { font-size: 12px; text-transform: uppercase; color: #667085; letter-spacing: .04em; }
        .actions { display: flex; gap: 8px; justify-content: flex-end; }
        .badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 3px 8px;
            font-size: 12px;
            font-weight: 750;
            background: #ecfdf3;
            color: #027a48;
        }
        .badge.off { background: #f2f4f7; color: #667085; }
        .badge.status-active { background: #ecfdf3; color: #027a48; }
        .badge.status-expired { background: #fffaeb; color: #b54708; }
        .badge.status-removed { background: #fef3f2; color: #b42318; }
        .badge.status-flagged { background: #fdf2fa; color: #c11574; }
        .alert {
            margin-bottom: 14px;
            padding: 10px 12px;
            border-radius: 8px;
            background: #ecfdf3;
            color: #027a48;
            font-weight: 650;
        }
        .errors {
            margin-bottom: 14px;
            padding: 10px 12px;
            border-radius: 8px;
            background: #fef3f2;
            color: #b42318;
        }
        .pagination { margin-top: 14px; }
        @media (max-width: 820px) {
            .shell { grid-template-columns: 1fr; }
            .sidebar { border-right: 0; border-bottom: 1px solid #e5e7eb; }
            .grid, .grid3, .stats-grid { grid-template-columns: 1fr; }
            .page-head { align-items: flex-start; flex-direction: column; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">SpotOn Admin</div>
        @auth
            <div class="userline">
                <span>{{ auth()->user()->display_name }}</span>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn secondary" type="submit">Esci</button>
                </form>
            </div>
        @endauth
    </header>

    <div class="shell">
        <aside class="sidebar">
            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
            <a class="nav-link {{ request()->routeIs('admin.locations.*') ? 'active' : '' }}" href="{{ route('admin.locations.index') }}">Luoghi</a>
            <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">Utenti</a>
            <a class="nav-link {{ request()->routeIs('admin.posts.*') ? 'active' : '' }}" href="{{ route('admin.posts.index') }}">Post</a>
        </aside>
        <main class="content">
            @yield('content')
        </main>
    </div>
</body>
</html>
