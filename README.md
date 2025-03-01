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
※ 「.env.example」へ、以下データベース接続とメール認証設定済み

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
**メール認証用、mailhogアクセス先 : http://localhost:8025/**  
**※メールが届かない場合は、再送信を行って下さい。**

### テストアカウント
      name: 管理者(管理者用ログインに使用)  
      email: admin@example.com  
      password: password  
      -------------------------
      name: スタッフ(スタッフ用会員登録、ログイン、メール認証時に使用)  
      email: staff@example.com  
      password: password  
      -------------------------

```text
docker-compose exec php bash　にて以下を実行
```
4. マイグレーション実行
```bash
php artisan migrate
```

5. ファクトリを使用し、  
    users テーブルにダミーデータを 10 件、  
    （ダミー用のパスワードは全てpasswordを設定）  
    attendances テーブルにダミーデータを 20 件作成、  
    resets テーブルにダミーデータを作成

6. シーダーファイルを使用し、  
   確認時に使用する管理者を、users テーブルへ個別で 1件作成、  
   - name : 管理者
   - email : admin@example.com
   - password : password

7. シーディングの実行
```bash
php artisan db:seed
```

### 3. PHPUnitテスト
**※本番環境と同一のデータベースを使用してテスト用テーブルを作成する為、**  
　**テスト用マイグレーションを実行すると、本番環境のデータが全て消えてしまいます。**  
　**テストケース検証後に本番環境の再確認が必要な場合は、お手数ですが再度**  
```text
docker-compose exec php bash　にて以下を実行
```
- マイグレーション実行
```bash
php artisan migrate
```
- シーディングの実行
```bash
php artisan db:seed
```
**をお願いいたします。**

1. テスト用テーブル作成の為「.env.example」ファイルを複製後、  
   「.env.testing」へ名前を変更し以下を設定
``` text
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass
DB_PREFIX=test_　←　この部分を追加
```
```text
docker-compose exec php bash　にて以下を実行
```
2. テスト用マイグレーション実行
``` bash
php artisan migrate --env=testing
```
3. テスト実行
``` bash
php artisan test
```

### 4. ER 図の作成
![ER図](./src/attendance-test_ER.drawio.svg)
