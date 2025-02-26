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
2. DockerDesktop アプリを起動
3. docker-compose up -d --build を実行
4. メール認証設定
- Dockerがインストールされている場合は、以下のコマンドでMailHogを起動できます。  
```docker
docker run -d -p 1025:1025 -p 8025:8025 mailhog/mailhog
```

### 2. Laravel の設定

1. docker-compose exec php bash コマンド実行
2. composer install にてパッケージのインストール
3. 「.env.example」ファイルを複製後 「.env」へ名前を変更  
※ 「.env.example」にて、以下データベース接続とメール認証設定済み

```text
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass
```
```text
MAIL_MAILER=smtp
MAIL_HOST=host.docker.internal
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=staff@example.com
MAIL_FROM_NAME="${APP_NAME}"
```
- メール認証用、mailhogアクセス先 : http://localhost:8025/

### テストアカウント
      name: 管理者(管理者用ログインに使用)  
      email: admin@example.com  
      password: password  
      -------------------------
      name: スタッフ(スタッフ用会員登録、ログイン、メール認証時に使用)  
      email: staff@example.com  
      password: password  
      -------------------------

5. アプリケーションキーの作成

```bash
php artisan key:generate
```

6. マイグレーション実行

```bash
php artisan migrate
```

7. ファクトリを使用し、  
    users テーブルにダミーデータを 10 件、  
    attendances テーブルにダミーデータを 10 件作成、  
    resets テーブルに 15 件のダミーデータを作成
8. シーダーファイルを使用し、  
   確認時に使用する管理者を、users テーブルへ個別で 1件作成、  
   - name : 管理者
   - email : admin@example.com
   - password : password
9. シーディングの実行

```bash
php artisan db:seed
```

### 3. ER 図の作成

![ER図](./src/attendance-test_ER.drawio.svg)
