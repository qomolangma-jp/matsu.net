/**
 * 松.net - 新規登録フォーム JavaScript
 */

// ========== 画面ログ（LIFF内では console が見えないため） ==========
var _debugPanel = (function () {
    var panel = document.createElement('div');
    panel.id = 'debug-panel';
    panel.style.cssText = 'position:fixed;bottom:0;left:0;right:0;max-height:45vh;overflow-y:auto;background:rgba(0,0,0,0.88);color:#0f0;font-size:11px;font-family:monospace;padding:6px 8px;z-index:99999;white-space:pre-wrap;word-break:break-all;';

    // DOMContentLoaded を待たずに即挿入を試みる
    function mountPanel() {
        if (document.body && !document.getElementById('debug-panel')) {
            document.body.appendChild(panel);
        }
    }
    if (document.body) {
        mountPanel();
    } else {
        document.addEventListener('DOMContentLoaded', mountPanel);
    }

    function appendLog(label, args, color) {
        mountPanel(); // まだなければ再試行
        var line = document.createElement('div');
        line.style.color = color;
        var ts = new Date().toISOString().substr(11, 12);
        var msg = Array.from(args).map(function (a) {
            try { return typeof a === 'object' ? JSON.stringify(a) : String(a); }
            catch (e) { return String(a); }
        }).join(' ');
        line.textContent = '[' + ts + '] ' + label + ' ' + msg;
        panel.appendChild(line);
        panel.scrollTop = panel.scrollHeight;
    }

    var _log   = console.log.bind(console);
    var _warn  = console.warn.bind(console);
    var _error = console.error.bind(console);
    console.log   = function () { _log.apply(console, arguments);   appendLog('LOG',   arguments, '#0f0'); };
    console.warn  = function () { _warn.apply(console, arguments);  appendLog('WARN',  arguments, '#ff0'); };
    console.error = function () { _error.apply(console, arguments); appendLog('ERR',   arguments, '#f55'); };

    window.addEventListener('error', function (e) {
        appendLog('UNCAUGHT', [e.message + ' (' + e.filename + ':' + e.lineno + ')'], '#f55');
    });
    window.addEventListener('unhandledrejection', function (e) {
        appendLog('PROMISE', [String(e.reason)], '#f55');
    });

    return { log: appendLog };
})();

console.log('=== JS loaded ===');
console.log('URL:', window.location.href);
console.log('UA:', navigator.userAgent.substr(0, 100));
// ===================================================================

function setStatus(msg, type) {
    const el = document.getElementById('liff-status');
    if (!el) return;
    const colors = {
        info:    { bg: '#f8f9fa', border: '#dee2e6', color: '#495057' },
        success: { bg: '#d1e7dd', border: '#a3cfbb', color: '#0a3622' },
        warning: { bg: '#fff3cd', border: '#ffc107', color: '#664d03' },
        error:   { bg: '#f8d7da', border: '#f1aeb5', color: '#58151c' },
    };
    const c = colors[type] || colors.info;
    el.style.background = c.bg;
    el.style.borderColor = c.border;
    el.style.color = c.color;
    el.innerHTML = msg;
    console.log('[LIFF Status]', msg.replace(/<[^>]+>/g, ''));
}

function isLocalEnvironment() {
    return window.location.hostname === 'matsu.localhost' ||
           window.location.hostname === 'localhost' ||
           window.location.hostname === '127.0.0.1';
}

/**
 * 既存ユーザーチェック（ページリロード）
 */
function checkExistingUser(lineId) {
    if (!lineId || lineId.trim() === '') {
        return;
    }

    const urlParams = new URLSearchParams(window.location.search);
    const currentLineId = urlParams.get('line_id');

    if (currentLineId === lineId) {
        return;
    }

    const newUrl = `${window.location.pathname}?line_id=${encodeURIComponent(lineId)}`;
    console.log('既存ユーザーチェック:', lineId);
    window.location.href = newUrl;
}

/**
 * ローカル環境：LINE ID 手動入力による自動ログインチェック
 */
function initializeLocalAutoLogin() {
    const lineIdInput = document.getElementById('lineId');
    if (!lineIdInput) {
        return;
    }

    lineIdInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            checkExistingUser(this.value);
        }
    });

    lineIdInput.addEventListener('blur', function() {
        if (this.value.trim()) {
            checkExistingUser(this.value);
        }
    });

    console.log('ローカル環境：自動ログインチェック機能を有効化しました');
}

window.onload = async function() {
    const submitBtn = document.getElementById('submitBtn');

    console.log('=== window.onload fired ===');
    console.log('LIFF_ID:', window.LIFF_ID);
    var _lineIdEl = document.getElementById('lineId');
    console.log('lineId input value:', _lineIdEl ? _lineIdEl.value : '(none)');
    console.log('isLocal:', isLocalEnvironment());

    // 生年月日変更時のイベント
    const birthDateInput = document.getElementById('birthDate');
    if (birthDateInput) {
        birthDateInput.addEventListener('change', function() {
            updateGraduationYearOptions(this.value);
        });
    }

    const searchAddressBtn = document.getElementById('searchAddressBtn');
    if (searchAddressBtn) {
        searchAddressBtn.addEventListener('click', searchAddressByPostalCode);
    }
    const postalCodeEl = document.getElementById('postalCode');
    if (postalCodeEl) {
        postalCodeEl.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchAddressByPostalCode();
            }
        });
    }

    // フォーム送信前バリデーション
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const gradYearEl = document.getElementById('graduationYear');
            const graduationYear = gradYearEl ? gradYearEl.value : '';
            if (!graduationYear) {
                e.preventDefault();
                alert('卒業年度を選択してください');
                return;
            }
        });
    }

    if (isLocalEnvironment()) {
        setStatus('🏠 <b>ローカル環境</b> — LINE IDフィールドに手動入力してください', 'warning');
        initializeLocalAutoLogin();

        // ローカルではlineIdの入力値をリアルタイム表示
        const lineIdInput = document.getElementById('lineId');
        if (lineIdInput) {
            const showLocalId = () => {
                const val = lineIdInput.value.trim();
                if (val) {
                    setStatus('🏠 <b>ローカル環境</b> — LINE ID（手動）: <code>' + val + '</code>', 'success');
                } else {
                    setStatus('🏠 <b>ローカル環境</b> — LINE IDフィールドに手動入力してください', 'warning');
                }
            };
            lineIdInput.addEventListener('input', showLocalId);
            lineIdInput.addEventListener('change', showLocalId);
            showLocalId();
        }
        return;
    }

    const liffId = window.LIFF_ID;
    if (!liffId) {
        setStatus('❌ <b>LIFF ID未設定</b> — config services.line.liff_id を確認してください', 'error');
        return;
    }

    // LINE ID取得完了まで送信ボタンを無効化
    // ただし /auth/line から戻ってきた場合（hidden に値あり）はスキップ
    const lineIdEl2 = document.getElementById('lineId');
    const existingLineId = lineIdEl2 ? lineIdEl2.value : '';
    if (existingLineId) {
        setStatus('✅ <b>LINE ID確認済み</b>: <code>' + existingLineId + '</code>', 'success');
        return;
    }

    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.dataset.originalText = submitBtn.textContent;
        submitBtn.textContent = 'LINE ID取得中...';
    }
    setStatus('⏳ <b>LIFF初期化中...</b>', 'info');

    try {
        await liff.init({ liffId: liffId });

        setStatus('🔐 isLoggedIn=' + liff.isLoggedIn() + ' / isInClient=' + liff.isInClient(), 'info');

        if (!liff.isLoggedIn()) {
            // LINE アプリ外（PC ブラウザ等）からのアクセス
            setStatus('⚠️ <b>LINE アプリからアクセスしてください</b>', 'error');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'LINE アプリからアクセスしてください';
            }
            return;
        }

        setStatus('⏳ <b>プロフィール取得中...</b>', 'info');

        const profile = await liff.getProfile();
        document.getElementById('lineId').value = profile.userId;

        setStatus('✅ <b>LINE ID取得成功！</b><br>userId: <code>' + profile.userId + '</code><br>displayName: ' + profile.displayName, 'success');
        console.log('LINE User ID:', profile.userId);

        // 既存ユーザーチェック → サーバー側で振り分け
        window.location.href = '/auth/line?line_id=' + encodeURIComponent(profile.userId);

    } catch (err) {
        setStatus('❌ <b>エラー: ' + err.message + '</b>', 'error');
        console.error('LIFF初期化エラー:', err);
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = submitBtn.dataset.originalText || '登録する';
        }
    }
};

/**
 * 生年月日から卒業年度の選択肢を生成
 *
 * 【計算基準】
 * - 高校51回期 = 1999年3月卒業（1998年度）
 * - 高校1回期 = 1949年3月卒業（1948年度）
 * - 回期番号 = 卒業年度 - 1947
 *
 * @param {string} birthDate - 生年月日（YYYY-MM-DD形式）
 */
function updateGraduationYearOptions(birthDate) {
    const select = document.getElementById('graduationTerm');
    if (!birthDate || !select) {
        return;
    }

    select.innerHTML = '<option value="">選択してください</option>';

    const birth = new Date(birthDate);
    const birthYear = birth.getFullYear();
    const birthMonth = birth.getMonth() + 1; // 1-12
    const birthDay = birth.getDate();

    // 4月2日〜翌年4月1日生まれが同じ学年
    let baseGraduationYear;
    if (birthMonth > 4 || (birthMonth === 4 && birthDay >= 2)) {
        baseGraduationYear = birthYear + 18;
    } else {
        baseGraduationYear = birthYear + 17;
    }

    const years = [];
    for (let i = -1; i <= 1; i++) {
        years.push(baseGraduationYear + i);
    }
    years.sort((a, b) => a - b);

    years.forEach(year => {
        const option = document.createElement('option');
        const termNumber = year - 1947;
        const termValue = `高校${termNumber}回期`;
        const graduationMonth = `${year + 1}年3月`;

        option.value = termValue;

        if (year === baseGraduationYear) {
            option.textContent = `${graduationMonth}卒（${termNumber}回期）【現役想定】`;
            option.selected = true;
        } else if (year < baseGraduationYear) {
            option.textContent = `${graduationMonth}卒（${termNumber}回期）【早期卒業】`;
        } else {
            option.textContent = `${graduationMonth}卒（${termNumber}回期）【留年等】`;
        }

        select.appendChild(option);
    });

    console.log(`[卒業年度計算] 生年月日: ${birthDate} → 現役卒業: ${baseGraduationYear + 1}年3月（${baseGraduationYear - 1947}回期）`);
}

/**
 * 郵便番号から住所を検索（zipcloud API）
 */
function searchAddressByPostalCode() {
    const postalCodeInput = document.getElementById('postalCode');
    const addressInput = document.getElementById('address');
    const searchBtn = document.getElementById('searchAddressBtn');

    if (!postalCodeInput || !addressInput) {
        return;
    }

    const postalCode = postalCodeInput.value.replace(/[^0-9]/g, '');

    if (postalCode.length !== 7) {
        alert('郵便番号は7桁で入力してください（例：1234567）');
        return;
    }

    searchBtn.disabled = true;
    searchBtn.textContent = '検索中...';

    fetch(`https://zipcloud.ibsnet.co.jp/api/search?zipcode=${postalCode}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 200 && data.results) {
                const result = data.results[0];
                const address = result.address1 + result.address2 + result.address3;
                addressInput.value = address;
                addressInput.classList.add('border-success');
                setTimeout(() => {
                    addressInput.classList.remove('border-success');
                }, 2000);
                console.log('住所取得成功:', address);
            } else {
                alert('該当する住所が見つかりませんでした。郵便番号をご確認ください。');
            }
        })
        .catch(error => {
            console.error('住所検索エラー:', error);
            alert('住所の取得に失敗しました。もう一度お試しください。');
        })
        .finally(() => {
            searchBtn.disabled = false;
            searchBtn.textContent = '住所検索';
        });
}

