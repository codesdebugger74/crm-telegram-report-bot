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
| Setup Webhook | ![Webhook Setup](https://private-user-images.githubusercontent.com/65335426/494345084-35325357-14ee-442d-9ee5-c0500a9208de.png?jwt=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJnaXRodWIuY29tIiwiYXVkIjoicmF3LmdpdGh1YnVzZXJjb250ZW50LmNvbSIsImtleSI6ImtleTUiLCJleHAiOjE3NTg4Nzk3MzMsIm5iZiI6MTc1ODg3OTQzMywicGF0aCI6Ii82NTMzNTQyNi80OTQzNDUwODQtMzUzMjUzNTctMTRlZS00NDJkLTllZTUtYzA1MDBhOTIwOGRlLnBuZz9YLUFtei1BbGdvcml0aG09QVdTNC1ITUFDLVNIQTI1NiZYLUFtei1DcmVkZW50aWFsPUFLSUFWQ09EWUxTQTUzUFFLNFpBJTJGMjAyNTA5MjYlMkZ1cy1lYXN0LTElMkZzMyUyRmF3czRfcmVxdWVzdCZYLUFtei1EYXRlPTIwMjUwOTI2VDA5MzcxM1omWC1BbXotRXhwaXJlcz0zMDAmWC1BbXotU2lnbmF0dXJlPTY2ODVlMjkzZTM5MTU5NjAyZWVhNDA5YjRhNDYxZjU5NzU3MWFiZjA4YWExMzg0NTU4ZGE0MjQ2ZWVhOWY4OGEmWC1BbXotU2lnbmVkSGVhZGVycz1ob3N0In0.vWqxQS6SMvlbuxfpymwQ9KsWVp9DU8dYj8xPLcZoDdY) |
| Chat ID | ![Chat ID](https://private-user-images.githubusercontent.com/65335426/494344041-287c8f88-54fc-4e10-b2c4-49a70071dea7.png?jwt=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJnaXRodWIuY29tIiwiYXVkIjoicmF3LmdpdGh1YnVzZXJjb250ZW50LmNvbSIsImtleSI6ImtleTUiLCJleHAiOjE3NTg4Nzk3NTYsIm5iZiI6MTc1ODg3OTQ1NiwicGF0aCI6Ii82NTMzNTQyNi80OTQzNDQwNDEtMjg3YzhmODgtNTRmYy00ZTEwLWIyYzQtNDlhNzAwNzFkZWE3LnBuZz9YLUFtei1BbGdvcml0aG09QVdTNC1ITUFDLVNIQTI1NiZYLUFtei1DcmVkZW50aWFsPUFLSUFWQ09EWUxTQTUzUFFLNFpBJTJGMjAyNTA5MjYlMkZ1cy1lYXN0LTElMkZzMyUyRmF3czRfcmVxdWVzdCZYLUFtei1EYXRlPTIwMjUwOTI2VDA5MzczNlomWC1BbXotRXhwaXJlcz0zMDAmWC1BbXotU2lnbmF0dXJlPWY2OTMyODA5MTc5NmQ3OTY1NzcxZTJmZDg4MDM4YzkwMzFmMjEwYjBiMDc4MGU4YmI1OGEwOTJmNTlhN2YyMzUmWC1BbXotU2lnbmVkSGVhZGVycz1ob3N0In0.J38kqESewNUhv84nelMbvKioqvDwqCKaNY1Yw6aY_yc) |
| Bot Commands | ![Bot Commands](https://private-user-images.githubusercontent.com/65335426/494345343-ce98c1c0-3f4e-4f07-8529-0f5fe6e885b7.png?jwt=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJnaXRodWIuY29tIiwiYXVkIjoicmF3LmdpdGh1YnVzZXJjb250ZW50LmNvbSIsImtleSI6ImtleTUiLCJleHAiOjE3NTg4Nzk3NzEsIm5iZiI6MTc1ODg3OTQ3MSwicGF0aCI6Ii82NTMzNTQyNi80OTQzNDUzNDMtY2U5OGMxYzAtM2Y0ZS00ZjA3LTg1MjktMGY1ZmU2ZTg4NWI3LnBuZz9YLUFtei1BbGdvcml0aG09QVdTNC1ITUFDLVNIQTI1NiZYLUFtei1DcmVkZW50aWFsPUFLSUFWQ09EWUxTQTUzUFFLNFpBJTJGMjAyNTA5MjYlMkZ1cy1lYXN0LTElMkZzMyUyRmF3czRfcmVxdWVzdCZYLUFtei1EYXRlPTIwMjUwOTI2VDA5Mzc1MVomWC1BbXotRXhwaXJlcz0zMDAmWC1BbXotU2lnbmF0dXJlPTU5Y2YzMTAxODA1NGE0YTNkN2Y3ZGVjYTc5OGFmNTk3YjU2MGVlMjE5YzkwNTAwYjc3N2FjY2UyMDg1ZDAyYTMmWC1BbXotU2lnbmVkSGVhZGVycz1ob3N0In0.fv7FCqMbValtqOv4UOKkse4piwIDoIt3k-wc1sYa76s) |
| LTV Report | ![LTV Report](https://private-user-images.githubusercontent.com/65335426/494345547-6cdd95fe-3600-4ab7-ad1e-0506765f4282.png?jwt=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJnaXRodWIuY29tIiwiYXVkIjoicmF3LmdpdGh1YnVzZXJjb250ZW50LmNvbSIsImtleSI6ImtleTUiLCJleHAiOjE3NTg4Nzk3ODgsIm5iZiI6MTc1ODg3OTQ4OCwicGF0aCI6Ii82NTMzNTQyNi80OTQzNDU1NDctNmNkZDk1ZmUtMzYwMC00YWI3LWFkMWUtMDUwNjc2NWY0MjgyLnBuZz9YLUFtei1BbGdvcml0aG09QVdTNC1ITUFDLVNIQTI1NiZYLUFtei1DcmVkZW50aWFsPUFLSUFWQ09EWUxTQTUzUFFLNFpBJTJGMjAyNTA5MjYlMkZ1cy1lYXN0LTElMkZzMyUyRmF3czRfcmVxdWVzdCZYLUFtei1EYXRlPTIwMjUwOTI2VDA5MzgwOFomWC1BbXotRXhwaXJlcz0zMDAmWC1BbXotU2lnbmF0dXJlPWYwZjI3YWExYmMwNjc1YWQ1YzgxNmMyNTkyMmMyNmFjNWQzZTE4Yjc3NmY1MmI5NjI3ODA5MGM4NDJiMDAxOTQmWC1BbXotU2lnbmVkSGVhZGVycz1ob3N0In0.ELtSyPOzT-TO50vvDSTfIJMmc2LkmtS-AY3ttXAGOUQ) |
| Sales Report | ![Sales Report](https://private-user-images.githubusercontent.com/65335426/494345799-31bba26f-21a2-4aa1-955d-b98d3ed75a17.png?jwt=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJnaXRodWIuY29tIiwiYXVkIjoicmF3LmdpdGh1YnVzZXJjb250ZW50LmNvbSIsImtleSI6ImtleTUiLCJleHAiOjE3NTg4Nzk4MDMsIm5iZiI6MTc1ODg3OTUwMywicGF0aCI6Ii82NTMzNTQyNi80OTQzNDU3OTktMzFiYmEyNmYtMjFhMi00YWExLTk1NWQtYjk4ZDNlZDc1YTE3LnBuZz9YLUFtei1BbGdvcml0aG09QVdTNC1ITUFDLVNIQTI1NiZYLUFtei1DcmVkZW50aWFsPUFLSUFWQ09EWUxTQTUzUFFLNFpBJTJGMjAyNTA5MjYlMkZ1cy1lYXN0LTElMkZzMyUyRmF3czRfcmVxdWVzdCZYLUFtei1EYXRlPTIwMjUwOTI2VDA5MzgyM1omWC1BbXotRXhwaXJlcz0zMDAmWC1BbXotU2lnbmF0dXJlPWI4OWQ2Yjc4NjZmY2NjNjEzOGExNjcwZGE4YTFlOTg2MjdhNTI2YzIzY2QzYWVhM2NhMzQ5ZjMwYWMzNzY5NGQmWC1BbXotU2lnbmVkSGVhZGVycz1ob3N0In0.vsb_p_1s1ZioubvR9tHKn8yxn8Qu0oPJifPmzRuGJn4) |

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
   $allow_cmd_chat = 1234567890;     // Chat allowed to send commands
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


