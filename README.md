# 📤 Dropzone File Sharing

**Simple and secure file sharing via drag & drop** – with temporary links or via email, password protection, and expiration settings.  
Version **2.4** – developed by Kevin Tobler 🌐 [www.kevintobler.ch](https://www.kevintobler.ch)

---

## 🔄 Changelog

### 🆕 Version 2.x
- **2.4**
  - ⚙️ Added **Admin Panel** with password-protected setup
  - 📎 Manage Uploads (change expiration time, change password)
  - 📥 Download Uploads directly from the **Admin Panel**
  - 🧹 Delete Uploads directly from the **Admin Panel**
- **2.3**
  - 🔒 Security improvements  
  - 🗑️ When the user manually cancels the upload, reloads the page, or closes the browser, temporary files are cleaned up and the entry is removed from the JSON file
- **2.2**
  - 🔒 Security improvements  
  - 💾 Check for sufficient disk space before upload (error message if too little free space)  
- **2.1**
  - 📧 The time period for which the file is valid is included in the email to the recipient
- **2.0**
  - 📘 Completely reworked chunk upload  
  - ⚠️ No more errors when uploading very large files  
  - 🐞 Other bug fixes  

### ✨ Version 1.x
- **1.9**
  - 📘 New logo, colors and file list  
  - 📱 Responsive design for phones  
- **1.8**
  - 📧 Option to send files via email or just copy the download link  
- **1.7**
  - 📧 Share a unique download link directly to the recipient’s email inbox (multiple recipients supported)  
  
---

## 🚀 Features

- 📂 Drag & drop upload for files or entire folders  
- 🔐 Optional password protection for each upload  
- ⏳ Set link expiration (1h, 3h, 6h, 12h, 1–30 days or keep forever)  
- 🔁 One-time or reusable download links  
- 📎 Automatically creates a ZIP archive for folder uploads  
- 🗣️ Multilingual (German, English, French & Italian)  
- ✨ No database required – pure PHP
- 🚫 No filesize limit using chunks
- ✅ Upload with Progress Bar
- ⚙️ Integrated Admin Panel

---

## 📸 Screenshot

![Screenshot](https://online.kevintobler.ch/projectimages/DropzoneFileSharingV2-4.png)
![Screenshot](https://online.kevintobler.ch/projectimages/DropzoneFileSharingV2-4_AdminPanel.png)

---

## 🌍 Online Demo

Try Dropzone File Sharing directly in your browser:  
🔗 [https://share.kevintobler.ch](https://share.kevintobler.ch)

---

## 🔧 Installation

1. Upload all files to your web server
2. If `'send_email' => true` is set in the `config.php`, then the `.env` file must define the `SMTP server`, `SMTP port`, `username`, and `password`.
3. Open the application in your browser
4. Access `/admin.php` to create your admin credentials

> ⚠️ Requires PHP 7.4 or higher. No database needed.

---

## 🧭 Admin Panel

The **Admin Panel** provides a secure management interface for your **Dropzone File Sharing** installation.

### 🔐 Login & Setup Admin Panel
- First-time access via `/admin.php` triggers **Admin Setup** (username + password creation)
- Credentials are stored securely (hashed) in `.admin.json` and secured with `.htaccess`
- After setup, login via the **Admin Login** form in `/admin.php`

---

## 🌍 Language Support

Default language is German `?lang=de`. Use `?lang=en` to switch to English, `?lang=fr` to switch to French, `?lang=it` to switch to Italian or click on the flag:

```
https://example.com/index.php?lang=de
https://example.com/index.php?lang=en
https://example.com/index.php?lang=fr
https://example.com/index.php?lang=it
```

---

## ⚙️ Configuration

You can configure the following options:

- `'lang_default' => 'de'`: Default language (e.g. 'de', 'en', 'fr' or 'it')
- `'valid_xx' => true/false`: Control link expiration options
- `'onlyupload' => true/false`: Disable password protection and only allow upload without generating a link with the setting "true" (only admin can download files from the upload folder)
- `'send_email' => true/false`: Enable/Disable email sending (⚠️ make sure to update the `.env` file when enabled).
- `'pwzip' => true/false`: Enable/Disable password protection for the zip file itself. If false, only the download is password-protected, not the ZIP file.
- `'timezone' => 'Europe/Zurich'`: Change timezone to your preference (e.g. `America/New_York`, `Asia/Tokyo`, `UTC`, `Etc/GMT+1`)

> 💡 Full list of valid timezones: [https://www.php.net/manual/en/timezones.php](https://www.php.net/manual/en/timezones.php)

> ⚠️ Changes take effect automatically on the next page load.

---

## 🔒 Security

- Each upload can be protected with a custom password  
- Option to allow only a **single download** or multiple downloads  
- Files are automatically deleted after the expiration time
- Passwords are **never stored in plain text**

---

## 📁 Folder Uploads & ZIP

When uploading a folder, the tool detects it and automatically creates a ZIP file from its contents to simplify sharing.

---

## 🧑‍💻 Developer

**Kevin Tobler**  
🌐 [www.kevintobler.ch](https://www.kevintobler.ch)

---

## 📜 License

This project is licensed under the **MIT License** – feel free to use, modify, and distribute.
