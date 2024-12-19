# 勤怠管理アプリ

## 実行環境
- Laravel Framework : 8.83.8
- MySQL Database : 8.0.26
- Nginx Server : 1.21.1
- PHP : 7.4.9-fpm
- MySQL 管理ツール : phpMyadmin


## URL
- 開発環境：http://localhost/
- phpMyAdmin:：http://localhost:8080/

## 環境構築

### 1. Docker ビルド
1. [git clone リンク](https://github.com/coachtech-material/laravel-docker-template)
2. DockerDesktopアプリ起動
3. docker-compose up -d --build を実行

### 2. Laravel の設定
1. docker-compose exec php bash コマンド実行
2. composer install にてパッケージのインストール
3. 「.env.example」ファイルを複製後 「.env」へ名前を変更
4. データベース接続の為.env へ以下を設定
``` text
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass
```
5. アプリケーションキーの作成
``` bash
php artisan key:generate
```
6. マイグレーション実行
``` bash
php artisan migrate
```
7. ファクトリを使用し、users テーブルにダミーデータを 10 件作成
8. シーダーファイルを使用し、  
   attendances テーブルに 10 件、  
   breaks テーブルに 10 件のダミーデータを作成
9. シーディングの実行
``` bash
php artisan db:seed
```

### 3. HTML・CSS にて各ページの作成
- 会員登録画面【一般ユーザー】(/register)
- ログイン画面【一般ユーザー】(/login)
- 勤怠登録画面【一般ユーザー】(/attendance)
- 勤怠一覧画面【一般ユーザー】(/attendance/list)
- 勤怠詳細画面【一般ユーザー】(/attendance/{id})
- 申請一覧画面【一般ユーザー】(/stamp_correction_request/list)
- ログイン画面【管理者】(/admin/login)
- 勤怠一覧画面【管理者】(/admin/attendance/list)
- 勤怠詳細画面【管理者】(/attendance/{id})
- スタッフ一覧画面【管理者】(/admin/staff/list)
- スタッフ別勤怠一覧画面【管理者】(/admin/attendance/staff/{id})
- 申請一覧画面【管理者】(/stamp_correction_request/list)
- 修正申請承認画面【管理者】  
    (/stamp_correction_request/approve/{attendance_correct_request})

### 4. ER 図の作成
![ER図](./src/attendance-test_ER.drawio.svg)