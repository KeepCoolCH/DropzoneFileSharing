# 📤 Dropzone File Sharing

**Simple and secure file sharing via drag & drop** – with temporary links or via email, password protection, and expiration settings.  
Version **2.6** – developed by Kevin Tobler 🌐 [www.kevintobler.ch](https://www.kevintobler.ch)

---

## 🔄 Changelog

### 🆕 Version 2.x
- **2.6**
  - 📊 Download tracking added (download counter + last download timestamp)
  - 👁️ Display of “Downloads” and “Last Download” directly in the **Admin Panel**
- **2.5**
  - ⚙️ Improved **Admin Panel** with configuration and email settings
  - 🧭 Clearer navigation and visual refinements in the **Admin Panel**
  - 📱 Fully responsive redesign of the **Admin Panel** for mobile devices
  - 🔍 Integrated search function for uploads
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
- ⚙️ Integrated Admin Panel with configuration and email settings
- 🔍 Search uploads with filename, filesize, date or email

---

## 📸 Screenshot

![Screenshot](https://online.kevintobler.ch/projectimages/DropzoneFileSharingV2-6.png)
![Screenshot](https://online.kevintobler.ch/projectimages/DropzoneFileSharingV2-6_AdminPanel2.png)

---

## 🌍 Online Demo

Try Dropzone File Sharing directly in your browser:  
🔗 [https://share.kevintobler.ch](https://share.kevintobler.ch)

---

## 🔧 Installation

1. Upload all files to your web server
2. Open the application in your browser
3. Access `/admin.php` to create your admin credentials
4. Choose your desired configuration values in the **Admin Panel**
5. When `send_email` is set to active, make shure to define the `SMTP server`, `SMTP port`, `SMTP username`, and `SMTP password` in the **Admin Panel**

> ⚠️ Requires PHP 7.4 or higher. No database needed.

---

## 🧭 Admin Panel

The **Admin Panel** provides a secure management interface for your **Dropzone File Sharing** installation.

### 🔐 Login & Setup Admin Panel
- First-time access via `/admin.php` triggers **Admin Setup** (username + password creation)
- Credentials are stored securely (hashed) in `.admin.json` and secured with `.htaccess`
- After setup, login via the **Admin Login** form in `/admin.php`
- Setup your desired configuration values and when `send_email` is set to active, make shure to define the `SMTP server`, `SMTP port`, `SMTP username`, and `SMTP password`

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

You can configure the following options in the **Admin Panel**:

- Choose default language (e.g. 'de', 'en', 'fr' or 'it')
- Set the timezone according to your preference
- Control link expiration options
- Enable/Disable `only_upload` mode without generating a link
- Enable/Disable `send_email` mode (⚠️ make sure to define the `SMTP server`, `SMTP port`, `SMTP username`, and `SMTP password`).
- Enable/Disable `pwzip` mode for password protection of the zip file itself. If deactivated, only the download is password-protected, not the ZIP file (⚠️ ZIP password cannot be modified).

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
