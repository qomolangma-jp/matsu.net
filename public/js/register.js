/**
 * 松.net - 新規登録フォーム JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // LIFF初期化
    initializeLiff();
    
    // ローカル環境：LINE ID自動ログインチェック
    initializeLocalAutoLogin();
    
    // 生年月日変更時のイベント
    const birthDateInput = document.getElementById('birthDate');
    if (birthDateInput) {
        birthDateInput.addEventListener('change', function() {
            updateGraduationYearOptions(this.value);
        });
    }
    
    // 郵便番号検索ボタンのイベント
    const searchAddressBtn = document.getElementById('searchAddressBtn');
    if (searchAddressBtn) {
        searchAddressBtn.addEventListener('click', function() {
            searchAddressByPostalCode();
        });
    }
    
    // 郵便番号入力時のEnterキーイベント
    const postalCodeInput = document.getElementById('postalCode');
    if (postalCodeInput) {
        postalCodeInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchAddressByPostalCode();
            }
        });
    }
});

/**
 * ローカル環境：LINE ID自動ログインチェック
 */
function initializeLocalAutoLogin() {
    const isLocal = window.location.hostname === 'matsu.localhost' || 
                    window.location.hostname === 'localhost' || 
                    window.location.hostname === '127.0.0.1';
    
    if (!isLocal) {
        return; // ローカル環境のみ
    }
    
    const lineIdInput = document.getElementById('lineId');
    if (!lineIdInput) {
        return;
    }
    
    // LINE IDフィールドのEnterキー押下時
    lineIdInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            checkExistingUser(this.value);
        }
    });
    
    // LINE IDフィールドのフォーカスアウト時
    lineIdInput.addEventListener('blur', function() {
        if (this.value.trim()) {
            checkExistingUser(this.value);
        }
    });
    
    console.log('ローカル環境：自動ログインチェック機能を有効化しました');
}

/**
 * 既存ユーザーチェック（ページリロード）
 */
function checkExistingUser(lineId) {
    if (!lineId || lineId.trim() === '') {
        return;
    }
    
    // 現在のURLパラメータを確認
    const urlParams = new URLSearchParams(window.location.search);
    const currentLineId = urlParams.get('line_id');
    
    // 既に同じLINE IDでチェック済みの場合はスキップ
    if (currentLineId === lineId) {
        return;
    }
    
    // line_idパラメータを付けてページをリロード（自動ログインチェックをトリガー）
    const newUrl = `${window.location.pathname}?line_id=${encodeURIComponent(lineId)}`;
    console.log('既存ユーザーチェック:', lineId);
    window.location.href = newUrl;
}

/**
 * LIFF初期化
 */
function initializeLiff() {
    // ローカル環境の場合はスキップ
    const isLocal = window.location.hostname === 'matsu.localhost' || 
                    window.location.hostname === 'localhost' || 
                    window.location.hostname === '127.0.0.1';
    
    if (isLocal) {
        console.log('ローカル環境のため、LIFF初期化をスキップします');
        return;
    }
    
    const liffId = '{{ env("LIFF_ID") }}'; // .envからLIFF IDを取得
    
    if (!liffId) {
        console.warn('LIFF IDが設定されていません');
        return;
    }
    
    liff.init({
        liffId: liffId
    }).then(() => {
        if (liff.isLoggedIn()) {
            // ログイン済みの場合、プロフィール情報を取得
            liff.getProfile().then(profile => {
                console.log('LINE User ID:', profile.userId);
                document.getElementById('lineId').value = profile.userId;
            }).catch((err) => {
                console.error('プロフィール取得エラー:', err);
            });
        } else {
            // 未ログインの場合、ログイン画面へ
            liff.login();
        }
    }).catch((err) => {
        console.error('LIFF初期化エラー:', err);
    });
}

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
    
    // 選択肢をクリア
    select.innerHTML = '<option value="">選択してください</option>';
    
    const birth = new Date(birthDate);
    const birthYear = birth.getFullYear();
    const birthMonth = birth.getMonth() + 1; // 1-12
    const birthDay = birth.getDate();
    
    // 18歳になる年度を計算（高校現役卒業を基準）
    // 4月2日〜翌年4月1日生まれが同じ学年
    let baseGraduationYear;
    if (birthMonth > 4 || (birthMonth === 4 && birthDay >= 2)) {
        // 4月2日以降生まれ → 生まれた年 + 18
        baseGraduationYear = birthYear + 18;
    } else {
        // 1月1日〜4月1日生まれ（早生まれ） → 生まれた年 + 17
        baseGraduationYear = birthYear + 17;
    }
    
    // 前後1年分の選択肢を生成（計3年分：留年・浪人を考慮）
    const years = [];
    for (let i = -1; i <= 1; i++) {
        years.push(baseGraduationYear + i);
    }
    
    // 昇順でソート
    years.sort((a, b) => a - b);
    
    // オプションを追加
    years.forEach(year => {
        const option = document.createElement('option');
        
        // 卒業年度から回期を計算
        // 例：1998年度 → 51回期（1998 - 1947 = 51）
        const termNumber = year - 1947;
        const termValue = `高校${termNumber}回期`;
        const graduationMonth = `${year + 1}年3月`; // 卒業は翌年の3月
        
        option.value = termValue; // 「高校51回期」形式で保存
        
        // 基準年度（現役）にマーク
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
 * 郵便番号から住所を検索
 * zipcloud APIを使用
 */
function searchAddressByPostalCode() {
    const postalCodeInput = document.getElementById('postalCode');
    const addressInput = document.getElementById('address');
    const searchBtn = document.getElementById('searchAddressBtn');
    
    if (!postalCodeInput || !addressInput) {
        return;
    }
    
    const postalCode = postalCodeInput.value.replace(/[^0-9]/g, ''); // 数字のみ抽出
    
    if (postalCode.length !== 7) {
        alert('郵便番号は7桁で入力してください（例：1234567）');
        return;
    }
    
    // ボタンを無効化
    searchBtn.disabled = true;
    searchBtn.textContent = '検索中...';
    
    // zipcloud APIで住所検索
    fetch(`https://zipcloud.ibsnet.co.jp/api/search?zipcode=${postalCode}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 200 && data.results) {
                const result = data.results[0];
                const address = result.address1 + result.address2 + result.address3;
                
                addressInput.value = address;
                
                // 住所入力欄をハイライト
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
            // ボタンを有効化
            searchBtn.disabled = false;
            searchBtn.textContent = '住所検索';
        });
}

/**
 * フォーム送信前のバリデーション
 */
document.getElementById('registerForm')?.addEventListener('submit', function(e) {
    const graduationYear = document.getElementById('graduationYear').value;
    
    if (!graduationYear) {
        e.preventDefault();
        alert('卒業年度を選択してください');
        return false;
    }
    
    return true;
});
