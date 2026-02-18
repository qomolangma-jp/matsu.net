@extends('layouts.app')

@section('title', '登録完了 - 松.net')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card text-center">
            <div class="card-body p-5">
                @if(session('pending'))
                    <!-- 承認待ちの場合 -->
                    <div class="mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="currentColor" class="bi bi-hourglass-split text-warning" viewBox="0 0 16 16">
                            <path d="M2.5 15a.5.5 0 1 1 0-1h1v-1a4.5 4.5 0 0 1 2.557-4.06c.29-.139.443-.377.443-.59v-.7c0-.213-.154-.451-.443-.59A4.5 4.5 0 0 1 3.5 3V2h-1a.5.5 0 0 1 0-1h11a.5.5 0 0 1 0 1h-1v1a4.5 4.5 0 0 1-2.557 4.06c-.29.139-.443.377-.443.59v.7c0 .213.154.451.443.59A4.5 4.5 0 0 1 12.5 13v1h1a.5.5 0 0 1 0 1zm2-13v1c0 .537.12 1.045.337 1.5h6.326c.216-.455.337-.963.337-1.5V2zm3 6.35c0 .701-.478 1.236-1.011 1.492A3.5 3.5 0 0 0 4.5 13s.866-1.299 3-1.48zm1 0v3.17c2.134.181 3 1.48 3 1.48a3.5 3.5 0 0 0-1.989-3.158C8.978 9.586 8.5 9.052 8.5 8.351z"/>
                        </svg>
                    </div>
                    <h3 class="mb-3">登録申請を受け付けました</h3>
                    <p class="text-muted">
                        登録情報を受け付けました。<br>
                        承認が完了するまで今しばらくお待ちください。<br>
                        <br>
                        承認完了後、LINEでお知らせいたします。
                    </p>
                    
                    @auth
                        <div class="alert alert-info mt-4" style="text-align: left;">
                            <strong>📌 ログイン完了</strong><br>
                            <small>
                                ようこそ、{{ Auth::user()->full_name }} さん<br>
                                承認待ちの状態ですが、一部機能はご利用いただけます。
                            </small>
                        </div>
                    @endauth
                    
                    <div class="alert alert-warning mt-3" style="text-align: left;">
                        <strong>📌 承認について</strong><br>
                        <small>
                            ・参照名簿との照合結果により、学年管理者またはマスター管理者が承認を行います。<br>
                            ・通常、1〜3営業日以内に承認プロセスが完了します。<br>
                            ・ご不明な点がございましたら、お問い合わせください。
                        </small>
                    </div>
                @else
                    <!-- 自動承認の場合 -->
                    <div class="mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="currentColor" class="bi bi-check-circle-fill text-success" viewBox="0 0 16 16">
                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                        </svg>
                    </div>
                    <h3 class="mb-3">登録が完了しました！</h3>
                    <p class="text-muted">
                        松.netへのご登録ありがとうございます。<br>
                        同窓生の皆様とのつながりをお楽しみください。
                    </p>
                    
                    @auth
                        <div class="alert alert-success mt-4" style="text-align: left;">
                            <strong>✅ 自動ログイン完了</strong><br>
                            <small>
                                ようこそ、{{ Auth::user()->full_name }} さん<br>
                                参照名簿と完全一致したため、自動的に承認されました。
                            </small>
                        </div>
                    @endauth
                @endif
                
                <div class="mt-4">
                    @auth
                        @if(in_array(Auth::user()->role, ['master_admin', 'year_admin']))
                            <a href="{{ route('admin.users.index') }}" class="btn btn-primary">
                                <i class="bi bi-speedometer2"></i> 管理画面へ
                            </a>
                        @else
                            <a href="/" class="btn btn-primary">
                                <i class="bi bi-house"></i> トップページへ
                            </a>
                        @endif
                        
                        @if(config('app.env') === 'local')
                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle"></i> ログイン中: {{ Auth::user()->email ?? Auth::user()->line_id }}
                                </small>
                            </div>
                        @endif
                    @else
                        <a href="/" class="btn btn-primary">
                            トップページへ
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
