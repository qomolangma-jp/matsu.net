<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // LIFF経由の新規登録は誰でも可能
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'line_id' => 'required|string|max:255|unique:users,line_id',
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name_kana' => 'nullable|string|max:255',
            'first_name_kana' => 'nullable|string|max:255',
            'birth_date' => 'required|date|before:today',
            'graduation_term' => 'required|string|max:50', // 例: 高校51回期
            'email' => 'nullable|email|max:255',
            'postal_code' => 'nullable|string|max:10|regex:/^\d{3}-?\d{4}$/',
            'address' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'line_id' => 'LINE ID',
            'last_name' => '姓',
            'first_name' => '名',
            'last_name_kana' => '姓（カナ）',
            'first_name_kana' => '名（カナ）',
            'birth_date' => '生年月日',
            'graduation_term' => '卒業年度',
            'email' => 'メールアドレス',
            'postal_code' => '郵便番号',
            'address' => '住所',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'line_id.required' => 'LINE IDは必須です。',
            'line_id.unique' => 'この LINE ID は既に登録されています。',
            'last_name.required' => '姓は必須です。',
            'first_name.required' => '名は必須です。',
            'birth_date.required' => '生年月日は必須です。',
            'birth_date.before' => '生年月日は今日より前の日付を指定してください。',
            'graduation_term.required' => '卒業年度は必須です。',
            'email.email' => 'メールアドレスの形式が正しくありません。',
            'postal_code.regex' => '郵便番号は「123-4567」の形式で入力してください。',
        ];
    }

    /**
     * 検索用氏名を取得（全角・半角スペースを除去）
     * 
     * @return string
     */
    public function getSearchName(): string
    {
        $lastName = $this->input('last_name', '');
        $firstName = $this->input('first_name', '');
        $fullName = $lastName . $firstName;
        
        // 全角・半角スペースを除去
        return $this->removeSpaces($fullName);
    }

    /**
     * 検索用カナを取得（全角・半角スペースを除去）
     * 
     * @return string|null
     */
    public function getSearchKana(): ?string
    {
        $lastKana = $this->input('last_name_kana', '');
        $firstKana = $this->input('first_name_kana', '');
        
        if (empty($lastKana) && empty($firstKana)) {
            return null;
        }
        
        $fullKana = $lastKana . $firstKana;
        
        // 全角・半角スペースを除去
        return $this->removeSpaces($fullKana);
    }

    /**
     * 全角・半角スペースを除去
     * 
     * @param string $text
     * @return string
     */
    private function removeSpaces(string $text): string
    {
        // 全角スペース（U+3000）、半角スペース、タブ、改行を除去
        return preg_replace('/[\s　]+/u', '', $text);
    }

    /**
     * 卒業年度を数値に変換
     * 
     * 【計算基準】
     * - 高校51回期 = 1999年3月卒業（1998年度）
     * - 高校1回期 = 1949年3月卒業（1948年度）
     * - 卒業年度 = 回期番号 + 1947
     * 
     * @return int|null 卒業年度（例：51回期 → 1998）
     */
    public function getGraduationYear(): ?int
    {
        $term = $this->input('graduation_term', '');
        
        // "高校51回期" のような文字列から数値を抽出
        if (preg_match('/(\d+)回期/', $term, $matches)) {
            $termNumber = (int) $matches[1];
            
            // 卒業年度を計算
            // 例：51回期 → 1947 + 51 = 1998年度（1999年3月卒）
            return 1947 + $termNumber;
        }
        
        return null;
    }
}
