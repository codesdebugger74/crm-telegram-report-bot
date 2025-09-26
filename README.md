# Telegram Alert Bot

A PHP-based Telegram bot to generate regular report and send alerts. This bot supports sending reports to specific chats and allows command execution for managing alerts.

---

## File/Folder Structure

```
├── bot_cmd.php            # Bot commands handler
├── composer.json          # Composer dependencies file
├── config_sample.php      # Sample configuration file
├── cron.php               # Cron job for automated report generate
├── db_connect.php         # Database connection script
├── README.md              # Project documentation (this file)
├── setup.php              # Webhook setup script
├── .gitignore             # Ignore upload files to repo
└── url_check.php          # URL monitoring script
```

---

## Screenshots

| Step | Screenshot |
|------|------------|
| Setup Webhook | ![Webhook Setup](https://private-user-images.githubusercontent.com/65335426/494353371-8e135666-4909-4324-ad1b-205d2cdf1872.png?jwt=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJnaXRodWIuY29tIiwiYXVkIjoicmF3LmdpdGh1YnVzZXJjb250ZW50LmNvbSIsImtleSI6ImtleTUiLCJleHAiOjE3NTg4ODA1ODYsIm5iZiI6MTc1ODg4MDI4NiwicGF0aCI6Ii82NTMzNTQyNi80OTQzNTMzNzEtOGUxMzU2NjYtNDkwOS00MzI0LWFkMWItMjA1ZDJjZGYxODcyLnBuZz9YLUFtei1BbGdvcml0aG09QVdTNC1ITUFDLVNIQTI1NiZYLUFtei1DcmVkZW50aWFsPUFLSUFWQ09EWUxTQTUzUFFLNFpBJTJGMjAyNTA5MjYlMkZ1cy1lYXN0LTElMkZzMyUyRmF3czRfcmVxdWVzdCZYLUFtei1EYXRlPTIwMjUwOTI2VDA5NTEyNlomWC1BbXotRXhwaXJlcz0zMDAmWC1BbXotU2lnbmF0dXJlPTgwMTZkNmQ5YTk2MGE0MThlMmM4MzJjZjUyYzIzNTczZjg0ZmMxZjI0MzY3NTYzZmRhY2M3OTk5M2IwNjYyZWEmWC1BbXotU2lnbmVkSGVhZGVycz1ob3N0In0.H4BWBCnt3fcPBXOUUW0kJttlakWMZLn3jzHYlWO3dD8) |
| Chat ID | ![Chat ID](https://private-user-images.githubusercontent.com/65335426/494353593-b5c84036-b759-4e68-9c59-4d43d4c07c2c.png?jwt=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJnaXRodWIuY29tIiwiYXVkIjoicmF3LmdpdGh1YnVzZXJjb250ZW50LmNvbSIsImtleSI6ImtleTUiLCJleHAiOjE3NTg4ODA2MTYsIm5iZiI6MTc1ODg4MDMxNiwicGF0aCI6Ii82NTMzNTQyNi80OTQzNTM1OTMtYjVjODQwMzYtYjc1OS00ZTY4LTljNTktNGQ0M2Q0YzA3YzJjLnBuZz9YLUFtei1BbGdvcml0aG09QVdTNC1ITUFDLVNIQTI1NiZYLUFtei1DcmVkZW50aWFsPUFLSUFWQ09EWUxTQTUzUFFLNFpBJTJGMjAyNTA5MjYlMkZ1cy1lYXN0LTElMkZzMyUyRmF3czRfcmVxdWVzdCZYLUFtei1EYXRlPTIwMjUwOTI2VDA5NTE1NlomWC1BbXotRXhwaXJlcz0zMDAmWC1BbXotU2lnbmF0dXJlPWI4ZjE1NTdjMzY2MmZjYjIwMWIzZTJiNzJkZGQ1MDA2MTQwZDVlNmQxOGIxMzY1M2FhZDE3MTAwZTQ0YzlkMmYmWC1BbXotU2lnbmVkSGVhZGVycz1ob3N0In0.Rxtu9uwYRjEsmkzLKBXk7mDtZrYl3lYKQxcfUmzeKT8) |
| Bot Commands | ![Bot Commands](https://private-user-images.githubusercontent.com/65335426/494353763-aa173a58-6b02-41a0-9838-2e3d60ccc32a.png?jwt=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJnaXRodWIuY29tIiwiYXVkIjoicmF3LmdpdGh1YnVzZXJjb250ZW50LmNvbSIsImtleSI6ImtleTUiLCJleHAiOjE3NTg4ODA2NDEsIm5iZiI6MTc1ODg4MDM0MSwicGF0aCI6Ii82NTMzNTQyNi80OTQzNTM3NjMtYWExNzNhNTgtNmIwMi00MWEwLTk4MzgtMmUzZDYwY2NjMzJhLnBuZz9YLUFtei1BbGdvcml0aG09QVdTNC1ITUFDLVNIQTI1NiZYLUFtei1DcmVkZW50aWFsPUFLSUFWQ09EWUxTQTUzUFFLNFpBJTJGMjAyNTA5MjYlMkZ1cy1lYXN0LTElMkZzMyUyRmF3czRfcmVxdWVzdCZYLUFtei1EYXRlPTIwMjUwOTI2VDA5NTIyMVomWC1BbXotRXhwaXJlcz0zMDAmWC1BbXotU2lnbmF0dXJlPTRmYWFhYzU3MTJiYTRmNWVjODI2ZTQ0MTExZmM2NDkyZGY2ZTNmNjE4MTc0ZWFmMzI4ODJjNGVhZTMyZDA3YjImWC1BbXotU2lnbmVkSGVhZGVycz1ob3N0In0.yvmsdRNNANWNruhULiY43QkKuGNiiMDCMKZ2dSu8Q_8) |
| LTV Report | ![LTV Report](https://private-user-images.githubusercontent.com/65335426/494353909-0bb7a99c-08fb-4664-98f9-f480cb4437ca.png?jwt=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJnaXRodWIuY29tIiwiYXVkIjoicmF3LmdpdGh1YnVzZXJjb250ZW50LmNvbSIsImtleSI6ImtleTUiLCJleHAiOjE3NTg4ODA2NjMsIm5iZiI6MTc1ODg4MDM2MywicGF0aCI6Ii82NTMzNTQyNi80OTQzNTM5MDktMGJiN2E5OWMtMDhmYi00NjY0LTk4ZjktZjQ4MGNiNDQzN2NhLnBuZz9YLUFtei1BbGdvcml0aG09QVdTNC1ITUFDLVNIQTI1NiZYLUFtei1DcmVkZW50aWFsPUFLSUFWQ09EWUxTQTUzUFFLNFpBJTJGMjAyNTA5MjYlMkZ1cy1lYXN0LTElMkZzMyUyRmF3czRfcmVxdWVzdCZYLUFtei1EYXRlPTIwMjUwOTI2VDA5NTI0M1omWC1BbXotRXhwaXJlcz0zMDAmWC1BbXotU2lnbmF0dXJlPWZiMjIwN2FhZmIwMjhiOTFmOTJlNTk2YTUzNWNhNDQwOTVlNjc1Y2ZlZWJiNTZiMTQxMWQzMzg0ZjBkZDNhMDQmWC1BbXotU2lnbmVkSGVhZGVycz1ob3N0In0.BZ67cfCnK56vTs0IurLcnWXuOlquH9AJwY2neuZWeoc) |
| Sales Report | ![Sales Report](https://private-user-images.githubusercontent.com/65335426/494354094-7b02edfc-abf9-45bd-b989-8422541985b1.png?jwt=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJnaXRodWIuY29tIiwiYXVkIjoicmF3LmdpdGh1YnVzZXJjb250ZW50LmNvbSIsImtleSI6ImtleTUiLCJleHAiOjE3NTg4ODA2ODcsIm5iZiI6MTc1ODg4MDM4NywicGF0aCI6Ii82NTMzNTQyNi80OTQzNTQwOTQtN2IwMmVkZmMtYWJmOS00NWJkLWI5ODktODQyMjU0MTk4NWIxLnBuZz9YLUFtei1BbGdvcml0aG09QVdTNC1ITUFDLVNIQTI1NiZYLUFtei1DcmVkZW50aWFsPUFLSUFWQ09EWUxTQTUzUFFLNFpBJTJGMjAyNTA5MjYlMkZ1cy1lYXN0LTElMkZzMyUyRmF3czRfcmVxdWVzdCZYLUFtei1EYXRlPTIwMjUwOTI2VDA5NTMwN1omWC1BbXotRXhwaXJlcz0zMDAmWC1BbXotU2lnbmF0dXJlPTRjZDlkMzRiNmVmMDhiMzU2MWM1ZjJlZjBmYjAwZmY4NjM4OTg4ZGY0NTA0MzI4MTAxNzUxMDk5NDM5ZDc2OTMmWC1BbXotU2lnbmVkSGVhZGVycz1ob3N0In0.rv4f8bFW8OX_DpZFG3cW1qAS4SGCbhOnzKA2TUrIdpg) |

---

## Setup Instructions

### Step 1: Initial Setup

1. **Install Dependencies**  
   Run the following command in CLI:
   ```bash
   composer update
   ```
   This will download all required dependencies.  
   > **Note:** If Composer is not installed, contact your IT team.

2. **Rename Config File**  
   In your file manager, rename:
   ```text
   config_sample.php -> config.php
   ```

3. **Edit Config File**  
   Open `config.php` in a code editor and update the following:
   - **Server Details**
   - **Telegram `botToken`**
   - **Base URL**

   **Example:**  
   If your files are uploaded to `/home/cpaneluser/tg_alert/` and accessible via `https://exampledomain.com/tg_alert/`, then set:
   ```php
   $base_url = "https://exampledomain.com/tg_alert/";
   ```
   > Make sure to include the trailing `/`.

4. **Set Webhook**  
   Open in browser:
   ```
   https://exampledomain.com/tg_alert/setup.php
   ```
   If setup is successful, you should see:
   ```json
   {"ok":true,"result":true,"description":"Webhook was set"}
   ```

---

### Step 2: Bot Configuration

5. **Get Chat ID**  
   Open your Telegram bot chat (either personal or group) and type:
   ```
   /start
   ```
   You will receive a message like:
   ```
   Your chat id is: 1234567890
   ```

6. **Update Chat IDs in Config**  
   Add your chat IDs to `config.php`:
   ```php
   $report_chat_id = 1234567890;     // Chat to receive regular reports
   $allow_cmd_chat = [1234567890];     // Chat allowed to send commands
   ```

7. **Test Bot Commands**  
   Type `Hi` or `/help` in your bot chat to see available commands.

   📖 **Available Commands:**
   1. `/start` - Create new report
   2. `/getall` - Get all existing reports
   3. `/pause <report_id>` - Pause a specific report
   4. `/help` - Show this help message
   5. `/end` - End any running operation

---

## Notes

- Ensure PHP version is compatible with the Composer dependencies.  
- Cron jobs can be set up using `cron.php` for automated report generate.  
- All database interactions are handled via `db_connect.php`.  
- Commands can be executed from allowed chats as defined in `allow_cmd_chat`.
- Make sure we can delete README.md file from production.


