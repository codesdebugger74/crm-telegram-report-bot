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
├── url_check.php          # URL monitoring script
└── gitignore.php          # Ignore upload files to repo
```

---

## Screenshots

| Step | Screenshot |
|------|------------|
| Setup Webhook | ![Webhook Setup](https://i.postimg.cc/KjgFMzcr/setup-webhook.png) |
| Chat ID | ![Chat ID](https://i.postimg.cc/3wSdjkhB/chat-id.png) |
| Bot Commands | ![Bot Commands](https://i.postimg.cc/15q5gW4H/bot-commands.png) |
| LTV Report | ![LTV Report](https://i.postimg.cc/KzCjVCR5/ltv-report.png) |
| Sales Report | ![Sales Report](https://i.postimg.cc/7Y8bN8vY/sales-report.png) |

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


