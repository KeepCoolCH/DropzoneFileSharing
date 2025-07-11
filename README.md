# 📤 Dropzone File Sharing

**Simple and secure file sharing via drag & drop** – with temporary links, password protection, and expiration settings.  
Version **1.2** – developed by Kevin Tobler 🌐 [www.kevintobler.ch](https://www.kevintobler.ch)

---

## 🚀 Features

- 📂 Drag & drop upload for files or entire folders  
- 🔐 Optional password protection for each upload  
- ⏳ Set link expiration (1h, 3h, 1–7 days)  
- 🔁 One-time or reusable download links  
- 📎 Automatically creates a ZIP archive for folder uploads  
- 🗣️ Multilingual (English & German)  
- ✨ No database required – pure PHP

---

## 🔧 Installation

1. Upload the `index.php` file to your web server
2. Make sure the `uploads/` directory is writable (is created automatically)
3. Open the application in your browser

> ⚠️ Requires PHP 7.4 or higher. No database needed.

---

## 🌍 Language Support

Default language is German. Use `?lang=en` to switch to English or click on the flag:

```
https://example.com/?lang=en
```

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

![Screenshot](https://online.kevintobler.ch/projectimages/DropzoneFileSharing.png)

---

## 🧑‍💻 Developer

**Kevin Tobler**  
🌐 [www.kevintobler.ch](https://www.kevintobler.ch)

---

## 📜 License

This project is licensed under the **MIT License** – feel free to use, modify, and distribute.
