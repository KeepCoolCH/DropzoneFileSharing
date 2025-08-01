# 📤 Dropzone File Sharing

**Simple and secure file sharing via drag & drop** – with temporary links, password protection, and expiration settings.  
Version **1.6** – developed by Kevin Tobler 🌐 [www.kevintobler.ch](https://www.kevintobler.ch)

---

## 🚀 Features

- 📂 Drag & drop upload for files or entire folders  
- 🔐 Optional password protection for each upload  
- ⏳ Set link expiration (1h, 3h, 6h, 12h, 1–30 days or keep forever)  
- 🔁 One-time or reusable download links  
- 📎 Automatically creates a ZIP archive for folder uploads  
- 🗣️ Multilingual (English, German, French & Italian)  
- ✨ No database required – pure PHP
- 🚫 No filesize limit using chunks
- ✅ Chunked Upload with Progress Bar

---

## 🔧 Installation

1. Upload all files to your web server
2. Make sure the `uploads/` directory is writable (is created automatically)
3. Open the application in your browser

> ⚠️ Requires PHP 7.4 or higher. No database needed.

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

- 'lang_default' => 'de': Default language (e.g. 'de', 'en', 'fr' or 'it')
- 'valid_xx' => true/false: Control link expiration options
- 'onlyupload' => true/false: Disable password protection and only allow upload without generating a link with the setting "true" (only admin can download files from the upload folder)

> ⚠️ Changes take effect automatically on the next page load.

---

## 🔒 Security

- Each upload can be protected with a custom password  
- Option to allow only a **single download** or multiple downloads  
- Files are automatically deleted after the expiration time

---

## 📁 Folder Uploads & ZIP

When uploading a folder, the tool detects it and automatically creates a ZIP file from its contents to simplify sharing.

---

## 📸 Screenshot

![Screenshot](https://online.kevintobler.ch/projectimages/DropzoneFileSharing1_5.png)

---

## 🧑‍💻 Developer

**Kevin Tobler**  
🌐 [www.kevintobler.ch](https://www.kevintobler.ch)

---

## 📜 License

This project is licensed under the **MIT License** – feel free to use, modify, and distribute.
