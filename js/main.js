let files = [];
let directories = [];

function copyLink() {
    const link = document.getElementById("link").href;
    navigator.clipboard.writeText(link).then(() => {
        const copied = document.getElementById("copied");
        copied.style.display = "inline";
        setTimeout(() => copied.style.display = "none", 2000);
    });
}

const form = document.getElementById('uploadForm');

const UPLOAD_ENDPOINT = 'upload.php';
const csrfInput = form ? form.querySelector('[name="csrf_token"]') : null;
const csrfToken = csrfInput ? csrfInput.value : '';
const CHUNK_SIZE = 1024 * 1024 * 5; // 5 MB
const MAX_RETRIES = 5;
const RETRY_BASE_DELAY = 1000; // ms

async function statusRequest(fileName, totalSize, extra = {}) {
    const fd = new FormData();
    fd.append("action", "status");
    fd.append("relativePath", fileName);
    fd.append("totalSize", totalSize);

    for (const [k,v] of Object.entries(extra)) {
        if (v !== undefined && v !== null) fd.append(k, v);
    }

    const r = await fetch(UPLOAD_ENDPOINT, { method: "POST", body: fd });
    const t = await r.text();
    if (!r.ok) {
        throw new Error(`Servererror ${r.status}: ${t}`);
    }
    if (t.startsWith("STATUS ")) return parseInt(t.split(" ")[1], 10) || 0;
    if (t.startsWith("OK "))     return 0;
    throw new Error("ERROR " + t);
}

function appendChunk(slice, relPath, totalSize, offset, extra = {}, attempt = 0) {
    return new Promise((resolve, reject) => {
        const fd = new FormData();
        fd.append("action", "append");
        fd.append("chunk", slice);
        fd.append("totalSize", totalSize);
        fd.append("offset", offset);

        for (const [k,v] of Object.entries(extra)) {
            if (v !== undefined && v !== null) fd.append(k, v);
        }

        const xhr = new XMLHttpRequest();
        xhr.open("POST", UPLOAD_ENDPOINT);
        xhr.onload = () => {
            const resp = xhr.responseText || "";
            if (xhr.status !== 200) {
                if (attempt + 1 <= MAX_RETRIES) {
                    const delay = RETRY_BASE_DELAY * Math.pow(2, attempt);
                    return setTimeout(() => {
                        appendChunk(slice, relPath, totalSize, offset, extra, attempt + 1).then(resolve, reject);
                    }, delay);
                }
                return reject(new Error(`Servererror ${xhr.status}: ${resp}`));
            }
            if (resp.startsWith("ERR")) {
                if (attempt + 1 <= MAX_RETRIES) {
                    const delay = RETRY_BASE_DELAY * Math.pow(2, attempt);
                    return setTimeout(() => {
                        appendChunk(slice, relPath, totalSize, offset, extra, attempt + 1).then(resolve, reject);
                    }, delay);
                }
                return reject(new Error(resp));
            }
            resolve(resp);
        };
        xhr.onerror = () => {
            if (attempt + 1 <= MAX_RETRIES) {
                const delay = RETRY_BASE_DELAY * Math.pow(2, attempt);
                return setTimeout(() => {
                    appendChunk(slice, relPath, totalSize, offset, extra, attempt + 1).then(resolve, reject);
                }, delay);
            }
            reject(new Error("Networkerror"));
        };
        xhr.send(fd);
    });
}

async function finalizeRequest(fileName, totalSize, extra = {}) {
    const fd = new FormData();
    fd.append("action", "finalize");
    fd.append("relativePath", fileName);
    fd.append("totalSize", totalSize);

    for (const [k,v] of Object.entries(extra)) {
        if (v !== undefined && v !== null) fd.append(k, v);
    }

    const r = await fetch(UPLOAD_ENDPOINT, { method: "POST", body: fd });
    const t = await r.text();

    if (!r.ok) {
        throw new Error(`Servererror ${r.status}: ${t}`);
    }
    if (t.includes("ERR ")) {
        throw new Error(t);
    }

    return t;
}

async function uploadFileResumable(file, relPath, startAt, updateBytesCb, extraFields) {
  let offset = startAt;
  const total = file.size;

  if (total === 0) {
    return finalizeRequest(relPath, 0, extraFields);
  }

  while (offset < total) {
    const slice = file.slice(offset, offset + CHUNK_SIZE);
    const resp = await appendChunk(slice, relPath, total, offset, extraFields);
    if (resp.includes("COMPLETE")) {
      updateBytesCb(total - startAt);
      return resp;
    }
    if (resp.startsWith("OK ")) {
      const rec = parseInt(resp.split(" ")[1], 10) || (offset + slice.size);
      const delta = rec - offset;
      offset = rec;
      updateBytesCb(delta);
      continue;
    }
    offset += slice.size;
    updateBytesCb(slice.size);
  }

  return finalizeRequest(relPath, total, extraFields);
}

let currentUploadId = null;

function setUploadControlsDisabled(disabled) {
    for (const id of ['btnNormal', 'btnEmail', 'btnDefault', 'mailChoice']) {
        const element = document.getElementById(id);
        if (!element) continue;
        element.disabled = disabled;
        if (!disabled && element.dataset.originalText) {
            element.textContent = element.dataset.originalText;
            delete element.dataset.originalText;
            element.style.backgroundColor = '';
            element.style.color = '';
            element.style.cursor = '';
        }
    }
}

if (!window._cleanupRegistered) {
  window.addEventListener("beforeunload", () => {
    if (currentUploadId) {
	      const data = JSON.stringify({
	        csrf_token: csrfToken,
	        uploadId: currentUploadId,
	        cleanup: true
      });

      navigator.sendBeacon(
        "delete_emailadress.php",
        new Blob([data], { type: "application/json" })
      );
    }
  });
  window._cleanupRegistered = true;
}

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const uploadId = [...crypto.getRandomValues(new Uint8Array(8))].map(b => b.toString(16).padStart(2, '0')).join('');
        
    currentUploadId = uploadId;
    
    const mailChoiceEl = document.getElementById('mailChoice');
    const fixedMailChoice = mailChoiceEl ? mailChoiceEl.value : 'no';
    if (mailChoiceEl) mailChoiceEl.disabled = true;

    const uploaderEmail = form.querySelector('[name="uploader_email"]').value;
    const recipientEmail = form.querySelector('[name="recipient_email"]').value;
    
    let paths;
    try {
        const metadataResponse = await fetch('save_emailadress.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
	                uploadId,
	                uploader_email: uploaderEmail,
	                recipient_email: recipientEmail,
	                csrf_token: csrfToken
	            })
        });
        const metadataText = await metadataResponse.text();
        if (!metadataResponse.ok) {
            throw new Error(`Servererror ${metadataResponse.status}: ${metadataText}`);
        }
        paths = JSON.parse(document.getElementById('paths').value || '[]');
    } catch (err) {
        console.error(err);
        currentUploadId = null;
        setUploadControlsDisabled(false);
        alert(t.upload_error);
        return;
    }

    const mode = form.querySelector('[name="mode"]').value;
    const pw = form.querySelector('[name="pw"]').value;
    const progressBar = document.getElementById('progressBar');
    progressBar.style.display = 'block';
    const progressText = document.getElementById('progressText');
    progressText.style.display = 'block';

    let totalUploaded = 0;
    const totalBytes = files.reduce((sum, file) => sum + file.size, 0);
    const currentLang = document.documentElement.lang || 'de';

    const commonExtra = {
	        uploadId: uploadId,
	        csrf_token: csrfToken,
        pw: pw,
        mode: mode,
	        mailChoice: fixedMailChoice,
	        totalFiles: files.length,
	        directories: JSON.stringify(directories),
	        lang: currentLang
    };

	    let lastHtml = "";
	    if (files.length === 0 && directories.length > 0) {
	        const placeholderDir = directories[0];
	        const placeholder = new File([], '.dropzone-empty-folder-placeholder');
	        Object.defineProperty(placeholder, 'dropzoneRelativePath', {
	            value: placeholderDir + '/.dropzone-empty-folder-placeholder'
	        });
	        files = [placeholder];
	        paths = [placeholder.dropzoneRelativePath];
	        document.getElementById('paths').value = JSON.stringify(paths);
	        commonExtra.totalFiles = files.length;
	    }
	    
	    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const rawName = paths[i] || file.name;

        const name = rawName.replace(/^(\.\.[\/\\])+/, '').replace(/^\/+/, '');
        const relPath = name;

        try {
            const startAt = await statusRequest(relPath, file.size, { ...commonExtra, relativePath: rawName });

            const htmlOrText = await uploadFileResumable(
                file,
                relPath,
                startAt,
                (deltaBytes) => {
                    totalUploaded += deltaBytes;
                    if (totalUploaded > totalBytes) totalUploaded = totalBytes;
                    const percent = Math.min(100, Math.round((totalUploaded / totalBytes) * 100));
                    progressBar.value = percent;
                    const uploadedText = formatBytes(totalUploaded);
                    const totalText = formatBytes(totalBytes);
                    progressText.textContent = `${t.upload_text} ${uploadedText} / ${totalText} (${percent}%)`;
                    if (percent >= 100) {
                        progressText.style.display = 'none';
                        document.getElementById('uploadStatusText').textContent = t.creating_zip;
                        document.getElementById('uploadStatusText').style.display = 'block';
                    }
                },
                {
                    ...commonExtra,
                    relativePath: rawName
                }
            );
            
            if (typeof htmlOrText === "string") {
                lastHtml = htmlOrText;
            }

        } catch (err) {
            console.error(err);
            await fetch('delete_emailadress.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
	                            csrf_token: csrfToken,
	                            uploadId,
                            cleanup: true
                    })
            });
            
            currentUploadId = null;
            
            alert(t.upload_error);
            progressBar.style.display = 'none';
            progressText.style.display = 'none';
            document.getElementById('uploadStatusText').style.display = 'none';
            setUploadControlsDisabled(false);
            return;
        }
    }

    if (lastHtml) {
        document.getElementById('uploadStatusText').style.display = 'none';
        progressText.textContent = (t.upload_success || 'finished');
        progressText.style.display = 'block';
        document.getElementById('uploadResult').innerHTML = lastHtml;
        
        currentUploadId = null;
    }
});


// Dropzone handling
const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('fileInput');
const selectedFileDiv = document.getElementById('selectedFile');

function buildTreeWithSizes(files) {
    const tree = {};
    let totalSize = 0;
    const directorySet = new Set(directories);

    directories.forEach(dirPath => {
        const parts = String(dirPath || '').split('/').filter(Boolean);
        let current = tree;
        parts.forEach(part => {
            if (!current[part] || current[part].size !== undefined) {
                current[part] = {};
            }
            current = current[part];
        });
    });

    files.forEach(file => {
        const relativePath = getRelativePath(file);
        if (directorySet.has(relativePath) || looksLikeDroppedDirectoryFile(file)) {
            addDirectoryPath(relativePath);
            const parts = String(relativePath || '').split('/').filter(Boolean);
            let current = tree;
            parts.forEach(part => {
                if (!current[part] || current[part].size !== undefined) {
                    current[part] = {};
                }
                current = current[part];
            });
            return;
        }
        const parts = relativePath.split('/');
        let current = tree;
        parts.forEach((part, i) => {
            if (i === parts.length - 1) {
                if (!current[part] || current[part].size !== undefined) {
                    current[part] = { size: file.size };
                    totalSize += file.size;
                }
            } else {
                if (!current[part]) {
                    current[part] = {};
                }
                current = current[part];
            }
        });
    });
    return { tree, totalSize };
}

function getRelativePath(file) {
    return file.dropzoneRelativePath || file.webkitRelativePath || file.name;
}

function addDirectoryPath(path) {
    const normalized = String(path || '').replace(/\\/g, '/').replace(/^\/+|\/+$/g, '');
    if (normalized && !directories.includes(normalized)) {
        directories.push(normalized);
    }
}

function collectParentDirs(path) {
    const parts = String(path || '').replace(/\\/g, '/').split('/').filter(Boolean);
    for (let i = 1; i < parts.length; i++) {
        addDirectoryPath(parts.slice(0, i).join('/'));
    }
}

function looksLikeDroppedDirectoryFile(file) {
    return file
        && file.size === 0
        && !String(file.name || '').includes('.');
}

function renderTree(tree) {
    let html = '<ul>';
    const keys = Object.keys(tree).sort((a, b) => a.localeCompare(b, 'de', { numeric: true }));

    const folders = keys.filter(k => tree[k] && tree[k].size === undefined);
    const files   = keys.filter(k => tree[k] && tree[k].size !== undefined);

    for (const key of folders) {
        html += `<li>📁 ${escapeHtml(key)}${renderTree(tree[key])}</li>`;
    }

    for (const key of files) {
        const size = formatBytes(tree[key].size);
        html += `<li>📄 ${escapeHtml(key)} (${size})</li>`;
    }

    html += '</ul>';
    return html;
}

function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    })[char]);
}

function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0.00 B';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return (bytes / Math.pow(k, i)).toFixed(dm) + ' ' + sizes[i];
}

async function checkFreeSpace(totalSize) {
    try {
        const res = await fetch("check_disk_space.php");
        if (!res.ok) {
            throw new Error(`Servererror ${res.status}`);
        }
        const data = await res.json();
        const free = data.free || 0;

        const required = (totalSize * 2) + (10 * 1024 * 1024);
        if (required > free) {
            alert(`${t.diskspace_error}\n${t.diskspace_required} ${formatBytes(totalSize)}\n${t.diskspace_free} ${formatBytes(free)}`);
            return false;
        } else {
            return true;
        }
    } catch (e) {
        alert(`${t.diskspace_check_failed}`);
        return true;
    }
}

async function updateSelectedFile() {
    if (files.length > 0 || directories.length > 0) {
        const fileArr = files;
        const { tree, totalSize } = buildTreeWithSizes(fileArr);
        const totalSizeText = `<div style="margin-top:10px;">📦 ${formatBytes(totalSize)}</div>`;

        selectedFileDiv.innerHTML =
            (fileArr.length !== 1 || directories.length > 0 ? t.selected_files_plural : t.selected_files) +
            ":" + renderTree(tree) + totalSizeText;
        selectedFileDiv.classList.add("shown");
        
        const ok = await checkFreeSpace(totalSize);
	        if (!ok) {
	            fileInput.value = "";
	            files = [];
	            directories = [];
	            document.getElementById('directories').value = JSON.stringify(directories);
	            selectedFileDiv.innerHTML = "";
            selectedFileDiv.classList.remove("shown");
        }
    } else {
        selectedFileDiv.innerHTML = "";
        selectedFileDiv.classList.remove("shown");
    }
}

async function traverseFileTree(item, path = '', fileList = []) {
	    if (item.isFile) {
	        await new Promise(resolve => {
	            item.file(file => {
	                const relativePath = path + file.name;
	                if (looksLikeDroppedDirectoryFile(file)) {
	                    addDirectoryPath(relativePath);
	                    resolve();
	                    return;
	                }
	                const fileWithPath = new File([file], file.name, {
	                    type: file.type,
	                    lastModified: file.lastModified
                });
                Object.defineProperty(fileWithPath, 'dropzoneRelativePath', {
                    value: relativePath
                });
                fileList.push(fileWithPath);
                resolve();
            });
        });
    } else if (item.isDirectory) {
        const directoryPath = path + item.name;
        addDirectoryPath(directoryPath);
        const reader = item.createReader();
        while (true) {
            const entries = await new Promise((resolve, reject) => {
                reader.readEntries(resolve, reject);
            });
            if (entries.length === 0) break;
            for (const entry of entries) {
                await traverseFileTree(entry, directoryPath + '/', fileList);
            }
        }
    }
}

async function traverseFileSystemHandle(handle, path = '', fileList = []) {
    if (handle.kind === 'file') {
        const file = await handle.getFile();
        const relativePath = path + file.name;
        const fileWithPath = new File([file], file.name, {
            type: file.type,
            lastModified: file.lastModified
        });
        Object.defineProperty(fileWithPath, 'dropzoneRelativePath', {
            value: relativePath
        });
        fileList.push(fileWithPath);
        collectParentDirs(relativePath);
        return;
    }

    if (handle.kind === 'directory') {
        const directoryPath = path + handle.name;
        addDirectoryPath(directoryPath);
        for await (const child of handle.values()) {
            await traverseFileSystemHandle(child, directoryPath + '/', fileList);
        }
    }
}

dropzone.addEventListener('click', () => fileInput.click());

fileInput.addEventListener('change', () => {
    files = Array.from(fileInput.files);
    directories = [];
    files.forEach(file => collectParentDirs(getRelativePath(file)));
    document.getElementById('paths').value = JSON.stringify(files.map(getRelativePath));
    document.getElementById('directories').value = JSON.stringify(directories);
    updateSelectedFile();
});

dropzone.addEventListener('dragover', e => {
    e.preventDefault();
    dropzone.classList.add('dragover');
});

dropzone.addEventListener('dragleave', () => {
    dropzone.classList.remove('dragover');
});

dropzone.addEventListener('drop', async (e) => {
    e.preventDefault();
    dropzone.classList.remove('dragover');

    const dtItems = e.dataTransfer.items;
    const dtFiles = e.dataTransfer.files;

    files = [];
    directories = [];

    if (dtItems && dtItems.length > 0) {
        const entriesProcessed = [];

        for (let i = 0; i < dtItems.length; i++) {
            const dataItem = dtItems[i];
            if (typeof dataItem.getAsFileSystemHandle === 'function') {
                entriesProcessed.push(
                    dataItem.getAsFileSystemHandle().then(handle => {
                        if (handle) {
                            return traverseFileSystemHandle(handle, '', files);
                        }
                    })
                );
                continue;
            }

            const item = dataItem.webkitGetAsEntry?.();
            if (item) {
                entriesProcessed.push(traverseFileTree(item, '', files));
                continue;
            }

            const droppedFile = dataItem.kind === 'file' ? dataItem.getAsFile() : null;
            if (looksLikeDroppedDirectoryFile(droppedFile)) {
                addDirectoryPath(droppedFile.name);
            }
        }

        await Promise.all(entriesProcessed);
    }

    if (files.length === 0 && dtFiles.length > 0) {
        files = Array.from(dtFiles).filter(file => {
            if (looksLikeDroppedDirectoryFile(file)) {
                addDirectoryPath(file.name);
                return false;
            }
            return true;
        });
    }

    files.forEach(file => collectParentDirs(getRelativePath(file)));

    const dataTransfer = new DataTransfer();
    for (const file of files) {
        dataTransfer.items.add(file);
    }
    fileInput.files = dataTransfer.files;

    document.getElementById('paths').value = JSON.stringify(files.map(getRelativePath));
    document.getElementById('directories').value = JSON.stringify(directories);
    updateSelectedFile();
});
