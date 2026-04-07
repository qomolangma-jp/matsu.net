/**
 * 松.net - 新規登録フォーム JavaScript
 */

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
    const status = document.getElementById('liff-status');
    const submitBtn = document.getElementById('submitBtn');

    // 生年月日変更時のイベント
    const birthDateInput = document.getElementById('birthDate');
    if (birthDateInput) {
        birthDateInput.addEventListener('change', function() {
            updateGraduationYearOptions(this.value);
        });
    }

    // 郵便番号検索ボタン
    document.getElementById('searchAddressBtn')?.addEventListener('click', searchAddressByPostalCode);
    document.getElementById('postalCode')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchAddressByPostalCode();
        }
    });

    // フォーム送信前バリデーション
    document.getElementById('registerForm')?.addEventListener('submit', function(e) {
        const graduationYear = document.getElementById('graduationYear')?.value;
        if (!graduationYear) {
            e.preventDefault();
            alert('卒業年度を選択してください');
            return;
        }
        // 本番環境：LINE IDが取得できていなければ送信を止める
        if (!isLocalEnvironment()) {
            const lineId = document.getElementById('lineId')?.value;
            if (!lineId) {
                e.preventDefault();
                alert('LINE IDを取得中です。しばらくお待ちください。');
                return;
            }
        }
    });

    if (isLocalEnvironment()) {
        if (status) status.innerText = 'ローカル環境で起動中...';
        initializeLocalAutoLogin();
        return;
    }

    const liffId = window.LIFF_ID;
    if (!liffId) {
        if (status) status.innerText = 'LIFF ID未設定';
        return;
    }

    // LINE ID取得完了まで送信ボタンを無効化
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.dataset.originalText = submitBtn.textContent;
        submitBtn.textContent = 'LINE ID取得中...';
    }
    if (status) status.innerText = 'LINEログイン情報を確認中...';

    try {
        await liff.init({ liffId: liffId });

        if (!liff.isLoggedIn()) {
            liff.login({ redirectUri: window.location.href });
            return;
        }

        if (status) status.innerText = 'LINE情報を取得中...';

        const profile = await liff.getProfile();
        document.getElementById('lineId').value = profile.userId;
        console.log('LINE User ID:', profile.userId);

        if (status) status.innerText = 'LINE ID: ' + profile.userId;

        // 送信ボタンを有効化
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = submitBtn.dataset.originalText || '登録する';
        }

        // ローカル環境と違い、本番では既存ユーザーチェックのリダイレクトは行わない
        // LINE ID は hidden input にセット済みのため、そのままフォーム入力へ進む

    } catch (err) {
        if (status) status.innerText = '初期化エラー: ' + err.message;
        console.error('LIFF初期化エラー:', err);
        // エラー時も送信ボタンを戻す（再試行できるよう）
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

